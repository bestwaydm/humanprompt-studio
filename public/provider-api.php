<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';
require_file('app/Repositories/JsonRepository.php');
require_file('app/Repositories/ProviderVaultRepository.php');
require_file('app/Services/EncryptionService.php');
require_file('app/Services/AiProviderService.php');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; connect-src \'self\'; base-uri \'self\'; form-action \'self\'; frame-ancestors \'self\'');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

verify_csrf_header();
rate_limit_or_fail(60, 60);

$raw = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Invalid JSON payload.'], 422);
}

$config = require dirname(__DIR__) . '/config/app.php';
$action = sanitize_key((string)($input['action'] ?? ''));

if ($action === 'setup') {
    if (vault_is_configured($config)) {
        json_response(['ok' => false, 'error' => 'Provider Vault is already configured.'], 409);
    }

    $password = trim((string)($input['password'] ?? ''));
    $secret = trim((string)($input['secret'] ?? ''));

    if (strlen($password) < 6) {
        json_response(['ok' => false, 'error' => 'Vault password must be at least 6 characters.'], 422);
    }

    if ($secret === '') {
        $secret = 'hpstudio_' . bin2hex(random_bytes(32));
    }

    if (strlen($secret) < 32) {
        json_response(['ok' => false, 'error' => 'Encryption secret must be at least 32 characters.'], 422);
    }

    $saved = upsert_env_values([
        'APP_ENV' => (string)env_value('APP_ENV', 'local'),
        'APP_DEBUG' => (string)env_value('APP_DEBUG', 'true'),
        'HUMANPROMPT_SECRET_KEY' => $secret,
        'HUMANPROMPT_ADMIN_PASSWORD_HASH' => password_hash($password, PASSWORD_DEFAULT),
        'HUMANPROMPT_DEFAULT_PROVIDER' => (string)env_value('HUMANPROMPT_DEFAULT_PROVIDER', 'local_rule_engine'),
    ]);

    if (!$saved) {
        json_response(['ok' => false, 'error' => 'Could not write .env file. Check folder permissions.'], 500);
    }

    session_regenerate_id(true);
    $_SESSION['provider_vault_unlocked'] = true;

    json_response([
        'ok' => true,
        'data' => [
            'message' => 'Provider Vault configured and unlocked.',
            'env_path' => env_file_path(),
        ],
    ]);
}

$adminHash = (string)($config['provider_vault']['admin_password_hash'] ?? '');
$encryption = new EncryptionService((string)($config['provider_vault']['encryption_secret'] ?? ''));
$repo = new JsonRepository();
$vault = new ProviderVaultRepository($repo, $encryption);

if ($action === 'status') {
    json_response([
        'ok' => true,
        'data' => [
            'unlocked' => is_admin_unlocked(),
            'encryption_ready' => $encryption->isReady(),
            'password_ready' => $adminHash !== '',
            'needs_setup' => !$encryption->isReady() || $adminHash === '',
            'env_path' => env_file_path(),
        ],
    ]);
}

if ($action === 'unlock') {
    if (!$encryption->isReady() || $adminHash === '') {
        json_response([
            'ok' => false,
            'error' => 'Provider Vault is not configured. Use the setup box first, then add your API key and model name.',
        ], 422);
    }

    $password = (string)($input['password'] ?? '');
    if (!password_verify($password, $adminHash)) {
        json_response(['ok' => false, 'error' => 'Invalid vault password.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['provider_vault_unlocked'] = true;
    json_response(['ok' => true, 'data' => ['message' => 'Provider Vault unlocked.']]);
}

if ($action === 'logout') {
    unset($_SESSION['provider_vault_unlocked']);
    json_response(['ok' => true, 'data' => ['message' => 'Provider Vault locked.']]);
}

require_admin_unlocked();


function live_provider_test(ProviderVaultRepository $vault, string $key): array
{
    $key = sanitize_key($key);
    $localCheck = $vault->testProvider($key);
    if (($localCheck['status'] ?? '') !== 'ready') {
        return $localCheck;
    }

    if ($key === 'local_rule_engine') {
        return $localCheck;
    }

    $service = new AiProviderService($vault);
    $draft = [
        'improved_prompt' => 'Provider health check. Reply with a short valid JSON prompt response.',
        'short_prompt' => 'Provider health check.',
        'negative_prompt' => 'No generic output.',
        'english_prompt' => 'Provider health check.',
        'why_stronger' => 'Testing live provider connectivity.',
        'warning' => '',
        'score' => [
            'goal_clarity' => 8,
            'style_strength' => 8,
            'anti_ai_strength' => 8,
            'output_specificity' => 8,
            'execution_readiness' => 8,
        ],
        'settings' => [
            'prompt_type' => 'planning',
            'domain' => 'provider health check',
            'market' => 'global',
            'language' => 'Auto',
            'format' => 'JSON health check',
            'style' => 'technical',
            'platform' => 'provider-api',
        ],
        'provider' => $service->resolve($key),
    ];

    $result = $service->generate($key, [
        'prompt' => 'Live provider health check. Return a short valid JSON response for HumanPrompt Studio.',
        'type' => 'planning',
        'style' => 'auto',
        'platform' => 'general',
        'brand_profile' => 'general_project',
        'market' => 'global',
        'language' => 'Auto',
        'anti_ai' => true,
    ], $draft);

    if (!empty($result['used_external'])) {
        return [
            'status' => 'ready',
            'message' => 'Live API call succeeded. This provider is ready for real prompt generation.',
        ];
    }

    return [
        'status' => 'error',
        'message' => (string)($result['note'] ?? 'Live provider test failed.'),
    ];
}

try {
    match ($action) {
        'list' => json_response(['ok' => true, 'data' => $vault->publicList()]),
        'save' => json_response(['ok' => true, 'data' => $vault->upsert($input['provider'] ?? [])]),
        'delete' => json_response(['ok' => true, 'data' => ['deleted' => $vault->delete((string)($input['key'] ?? ''))]]),
        'set_default' => json_response(['ok' => true, 'data' => ['saved' => $vault->setDefault((string)($input['key'] ?? ''))]]),
        'test' => json_response(['ok' => true, 'data' => live_provider_test($vault, (string)($input['key'] ?? ''))]),
        default => json_response(['ok' => false, 'error' => 'Unknown action.'], 422),
    };
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
