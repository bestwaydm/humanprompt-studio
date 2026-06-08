# Installation

## Requirements

- PHP 8.1+
- `openssl` extension for encrypted API keys
- `curl` extension for live AI provider calls
- `json` extension
- Web server: Apache, Nginx, XAMPP, Laragon, or PHP built-in server

## Local installation

```bash
git clone https://github.com/YOUR_USERNAME/humanprompt-studio.git
cd humanprompt-studio
cp .env.example .env
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000
```

## XAMPP installation

Copy the project folder to:

```text
C:\xampp\htdocs\humanprompt-studio
```

Open:

```text
http://localhost/humanprompt-studio/public/
```

Provider Vault:

```text
http://localhost/humanprompt-studio/public/providers.php
```

## Shared hosting installation

Recommended layout:

```text
account-root/
  humanprompt-studio/
    app/
    config/
    data/
    storage/
    .env
  public_html/
    index.php
    api.php
    providers.php
    assets/
```

If your host does not allow moving files outside `public_html`, upload the whole project but protect `data/`, `storage/`, and `.env`. The repository includes `.htaccess` files to deny direct access for Apache, but the safest setup is still to expose only `public/`.

## Production environment

Set:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

Then configure the Provider Vault from `/providers.php`.
