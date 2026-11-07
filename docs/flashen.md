# Firmware bauen und flashen (PlatformIO, Linux-Laptop)

Stand: 06.11.2026. Keine Schlüssel in diese Datei. Schlüssel-Archiv: lokal, nicht im Repo. Quelle u. a. Wiki-Kapitel 10 „Firmware bauen und flashen“ (29.10.2026).

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

## Bekannte Geräte

Startzeile seriell: `Name: <LOGGER_NAME> UID: <w2 w1 w0> DevAddr: <hex>` – die UID steht dort in umgekehrter Wortreihenfolge zu stm32loader.


Geräte-ID = `device_id` in TTN (für API und `scripts/ttn-status.sh <app> <device_id>`). Rolle laut Planung Paket 3.

| Nr. | Name | Board | Modus | TTN-App / Gerät (device_id) | Firmware |
|---|---|---|---|---|---|
| 1 | beelogger09 | Black F411CE | ABP 260BD691 | beelogger09 / beelogger09 | Paket 2 |
| 2 | beeloggerblue05 | Blue F103CB | ABP 260B6573 | beeloggerblue05 / eui-70b3d57ed005f5e0 | Paket 2 |
| 3 | beelogger011 | Blue F103C8 | OTAA | beelogger011 / beelogger011-otaa | 261106 OTAA |
| 4 | beelogger08 | Black F411CE | OTAA | beelogger08 / beelogger08-otaa | 261106 OTAA |
| – | beeloggerblue01 | Black F411 | ABP 260B939B | beeloggerblue01 / beeloggerblue01 | 260705 |
| – | beelogger07 (Feldgerät) | ? | ABP 260BEB70 | beeloggerblue07 / eui-70b3d57ed0066fa2 | alte Eigenbau-FW |

Nr. 1–4 = Testgeräte (Rolle laut Planung Paket 3). UID wie stm32loader sie meldet.

**1 beelogger09** (Quad9, Testboard)
- UID 0066-0041-33375106-35343932, 512 KB.
- Alte FW Aux3 = 1. 06.11. kurz versehentlich als 260BEB70 geflasht, dann 260705 als beelogger09 (Uplink 14:44 UTC, FCnt 4), danach Paket 2. 261106 noch nicht geflasht.

**2 beeloggerblue05** (Testboard Blue)
- Startzeile-UID 30003F2A000000001A5A5BB6. Reset-Taster defekt (R gegen G brücken).
- Paket 2 abgenommen 06.11. 18:51 UTC. 261106 noch nicht geflasht.

**3 beelogger011** (Testboard Blue)
- UID 5BB6-1A5A-00000000-301117FD, F103C8T6 meldet 128 KB. DevEUI 70B3D57ED0079D7A. Altes ABP-Gerät `beelogger011` (260BF6FE) steht noch in TTN.
- Kalibrierung 4150 mV / 1636 Bit.
- 07.11. 01:17 261106 mit OTAA (Session im Flash): POR → Join 01:18:34 (DevAddr 260B21D9), FCnt 0; 01:23 Neustart aus Flash ohne Join, FCnt 1, t-on 5 s.
- Davor 261106 ABP seit 06.11.: erster Uplink 20:31 UTC FCnt 1 (FCnt 0 verworfen, Altwert vom März); 20:38 UTC Rx2 DR 3 + Session/MAC-Reset per API.

**4 beelogger08**
- UID 0030-006E-33375105-35343932, 512 KB. DevEUI 70B3D57ED0079D78. Altes ABP-Gerät `beelogger08` (260B2D40) steht noch in TTN.
- 261106 OTAA seit 06.11. 22:26 (Join 21:26:29 UTC, erster Uplink FCnt 0). Akku ab/an 22:32–22:40: jedes Mal Neu-Join nach 7–8 s mit neuer DevAddr, FCnt 0 angenommen.
- 07.11. 02:56 neu geflasht (Kalibrierung 4200 mV / 1648 Bit, Anzeige 4,20 V = Multimeter), Session blieb erhalten.
- Strom: alte FW wach 24–36 mA, Schlaf 18,2 µA, Wachzeit 18 s; neue FW wach 24–34 mA, Schlaf 18,09 µA. Multimeter in Reihe senkt die angezeigte Akkuspannung (3,8–4,1 V statt 4,21 V).

**beeloggerblue01** (Testumgebung Black): laut 29.10. 256 KB Flash gemeldet, 260705 seit 29.10.

**beelogger07** (Feldgerät): FCnt 8203 am 06.11., Aux3 = 1.
