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

1. BOOT0 = 1, Reset drücken (vor **jedem** Versuch; ein fehlgeschlagener Versuch verbraucht die Auto-Baud-Erkennung).
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

## Bekannte Geräte

Startzeile seriell: `Name: <LOGGER_NAME> UID: <w2 w1 w0> DevAddr: <hex>` – die UID steht dort in umgekehrter Wortreihenfolge zu stm32loader.


Geräte-ID = `device_id` in TTN (für API und `scripts/ttn-status.sh <app> <device_id>`). Rolle laut Planung Paket 3.

| Name | Rolle | DevAddr | TTN-App | TTN-Gerät (device_id) | Chip / UID | Firmware |
|---|---|---|---|---|---|---|
| beelogger09 (Quad9, Testboard) | Test 1, ABP | 260BD691 | beelogger09 | beelogger09 | Black F411CE 512 KB, UID 0066-0041-33375106-35343932 | alte FW Aux3 = 1; 06.11. kurz versehentlich als 260BEB70 geflasht, dann 260705 als beelogger09 (Uplink 06.11. 14:44 UTC, FCnt 4); danach Paket 2. 261106 noch nicht geflasht |
| beeloggerblue05 (Testboard Blue) | Test 2, ABP | 260B6573 | beeloggerblue05 | eui-70b3d57ed005f5e0 | Blue F103, Startzeile-UID 30003F2A000000001A5A5BB6; Reset-Taster defekt | Paket 2, abgenommen 06.11. 18:51 UTC. 261106 noch nicht geflasht |
| beelogger011 (Testboard Blue) | Test 3, OTAA geplant | 260BF6FE | beelogger011 | beelogger011 | Blue F103C8T6, meldet 128 KB, UID 5BB6-1A5A-00000000-301117FD | 261106 seit 06.11. (vorher alte FW, nicht empfangen); Spannung kalibriert 4150 mV / 1636 Bit; erster Uplink 06.11. 20:31 UTC FCnt 1 (FCnt 0 verworfen, Altwert 0 vom März); 20:38 UTC Rx2 DR 3 + Session/MAC-Reset per API |
| beelogger08 | Test 4, OTAA geplant | 260B2D40 | beelogger08 | beelogger08 | Black, Chip ? | alte FW; **vor dem Update Strom/Wachzeit messen** |
| beeloggerblue01 (Testumgebung Black) | – | 260B939B | beeloggerblue01 | beeloggerblue01 | F411, laut 29.10. 256 KB Flash gemeldet | 260705 seit 29.10. |
| beelogger07 (Feldgerät) | – | 260BEB70 | beeloggerblue07 | eui-70b3d57ed0066fa2 | ? | alte Eigenbau-FW, Aux3 = 1 (FCnt 8203 am 06.11.) |
