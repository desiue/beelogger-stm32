#!/usr/bin/env python3
"""doku-check.py – prüft, ob Zahlen in README.md und docs/*.md zum Code passen.

Liest Schlüsselwerte aus dem Code (src/, server/) und sucht in der Doku nach Stellen,
an denen derselbe Begriff mit einer anderen Zahl steht. Siehe docs/doku_pflege.md.

Aufruf:  python3 scripts/doku-check.py        (im Repo-Wurzelordner)
Rückgabe: 0 = keine Abweichung, 1 = Abweichungen gefunden, 2 = Wert im Code nicht gefunden.
Planungsdateien (docs/planung/) werden nicht geprüft: sie beschreiben ältere Stände.
"""
import glob
import re
import sys

def code(path, pattern):
    txt = open(path, encoding="utf-8", errors="replace").read()
    m = re.search(pattern, txt, re.M)
    return m.group(1) if m else None

# (Name, Code-Datei, Regex für den Wert im Code, Regex für Doku-Stellen; Gruppe 1 = Zahl in der Doku)
PRUEFUNGEN = [
    ("VMinimum", "src/Config_MULTI.h.example", r"^const float VMinimum\s*=\s*([\d.]+)",
     r"VMinimum`?\s*\(?\s*([\d,]+)\s*V"),
    ("VAlternativ", "src/Config_MULTI.h.example", r"^const float VAlternativ\s*=\s*([\d.]+)",
     r"VAlternativ`?\s*\(?\s*([\d,]+)\s*V"),
    ("AlternativIntervallMinuten", "src/Config_LORA.h.example", r"#define AlternativIntervallMinuten\s+(\d+)",
     r"AlternativIntervallMinuten`?\s*(?:=|\|)?\s*(\d+)"),
    ("Uplink-FPort", "src/STM32_LORA.ino", r"LMIC_setTxData2\((\d+),",
     r"Uplink(?: auf| \(Logger → Server\) \|)? FPort\s*(\d+)\b"),
    ("OTAA-Neu-Join bei FCnt", "src/STM32_LORA.ino", r"#define OTAA_FCNT_MAX\s+(\d+)",
     r"Neu-Join bei FCnt\s*(?:≥\s*)?(\d+)"),
    ("Zählerüberlauf ab 260705", "src/STM32_LORA.ino", r"^\s*if \(LoRaSeqCounter > (\d+)\) LoRaSeqCounter = 0;\s*\n\}",
     r"(?:springt|Überlauf)[^\n.]{0,25}?nach (\d{5})(?![^\n]{0,30}230711)"),
    ("Fern-Intervall FPort 10 max", "src/STM32_LORA.ino", r"m >= 5 && m <= (\d+)\)\) FernIntervall",
     r"FPort 10[^\n]{0,60}?5\s*[–-]\s*(\d+)"),
    ("Fern-Intervall FPort 11 max", "src/STM32_LORA.ino", r"d\[6\] <= (\d+)\) FernIntervall",
     r"FPort 11[^\n]{0,60}?5\s*[–-]\s*(\d+)"),
    ("Nacht-Beginn", "src/Config_LORA.h.example", r"#define WeckIntervall_Nacht_Anfang\s+(\d+)",
     r"Nacht\w*\s+(\d+)\s*[–-]\s*\d+\s*Uhr"),
    ("Nacht-Ende", "src/Config_LORA.h.example", r"#define WeckIntervall_Nacht_Ende\s+(\d+)",
     r"Nacht\w*\s+\d+\s*[–-]\s*(\d+)\s*Uhr"),
    ("Downlink-Intervall Server max", "server/lora_downlink.php", r"\$iv>(\d+)\)\$iv=0",
     r"Messintervall in Minuten \(5\s*[–-]\s*(\d+)\)"),
]

def norm(z):
    return z.replace(",", ".").rstrip("0").rstrip(".") if "." in z.replace(",", ".") else z

def main():
    dateien = ["README.md"] + sorted(glob.glob("docs/*.md"))
    fehler = 0
    fehlt = 0
    for name, datei, rx_code, rx_doku in PRUEFUNGEN:
        try:
            soll = code(datei, rx_code)
        except FileNotFoundError:
            soll = None
        if soll is None:
            print(f"?  {name}: Wert in {datei} nicht gefunden (Regex anpassen)")
            fehlt += 1
            continue
        for d in dateien:
            for nr, zeile in enumerate(open(d, encoding="utf-8"), 1):
                for m in re.finditer(rx_doku, zeile):
                    ist = m.group(1)
                    if norm(ist) != norm(soll):
                        print(f"✗  {d}:{nr}: {name} = {ist}, Code ({datei}) = {soll}")
                        fehler += 1
        print(f"ok {name} = {soll}")
    print(f"\n{fehler} Abweichung(en), {fehlt} nicht gefunden")
    sys.exit(1 if fehler else (2 if fehlt else 0))

if __name__ == "__main__":
    main()
