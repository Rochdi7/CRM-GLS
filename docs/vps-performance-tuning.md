# GLS CRM — Production performance tuning (VPS)

Companion to `docs/vps-deployment.md`. Apply once on
`crm.gls-sprachzentrum.ma` (`/var/www/crm-gls`), then keep `deploy.sh` as is.

## Why the app "sometimes gets slow"

Two independent causes were measured on 24/08/2026:

1. **Per-row queries in the read models** (fixed in code, ships with the
   next deploy):
   - the dashboard chart ran ONE `SUM` query **per fee** of the year
     (`InscriptionFee::montantPaye()` inside a loop) — thousands of
     queries per dashboard visit on a real centre; now 4 `GROUP BY month`
     aggregates;
   - the KPI cards ran 16 separate `COUNT`s — now 5 `COUNT(*) FILTER`
     aggregates;
   - Recouvrements ran one `SUM` per overdue fee, twice — now `withSum`;
   - the Encaissements page recomputed the **full student list of the
     centre** on every keystroke of the search box and every page change —
     now computed on the first visit only (closure props + partial
     reloads);
   - three date columns had no index (`encaissements.date_paiement`,
     `inscription_fees.date_echeance`, `depenses.date_depense`) — now
     declared in each table's `create_*` migration.
2. **Fixed overhead on EVERY request** (server configuration — this
   document): sessions AND the cache live in PostgreSQL
   (`SESSION_DRIVER=database`, `CACHE_STORE=database`), so each page does a
   session `SELECT` + `UPDATE`, a cache-table `SELECT` for the Spatie
   permission blob (unserialised on every request), and the PHP-FPM pool +
   OPcache run on Ubuntu defaults (timestamp validation on, small file
   cache, few workers), with no gzip on the Inertia JSON responses.

Do the steps below in order; each is safe to apply alone.

---

## Step 1 — Redis for cache, sessions and the queue

```bash
apt install -y redis-server php8.4-redis
systemctl enable --now redis-server
redis-cli ping          # PONG
php8.4 -m | grep -i redis   # redis
```

Keep Redis on localhost only (default in Ubuntu: `bind 127.0.0.1 ::1`), and
give it a memory cap so it can never starve PostgreSQL:

```bash
sed -i 's/^# maxmemory <bytes>/maxmemory 256mb/; s/^# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl restart redis-server
```

Edit `/var/www/crm-gls/.env`:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

Then:

```bash
cd /var/www/crm-gls
sudo -u www-data php8.4 artisan config:cache
sudo -u www-data php8.4 artisan permission:cache-reset
systemctl restart crm-gls-queue        # the worker must pick up the redis queue
```

Everyone is signed out once (sessions moved store) — announce it.

> The `cache`/`sessions`/`jobs` tables stay in PostgreSQL untouched; nothing
> reads them any more. Do not drop them (the migrations own them).

---

## Step 2 — OPcache for production

Create `/etc/php/8.4/fpm/conf.d/99-gls-opcache.ini`:

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=30000
; Production: never stat() PHP files per request. deploy.sh already does
; `systemctl reload php8.4-fpm`, which is what refreshes the cache.
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=off
realpath_cache_size=4096K
realpath_cache_ttl=600
```

```bash
systemctl restart php8.4-fpm
php8.4-fpm -i 2>/dev/null | grep -E 'opcache.(enable|validate_timestamps|memory_consumption) '
```

⚠ With `validate_timestamps=0`, a `git pull` alone changes nothing until
PHP-FPM is reloaded — always deploy with `deploy.sh` (it reloads FPM).

---

## Step 3 — PHP-FPM pool sizing

`/etc/php/8.4/fpm/pool.d/www.conf` — the Ubuntu default is
`pm.max_children = 5`, which serialises the whole school behind 5 workers.
For the KVM 4 plan (4 vCPU / 16 GB) with ~60–80 MB per worker:

```ini
pm = dynamic
pm.max_children = 40
pm.start_servers = 8
pm.min_spare_servers = 6
pm.max_spare_servers = 16
pm.max_requests = 1000
pm.status_path = /fpm-status
request_terminate_timeout = 300
```

```bash
php-fpm8.4 -t && systemctl reload php8.4-fpm
```

---

## Step 4 — Nginx: gzip + HTTP/2 + FastCGI buffers

In `/etc/nginx/sites-available/crm-gls` (the `server` block certbot
rewrote for 443):

```nginx
    listen 443 ssl;
    http2 on;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_comp_level 5;
    gzip_types text/plain text/css application/json application/javascript
               application/x-javascript text/javascript application/xml
               image/svg+xml font/woff2;

    # Inertia responses can carry several hundred KB of props (option lists,
    # paginated rows) — default 4k/8k FastCGI buffers make Nginx spill them
    # to disk.
    location ~ \.php$ {
        fastcgi_buffer_size 64k;
        fastcgi_buffers 16 64k;
        fastcgi_busy_buffers_size 128k;
        # …existing fastcgi_* lines stay as they are…
    }
```

```bash
nginx -t && systemctl reload nginx
curl -sI -H 'Accept-Encoding: gzip' https://crm.gls-sprachzentrum.ma/backoffice/login | grep -i content-encoding
```

Expected: `content-encoding: gzip`.

---

## Step 5 — PostgreSQL memory settings

Ubuntu's `postgresql.conf` ships with `shared_buffers = 128MB`. For a
16 GB machine shared with MySQL (`/var/www/gls`):

```bash
sudo -u postgres psql -c "ALTER SYSTEM SET shared_buffers = '2GB';"
sudo -u postgres psql -c "ALTER SYSTEM SET effective_cache_size = '6GB';"
sudo -u postgres psql -c "ALTER SYSTEM SET work_mem = '32MB';"
sudo -u postgres psql -c "ALTER SYSTEM SET maintenance_work_mem = '256MB';"
sudo -u postgres psql -c "ALTER SYSTEM SET random_page_cost = 1.1;"   # SSD
systemctl restart postgresql
```

After the next deploy (which adds the three date indexes) refresh the
planner statistics once:

```bash
sudo -u postgres psql -d gls_crm -c "ANALYZE;"
```

---

## Step 6 — Deploy the code changes

```bash
/var/www/crm-gls/deploy.sh
```

`migrate --force` inside it creates the three indexes (seconds, even on
years of data).

---

## Verify

```bash
# Wall time of the dashboard for a signed-in user (paste your session cookie)
curl -s -o /dev/null -w 'total=%{time_total}s\n' \
  -H 'Cookie: gls_crm_session=…' https://crm.gls-sprachzentrum.ma/backoffice/dashboard

# No more cache/session traffic on PostgreSQL
sudo -u postgres psql -d gls_crm -c "SELECT query FROM pg_stat_activity WHERE datname='gls_crm' AND state='active';"

# Redis actually used
redis-cli -n 1 dbsize        # cache keys
redis-cli -n 0 dbsize        # sessions + queue

# Slow-query hunt if something is still slow (log statements > 200 ms)
sudo -u postgres psql -c "ALTER SYSTEM SET log_min_duration_statement = 200;"
sudo -u postgres psql -c "SELECT pg_reload_conf();"
tail -f /var/log/postgresql/postgresql-16-main.log
```

Set `log_min_duration_statement = -1` again once done.

---

## What NOT to do

- Do not add client-side DataTables or "load everything then filter in
  React" — every list stays server-paginated (CLAUDE.md §5/§7).
- Do not cache money figures (`caisses.solde`, dashboard sums) in Redis with
  a TTL: they must always be read from PostgreSQL (CLAUDE.md §11).
- Do not call `InscriptionFee::montantPaye()` / `Encaissement::montantUtilise()`
  / `Cheque::montantRestant()` inside a loop over a list — use
  `withSum(...)` on the query or a `GROUP BY` aggregate. The per-row
  methods exist for the money **actions**, which re-read one locked row.
