# beelogger LoRa für ATmega328 – Vorschlag 261107

Der ATmega-LoRa-Sketch **beelogger_LORA_250326** von [beelogger.de](https://beelogger.de/?page_id=200563) (beelogger-Universal 1.x und beeloggerSMD) mit **zwei kleinen Änderungen als Vorschlag**. Es sind dieselben zwei Stellen, die mir im STM32-Sketch 260705 aufgefallen sind (siehe [README](../README.md#gefundene-fehler)). Alles andere ist wie im Original: ABP, Messablauf, Payload, Server.

**Stand 07.11.2026:** baut für alle drei Boards. **Auf einem Logger noch nicht getestet**, weil mir kein ATmega-beelogger zur Verfügung steht. Bitte als Vorschlag verstehen. Falls ich etwas übersehen habe, freue ich mich über einen Hinweis.

## Änderungen

| Stelle | Vorher | Nachher | Hintergrund |
|---|---|---|---|
| `Multi_LORA.ino`, `prep_payload()` | `if (LMIC.seqnoUp % 10 == 0)` | `if (LoRaSeqCounter % 10 == 0)` | `LMIC_reset()` löscht die LMIC-Struktur, `seqnoUp` ist danach 0 und wird erst nach `prep_payload()` gesetzt. Nach meinem Verständnis geht die Sketch-ID (4 Byte) deshalb in jedem Uplink mit statt, wie im Kommentar gedacht, in jedem 10. Am STM32 habe ich das in TTN beobachtet, am ATmega nicht. |
| `Multi_LORA.ino`, `setup_LORA()` | – | `LMIC_setAdrMode(0);` | `LMIC_reset()` setzt ADR an. Da der Logger nach jedem Senden schläft, kann er LinkADRReq vermutlich nicht beantworten, und TTN wiederholt sie. Mit der TTN-Einstellung „ADR Disabled“ lässt sich das auch ohne neue Firmware vermeiden. |
| `beelogger_LORA_261107.ino` | `l_ID = 250326` | `l_ID = 261107` | Sketch-ID, damit man am Server sieht, welche Fassung läuft. |

Vollständiger Diff: [`aenderungen_gegen_250326.diff`](aenderungen_gegen_250326.diff). Bibliothek: Original 2026_04, **unverändert** (`DISABLE_JOIN` bleibt gesetzt).

## Speicher (PlatformIO, avr-gcc 7.3, Bibliotheken 2026_04)

| Board | Original 250326 | 261107 |
|---|---|---|
| Pro Mini 8 MHz (beeloggerSMD) | Flash 26 278 B, RAM 1 439 B | Flash 26 264 B, RAM 1 437 B |
| Pro Mini 16 MHz (Universal 1.x) | Flash 26 292 B, RAM 1 439 B | Flash 26 280 B, RAM 1 439 B |
| Nano 16 MHz (Universal 1.x) | Flash 26 292 B, RAM 1 439 B | Flash 26 280 B, RAM 1 439 B |

Verfügbar: 30 720 B Flash (32 KB minus Bootloader), 2 048 B RAM. Der Speicherbedarf bleibt praktisch gleich. Mit der Arduino IDE oder einer eigenen `Config_MULTI.h` können die Werte etwas abweichen.

## Übernehmen

1. Eigene `Config_LORA.h` und `Config_MULTI.h` behalten. Die Dateien in diesem Ordner sind die Platzhalter aus dem Download.
2. Entweder die beiden Zeilen im eigenen Sketch ändern (siehe Tabelle) oder die `.ino`-Dateien aus [`beelogger_LORA_261107/`](beelogger_LORA_261107/) nehmen und die eigenen Config-Dateien dazulegen.
3. Mit der Arduino IDE wie gewohnt aufspielen. Der Frame-Counter im EEPROM bleibt erhalten.

Nur bauen ohne Aufspielen: `pio run` in diesem Ordner (Umgebungen `promini8`, `promini16`, `nano`).

## Test auf einem Logger

1. In TTN unter Live data einen Uplink vor und einen nach dem Aufspielen vergleichen:
   - FCnt läuft weiter (kein Sprung auf 0),
   - der Uplink hat das ADR-Bit nicht mehr (in den Uplink-Details `"adr": false` oder kein `adr`),
   - Payload-Länge: die Sketch-ID kommt nur noch bei jedem 10. FCnt mit, die übrigen Uplinks sind 4 Byte kürzer.
2. Am beelogger-Server: Werte kommen wie bisher, die Sketch-ID zeigt 261107.
3. Einen Tag laufen lassen und in TTN die Zahl der Downlinks ansehen. Mit den Einstellungen aus der [TTN-Anleitung](../docs/ttn_anleitung.md) (ADR Disabled, Status periodicity 0) sollten kaum noch welche kommen.

Wenn etwas anders aussieht als hier beschrieben, bitte melden. Dann habe ich mich vermutlich getäuscht.

## Lizenz

GPL v3 wie das Original, © R. Schick / Thorsten Gurzan, beelogger.de.
