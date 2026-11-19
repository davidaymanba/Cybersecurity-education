#!/usr/bin/env bash
# Run ON THE SERVER once: schedule the daily database backup (02:30 server time).
set -euo pipefail
cat > /etc/cron.d/cyberlearn-backup <<'CRON'
# CyberLearn AI — daily MySQL dump, 14 days kept in /opt/cyberlearn-backups
30 2 * * * root /opt/cyberlearn/deploy/backup.sh >> /var/log/cyberlearn-backup.log 2>&1
CRON
chmod 644 /etc/cron.d/cyberlearn-backup
echo "✓ Installed /etc/cron.d/cyberlearn-backup"
