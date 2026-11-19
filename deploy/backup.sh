#!/usr/bin/env bash
# Daily MySQL backup for CyberLearn AI. Installed on the server by deploy/install-backup-cron.sh
# and run by /etc/cron.d/cyberlearn-backup. Keeps KEEP_DAYS days of compressed dumps.
#
# Restore a dump:
#   gunzip -c /opt/cyberlearn-backups/cyberlearn-YYYYmmdd-HHMMSS.sql.gz \
#     | docker exec -i cyberlearn-mysql-1 sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$MYSQL_DATABASE"'
set -euo pipefail

DEST=/opt/cyberlearn-backups
KEEP_DAYS="${KEEP_DAYS:-14}"
CONTAINER=cyberlearn-mysql-1

umask 077
mkdir -p "$DEST"

out="$DEST/cyberlearn-$(date +%Y%m%d-%H%M%S).sql.gz"

# Credentials stay inside the container (MYSQL_PWD avoids putting them on a command line).
docker exec "$CONTAINER" sh -c \
  'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot --single-transaction --quick --routines --no-tablespaces "$MYSQL_DATABASE"' \
  | gzip -9 > "$out.tmp"

gzip -t "$out.tmp"
mv "$out.tmp" "$out"

find "$DEST" -name 'cyberlearn-*.sql.gz' -mtime +"$KEEP_DAYS" -delete

echo "$(date -Is) backup ok: $out ($(du -h "$out" | cut -f1))"
