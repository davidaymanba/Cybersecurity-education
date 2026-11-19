#!/usr/bin/env bash
# Deploy the current working tree to the VPS and rebuild the containers.
#
#   deploy/deploy.sh                 # default host
#   deploy/deploy.sh root@1.2.3.4    # another host
#
# Only files tracked by git (or new, non-ignored files) are sent; the server's .env is kept.
# First deploy: run deploy/server-init.sh on the server once to create .env with secrets.
set -euo pipefail

HOST="${1:-root@187.126.26.74}"
DIR=/opt/cyberlearn
COMPOSE="docker compose -f docker-compose.prod.yml"
# Reuse one SSH connection for every step (many quick connections can trip SSH rate limits).
SSH=(ssh -o ControlMaster=auto -o "ControlPath=$HOME/.ssh/cm-cyberlearn-%C" -o ControlPersist=5m)

cd "$(dirname "$0")/.."

# Never ship a broken build: run the test suite first (skip with SKIP_TESTS=1).
if [ "${SKIP_TESTS:-0}" != "1" ]; then
  echo "→ Running tests"
  php artisan test --compact || { echo "✗ Tests failed — deploy aborted." >&2; exit 1; }
fi

echo "→ Uploading code to $HOST:$DIR"
git ls-files -co --exclude-standard -z \
  | COPYFILE_DISABLE=1 tar --no-xattrs --no-mac-metadata --null -T - -czf - \
  | "${SSH[@]}" "$HOST" "set -e; mkdir -p $DIR; cd $DIR; find . -mindepth 1 -maxdepth 1 ! -name .env -exec rm -rf {} +; tar -xzf -"

echo "→ Building and starting containers"
"${SSH[@]}" "$HOST" "set -e; cd $DIR
  if [ ! -f .env ]; then echo 'Missing $DIR/.env: run deploy/server-init.sh first.' >&2; exit 1; fi
  $COMPOSE up -d --build --remove-orphans
  docker image prune -f >/dev/null
  echo '→ Waiting for the app to become healthy'
  for i in \$(seq 1 30); do
    status=\$(docker inspect -f '{{.State.Health.Status}}' cyberlearn-app-1 2>/dev/null || echo starting)
    [ \"\$status\" = healthy ] && { echo '✓ cyberlearn-app-1 is healthy'; exit 0; }
    sleep 3
  done
  echo '✗ App did not become healthy; recent logs:' >&2
  $COMPOSE logs --tail=60 app >&2
  exit 1"
