# Provider Vault

The Provider Vault stores AI provider settings and encrypted API keys.

## Files

```text
.env
data/ai_providers.json
```

Both are ignored by Git.

## First setup

Open:

```text
/providers.php
```

Create the vault:

1. Enter a vault password.
2. Leave encryption secret empty to auto-generate it.
3. Click **Create Vault**.
4. Unlock the vault using the password.

The app writes:

```env
HUMANPROMPT_SECRET_KEY=...
HUMANPROMPT_ADMIN_PASSWORD_HASH=...
```

## Add a provider

1. Choose a provider preset.
2. Paste API Key.
3. Enter Model Name.
4. Enable provider.
5. Save.
6. Optionally set as default.

## Important rules

- Do not commit `.env`.
- Do not commit `data/ai_providers.json`.
- Do not change `HUMANPROMPT_SECRET_KEY` after saving keys.
- If the live provider fails, the app falls back to Local Rule Engine.

## Example model names

OpenRouter:

```text
openai/gpt-4o-mini
anthropic/claude-3.5-sonnet
qwen/qwen-2.5-72b-instruct
```

DeepSeek:

```text
deepseek-chat
```

Kimi / Moonshot:

```text
kimi-latest
```

DeepInfra:

```text
Qwen/Qwen3-32B
meta-llama/Meta-Llama-3.1-8B-Instruct
```
