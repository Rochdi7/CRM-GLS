#!/usr/bin/env bash
# =============================================================================
# CRM GLS — protect the source code on the VPS
#
#   sudo bash secure-source.sh            # AUDIT ONLY — changes nothing
#   sudo bash secure-source.sh --apply    # apply the protections below
#   sudo bash secure-source.sh --report   # who read the code / ran zip, tar, scp…
#
# What it does with --apply (all idempotent, safe to re-run):
#   1. permissions : the project is readable ONLY by root + www-data
#                    (no other Linux account can read or copy a single file)
#   2. nginx       : refuses .git, .env, archives, dumps, source maps over HTTP
#   3. auditd      : records every file read and every command run by a
#                    logged-in human (SSH, PuTTY, WinSCP, FileZilla, Hostinger
#                    browser terminal) — php-fpm / cron / queue are NOT logged
#   4. backups     : /var/backups/crm-gls readable by root only
#   5. deploy.sh   : re-applies the permissions after every deploy
#
# It NEVER touches SSH config (no lock-out risk) — section 5 of the audit
# tells you what to change by hand.
#
# ⚠ Limit: root can read everything. If someone you don't trust has root,
# sudo, or your Hostinger panel password, no script can stop them. The audit
# below lists exactly who has that access — remove them first.
# =============================================================================
set -euo pipefail

APP=/var/www/crm-gls
DOMAIN=crm.gls-sprachzentrum.ma
BACKUPS=/var/backups/crm-gls
MODE="${1:-}"

[[ $EUID -eq 0 ]] || { echo "Run as root: sudo bash $0 $MODE"; exit 1; }
[[ -d $APP ]] || { echo "$APP not found"; exit 1; }

section() { printf '\n\033[1;36m=== %s ===\033[0m\n' "$1"; }
warn()    { printf '\033[1;31m  ! %s\033[0m\n' "$1"; }
ok()      { printf '\033[1;32m  ✓ %s\033[0m\n' "$1"; }

# -----------------------------------------------------------------------------
# --report : what humans did on the server
# -----------------------------------------------------------------------------
if [[ $MODE == "--report" ]]; then
    command -v ausearch >/dev/null || { echo "auditd not installed — run --apply first"; exit 1; }
    START="${2:-week-ago}"
    section "Archive / copy commands since $START"
    # one event per "----" block: print date, login user, full command line
    ausearch -k crm-cmd -i --start "$START" 2>/dev/null | awk -v RS='----' '
        /a0=([^ \n]*\/)?(zip|unzip|tar|gzip|7z|rar|scp|rsync|sftp-server|curl|wget|nc|ncat|base64|git)[ \n]/ {
            t = ""; u = ""; c = ""
            if (match($0, /msg=audit\([^)]*\)/)) t = substr($0, RSTART + 10, RLENGTH - 11)
            if (match($0, / auid=[^ \n]+/))      u = substr($0, RSTART + 6, RLENGTH - 6)
            if (match($0, /a0=[^\n]*/))          c = substr($0, RSTART, RLENGTH)
            printf "  %s  %-10s %s\n", t, u, c; n++
        }
        END { if (!n) print "  nothing" }'
    section "Logins that read files in $APP since $START (user → count)"
    ausearch -k crm-src -i --start "$START" 2>/dev/null \
        | grep -oE 'auid=[^ ]+' | sort | uniq -c | sort -rn \
        || echo "  nothing"
    section "SSH logins (last 30)"
    last -a -n 30 | head -32
    exit 0
fi

# -----------------------------------------------------------------------------
# AUDIT (always runs)
# -----------------------------------------------------------------------------
section "1. Linux accounts that can log in"
awk -F: '$7 !~ /(nologin|false|sync|halt|shutdown)$/ {printf "  %-15s uid=%-6s home=%s\n", $1, $3, $6}' /etc/passwd
echo "  → every name here except root (and yours) should be explained."

section "2. SSH keys allowed to log in"
found=0
for home in /root /home/*; do
    f="$home/.ssh/authorized_keys"
    [[ -s $f ]] || continue
    found=1
    echo "  -- $f"
    ssh-keygen -lf "$f" 2>/dev/null | sed 's/^/     /' || cat "$f"
done
[[ $found -eq 1 ]] || echo "  (none — logins are by password)"
echo "  → delete every key you do not recognise (one line per key in that file)."

section "3. Who has sudo (= root)"
getent group sudo | sed 's/^/  /'
grep -rhvE '^\s*(#|$|Defaults)' /etc/sudoers /etc/sudoers.d 2>/dev/null | sed 's/^/  /' || true

section "4. Last logins"
last -a -n 15 | head -17 | sed 's/^/  /'

section "5. SSH configuration"
sshd -T 2>/dev/null | grep -Ei '^(port|permitrootlogin|passwordauthentication|pubkeyauthentication) ' | sed 's/^/  /'
echo "  → recommended once your OWN key works in PuTTY: passwordauthentication no"
echo "    (edit /etc/ssh/sshd_config.d/99-hardening.conf, then: systemctl restart ssh)"
echo "    Keep this session open while testing a new one, or you can lock yourself out."

section "6. Archives / dumps on the disk (last 90 days, > 100 KB)"
find / -xdev \( -path /proc -o -path /sys -o -path /usr -o -path /snap -o -path /var/lib -o -path "$BACKUPS" \) -prune -o \
     -type f \( -iname '*.zip' -o -iname '*.tar' -o -iname '*.tar.gz' -o -iname '*.tgz' -o -iname '*.7z' \
                -o -iname '*.rar' -o -iname '*.sql' -o -iname '*.sql.gz' -o -iname '*.dump' \) \
     -mtime -90 -size +100k -printf '  %TY-%Tm-%Td %TH:%TM  %u  %10s  %p\n' 2>/dev/null | sort || true
echo "  → anything here you did not create = someone packed files. Check its owner and date."

section "7. Files the web could serve from public/"
find "$APP/public" -xdev -type f \( -iname '*.zip' -o -iname '*.tar*' -o -iname '*.sql*' -o -iname '*.map' \
     -o -iname '*.env' -o -iname '*.bak' -o -iname '*.log' -o \( -iname '*.php' ! -name index.php \) \) \
     -printf '  %p\n' 2>/dev/null || true
for p in .env .git/config .git/HEAD composer.json package.json storage/logs/laravel.log; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "https://$DOMAIN/$p" || echo ERR)
    if [[ $code == 200 ]]; then warn "https://$DOMAIN/$p → $code  DOWNLOADABLE!"; else ok "/$p → $code"; fi
done

section "8. Project permissions"
other=$(find "$APP" -xdev -perm -o=r 2>/dev/null | wc -l)
if [[ $other -gt 0 ]]; then warn "$other files/dirs readable by ANY Linux account"; else ok "no file readable by other accounts"; fi
stat -c '  %U:%G %a  %n' "$APP" "$APP/.env" "$APP/.git" 2>/dev/null || true
[[ -d $BACKUPS ]] && stat -c '  %U:%G %a  %n' "$BACKUPS"

section "9. Queue worker user"
qu=$(systemctl show crm-gls-queue -p User --value 2>/dev/null || true)
echo "  crm-gls-queue runs as: ${qu:-root}"

if [[ $MODE != "--apply" ]]; then
    printf '\nAudit done — nothing was changed. Run with --apply to protect.\n'
    exit 0
fi

# -----------------------------------------------------------------------------
# APPLY
# -----------------------------------------------------------------------------
if [[ -n $qu && $qu != www-data && $qu != root ]]; then
    warn "queue worker runs as '$qu' — adding it to group www-data so it keeps reading the code"
    usermod -aG www-data "$qu"
fi

section "A. Permissions: root + www-data only"
# group www-data keeps php-fpm / nginx / queue reading everything (incl. files
# created as root by git pull); owner is unchanged, so writable dirs stay writable.
chgrp -R www-data "$APP"
chmod -R g+rX,o-rwx "$APP"
chmod 640 "$APP/.env"
ok "done — other Linux accounts can no longer read or copy the project"

section "B. Backups: root only"
if [[ -d $BACKUPS ]]; then chmod 700 "$BACKUPS"; chmod -R o-rwx,g-rwx "$BACKUPS"; ok "$BACKUPS → 700"; fi

section "C. deploy.sh re-applies permissions after each deploy"
if [[ -f $APP/deploy.sh ]]; then
    if grep -q 'secure-source: perms' "$APP/deploy.sh"; then
        ok "already present"
    else
        cat >> "$APP/deploy.sh" <<EOF

# secure-source: perms — project readable by root + www-data only
chgrp -R www-data $APP && chmod -R g+rX,o-rwx $APP && chmod 640 $APP/.env
EOF
        ok "line appended to deploy.sh"
    fi
else
    warn "$APP/deploy.sh not found — after each deploy run: sudo bash $0 --apply"
fi

section "D. nginx: deny hidden files, archives, dumps, source maps"
cat > /etc/nginx/snippets/crm-gls-deny.conf <<'EOF'
# CRM GLS — never serve source code, secrets or archives (secure-source.sh)
location ~ /\.(?!well-known/) { return 404; }
location ~* \.(zip|tar|gz|tgz|7z|rar|sql|dump|bak|old|orig|swp|map|log|env|sh|ps1)$ { return 404; }
EOF
SITE=$(grep -l "root $APP/public" /etc/nginx/sites-enabled/* 2>/dev/null | head -1 || true)
if [[ -z $SITE ]]; then
    warn "nginx site for $APP not found — add 'include snippets/crm-gls-deny.conf;' after 'root …;' by hand"
else
    SITE=$(readlink -f "$SITE")
    if grep -q 'crm-gls-deny.conf' "$SITE"; then
        ok "already included in $SITE"
    else
        BAK="$SITE.bak-$(date +%F-%H%M%S)"
        cp "$SITE" "$BAK"
        # first in the server block, before the /build regex, so it wins
        sed -i "s#^\(\s*\)root $APP/public;#&\n\1include snippets/crm-gls-deny.conf;#" "$SITE"
        if nginx -t 2>/dev/null; then
            systemctl reload nginx
            ok "included in $SITE, nginx reloaded (backup: $BAK)"
        else
            cp "$BAK" "$SITE"
            nginx -t || true
            warn "nginx test failed — change rolled back, site untouched"
        fi
    fi
fi
code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "https://$DOMAIN/backoffice/login" || echo ERR)
[[ $code =~ ^(200|302)$ ]] && ok "site still answers ($code)" || warn "site answers $code — check it in the browser NOW"

section "E. auditd: log every read / command by a logged-in human"
if ! command -v auditctl >/dev/null; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq auditd >/dev/null
fi
PHPBIN=$(readlink -f "$(command -v php8.4 || command -v php)")
cat > /etc/audit/rules.d/crm-gls.rules <<EOF
# CRM GLS — auid is set only for real logins (SSH, sftp, console); daemons
# (php-fpm, nginx, queue) have auid unset and are not logged.
# The every-minute "artisan schedule:run" cron DOES carry auid=0, so file
# reads by the php binary are skipped (its command line is still logged by
# crm-cmd) — otherwise each Laravel boot writes hundreds of lines a minute.
-a never,exit -F arch=b64 -F dir=$APP -F perm=r -F exe=$PHPBIN
-a always,exit -F arch=b64 -F dir=$APP -F perm=r -F auid!=4294967295 -k crm-src
-a always,exit -F arch=b64 -S execve -F auid!=4294967295 -k crm-cmd
-a always,exit -F arch=b32 -S execve -F auid!=4294967295 -k crm-cmd
# protect the logs and the rules themselves
-w /etc/audit/ -p wa -k audit-config
-w /var/log/audit/ -p wa -k audit-log
EOF
sed -i 's/^max_log_file *=.*/max_log_file = 50/; s/^num_logs *=.*/num_logs = 20/' /etc/audit/auditd.conf
systemctl enable --now auditd >/dev/null 2>&1
augenrules --load >/dev/null
ok "auditd active — see activity with: sudo bash $0 --report"

printf '\n\033[1;32mDone.\033[0m Re-run without arguments to check, --report to see who did what.\n'
