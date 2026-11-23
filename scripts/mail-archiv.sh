#!/usr/bin/env bash
# Mailarchiv Bichon (nur lesend). Token bleibt auf der Hermes-VM, wird nie ausgegeben.
#   scripts/mail-archiv.sh suche "<Text>" [seit JJJJ-MM-TT] [Anzahl, Vorgabe 30]   Liste: Datum | Absender | Betreff | ID
#   scripts/mail-archiv.sh lesen <ID> [max. Zeichen, Vorgabe 6000]                 Text einer Mail
# Text darf Bichon-Operatoren enthalten (AND, OR, NOT). Konto zeljko@majkic.de (7848472283716800).
# Beispiel: scripts/mail-archiv.sh suche "support@beelogger.de" 2026-11-01 40
case "${1:-}" in
  suche) T=${2:?Suchtext fehlt}; SEIT=${3:-}; N=${4:-30} ;;
  lesen) ID=${2:?ID fehlt}; MAXZ=${3:-6000} ;;
  *) sed -n 2,7p "$0"; exit 1 ;;
esac
ssh HermesVM python3 - "$1" "${T:-${ID:-}}" "${SEIT:--}" "${N:-${MAXZ:--}}" <<'PY'
import json, sys, subprocess, urllib.request, datetime as D
modus, arg, seit, n = sys.argv[1:5]
ACC = 7848472283716800; B = "http://192.168.6.180:15630/api/v1"
tok = subprocess.check_output([__import__("os").path.expanduser("~/.local/bin/vault"), "password", "bichon-archiv-192.168.6.180"]).decode().strip()
def call(pfad, body=None):
    r = urllib.request.Request(B + pfad, data=json.dumps(body).encode() if body else None,
        headers={"Authorization": "Bearer " + tok, "Content-Type": "application/json"})
    return urllib.request.urlopen(r, timeout=60).read().decode()
if modus == "suche":
    f = {"text": arg, "account_ids": [ACC]}
    if seit != "-": f["since"] = int(D.datetime.strptime(seit, "%Y-%m-%d").timestamp() * 1000)
    d = json.loads(call("/search-messages", {"filter": f, "page": 1, "page_size": int(n) if n != "-" else 30}))
    if "items" not in d: print(str(d)[:300]); sys.exit(1)
    print("Treffer gesamt:", d.get("total_items"))
    for m in sorted(d["items"], key=lambda m: m.get("date") or 0):
        ts = D.datetime.fromtimestamp(int(m.get("date") or 0) / 1000).strftime("%Y-%m-%d %H:%M")
        fr = m.get("from"); fr = fr.get("address") if isinstance(fr, dict) else fr
        print("%s | %s | %s | %s" % (ts, str(fr)[:30], (m.get("subject") or "")[:70], m.get("id")))
else:
    t = call("/message-content/%d/%s" % (ACC, arg))
    try: j = json.loads(t); t = j.get("text") or j.get("plain") or j.get("html") or json.dumps(j, ensure_ascii=False)
    except Exception: pass
    print(t[:int(n) if n != "-" else 6000])
PY
