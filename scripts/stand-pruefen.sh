#!/usr/bin/env bash
# Misst den Live-Stand zum Sitzungsstart (nur lesend). Aussagen wie „läuft“, „online“, „aktuell“
# in Doku oder Antworten nur mit einer Messung aus derselben Sitzung (Regel Projekt-CLAUDE.md).
#   scripts/stand-pruefen.sh            alles
#   scripts/stand-pruefen.sh server     nur Server-Pakete/Versionen
#   scripts/stand-pruefen.sh logger     nur letzte Messwerte (eigener Server, Community-Server, TTN)
#   scripts/stand-pruefen.sh lokal      nur USB-Ports, Seriell-Logging, Repo
# Ausgabe: "ok"/"!!" am Zeilenanfang; "!!" = älter als ALT_MIN Minuten (Vorgabe 30) oder Fehler.
# Eigene Ziele in docs/privat/stand-pruefen.conf (nicht im Repo), z. B.:
#   EIGEN_SSH=meinserver            ssh-Host mit den Server-Skripten (Schlüssel-Login)
#   EIGEN_PFAD='~/web/beelogger'    Basisordner dort (Unterordner Quad1, Quad2 …)
#   COMMUNITY_URL=https://community.beelogger.de/<name>
#   TTN_KEYFILE=~/.config/ttn/konto.key   (Rechte: Applications/Devices lesen)
# Fehlt ein Wert, wird der Teil übersprungen. Braucht curl, python3.
# Schreibt nichts, gibt keine Schlüssel/Passwörter aus.
set -u
TEIL=${1:-alle}
ALT_MIN=${ALT_MIN:-30}
REPO=$(cd "$(dirname "$0")/.." && pwd)
EIGEN_SSH=""; EIGEN_PFAD=""; COMMUNITY_URL=""; TTN_KEYFILE=""
# shellcheck source=/dev/null
[ -f "$REPO/docs/privat/stand-pruefen.conf" ] && . "$REPO/docs/privat/stand-pruefen.conf"
echo "Messung $(date '+%F %T %Z') auf $(hostname)"

server() {
  echo; echo "== Server-Pakete auf beelogger.de (öffentlich zum Download) =="
  local seite
  for seite in https://beelogger.de/webserver/webserver-skripte/webserver-skripte-release/ \
               https://beelogger.de/systeme/downloads/; do
    curl -sL -m 20 "$seite" | grep -oE 'ddownload=[0-9]+' | sort -u | while read -r d; do
      n=$(curl -sI -m 20 "https://beelogger.de/?$d" | grep -i '^content-disposition' | sed 's/.*filename="//;s/".*//')
      case "$n" in *ServerSkripte*) echo "   $n  ($seite)";; esac
    done
  done
  echo "   Ankündigungen auf der Release-Seite:"
  curl -sL -m 20 https://beelogger.de/webserver/webserver-skripte/webserver-skripte-release/ |
    python3 -c '
import re,html,sys
t=re.sub(r"<script.*?</script>|<style.*?</style>","",sys.stdin.read(),flags=re.S)
t=re.sub(r"\s+"," ",html.unescape(re.sub(r"<[^>]+>"," ",t)))
for m in re.finditer(r"M\.\d\d Ankündigung[^.]{0,160}",t): print("     "+m.group(0))'
  echo "   Lokale Pakete im Repo: $(cd "$REPO" && ls -d M[0-9]*_ServerSkripte server/M[0-9]* 2>/dev/null | tr '\n' ' ')"
  echo "   community.beelogger.de: Version per HTTP nicht lesbar (\$show_version=0 in beelogger_show.php)"
}

logger() {
  if [ -n "$EIGEN_SSH" ]; then
  echo; echo "== eigener Server ($EIGEN_SSH): letzte Zeile beelogger.csv, Server-Version je Quad =="
  ssh -o ConnectTimeout=10 "$EIGEN_SSH" "cd $EIGEN_PFAD"' && for q in Quad[0-9]*; do
      f=$q/beelogger.csv; [ -f "$f" ] || continue
      v=$(grep -o "Softwareversion = \"[^\"]*\"" $q/beelogger_log.php 2>/dev/null | head -1 | cut -d\" -f2)
      echo "$q|$(tail -1 "$f" | cut -d, -f1)|$v"; done' |
  ALT_MIN=$ALT_MIN python3 -c '
import sys,os,datetime as D
jetzt=D.datetime.now(); alt=int(os.environ["ALT_MIN"])
for z in sys.stdin:
    q,t,v=z.strip().split("|")
    try: m=int((jetzt-D.datetime.strptime(t,"%Y/%m/%d %H:%M:%S")).total_seconds()//60)
    except ValueError: m=None
    s="ok" if m is not None and m<=alt else "!!"
    print(f"{s} {q:6} {t}  vor {m} min  Server {v}")'
  else echo; echo "(eigener Server: EIGEN_SSH nicht gesetzt, übersprungen)"; fi

  if [ -n "$COMMUNITY_URL" ]; then
  echo; echo "== Community-Server: letzter Zeitstempel aus beelogger_show.php je Quad (CSV dort gesperrt, 403) =="
  for q in $(seq 1 12); do
    curl -s -m 20 "$COMMUNITY_URL/Quad$q/beelogger_show.php" | grep -oE '20[0-9]{2}/[0-9]{2}/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}' | tail -1 | sed "s/^/Quad$q|/"
  done | ALT_MIN=$ALT_MIN python3 -c '
import sys,os,datetime as D
jetzt=D.datetime.now(); alt=int(os.environ["ALT_MIN"])
for z in sys.stdin:
    q,t=z.strip().split("|")
    m=int((jetzt-D.datetime.strptime(t,"%Y/%m/%d %H:%M:%S")).total_seconds()//60)
    print(("ok" if m<=alt else "!!")+f" {q:6} {t}  vor {m} min")'
  else echo; echo "(Community-Server: COMMUNITY_URL nicht gesetzt, übersprungen)"; fi

  [ -n "$TTN_KEYFILE" ] || { echo; echo "(TTN: TTN_KEYFILE nicht gesetzt, übersprungen)"; return; }
  echo; echo "== TTN: letzter Uplink je Gerät (Network Server, UTC) =="
  local KEY; KEY=$(tr -d ' \n' < "${TTN_KEYFILE/#\~/$HOME}")
  TTN_KEY=$KEY ALT_MIN=$ALT_MIN python3 -c '
import json,os,urllib.request,datetime as D
K=os.environ["TTN_KEY"]; alt=int(os.environ["ALT_MIN"]); B="https://eu1.cloud.thethings.network/api/v3"
def get(p):
    r=urllib.request.Request(B+p,headers={"Authorization":"Bearer "+K})
    return json.load(urllib.request.urlopen(r,timeout=20))
jetzt=D.datetime.now(D.timezone.utc)
for a in sorted(x["ids"]["application_id"] for x in get("/applications?limit=100").get("applications",[])):
    for d in get(f"/applications/{a}/devices?limit=100").get("end_devices",[]):
        dev=d["ids"]["device_id"]
        try: n=get(f"/ns/applications/{a}/devices/{dev}?field_mask=mac_state.recent_uplinks,session.last_f_cnt_up")
        except Exception as e: print(f"!! {a}/{dev}: Fehler {e}"); continue
        up=n.get("mac_state",{}).get("recent_uplinks",[])
        if not up: print(f"!! {a}/{dev}: kein Uplink im MAC-State"); continue
        t=up[-1].get("received_at","")[:19]
        m=int((jetzt-D.datetime.fromisoformat(t+"+00:00")).total_seconds()//60)
        fc=n.get("session",{}).get("last_f_cnt_up",0)
        print(("ok" if m<=alt else "!!")+f" {a}/{dev}: {t}  vor {m} min  FCntUp {fc}")'
}

lokal() {
  echo; echo "== Laptop: USB-Seriell, Seriell-Logging, Repo =="
  echo "   Ports: $(ls /dev/ttyUSB* /dev/ttyACM* 2>/dev/null | tr '\n' ' ')"
  ls -l /dev/serial/by-id/ 2>/dev/null | awk 'NR>1{print "   "$9" -> "$11}'
  local p; p=$(pgrep -af 'seriell-(log|alle)\.sh' | grep -v pgrep)
  if [ -n "$p" ]; then echo "   Seriell-Logging läuft:"; echo "$p" | sed 's/^/     /'
  else echo "   Seriell-Logging: kein Prozess"; fi
  [ -f "$HOME/beelogger-logs/seriell.log" ] && echo "   seriell.log zuletzt geschrieben: $(date -r "$HOME/beelogger-logs/seriell.log" '+%F %T')"
  echo "   Repo: Branch $(git -C "$REPO" branch --show-current), $(git -C "$REPO" log -1 --format='%h %cs %s')"
  local u; u=$(git -C "$REPO" status --porcelain | wc -l); echo "   ungespeicherte Änderungen: $u Datei(en)"
}

case "$TEIL" in
  server) server ;; logger) logger ;; lokal) lokal ;;
  alle) server; logger; lokal ;;
  -h|--help) sed -n '2,16p' "$0" ;;
  *) echo "unbekannt: $TEIL (server|logger|lokal|alle)"; exit 1 ;;
esac
