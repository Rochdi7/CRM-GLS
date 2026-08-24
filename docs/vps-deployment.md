# GLS CRM — Deployment on Hostinger VPS

Target server: `srv1797354.hstgr.cloud` — `69.62.111.69` — Ubuntu 24.04 LTS (KVM 4)
Domain: **`crm.gls-sprachzentrum.ma`**
Install path: **`/var/www/crm-gls`** (fully separate from the existing `/var/www/gls`)
Repository: <https://github.com/Rochdi7/CRM-GLS>

The existing `gls` project (MySQL, `/var/www/gls`) is **not touched** by any step
here. PostgreSQL runs on port 5432, MySQL stays on 3306 — they coexist.

---

## Step 0 — DNS (do this first, it needs time to propagate)

In your domain registrar's DNS zone for `gls-sprachzentrum.ma`, add:

| Type | Name  | Value           | TTL  |
|------|-------|-----------------|------|
| A    | `crm` | `69.62.111.69`  | 3600 |

Verify from your Windows machine before continuing (it may take 5–60 minutes):

```powershell
nslookup crm.gls-sprachzentrum.ma
```

It must return `69.62.111.69`. **Do not run Step 8 (SSL) until this resolves.**

---

## Step 1 — Reboot and update the server

The login banner said *"System restart required"*. Clear that first.

```bash
ssh root@69.62.111.69
apt update && apt upgrade -y
reboot
```

Wait ~60 seconds, then reconnect:

```bash
ssh root@69.62.111.69
```

---

## Step 2 — Install PHP 8.4, Nginx, PostgreSQL 16, Node 20

Ubuntu 24.04 ships PHP 8.3, but the project requires `^8.3` and your local dev is
8.4 — install 8.4 from the `ondrej/php` PPA so local and production match.

```bash
# --- PHP 8.4 repository ---
apt install -y software-properties-common ca-certificates lsb-release apt-transport-https curl gnupg
add-apt-repository -y ppa:ondrej/php
apt update

# --- PHP 8.4 + every extension this project needs ---
apt install -y \
  php8.4-fpm php8.4-cli php8.4-common \
  php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl \
  php8.4-exif php8.4-opcache php8.4-readline

# --- Nginx ---
apt install -y nginx

# --- PostgreSQL 16 (Ubuntu 24.04 default; satisfies the 16+ minimum) ---
apt install -y postgresql postgresql-contrib

# --- Node.js 20 LTS (for `npm run build`) ---
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# --- Supporting tools ---
apt install -y git unzip
```

Verify everything:

```bash
php8.4 -v          # PHP 8.4.x
psql --version     # psql (PostgreSQL) 16.x
node -v            # v20.x
npm -v
nginx -v
```

> **`ext-exif` note (CLAUDE.md §11):** `php8.4-exif` is installed above —
> `spatie/laravel-medialibrary` needs it. Confirm with
> `php8.4 -m | grep -E 'exif|pgsql|gd|intl'` — all four must be listed.

### Install Composer

```bash
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php8.4 /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version
```

---

## Step 3 — Confirm MySQL is untouched, then configure PostgreSQL

Sanity check that your existing site's database is still fine:

```bash
systemctl status mysql --no-pager | head -5
systemctl status postgresql --no-pager | head -5
```

Both should be `active (running)` — different ports, no conflict.

### Create the database and a dedicated non-superuser role

Per CLAUDE.md §17, production must **never** use the `postgres` superuser.

First generate a strong password and keep it — you'll paste it into `.env`:

```bash
openssl rand -base64 32
```

Copy the output. Then (replace `PASTE_PASSWORD_HERE` with it):

```bash
sudo -u postgres psql <<'SQL'
CREATE ROLE gls_crm_app WITH LOGIN PASSWORD 'PASTE_PASSWORD_HERE';
CREATE DATABASE gls_crm OWNER gls_crm_app ENCODING 'UTF8';
SQL
```

Grant schema ownership so migrations can create tables:

```bash
sudo -u postgres psql -d gls_crm <<'SQL'
GRANT ALL ON SCHEMA public TO gls_crm_app;
ALTER SCHEMA public OWNER TO gls_crm_app;
SQL
```

### Lock PostgreSQL to localhost

```bash
grep -n "^listen_addresses" /etc/postgresql/16/main/postgresql.conf
```

If it is not already `localhost`, set it:

```bash
sed -i "s/^#\?listen_addresses.*/listen_addresses = 'localhost'/" /etc/postgresql/16/main/postgresql.conf
systemctl restart postgresql
```

Confirm port 5432 is bound only to loopback:

```bash
ss -lntp | grep 5432
```

You must see `127.0.0.1:5432` — **never** `0.0.0.0:5432`.

Test the app role can log in:

```bash
psql "postgresql://gls_crm_app:PASTE_PASSWORD_HERE@127.0.0.1:5432/gls_crm" -c '\conninfo'
```

---

## Step 4 — Clone the project into /var/www/crm-gls

```bash
mkdir -p /var/www/crm-gls
git clone https://github.com/Rochdi7/CRM-GLS.git /var/www/crm-gls
cd /var/www/crm-gls
git log --oneline -1
```

If the repo is private, GitHub will prompt for credentials — use a Personal
Access Token as the password, or set up a deploy key.

### Install dependencies and build assets

```bash
cd /var/www/crm-gls

# PHP dependencies — production flags (no dev packages, optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction

# Frontend build (Inertia + React + PreSkool)
npm ci
npm run build
```

`npm run build` writes `public/build/` — it is gitignored, so it **must** be
built on the server (or uploaded) or every page will 500 on a missing manifest.

> **Memory note:** the build needs ~1 GB free RAM. KVM 4 has plenty, but if
> `npm run build` is ever killed, add swap:
> `fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile`

---

## Step 5 — Configure the environment

```bash
cd /var/www/crm-gls
cp .env.example .env
nano .env
```

Set these values (everything else can stay as-is):

```env
APP_NAME="GLS CRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://crm.gls-sprachzentrum.ma

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gls_crm
DB_USERNAME=gls_crm_app
DB_PASSWORD=PASTE_PASSWORD_HERE
DB_SSLMODE=prefer

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=crm.gls-sprachzentrum.ma

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
FILESYSTEM_DISK=local
```

> Redis (cache + sessions + queue), OPcache, PHP-FPM pool, Nginx gzip and
> PostgreSQL memory settings are set up in **`docs/vps-performance-tuning.md`**
> — do that pass right after this guide. Until Redis is installed you may
> keep `database` for the three drivers above; it works, it is just slower on
> every request.

⚠ Three critical settings:

- `APP_DEBUG=false` — `true` in production leaks your DB password and full
  source paths on any error page.
- `APP_LOCALE=fr` — the UI is French (CLAUDE.md §12); the default `en` would
  show raw English keys.
- `SESSION_SECURE_COOKIE=true` — only valid **after** HTTPS works (Step 8). If
  you test over plain HTTP first, leave it `false` and flip it after SSL.

Save (`Ctrl+O`, `Enter`, `Ctrl+X`), then generate the app key:

```bash
php8.4 artisan key:generate
```

### Mail (optional now, needed for password resets)

`MAIL_MAILER=log` writes reset links to `storage/logs/` instead of sending them.
To send real email, set your SMTP credentials:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=crm@gls-sprachzentrum.ma
MAIL_PASSWORD=your-mailbox-password
MAIL_SCHEME=smtps
MAIL_FROM_ADDRESS="crm@gls-sprachzentrum.ma"
MAIL_FROM_NAME="GLS CRM"
```

---

## Step 6 — File permissions

Nginx and PHP-FPM run as `www-data`. Laravel must be able to write to
`storage/` and `bootstrap/cache/` — and only those.

```bash
cd /var/www/crm-gls
chown -R www-data:www-data /var/www/crm-gls
find /var/www/crm-gls -type f -exec chmod 644 {} \;
find /var/www/crm-gls -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 640 .env          # .env must never be world-readable
```

### Create the media symlink

Uploads (student photos, expense receipts) are served from `/media/…` via a
custom path generator (CLAUDE.md §11) — **not** `/storage/…`:

```bash
sudo -u www-data php8.4 artisan storage:link
ls -la public/media public/storage
```

Both must show as symlinks into `storage/app/…`. If `public/media` is missing,
check `config/filesystems.php` `'links'` and re-run.

---

## Step 7 — Nginx site (separate from the existing gls site)

```bash
nano /etc/nginx/sites-available/crm-gls
```

Paste:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name crm.gls-sprachzentrum.ma;

    root /var/www/crm-gls/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;   # student photos / expense receipts

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Long-cache the Vite build and static PreSkool theme assets
    location ~* ^/(build|assets)/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Uploaded media — served through the symlink, never executed
    location /media/ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;   # long imports (Excel encaissements)
    }

    # Block dotfiles (.env, .git) outright
    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_log  /var/log/nginx/crm-gls-error.log;
    access_log /var/log/nginx/crm-gls-access.log;
}
```

Enable it and reload — this leaves the existing `gls` site running:

```bash
ln -s /etc/nginx/sites-available/crm-gls /etc/nginx/sites-enabled/crm-gls
nginx -t
systemctl reload nginx
```

`nginx -t` must say `syntax is ok` / `test is successful` before you reload.

### PHP-FPM tuning for imports

Excel imports need headroom over the defaults:

```bash
nano /etc/php/8.4/fpm/php.ini
```

Set:

```ini
memory_limit = 512M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
```

```bash
systemctl restart php8.4-fpm
```

---

## Step 8 — HTTPS with Let's Encrypt

**Only run this once `nslookup crm.gls-sprachzentrum.ma` returns `69.62.111.69`.**

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d crm.gls-sprachzentrum.ma
```

Answer the prompts: enter your email, agree to the terms, and choose
**"Redirect"** so HTTP traffic is forced to HTTPS.

Certbot rewrites the Nginx config with the certificate and installs an automatic
renewal timer. Verify renewal works:

```bash
certbot renew --dry-run
systemctl status certbot.timer --no-pager
```

Now that HTTPS is live, confirm `.env` has `SESSION_SECURE_COOKIE=true` and
`APP_URL=https://…` (Step 5), then continue.

---

## Step 9 — Migrate and seed the database

The demo seeders that used to make a bare `db:seed` dangerous
(`DemoDataSeeder`, `DemoFinanceSeeder`, `DemoRoleUsersSeeder`,
`DemoStockSeeder`, `DemoRecouvrementSeeder`, `DemoDashboardSeeder`,
`DemoLongueDureeSeeder` and `BookStockSeeder`) have been **deleted from the
project**. `DatabaseSeeder` now contains production data only, so it is safe
to run whole:

```bash
cd /var/www/crm-gls

# Schema
sudo -u www-data php8.4 artisan migrate --force

# Production reference data + real GLS staff — everything is idempotent
sudo -u www-data php8.4 artisan db:seed --force
```

If you prefer to run them one at a time, the order is:

```bash
sudo -u www-data php8.4 artisan db:seed --force --class=RolesAndPermissionsSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=ReferentialDataSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=TypeDepenseSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=StockTypeSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=FraisSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=BanqueSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=MotifAnnulationSeeder
sudo -u www-data php8.4 artisan db:seed --force --class=GlsStaffSeeder
```

What each one gives you:

| Seeder | Contents |
|---|---|
| `RolesAndPermissionsSeeder` | 61 permissions + the role matrix (**required** — the app cannot authorize anything without it) |
| `ReferentialDataSeeder` | Academic years 2025/2026 (default) + 2026/2027, the 7 GLS branches, 2 rooms each |
| `TypeDepenseSeeder` | Locked `is_system` expense types |
| `StockTypeSeeder` | Stock categories (the book catalog is **not** seeded — open real quantities via the Import screen) |
| `FraisSeeder` | Starter fee catalog (Paramètres → Frais) |
| `BanqueSeeder` | Bank list for cheque/transfer payments |
| `MotifAnnulationSeeder` | Cancellation reasons |
| `GlsStaffSeeder` | The real `@glszentrum.com` staff: employees, their center assignments, logins and roles |

⚠ **`GlsStaffSeeder` prints a one-time password per NEW account, once.**
Capture that output when you run it, hand the credentials over, then discard
it — everyone is forced to change their password at first sign-in. Re-running
never resets an existing password.

`AdminUserSeeder` runs as part of `db:seed`, but refuses to create an
administrator outside `local`/`testing` unless `ADMIN_PASSWORD` is set — so it
can never publish the well-known `admin@gls.test` / `password` pair on a public
server. Set `ADMIN_EMAIL`/`ADMIN_USERNAME`/`ADMIN_PASSWORD` in `.env` first, or
skip it and use the command below.

### Create your real super-admin

Use Tinker so no credentials are ever written to a file:

```bash
cd /var/www/crm-gls
sudo -u www-data php8.4 artisan tinker
```

In the Tinker prompt (replace the email, name, and password with your own —
use a long, unique password):

```php
$u = \App\Models\User::create([
    'name' => 'Rochdi Karouali',
    'email' => 'rochdi.karouali1234@gmail.com',
    'username' => 'rochdi',
    'password' => \Illuminate\Support\Facades\Hash::make('YOUR-STRONG-PASSWORD-HERE'),
    'must_change_password' => false,
    'is_active' => true,
]);

\App\Models\Employee::create([
    'user_id'    => $u->id,
    'reference'  => 'EMP-000001',
    'nom'        => 'Karouali',
    'prenom'     => 'Rochdi',
    'categorie'  => \App\Models\Employee::CATEGORIE_DIRECTEUR,
    'statut'     => \App\Models\Employee::STATUT_ACTIF,
    'email'      => 'rochdi.karouali1234@gmail.com',
]);

exit
```

The `Employee` record matters — money operations need an employee identity for
`agent_id` / `requested_by` accountability (CLAUDE.md §11). Passing `user_id`
explicitly stops `EmployeeObserver` from generating a second login.

Now grant the super-admin role:

```bash
sudo -u www-data php8.4 artisan auth:assign-super-admin rochdi.karouali1234@gmail.com
```

It will ask to confirm because `APP_ENV=production` — answer `yes`.

---

## Step 10 — Cache for production and start the queue worker

```bash
cd /var/www/crm-gls
sudo -u www-data php8.4 artisan config:cache
sudo -u www-data php8.4 artisan route:cache
sudo -u www-data php8.4 artisan view:cache
sudo -u www-data php8.4 artisan event:cache
```

> Re-run these after **every** `.env` change or `git pull` — a cached config
> ignores later `.env` edits, which is the most common "why didn't my change
> apply" trap.

### Queue worker (systemd)

`QUEUE_CONNECTION=database`, so a worker must be running for queued jobs
(media conversions, mail) to execute.

```bash
nano /etc/systemd/system/crm-gls-queue.service
```

```ini
[Unit]
Description=GLS CRM queue worker
After=network.target postgresql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php8.4 /var/www/crm-gls/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/crm-gls-queue.log
StandardError=append:/var/log/crm-gls-queue.log

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable --now crm-gls-queue
systemctl status crm-gls-queue --no-pager
```

### Scheduler (cron)

```bash
crontab -e
```

Add this single line:

```cron
* * * * * cd /var/www/crm-gls && sudo -u www-data /usr/bin/php8.4 artisan schedule:run >> /dev/null 2>&1
```

---

## Step 11 — Firewall

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
ufw status verbose
```

Ports 80/443/22 open; **5432 and 3306 stay closed** (both databases are
localhost-only).

---

## Step 12 — Verify the deployment

```bash
# Nginx and PHP are healthy
nginx -t
systemctl status nginx php8.4-fpm postgresql crm-gls-queue --no-pager | grep -E 'Active|●'

# The app responds over HTTPS
curl -I https://crm.gls-sprachzentrum.ma

# Routes are registered
cd /var/www/crm-gls
sudo -u www-data php8.4 artisan route:list | head -20

# The build manifest exists
ls -la public/build/manifest.json
```

Then open **<https://crm.gls-sprachzentrum.ma>** in a browser. It redirects to
the backoffice login (CLAUDE.md §15 — `/` currently points at
`backoffice.login`). Sign in with the super-admin you created in Step 9.

Check in the browser:

- Login works, dashboard loads with the PreSkool theme (CSS/JS not 404ing)
- The context switcher shows the 7 centers and 2025/2026 as the active year
- Paramètres → Frais / Établissements / Salles are populated
- No console errors in DevTools
- Padlock icon shows a valid certificate

If a page 500s, read the real error:

```bash
tail -50 /var/www/crm-gls/storage/logs/laravel.log
tail -50 /var/log/nginx/crm-gls-error.log
```

---

## Updating the site later

Save this as `/var/www/crm-gls/deploy.sh`:

```bash
cat > /var/www/crm-gls/deploy.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
cd /var/www/crm-gls

php8.4 artisan down --render="errors::503" || true

# Safety net: dump the database BEFORE anything touches it, with an
# hour-minute stamp so it never overwrites the nightly 02:30 dump.
# Added after 21/08/2026, when a migrate:fresh on production dropped all
# tables and the nightly dump was the only thing that saved the data.
mkdir -p /var/backups/crm-gls
sudo -u postgres pg_dump -Fc gls_crm \
  > "/var/backups/crm-gls/gls_crm-predeploy-$(date +%F-%H%M).dump"

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php8.4 artisan migrate --force

php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
php8.4 artisan event:cache

chown -R www-data:www-data storage bootstrap/cache public/build
chmod -R 775 storage bootstrap/cache

systemctl restart crm-gls-queue
systemctl reload php8.4-fpm

php8.4 artisan up
echo "Deployed: $(git log --oneline -1)"
EOF

chmod +x /var/www/crm-gls/deploy.sh
```

Then future updates are one command:

```bash
/var/www/crm-gls/deploy.sh
```

`artisan down` puts the site in maintenance mode during the update so users
never hit a half-migrated database.

---

## Backups

Set up a nightly PostgreSQL dump before you enter real data:

```bash
mkdir -p /var/backups/crm-gls
cat > /usr/local/bin/crm-gls-backup.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
STAMP=$(date +%F)
sudo -u postgres pg_dump -Fc gls_crm > /var/backups/crm-gls/gls_crm-$STAMP.dump
tar czf /var/backups/crm-gls/media-$STAMP.tar.gz -C /var/www/crm-gls/storage/app media
find /var/backups/crm-gls -type f -mtime +14 -delete
EOF
chmod +x /usr/local/bin/crm-gls-backup.sh
```

```bash
crontab -e
```

```cron
30 2 * * * /usr/local/bin/crm-gls-backup.sh >> /var/log/crm-gls-backup.log 2>&1
```

This keeps 14 days of database dumps **and** uploaded media. Copy them off the
VPS periodically — a backup on the same disk is not a backup.

Three layers now stand between production data and a destructive command:

1. **Nightly dump** (02:30 cron above) — the recovery floor.
2. **Pre-deploy dump** — `deploy.sh` snapshots the database before every
   deploy, timestamped to the minute so same-day dumps never overwrite
   each other.
3. **Hard block in the app** — `AppServiceProvider` calls
   `DB::prohibitDestructiveCommands()` when `APP_ENV=production`, so
   `migrate:fresh`, `migrate:refresh`, `migrate:reset` and `db:wipe` are
   refused outright, with no interactive prompt to click through. Production
   migrations are `php artisan migrate --force` only (CLAUDE.md §17).

Restore procedure:

```bash
sudo -u postgres pg_restore -d gls_crm --clean --if-exists /var/backups/crm-gls/gls_crm-YYYY-MM-DD.dump
```

---

## Isolation from the existing `gls` project — summary

| Resource | Existing `gls` | New `crm-gls` |
|---|---|---|
| Path | `/var/www/gls` | `/var/www/crm-gls` |
| Nginx site | its own file | `/etc/nginx/sites-available/crm-gls` |
| Database engine | MySQL (3306) | PostgreSQL (5432) |
| Database / role | its own | `gls_crm` / `gls_crm_app` |
| PHP-FPM pool | its own version | `php8.4-fpm.sock` |
| Queue service | its own | `crm-gls-queue.service` |
| Logs | its own | `/var/log/nginx/crm-gls-*.log` |
| Domain | its own | `crm.gls-sprachzentrum.ma` |

Nothing in this guide writes to `/var/www/gls`, its database, or its Nginx
config. The only shared components are Nginx itself and the OS — and the only
Nginx change is *adding* a new site file.

> **Note on PHP versions:** if the existing `gls` project runs on an older PHP
> (e.g. 8.2 or 8.3), installing 8.4 does not break it — its Nginx config points
> at its own `phpX.Y-fpm.sock`. Confirm with
> `grep -r fastcgi_pass /etc/nginx/sites-enabled/` that the two sites reference
> different sockets. If the `gls` site uses a generic socket path, pin it to its
> real version before reloading Nginx.
