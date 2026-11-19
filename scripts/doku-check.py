#!/usr/bin/env python3
"""doku-check.py – prüft, ob Zahlen in README.md, docs/*.md und atmega_lora/*.md zum Code passen.

Liest Schlüsselwerte aus dem Code (src/, server/) und sucht in der Doku nach Stellen,
an denen derselbe Begriff mit einer anderen Zahl steht. Siehe docs/doku_pflege.md.

Zweiter Teil (Belegpflicht, Projekt-Regel 19.11.2026): Absätze/Tabellenzeilen mit Zustandsaussagen
(„getestet“, „fertig“, „läuft weiter“, „seit 08.11.“, „Langzeittest“ …) brauchen im selben Absatz einen
Beleg: Code-Stelle (Datei:Zeile, „Z. 12“), Commit, Datum, „gemessen“, Bytes, Mail, oder „ungeprüft“.
Fehltreffer (keine Zustandsaussage) stehen in KEIN_ZUSTAND.

Aufruf:  python3 scripts/doku-check.py [--locker]   (im Repo-Wurzelordner)
Rückgabe: 0 = keine Abweichung, 1 = Abweichungen gefunden (Vorgabe streng: auch unbelegte Aussagen; mit --locker nur melden),
2 = Wert im Code nicht gefunden.
Planungsdateien (docs/planung/) werden nicht geprüft: sie beschreiben ältere Stände.
Achtung: Die Sollwerte kommen aus dem STM32-Code. Nennt eine ATmega-Seite einen eigenen Wert mit
demselben Begriff, braucht er einen eigenen Eintrag mit Code-Datei unter atmega_lora/.
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
    ("Fern-Intervall FPort 10 max", "src/STM32_LORA.ino", r"m >= 5 && m <= (\d+)\)\)[^;\n]{0,30}FernIntervall",
     r"FPort 10[^\n]{0,60}?5\s*[–-]\s*(\d+)"),
    ("Fern-Intervall FPort 11 max", "src/STM32_LORA.ino", r"d\[6\] <= (\d+)[^;\n]{0,30}\) FernIntervall",
     r"FPort 11[^\n]{0,60}?5\s*[–-]\s*(\d+)"),
    ("Nacht-Beginn", "src/Config_LORA.h.example", r"#define WeckIntervall_Nacht_Anfang\s+(\d+)",
     r"Nacht\w*\s+(\d+)\s*[–-]\s*\d+\s*Uhr"),
    ("Nacht-Ende", "src/Config_LORA.h.example", r"#define WeckIntervall_Nacht_Ende\s+(\d+)",
     r"Nacht\w*\s+\d+\s*[–-]\s*(\d+)\s*Uhr"),
    ("Downlink-Intervall Server max", "server/lora_downlink.php", r"\$iv>(\d+)\)\$iv=0",
     r"Messintervall in Minuten \(5\s*[–-]\s*(\d+)\)"),
]

# Zustandsaussage: behauptet einen Test-, Lauf- oder Planungsstand
ZUSTAND = re.compile(r"\b(getestet|erprobt|bestätigt|fertig|online|noch offen|wird (?:getestet|geprüft)|geplant"
                     r"|in Arbeit|seit \d\d?\.\d\d?\.|läuft (?:weiter|noch|seit|stabil)|läuft \(|laufen (?:weiter|noch|seit)"
                     r"|funktioniert (?:auf|mit|bei)|Langzeittest|Dauerlauf|ungetestet|neueste?"
                     r"|aktuelle[nrs]? (?:Stand|Version|Firmware|Paket))", re.I)
BELEG = re.compile(r"(nicht (?:selbst )?getestet|ungetestet|ungeprüft|\w\.\w{1,4}:\d+|\bZ\.\s?\d+|\bZeile\s\d+"
                   r"|\b[0-9a-f]{7,40}\b|gemessen|Messung|Bytes?\b|\bB\)|Mail|Stand:?\s\d|Commit|Quelle|laut "
                   r"|\(\d\d\.\d\d\.(?:\d{4})?\)|\d\d\.\d\d\.\d{4})", re.I)
# (Datei, Textstück) – Treffer, die keine Zustandsaussage sind
KEIN_ZUSTAND = [
    ("docs/architektur.md", "Uhrenquarz läuft (LSE)"),
    ("docs/fehlersuche.md", "Senden nicht innerhalb der Wartezeit fertig"),
    ("docs/fehlersuche.md", "Downlink geplant, kommt nie an"),
    ("docs/hardware.md", "Fertigungsdaten"),
    ("docs/ttn_downlink_schleife.md", "ältere Sketches ohne Überlauf | läuft weiter"),
    ("docs/ttn_downlink_schleife.md", "noch offene MAC-Befehle"),
]

def absaetze(txt):
    """liefert (Zeilennr, Text) je Absatz bzw. Tabellenzeile, Codeblöcke ausgenommen"""
    txt = re.sub(r"```.*?```", lambda m: "\n" * m.group(0).count("\n"), txt, flags=re.S)
    blk, start = [], 1
    for i, z in enumerate(txt.split("\n"), 1):
        if z.strip() == "" or z.startswith("#") or z.startswith("|"):
            if blk:
                yield start, " ".join(blk)
                blk = []
            if z.startswith("|"):
                yield i, z
        else:
            if not blk:
                start = i
            blk.append(z)
    if blk:
        yield start, " ".join(blk)

def belege(dateien):
    n = 0
    for d in dateien:
        for nr, a in absaetze(open(d, encoding="utf-8").read()):
            m = ZUSTAND.search(a)
            if not m or BELEG.search(a) or any(d == f and t in a for f, t in KEIN_ZUSTAND):
                continue
            print(f"?  {d}:{nr}: unbelegt „{m.group(0)}“: {a.strip()[:100]}")
            n += 1
    return n

def norm(z):
    return z.replace(",", ".").rstrip("0").rstrip(".") if "." in z.replace(",", ".") else z

def main():
    dateien = ["README.md"] + sorted(glob.glob("docs/*.md")) + sorted(glob.glob("atmega_lora/*.md"))
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
    print()
    unbelegt = belege(dateien)
    print(f"\n{fehler} Abweichung(en), {fehlt} nicht gefunden, {unbelegt} unbelegte Zustandsaussage(n)")
    streng = "--locker" not in sys.argv  # Vorgabe seit 19.11.2026: unbelegte Aussagen = Fehler
    sys.exit(1 if fehler or (streng and unbelegt) else (2 if fehlt else 0))

if __name__ == "__main__":
    main()
