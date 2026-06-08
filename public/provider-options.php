<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';
require_file('app/Repositories/JsonRepository.php');
require_file('app/Repositories/ProviderVaultRepository.php');
require_file('app/Services/EncryptionService.php');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$config = require dirname(__DIR__) . '/config/app.php';
$repo = new JsonRepository();
$encryption = new EncryptionService((string)($config['provider_vault']['encryption_secret'] ?? ''));
$vault = new ProviderVaultRepository($repo, $encryption);

json_response([
    'ok' => true,
    'data' => [
        'options' => $vault->enabledOptions(),
        'vault_ready' => $encryption->isReady() && (string)($config['provider_vault']['admin_password_hash'] ?? '') !== '',
    ],
]);
