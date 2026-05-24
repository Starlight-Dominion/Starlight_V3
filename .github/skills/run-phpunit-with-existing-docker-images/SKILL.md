---
name: run-phpunit-with-existing-docker-images
description: >
  Run PHPUnit in Starlight_V3 using existing Docker images and containers first,
   without local PHP dependency and with automatic pull or build only when required. Use when tests
  fail locally due PHP version mismatch, or when asked to run specific PHPUnit files,
  suites, or filters through Docker Compose.
---

# Run PHPUnit With Existing Docker Images

Use this workflow to execute PHPUnit from Docker so host PHP version differences do not block test runs.

## Inputs

- Optional test target path, for example `tests/Unit/DiscordLinkServiceTest.php`
- Optional PHPUnit args, for example `--filter testName`

If no target is provided, run the full suite.

## Workflow

1. Confirm repository root is Starlight_V3.
2. Check Docker Compose availability:
   - `docker compose version`
3. Detect whether the app container is already running:
   - `docker compose ps app`
4. Start only required services without rebuilding images:
   - `docker compose up -d --no-build app db redis`
5. If the previous step reports missing image artifacts, resolve automatically:
   - `docker compose pull app db redis || true`
   - `docker compose build app`
   - `docker compose up -d app db redis`
6. Verify runtime PHP version in container:
   - `docker compose exec -T app php -v`
7. Run PHPUnit in container with project autoload:
   - Full suite: `docker compose exec -T app ./vendor/bin/phpunit`
   - Single file: `docker compose exec -T app ./vendor/bin/phpunit <target>`
   - File plus filter: `docker compose exec -T app ./vendor/bin/phpunit <target> --filter <pattern>`
8. If `exec` fails because container is not running, use one-shot run with existing image:
   - `docker compose run --rm app ./vendor/bin/phpunit <args>`
9. Summarize outcomes:
   - Passed or failed
   - Failing tests with first actionable error lines
   - Exact command used

## Decision Points

- If services are down but images exist:
  - Start with `up -d --no-build` and continue.
- If images are missing and `--no-build` fails:
   - Pull and build automatically, then continue.
- If DB connection errors appear in tests:
  - Ensure `db` service is running, then rerun.
- If only one test file is requested:
  - Do not run full suite unless requested.

## Completion Criteria

- PHPUnit command executed inside Docker app container or one-shot app container.
- Report includes command, exit status, and concise failure summary when failing.
- No host-PHP execution used unless explicitly requested.
- Missing image artifacts are handled automatically through pull or build before retrying.

## Example Prompts

- Run PHPUnit via Docker for tests/Unit/DiscordLinkServiceTest.php.
- Run the full PHPUnit suite using existing images only.
- Run only tests matching DiscordLink in Docker and show the first failure.
