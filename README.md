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
| `lib/` | beelogger-Bibliotheken 2026_04; geändert nur LMIC (`lib/beelogger_arduino-lmic-master/src/lmic/`: Join-Code an, Link-Check, `LMIC_restoreJoin`), siehe Diff |
| `orginal_firmware_bibliotheken/` | Originale von beelogger.de zum Vergleich (Sketche 202610, Bibliotheken 2026_04, WLAN/LTE 230928) |
| `platformio.ini` | Boards BluePill F103CB, BlackPill F411CE, BlackPill F401CC |
| `docs/flashen.md` | Bauen, Flashen über FTDI (stm32loader), Gerätetabelle |
| `docs/ttn_anleitung.md` | TTN Schritt für Schritt: Application, Gerät ABP/OTAA, Prüfen, Downlink, Fehlersuche, API-Keys |
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
4. **`src/Config_LORA.h` ausfüllen:** `LOGGER_NAME`; Variante wählen mit `#define LORA_OTAA` (siehe „ABP oder OTAA“). ABP (`0`): `DEVADDR`, `NWKSKEY`, `APPSKEY` aus der TTN-Konsole. OTAA (`1`): `DEVEUI`, `APPEUI` (JoinEUI), `APPKEY` – DevEUI und JoinEUI in LSB-Reihenfolge, AppKey in MSB (in der TTN-Konsole umschaltbar). Sendeintervalle `WeckIntervall_*`. `LORA_CHANNELS` (1, 3 oder 8) muss zu den Frequenzen in TTN passen.
5. **`src/Config_MULTI.h` ausfüllen:** Sensoren, Waagen-Kalibrierung, wie bei der Original-Firmware.
6. **Board wählen** (unten in der PlatformIO-Leiste oder per Befehl):
   - BluePill (STM32F103, „Blue“): `bluepill_f103c8`
   - BlackPill (STM32F411, „Black“): `blackpill_f411ce`, ältere BlackPill: `blackpill_f401cc`
7. **Bauen:** Häkchen in der PlatformIO-Leiste oder `pio run -e blackpill_f411ce`. Ergebnis: `.pio/build/<board>/firmware.bin`.
8. **Flashen** über FTDI-Adapter mit stm32loader: siehe [`docs/flashen.md`](docs/flashen.md). Vorher an der Startzeile prüfen, welches Gerät am Kabel hängt.
9. **TTN einstellen** wie unter „TTN-Einstellungen“, sonst kommen Downlinks nicht an.

ABP oder OTAA per Schalter `#define LORA_OTAA` (0/1) im Geräteblock von `Config_LORA.h`, auf BluePill und BlackPill. BluePill: Firmware muss unter 63 KB bleiben (Session-Seite), Build mit OTAA 56 176 B.

Hinweise:
- STM32-Core 2.6.0 (`framework-arduinoststm32@~4.20600`), in `platformio.ini` festgelegt.
- BluePill: Board `bluepill_f103c8_128k` mit `-DARDUINO_BLUEPILL_F103CB` (die Firmware braucht 128 KB).
- Flash-Belegung (Paket 2): F103CB 53 884 B, F411CE 53 176 B.
- `pio run -t upload` geht beim F411 nicht (stm32flash kennt die Chip-ID 0x431 nicht), deshalb stm32loader.

## Eine Firmware, vier Varianten

| | ABP (`LORA_OTAA 0`) | OTAA (`LORA_OTAA 1`) |
|---|---|---|
| **BluePill** F103 (`bluepill_f103c8`) | läuft (beeloggerblue05) | läuft (beelogger011, seit 07.11.) |
| **BlackPill** F411/F401 (`blackpill_f411ce`, `blackpill_f401cc`) | läuft (beelogger09) | läuft (beelogger08) |

Board = PlatformIO-Umgebung, ABP/OTAA = Schalter im Geräteblock von `Config_LORA.h`. Sonst ist der Code gleich, Korrekturen gelten für alle Varianten.

## ABP oder OTAA

**ABP** (Activation by Personalization): DevAddr und Sitzungsschlüssel stehen fest in der Firmware. Das Gerät sendet sofort.
**OTAA** (Over-the-Air Activation): In der Firmware steht nur ein Stammschlüssel (AppKey). Beim Start meldet sich das Gerät an („Join“), TTN vergibt DevAddr und Sitzungsschlüssel neu.

| | ABP | OTAA |
|---|---|---|
| Start | sendet sofort | Join beim ersten Start und nach Stromausfall: ein Downlink, ca. 8 s länger wach (gemessen: 19 s statt sonst ca. 18 s mit 4 Waagen) |
| Gateway beim Start | egal | muss erreichbar sein, sonst kein Join und kein Uplink |
| Schlüssel | Sitzungsschlüssel fest, gelten für immer | bei jedem Join neu; im Gerät nur AppKey |
| Zähler nach Akku-ab | fängt bei 0 an → in TTN „Resets frame counters“ nötig (schwächt den Schutz gegen wiederholte Nachrichten) | neuer Join, neue Session – kein Zähler-Reset in TTN nötig |
| TTN-Einstellungen (Rx1/Rx2) | müssen genau zur Firmware passen, sonst Downlink-Schleife; Änderung erst nach „Reset session and MAC state“ | kommen mit dem Join-Accept ins Gerät |
| Netzausfall erkennen | nein | ja: 1 Link-Check pro Tag, nach 3 Tagen ohne Antwort Neu-Join (kostet 1 Downlink/Tag von 10 erlaubten) |
| Gerät in andere TTN-App umziehen | neu flashen | in TTN neu anlegen, Join |
| Boards | Blue und Black | Blue und Black |
| Strom | – | gleich (beelogger08: Schlaf 18,1 µA, wach 24–34 mA, wie mit alter FW) |

**Warum beide:** Die vorhandenen Feldgeräte laufen mit ABP und sollen nach dem Update ohne Neuanlage in TTN weiterlaufen. Neue Geräte sollen OTAA nutzen: sicherer, weniger Fehlerquellen bei den TTN-Einstellungen, und ein Netzausfall wird erkannt. Beides aus einer Codebasis, damit Korrekturen nur einmal nötig sind.

OTAA-Gerät in TTN anlegen: LoRaWAN 1.0.2, RP 1.0.2 rev B, Frequenzplan EU_863_870_TTN, ADR aus. Die Session (DevAddr, Nonces, Rx-Werte, 4 × 32 Bit) liegt bei der BlackPill in RTC-Backup-Registern 10–13, beim BluePill in einer Flash-Seite bei 0x0800FC00 (63 KB, auch beim C8 vorhanden; geschrieben nur bei Änderung: Join, 1 Link-Check/Tag, Löschen). Sie übersteht den Tiefschlaf; die Sitzungsschlüssel werden daraus neu berechnet und nicht gespeichert. Neu-Join nach Stromausfall (POR), unbekanntem Frame-Zähler, FCnt ≥ 16000 (vor dem Überlauf des 14-Bit-Zählers), ungültiger Prüfsumme der Session oder 3 Tagen ohne Link-Check-Antwort.

Mitschnitt OTAA (beelogger08, BlackPill, nach Akku ab/an):
```
Name: beelogger08 UID: 3534393233375105006E0030 DevAddr: 00000000
POR
...
LoRa-frame counter: 0
OTAA: Join
546276: Joining
1045852: Joined DevAddr: 638256495        (= 0x260B056F)
1180909: TX done!
 Weckzeit 01.01.2022 00:04:59
  LoRaSeq_Nr:1 Interval: 5min  DL: 0min  t-on: 19  sw: 0 0
```
Nach einem normalen Neustart (Reset, Tiefschlaf) zeigt die Startzeile die gespeicherte DevAddr, und es gibt keinen Join.

TTN-Live-Daten als Beispiel: OTAA mit zwei Joins (Join-Request, Join-Accept, erster Uplink FCnt 0) in `docs/ttn/beispiele/2026-11-06_beelogger08_otaa_live_data_join.json` (TTN-Ereignis-API, ohne Schlüssel, Standorte und Gateway-Token). ABP: `docs/ttn/beispiele/` – z. B. `2026-11-06_beelogger09_live_data_2h.json` (Uplinks mit dekodierter Payload) und `…_downlink_15min.json` (Intervall-Downlink). Payload-Decoder: `docs/ttn/payload_decoder_ttn.js`.

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
6. **OTAA** (`#define LORA_OTAA 1`, Werte `DEVEUI`, `APPEUI` = JoinEUI, beide LSB, `APPKEY` MSB; TTN: LoRaWAN 1.0.2, RP 1.0.2 rev B):
   - Join beim ersten Versand. Gespeichert werden nur DevAddr, NetID, AppNonce, DevNonce, Rx1-Delay und Rx2-DR (RTC-Backup-Register 10–13, mit Version und Prüfsumme). Die Session-Schlüssel rechnet der Logger nach jedem Aufwachen aus dem AppKey neu (`LMIC_restoreJoin`).
   - Neu-Join: nach Power-On-Reset (Akku ab), bei ungültigem Datum, ab FCnt 16000 und nach 3 Tagen ohne Antwort auf den täglichen LinkCheckReq (1 Downlink/Tag).
   - Join-Versuch höchstens 60 s pro Zyklus; ohne Erfolg neuer Versuch im nächsten Zyklus.
   - LMIC: Join-Code eingeschaltet (`DISABLE_JOIN` aus), LinkCheckReq/-Ans ergänzt. ABP-Geräte joinen nie (DevAddr ist gesetzt), Build ca. 0,7 KB größer.
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

Gilt für ABP. Bei OTAA kommen Rx1-Delay und Rx2 mit dem Join; Frequenzplan, LoRaWAN-Version und ADR aus wie unten.

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
| ADR | aus (Disabled) | |
| Status count / time periodicity | 0 / 0 | Vorgabe 200 / 86400: TTN fragt den Gerätestatus per MAC-Befehl ab, Firmware antwortet nicht |

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
Paket 1 (Grundlage) und Paket 2 (Downlink-Intervall, ABP) fertig. Paket 3 (OTAA): BlackPill läuft und ist abgenommen (beelogger08: Join, Neustart ohne Join, Neu-Join nach Stromausfall, Strom gleich). BluePill abgenommen 07.11. (beelogger011: Join, Wiederanlauf aus dem Flash ohne Join).
