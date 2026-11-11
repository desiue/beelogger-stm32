#!/usr/bin/env bash
# Seriell-Logging aller Testaufbauten starten (z. B. nach einem Neustart des Laptops).
#   Start:  scripts/seriell-alle.sh        Stopp: scripts/seriell-alle.sh stop
# Zuordnung über den USB-Steckplatz (by-path), weil ttyUSBn nach dem Neustart anders verteilt
# sein kann. Die Kennung im Log ([USB0] ...) bleibt so beim gleichen Gerät.
# Achtung: Port öffnen setzt Uno/Nano zurück.
cd "$(dirname "$0")" || exit 1
BP=/dev/serial/by-path/pci-0000:00:14.0-usb-0
declare -A PLATZ=(
  [USB0]=$BP:4.1.3.4.1:1.0-port0   # CH340
  [USB1]=$BP:4.1.3.4.2:1.0-port0   # CH340, Uhr
  [USB2]=$BP:4.1.3.4.4:1.0-port0   # FT232, Quad8 BlackPill
  [USB3]=$BP:2:1.0-port0           # FT232, Quad4 Blue
)
meldung=""
for tag in USB0 USB1 USB2 USB3; do
  p=${PLATZ[$tag]}
  if [ ! -e "$p" ]; then meldung+="$tag: nicht eingesteckt"$'\n'; continue; fi
  dev=$(readlink -f "$p")
  r=$(PORT=$dev LOGTAG=$tag ./seriell-log.sh "$1")
  meldung+="$tag ($dev): $r"$'\n'
done
echo "$meldung"
command -v notify-send >/dev/null && notify-send "beelogger Seriell-Log" "$meldung"
exit 0
