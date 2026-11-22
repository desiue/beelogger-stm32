#!/usr/bin/env python3
"""TTN-Matrix Gerät -> Application -> Webhook -> Server (nur lesend, Konto-Key, Cluster eu1).

  scripts/ttn-matrix.py            Markdown-Tabelle auf stdout (Zeiten MEZ = UTC+1, Winterzeit)
Passwörter in Webhook-URLs werden nie ausgegeben. Rollen der Feld-Logger stehen in ROLLE.
"""
import json, os, re, sys, urllib.request, urllib.parse, datetime as D
B = "https://eu1.cloud.thethings.network/api/v3"
KEY = open(os.path.expanduser("~/.config/ttn/konto.key")).read().strip()
ROLLE = {"beelogger10": "**Q5 alt (Feld, stillgelegt 22.11.)**", "q5-oppum-neu": "**Q5 Oppum (Ersatz, Feld)**",
         "eui-70b3d57ed004e3fd": "**Q2 SC (Feld)**", "eui-70b3d57ed004e7bf": "**Q6 Outokumpu (Feld)**",
         "eui-70b3d57ed0066fa2": "**Q1 SWK (Feld)**", "eui-70b3d57ed004e3e1": "**Q7 Immenhof (Feld)**"}
SERVER = {"beelogger.majkic.de": "majkic", "community.beelogger.de": "community", "immenhof.majkic.de": "immenhof"}
def get(p):
    r = urllib.request.Request(B + p, headers={"Authorization": "Bearer " + KEY})
    return json.load(urllib.request.urlopen(r, timeout=30))
def ziel(url, wid, dl):
    u = urllib.parse.urlparse(url); h = u.hostname or ""
    s = SERVER.get(h, h + u.path)
    q = re.search(r"(Quad\d+)", url)
    return "%s→%s%s%s" % (wid, s, "/" + q.group(1) if q else "", "[DL]" if dl else "")
jetzt = D.datetime.now(D.timezone.utc)
zeilen = []
for a in sorted(x["ids"]["application_id"] for x in get("/applications?limit=100").get("applications", [])):
    wh = get(f"/as/webhooks/{a}?field_mask=base_url,downlink_api_key").get("webhooks", [])
    w = "<br>".join(sorted(ziel(x.get("base_url", ""), x["ids"]["webhook_id"], bool(x.get("downlink_api_key"))) for x in wh)) or "–"
    for d in sorted(get(f"/applications/{a}/devices?limit=100").get("end_devices", []), key=lambda e: e["ids"]["device_id"]):
        dev = d["ids"]["device_id"]
        n = get(f"/ns/applications/{a}/devices/{dev}?field_mask=mac_state.recent_uplinks,session.dev_addr,supports_join")
        up = n.get("mac_state", {}).get("recent_uplinks", [])
        t = "–"
        if up:
            z = D.datetime.fromisoformat(up[-1]["received_at"][:19] + "+00:00") + D.timedelta(hours=1)
            t = z.strftime("%d.%m. %H:%M")
        zeilen.append((a, dev, ROLLE.get(dev, ""), "OTAA" if n.get("supports_join") else "ABP",
                       n.get("session", {}).get("dev_addr", "-").upper() or "-", t, w))
print("| App | Gerät | Rolle | Art | DevAddr | letzter Uplink (MEZ) | Webhooks → Server/Quad |\n|---|---|---|---|---|---|---|")
for z in zeilen: print("| " + " | ".join(z) + " |")
print("\nGemessen %s MEZ" % (jetzt + D.timedelta(hours=1)).strftime("%d.%m.%Y %H:%M"), file=sys.stderr)
