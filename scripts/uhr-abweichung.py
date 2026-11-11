#!/usr/bin/env python3
"""Uhrabweichung eines Loggers aus dem seriellen Log schätzen.

Vergleicht die angekündigte "Weckzeit" (Logger-Uhr) mit dem Laptop-Zeitstempel
der ersten Ausgabe nach dem Aufwachen (Laptop per NTP). Der Wert enthält eine
feste Anlaufzeit von wenigen Sekunden; aussagekräftig ist die Änderung über
die Zeit. Zeilen mit "Uhr gestellt" werden markiert.

Aufruf: python3 scripts/uhr-abweichung.py [USB3] [~/beelogger-logs/seriell.log]
"""
import datetime as d, os, re, sys

port = sys.argv[1] if len(sys.argv) > 1 else 'USB3'
log = os.path.expanduser(sys.argv[2] if len(sys.argv) > 2 else '~/beelogger-logs/seriell.log')
tag = f'[{port}]'
fmt = '%Y-%m-%d %H:%M:%S'
weck = None
for zeile in open(log, errors='replace'):
    if tag not in zeile:
        continue
    try:
        t = d.datetime.strptime(zeile[:19], fmt)
    except ValueError:
        continue
    if 'Uhr gestellt' in zeile:
        print(f'{t}  --- Uhr gestellt ---')
    m = re.search(r'Weckzeit (\d\d\.\d\d\.\d{4} \d\d:\d\d:\d\d)', zeile)
    if m:
        weck = (d.datetime.strptime(m.group(1), '%d.%m.%Y %H:%M:%S'), t)
        continue
    if weck and (t - weck[1]).total_seconds() > 30:
        abw = int((t - weck[0]).total_seconds())
        if abs(abw) < 600:  # Lücken/Neustarts ausblenden
            print(f'{weck[0]}  wach {t:%H:%M:%S}  Abweichung {abw:+d} s')
        weck = None
