#!/usr/bin/env bash
# Serielle Ausgabe des beeloggers (9600 8N1) mit Uhrzeit in eine Datei schreiben.
#   Start:    scripts/seriell-log.sh
#   Stopp:    scripts/seriell-log.sh stop      (vor dem Flashen! stm32loader braucht den Port allein)
#   Ansehen:  tail -f ~/beelogger-logs/seriell.log
# Anderer Port: PORT=/dev/ttyUSB1 scripts/seriell-log.sh
PORT=${PORT:-/dev/ttyUSB0}
DIR=$HOME/beelogger-logs
PIDF=$DIR/seriell.pid
LOG=$DIR/seriell.log
mkdir -p "$DIR"

laeuft() { [ -f "$PIDF" ] && kill -0 "$(cat "$PIDF")" 2>/dev/null; }

case "$1" in
  stop)
    laeuft && kill -- -"$(cat "$PIDF")" 2>/dev/null
    rm -f "$PIDF"; echo "gestoppt"; exit 0 ;;
  run) ;;
  *)
    if laeuft; then echo "läuft schon (PID $(cat "$PIDF")), Log: $LOG"; exit 0; fi
    setsid "$0" run >/dev/null 2>&1 &
    echo "gestartet, Log: $LOG"; exit 0 ;;
esac

# Eigene Prozessgruppe (setsid), damit "stop" auch cat beendet
echo $$ > "$PIDF"
stty -F "$PORT" 9600 cs8 -parenb -cstopb raw -echo || exit 1
echo "$(date '+%F %T') --- Log gestartet ($PORT) ---" >> "$LOG"
cat "$PORT" | while IFS= read -r l; do
  printf '%s %s\n' "$(date '+%F %T')" "${l%$'\r'}"
done >> "$LOG"
