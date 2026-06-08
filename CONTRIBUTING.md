# Contributing

Thank you for considering contributing to HumanPrompt Studio.

## Development rules

- Do not commit `.env`.
- Do not commit real API keys.
- Do not commit `data/ai_providers.json`.
- Do not commit `data/prompt_history.json` if it contains user prompts.
- Keep examples generic and safe.
- Keep UI labels clear for non-technical users.
- Preserve Provider Vault encryption behavior.
- Preserve Local Rule Engine fallback.

## Local check

```bash
composer run check
```

Or:

```bash
find app config public -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Pull request checklist

- [ ] PHP syntax check passes.
- [ ] No secrets are committed.
- [ ] README/docs are updated if behavior changed.
- [ ] UI still works without API keys.
- [ ] Provider Vault still works.
- [ ] Local fallback still works.
