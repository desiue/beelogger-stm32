# Bauen und flashen

[← Übersicht](README.md)

Stand: 09.11.2026 · gilt für: STM32 261107 · geprüft gegen Code: Commit 80673f1

```mermaid
flowchart LR
  A[Repo holen] --> B[Config_LORA.h und<br/>Config_MULTI.h ausfüllen]
  B --> C[Board wählen<br/>bluepill_f103c8 / blackpill_f411ce]
  C --> D[pio run<br/>firmware.bin]
  D --> E[Startzeile prüfen:<br/>welches Gerät hängt am Kabel?]
  E --> F[Flashen über FTDI<br/>stm32loader]
  F --> G[TTN einstellen<br/>ttn_einrichten.md]
```

## Firmware selbst bauen (PlatformIO)

1. **Repo holen:** `git clone https://github.com/desiue/beelogger-stm32.git` (oder auf GitHub „Code → Download ZIP“).
2. **VS Code** installieren, darin die Erweiterung **PlatformIO IDE**. Ordner `beelogger-stm32` öffnen. PlatformIO lädt beim ersten Bauen den STM32-Core 2.6.0 selbst nach.
3. **Vorlagen kopieren:**
   ```
   cp src/Config_LORA.h.example src/Config_LORA.h
   cp src/Config_MULTI.h.example src/Config_MULTI.h
   ```
4. **`src/Config_LORA.h` ausfüllen:** `LOGGER_NAME`; Variante wählen mit `#define LORA_OTAA` (siehe „ABP oder OTAA“). ABP (`0`): `DEVADDR`, `NWKSKEY`, `APPSKEY` aus der TTN-Konsole. OTAA (`1`): `DEVEUI`, `APPEUI` (JoinEUI), `APPKEY` – DevEUI und JoinEUI in LSB-Reihenfolge, AppKey in MSB (in der TTN-Konsole umschaltbar). Sendeintervalle `WeckIntervall_*`. `LORA_CHANNELS` (1, 3 oder 8) muss zu den Frequenzen in TTN passen.
5. **`src/Config_MULTI.h` ausfüllen:** Sensoren, Waagen-Kalibrierung, wie bei der Original-Firmware.
6. **Board wählen** (unten in der PlatformIO-Leiste oder per Befehl):
   - BluePill (STM32F103, „Blue“): `bluepill_f103c8`
   - BlackPill (STM32F411, „Black“): `blackpill_f411ce`, ältere BlackPill: `blackpill_f401cc`
7. **Bauen:** Häkchen in der PlatformIO-Leiste oder `pio run -e blackpill_f411ce`. Ergebnis: `.pio/build/<board>/firmware.bin`.
8. **Flashen** über FTDI-Adapter mit stm32loader: siehe [`bauen_flashen.md`](bauen_flashen.md). Vorher an der Startzeile prüfen, welches Gerät am Kabel hängt.
9. **TTN einstellen** wie unter [TTN-Einstellungen](ttn_einrichten.md#7-mac-einstellungen-im-detail-abp-lmic-dieser-firmware) bzw. in der [TTN-Anleitung](ttn_einrichten.md), sonst kommen Downlinks nicht an.

**ATmega-Logger** (Universal 1.x, beeloggerSMD): eigener Ordner mit eigener Anleitung, siehe [`atmega_lora/README.md`](../atmega_lora/README.md#selbst-bauen).

> **Tipp:** Vor dem Flashen an der [Startzeile](#serielle-konsole-und-startzeile) prüfen, welches Gerät am Kabel hängt – sonst landen die Schlüssel des falschen Geräts auf dem Board.

Hinweise:
- STM32-Core 2.6.0 (`framework-arduinoststm32@~4.20600`), in `platformio.ini` festgelegt.
- BluePill: Board `bluepill_f103c8_128k` mit `-DARDUINO_BLUEPILL_F103CB` (die Firmware braucht 128 KB).
- Flash-Belegung (07.11., mit OTAA): F103CB 56 500 B, F411CE 55 308 B. BluePill: Die OTAA-Session liegt in der letzten Flash-Seite (F103CB: bei 127 KB). Reicht das Programm bis dorthin, speichert der Logger keine Session und joint bei jedem Start ([speicher.md](speicher.md)).
- `pio run -t upload` geht beim F411 nicht (stm32flash kennt die Chip-ID 0x431 nicht), deshalb stm32loader.

## Eine Firmware, vier Varianten

| | ABP (`LORA_OTAA 0`) | OTAA (`LORA_OTAA 1`) |
|---|---|---|
| **BluePill** F103 (`bluepill_f103c8`) | läuft (beeloggerblue05) | läuft (beelogger011) |
| **BlackPill** F411/F401 (`blackpill_f411ce`, `blackpill_f401cc`) | läuft (beelogger09) | läuft (beelogger08) |

Board = PlatformIO-Umgebung, ABP/OTAA = Schalter im Geräteblock von `Config_LORA.h`. Sonst ist der Code gleich, Korrekturen gelten für alle Varianten.

## Serielle Konsole und Startzeile

9600 Baud, BluePill an `Serial` (PA9/PA10), BlackPill an `Serial1`. Nach jedem Start (also jedem Zyklus) erscheint:

```
STM32_LORA_261107 - STM32F411CE
Name: beelogger09 UID: 353439323337510600410066 DevAddr: 260BD691
```

- `261107`: Firmware-Kennung (`l_ID`), danach der Chip-Typ.
- `Name`: `LOGGER_NAME` aus `Config_LORA.h`.
- `UID`: 96-Bit-Seriennummer des Chips, eindeutig je Board.
- `DevAddr`: LoRaWAN-Adresse, unter der das Gerät in TTN steht.

Damit sieht man vor dem Flashen, welches Gerät am Kabel hängt und welche Firmware darauf läuft.


## Flashen über FTDI (Linux)

Keine Schlüssel in diese Datei.

## Bauen

```
~/.platformio/penv/bin/pio run -e blackpill_f411ce     # oder bluepill_f103c8, blackpill_f401cc
```
Ergebnis: `.pio/build/<env>/firmware.bin`.

Vorher in `src/Config_LORA.h` genau **einen** Geräteblock aktiv lassen (DEVADDR, NWKSKEY, APPSKEY, LOGGER_NAME), in `src/Config_MULTI.h` genau eine Kalibrierung.
**Erst prüfen, welches Gerät am Kabel hängt** (Startzeile `Name/UID/DevAddr`, siehe unten) – sonst sendet das Testboard mit den Schlüsseln eines Feld-Loggers.

## Flashen (ROM-Bootloader über FTDI, USART1 PA9/PA10)

`pio run -t upload` geht für F4 **nicht**: Das mitgelieferte `stm32flash` kennt den F411 nicht („Unknown/unsupported device (Device ID: 0x431)“).
Stattdessen `stm32loader` aus der PlatformIO-Umgebung:

1. BOOT0 = 1, Reset drücken (vor **jedem** Versuch; ein fehlgeschlagener Versuch verbraucht die Auto-Baud-Erkennung). BlackPill: BOOT halten, Reset drücken und loslassen, **BOOT noch ca. 1 s halten**. Zu früh losgelassen startet die alte Firmware (Multimeter: üblicher Wachzyklus statt gleichmäßigem Strom), stm32loader meldet dann „Can't init into bootloader“.
2. Flashen (löschen, schreiben, prüfen, starten):
   ```
   ~/.platformio/penv/bin/stm32loader -p /dev/ttyUSB0 -f F4 -e -w -v -g 0x08000000 -n .pio/build/blackpill_f411ce/firmware.bin
   ```
   BluePill: `-f F1` und `.pio/build/bluepill_f103c8/firmware.bin`.
3. BOOT0 zurück auf 0. Sonst landet der Logger nach jedem Aufwachen (Standby = Neustart) wieder im Bootloader.
4. Schnittstelle zurückstellen – stm32loader lässt sie auf 8E1 (gerade Parität):
   ```
   stty -F /dev/ttyUSB0 9600 cs8 -parenb -cstopb raw -echo
   cat /dev/ttyUSB0
   ```

Hinweise:
- Debug-Konsole: **9600 Baud** (`monitor_speed` in platformio.ini steht seit 06.11. richtig).
- Der Frame-Zähler (Backup-Register 3) überlebt das Flashen, solange der Akku dran bleibt (29.10.: 7958 → 7959). Akku ab = POR = Zähler 0.
- Ausgabe direkt nach dem Flash kann fehlen: Der Logger sendet sofort und geht schlafen; die Startzeile kommt beim nächsten Aufwachen bzw. nach Reset.

## Ablauf am Stück (getestet 07.11.2026 an beelogger08, Black F411, OTAA)

Die Configs je Gerät liegen als `src/Config_*.h.bak_<gerät>` (nicht im Repo). Erst die Startzeile im Seriell-Log prüfen: Hängt das richtige Gerät am Kabel?

```
cd ~/beelogger-stm32-vscode
scripts/seriell-log.sh stop                  # stm32loader braucht den Port allein
cp src/Config_LORA.h.bak_beelogger08 src/Config_LORA.h
cp src/Config_MULTI.h.bak_beelogger08 src/Config_MULTI.h
~/.platformio/penv/bin/pio run -e blackpill_f411ce
# BOOT halten, Reset drücken und loslassen, BOOT noch ca. 1 s halten
~/.platformio/penv/bin/stm32loader -p /dev/ttyUSB0 -f F4 -e -w -v -g 0x08000000 -n .pio/build/blackpill_f411ce/firmware.bin
# BlackPill: BOOT-Taste ist danach schon los. BluePill: Jumper BOOT0 zurück auf 0
stty -F /dev/ttyUSB0 9600 cs8 -parenb -cstopb raw -echo
scripts/seriell-log.sh start                 # "start" kann auch entfallen
```

Erwartet: stm32loader meldet die UID des Geräts (siehe Tabelle unten) und `Verification OK`. `-g` startet die Firmware sofort, die erste Startzeile ist meist schon vorbei, bevor das Log läuft. Die nächste kommt beim nächsten Aufwachen (bis zu ein Intervall später): `Name: beelogger08 …`, `Bat. [V]` plausibel. Die OTAA-Session übersteht das Flashen, solange der Akku dran bleibt (kein Neu-Join; bei TTN: FCnt 46 vor dem Flashen, 47 direkt nach dem Start durch `-g`, 48 beim nächsten Aufwachen, Session unverändert seit 06.11.).

BluePill (F103): `-e bluepill_f103c8`, `-f F1`, `.pio/build/bluepill_f103c8/firmware.bin`.

Debug-Zeilen `batt.:`, `solar:`, `temperature:` im Block `Data:` zeigen die zwei Payload-Bytes einzeln dezimal ohne Trennzeichen: `batt.: 16106` = 16 | 106 = 0x106A = 4202 mV. Kein Fehler.

## Akkuspannung kalibrieren

`Kalib_Spannung` (mV) und `Kalib_Bitwert` in `Config_MULTI.h` sind ein Messpaar: Akkuspannung mit Multimeter messen (nicht in Reihe als Strommesser, das senkt die Spannung), gleichzeitig im Log `ADC-bits: … Bat. [V]` ablesen, beide Werte eintragen. Beispiel beelogger08 (07.11.): 4,20 V bei 1647–1649 Bit → `4200` / `1648` (vorher Black-Vorgabe 4120 / 1650, Anzeige 4,11 V).
