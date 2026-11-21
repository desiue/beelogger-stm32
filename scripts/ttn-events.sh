#!/usr/bin/env bash
# Live-Events eines TTN-Geräts mitschreiben (Uplink-Empfang, Weiterleitung an Webhook), je Zeile: Zeit Ereignis FCnt.
#   scripts/ttn-events.sh <app-id> <device-id> [Sekunden, Vorgabe 3600]
# Zweck: prüfen, ob auch Uplinks mit FCnt 0 (nach Reset/Flash) im Network Server ankommen und weitergeleitet werden.
APP=$1; DEV=$2; T=${3:-3600}
[ -n "$APP" ] && [ -n "$DEV" ] || { echo "Aufruf: $0 <app-id> <device-id> [Sekunden]"; exit 1; }
KEY=$(tr -d ' \n' < "$HOME/.config/ttn/konto.key")
curl -s -N --max-time "$T" -H "Authorization: Bearer $KEY" -H 'Accept: text/event-stream' \
  "https://${TTN_CLUSTER:-eu1}.cloud.thethings.network/api/v3/events" \
  -d "{\"identifiers\":[{\"device_ids\":{\"application_ids\":{\"application_id\":\"$APP\"},\"device_id\":\"$DEV\"}}]}" |
python3 -u -c '
import sys,json,time
for l in sys.stdin:
    l=l.strip()
    if not l: continue
    r=json.loads(l)["result"]; d=r.get("data",{}) or {}
    p=d.get("message",{}).get("payload",{}) if isinstance(d.get("message"),dict) else d.get("payload",{}) or {}
    f=p.get("mac_payload",{}).get("f_hdr",{}).get("f_cnt","") if isinstance(p,dict) else ""
    print(time.strftime("%H:%M:%S"), r["name"], "FCnt="+str(f) if f!="" else "", flush=True)'
