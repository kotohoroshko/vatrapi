#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${ROOT_DIR}/Docker/docker-compose.yml"

cd "${ROOT_DIR}"

if [[ "${1:-}" == "--check" ]]; then
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan boost:mcp --help >/dev/null
  echo "Laravel Boost MCP is available inside the app container."
  exit 0
fi

exec docker compose -f "${COMPOSE_FILE}" exec -T app php artisan boost:mcp
