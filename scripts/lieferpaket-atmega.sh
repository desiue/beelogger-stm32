#!/usr/bin/env bash
# Baut das ATmega-Paket: Sketch-Ordner ABP 261107 und OTAA 261108 (Config-Dateien nur mit Platzhaltern),
# Diffs gegen 250326 und die Anleitung atmega_lora/README.md.
# Aufruf: scripts/lieferpaket-atmega.sh [Zielordner]   (Standard: dist/)
set -euo pipefail

ZIEL="${1:-dist}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"
A="$REPO/atmega_lora"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

cp -r "$A/beelogger_LORA_261107" "$A/beelogger_LORA_OTAA_261108" "$TMP/"
cp "$A/aenderungen_gegen_250326.diff" "$A/otaa_aenderungen_gegen_250326.diff" "$TMP/"
BR="$(git -C "$REPO" rev-parse --abbrev-ref HEAD)"
{ echo "Online mit funktionierenden Links: https://github.com/desiue/beelogger-stm32/tree/$BR/atmega_lora"; echo; cat "$A/README.md"; } > "$TMP/Anleitung.md"

# Sicherheitsprüfung: keine Schlüssel ungleich 0x00, DevAddr nur Platzhalter
if grep -rhoE '(NWKSKEY|APPSKEY|APPKEY|DEVEUI|APPEUI)\[[0-9]+\] *= *\{[^}]*\}' "$TMP"/*/Config_LORA.h \
   | grep -qE '0x(0[1-9A-Fa-f]|[1-9A-Fa-f][0-9A-Fa-f])'; then
  echo "FEHLER: Config_LORA.h enthält Schlüssel" >&2; exit 1
fi
if grep -hE '^static const u4_t DEVADDR' "$TMP"/*/Config_LORA.h | grep -vq '0x12345678'; then
  echo "FEHLER: DEVADDR ist kein Platzhalter" >&2; exit 1
fi

case "$ZIEL" in /*) ;; *) ZIEL="$REPO/$ZIEL" ;; esac
mkdir -p "$ZIEL"
OUT="$ZIEL/beelogger_LORA_ATmega_261108.zip"
rm -f "$OUT"
(cd "$TMP" && bsdtar --format zip -cf "$OUT" beelogger_LORA_261107 beelogger_LORA_OTAA_261108 \
  aenderungen_gegen_250326.diff otaa_aenderungen_gegen_250326.diff Anleitung.md)
echo "$OUT"
