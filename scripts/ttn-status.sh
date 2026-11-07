#!/usr/bin/env bash
# Zeigt die für die beelogger-Firmware wichtigen TTN-Werte eines Geräts (ohne Schlüssel).
#   scripts/ttn-status.sh <app-id> <device-id>
# API-Key (Rechte: devices read, traffic read, downlink write) in ~/.config/ttn/<app-id>.key,
# sonst Konto-Key ~/.config/ttn/konto.key
# Cluster: TTN_CLUSTER (Vorgabe eu1)
set -e
APP=$1; DEV=$2
[ -n "$APP" ] && [ -n "$DEV" ] || { echo "Aufruf: $0 <app-id> <device-id>"; exit 1; }
KF="$HOME/.config/ttn/$APP.key"; [ -f "$KF" ] || KF="$HOME/.config/ttn/konto.key"  # Konto-Key als Rückfall
KEY=$(tr -d ' \n' < "$KF")
curl -s -H "Authorization: Bearer $KEY" \
  "https://${TTN_CLUSTER:-eu1}.cloud.thethings.network/api/v3/ns/applications/$APP/devices/$DEV?field_mask=session.last_n_f_cnt_down,session.last_f_cnt_up,session.started_at,mac_settings,mac_state.current_parameters,mac_state.desired_parameters,mac_state.recent_uplinks,mac_state.pending_requests,lorawan_version,supports_join" |
python3 -c '
import json,sys
d=json.load(sys.stdin)
if "code" in d: sys.exit("TTN-Fehler: "+d.get("message",""))
s=d.get("session",{}); m=d.get("mac_settings",{}); st=d.get("mac_state",{})
cur=st.get("current_parameters",{}); des=st.get("desired_parameters",{})
ok=lambda b:"ok " if b else "!! "
print(d["ids"]["device_id"], d.get("lorawan_version"), "OTAA" if d.get("supports_join") else "ABP")
fd=int(s.get("last_n_f_cnt_down",0))
print(ok(fd<65536)+"FCntDown", fd, "(muss < 65536 sein, sonst Session/MAC-Reset)")
print("   Session seit", s.get("started_at","-")[:19], "letzter FCntUp", s.get("last_f_cnt_up",0))
a=m.get("rx1_delay",0); b=m.get("desired_rx1_delay",0)
print(ok(a==1 and b==1)+"Einstellung rx1_delay: %s / desired %s (soll 1)"%(a,b))
# Rx2-DR: gleich der Firmware (neue FW: 3 = SF9; Altgeräte laut Wiki 06a: 0). Hauptsache alles gleich, sonst RXParamSetupReq bei jedem Uplink
a=m.get("rx2_data_rate_index",0); b=m.get("desired_rx2_data_rate_index",0)
r2c=cur.get("rx2_data_rate_index",0); r2d=des.get("rx2_data_rate_index",0)
print(ok(a==b)+"Einstellung rx2_data_rate_index: %s / desired %s (gleich der Firmware: neue FW 3)"%(a,b))
print(ok(r2c==a and r2d==b)+"laufende Session RX2-DR: %s / %s (ungleich Einstellung: MAC-Reset)"%(r2c,r2d))
pr=[p.get("cid","") for p in st.get("pending_requests",[])]
print(ok(not pr)+"offene MAC-Befehle:", pr or "keine")
if not d.get("supports_join"):  # nur ABP; OTAA setzt Zähler per Join zurück
    print(ok(m.get("resets_f_cnt",False))+"Resets frame counters:", m.get("resets_f_cnt",False))
print(ok("disabled" in m.get("adr",{}))+"ADR aus:", "disabled" in m.get("adr",{}))
# fehlt das Feld, gilt die TTN-Vorgabe 200 / 86400 s (Statusabfrage, die die Firmware nicht beantwortet)
sc=m.get("status_count_periodicity"); stp=m.get("status_time_periodicity")
print(ok(sc==0 and stp in ("0s","0")) +"Status periodicity count/time: %s / %s (soll 0 / 0s)"%(sc,stp))
# Join-Request hat kein mac_payload
up=[(u.get("received_at","")[11:19],u.get("payload",{}).get("mac_payload",{}).get("f_hdr",{}).get("f_cnt",0) if "mac_payload" in u.get("payload",{}) else "Join") for u in st.get("recent_uplinks",[])]
print("   letzte Uplinks (UTC, FCnt):", up[-5:])
'
# Application Server zählt Downlinks selbst weiter; "Reset session and MAC state" setzt ihn nicht zurück
curl -s -H "Authorization: Bearer $KEY" \
  "https://${TTN_CLUSTER:-eu1}.cloud.thethings.network/api/v3/as/applications/$APP/devices/$DEV?field_mask=session.last_a_f_cnt_down" |
python3 -c '
import json,sys
a=int(json.load(sys.stdin).get("session",{}).get("last_a_f_cnt_down",0))
print(("ok " if a<65536 else "!! ")+"AS-FCntDown", a, "(muss < 65536 sein; sonst AS-Zähler zurücksetzen)")'
