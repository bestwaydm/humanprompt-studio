<?php

return [
    'name' => 'HumanPrompt Studio',
    'version' => '1.0.0-output-controls-spellcheck',
    'default_market' => 'Global',
    'default_language' => 'English',
    'history_limit' => 200,
    'provider_vault' => [
        'enabled' => true,
        'default_runtime_provider' => env_value('HUMANPROMPT_DEFAULT_PROVIDER', 'local_rule_engine'),
        'admin_password_hash' => env_value('HUMANPROMPT_ADMIN_PASSWORD_HASH', ''),
        'encryption_secret' => env_value('HUMANPROMPT_SECRET_KEY', ''),
    ],
];
