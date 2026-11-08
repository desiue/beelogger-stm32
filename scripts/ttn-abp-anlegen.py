#!/usr/bin/env python3
# Legt (falls nötig) eine TTN-Anwendung mit beelogger-Payload-Formatter und darin ein ABP-Gerät an
# und schreibt DEVADDR/NWKSKEY/APPSKEY in eine Config_LORA.h, ohne Schlüssel auszugeben.
# Aufruf: scripts/ttn-abp-anlegen.py <app> <device_id> <config_lora.h> [formatter.js]
# Die Config darf nicht von git verfolgt sein (echte Schlüssel).
import json, os, re, secrets, subprocess, sys, urllib.request

app, dev, cfg = sys.argv[1:4]
fmt = sys.argv[4] if len(sys.argv) > 4 else None
key = open(os.path.expanduser("~/.config/ttn/konto.key")).read().strip()
host = "eu1.cloud.thethings.network"
base = f"https://{host}/api/v3"

if subprocess.run(["git", "ls-files", "--error-unmatch", cfg], cwd=os.path.dirname(os.path.abspath(cfg)),
                  capture_output=True).returncode == 0:
    sys.exit(f"{cfg} wird von git verfolgt - keine Schlüssel hineinschreiben")

def call(method, path, body=None, ok404=False):
    req = urllib.request.Request(base + path, method=method, data=json.dumps(body).encode() if body is not None else None,
                                 headers={"Authorization": "Bearer " + key, "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        if ok404 and e.code == 404:
            return None
        sys.exit(f"{method} {path}: HTTP {e.code} {e.read().decode()[:300]}")

# Config vorher prüfen, damit nichts halb angelegt wird
s = open(cfg, newline="").read()
for pat in (r"NWKSKEY\[16\] = \{[^}]*\}", r"APPSKEY\[16\] = \{[^}]*\}", r"\nstatic const u4_t DEVADDR = 0x[0-9A-Fa-f]+;"):
    assert len(re.findall(pat, s)) == 1, "nicht eindeutig in Config: " + pat

if call("GET", f"/applications/{app}", ok404=True) is None:
    user = call("GET", "/auth_info")["api_key"]["entity_ids"]["user_ids"]["user_id"]
    call("POST", f"/users/{user}/applications", {"application": {"ids": {"application_id": app}, "name": app}})
    print("Anwendung angelegt:", app)
if fmt:
    call("PUT", f"/as/applications/{app}/link", {"link": {"default_formatters": {"up_formatter": "FORMATTER_JAVASCRIPT",
         "up_formatter_parameter": open(fmt).read()}}, "field_mask": {"paths": ["default_formatters"]}})

dev_addr = call("GET", "/ns/dev_addr")["dev_addr"]
nwk, aps = secrets.token_hex(16).upper(), secrets.token_hex(16).upper()
ids = {"device_id": dev, "application_ids": {"application_id": app}, "dev_addr": dev_addr}

call("POST", f"/applications/{app}/devices", {"end_device": {"ids": ids, "name": dev + " ABP",
     "network_server_address": host, "application_server_address": host},
     "field_mask": {"paths": ["name", "network_server_address", "application_server_address"]}})
mac = {"rx1_delay": 1, "desired_rx1_delay": 1, "rx1_data_rate_offset": 0, "desired_rx1_data_rate_offset": 0,
       "rx2_data_rate_index": 3, "desired_rx2_data_rate_index": 3, "rx2_frequency": "869525000", "desired_rx2_frequency": "869525000",
       "factory_preset_frequencies": ["868100000", "868300000", "868500000", "867100000", "867300000", "867500000", "867700000", "867900000"],
       "supports_32_bit_f_cnt": True, "resets_f_cnt": True, "status_time_periodicity": "0s", "status_count_periodicity": 0,
       "adr": {"disabled": {}}}
call("PUT", f"/ns/applications/{app}/devices/{dev}", {"end_device": {"ids": ids, "frequency_plan_id": "EU_863_870_TTN",
     "lorawan_version": "MAC_V1_0_2", "lorawan_phy_version": "PHY_V1_0_2_REV_B", "supports_join": False, "mac_settings": mac,
     "session": {"dev_addr": dev_addr, "keys": {"f_nwk_s_int_key": {"key": nwk}}}},
     "field_mask": {"paths": ["frequency_plan_id", "lorawan_version", "lorawan_phy_version", "supports_join", "mac_settings",
                              "session.dev_addr", "session.keys.f_nwk_s_int_key.key"]}})
call("PUT", f"/as/applications/{app}/devices/{dev}", {"end_device": {"ids": ids,
     "session": {"dev_addr": dev_addr, "keys": {"app_s_key": {"key": aps}}}},
     "field_mask": {"paths": ["session.dev_addr", "session.keys.app_s_key.key"]}})

arr = lambda h: ", ".join("0x" + b for b in re.findall("..", h))  # ABP-Schlüssel MSB zuerst
s = re.sub(r"NWKSKEY\[16\] = \{[^}]*\}", "NWKSKEY[16] = { %s }" % arr(nwk), s)
s = re.sub(r"APPSKEY\[16\] = \{[^}]*\}", "APPSKEY[16] = { %s }" % arr(aps), s)
s = re.sub(r"\nstatic const u4_t DEVADDR = 0x[0-9A-Fa-f]+;", "\nstatic const u4_t DEVADDR = 0x%s;" % dev_addr, s)
open(cfg, "w", newline="").write(s)
print("angelegt:", app, dev, "DevAddr", dev_addr, "- Schlüssel in", cfg)
