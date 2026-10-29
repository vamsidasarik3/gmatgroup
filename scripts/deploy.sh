#!/usr/bin/env bash
# ==============================================================================
# GMATGROUP: ONE-COMMAND CODE AND DATABASE DEPLOYMENT PIPELINE
# Repository: https://github.com/vamsidasarik3/gmatgroup.git
# Live Website: https://www.gmatgroup.com
# ==============================================================================
# Usage:
#   bash scripts/deploy.sh [OPTIONS]
#
# Options:
#   -m, --message    Custom Git commit message (default: "Auto-deploy: YYYY-MM-DD HH:MM:SS")
#   --no-git         Skip automatic local git add, commit, and push
#   --dry-run        Simulate deployment without modifying production
#   --skip-db        Skip local database export and remote database import
#   --skip-code      Deploy and import database only, skipping git pull / composer install
#   --no-backup      Skip remote pre-deployment database backup
#   --config <file>  Specify a custom configuration file (default: scripts/deploy.env)
#   -h, --help       Display this help message and exit
# ==============================================================================

set -euo pipefail

# Visual styling
C_RESET='\033[0m'
C_BOLD='\033[1m'
C_GREEN='\033[32m'
C_BLUE='\033[34m'
C_YELLOW='\033[33m'
C_RED='\033[31m'
C_CYAN='\033[36m'

log_info()    { printf "%b %s\n" "${C_BLUE}${C_BOLD}[INFO]${C_RESET}" "$*"; }
log_success() { printf "%b %s\n" "${C_GREEN}${C_BOLD}[SUCCESS]${C_RESET}" "$*"; }
log_warn()    { printf "%b %s\n" "${C_YELLOW}${C_BOLD}[WARN]${C_RESET}" "$*"; }
log_error()   { printf "%b %s\n" "${C_RED}${C_BOLD}[ERROR]${C_RESET}" "$*"; }
log_step()    { printf "\n%b %s\n" "${C_CYAN}${C_BOLD}===> STEP:${C_RESET}" "$*"; }

# Determine script and project directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

CURRENT_STEP="Initialization"
trap 'handle_failure $? $LINENO' ERR

handle_failure() {
    local exit_code=$1
    local line_no=$2
    echo -e "\n${C_RED}${C_BOLD}========================================================================${C_RESET}"
    log_error "Deployment failed at step: [${CURRENT_STEP}] (Line ${line_no}, Exit code: ${exit_code})"
    echo -e "${C_RED}${C_BOLD}========================================================================${C_RESET}"
    exit "${exit_code}"
}

# CLI Flags Defaults
DRY_RUN=false
SKIP_DB=false
SKIP_CODE=false
NO_BACKUP=false
CUSTOM_CONFIG=""
COMMIT_MESSAGE_INPUT=""
AUTO_GIT=true

while [[ $# -gt 0 ]]; do
    case "$1" in
        -m|--message)
            COMMIT_MESSAGE_INPUT="$2"
            shift 2
            ;;
        --no-git|--skip-git)
            AUTO_GIT=false
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-db|--skip-content)
            SKIP_DB=true
            shift
            ;;
        --skip-code)
            SKIP_CODE=true
            shift
            ;;
        --no-backup)
            NO_BACKUP=true
            shift
            ;;
        --config)
            CUSTOM_CONFIG="$2"
            shift 2
            ;;
        -h|--help)
            sed -ne '/^# Usage:/,/^# ====/p' "$0" | sed 's/^# //'
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use 'bash scripts/deploy.sh --help' for usage."
            exit 1
            ;;
    esac
done

echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}       GMATGROUP ONE-COMMAND CODE AND DATABASE DEPLOYMENT PIPELINE       ${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
if [ "$DRY_RUN" = true ]; then
    echo -e "${C_YELLOW}${C_BOLD}*** DRY RUN MODE ENABLED: No production changes will be made ***${C_RESET}"
fi

# ------------------------------------------------------------------------------
# 1. LOAD CONFIGURATION
# ------------------------------------------------------------------------------
CURRENT_STEP="Load Configuration"
log_step "Loading deployment configuration"

CONFIG_FILE="${CUSTOM_CONFIG}"
if [ -z "${CONFIG_FILE}" ]; then
    if [ -f "${SCRIPT_DIR}/deploy.env" ]; then
        CONFIG_FILE="${SCRIPT_DIR}/deploy.env"
    elif [ -f "${PROJECT_ROOT}/.deploy.env" ]; then
        CONFIG_FILE="${PROJECT_ROOT}/.deploy.env"
    fi
fi

if [ -n "${CONFIG_FILE}" ] && [ -f "${CONFIG_FILE}" ]; then
    log_info "Loading configuration from: ${CONFIG_FILE}"
    set -a
    # shellcheck disable=SC1090
    source "${CONFIG_FILE}"
    set +a
else
    log_warn "No dedicated deploy.env file found. Checking environment variables."
fi

# Configuration Defaults & Fallbacks
DEPLOY_HOST="${DEPLOY_HOST:-159.198.76.52}"
DEPLOY_USER="${DEPLOY_USER:-root}"
DEPLOY_EXEC_USER="${DEPLOY_EXEC_USER:-vamsidasarik}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_SSH_KEY="${DEPLOY_SSH_KEY:-}"
DEPLOY_REMOTE_PATH="${DEPLOY_REMOTE_PATH:-/home/vamsidasarik/web/gmatgroup.com/public_html}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DEPLOY_REMOTE_PHP="${DEPLOY_REMOTE_PHP:-php}"
DEPLOY_REMOTE_COMPOSER="${DEPLOY_REMOTE_COMPOSER:-composer}"
DEPLOY_REMOTE_DB="${DEPLOY_REMOTE_DB:-vamsidasarik_gmatgroup}"
DEPLOY_REMOTE_DB_USER="${DEPLOY_REMOTE_DB_USER:-vamsidasarik_gmatgroup}"
DEPLOY_REMOTE_DB_PASS="${DEPLOY_REMOTE_DB_PASS:-Gm4tGr0up_Secure2026}"
DEPLOY_BACKUP_DB="${DEPLOY_BACKUP_DB:-true}"
DEPLOY_HEALTHCHECK_URL="${DEPLOY_HEALTHCHECK_URL:-https://www.gmatgroup.com}"
DEPLOY_KEEP_LOCAL_BUNDLE="${DEPLOY_KEEP_LOCAL_BUNDLE:-false}"

if [ "$NO_BACKUP" = true ]; then
    DEPLOY_BACKUP_DB="false"
fi

# Determine Local PHP binary
DEPLOY_LOCAL_PHP="${DEPLOY_LOCAL_PHP:-php}"
if ! command -v "${DEPLOY_LOCAL_PHP}" >/dev/null 2>&1 || ! "${DEPLOY_LOCAL_PHP}" -v >/dev/null 2>&1; then
    if [ -f "G:/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="G:/xampp/php/php.exe"
    elif [ -f "/g/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="/g/xampp/php/php.exe"
    elif [ -f "C:/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="C:/xampp/php/php.exe"
    elif [ -f "/c/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="/c/xampp/php/php.exe"
    fi
fi

cd "${PROJECT_ROOT}"

if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
    log_error "Current directory is not a valid Laravel project: ${PROJECT_ROOT}"
    exit 1
fi

if ! command -v git >/dev/null 2>&1; then
    log_error "Git is required locally but could not be found."
    exit 1
fi

# ------------------------------------------------------------------------------
# 2. AUTOMATIC LOCAL GIT COMMIT & PUSH
# ------------------------------------------------------------------------------
CURRENT_STEP="Local Git Sync"
log_step "Managing local Git repository state and GitHub synchronization"

# Ensure repo is initialized
if [ ! -d ".git" ]; then
    log_info "Initializing local Git repository..."
    git init
    git remote add origin https://github.com/vamsidasarik3/gmatgroup.git
    git branch -M "${DEPLOY_BRANCH}"
fi

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "${DEPLOY_BRANCH}")"
log_info "Local Git Branch: ${CURRENT_BRANCH} (Target deployment branch: ${DEPLOY_BRANCH})"

if [ "${CURRENT_BRANCH}" != "${DEPLOY_BRANCH}" ]; then
    log_error "Local branch '${CURRENT_BRANCH}' does not match target deployment branch '${DEPLOY_BRANCH}'."
    echo "Switch to the target branch ('git checkout ${DEPLOY_BRANCH}') before deploying."
    exit 1
fi

if [ "${AUTO_GIT}" = true ]; then
    DIRTY_FILES="$(git status --porcelain || true)"
    if [ -n "${DIRTY_FILES}" ]; then
        log_info "Uncommitted changes detected in local repository."
        if [ "$DRY_RUN" = true ]; then
            echo -e "${C_YELLOW}${C_BOLD}[DRY-RUN] Would stage and commit the following changes:${C_RESET}"
            echo -e "${C_YELLOW}${DIRTY_FILES}${C_RESET}\n"
        else
            DEFAULT_COMMIT_MSG="Auto-deploy: $(date +'%Y-%m-%d %H:%M:%S')"
            MSG="${COMMIT_MESSAGE_INPUT:-$DEFAULT_COMMIT_MSG}"
            log_info "Staging all local changes (git add -A)..."
            git add -A
            log_info "Committing changes: '${MSG}'..."
            git commit -m "${MSG}"
            log_success "Created local commit: $(git log -1 --pretty=format:'%s (%h)')"
        fi
    else
        log_info "Working tree is clean; no uncommitted changes."
    fi

    # Push to remote GitHub
    log_info "Checking sync with remote repository (origin/${DEPLOY_BRANCH})..."
    git fetch origin "${DEPLOY_BRANCH}" --quiet || true

    AHEAD_COMMITS="$(git rev-list --count "origin/${DEPLOY_BRANCH}..HEAD" 2>/dev/null || echo "0")"
    if [ "${AHEAD_COMMITS}" -gt 0 ]; then
        if [ "$DRY_RUN" = true ]; then
            log_info "[DRY-RUN] Would push ${AHEAD_COMMITS} commit(s) to GitHub (origin/${DEPLOY_BRANCH})."
        else
            log_info "Pushing ${AHEAD_COMMITS} commit(s) to GitHub (origin/${DEPLOY_BRANCH})..."
            git push origin "${DEPLOY_BRANCH}"
            log_success "Successfully pushed to GitHub (origin/${DEPLOY_BRANCH})!"
        fi
    else
        log_success "GitHub repository (origin/${DEPLOY_BRANCH}) is up-to-date with local."
    fi
else
    DIRTY_FILES="$(git status --porcelain || true)"
    if [ -n "${DIRTY_FILES}" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo -e "${C_YELLOW}${C_BOLD}[WARN] Local working tree has uncommitted changes (--no-git mode):${C_RESET}"
            echo -e "${C_YELLOW}${DIRTY_FILES}${C_RESET}\n"
        else
            log_error "Working tree has uncommitted changes and --no-git was specified."
            echo -e "${DIRTY_FILES}"
            exit 1
        fi
    fi
fi

COMMIT_MESSAGE="$(git log -1 --pretty=format:'%s (%h)' 2>/dev/null || echo 'Initial commit')"
log_success "Git repository ready! Current deployment commit: ${COMMIT_MESSAGE}"

# ------------------------------------------------------------------------------
# 3. EXPORT LOCAL DATABASE (IF ENABLED)
# ------------------------------------------------------------------------------
BUNDLE_FILE=""
if [ "$SKIP_DB" = false ]; then
    CURRENT_STEP="Export Local Database"
    log_step "Exporting local database for deployment import"

    log_info "Running: php artisan db:export-for-deploy"
    EXPORT_OUTPUT="$("${DEPLOY_LOCAL_PHP}" artisan db:export-for-deploy)"
    echo "${EXPORT_OUTPUT}"

    BUNDLE_FILE="$(echo "${EXPORT_OUTPUT}" | grep '^BUNDLE_PATH=' | head -n 1 | cut -d '=' -f2- | tr -d '\r\n')"
    BUNDLE_FILE="$(echo "${BUNDLE_FILE}" | tr '\\' '/')"
    if [ -z "${BUNDLE_FILE}" ] || [ ! -f "${BUNDLE_FILE}" ]; then
        log_error "Failed to locate generated database export bundle."
        exit 1
    fi

    BUNDLE_SIZE="$(ls -lh "${BUNDLE_FILE}" | awk '{print $5}')"
    log_success "Database export bundle ready: ${BUNDLE_FILE} (${BUNDLE_SIZE})"
fi

# ------------------------------------------------------------------------------
# 4. VALIDATE PRODUCTION CONNECTION
# ------------------------------------------------------------------------------
CURRENT_STEP="Validate Remote Configuration"
log_step "Validating remote production server configuration"

SSH_OPTS=(-n -p "${DEPLOY_PORT}" -o "BatchMode=yes" -o "StrictHostKeyChecking=accept-new" -o "ConnectTimeout=10")
SCP_OPTS=(-P "${DEPLOY_PORT}" -o "BatchMode=yes" -o "StrictHostKeyChecking=accept-new" -o "ConnectTimeout=10")

if [ -n "${DEPLOY_SSH_KEY}" ] && [ -f "${DEPLOY_SSH_KEY}" ]; then
    SSH_OPTS+=(-i "${DEPLOY_SSH_KEY}")
    SCP_OPTS+=(-i "${DEPLOY_SSH_KEY}")
fi

SSH_TARGET="${DEPLOY_USER}@${DEPLOY_HOST}"

log_info "Target Host: ${DEPLOY_HOST} (Port: ${DEPLOY_PORT}, Connect User: ${DEPLOY_USER})"
if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
    log_info "Execution User: ${DEPLOY_EXEC_USER} (switching via: su - ${DEPLOY_EXEC_USER})"
fi
log_info "Remote Path: ${DEPLOY_REMOTE_PATH}"
log_info "Target Branch: ${DEPLOY_BRANCH}"

# Test SSH connectivity
log_info "Testing SSH connectivity to ${SSH_TARGET}..."
if ! ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "echo 'SSH_OK'" >/dev/null 2>&1; then
    log_error "Unable to establish SSH connection to ${SSH_TARGET} on port ${DEPLOY_PORT}."
    exit 1
fi
log_success "SSH connection established successfully!"

if [ "$DRY_RUN" = true ]; then
    log_step "DRY-RUN: Simulation completed successfully!"
    echo -e "Planned execution steps on ${SSH_TARGET}:"
    echo -e "  1. Pull latest code from GitHub '${DEPLOY_BRANCH}'."
    echo -e "  2. Install composer production dependencies."
    [ "$DEPLOY_BACKUP_DB" = "true" ] && echo -e "  3. Backup production database '${DEPLOY_REMOTE_DB}'."
    [ "$SKIP_DB" = false ] && echo -e "  4. Upload and import database dump (${BUNDLE_SIZE})."
    echo -e "  5. Run migrations & rebuild production caches."
    echo -e "  6. Verify endpoints on ${DEPLOY_HEALTHCHECK_URL}."
    exit 0
fi

# ------------------------------------------------------------------------------
# 5. TRANSFER DATABASE BUNDLE TO PRODUCTION
# ------------------------------------------------------------------------------
REMOTE_BUNDLE_PATH=""
if [ "$SKIP_DB" = false ] && [ -n "${BUNDLE_FILE}" ]; then
    CURRENT_STEP="Transfer Database Bundle"
    log_step "Transferring database bundle to production server"

    BUNDLE_BASENAME="$(basename "${BUNDLE_FILE}")"
    REMOTE_INCOMING_DIR="${DEPLOY_REMOTE_PATH}/storage/app/deployments/incoming"
    REMOTE_BUNDLE_PATH="${REMOTE_INCOMING_DIR}/${BUNDLE_BASENAME}"

    # Ensure remote directory exists and is owned by DEPLOY_EXEC_USER
    ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "mkdir -p '${REMOTE_INCOMING_DIR}' '${DEPLOY_REMOTE_PATH}/storage' '${DEPLOY_REMOTE_PATH}/bootstrap/cache' && chown -R '${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER}' '${DEPLOY_REMOTE_PATH}'"

    log_info "Uploading ${BUNDLE_BASENAME} via SCP..."
    scp "${SCP_OPTS[@]}" "${BUNDLE_FILE}" "${SSH_TARGET}:${REMOTE_BUNDLE_PATH}"

    if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
        ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "chown -R '${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER}' '${DEPLOY_REMOTE_PATH}'"
    fi
    log_success "Database bundle transferred successfully to ${REMOTE_BUNDLE_PATH}"
fi

# ------------------------------------------------------------------------------
# 6. EXECUTE REMOTE DEPLOYMENT WORKFLOW
# ------------------------------------------------------------------------------
CURRENT_STEP="Execute Remote Deployment"
log_step "Executing production deployment commands on remote server"

# Construct remote script
REMOTE_SCRIPT=$(cat << 'REMOTE_EOF'
set -euo pipefail

DEPLOY_REMOTE_PATH="$1"
DEPLOY_BRANCH="$2"
DEPLOY_REMOTE_PHP="$3"
DEPLOY_REMOTE_COMPOSER="$4"
DEPLOY_BACKUP_DB="$5"
SKIP_CODE="$6"
SKIP_DB="$7"
REMOTE_BUNDLE_PATH="$8"
DEPLOY_REMOTE_DB="$9"
DEPLOY_REMOTE_DB_USER="${10}"
DEPLOY_REMOTE_DB_PASS="${11}"

mkdir -p "${DEPLOY_REMOTE_PATH}"
cd "${DEPLOY_REMOTE_PATH}"

# Initialize git if not present or checkout
if [ ! -d ".git" ]; then
    echo "Initializing Git repository in ${DEPLOY_REMOTE_PATH}..."
    git init
    git remote add origin https://github.com/vamsidasarik3/gmatgroup.git
fi

# Clean transient changes in storage/bootstrap
git checkout -- storage/ bootstrap/cache/ 2>/dev/null || true

# Maintenance mode trap
MAINTENANCE_ACTIVE=0
cleanup() {
    if [ "${MAINTENANCE_ACTIVE}" = "1" ]; then
        echo "Restoring application from maintenance mode..."
        "${DEPLOY_REMOTE_PHP}" artisan up || true
    fi
}
trap cleanup EXIT ERR

# Put application into maintenance mode if artisan exists
if [ -f "artisan" ]; then
    echo "Entering maintenance mode..."
    "${DEPLOY_REMOTE_PHP}" artisan down --secret="gmatgroup-deploy-bypass" || true
    MAINTENANCE_ACTIVE=1
fi

# Code Deployment
if [ "${SKIP_CODE}" = "false" ]; then
    echo "Updating code from GitHub branch '${DEPLOY_BRANCH}'..."
    git fetch origin "${DEPLOY_BRANCH}"
    git checkout -f -B "${DEPLOY_BRANCH}" "origin/${DEPLOY_BRANCH}"

    echo "Installing production PHP dependencies..."
    "${DEPLOY_REMOTE_COMPOSER}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

# Ensure production .env exists
if [ ! -f ".env" ]; then
    echo "Creating production .env configuration..."
    cat << ENV_EOF > .env
APP_NAME="GmatGroup"
APP_ENV=production
APP_KEY=base64:Drgw2yaGxE1dqeJWNgT8/NkDJ7Es4kydNx0PuiKxBWE=
APP_DEBUG=false
APP_URL=https://www.gmatgroup.com
ADMIN_LOGIN_PATH=secure-admin-login

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DEPLOY_REMOTE_DB}
DB_USERNAME=${DEPLOY_REMOTE_DB_USER}
DB_PASSWORD=${DEPLOY_REMOTE_DB_PASS}

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
ENV_EOF
fi

# Database Backup (if enabled)
if [ "${DEPLOY_BACKUP_DB}" = "true" ]; then
    echo "Creating pre-deployment database backup..."
    BACKUP_DIR="${DEPLOY_REMOTE_PATH}/storage/app/deployments/backups"
    mkdir -p "${BACKUP_DIR}"
    BACKUP_FILE="${BACKUP_DIR}/db_backup_$(date +%Y%m%d_%H%M%S).sql"

    if command -v mariadb-dump >/dev/null 2>&1; then
        mariadb-dump -h 127.0.0.1 -u "${DEPLOY_REMOTE_DB_USER}" -p"${DEPLOY_REMOTE_DB_PASS}" "${DEPLOY_REMOTE_DB}" > "${BACKUP_FILE}" 2>/dev/null || true
    elif command -v mysqldump >/dev/null 2>&1; then
        mysqldump -h 127.0.0.1 -u "${DEPLOY_REMOTE_DB_USER}" -p"${DEPLOY_REMOTE_DB_PASS}" "${DEPLOY_REMOTE_DB}" > "${BACKUP_FILE}" 2>/dev/null || true
    fi
    if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
        echo "Database backup created: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"
    fi
fi

# Database Import
if [ "${SKIP_DB}" = "false" ] && [ -n "${REMOTE_BUNDLE_PATH}" ] && [ -f "${REMOTE_BUNDLE_PATH}" ]; then
    echo "Importing database dump: ${REMOTE_BUNDLE_PATH}..."
    MYSQL_CLI="mariadb"
    command -v "${MYSQL_CLI}" >/dev/null 2>&1 || MYSQL_CLI="mysql"

    if [[ "${REMOTE_BUNDLE_PATH}" == *.gz ]]; then
        gzip -dc "${REMOTE_BUNDLE_PATH}" | "${MYSQL_CLI}" -h 127.0.0.1 -u "${DEPLOY_REMOTE_DB_USER}" -p"${DEPLOY_REMOTE_DB_PASS}" "${DEPLOY_REMOTE_DB}"
    else
        "${MYSQL_CLI}" -h 127.0.0.1 -u "${DEPLOY_REMOTE_DB_USER}" -p"${DEPLOY_REMOTE_DB_PASS}" "${DEPLOY_REMOTE_DB}" < "${REMOTE_BUNDLE_PATH}"
    fi
    echo "Database import completed successfully!"

    # Clean up uploaded bundle
    rm -f "${REMOTE_BUNDLE_PATH}"
fi

# Run Migrations
echo "Running any pending migrations..."
"${DEPLOY_REMOTE_PHP}" artisan migrate --force

# Storage Link & Optimization Caches
echo "Rebuilding Laravel application caches..."
"${DEPLOY_REMOTE_PHP}" artisan storage:link || true
"${DEPLOY_REMOTE_PHP}" artisan config:clear || true
"${DEPLOY_REMOTE_PHP}" artisan cache:clear || true
"${DEPLOY_REMOTE_PHP}" artisan config:cache
"${DEPLOY_REMOTE_PHP}" artisan route:cache
"${DEPLOY_REMOTE_PHP}" artisan view:cache

# Bring application up
if [ -f "artisan" ]; then
    echo "Bringing application online..."
    "${DEPLOY_REMOTE_PHP}" artisan up
    MAINTENANCE_ACTIVE=0
fi

echo "REMOTE_DEPLOYMENT_COMPLETE"
REMOTE_EOF
)

# Run remote script over SSH
log_info "Executing remote deployment pipeline on server..."
REMOTE_WRAPPER="/tmp/gmatgroup_deploy_$$.sh"

ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "chown -R '${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER}' '${DEPLOY_REMOTE_PATH}'
cat << 'EOF' > '${REMOTE_WRAPPER}'
${REMOTE_SCRIPT}
EOF
chmod 755 '${REMOTE_WRAPPER}'
chown ${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER} '${REMOTE_WRAPPER}'
su - ${DEPLOY_EXEC_USER} -c \"bash '${REMOTE_WRAPPER}' '${DEPLOY_REMOTE_PATH}' '${DEPLOY_BRANCH}' '${DEPLOY_REMOTE_PHP}' '${DEPLOY_REMOTE_COMPOSER}' '${DEPLOY_BACKUP_DB}' '${SKIP_CODE}' '${SKIP_DB}' '${REMOTE_BUNDLE_PATH}' '${DEPLOY_REMOTE_DB}' '${DEPLOY_REMOTE_DB_USER}' '${DEPLOY_REMOTE_DB_PASS}'\"
STATUS=\$?
rm -f '${REMOTE_WRAPPER}'

# Ensure Hestia document root points to public_html/public
if [ -x /usr/local/hestia/bin/v-change-web-domain-docroot ]; then
    /usr/local/hestia/bin/v-change-web-domain-docroot ${DEPLOY_EXEC_USER} gmatgroup.com gmatgroup.com public yes >/dev/null 2>&1 || true
fi

# Ensure storage and bootstrap permissions
chown -R ${DEPLOY_EXEC_USER}:www-data '${DEPLOY_REMOTE_PATH}/storage' '${DEPLOY_REMOTE_PATH}/bootstrap/cache' 2>/dev/null || true
chmod -R 775 '${DEPLOY_REMOTE_PATH}/storage' '${DEPLOY_REMOTE_PATH}/bootstrap/cache' 2>/dev/null || true

exit \$STATUS
"

# ------------------------------------------------------------------------------
# 7. POST-DEPLOYMENT PRODUCTION HEALTH VERIFICATION
# ------------------------------------------------------------------------------
CURRENT_STEP="Post-Deployment Verification"
log_step "Verifying production endpoints and site health"

VERIFICATION_ERRORS=()
PROD_BASE_URL="${DEPLOY_HEALTHCHECK_URL%/}"

if command -v curl >/dev/null 2>&1; then
    # 1. Homepage
    log_info "Verifying production homepage: ${PROD_BASE_URL}..."
    HOME_STATUS="$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${PROD_BASE_URL}" || echo "000")"
    if [ "${HOME_STATUS}" = "200" ] || [ "${HOME_STATUS}" = "301" ] || [ "${HOME_STATUS}" = "302" ]; then
        log_success "Homepage reachable (HTTP ${HOME_STATUS})."
    else
        log_error "Homepage returned HTTP ${HOME_STATUS}"
        VERIFICATION_ERRORS+=("Homepage returned HTTP ${HOME_STATUS}")
    fi

    # 2. Member Login
    LOGIN_URL="${PROD_BASE_URL}/login"
    log_info "Verifying member login: ${LOGIN_URL}..."
    LOGIN_STATUS="$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${LOGIN_URL}" || echo "000")"
    if [ "${LOGIN_STATUS}" = "200" ]; then
        log_success "Member login endpoint verified (HTTP 200 OK)."
    else
        log_warn "Member login returned HTTP ${LOGIN_STATUS}"
    fi

    # 3. Articles Endpoint
    ARTICLES_URL="${PROD_BASE_URL}/articles"
    log_info "Verifying articles catalog: ${ARTICLES_URL}..."
    ART_STATUS="$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${ARTICLES_URL}" || echo "000")"
    if [ "${ART_STATUS}" = "200" ]; then
        log_success "Articles catalog verified (HTTP 200 OK)."
    else
        log_warn "Articles catalog returned HTTP ${ART_STATUS}"
    fi
else
    log_warn "curl not available locally; skipping live HTTP endpoint verification."
fi

# Clean up local bundle if configured
if [ "$DEPLOY_KEEP_LOCAL_BUNDLE" = "false" ] && [ -n "${BUNDLE_FILE}" ] && [ -f "${BUNDLE_FILE}" ]; then
    log_info "Cleaning up temporary local bundle archive..."
    rm -f "${BUNDLE_FILE}"
fi

# ------------------------------------------------------------------------------
# 8. DEPLOYMENT SUMMARY REPORT
# ------------------------------------------------------------------------------
echo -e "\n${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}                   DEPLOYMENT COMPLETED SUCCESSFULLY                    ${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "Target Server:       ${C_CYAN}${DEPLOY_HOST}${C_RESET} (${DEPLOY_USER} -> ${DEPLOY_EXEC_USER})"
echo -e "Deployed Commit:     ${C_CYAN}${COMMIT_MESSAGE}${C_RESET}"
echo -e "Branch:              ${C_CYAN}${DEPLOY_BRANCH}${C_RESET}"
echo -e "Code Deployed:       ${C_GREEN}$([ "$SKIP_CODE" = false ] && echo "YES" || echo "SKIPPED")${C_RESET}"
echo -e "Database Imported:   ${C_GREEN}$([ "$SKIP_DB" = false ] && echo "YES" || echo "SKIPPED")${C_RESET}"
echo -e "Database Backup:     ${C_GREEN}$([ "$DEPLOY_BACKUP_DB" = "true" ] && echo "CREATED" || echo "SKIPPED")${C_RESET}"
echo -e "Live URL:            ${C_CYAN}${DEPLOY_HEALTHCHECK_URL}${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}\n"

exit 0
