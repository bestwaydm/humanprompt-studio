<?php

declare(strict_types=1);

session_start();
require_once dirname(__DIR__) . '/app/Helpers/helpers.php';
$csrfToken = ensure_csrf_token();
$config = require dirname(__DIR__) . '/config/app.php';
?>
<!doctype html>
<html lang="en" dir="ltr" data-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h($csrfToken) ?>">
    <title>Provider Vault — <?= h($config['name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <main class="shell">
        <section class="hero card compact-hero">
            <div>
                <p class="eyebrow">Easy Encrypted Provider Vault</p>
                <h1>Add provider, API key, and model name.</h1>
                <p class="lead">No confusing provider keys. Choose a provider, paste the API key, write the model name, and save. Secrets are encrypted before JSON storage.</p>
            </div>
            <div class="hero-panel">
                <a class="btn btn-secondary" href="index.php">Back to Studio</a>
                <button class="btn btn-ghost" type="button" id="lockBtn">Lock Vault</button>
            </div>
        </section>

        <section id="setupNotice" class="card notice-card" hidden>
            <h2>First-time setup</h2>
            <p class="muted">Create the vault password once. The app will generate the encryption secret automatically and write both values to <code>.env</code>.</p>
            <div class="form-grid compact-form-grid">
                <div>
                    <label for="setupPassword">New vault password</label>
                    <input id="setupPassword" type="password" placeholder="Example: 465464" autocomplete="new-password">
                </div>
                <div>
                    <label for="setupSecret">Encryption secret — optional</label>
                    <input id="setupSecret" placeholder="Leave empty to auto-generate">
                </div>
            </div>
            <div class="actions mt-16">
                <button class="btn btn-primary" type="button" id="setupVaultBtn">Create Vault</button>
            </div>
            <p class="muted mt-16">After setup, use the same password to unlock this page. Do not change <code>HUMANPROMPT_SECRET_KEY</code> after saving API keys.</p>
        </section>

        <section id="unlockPanel" class="card form-card vault-panel">
            <h2>Unlock Vault</h2>
            <p class="muted">Enter the vault password you created during setup.</p>
            <label for="vaultPassword">Vault password</label>
            <input id="vaultPassword" type="password" autocomplete="current-password" placeholder="Enter vault password">
            <div class="actions mt-16">
                <button class="btn btn-primary" id="unlockBtn" type="button">Unlock</button>
            </div>
        </section>

        <section id="vaultApp" class="grid vault-grid" hidden>
            <form id="providerForm" class="card form-card">
                <h2>Quick Provider Setup</h2>
                <p class="muted">Required fields only: provider, model name, and API key. Advanced fields are filled automatically and can be edited if needed.</p>

                <input id="providerKey" type="hidden">
                <input id="providerName" type="hidden">
                <input id="providerType" type="hidden">

                <div class="form-grid">
                    <div>
                        <label for="providerPreset">Provider</label>
                        <select id="providerPreset">
                            <option value="openrouter">OpenRouter</option>
                            <option value="openai">OpenAI</option>
                            <option value="deepseek">DeepSeek</option>
                            <option value="kimi">Kimi / Moonshot AI</option>
                            <option value="deepinfra">DeepInfra</option>
                            <option value="together">Together AI</option>
                            <option value="groq">Groq</option>
                            <option value="mistral">Mistral AI</option>
                            <option value="perplexity">Perplexity</option>
                            <option value="novita">Novita AI</option>
                            <option value="fireworks">Fireworks AI</option>
                            <option value="cerebras">Cerebras</option>
                            <option value="gemini">Gemini</option>
                            <option value="claude">Claude</option>
                            <option value="grok">Grok / xAI</option>
                            <option value="custom">Custom Provider</option>
                        </select>
                    </div>
                    <div>
                        <label for="providerModel">Model name</label>
                        <input id="providerModel" placeholder="Example: openai/gpt-4o-mini or gpt-4.1-mini" required>
                    </div>
                    <div class="full-field">
                        <label for="providerApiKey">API key</label>
                        <input id="providerApiKey" type="password" autocomplete="new-password" placeholder="Paste the provider API key here">
                    </div>
                </div>

                <details class="advanced-box">
                    <summary>Advanced settings</summary>
                    <div class="form-grid mt-16">
                        <div>
                            <label for="providerBaseUrl">Base URL</label>
                            <input id="providerBaseUrl" placeholder="https://api.example.com/v1">
                        </div>
                        <div>
                            <label for="providerCapabilities">Capabilities</label>
                            <input id="providerCapabilities" value="text, code" placeholder="text, code, image, video">
                        </div>
                        <div>
                            <label for="providerPriority">Priority</label>
                            <input id="providerPriority" type="number" min="0" max="999" value="100">
                        </div>
                        <div>
                            <label for="providerRateLimit">Rate limit / minute</label>
                            <input id="providerRateLimit" type="number" min="1" max="1000" value="30">
                        </div>
                        <div>
                            <label for="providerCostGuard">Daily cost guard USD</label>
                            <input id="providerCostGuard" type="number" min="0" step="0.01" value="3">
                        </div>
                    </div>
                </details>

                <label class="check-row">
                    <input id="providerEnabled" type="checkbox" checked>
                    <span>Provider enabled</span>
                </label>
                <label class="check-row">
                    <input id="providerDefault" type="checkbox" checked>
                    <span>Set as default provider</span>
                </label>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Save Provider</button>
                    <button class="btn btn-secondary" type="button" id="newProviderBtn">Clear</button>
                </div>
            </form>

            <section class="card result-card">
                <div class="result-header">
                    <div>
                        <p class="eyebrow">Safe list</p>
                        <h2>Saved Providers</h2>
                    </div>
                    <button class="btn btn-secondary" type="button" id="refreshProvidersBtn">Refresh</button>
                </div>
                <div id="vaultStatus" class="status">Ready.</div>
                <div id="providersList" class="providers-list"></div>
            </section>
        </section>
    </main>

    <script src="assets/js/providers.js"></script>
</body>
</html>
