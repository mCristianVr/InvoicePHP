# InvoicePHP Runbook

This page is the quick operational guide for this project.

## 1. Current Baseline

- Stack: Symfony 8.1, PHP 8.5, PostgreSQL 16, Doctrine ORM/Migrations, Tailwind.
- Domain: invoicing for Spanish freelancers with immutable finalized invoices and hash chaining foundations.
- Deploy model: GitHub Actions -> Hetzner VPS -> release folders -> current symlink.

## 2. Daily Operator Checklist (5 minutes)

Run on VPS:

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.5-fpm --no-pager
sudo systemctl status postgresql --no-pager
```

Run from app path:

```bash
cd /var/www/invoicephp/current
php bin/console about --env=prod
php bin/console doctrine:query:sql "SELECT 1" --env=prod
```

Expected:

- Services active.
- Symfony boots in prod.
- DB query returns success.

## 3. Deploy Checklist

Before pushing:

```bash
php bin/phpunit tests/Deployment --testdox
composer validate --strict --no-check-publish
```

Deploy trigger:

- Push to main, or run workflow_dispatch in GitHub Actions.

After deploy:

```bash
ls -la /var/www/invoicephp/current
php -v
curl -I http://solofactura.es
```

If HTTPS configured:

```bash
curl -I https://solofactura.es
```

## 4. Production Config Source of Truth

Runtime production env comes from:

- /var/www/invoicephp/shared/.env.local

Important:

- Repo .env is not the production source.
- Never commit production secrets to git.

## 5. Security Baseline (Practical)

- SSH keys only. Disable password auth and root login.
- UFW open only: 22, 80, 443.
- Fail2ban enabled.
- PostgreSQL not publicly exposed (localhost bind only).
- HTTPS certificate active; HTTP redirected to HTTPS.

Quick checks:

```bash
sudo ufw status verbose
sudo fail2ban-client status
sudo ss -ltnp | grep 5432
```

## 6. Database and Migrations Notes

Current migration split:

- Version20260828000100: table creation.
- Version20260828000200: constraints.

Run migrations manually:

```bash
cd /var/www/invoicephp/current
php bin/console doctrine:migrations:migrate --no-interaction --env=prod -vvv
```

If schema privilege errors appear:

- Verify runtime DB user with Symfony query.
- Ensure schema public allows CREATE/USAGE for app role, or align ownership.

## 7. Frequent Errors and Fast Fixes

1. Host key verification failed (GitHub Actions)
- Fix PROD_KNOWN_HOSTS with a clean ssh-keyscan line for the exact PROD_HOST and PROD_SSH_PORT.

2. Permission denied for schema public
- Grant/create rights from postgres superuser, not from app user.

3. relation "invoice" does not exist during migration
- Ensure both migrations are present and executed in order.

4. Nginx welcome page appears
- Disable default site and ensure root points to /var/www/invoicephp/current/public.

## 8. Backups (Minimum Viable)

Daily PostgreSQL backup:

```bash
pg_dump -h 127.0.0.1 -U <db_user> -d invoicephp_prod > /var/backups/invoicephp_$(date +%F).sql
```

Keep at least 7-14 copies and test restore monthly.

## 9. Next Sprint Plan

1. Implement auth slice:
- User entity
- Register form
- Login/logout
- Protected dashboard

2. Add tests:
- Auth flow and access control
- Invoice finalization immutability
- Hash chain continuity

3. Improve operations:
- Add staging environment
- Add uptime and error alerts
- Add restore drill checklist

## 10. Ownership and Release Hygiene

- Use small PRs.
- Keep migrations backward-safe.
- Deploy only from main after CI passes.
- If deploy fails, capture failing step + first SQL/SSH error line before making changes.

## 11. Application Logging and 500 Triage

Log files written by the app:

- Symfony default: `var/log/prod.log` (env-scoped).
- Auth flow details: `var/log/auth.log`.
- Unhandled exceptions and request failures: `var/log/app.log`.

Tail logs in production:

```bash
cd /var/www/invoicephp/current
tail -f var/log/prod.log var/log/auth.log var/log/app.log
```

When a user reports error 500:

1. Reproduce once and copy the `X-Request-Id` response header from browser devtools.
2. Filter logs by that request id:

```bash
cd /var/www/invoicephp/current
grep -R "<REQUEST_ID>" var/log/prod.log var/log/auth.log var/log/app.log
```

3. For register/login issues, inspect `auth.log` first (missing fields, duplicate email, auth failures).
4. For unexpected crashes, inspect `app.log` entry `Unhandled application exception.` and read `exception_class` + `exception_message`.
