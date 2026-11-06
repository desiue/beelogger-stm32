#!/usr/bin/env python3
# Legt ein OTAA-Gerät in TTN an und schreibt DEVEUI/APPEUI/APPKEY in Config_LORA.h (Block mit LOGGER_NAME), ohne Schlüssel auszugeben.
# Aufruf: scripts/ttn-otaa-anlegen.py <app> <device_id> <logger_name> <config_lora.h>
import json, os, re, secrets, sys, urllib.request

app, dev, name, cfg = sys.argv[1:5]
key = open(os.path.expanduser("~/.config/ttn/konto.key")).read().strip()
base = "https://eu1.cloud.thethings.network/api/v3"
host = "eu1.cloud.thethings.network"

def call(method, path, body=None):
    req = urllib.request.Request(base + path, method=method, data=json.dumps(body).encode() if body is not None else None,
                                 headers={"Authorization": "Bearer " + key, "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        sys.exit(f"{method} {path}: HTTP {e.code} {e.read().decode()[:300]}")

# Config-Block vorher prüfen, damit nichts halb angelegt wird
s = open(cfg).read()
m = re.search(r'#define LOGGER_NAME "%s".*?(?=\n//\w[^\n]* - |\Z)' % re.escape(name), s, re.S)
assert m and m.group(0).count("DEVEUI[8]") == 1, "Block mit DEVEUI für %s nicht gefunden" % name

dev_eui = call("POST", f"/applications/{app}/dev-eui", {})["dev_eui"]
join_eui = "0000000000000000"
app_key = secrets.token_hex(16).upper()
ids = {"device_id": dev, "application_ids": {"application_id": app}, "dev_eui": dev_eui, "join_eui": join_eui}

call("POST", f"/applications/{app}/devices", {"end_device": {"ids": ids, "name": name + " OTAA",
     "network_server_address": host, "application_server_address": host, "join_server_address": host},
     "field_mask": {"paths": ["name", "network_server_address", "application_server_address", "join_server_address"]}})
call("PUT", f"/js/applications/{app}/devices/{dev}", {"end_device": {"ids": ids,
     "network_server_address": host, "application_server_address": host, "root_keys": {"app_key": {"key": app_key}}},
     "field_mask": {"paths": ["network_server_address", "application_server_address", "root_keys.app_key.key"]}})
mac = {"rx1_delay": 1, "desired_rx1_delay": 1, "rx1_data_rate_offset": 0, "desired_rx1_data_rate_offset": 0,
       "rx2_data_rate_index": 3, "desired_rx2_data_rate_index": 3, "rx2_frequency": "869525000", "desired_rx2_frequency": "869525000",
       "supports_32_bit_f_cnt": True, "resets_f_cnt": False, "status_time_periodicity": "0s", "status_count_periodicity": 0,
       "adr": {"disabled": {}}}
call("PUT", f"/ns/applications/{app}/devices/{dev}", {"end_device": {"ids": ids, "frequency_plan_id": "EU_863_870_TTN",
     "lorawan_version": "MAC_V1_0_2", "lorawan_phy_version": "PHY_V1_0_2_REV_B", "supports_join": True, "mac_settings": mac},
     "field_mask": {"paths": ["frequency_plan_id", "lorawan_version", "lorawan_phy_version", "supports_join", "mac_settings"]}})
call("PUT", f"/as/applications/{app}/devices/{dev}", {"end_device": {"ids": ids}, "field_mask": {"paths": []}})

arr = lambda h, lsb: ", ".join("0x" + b for b in (lambda l: l[::-1] if lsb else l)(re.findall("..", h)))
blk = m.group(0)
blk = re.sub(r"DEVEUI\[8\] = \{[^}]*\}", "DEVEUI[8] = { %s }" % arr(dev_eui, True), blk)
blk = re.sub(r"APPEUI\[8\] = \{[^}]*\}", "APPEUI[8] = { %s }" % arr(join_eui, True), blk)
blk = re.sub(r"APPKEY\[16\] = \{[^}]*\}", "APPKEY[16] = { %s }" % arr(app_key, False), blk)
open(cfg, "w").write(s[:m.start()] + blk + s[m.end():])
print("angelegt:", app, dev, "DevEUI", dev_eui, "JoinEUI", join_eui, "- Schlüssel in", cfg)
