#!/usr/bin/env bash
# Setzt die lokale DB auf den Stand von setup/snapshot.sql zurück und startet den PHP-Server.
# Snapshot neu erzeugen: sqlite3 data/game.sqlite .dump > setup/snapshot.sql
# Host/Port: PHP_SERVER_HOST, PHP_SERVER_PORT
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
HOST="${PHP_SERVER_HOST:-127.0.0.1}"
PORT="${PHP_SERVER_PORT:-8080}"

mkdir -p data
rm -f data/game.sqlite data/game.sqlite-wal data/game.sqlite-shm
sqlite3 data/game.sqlite < setup/snapshot.sql

echo -e "DB zurückgesetzt.\nSpiel: http://$HOST:$PORT/index.html\nAdmin: http://$HOST:$PORT/admin/login.php\n"
exec php -S "$HOST:$PORT" -t .
