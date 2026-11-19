#!/usr/bin/env bash
# Baut die ATmega-LoRa-Sketche (ABP 261107, OTAA 261119) mit verschiedenen Sensorkombinationen
# und gibt eine Markdown-Tabelle mit Flash- und RAM-Bedarf aus (Pro Mini 8 MHz, ATmega328P).
#   scripts/atmega-speicher-matrix.sh                 alle Kombinationen
#   scripts/atmega-speicher-matrix.sh "DS18B20=1 Gewicht=4 Si7021=1"   nur diese eine
# Kürzel = Teil hinter Anzahl_Sensoren_ / Anzahl_Sensor_ in Config_MULTI.h (DHT, Si7021, SHT31, BME280,
# DS18B20, Licht, Regen, Gewicht; Regen wirkt nicht: Aufruf im Sketch auskommentiert), dazu myDEBUG=0
# (serielle Debug-Ausgaben aus, beelogger_config.h). Nicht genannte Werte bleiben wie im Sketch (DS18B20=1, Gewicht=1, Rest 0).
# Grenzen laut Board-Definition: Flash 30 720 B (32 KB minus Bootloader), RAM 2 048 B.
# OTAA: Die LMIC-Kopie legt für ihre Fehlermeldungen den Dateipfad ab (src/lmic_otaa/lmic/oslmic.h:46).
# Flash und RAM steigen um ca. 2 B je Zeichen im Pfad des Sketch-Ordners → kurzer ARBEIT-Ordner, Länge in der Fußzeile.
# Braucht arduino-cli mit Core arduino:avr; Bibliotheken aus orginal_firmware_bibliotheken/.
set -eu
REPO=$(cd "$(dirname "$0")/.." && pwd)
FQBN=${FQBN:-arduino:avr:pro:cpu=8MHzatmega328}
LIBS=$REPO/orginal_firmware_bibliotheken/beelogger_arduino_libs_2026_04
ARBEIT=${ARBEIT:-${TMPDIR:-/tmp}/atmega-matrix}
mkdir -p "$ARBEIT"

KOMBIS=(
  "DS18B20=1 Gewicht=1"
  "DS18B20=0 Gewicht=1"
  "DS18B20=2 Gewicht=1"
  "DS18B20=1 Gewicht=2"
  "DS18B20=1 Gewicht=3"
  "DS18B20=1 Gewicht=4"
  "DS18B20=1 Gewicht=1 DHT=1"
  "DS18B20=1 Gewicht=1 DHT=2"
  "DS18B20=1 Gewicht=1 Si7021=1"
  "DS18B20=1 Gewicht=1 SHT31=1"
  "DS18B20=1 Gewicht=1 SHT31=2"
  "DS18B20=1 Gewicht=1 BME280=1"
  "DS18B20=1 Gewicht=1 BME280=2"
  "DS18B20=1 Gewicht=1 Licht=1"
  "DS18B20=1 Gewicht=4 DHT=1"
  "DS18B20=1 Gewicht=4 Si7021=1"
  "DS18B20=1 Gewicht=4 SHT31=1"
  "DS18B20=1 Gewicht=4 BME280=1"
  "DS18B20=1 Gewicht=4 Licht=1"
  "DS18B20=2 Gewicht=4"
  "DS18B20=1 Gewicht=4 Si7021=1 Licht=1"
  "DS18B20=1 Gewicht=4 SHT31=1 Licht=1"
  "DS18B20=1 Gewicht=1 BME280=1 Licht=1"
)
[ $# -gt 0 ] && KOMBIS=("$@")

# baut Sketch $1 (Ordnername unter atmega_lora/) mit Kombination $2; gibt "flash ram" aus
bau() {
  local sk=$1 kombi=$2 d=$ARBEIT/src/$1 cfg kv k v
  rm -rf "$d"; mkdir -p "$d"; cp -r "$REPO/atmega_lora/$sk/." "$d/"
  cfg=$d/Config_MULTI.h
  for kv in $kombi; do
    k=${kv%%=*}; v=${kv#*=}
    if [ "$k" = myDEBUG ]; then  # Debug-Ausgaben (beelogger_config.h), Vorgabe 1
      sed -i -E "s/^(#define myDEBUG +)[0-9]+/\1$v/" "$d/beelogger_config.h"; continue
    fi
    if grep -qE "^#define Anzahl_Sensor(en)?_$k " "$cfg"; then
      sed -i -E "s/^(#define Anzahl_Sensor(en)?_$k +)[0-9]+/\1$v/" "$cfg"
    else echo "Kürzel $k nicht in Config_MULTI.h" >&2; return 1; fi
  done
  arduino-cli compile --fqbn "$FQBN" --libraries "$LIBS" --build-path "$ARBEIT/build-$sk" \
      --format json "$d" 2>/dev/null |
    python3 -c '
import json,re,sys
d=json.load(sys.stdin)
if d.get("success"):
    s={x["name"]:x["size"] for x in d["builder_result"]["executable_sections_size"]}
    print(s["text"], s["data"]); sys.exit()
# zu groß: Größen stehen nur im Text („Der Sketch verwendet 32872 Bytes (107%)“ / „Sketch uses …“)
z=re.findall(r"(\d+) [Bb]ytes \(\d+%\)", d.get("compiler_err","")+d.get("compiler_out",""))
print(*(z[:2] if len(z)>=2 else ["Fehler","Fehler"]))'
}

zelle() {  # $1 = Bytes, $2 = Grenze
  [ "$1" = Fehler ] && { echo "Baufehler"; return; }
  python3 -c "
b,g=$1,$2
print(f'{b:,} B ({b*100//g} %) '.replace(',', ' ')+('passt' if b<=g else '**passt nicht**'))"
}

echo "| Sensoren | ABP 261107 Flash | OTAA 261119 Flash | RAM ABP / OTAA |"
echo "|---|---|---|---|"
for k in "${KOMBIS[@]}"; do
  read -r fa ra < <(bau beelogger_LORA_261107 "$k")
  read -r fo ro < <(bau beelogger_LORA_OTAA_261119 "$k")
  echo "| $k | $(zelle "$fa" 30720) | $(zelle "$fo" 30720) | $ra / $ro B |"
done
echo
echo "OTAA-Sketchpfad beim Bauen: $ARBEIT/src/beelogger_LORA_OTAA_261119 ($(printf %s "$ARBEIT/src/beelogger_LORA_OTAA_261119" | wc -c) Zeichen)"
echo "Gebaut $(date +%F) mit arduino-cli $(arduino-cli version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1), $(arduino-cli core list | awk '/arduino:avr/{print $1, $2}'), FQBN $FQBN"
