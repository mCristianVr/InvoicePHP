# Hetzner Deployment with GitHub Actions

This project deploys automatically from GitHub Actions on every push to main.

## 1. Required GitHub Secrets

Create these repository secrets:

- PROD_HOST: server IP or hostname
- PROD_SSH_PORT: usually 22
- PROD_SSH_USER: deploy user on VPS
- PROD_SSH_PRIVATE_KEY: private key that matches deploy user's authorized key
- PROD_KNOWN_HOSTS: output of ssh-keyscan for your host

Generate known hosts line locally:

```bash
ssh-keyscan -p 22 your-server-ip
```

## 2. One-time VPS layout

Run once on the server:

```bash
sudo mkdir -p /var/www/invoicephp/releases
sudo mkdir -p /var/www/invoicephp/shared
sudo chown -R deploy:deploy /var/www/invoicephp
```

## 3. Production environment file

Create shared environment file that survives releases:

```bash
nano /var/www/invoicephp/shared/.env.local
```

Example:

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=replace_with_a_random_secret
DATABASE_URL="postgresql://invoicephp_user:replace_password@127.0.0.1:5432/invoicephp_prod?serverVersion=16&charset=utf8"
MAILER_DSN=null://null
```

## 4. Nginx document root

Point Nginx root to:

- /var/www/invoicephp/current/public

The deploy workflow updates the current symlink atomically.

## 5. What the workflow does

On push to main:

1. Runs CI verification (composer validation, platform checks, container lint, doctrine mapping, deployment tests).
2. Creates a release directory under /var/www/invoicephp/releases/<commit-sha>.
3. Uploads project files via rsync.
4. Links shared .env.local.
5. Runs production install/build/migration commands.
6. Switches current symlink to new release.
7. Keeps only the last 5 releases.

## 6. First deployment

Push to main or trigger workflow_dispatch in Actions.

After completion:

```bash
ls -la /var/www/invoicephp/current
php -v
```

Then open your domain.

## 7. Rollback procedure

List releases:

```bash
ls -1dt /var/www/invoicephp/releases/*
```

Switch to previous release:

```bash
ln -sfn /var/www/invoicephp/releases/<previous-sha> /var/www/invoicephp/current
```

Reload PHP-FPM/Nginx if needed.
