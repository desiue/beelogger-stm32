#!/usr/bin/env bash
# Black-Pill-Board erfassen: Bootloader lesen (ID, Flash, UID, Option Bytes), Firmware mit Sensoren flashen,
# Startlog auswerten (DS18B20, BME280, BH1750, Waagen, Spannungen) und alles in docs/privat/board-katalog.csv sammeln.
#   Aufruf:  scripts/board-erfassen.sh <Name> [-r "Beschriftung/Revision"] [-i Ruhestrom_uA] [-n "Notiz"] [--ohne-flash]
#   Vorher:  BOOT0 = 1, NRST drücken, BOOT0 gedrückt lassen (das Skript fragt 90 s lang ab).
#   Anderer Port: PORT=/dev/ttyUSB1 LOGTAG=USB5 (Vorgabe)
#   Blue Pill (F103): PIOENV=bluepill_f103c8 – liest dann Option Bytes 0x1FFFF800 und Systemspeicher ab 0x1FFFF7E0
#   (Flash-Größe + UID) statt 0x1FFFC000 / 0x1FFF7A00; Spalte SysSpeicher_7A00 enthält bei F103 diese 32 Byte.
set -u
cd "$(dirname "$0")/.." || exit 1
PORT=${PORT:-/dev/ttyUSB1}; TAG=${LOGTAG:-USB5}
ENV=${PIOENV:-blackpill_f411ce}
BIN=${BIN:-.pio/build/$ENV/firmware.bin}   # andere Datei: BIN=lokal/fw-bluepill/ohne_i2c.bin
PY=$HOME/.platformio/penv/bin/python
LOG=$HOME/beelogger-logs/seriell.log
KAT=docs/privat/board-katalog.csv
NAME=${1:-?}; shift || true
REV=""; RUHE=""; NOTIZ=""; FLASH=1
while [ $# -gt 0 ]; do case "$1" in
  -r) REV=$2; shift 2;; -i) RUHE=$2; shift 2;; -n) NOTIZ=$2; shift 2;; --ohne-flash) FLASH=0; shift;;
  *) echo "Unbekannte Option $1"; exit 2;; esac; done
case "$ENV" in
  bluepill*) FAM=F1; OPTADR=0x1FFFF800; SYSADR=0x1FFFF7E0;;
  *)         FAM=F4; OPTADR=0x1FFFC000; SYSADR=0x1FFF7A00;;
esac
LOADER=("$PY" -m stm32loader -p "$PORT" -f "$FAM")
TMP=$(mktemp -d)

PORT=$PORT LOGTAG=$TAG scripts/seriell-log.sh stop >/dev/null
echo "Warte bis zu 90 s auf den Bootloader ($PORT) – BOOT0 halten, NRST drücken ..."
end=$((SECONDS+90)); out=""
while [ $SECONDS -lt $end ]; do
  out=$(timeout 6 "${LOADER[@]}" 2>&1)
  echo "$out" | grep -q "Chip id" && break
done
echo "$out" | grep -q "Chip id" || { echo "FEHLER: Bootloader meldet sich nicht"; PORT=$PORT LOGTAG=$TAG scripts/seriell-log.sh >/dev/null; exit 1; }
ID=$(echo "$out" | sed -n 's/^Chip id: \(0x[0-9A-Fa-f]*\).*/\1/p' | head -1)
BLV=$(echo "$out" | sed -n 's/^Bootloader version: \(0x[0-9A-Fa-f]*\).*/\1/p' | head -1)
FSZ=$(echo "$out" | sed -n 's/^Flash size: \(.*\)/\1/p' | head -1)
UID_=$(echo "$out" | sed -n 's/^Device UID: \(.*\)/\1/p' | head -1)
"${LOADER[@]}" -r -a $OPTADR -l 16 $TMP/opt.bin >/dev/null 2>&1
"${LOADER[@]}" -r -a $SYSADR -l 32 $TMP/sys.bin >/dev/null 2>&1
OPT=$(xxd -p $TMP/opt.bin | tr -d '\n'); SYS=$(xxd -p $TMP/sys.bin | tr -d '\n')
echo "ID $ID, Flash $FSZ, UID $UID_, Bootloader $BLV"; echo "Option Bytes $OPT"

FW=""; FPROBE=""; RPROBE=""; N0=$(wc -l < "$LOG")
if [ "$FLASH" = 1 ]; then
  [ -f "$BIN" ] || { echo "FEHLER: $BIN fehlt"; exit 1; }
  FW="$(basename "$BIN") $(stat -c %s "$BIN") B"
  echo "Flashe $FW ..."
  FOUT=$("${LOADER[@]}" -e -w -v -a 0x08000000 "$BIN" 2>&1); echo "$FOUT" | tail -3
  if echo "$FOUT" | grep -qiE "failed|error|mismatch" ; then
    echo "FEHLER beim Flashen – abgebrochen, keine Zeile im Katalog. BOOT0 bis zum Ende gedrückt lassen und neu starten."
    PORT=$PORT LOGTAG=$TAG scripts/seriell-log.sh >/dev/null; exit 1
  fi
  # Speicherprobe (nur F103, Chip frisch gelöscht): Testmuster auf vorletzte 1-KB-Seite 0x0801F800 schreiben und
  # prüfen (= echte 128 KiB, unabhängig vom gemeldeten Wert), RAM-Grenze 20 KiB lesen. Die Seite nutzt die Firmware nicht.
  if [ "$FAM" = F1 ]; then
    printf 'beelogger-probe!' > $TMP/probe.bin
    "${LOADER[@]}" -w -v -a 0x0801F800 $TMP/probe.bin 2>&1 | grep -q "Verification OK" && FPROBE="128k ok" || FPROBE="128k FEHLER"
    "${LOADER[@]}" -r -a 0x20004FF0 -l 16 $TMP/r1.bin >/dev/null 2>&1 && [ -s $TMP/r1.bin ] && R1=ja || R1=nein
    "${LOADER[@]}" -r -a 0x20005000 -l 16 $TMP/r2.bin >/dev/null 2>&1 && [ -s $TMP/r2.bin ] && R2=ja || R2=nein
    RPROBE="20k lesbar $R1, über 20k lesbar $R2"
    echo "Speicherprobe: Flash $FPROBE (gemeldet $FSZ) | RAM $RPROBE"
  fi
  PORT=$PORT LOGTAG=$TAG scripts/seriell-log.sh >/dev/null
  echo ">>> Jetzt BOOT0 LOSLASSEN und NRST drücken (Log läuft, warte bis 120 s auf den Start) <<<"
  for i in $(seq 1 60); do tail -n +$((N0+1)) "$LOG" | grep -q "STM32_LORA_" && break; sleep 2; done
  sleep 30
else
  PORT=$PORT LOGTAG=$TAG scripts/seriell-log.sh >/dev/null
fi

# Startlog auswerten (nur Zeilen dieses Ports seit dem Flashen)
tail -n +$((N0+1)) "$LOG" | grep "\[$TAG\]" | sed "s/^[0-9-]* [0-9:]* \[$TAG\] //" > $TMP/start.txt
wert() { awk -v k="$1" -v n="${2:-1}" '$0 ~ k {f=1; c=0; next} f && /\]:/ {c++; if (c==n) {sub(/.*: */,""); print; exit}}' $TMP/start.txt; }
DS=$(wert "^DS18B20"); BMEP=$(wert "^BME280" 1); BMET=$(wert "^BME280" 2); BMEH=$(wert "^BME280" 3); LUX=$(wert "^BH1750")
VDD=$(sed -n 's/^Vdd. \[V\]: //p' $TMP/start.txt | head -1)
TST=$(sed -n 's/^T - STM32 \[C\]: //p' $TMP/start.txt | head -1)
BAT=$(sed -n 's/^ADC-bits: [0-9]*  Bat. \[V\]: //p' $TMP/start.txt | head -1)
W=$(sed -n 's/^W \([1-4]\) \[kg\] /W\1=/p' $TMP/start.txt | head -4 | tr '\n' ' ')
I2C="nein"; [ -n "$BMET" ] && [ -n "$LUX" ] && I2C="ja"
OW="nein"; [ -n "$DS" ] && [ "$DS" != "-127.00" ] && OW="ja"
echo "DS18B20 $DS ($OW) | BME280 $BMET °C $BMEH % $BMEP hPa | BH1750 $LUX lux ($I2C) | Vdd $VDD | T-STM32 $TST | Akku $BAT | $W"

[ -f "$KAT" ] || echo 'Datum;Name;UID;ChipID;Flash;Bootloader;OptionBytes;SysSpeicher_7A00;Beschriftung_Revision;Firmware;DS18B20_C;OneWire_ok;BME280_C;BME280_rF;BME280_hPa;BH1750_lux;I2C_ok;Vdd;T_STM32;Akku;Waagen;Ruhestrom_uA;Notiz;Flash_Probe;RAM_Probe' > "$KAT"
printf '%s\n' "$(date '+%F %T');$NAME;$UID_;$ID;$FSZ;$BLV;$OPT;$SYS;$REV;$FW;$DS;$OW;$BMET;$BMEH;$BMEP;$LUX;$I2C;$VDD;$TST;$BAT;$W;$RUHE;$NOTIZ;$FPROBE;$RPROBE" >> "$KAT"
echo "Eingetragen in $KAT"
rm -rf "$TMP"
