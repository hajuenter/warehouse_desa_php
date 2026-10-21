#!/bin/bash
set -e

echo "=== Generating .env file from environment variables ==="

# Generate .env file so PHP app can read it via Dotenv
# DB_HOST points to the external MariaDB server
cat > /app/.env <<EOF
PORT="${PORT:-3000}"
FRONTEND_URL="${FRONTEND_URL:-http://localhost:8090}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-desa_induk}"
JWT_SECRET="${JWT_SECRET:-}"
JWT_EXPIRES_IN="${JWT_EXPIRES_IN:-8h}"
ADMIN_USERNAME="${ADMIN_USERNAME:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin123}"
ADMIN_EMAIL="${ADMIN_EMAIL:-}"
SMTP_HOST="${SMTP_HOST:-smtp.gmail.com}"
SMTP_PORT="${SMTP_PORT:-587}"
SMTP_USER="${SMTP_USER:-}"
SMTP_PASS="${SMTP_PASS:-}"
EOF

echo ".env file generated."

echo "=== Ensuring data directory is writable ==="

# Ensure data directory exists and is writable for admin.json and reset_tokens.json
mkdir -p /app/data
chmod -R 777 /app/data

echo "=== Starting services via supervisord ==="

# Start supervisord (manages PHP app)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
