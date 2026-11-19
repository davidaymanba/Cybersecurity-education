#!/usr/bin/env bash
# One-time server setup: create /opt/cyberlearn/.env from .env.production.example with
# freshly generated secrets. Run ON THE SERVER after the first upload:
#   bash /opt/cyberlearn/deploy/server-init.sh
# Then fill in GROQ_API_KEY and the SEED_* passwords in /opt/cyberlearn/.env.
set -euo pipefail
cd /opt/cyberlearn

if [ -f .env ]; then
  echo ".env already exists; not overwriting." >&2
  exit 0
fi

rand() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "$1"; }

umask 077
sed -e "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(rand 32)|" \
    -e "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=$(rand 32)|" \
    .env.production.example > .env
chmod 600 .env

echo "✓ Created /opt/cyberlearn/.env with generated APP_KEY and database passwords."
echo "  Next: set GROQ_API_KEY, SEED_ADMIN_PASSWORD and SEED_STUDENT_PASSWORD in it."
