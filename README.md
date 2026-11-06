# beelogger STM32 – LoRaWAN-Firmware (PlatformIO)

Die beelogger-Firmware **STM32_LORA_260705** (beelogger.de) als PlatformIO-Projekt für VS Code, mit Erweiterungen.
Ziel: Das Sendeintervall soll sich per Downlink aus der Ferne ändern lassen, und neue Geräte sollen per OTAA statt ABP ins Netz gehen.
Gefundene Fehler werden behoben und dokumentiert, damit die beelogger-Entwickler sie übernehmen können.

**Keine Schlüssel oder Passwörter im Repo** (siehe „Schlüssel“).

## Inhalt

| Pfad | Inhalt |
|---|---|
| `src/` | Firmware (Sketch 260705 mit Änderungen, siehe unten) |
| `src/Config_LORA.h.example`, `src/Config_MULTI.h.example` | Vorlagen für LoRa- und Sensorkonfiguration (nur Platzhalter) |
| `lib/` | beelogger-Bibliotheken 2026_04, unverändert |
| `orginal_firmware_bibliotheken/` | Originale von beelogger.de zum Vergleich (Sketche 202610, Bibliotheken 2026_04, WLAN/LTE 230928) |
| `platformio.ini` | Boards BluePill F103CB, BlackPill F411CE, BlackPill F401CC |
| `docs/flashen.md` | Bauen, Flashen über FTDI (stm32loader), Gerätetabelle |
| `docs/planung_otaa_downlink.md` | Plan, Entscheidungen und Verlauf der Arbeitspakete |
| `docs/aenderungen_gegen_260705.diff` | Alle Änderungen gegenüber dem Original-Sketch 260705 |
| `docs/ttn/` | TTN-Einstellungen (JSON aus der TTN-API), Payload-Decoder, Beispielnachrichten |
| `docs/logs/` | Serielle Mitschnitte (9600 Baud) |
| `scripts/` | `seriell-log.sh` (Konsole mitschreiben), `ttn-status.sh` (TTN-Werte eines Geräts prüfen) |

## Firmware selbst bauen

1. **Repo holen:** `git clone https://github.com/desiue/beelogger-stm32.git` (oder auf GitHub „Code → Download ZIP“).
2. **VS Code** installieren, darin die Erweiterung **PlatformIO IDE**. Ordner `beelogger-stm32` öffnen. PlatformIO lädt beim ersten Bauen den STM32-Core 2.6.0 selbst nach.
3. **Vorlagen kopieren:**
   ```
   cp src/Config_LORA.h.example src/Config_LORA.h
   cp src/Config_MULTI.h.example src/Config_MULTI.h
   ```
4. **`src/Config_LORA.h` ausfüllen:** `LOGGER_NAME`, `DEVADDR`, `NWKSKEY`, `APPSKEY` aus der TTN-Konsole (ABP), Sendeintervalle `WeckIntervall_*`. `LORA_CHANNELS` (1, 3 oder 8) muss zu den Frequenzen in TTN passen.
5. **`src/Config_MULTI.h` ausfüllen:** Sensoren, Waagen-Kalibrierung, wie bei der Original-Firmware.
6. **Board wählen** (unten in der PlatformIO-Leiste oder per Befehl):
   - BluePill (STM32F103, „Blue“): `bluepill_f103c8`
   - BlackPill (STM32F411, „Black“): `blackpill_f411ce`, ältere BlackPill: `blackpill_f401cc`
7. **Bauen:** Häkchen in der PlatformIO-Leiste oder `pio run -e blackpill_f411ce`. Ergebnis: `.pio/build/<board>/firmware.bin`.
8. **Flashen** über FTDI-Adapter mit stm32loader: siehe [`docs/flashen.md`](docs/flashen.md). Vorher an der Startzeile prüfen, welches Gerät am Kabel hängt.
9. **TTN einstellen** wie unter „TTN-Einstellungen“, sonst kommen Downlinks nicht an.

OTAA (Paket 3) kommt später als Schalter `#define LORA_OTAA` in `Config_LORA.h`. Bis dahin nur ABP.

Hinweise:
- STM32-Core 2.6.0 (`framework-arduinoststm32@~4.20600`), in `platformio.ini` festgelegt.
- BluePill: Board `bluepill_f103c8_128k` mit `-DARDUINO_BLUEPILL_F103CB` (die Firmware braucht 128 KB).
- Flash-Belegung (Paket 2): F103CB 53 884 B, F411CE 53 176 B.
- `pio run -t upload` geht beim F411 nicht (stm32flash kennt die Chip-ID 0x431 nicht), deshalb stm32loader.

## Serielle Konsole und Startzeile

9600 Baud, BluePill an `Serial` (PA9/PA10), BlackPill an `Serial1`. Nach jedem Start (also jedem Zyklus) erscheint:

```
STM32_LORA_
261106STM32F411CE
Name: beelogger09 UID: 353439323337510600410066 DevAddr: 260BD691
```

- `261106`: Firmware-Kennung (`l_ID`), danach der Chip-Typ.
- `Name`: `LOGGER_NAME` aus `Config_LORA.h`.
- `UID`: 96-Bit-Seriennummer des Chips, eindeutig je Board.
- `DevAddr`: LoRaWAN-Adresse, unter der das Gerät in TTN steht.

Damit sieht man vor dem Flashen, welches Gerät am Kabel hängt und welche Firmware darauf läuft.

## Änderungen gegenüber 260705

Vollständig in `docs/aenderungen_gegen_260705.diff`. Kurz:

1. **Startzeile** in `System_Init()` (siehe oben).
2. **Downlink Sendeintervall** (`onEvent()` in `STM32_LORA.ino`):
   - FPort **10**, 1 Byte = Intervall in Minuten, gültig **5–180**; **0** = zurück zur Firmware-Vorgabe; andere Werte werden ignoriert.
   - Gespeichert in RTC-Backup-Register 5, Bit 9–14 (Minuten/5). Übersteht den Tiefschlaf, nicht aber Akku-ab. Alte Firmware schreibt dort 0, ein Update ist also neutral.
   - Ersetzt Tag/Nacht/Winter-Intervall. Service-Modus und Akkusparmodus haben Vorrang.
   - Class A: wirkt ab dem Zyklus nach dem Uplink, in dessen Empfangsfenster der Downlink kam.
   - Getestet auf BlackPill F411 (beelogger09) und BluePill F103 (beeloggerblue05).
3. **Sketch-ID-Fehler behoben** (Feld Aux2, siehe „Gefundene Fehler“; in älteren Notizen „Aux3“ genannt).
4. `Config_LORA.h`: `LOGGER_NAME` je Geräteblock.
5. **Firmware-Kennung** `l_ID` = **261106** (Form JJMMTT wie im Original, `STM32_LORA_260705.ino`). Steht in der Startzeile und geht alle 10 Uplinks an den Server. Bei jeder neuen Version auf das Datum setzen.

## Gefundene Fehler

| Fehler | Folge | Stand |
|---|---|---|
| `prep_payload()` prüft `LMIC.seqnoUp % 10`, bevor `seqnoUp` gesetzt ist | Sketch-ID (Aux2, 4 Byte) in **jedem** Uplink statt in jedem 10. → mehr Airtime | behoben: `LoRaSeqCounter % 10` |
| Backup-Register 4 `WeckIntervallMinuten` wird geschrieben, aber nirgends gelesen | toter Code, Register belegt | unverändert, nur dokumentiert |
| TTN-Vorgabe RX2 = SF12 (DR0), Firmware hört auf SF9 (DR3) | TTN schickt Antworten ins Leere und wiederholt MAC-Befehle („Downlink-Schleife“), bis zu 288 Downlinks/Tag | TTN auf Rx2 DR 3 stellen (siehe unten) |
| FCntDown in TTN > 65535; LMIC nutzt nach jedem Neustart nur 16 Bit | Downlink wird **ohne Meldung verworfen** | Zähler in TTN auf 0 setzen (siehe unten) |
| „Reset session and MAC state“ setzt nur den Zähler im Network Server zurück, nicht im Application Server | Downlink bleibt verworfen, obwohl die Konsole 0 zeigt | beide Zähler per API zurücksetzen |

## TTN-Einstellungen (ABP, LMIC dieser Firmware)

Die Firmware startet nach jedem Zyklus neu (`LowPower.shutdown()`) und beantwortet keine MAC-Befehle. TTN muss deshalb genau die Werte kennen, die die Firmware fest benutzt. Sonst hängt TTN an jeden Downlink MAC-Befehle („Downlink-Schleife“).

| Einstellung (Advanced MAC settings) | Wert | Grund |
|---|---|---|
| LoRaWAN-Version | 1.0.2, PHY 1.0.2 rev B | LMIC 1.5 |
| Frequenzplan | EU_863_870_TTN | |
| Frame counter width | 32 bit | |
| Rx1 delay / Desired | 1 / 1 s | LMIC-Vorgabe |
| Rx1 data rate offset / Desired | 0 / 0 | |
| **Rx2 data rate index / Desired** | **3 / 3** (SF9) | `LMIC.dn2Dr = DR_SF9` in `setup_LORA()` |
| Rx2 frequency / Desired | 869,525 / 869,525 MHz | |
| Resets frame counters | an | Zähler fängt nach Akku-ab bei 0 an |
| Factory preset frequencies | 868,1 / 868,3 / 868,5 / 867,1 … 867,9 MHz | muss alle Sendefrequenzen enthalten |
| ADR | aus | |

Links („aktuell“) und rechts („Desired“) müssen gleich sein. Beispiel aus der API: `docs/ttn/beelogger09_ns_einstellungen_abp.json`.

**Achtung:** Geänderte MAC-Einstellungen gelten bei einer laufenden ABP-Session erst nach „Reset session and MAC state“ in der TTN-Konsole. Bis dahin nutzt TTN die alten Werte. Beispiel beelogger09 am 06.11.: Settings RX2 = DR3, die laufende Session aber noch DR0 (SF12). Der Downlink kam trotzdem an, weil TTN ihn über RX1 schickte (SF7, gleiche Frequenz wie der Uplink). Mitschnitt: `docs/ttn/beispiele/2026-11-06_beelogger09_live_data_downlink_15min.json`.

**Downlink-Zähler (FCntDown):** Die Firmware beginnt nach jedem Neustart bei FCntDown 0 und sieht nur die unteren 16 Bit. Steht der Zähler in TTN über 65535, wird der Downlink **ohne Meldung verworfen**. Das passiert bei Geräten, die lange in der Downlink-Schleife hingen (beeloggerblue05: 224 647). Den Zähler führt TTN **zweimal**, im Network Server und im Application Server. „Reset session and MAC state“ setzt nur den ersten zurück. Abhilfe: beide per API auf 0 setzen (PUT auf `/api/v3/ns/…` und `/api/v3/as/…/devices/<dev>`, Feldmaske `session.last_n_f_cnt_down` bzw. `session.last_a_f_cnt_down`, API-Key mit „Edit devices“) oder das Gerät neu anlegen. `scripts/ttn-status.sh <app> <device>` zeigt beide Werte und warnt ab 65536.

Kanäle: `LORA_CHANNELS` in `Config_LORA.h` (1, 3 oder 8). Testgeräte: 3.

Downlink senden (TTN-Konsole → Gerät → Messaging → Downlink): FPort 10, Payload hex `0F` = 15 min, `00` = Firmware-Vorgabe.

## TTN Fair Use Policy

Pro Gerät im Schnitt **30 s Uplink-Airtime** und **höchstens 10 Downlinks** in 24 h.

| Intervall | Uplinks/Tag | Airtime/Tag bei SF7 (ca. 77 ms je Uplink) |
|---|---|---|
| 5 min | 288 | 22,2 s (74 %) |
| 10 min | 144 | 11,1 s |
| 15 min | 96 | 7,4 s |

- Bei 5 min und SF8 wäre die Grenze überschritten, deshalb ist SF7 fest eingestellt. Mehr Payload (z. B. weitere Waagen) braucht längere Intervalle.
- Ein Intervall-Downlink ist ein einzelner Downlink und liegt weit unter der Grenze.
- Ein Gerät in der Downlink-Schleife bekommt dagegen bis zu 288 Downlinks am Tag, fast das 30-Fache. Deshalb die TTN-Einstellungen oben genau übernehmen.

## Schlüssel

- `src/Config_LORA.h` (LoRaWAN-Schlüssel) und `src/Config_MULTI.h` (WLAN-/Server-Passwörter, Kalibrierung) stehen in `.gitignore`. Im Repo sind nur die Vorlagen `*.example`.
- Kompilierte Firmware (`*.bin`, `.pio/`, `backup/`) enthält die Schlüssel und ist ebenfalls ausgeschlossen.
- Die LMIC-Beispiele in `lib/…/examples/` enthalten den öffentlichen Testschlüssel der LMIC-Bibliothek, keine echten Schlüssel.

## Lizenz

Firmware: (C) R. Schick – beelogger.de, **GNU GPL v3 oder später**. Die Änderungen hier stehen unter derselben Lizenz ([`LICENSE`](LICENSE)). Geänderte Dateien tragen einen Änderungsvermerk mit Datum. Die Bibliotheken behalten ihre eigenen Lizenzen. Einzelheiten und wie die GPL-Bedingungen erfüllt werden: [`docs/lizenz.md`](docs/lizenz.md).

Kein offizielles Projekt von beelogger.de. Original: https://beelogger.de

## Stand

Siehe `docs/planung_otaa_downlink.md`, Abschnitte „Arbeitspakete“ und „Verlauf“.
Paket 1 (Grundlage) und Paket 2 (Downlink-Intervall, ABP) fertig. Paket 3 (OTAA) offen.
