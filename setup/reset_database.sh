#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
DB_FILE="${ROOT_DIR}/data/game.sqlite"
DB_WAL="${DB_FILE}-wal"
DB_SHM="${DB_FILE}-shm"
SERVER_HOST="${PHP_SERVER_HOST:-127.0.0.1}"
SERVER_PORT="${PHP_SERVER_PORT:-8080}"
AUTO_CONFIRM=0

usage() {
  cat <<'EOF'
Verwendung:
  setup/reset_database.sh [--yes]

Das Skript löscht die lokale SQLite-Datenbank und legt sie anschließend
mit dem Schema, drei Admin-Konten und den Seed-Stationen neu an. Danach
wird der PHP-Entwicklungsserver gestartet.

Für nicht-interaktive Aufrufe können die Passwörter übergeben werden:
  ADMIN_STINA_PASSWORD=... ADMIN_KOLJA_PASSWORD=... ADMIN_ADMIN_PASSWORD=... \
    setup/reset_database.sh --yes

Host und Port können angepasst werden:
  PHP_SERVER_HOST=0.0.0.0 PHP_SERVER_PORT=8080 setup/reset_database.sh
EOF
}

for arg in "$@"; do
  case "$arg" in
    --yes|-y)
      AUTO_CONFIRM=1
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unbekannte Option: ${arg}" >&2
      usage >&2
      exit 2
      ;;
  esac
done

if ! command -v php >/dev/null 2>&1; then
  echo "Fehler: PHP wurde nicht gefunden." >&2
  exit 1
fi

mkdir -p "${ROOT_DIR}/data"

if [[ "${AUTO_CONFIRM}" -ne 1 ]]; then
  printf 'Achtung: Diese Aktion löscht %s und legt die Datenbank neu an. Fortfahren? [j/N] ' "${DB_FILE}"
  read -r answer
  case "${answer}" in
    j|J|ja|JA|Ja)
      ;;
    *)
      echo "Abgebrochen."
      exit 0
      ;;
  esac
fi

read_password() {
  local variable_name="$1"
  local username="$2"
  local password

  if [[ -n "${!variable_name:-}" ]]; then
    printf -v "${variable_name}" '%s' "${!variable_name}"
    return
  fi

  while :; do
    read -r -s -p "Passwort für ${username} (mindestens 8 Zeichen): " password
    printf '\n' >&2
    if [[ "${#password}" -ge 8 ]]; then
      printf -v "${variable_name}" '%s' "${password}"
      return
    fi
    echo "Das Passwort ist zu kurz." >&2
  done
}

read_password ADMIN_STINA_PASSWORD stina
read_password ADMIN_KOLJA_PASSWORD kolja
read_password ADMIN_ADMIN_PASSWORD admin

rm -f -- "${DB_FILE}" "${DB_WAL}" "${DB_SHM}"

(
  cd "${ROOT_DIR}"
  php setup/create_admins.php stina "${ADMIN_STINA_PASSWORD}"
  php setup/create_admins.php kolja "${ADMIN_KOLJA_PASSWORD}"
  php setup/create_admins.php admin "${ADMIN_ADMIN_PASSWORD}"
  php setup/migrate_stations.php
)

unset ADMIN_STINA_PASSWORD ADMIN_KOLJA_PASSWORD ADMIN_ADMIN_PASSWORD
echo "Datenbank wurde neu angelegt, Admins wurden erstellt und Stationen migriert."
echo
echo "Spiel:  http://${SERVER_HOST}:${SERVER_PORT}/index.html"
echo "Admin:  http://${SERVER_HOST}:${SERVER_PORT}/admin/login.php"
echo
echo "PHP-Server läuft. Mit Ctrl+C beenden."
cd "${ROOT_DIR}"
exec php -S "${SERVER_HOST}:${SERVER_PORT}" -t "${ROOT_DIR}"
