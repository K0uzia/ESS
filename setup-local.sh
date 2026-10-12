#!/usr/bin/env bash
# ------------------------------------------------------------------------------
# setup-local.sh — Setup local pour SSA_CAPSULE (php -S + SQLite).
#   - Idempotent, lisible, avec logs.
#   - MySQL optionnel (via autres scripts/env, non activé ici).
#
# USAGE:
#   ./setup-local.sh info         # affiche versions & état
#   ./setup-local.sh deps         # installe php-cli/pdo/sqlite/mysql (dnf/apt) puis vérifie
#   ./setup-local.sh init         # crée data/, applique migration SQLite si DB absente, génère bin/ & Makefile si manquants
#   ./setup-local.sh reset        # réinitialise la DB depuis la migration
#   ./setup-local.sh dev          # lance php -S localhost:${PORT:-8080} (docroot: public/)
#   ./setup-local.sh db.shell     # ouvre un shell sqlite
#   ./setup-local.sh bin.install  # (ré)génère bin/dev et bin/db si absents
#   ./setup-local.sh make.inject  # ajoute des cibles Make (non destructif)
#
# ENV (personnalisables):
#   PORT=8080 PUBLIC_DIR=public DATA_DIR=./data MIG_FILE=./migrations/sqlite_init.sql
#   PERSISTENT_DATA_ROOT=/chemin/persistant   # force DB SQLite sur /chemin/persistant/database.sqlite
# ------------------------------------------------------------------------------

set -euo pipefail

# --- Paths & défauts ----------------------------------------------------------
ROOT="$(pwd)"
PUBLIC_DIR="${PUBLIC_DIR:-public}"
DATA_DIR="${DATA_DIR:-$ROOT/data}"
DB_SQLITE="${DB_SQLITE:-}"

MIG_FILE_DEFAULT="$ROOT/migrations/sqlite_init.sql"
MIG_FILE="${MIG_FILE:-$MIG_FILE_DEFAULT}"

# --- Logging helpers ----------------------------------------------------------
log() { printf '· %s\n' "$*"; }
ok() { printf '✅ %s\n' "$*"; }
warn() { printf '⚠️  %s\n' "$*"; }
err() { printf '❌ %s\n' "$*" >&2; }
die() {
    err "$*"
    exit 1
}

need() { command -v "$1" >/dev/null 2>&1 || die "Manque binaire: $1"; }

filesystem_type() {
    df -T "$1" 2>/dev/null | awk 'NR==2 {print $2}'
}

is_network_filesystem() {
    case "$(filesystem_type "$1")" in
        cifs|nfs|nfs4|smbfs|fuse.sshfs|9p) return 0 ;;
        *) return 1 ;;
    esac
}

resolve_db_sqlite() {
    if [[ -n "$DB_SQLITE" ]]; then
        echo "$DB_SQLITE"
        return
    fi

    if [[ -n "${PERSISTENT_DATA_ROOT:-}" ]]; then
        echo "${PERSISTENT_DATA_ROOT%/}/database.sqlite"
        return
    fi

    if is_network_filesystem "$ROOT"; then
        local state_home="${XDG_STATE_HOME:-${HOME}/.local/state}"
        echo "$state_home/$(basename "$ROOT")/database.sqlite"
        return
    fi

    echo "$DATA_DIR/database.sqlite"
}

DB_SQLITE="$(resolve_db_sqlite)"

# --- Common checks / ensure ---------------------------------------------------
ensure_dirs() { mkdir -p "$DATA_DIR" "$(dirname "$DB_SQLITE")" "$ROOT/bin" "$ROOT/config"; }

ensure_public() {
    [[ -d "$PUBLIC_DIR" ]] || die "Dossier public introuvable: $PUBLIC_DIR"
    if [[ ! -f "$PUBLIC_DIR/index.php" ]]; then
        cat >"$PUBLIC_DIR/index.php" <<'PHP'
<?php declare(strict_types=1);
echo "Capsule running (local).";
PHP
        log "Généré $PUBLIC_DIR/index.php minimal (remplace-le par ton front controller)."
    fi
}

ensure_config_php() {
    local cfg="$ROOT/config/database.php"
    [[ -f "$cfg" ]] && return 0
    cat >"$cfg" <<'PHP'
<?php
declare(strict_types=1);

return [
    'dsn'      => $_ENV['DB_DSN']      ?? 'sqlite:' . dirname(__DIR__) . '/data/database.sqlite',
    'user'     => $_ENV['DB_USER']     ?? null,
    'password' => $_ENV['DB_PASSWORD'] ?? null,
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
PHP
    ok "Créé config/database.php (DSN par défaut = SQLite)."
}

resolve_migration() {
    if [[ -f "$MIG_FILE" ]]; then
        echo "$MIG_FILE"
        return
    fi
    if [[ -f "$MIG_FILE_DEFAULT" ]]; then
        echo "$MIG_FILE_DEFAULT"
        return
    fi
    die "Migration introuvable: ni $MIG_FILE, ni $MIG_FILE_DEFAULT"
}

# --- Package manager / deps ---------------------------------------------------
detect_pkg() {
    if command -v dnf >/dev/null 2>&1; then
        echo "dnf"
    elif command -v apt-get >/dev/null 2>&1; then
        echo "apt"
    else echo "unknown"; fi
}

install_deps() {
    local pm pkgs
    pm="$(detect_pkg)"
    case "$pm" in
        dnf)
            pkgs=(php-cli php-pdo sqlite3 php-mysqlnd php-intl php-sqlite3 php-gd)
            log "Installer via dnf: ${pkgs[*]}"
            if command -v sudo >/dev/null 2>&1; then
                sudo dnf install -y "${pkgs[@]}"
            else
                dnf install -y "${pkgs[@]}"
            fi
            ;;
        apt)
            pkgs=(php-cli sqlite3 php-mysql php-intl php-sqlite3 php-gd)
            log "Installer via apt: ${pkgs[*]}"
            if command -v sudo >/dev/null 2>&1; then
                sudo apt-get update
                sudo apt-get install -y "${pkgs[@]}"
            else
                apt-get update
                apt-get install -y "${pkgs[@]}"
            fi
            ;;
        *)
            die "Gestionnaire de paquets non supporté. Installe manuellement :
  - Fedora/RHEL : php-cli php-pdo php-sqlite3 php-mysqlnd php-intl
  - Debian/Ubuntu : php-cli php-sqlite3 php-mysql php-intl"
            ;;
    esac

    ok "Dépendances PHP installées (pdo, sqlite3, mysql, intl)."

    if command -v rg >/dev/null 2>&1; then
        php -m | rg -i 'pdo|sqlite|mysql|intl' || true
    else
        php -m | grep -Ei 'pdo|sqlite|mysql|intl' || true
    fi
    php -v
}

# --- SQLite actions -----------------------------------------------------------
sqlite_apply_migration_fresh() {
    need sqlite3
    local mig
    mig="$(resolve_migration)"

    local backup_sql=""
    backup_sql="$(mktemp)"
    local overrides_file="$ROOT/app/Lang/locales/fr/content_overrides.php"
    local backup_overrides_file=""
    backup_overrides_file="$(mktemp)"

    if [[ -f "$overrides_file" ]]; then
        cp "$overrides_file" "$backup_overrides_file"
    fi

    if [[ -f "$DB_SQLITE" ]] && sqlite3 "$DB_SQLITE" "SELECT 1 FROM sqlite_master WHERE type='table' AND name='page_contents' LIMIT 1;" | grep -q "1"; then
        {
            echo "PRAGMA foreign_keys = OFF;"
            sqlite3 "$DB_SQLITE" "
                SELECT
                    'INSERT INTO page_contents (page_key, section_key, field_key, content_type, value, position, created_at, updated_at) VALUES ('
                    || quote(page_key) || ', '
                    || quote(section_key) || ', '
                    || quote(field_key) || ', '
                    || quote(content_type) || ', '
                    || quote(value) || ', '
                    || quote(position) || ', '
                    || quote(created_at) || ', '
                    || quote(updated_at) || ') '
                    || 'ON CONFLICT(page_key, section_key, field_key) DO UPDATE SET '
                    || 'content_type=excluded.content_type, value=excluded.value, position=excluded.position, updated_at=excluded.updated_at;'
                FROM page_contents
                ORDER BY page_key, section_key, field_key;
            "

            echo "PRAGMA foreign_keys = ON;"
        } > "$backup_sql"
    fi

    mkdir -p "$(dirname "$DB_SQLITE")"
    rm -f "$DB_SQLITE"
    # Schéma ($mig) + données versionnées (migrations/data), via bin/migrate
    DB_DSN="sqlite:$DB_SQLITE" php "$ROOT/bin/migrate" >/dev/null

    if [[ -s "$backup_sql" ]]; then
        sqlite3 "$DB_SQLITE" <"$backup_sql"
    fi

    if [[ -s "$backup_overrides_file" ]]; then
        mkdir -p "$(dirname "$overrides_file")"
        cp "$backup_overrides_file" "$overrides_file"
    fi

    rm -f "$backup_sql"
    rm -f "$backup_overrides_file"

    ok "SQLite initialisée (fresh): $DB_SQLITE (contenu éditorial + overrides FR préservés)"
}

sqlite_apply_migration_if_absent() {
    need sqlite3
    local mig
    mig="$(resolve_migration)"
    if [[ -f "$DB_SQLITE" ]]; then
        log "DB déjà présente: $DB_SQLITE (skip init). Utilise 'reset' pour repartir de zéro."
    else
        mkdir -p "$(dirname "$DB_SQLITE")"
        # Schéma ($mig) + données versionnées (migrations/data), via bin/migrate
        DB_DSN="sqlite:$DB_SQLITE" php "$ROOT/bin/migrate" >/dev/null
        ok "SQLite initialisée: $DB_SQLITE"
    fi
}

# --- bin/ scripts -------------------------------------------------------------
bin_install() {
    local db="$ROOT/bin/db" dev="$ROOT/bin/dev"

    if [[ ! -f "$db" ]]; then
        cat >"$db" <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
DB="./data/database.sqlite"

mkdir -p ./data
case "${1:-}" in
  init|reset) rm -f "$DB"; DB_DSN="sqlite:$DB" php ./bin/migrate >/dev/null; echo "DB initialized at $DB" ;;
  shell) sqlite3 "$DB" ;;
  *) echo "Usage: bin/db {init|reset|shell}" >&2; exit 1;;
esac
BASH
        chmod +x "$db"
        ok "Créé bin/db"
    fi

    if [[ ! -f "$dev" ]]; then
        cat >"$dev" <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
PORT="${PORT:-8080}"
    PHP_OPTS="-d display_errors=1 -d error_reporting=32767 -d zend.assertions=1 -d assert.exception=1 -d upload_max_filesize=64M -d post_max_size=64M -d memory_limit=256M"
echo "→ http://localhost:$PORT"
php $PHP_OPTS -S "localhost:${PORT}" -t public public/router.php
BASH
        chmod +x "$dev"
        ok "Créé bin/dev"
    fi
}

# --- Makefile minimal / enrichissement ---------------------------------------
make_inject() {
    local mk="$ROOT/Makefile"
    if [[ ! -f "$mk" ]]; then
        cat >"$mk" <<'MAKE'
.PHONY: dev db.init db.reset db.shell info
dev: ; bin/dev
db.init: ; bin/db init
db.reset: ; bin/db reset
db.shell: ; bin/db shell
info:
    @php -v
    @sqlite3 --version || true
MAKE
        ok "Créé Makefile minimal."
        return
    fi

    if ! grep -qE '^info:' "$mk"; then
        cat >>"$mk" <<'MAKE'

info:
    @php -v
    @sqlite3 --version || true
MAKE
    fi
    ok "Makefile enrichi (non destructif)."
}

# --- Commands -----------------------------------------------------------------
cmd="${1:-help}"

case "$cmd" in
    info)
        php -v
        php -m | grep -Ei 'pdo|sqlite|mysql|intl' || true
        command -v sqlite3 && sqlite3 --version || true
        command -v mysql && mysql --version || echo "(mysql non installé)"
        echo "Public dir : $PUBLIC_DIR"
        echo "DB (SQLite): $DB_SQLITE"
        if is_network_filesystem "$ROOT"; then
            echo "FS projet   : $(filesystem_type "$ROOT") (base locale forcée hors partage)"
        fi
        echo "Migration   : $(resolve_migration)"
        ;;
    deps)
        install_deps
        ;;
    init)
        need php
        ensure_dirs
        ensure_public
        ensure_config_php
        bin_install
        make_inject
        sqlite_apply_migration_if_absent
        ok "Init terminé. Lance: ./setup-local.sh dev  (ou  make dev)"
        ;;
    reset)
        sqlite_apply_migration_fresh
        ;;
    dev)
        need php
        [[ -d "$PUBLIC_DIR" ]] || die "$PUBLIC_DIR manquant"
        echo "🔌 Serveur dev → http://localhost:${PORT:-8080}  (CTRL+C pour arrêter)"
        php -d display_errors=1 -d error_reporting=32767 -d upload_max_filesize=64M -d post_max_size=64M -d memory_limit=256M -S "localhost:${PORT:-8080}" -t "$PUBLIC_DIR" "$PUBLIC_DIR/router.php"
        ;;
    db.shell)
        need sqlite3
        sqlite3 "$DB_SQLITE"
        ;;
    bin.install)
        bin_install
        ;;
    make.inject)
        make_inject
        ;;
    *)
        cat <<USAGE
Usage: $0 {info|deps|init|reset|dev|db.shell|bin.install|make.inject}

ENV utilisables:
  PORT=8080 PUBLIC_DIR=public DATA_DIR=./data MIG_FILE=./migrations/sqlite_init.sql
    PERSISTENT_DATA_ROOT=/chemin/persistant
    DB_SQLITE=/chemin/persistant/database.sqlite
USAGE
        exit 1
        ;;
esac
