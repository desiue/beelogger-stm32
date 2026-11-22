#!/usr/bin/env bash
# Doku-Prüfung in einem Aufruf: doku-check (streng) + mkdocs build --strict.
# mkdocs kommt aus pipx (fest installiert 22.11.2026: pipx install mkdocs;
# pipx inject mkdocs mkdocs-material pymdown-extensions). Fehlt es, bricht das Skript ab.
set -euo pipefail
cd "$(dirname "$0")/.."

MKDOCS="$(command -v mkdocs || true)"
[ -z "$MKDOCS" ] && [ -x "$HOME/.local/bin/mkdocs" ] && MKDOCS="$HOME/.local/bin/mkdocs"
if [ -z "$MKDOCS" ]; then
  echo "FEHLER: mkdocs fehlt. Installieren: pipx install mkdocs && pipx inject mkdocs mkdocs-material pymdown-extensions" >&2
  exit 2
fi

echo "== doku-check =="
python3 scripts/doku-check.py --streng

echo "== mkdocs build --strict =="
site="$(mktemp -d)"
trap 'rm -rf "$site"' EXIT
NO_MKDOCS_2_WARNING=1 "$MKDOCS" build --strict -q -d "$site"
echo "OK: doku-check und mkdocs strict ohne Fehler"
