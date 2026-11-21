#!/usr/bin/env bash
# Prüft die ganze Funkkette eines Geräts, vom Seriell-Log bis zu TTN (Fehlersuche "Paket kommt nicht an").
#   scripts/kette-pruefen.sh <DevAddr-hex> <app-id> <device-id> <Seriell-Tag, z. B. USB5>
# Stufen: 1 Firmware (Seriell-Log)  2 Gateway Mikrotik (REST, Zugang aus Vaultwarden)
#         3 TTN Gateway-Verbindung  4 TTN Network Server (letzter Zähler)  5 TTN Weiterleitung (Events, Webhook)
# Zugangsdaten werden nie ausgegeben. Nur lesend.
ADDR=${1^^}; APP=$2; DEV=$3; TAG=${4:-USB5}
[ -n "$ADDR" ] && [ -n "$APP" ] && [ -n "$DEV" ] || { sed -n 2,6p "$0"; exit 1; }
GW_IP=192.168.6.196; GW_ID=eui-50303541786f4750; B=https://eu1.cloud.thethings.network/api/v3
KEY=$(tr -d ' \n' < "$HOME/.config/ttn/konto.key")

echo "== 1 Firmware: letzte Sendungen im Seriell-Log [$TAG] (Zähler = FCnt) =="
grep -a "\[$TAG\]" "$HOME/beelogger-logs/seriell.log" | grep -aE 'frame counter|TX done' | tail -6

echo; echo "== 2 Gateway Mikrotik $GW_IP: empfangene Pakete von $ADDR (Pufferspeicher, ca. 15 min) =="
PW=$(ssh HermesVM '~/.local/bin/vault password mikrotik_192.168.6.196_lora_gw' 2>/dev/null | python3 -c "
import sys,json
t=sys.stdin.read().strip()
try:
    d=json.loads(t[t.find('{'):]); x=d.get('data'); print(x.get('data',x) if isinstance(x,dict) else x)
except Exception: print(t)")
curl -s -m 15 -u "admin:$PW" "http://$GW_IP/rest/iot/lora/traffic" | ADDR=$ADDR python3 -c "
import sys,json,os
d=json.load(sys.stdin); a=os.environ['ADDR']
print('Puffer: %d Pakete, %s bis %s'%(len(d),d[0]['time'],d[-1]['time']))
for p in d:
    if p.get('devaddr','').replace(' ','').upper()==a: print(' ',p['time'],'FCnt',p.get('fcnt'),'CRC',p.get('crc'),'RSSI',p.get('rssi'),'SNR',p.get('snr'),p.get('freqhz'),p.get('datarate'))" || echo "!! Gateway nicht lesbar"

echo; echo "== 3 TTN: Gateway-Verbindung =="
curl -s -H "Authorization: Bearer $KEY" "$B/gs/gateways/$GW_ID/connection/stats" | python3 -c "
import json,sys;d=json.load(sys.stdin)
print(' verbunden seit',d.get('connected_at','-')[:19],'| letzter Uplink (alle Geräte)',d.get('last_uplink_received_at','-')[:19],'UTC | Uplinks gesamt',d.get('uplink_count'))"

echo; echo "== 4 TTN Network Server: Zähler und Uplinks von $APP/$DEV =="
curl -s -H "Authorization: Bearer $KEY" "$B/ns/applications/$APP/devices/$DEV?field_mask=session.last_f_cnt_up,mac_settings.resets_f_cnt,mac_state.recent_uplinks" | python3 -c "
import json,sys;d=json.load(sys.stdin)
print(' letzter akzeptierter FCnt (NS):',d.get('session',{}).get('last_f_cnt_up',0),'| Resets frame counters:',d.get('mac_settings',{}).get('resets_f_cnt'))
for u in d.get('mac_state',{}).get('recent_uplinks',[])[-5:]: print('  ',u['received_at'][:19],'UTC FCnt',u.get('payload',{}).get('mac_payload',{}).get('f_hdr',{}).get('f_cnt',0))"

echo; echo "== 5 TTN Weiterleitung (Events dieses Geräts, soweit TTN sie noch hält) =="
curl -s -m 8 -H "Authorization: Bearer $KEY" -H 'Accept: text/event-stream' "$B/events" \
  -d "{\"identifiers\":[{\"device_ids\":{\"application_ids\":{\"application_id\":\"$APP\"},\"device_id\":\"$DEV\"}}],\"tail\":200}" | python3 -c "
import sys,json
for l in sys.stdin:
    l=l.strip()
    if not l: continue
    r=json.loads(l)['result']
    if r['name'].startswith(('ns.up.data.receive','as.up.data.forward','as.webhook','ns.up.data.drop','ns.up.data.process')) or 'fail' in r['name'] or 'drop' in r['name']:
        f=r.get('data',{}).get('message',{}).get('payload',{}).get('mac_payload',{}).get('f_hdr',{}).get('f_cnt','') if isinstance(r.get('data'),dict) and isinstance(r['data'].get('message'),dict) else ''
        print(' ',r['time'][:19],'UTC',r['name'],('FCnt='+str(f)) if f!='' else '')"
echo; echo "Lesehilfe: Stufe 2 hat das Paket, Stufe 4/5 nicht -> TTN verwirft (FCnt <= letzter akzeptierter FCnt, siehe docs/privat/kommunikationskette.md)."
