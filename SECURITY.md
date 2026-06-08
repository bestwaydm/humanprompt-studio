# Security Policy

## Supported versions

The latest release is supported.

## Reporting a vulnerability

Please do not open a public issue with sensitive vulnerability details.

Send a private report to the repository owner, or open a minimal issue that says a security report is available without disclosing secrets, exploit details, or real API keys.

## Security design

HumanPrompt Studio uses:

- `.env` for secrets
- encrypted provider keys
- API key hints instead of displaying full keys
- CSRF protection for POST requests
- session-based vault unlock
- local fallback when live provider calls fail
- `.gitignore` protection for runtime secrets

## Important deployment notes

- Serve the app from `public/` when possible.
- Never expose `.env` to the web.
- Never expose `data/ai_providers.json`.
- Never expose `storage/`.
- Set `APP_DEBUG=false` in production.
- Use HTTPS in production.
