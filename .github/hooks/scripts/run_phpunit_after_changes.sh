#!/usr/bin/env bash
set -u

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$repo_root" || exit 0

# Only run when there are working tree or staged changes.
if git diff --quiet && git diff --cached --quiet; then
  exit 0
fi

mapfile -t changed_files < <((git diff --name-only; git diff --name-only --cached) | sort -u)

has_php_related_change=false
for path in "${changed_files[@]}"; do
  if [[ "$path" == *.php ]]; then
    has_php_related_change=true
    break
  fi
done

if [[ "$has_php_related_change" != true ]]; then
  exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
  exit 0
fi

if ! docker compose version >/dev/null 2>&1; then
  exit 0
fi

if ! docker compose up -d --no-build app db redis >/dev/null 2>&1; then
  docker compose pull app db redis >/dev/null 2>&1 || true
  docker compose build app >/dev/null 2>&1 || true
  docker compose up -d app db redis >/dev/null 2>&1 || true
fi

declare -a targets
for path in "${changed_files[@]}"; do
  if [[ "$path" == tests/*Test.php ]]; then
    targets+=("$path")
    continue
  fi

  if [[ "$path" == src/*.php || "$path" == src/*/*.php || "$path" == src/*/*/*.php ]]; then
    base="$(basename "$path" .php)"
    for candidate in "tests/Unit/${base}Test.php" "tests/Integration/${base}Test.php" "tests/Feature/${base}Test.php"; do
      if [[ -f "$candidate" ]]; then
        targets+=("$candidate")
      fi
    done
  fi
done

if [[ ${#targets[@]} -gt 0 ]]; then
  # Deduplicate while preserving order.
  mapfile -t unique_targets < <(printf '%s\n' "${targets[@]}" | awk '!seen[$0]++')
  if docker compose exec -T app ./vendor/bin/phpunit "${unique_targets[@]}" >/tmp/starlight-hook-phpunit.log 2>&1; then
    exit 0
  fi

  echo '{"continue": true, "systemMessage": "Hook: Docker PHPUnit targeted tests failed. See /tmp/starlight-hook-phpunit.log for details."}'
  exit 0
fi

if docker compose exec -T app ./vendor/bin/phpunit --testsuite Unit >/tmp/starlight-hook-phpunit.log 2>&1; then
  exit 0
fi

echo '{"continue": true, "systemMessage": "Hook: Docker PHPUnit unit suite failed. See /tmp/starlight-hook-phpunit.log for details."}'
exit 0
