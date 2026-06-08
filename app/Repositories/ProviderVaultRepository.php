<?php

declare(strict_types=1);

final class ProviderVaultRepository
{
    public function __construct(private JsonRepository $repo, private EncryptionService $encryption)
    {
    }

    public function vault(): array
    {
        $vault = $this->repo->read('ai_providers.json', []);
        if (!$vault) {
            $vault = [
                'default_provider' => 'local_rule_engine',
                'providers' => [],
                'updated_at' => gmdate('c'),
            ];
        }

        $vault['providers'] ??= [];
        $vault['default_provider'] ??= 'local_rule_engine';
        return $vault;
    }

    public function publicList(): array
    {
        $vault = $this->vault();
        $providers = [];

        foreach (($vault['providers'] ?? []) as $key => $provider) {
            $providers[$key] = $this->safeProvider($provider);
        }

        return [
            'default_provider' => $vault['default_provider'] ?? 'local_rule_engine',
            'providers' => $providers,
            'updated_at' => $vault['updated_at'] ?? null,
        ];
    }

    public function enabledOptions(): array
    {
        $vault = $this->vault();
        $options = [
            [
                'key' => 'local_rule_engine',
                'name' => 'Local Rule Engine',
                'type' => 'local',
                'default_model' => 'built-in',
                'is_default' => ($vault['default_provider'] ?? 'local_rule_engine') === 'local_rule_engine',
            ],
        ];

        foreach (($vault['providers'] ?? []) as $key => $provider) {
            if (empty($provider['enabled'])) {
                continue;
            }

            $options[] = [
                'key' => $key,
                'name' => $provider['name'] ?? $key,
                'type' => $provider['type'] ?? 'custom',
                'default_model' => $provider['default_model'] ?? '',
                'is_default' => ($vault['default_provider'] ?? 'local_rule_engine') === $key,
            ];
        }

        return $options;
    }

    public function upsert(array $input): array
    {
        $key = sanitize_key((string)($input['key'] ?? ''));
        if ($key === '') {
            throw new InvalidArgumentException('Provider key is required.');
        }

        $vault = $this->vault();
        $existing = $vault['providers'][$key] ?? [];
        $apiKey = trim((string)($input['api_key'] ?? ''));

        $provider = [
            'key' => $key,
            'name' => trim((string)($input['name'] ?? '')) ?: ucfirst(str_replace('_', ' ', $key)),
            'type' => sanitize_key((string)($input['type'] ?? ($existing['type'] ?? 'custom'))) ?: 'custom',
            'enabled' => filter_var($input['enabled'] ?? true, FILTER_VALIDATE_BOOL),
            'base_url' => trim((string)($input['base_url'] ?? ($existing['base_url'] ?? ''))),
            'default_model' => trim((string)($input['default_model'] ?? ($existing['default_model'] ?? ''))),
            'capabilities' => $this->normalizeCapabilities($input['capabilities'] ?? ($existing['capabilities'] ?? [])),
            'priority' => max(0, min(999, (int)($input['priority'] ?? ($existing['priority'] ?? 100)))),
            'rate_limit_per_minute' => max(1, min(1000, (int)($input['rate_limit_per_minute'] ?? ($existing['rate_limit_per_minute'] ?? 30)))),
            'cost_guard_daily_usd' => max(0, (float)($input['cost_guard_daily_usd'] ?? ($existing['cost_guard_daily_usd'] ?? 3))),
            'updated_at' => gmdate('c'),
        ];

        if ($apiKey !== '') {
            $provider['api_key_encrypted'] = $this->encryption->encrypt($apiKey);
            $provider['api_key_hint'] = EncryptionService::keyHint($apiKey);
            $provider['has_api_key'] = true;
        } else {
            $provider['api_key_encrypted'] = $existing['api_key_encrypted'] ?? '';
            $provider['api_key_hint'] = $existing['api_key_hint'] ?? '';
            $provider['has_api_key'] = !empty($provider['api_key_encrypted']);
        }

        $vault['providers'][$key] = $provider;

        if (!empty($input['make_default'])) {
            $vault['default_provider'] = $key;
        }

        $vault['updated_at'] = gmdate('c');
        $this->repo->write('ai_providers.json', $vault);

        return $this->safeProvider($provider);
    }

    public function delete(string $key): bool
    {
        $key = sanitize_key($key);
        $vault = $this->vault();
        unset($vault['providers'][$key]);
        if (($vault['default_provider'] ?? '') === $key) {
            $vault['default_provider'] = 'local_rule_engine';
        }
        $vault['updated_at'] = gmdate('c');
        return $this->repo->write('ai_providers.json', $vault);
    }

    public function setDefault(string $key): bool
    {
        $key = sanitize_key($key);
        $vault = $this->vault();

        if ($key !== 'local_rule_engine' && empty($vault['providers'][$key])) {
            throw new InvalidArgumentException('Provider not found.');
        }

        $vault['default_provider'] = $key;
        $vault['updated_at'] = gmdate('c');
        return $this->repo->write('ai_providers.json', $vault);
    }

    public function getDecryptedKey(string $key): string
    {
        $key = sanitize_key($key);
        $vault = $this->vault();
        $payload = $vault['providers'][$key]['api_key_encrypted'] ?? '';
        if ($payload === '') {
            return '';
        }

        return $this->encryption->decrypt($payload);
    }

    public function testProvider(string $key): array
    {
        $key = sanitize_key($key);
        if ($key === 'local_rule_engine') {
            return [
                'status' => 'ready',
                'message' => 'Local Rule Engine is available and does not need an API key.',
            ];
        }

        $vault = $this->vault();
        $provider = $vault['providers'][$key] ?? null;
        if (!$provider) {
            return ['status' => 'error', 'message' => 'Provider was not found.'];
        }

        $errors = [];
        if (empty($provider['enabled'])) {
            $errors[] = 'Provider is disabled.';
        }
        if (empty($provider['api_key_encrypted'])) {
            $errors[] = 'API key is missing.';
        }
        if (empty($provider['default_model'])) {
            $errors[] = 'Default model is missing.';
        }
        if (($provider['type'] ?? '') !== 'custom' && empty($provider['base_url'])) {
            $errors[] = 'Base URL is missing.';
        }

        if ($errors) {
            return ['status' => 'warning', 'message' => implode(' ', $errors)];
        }

        try {
            $keyValue = $this->getDecryptedKey($key);
            if ($keyValue === '') {
                return ['status' => 'error', 'message' => 'Could not decrypt the API key.'];
            }
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Decryption test failed: ' . $e->getMessage()];
        }

        return [
            'status' => 'ready',
            'message' => 'Provider configuration is valid locally. Live API generation is enabled from the main Transform Prompt form.',
        ];
    }

    private function safeProvider(array $provider): array
    {
        unset($provider['api_key_encrypted']);
        $provider['has_api_key'] = !empty($provider['api_key_hint']);
        return $provider;
    }

    private function normalizeCapabilities(array|string $capabilities): array
    {
        if (is_string($capabilities)) {
            $capabilities = array_map('trim', explode(',', $capabilities));
        }

        $allowed = ['text', 'code', 'image', 'video', 'vision', 'embedding', 'audio'];
        $clean = [];
        foreach ($capabilities as $capability) {
            $capability = sanitize_key((string)$capability);
            if ($capability !== '' && in_array($capability, $allowed, true)) {
                $clean[] = $capability;
            }
        }

        return array_values(array_unique($clean ?: ['text']));
    }
}
