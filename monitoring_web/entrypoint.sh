#!/bin/sh
set -e

cat > /usr/share/nginx/html/config.js <<EOF
window.__APP_CONFIG__ = {
  VITE_API_URL: "${VITE_API_URL}",
  VITE_POLL_INTERVAL: "${VITE_POLL_INTERVAL}"
};
EOF

exec nginx -g "daemon off;"
