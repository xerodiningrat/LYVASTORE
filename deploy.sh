#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$ROOT_DIR/storage/logs"
LOG_FILE="$LOG_DIR/deploy.log"
DEPLOY_STATUS_FILE="$LOG_DIR/deploy-status.json"
RUN_MIGRATIONS="false"

for arg in "$@"; do
    case "$arg" in
        --migrate)
            RUN_MIGRATIONS="true"
            ;;
        --help|-h)
            cat <<'EOF'
Usage:
  ./deploy.sh
  ./deploy.sh --migrate

Options:
  --migrate   Run php artisan migrate --force after build
EOF
            exit 0
            ;;
        *)
            echo "[deploy] Unknown option: $arg" >&2
            exit 1
            ;;
    esac
done

cd "$ROOT_DIR"
mkdir -p "$LOG_DIR"

TIMESTAMP="$(date '+%Y-%m-%d %H:%M:%S %Z')"

log() {
    local message="$1"

    echo "$message"
    printf '%s %s\n' "[$TIMESTAMP]" "$message" >> "$LOG_FILE"
}

log "[deploy] Starting deploy in $ROOT_DIR"
log "[deploy] Building frontend assets and clearing Laravel cache..."
npm run build:live
log "[deploy] Regenerating sitemap..."
php artisan lyva:generate-sitemap

if [[ "$RUN_MIGRATIONS" == "true" ]]; then
    log "[deploy] Running database migrations..."
    php artisan migrate --force
fi

printf '{\n  "deployed_at": "%s",\n  "deployed_at_label": "%s",\n  "mode": "%s"\n}\n' \
    "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" \
    "$(date '+%d %b %Y, %H:%M %Z')" \
    "$([[ "$RUN_MIGRATIONS" == "true" ]] && echo "build+migrate" || echo "build")" > "$DEPLOY_STATUS_FILE"

log "[deploy] Done."
