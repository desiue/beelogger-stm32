# Black Pill und Blue Pill: Messungen an 15 Boards

[← Übersicht](README.md)

Stand: 21.11.2026 · gilt für: STM32 261119 · geprüft gegen Code: Commit 67d699d (Aussage zur Einstellungsseite: `src/STM32_LORA.ino:85-95`)

Alle Werte sind Messungen eines einzelnen Aufbaus (Mike, 20. und 21.11.2026): Firmware `STM32_LORA_261119`, Platine BlackPill 1.5.1 mit Adapter, serielles Flashen über FTDI, Ruhestrom mit Multimeter nach dem Start (FTDI abgesteckt, ohne RFM95). Es sind Stichproben, keine Aussage über alle Chargen.

## Ergebnis

1. **Black Pill (F411, Bootloader 0x31): alle sieben Boards laufen.** I²C geht, Ruhestrom 9,7 bis 18,7 µA mit den drei 100-kΩ-Widerständen auf der Platine.
2. **Blue Pill mit Bootloader 0x22 (echter ST-Chip): I²C geht** (blue05, Blue Pill 8).
3. **Blue Pill mit Bootloader 0x30 (vermutlich Nachbau-Chip): Hardware-I²C geht nicht** (Blue Pill 2, 4, 5, 6, 7). Sie laufen nur mit DS18B20 und einer Firmware ohne I²C-Sensoren. Fünf von acht Blue Pills waren so.
4. Blue Pill 3 meldet sich nicht (kein Bootloader, keine Ausgabe) und ist vermutlich defekt.

## Erkennen vor dem Einbau

`stm32loader` zeigt Chip-ID, Bootloader-Version und UID.

| Merkmal | Black Pill | Blue Pill echt | Blue Pill Nachbau |
|---|---|---|---|
| Chip-ID | 0x431 | 0x410 | 0x410 |
| Bootloader | 0x31 | 0x22 | 0x30 |
| UID beginnt mit | verschieden | verschieden (3A0C…, FF55…) | 5BB6-1A5A-00000000 (alle fünf) |
| Hardware-I²C | ja | ja | nein |

Dass 0x30 ein Nachbau ist, ist eine Vermutung aus UID und I²C-Verhalten, nicht belegt.

## Black Pill (Chip-ID 0x431, Bootloader 0x31)

| Board | UID | Flash gemeldet | I²C | Ruhestrom | Bemerkung |
|---|---|---|---|---|---|
| 1 | 0066-0041-33375106-35343932 | 512 KiB | ja | 18,7 µA | |
| 2 | 0015-0030-30345119-33393331 | 512 KiB | ja | 9,84 µA | vorher 422 µA |
| 3 | 005E-005A-31345116-33343336 | 512 KiB | ja | 9,7 µA | Wandler und LED-Widerstand abgelötet |
| 4 | 0057-0038-32355113-38363339 | 256 KiB | ja | 9,8 µA | Wandler und LED-Widerstand abgelötet |
| 5 | 0021-0064-30345119-33393331 | 512 KiB | ja | 9,8 µA | Wandler und LED-Widerstand abgelötet |
| 6 | 0025-0037-31345117-33343336 | 512 KiB | ja | 9,8 µA | Wandler und LED-Widerstand abgelötet |
| 7 | 001F-0035-31345117-33343336 | 512 KiB | ja | 9,9 µA | Wandler abgelötet |

Die großen Unterschiede vorher (50 bis 650 µA) kamen von schwebenden SPI-Eingängen des RFM95, nicht von den Boards. Die drei 100-kΩ-Widerstände (NSS, SCK, MOSI nach 3,3 V) behoben das.

## Blue Pill (Chip-ID 0x410)

| Board | UID | Bootloader | Flash gemeldet / Probe | I²C | Ruhestrom |
|---|---|---|---|---|---|
| blue05 | 3A0C-0F0C-303015C4-32303030 | 0x22 | 128 KiB | ja | 7,1 mA mit I²C-Firmware, Ursache offen |
| Blue Pill 8 | FF55-066E-48558071-67091833 | 0x22 | **64 KiB** / 128k ok | ja | 0,989 mA, LED und Wandler am Board |
| Blue Pill 2 | 5BB6-1A5A-00000000-30003F2A | 0x30 | 128 KiB | nein | 7,9 µA ohne I²C |
| Blue Pill 4 | 5BB6-1A5A-00000000-301117FD | 0x30 | 128 KiB / ok | nein | 7,9 µA ohne I²C |
| Blue Pill 5 | 5BB6-1A5A-00000000-300040FE | 0x30 | 128 KiB / ok | nein | 2 mA, LED und Wandler am Board |
| Blue Pill 6 | 5BB6-1A5A-00000000-301113DC | 0x30 | 128 KiB / ok | nein | 2 mA, LED und Wandler am Board |
| Blue Pill 7 | 5BB6-1A5A-00000000-30111221 | 0x30 | 128 KiB / ok | nein | 2 mA, Aufbau wie 5 und 6 |
| Blue Pill 3 | – | keiner | – | – | defekt (vermutet) |

Ruhestrom ab 0,9 mA stammt aus Aufbauten mit LED und Spannungswandler am Board und ist nicht mit den 7,9 µA vergleichbar. Ohne beide ist für Blue Pill 5 bis 8 und blue05 kein Wert gemessen.

### Hardware-I²C bei Bootloader 0x30

Blue Pill 2: Mit Hardware-I²C (STM32duino Wire, 100 und 10 kHz) antwortet keine der 126 Adressen, jede Anfrage endet nach 26 ms mit NACK. Software-I²C (Bit-Banging, etwa 10 kHz) auf PB6/PB7 findet BH1750 (0x23) und BME280 (0x76). Bus und Sensoren sind also in Ordnung; das I²C-Modul des Chips arbeitet mit dem Arduino-Core nicht. Mit der Firmware samt I²C-Sensoren bleibt der Start nach der Zeile „BME280“ hängen und der Logger schläft nicht (7,1 bis 7,2 mA). Die Ursache wurde nicht weiter verfolgt.

### Gemeldete Flash-Größe 64 KiB (Blue Pill 8)

Der Chip meldet 64 KiB, die Speicherprobe auf der letzten 1-KiB-Seite des 128-KiB-Bereichs gelang. Die Firmware legt ihre Einstellungsseite ans gemeldete Ende (`src/STM32_LORA.ino:91-95`). Ist die Firmware (67 100 B) größer, ist die Adresse 0: Es wird nichts gespeichert, der Logger joint bei jedem Start neu, das Programm bleibt heil. Die Speicherseite der Einstellungen ist in [Speicher](speicher.md) beschrieben.

## Ablauf der Erfassung

Der serielle Bootloader über FTDI genügt (BOOT0 = 1, Reset), ein USB-Bootloader ist bei der Blue Pill nicht nötig. Die Messwerte (Chip-ID, Bootloader, UID, Flash- und RAM-Probe, Sensoren, Spannungen) werden mit `scripts/board-erfassen.sh` erfasst. BOOT0 erst loslassen, wenn das Skript dazu auffordert.
