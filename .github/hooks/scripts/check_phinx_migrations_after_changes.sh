#!/usr/bin/env bash
set -u

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$repo_root" || exit 0

mapfile -t changed_files < <({ git diff --name-only; git diff --name-only --cached; } | sort -u)

has_migration_change=false
for path in "${changed_files[@]}"; do
  if [[ "$path" == migrations/*.php || "$path" == db/seeds/*.php ]]; then
    has_migration_change=true
    break
  fi
done

if [[ "$has_migration_change" != true ]]; then
  exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
  echo '{"continue": false, "stopReason": "Phinx migration hook blocked: docker CLI not found.", "systemMessage": "Phinx migration hook blocked: docker CLI not found."}'
  exit 2
fi

if ! docker compose version >/dev/null 2>&1; then
  echo '{"continue": false, "stopReason": "Phinx migration hook blocked: docker compose not available.", "systemMessage": "Phinx migration hook blocked: docker compose not available."}'
  exit 2
fi

if ! docker compose up -d --no-build app db >/dev/null 2>&1; then
  docker compose pull app db >/dev/null 2>&1 || true
  docker compose build app >/dev/null 2>&1 || true
  docker compose up -d app db >/dev/null 2>&1 || true
fi

log_file="/tmp/starlight-hook-phinx.log"

if ! docker compose exec -T app env DB_HOST=db DB_PORT=3306 DB_NAME=sdo DB_USER=sdo_admin DB_PASS=password ./vendor/bin/phinx status -e development >"$log_file" 2>&1; then
  echo '{"continue": false, "stopReason": "Phinx status check failed for changed migrations or seeds.", "systemMessage": "Phinx status check failed for changed migrations or seeds. See /tmp/starlight-hook-phinx.log."}'
  exit 2
fi

if ! docker compose exec -T app env DB_HOST=db DB_PORT=3306 DB_NAME=sdo DB_USER=sdo_admin DB_PASS=password ./vendor/bin/phinx migrate -e development --dry-run >>"$log_file" 2>&1; then
  echo '{"continue": false, "stopReason": "Phinx dry-run failed for changed migrations or seeds.", "systemMessage": "Phinx dry-run failed for changed migrations or seeds. See /tmp/starlight-hook-phinx.log."}'
  exit 2
fi

echo '{"continue": true, "systemMessage": "Hook: Phinx migration checks passed for changed migrations or seeds."}'
exit 0
