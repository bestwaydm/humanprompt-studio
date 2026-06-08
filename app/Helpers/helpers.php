<?php

declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_path(string $path = ''): string
{
    return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

function data_path(string $path = ''): string
{
    return app_path('data' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function storage_path(string $path = ''): string
{
    return app_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function require_file(string $relativePath): void
{
    require_once app_path($relativePath);
}

function load_env_file(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $path = app_path('.env');
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
        $_ENV[$key] ??= $value;
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    load_env_file();

    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $lower = is_string($value) ? strtolower($value) : $value;
    return match ($lower) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}

function ensure_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_header(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token.'], 419);
    }
}

function rate_limit_or_fail(int $limit = 30, int $windowSeconds = 60): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $now = time();
    $_SESSION['rate_window_started_at'] ??= $now;
    $_SESSION['rate_count'] ??= 0;

    if (($now - (int) $_SESSION['rate_window_started_at']) > $windowSeconds) {
        $_SESSION['rate_window_started_at'] = $now;
        $_SESSION['rate_count'] = 0;
    }

    $_SESSION['rate_count']++;

    if ($_SESSION['rate_count'] > $limit) {
        json_response(['ok' => false, 'error' => 'Too many requests. Please try again later.'], 429);
    }
}

function app_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function app_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function sanitize_key(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9_\-]/', '_', $value) ?? '';
    return trim($value, '_-');
}

function is_admin_unlocked(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return !empty($_SESSION['provider_vault_unlocked']) && $_SESSION['provider_vault_unlocked'] === true;
}

function require_admin_unlocked(): void
{
    if (!is_admin_unlocked()) {
        json_response(['ok' => false, 'error' => 'Provider Vault is locked. Please unlock it first.'], 401);
    }
}


function env_file_path(): string
{
    return app_path('.env');
}

function upsert_env_values(array $values): bool
{
    $path = env_file_path();
    $existing = [];
    if (is_file($path) && is_readable($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines !== false) {
            $existing = $lines;
        }
    } elseif (is_file(app_path('.env.example')) && is_readable(app_path('.env.example'))) {
        $lines = file(app_path('.env.example'), FILE_IGNORE_NEW_LINES);
        if ($lines !== false) {
            $existing = $lines;
        }
    }

    $used = [];
    $output = [];
    foreach ($existing as $line) {
        $trimmed = trim((string)$line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            $output[] = (string)$line;
            continue;
        }

        [$key] = explode('=', $trimmed, 2);
        $key = trim($key);
        if (array_key_exists($key, $values)) {
            $output[] = $key . '=' . (string)$values[$key];
            $used[$key] = true;
            putenv($key . '=' . (string)$values[$key]);
            $_ENV[$key] = (string)$values[$key];
        } else {
            $output[] = (string)$line;
        }
    }

    foreach ($values as $key => $value) {
        if (!isset($used[$key])) {
            $output[] = $key . '=' . (string)$value;
            putenv($key . '=' . (string)$value);
            $_ENV[$key] = (string)$value;
        }
    }

    $content = implode(PHP_EOL, $output) . PHP_EOL;
    return file_put_contents($path, $content, LOCK_EX) !== false;
}

function vault_is_configured(array $config): bool
{
    $secret = (string)($config['provider_vault']['encryption_secret'] ?? '');
    $hash = (string)($config['provider_vault']['admin_password_hash'] ?? '');
    return strlen($secret) >= 32 && $hash !== '';
}
