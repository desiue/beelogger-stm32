#!/usr/bin/env python3
"""Tauschtag Q5 (Oppum): Webhooks von beelogger009 (altes Board) nach beelogger-q5neu (Ersatz) umhaengen.

  scripts/q5-wechsel.py            Trockenlauf: zeigt nur, was passieren wuerde (URLs ohne Passwort)
  scripts/q5-wechsel.py --ausfuehren

Schritte (--ausfuehren):
  1. Webhooks 'beelogger009' (community Quad5) und 'beelogger009-2' (majkic Quad5) als
     'quad5-community' / 'quad5-majkic' in beelogger-q5neu anlegen (URL 1:1 kopiert, Passwort nie ausgegeben)
  2. Test-Webhook 'quad9-test' (majkic Quad9) in beelogger-q5neu loeschen
  3. Webhooks in beelogger009 loeschen (altes Board ist stillgelegt)
Abnahme danach: Uplink des Ersatz-Boards erscheint in Quad5 (beelogger.csv, letzte Zeile) und community.
Konto-Key: ~/.config/ttn/konto.key, Cluster eu1.
"""
import json, os, re, sys, urllib.request
ALT, NEU = "beelogger009", "beelogger-q5neu"
KOPIE = {"beelogger009": "quad5-community", "beelogger009-2": "quad5-majkic"}
TEST = "quad9-test"
B = "https://eu1.cloud.thethings.network/api/v3/as/webhooks"
KEY = open(os.path.expanduser("~/.config/ttn/konto.key")).read().strip()
def api(method, url, body=None):
    r = urllib.request.Request(url, method=method, data=json.dumps(body).encode() if body else None,
        headers={"Authorization": "Bearer " + KEY, "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(r) as f: t = f.read()
    except urllib.error.HTTPError as e: sys.exit("TTN-Fehler %s: %s" % (e.code, e.read()[:200]))
    return json.loads(t) if t.strip() else {}
mask = lambda u: re.sub(r"(?i)(pass\w*)=[^&]*", r"\1=***", u)
ausf = "--ausfuehren" in sys.argv
wh = {w["ids"]["webhook_id"]: w for w in api("GET", B + "/" + ALT + "?field_mask=base_url,format,uplink_message").get("webhooks", [])}
fehlt = [k for k in KOPIE if k not in wh]
if fehlt: sys.exit("Abbruch: Webhook(s) fehlen in %s: %s (schon umgehaengt?)" % (ALT, fehlt))
for alt, neu in KOPIE.items():
    print("1. kopiere %s/%s -> %s/%s  %s" % (ALT, alt, NEU, neu, mask(wh[alt]["base_url"])))
    if ausf:
        w = {"ids": {"application_ids": {"application_id": NEU}, "webhook_id": neu},
             "base_url": wh[alt]["base_url"], "format": wh[alt].get("format", "json"), "uplink_message": {}}
        api("POST", B + "/" + NEU, {"webhook": w, "field_mask": {"paths": ["base_url", "format", "uplink_message"]}})
print("2. loesche %s/%s" % (NEU, TEST))
if ausf:
    try: api("DELETE", B + "/%s/%s" % (NEU, TEST))
    except SystemExit as e: print("   (nicht gefunden/uebersprungen:", e, ")")
for alt in KOPIE:
    print("3. loesche %s/%s" % (ALT, alt))
    if ausf: api("DELETE", B + "/%s/%s" % (ALT, alt))
print("Stand jetzt:")
for a in (NEU, ALT):
    print(" ", a, [(w["ids"]["webhook_id"], mask(w["base_url"])[:70]) for w in api("GET", B + "/" + a + "?field_mask=base_url").get("webhooks", [])])
if not ausf: print("\nTrockenlauf, nichts geaendert. Zum Ausfuehren: --ausfuehren")
