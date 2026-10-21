#!/bin/bash
set -e

echo "=== Generating .env file from environment variables ==="

# Generate .env file so PHP app can read it via Dotenv
cat > /app/.env <<EOF
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-desa_induk}"
DESA_KODE="${DESA_KODE:-}"
WEBHOOK_TOKEN="${WEBHOOK_TOKEN:-}"
WEBHOOK_URL="${WEBHOOK_URL:-http://localhost:3000/api/webhook}"
WEBHOOK_TIMEOUT="${WEBHOOK_TIMEOUT:-10}"
MAX_RETRY="${MAX_RETRY:-5}"
POLL_INTERVAL="${POLL_INTERVAL:-3}"
EOF

echo ".env file generated."

echo "=== Starting queue worker ==="

# Start the queue worker (long-running process)
exec php /app/index.php
