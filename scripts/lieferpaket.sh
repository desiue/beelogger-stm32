#!/usr/bin/env bash
# Baut das Lieferpaket im Download-Format von beelogger.de:
#   STM32_LORA_<ID>/STM32_LORA_<ID>.ino + übrige Sketch-Dateien + src/lmic_otaa/
#   Config_*.h aus den .example-Vorlagen (keine Schlüssel), Diff daneben.
# Aufruf: scripts/lieferpaket.sh [ID] [Zielordner]   (Standard: ID 261107, Ziel dist/)
set -euo pipefail

ID="${1:-261107}"
ZIEL="${2:-dist}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"
NAME="STM32_LORA_${ID}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

S="$TMP/$NAME"
mkdir -p "$S/src"

# Sketch-Dateien (ohne lokale Configs und Sicherungen)
for f in "$REPO"/src/*.ino "$REPO"/src/STM32_beelogger.h; do
  cp "$f" "$S/"
done
mv "$S/STM32_LORA_260705.ino" "$S/$NAME.ino"
cp "$REPO/src/Config_LORA.h.example"  "$S/Config_LORA.h"
cp "$REPO/src/Config_MULTI.h.example" "$S/Config_MULTI.h"
cp -r "$REPO/src/lmic_otaa" "$S/src/"
cp "$REPO/docs/aenderungen_gegen_260705.diff" "$TMP/"

# Sicherheitsprüfung: keine Schlüssel ungleich 0x00 im Paket
if grep -rhoE '(NWKSKEY|APPSKEY|APPKEY|DEVEUI|APPEUI)\[[0-9]+\] *= *\{[^}]*\}' "$S"/Config_LORA.h \
   | grep -qE '0x(0[1-9A-Fa-f]|[1-9A-Fa-f][0-9A-Fa-f])'; then
  echo "FEHLER: Config_LORA.h enthält Schlüssel" >&2; exit 1
fi

mkdir -p "$REPO/$ZIEL"
OUT="$REPO/$ZIEL/beelogger_${NAME}.zip"
rm -f "$OUT"
(cd "$TMP" && bsdtar --format zip -cf "$OUT" "$NAME" aenderungen_gegen_260705.diff)
echo "$OUT"
