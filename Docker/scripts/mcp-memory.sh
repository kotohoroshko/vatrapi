#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MEMORY_DIR="${ROOT_DIR}/.mcp"
MEMORY_FILE="${MEMORY_DIR}/memory.jsonl"
SEED_FILE="${MEMORY_DIR}/memory.seed.jsonl"

mkdir -p "${MEMORY_DIR}"
touch "${MEMORY_FILE}"

# Bootstrap from tracked seed when memory file is empty (first run or fresh clone).
if [[ ! -s "${MEMORY_FILE}" && -f "${SEED_FILE}" ]]; then
  cp "${SEED_FILE}" "${MEMORY_FILE}"
fi

export MEMORY_FILE_PATH="${MEMORY_FILE}"

exec npx -y @modelcontextprotocol/server-memory@latest
