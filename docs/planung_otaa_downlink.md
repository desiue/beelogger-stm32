# Planung: OTAA und Fernsteuerung per Downlink

Stand: 06.11.2026. Sammelt Entscheidungen, Fakten und offene Fragen aus der Planung.
Keine Schlüssel in diese Datei.

## Ziel

1. Sendeintervall aus der Ferne über TTN-Downlink einstellen (5, 10, 15 ... Minuten).
2. Aktivierung wahlweise **ABP** (wie bisher) oder **OTAA**, umschaltbar per `#define LORA_OTAA` in `Config_LORA.h`.
3. Geräte-Kennung auf der seriellen Konsole: beim Start Name (`LOGGER_NAME` in `Config_LORA.h`), 96-Bit-Seriennummer des STM32 (UID), Chip, Firmware-Stand, DevAddr bzw. DevEUI. Damit ist immer klar, welcher Logger angeschlossen ist.
4. Zurückgestellt: Waagenkonfiguration (Tara, Kalibrierfaktor) per Downlink.
5. Nicht geplant: ADR (wäre für Helium nötig, siehe Abschnitt Helium). Erst nach OTAA neu bewerten.

Zielhardware: BluePill (F103) **und** BlackPill (F401/F411), eine Firmware. Testgeräte: 2 × Blue, 3 × Black.
Alte Firmware wird nicht gesichert (Entscheidung Nutzer, 06.11.2026): Es wird jedes Mal neu gebaut, die Zuordnung geht über TTN.

## Entscheidungen

| Datum | Entscheidung | Grund |
|---|---|---|
| 06.11.2026 | Eine Codebasis, ABP und OTAA per Schalter | Downlink-Empfang funktioniert mit beiden; bestehende ABP-Logger laufen unverändert weiter |
| 06.11.2026 | Reihenfolge: erst Downlink-Intervall (mit ABP testbar), dann OTAA | Kleiner Schritt zuerst, sofort am Testgerät prüfbar |
| 06.11.2026 | Waagenkonfiguration per Downlink zurückgestellt | Braucht Befehlsformat mit Prüfsumme und Rücklesen |
| 06.11.2026 | Kein externes EEPROM (nicht bestückt) | Hinweis Nutzer |
| 06.11.2026 | OTAA-Session: F1 im Flash, nur beim Join geschrieben; F4 in freien Backup-Registern | siehe Speicher |
| 06.11.2026 | Paket 3 mit 1 Blue + 1 Black, beide neu als OTAA-Gerät in TTN | beide Speicherwege (Flash F1, Backup-Register F4) testen |
| 06.11.2026 | 4 Testgeräte: ABP = beelogger09 (Black, 260BD691), beeloggerblue05 (Blue, 260B6573, TTN-Gerät eui-70b3d57ed005f5e0); OTAA = beelogger011 (Blue, 260BF6FE, F103C8 meldet 128 KB), beelogger08 (Black, 260B2D40, vorher Strom/Wachzeit messen) | Nutzerentscheid |
| 06.11.2026 | Neu-Join: ohne gültige Session oder nach Stromausfall (FCnt-Register leer); dazu 1 Link-Check pro Tag, 3 ohne Antwort in Folge → Neu-Join | Netzausfall erkennen, kostet nur 1 von 10 Downlinks pro Tag |

## Fakten aus dem Code (Firmware STM32_LORA_260705, Bibliotheken 2026_04)

- Hauptsketch: `src/STM32_LORA_260705.ino`, LoRa-Teil in `src/STM32_LORA.ino`.
- ABP: `LMIC_setSession()` in `setup_LORA()`, Schlüssel in `Config_LORA.h`.
- Nach jedem Zyklus `LowPower.shutdown()` (STM32-Standby) = kompletter Neustart beim Aufwachen.
- LMIC: alte IBM-Version 1.5.0+arduino-2, Join abgeschaltet per `DISABLE_JOIN` in `lib/beelogger_arduino-lmic-master/src/lmic/config.h:41`.
- DevNonce wird in LMIC zufällig erzeugt (`os_getRndU2()` in `LMIC_reset`).
- Empfangene Downlinks werden ignoriert (Auswertung in `onEvent()` auskommentiert).
- Debug-Ausgabe: `Serial` (BluePill, USART1, PA9 = TX) bzw. `Serial1` (BlackPill), **9600 Baud** (`STM32_beelogger.h:32`). `platformio.ini` hat fälschlich `monitor_speed = 115200`.

## Fakten aus dem Wiki (QNAP, `projekte:beelogger_doku`, `projekte:beelogger`)

- 03_firmware: Bekannter Fehler, auch in 260705: `prep_payload()` prüft `LMIC.seqnoUp % 10 == 0`, bevor `seqnoUp` gesetzt wird → Aux3 (Sketch-ID) geht in jeder Nachricht mit. Korrektur: `LoRaSeqCounter % 10 == 0`. **Beim Umbau mit beheben.**
- 03_firmware: Backup-Register 0 nicht nutzbar; Register speichern nur 16 Bit (Lib nimmt 32-Bit-Werte).
- 03_firmware: Flashen über FTDI an PA9/PA10 mit STM32CubeProgrammer, BOOT0 = 1 zum Laden.
- 03_firmware: `myDEBUG 1` im Dauerbetrieb auf 0 setzen (Strom, Flash).
- 06/06a: Downlink-Schleife durch MAC-Befehle; Custom MAC settings bisher nur bei beelogger08 gesetzt, beeloggerblue01 noch nicht.
- 07: Eigener Netzwerkserver `lns_udp.py` sendet **keine** Downlinks. Mit ihm ginge die Fernsteuerung nicht; wäre dort nachzurüsten.
- `projekte:beelogger` Abschnitt 3 sagt „OTAA (STM32)“. Laut Code nutzen die STM32-Logger **ABP**. Wiki-Angabe korrigieren.
- TTN-App des Testgeräts: `beeloggerblue01` („Testumgebung Black“).

## Speicher

| Speicher | Übersteht Deep Sleep | Übersteht Stromausfall | Abnutzung |
|---|---|---|---|
| RAM | nein (Standby löscht) | nein | keine |
| RTC-Backup-Register | ja | nein (nur mit VBAT-Batterie) | keine |
| Interner Flash | ja | ja | ca. 10 000 Löschzyklen pro Seite |

Belegung der Backup-Register (`save_data_to_ram()` in `STM32_LORA_utility.ino`):

| Register | Inhalt |
|---|---|
| 0 | RTC-Bibliothek |
| 1, 2 (F1) / 6, 7 (F4) | LetztesGewicht[0], [1] |
| 3 | LoRa-Frame-Counter |
| 4 | time_on, WeckIntervallMinuten |
| 5 | akku_save, stuck_switch |
| 6, 7 (F1) | RTC-Bibliothek (Datum) |
| 8, 9 | LetztesGewicht[2], [3] |

- F103 (BluePill): nur 10 × 16 Bit, **alle belegt**.
- F401/F411 (BlackPill): 20 × 32 Bit, Register 10 bis 19 frei.

### OTAA-Session speichern

- Gespeichert werden nur 12 Byte: DevAddr (4), AppNonce (3), NetID (3), DevNonce (2). AppSKey und NwkSKey werden nach jedem Aufwachen aus AppKey und diesen Werten neu berechnet.
- Dazu eine Prüfsumme. Ungültig oder fehlend → neuer Join.
- FCnt bleibt im Backup-Register 3.
- Stromausfall löscht die Backup-Register → Logger erkennt das und joint neu. Damit braucht TTN „Resets frame counters“ nicht mehr (bei ABP ist das ein Replay-Risiko).
- Flash wird nur beim Join beschrieben (erster Start, Stromausfall, Netz antwortet lange nicht). Ein Join pro Tag = ca. 27 Jahre bis 10 000 Zyklen.
- Nur schreiben, wenn die Akkuspannung ausreicht.

## Downlink: Sendeintervall

- Class A: Downlink kommt nur im RX1/RX2-Fenster direkt nach einem Uplink. Wirkt also einen Zyklus später.
- Vorschlag: FPort 10, 1 Byte = Intervall in Minuten, gültig 5 bis 180, sonst ignorieren.
- Speicherort: Backup-Register (F4) bzw. Flash (F1), damit der Wert den Deep Sleep übersteht. **Offen**, siehe unten.
- Vorrang: Akkusparmodus (`akku_save`, `AlternativIntervallMinuten`) schlägt den Fernwert. Fernwert ersetzt das Sommer/Winter-Intervall. Wert 0 = zurück auf Firmware-Vorgabe.
- TTN Fair Use: max. 10 Downlinks pro Tag, reicht für gelegentliches Umstellen.

## Zusammenhang mit der TTN-Downlink-Schleife (Wiki 06/06a)

Die Schleife entsteht, weil der Logger nach jedem Zyklus neu startet und MAC-Antworten nie sendet. Das bleibt auch mit OTAA so. Die TTN-Einstellungen aus Wiki `projekte:beelogger_doku:06_ttn` Abschnitt 6 sind auch für OTAA-Geräte nötig. Bei OTAA kommen die Kanäle zusätzlich mit dem Join-Accept (CFList).

## Testgerät

- Seit 06.11. Sitzung 1: **beelogger09** (260BD691, BlackPill F411CE, UID 0066-0041-33375106-35343932). Die Angaben unten stammen von diesem Board, nicht von beeloggerblue01. FTDI FT232R an `/dev/ttyUSB0`.
- 06.11.2026: Zwei Mitschnitte (115200 und 9600 Baud, je 3 min) leer. Dritter Mitschnitt (9600) nach Reset ok:
  - **Chip: STM32F411CE (BlackPill)**, nicht BluePill → Backup-Register 10 bis 19 frei, 512 KB Flash.
  - Sketch-ID im Debug: `STM32_LORA_` + `1` → geflashte Firmware ist **nicht** 260705 (dort `l_ID = 260705`). Stand unbekannt.
  - RTC-Datum 09.01.2022 (Vorgabe nach Power-On-Reset), Akku 3,74 V, Intervall 5 min, `sw: 0 1`.
  - Im mitgeschnittenen Zyklus kein `LoRa:`-Block, also nichts gesendet. Grund offen (Service-Schalter?).
  - Weckzeit 03:24:59 bei Uhrzeit 01:28:21 passt nicht zu 5 min. **Wahrscheinliche Ursache:** gemessen 3,74 V < `VMinimum` 3,75 V (`Config_MULTI.h:92`) → kein Versand, Intervall 120 min (`AlternativIntervallMinuten`). Multimeter: Akku hatte 3,75 V → **Messung des Loggers stimmt**, Ursache war der leere Akku. Akku gegen vollen (~4,2 V) getauscht.
  - Mitschnitt mit vollem Akku (4,20 V, Solar 5,41 V): erst mehrere Neustarts (POR/RST beim Akkutausch und Reset), dann **ein kompletter Zyklus**: Sensoren, `LoRa:`-Block, 22 Byte Payload, `TX!`, FCnt 1, Weckzeit +3 min auf die nächste 5-Minuten-Marke. **Testgerät funktioniert als Ausgangspunkt.**
  - Auffällig, aber unkritisch: Waage 4 zeigt 590 kg (kein Wägezelle an Kanal; Waagenzahl in der Config), nach RST steht `Interval: 0min` im Backup-Register.

## Arbeitspakete (je eine eigene Sitzung)

Jede Sitzung startet mit dieser Datei und endet mit einem Eintrag unter „Verlauf“.

| Nr | Paket | Ergebnis / Abnahme |
|---|---|---|
| 0 | Planung (diese Datei) | offene Fragen entschieden |
| 1 | Grundlage: Build der unveränderten 260705 für Blue und Black, Flash-Größe/-Belegung, `monitor_speed` 9600, Startzeile mit Name + UID + Chip + FW + DevAddr, flashen aufs Testgerät | Ein Zyklus mit `LoRa:`-Block im Log und Uplink in TTN; Build-Größen notiert |
| 2 | Downlink-Empfang + Sendeintervall (ABP), Aux3-Fehler beheben | Downlink aus TTN-Konsole ändert Intervall am Testgerät, Logausgabe |
| 3 | OTAA mit Schalter `LORA_OTAA`, Session im Flash (F1) / Backup-Register (F4), Neu-Join-Regel | Join, Neustart ohne Neu-Join, Akku ab → Neu-Join |
| 4 | Doku: Wiki aktualisieren (03, 06, Abschnitt 3 der Projektseite). **TTN-Anleitung mit Screenshots je Variante** (ABP neu, ABP Altgerät umstellen, OTAA): Gerät anlegen, Advanced MAC settings (Rx1 delay 1/1, Rx2 DR 3/3, Frequenzen, Resets frame counters, ADR aus), „Reset session and MAC state“, Downlink FPort 10 senden, Prüfung mit `scripts/ttn-status.sh`. Für Nutzer von beelogger, ins Repo (`docs/ttn/anleitung_*.md` + Bilder) und ins Wiki | Anleitung im Repo, Wiki-Seiten geändert |
| später | Waagenkonfiguration per Downlink | – |

## Verlauf

- 06.11.2026, Sitzung 0: Ziele, Speicherfrage, Reihenfolge geklärt. Serielle Konsole noch stumm.
- 06.11.2026, Sitzung 1 (Paket 1):
  - BluePill-Build scheiterte am `#error "STM32F103C8 not enough memory"`: Firmware will `ARDUINO_BLUEPILL_F103CB`. `platformio.ini`: Board `bluepill_f103c8_128k` + `board_build.extra_flags` mit `-DARDUINO_BLUEPILL_F103CB` (wie Arduino-IDE „BluePill F103CB (or C8 with 128k)“). Env-Name `bluepill_f103c8` bleibt.
  - `monitor_speed` überall 9600.
  - Build 260705 unverändert: F103CB 53 612 B Flash (41 %) / 2 316 B RAM; F411CE 53 016 B (10 %) / 2 372 B; F401CC 52 704 B (20 %) / 2 372 B.
  - Startzeile nach der Chip-Zeile in `System_Init()` (`STM32_LORA_utility.ino`): `Name: <LOGGER_NAME> UID: <24 Hex> DevAddr: <8 Hex>`. `LOGGER_NAME` im aktiven Block von `Config_LORA.h` (fehlt er, steht `?`). Kosten ca. 110 B Flash.
  - Mit Startzeile: F103CB 53 724 B, F411CE 53 128 B, F401CC 52 816 B. Platz für Paket 2/3 reichlich, auch auf F103CB (77 KB frei).
  - **Flashen:** `pio -t upload` scheitert am F411 (stm32flash kennt 0x431 nicht), wie schon am 29.10. Lösung `stm32loader`, Anleitung jetzt in `docs/flashen.md`.
  - **Panne:** Testboard für beelogger07 (260BEB70) gehalten und so geflasht. TTN zeigte 14:36 zwei Uplinks unter 260BEB70: echter beelogger07 (FCnt 8203, Aux3 1, 12,8 °C) und Testboard (FCnt 3, Aux3 260705, 23,9 °C). Testboard ist **beelogger09** (260BD691, Block aus lokalem Konfig-Archiv 20260303 übernommen). Falsche Werte bei beelogger07 ab 14:36 auf dem Server löschen.
  - Testgerät in Abschnitt „Testgerät“ ist damit beelogger09, nicht beeloggerblue01.
  - 14:44 UTC (15:44 Ortszeit): beelogger09 mit 260705 geflasht (stm32loader, Verify OK). TTN-App `beelogger09` nimmt Uplink an: FCnt 4 (Zähler überlebte Flash), Aux3 260705, VBatt 4,20 V, TempOut 24,1 °C, RSSI −69. **Abnahme Uplink Paket 1 erfüllt.**
  - Startzeile am Gerät nach Reset: `Name: beelogger09 UID: 353439323337510600410066 DevAddr: 260BD691` (UID-Wörter w2 w1 w0; stm32loader zeigt dieselbe UID umgekehrt als 0066-0041-33375106-35343932). **Paket 1 abgeschlossen.**
  - Rest: `Config_MULTI.h` (Waagen-Kalibrierung) steht noch auf blue01, für den Schreibtisch egal, vor Feldeinsatz umstellen. Falsche Werte bei beelogger07 ab 14:36 UTC auf dem Server löschen (Nutzer).

## Rückmeldung eines anderen Nutzers (06.11.2026)

- Alte beelogger universal (ATmega, LoRa-Chip + Level-Shifter) und beeloggerSMD mit LoRa-Shield, TTN; ein Gerät im Helium-Netz mit eigenem OTAA+ADR-Sketch.
- **Unsere STM32-Firmware läuft auf dessen Geräten nicht.** Er ist für dieses Projekt kein Ziel, aber für die Wiki-Anleitung 06/06a.
- Problem nach der Umstellung aus 06a: Gateway empfängt, App bekommt nichts. Logger sendet `f_cnt 62980`. Genau der in 06a (Abschnitt „Voraussetzung Frame Counter“, 6.1, 11) beschriebene Fall: Nach „Reset session and MAC state“ erwartet TTN den Zähler bei 0, Lücke > 16 384 → Uplinks verworfen. „Resets frame counters“ hilft nicht, es erlaubt nur den Sprung zurück auf 0, keine große Lücke nach vorn.
- (Das Frame-Counter-Problem ist laut Nutzer gelöst, steht im Wiki.)

### Helium (Bewertung 06.11.2026)

- Helium kennt nur OTAA, der NS steuert per ADR. Mit Paket 3 (OTAA + gespeicherte Session) wäre der Join bei Helium grundsätzlich möglich.
- **Hindernis:** Wie bei TTN startet der Logger nach jedem Zyklus neu und vergisst MAC-Befehle. Bei TTN lässt sich das per Custom MAC settings abstellen, bei Helium nicht so einfach. Folge: ständige Downlinks, und bei Helium kostet jedes Paket Data Credits.
- Für echtes ADR müsste die Firmware zusätzlich den MAC-Zustand (Datenrate, Sendeleistung, Kanalmaske, ausstehende Antworten) über den Standby retten. Auf F4 passt das in die Backup-Register, auf F1 in den Flash (nur bei Änderung schreiben).
- **Entscheidung:** Jetzt nicht bauen. Paket 3 so anlegen, dass der Speicherblock eine Versionsnummer hat und später um den MAC-Zustand erweitert werden kann. Testen ginge nur mit Helium-Abdeckung und Konto. Nutzer selbst nutzt Helium nicht, ein anderer Nutzer hat (noch) keine STM32-Logger.

- 06.11.2026, Sitzung 2 (Paket 2):
  - `WeckIntervallMinuten` (Register 4) wird gespeichert, aber nie benutzt; alte Firmware schreibt dort 5. Darum **nicht** als Fernwert genutzt (Feld-Logger liefen nach Update sonst auf 5 min).
  - **Entscheidung geändert:** Fernwert `FernIntervall` in Register 5, Bit 9–14, als Minuten/5 (Weckzeit rastet ohnehin auf 5 min). Gilt für F1 und F4, kein Flash nötig. Stromausfall → 0 → Firmware-Vorgabe.
  - Downlink in `onEvent()` (`STM32_LORA.ino`): FPort 10, 1 Byte, 0 oder 5–180, sonst ignoriert. Wirkt in `Alarm_konfigurieren()` statt Tag/Nacht/Winter; Service-Modus und Akkusparmodus haben Vorrang. Debug: `DL Port 10: <n>`, in der Infozeile `DL: <n>min`.
  - Aux3-Fehler behoben: `LoRaSeqCounter % 10`.
  - Build: F103CB 53 884 B, F411CE 53 176 B.
  - TTN-Gerät beelogger09 braucht Rx1 delay 1 s und RX2 SF9 (DR3) in den MAC-Einstellungen (LMIC-ABP nimmt beides fest an).
  - TTN beelogger09: Rx2 data rate index und Desired von 0 auf 3 gestellt (Firmware hört RX2 auf SF9). Rx1 delay 1/1 s war richtig. 8 Factory-preset-Frequenzen, Firmware sendet auf 3 (`LORA_CHANNELS 3`) – passt.
  - 15:11 UTC geflasht (stm32loader, Verify OK). Startzeile bestätigt beelogger09. Uplink FCnt 13 ohne Aux3 → Fix wirkt.
  - Downlink per TTN-API eingereiht: FPort 10, `0F`. Nach Uplink FCnt 14 im Log `DL Port 10: 15`, danach `DL: 15min`, Weckzeit 00:49:59 → 01:04:59 (RTC-Zeit) = 15 min. **Abnahme Paket 2 erfüllt.** Log: `docs/logs/2026-11-06_beelogger09_downlink_15min.log`.
  - Repo `beelogger-stm32` (privat, für beelogger-Entwickler) lokal angelegt, Push wartet auf `gh auth login`.
  - Rückstellen `00` (15:35 UTC eingereiht): Uplinks 15:14 → 15:29 → 15:44 (15 min), dann 15:49, 15:54, 15:59 (5 min). **Rückstellen funktioniert.**
  - BluePill **beeloggerblue05** (TTN-App `beeloggerblue05`, DevAddr 260B6573, F103CB 128 KB, UID 5BB6-1A5A-00000000-30003F2A, Reset-Taster defekt → R gegen G): 16:01 UTC mit Paket-2-Firmware geflasht. Startzeile ok, `TX done!` FCnt 1. `Interval: 5min` (Altwert der alten Firmware in Register 4), `DL: 0min` → Altwert stört wie geplant nicht.
  - Serielles Log dauerhaft: `scripts/seriell-log.sh` → `~/beelogger-logs/seriell.log` (vor dem Flashen `stop`).
  - Downlink-Test blue05 16:06 UTC: TTN sendete um 16:11 (RX1), Gerät verwarf ihn ohne Meldung. Ursache: FCntDown in TTN 224647 (Altlast der Downlink-Schleife seit 2023); LMIC startet nach jedem Aufwachen mit FCntDown 0 und ergänzt nur 16 Bit → MIC falsch. Abhilfe: „Reset session and MAC state“ in TTN. Für OTAA (Paket 3) beachten: FCntDown im gespeicherten Session-Block mitführen oder nach Join bei 0 lassen.
  - 16:19 UTC Nutzer: RX2 3/3 und „Reset session and MAC state“ bei beiden Geräten. `scripts/ttn-status.sh` zeigt danach FCntDown 0, RX2 3/3.
  - 16:22 UTC `0F` an blue05 erneut: TTN sendet um 16:26, **wieder nicht empfangen**. FCntDown springt auf **224648**: Der Reset setzt nur den Network Server zurück, der **Application Server** (verschlüsselt den Downlink) zählt mit seinem alten Stand weiter. beelogger09 betrifft das nicht (Zähler klein).
  - Bei der Übergabe: Paket 2 auf F411 abgenommen (15 min und Rückstellen 00), auf F103 Uplink ok, Downlink wegen TTN-Zähler offen.
  - Doku/Repo: öffentlich als github.com/desiue/beelogger-stm32 (GPL v3, `docs/lizenz.md`); Config_MULTI.h mit WLAN-/Server-Passwörtern aus dem Repo genommen, Repo neu angelegt (ein Commit). Private Angaben in `docs/privat/` (lokal). TTN-Export der Konsole kann vollständige API-Keys enthalten (Ereignis `application.api-key.create`) → vor dem Ablegen filtern.

- 06.11.2026, Sitzung 3 (Paket 3, Teil BlackPill):
  - LMIC: `DISABLE_JOIN` aus (`config.h`), `LMIC_restoreJoin()` (Schlüssel per `aes_sessKeys` aus AppKey + gespeicherten Werten), `LMIC.lchkReq` für LinkCheckReq/-Ans (`lmic.c`, `lmic.h`). ABP joint nie (DevAddr gesetzt); leere Callbacks `os_getDevKey` usw. für ABP.
  - Sketch: `LORA_OTAA` (Vorgabe 0). Session-Block Backup-Register 10–13 (F4): DevAddr | NetID + RxDelay/Rx2-DR | AppNonce + Version | DevNonce, Link-Check-Tag, Fehlversuche, Prüfsumme (über Bit 0–15 von Reg. 13 und Reg. 10–12). FCntDown wird nicht gespeichert (wie ABP, 16-Bit-Erweiterung ab 0).
  - Neu-Join: Power-On-Reset, ungültiges Datum, FCnt ≥ 16000, 3 Tage ohne LinkCheckAns. Join-Timeout 60 s.
  - F1: `#error` bei `LORA_OTAA` – Flash-Speicher folgt (beelogger011).
  - Build: F103CB ABP 55 172 B, F411CE ABP 54 440 B, F411CE OTAA 55 256 B.
  - TTN: Gerät `beelogger08-otaa` per API angelegt (DevEUI von TTN 70B3D57ED0079D78, JoinEUI 0, AppKey zufällig, direkt in `Config_LORA.h`; LoRaWAN 1.0.2, RP 1.0.2 rev B, Rx1 delay 1, Rx2 DR 3, ADR aus, kein FCnt-Reset). Skript: `scripts/ttn-otaa-anlegen.py <app> <device_id> <LOGGER_NAME> src/Config_LORA.h` (Konto-Key).
  - beelogger08 geflasht (vier Fehlversuche: BOOT zu früh losgelassen, siehe `flashen.md`). 21:26:29 UTC Join, erster Uplink FCnt 0. 21:28 UTC Neustart per Reset **ohne Neu-Join**, Uplink FCnt 1 in derselben Session → Schlüssel-Neuberechnung stimmt. **Abnahme Paket 3 auf F4: Join und Neustart ohne Join erfüllt; Akku-ab → Neu-Join noch testen.**
  - Zweitmeinung (`ask review`) zum Diff: nur Fehlalarme.

## Offene Punkte (Stand 06.11.2026, Ende Sitzung 3)

- [x] beelogger08: Akku kurz ab → Startzeile DevAddr 0, neuer Join (neue DevAddr in TTN). 06.11. 22:32: Join nach 8 s, DevAddr 260B056F, FCnt 0 angenommen, Wachzeit mit Join 19 s. (3,96 V kam vom Multimeter in Reihe.)
- [ ] beelogger08: Link-Check am nächsten Tag im Log (`Link-Check Fehlversuche: 0`).
- [x] beelogger08: Strom mit neuer FW (261106 OTAA) gemessen 06.11.: Schlaf 18,09 µA, wach 24–34 mA – gleich wie alte FW. Wachzeit normaler Zyklus noch aus `t-on` ablesen (mit Join 19 s).
- [ ] beelogger08: Spannungsanzeige gegen Multimeter prüfen (Kalibrierung vorerst Black-Wert 4120 mV / 1650 Bit).
- [ ] beelogger08: altes ABP-Gerät `beelogger08` in TTN löschen, wenn OTAA stabil läuft.
- [x] Paket 3 auf F1 (beelogger011), 07.11.: Session im Flash 0x0800FC00 (63 KB), nur bei Änderung geschrieben; TTN-Gerät beelogger011-otaa per Skript; Join 01:18:34 (260B21D9), Wiederanlauf 01:23 ohne Join. Akkuspannung nicht extra geprüft (Versand nur über VMinimum).
- [ ] beelogger011: Link-Check am nächsten Tag (Flash-Schreiben 1x/Tag), altes ABP-Gerät beelogger011 löschen, wenn stabil.
- [x] ADR: Firmware setzt ADR-Bit im Uplink (`"adr": true`, LMIC-Vorgabe, kein `LMIC_setAdrMode(0)`); TTN-Einstellung ADR aus verhindert ADR-Befehle. 07.11.: Kein Handlungsbedarf, Gerät bleibt fest auf SF7, ADR-Rückfall der LMIC ist durch `LMIC_setLinkCheckMode(0)` bei jedem Aufwachen aus. Zeile höchstens beim nächsten Flashen.
- [x] `scripts/ttn-status.sh` stürzt bei Join-Einträgen in recent_uplinks ab (kein mac_payload). 07.11. behoben (e398458).

### Aus Sitzung 2

**Paket 2 (Rest)**
- [x] blue05: AS- und NS-Downlink-Zähler per API auf 0 gesetzt (PUT `/api/v3/as/…` und `/api/v3/ns/…` mit Feldmaske `session.last_a_f_cnt_down` bzw. `session.last_n_f_cnt_down`, Key mit „Edit devices“). 18:51 UTC `DL Port 10: 15`, `DL: 15min`, Weckzeit +15 min. **Paket 2 auf F103 abgenommen.**
- [x] (07.11., `ttn_anleitung.md` Abschnitt 5) In die TTN-Anleitung: FCntDown muss < 65536 sein, in NS **und** AS (`scripts/ttn-status.sh` zeigt beide). Zurücksetzen per API (siehe oben) oder Gerät neu anlegen. beelogger09: AS 39426 – ok, aber im Blick behalten.
- [x] **Firmware-Version kenntlich machen:** `l_ID` = 261106 (JJMMTT, Nutzerentscheid 06.11.), README Punkt 5, Diff neu erzeugt. Noch nicht geflasht.
- [ ] Rückmeldung an den Server (07.11.: Entwurf `docs/vorschlag_payload_erweiterung.md`, wartet auf Rückmeldung der Entwickler), welches Intervall aktiv ist (z. B. in jedem 10. Uplink wie die ID)?

- [x] (07.11., `ttn_anleitung.md` Abschnitt 5) In die TTN-Anleitung: „Reset session and MAC state“ per API = `PATCH /api/v3/ns/applications/<app>/devices/<dev>` mit Body `{"field_mask":{"paths":["session","mac_state"]}}` (nicht `/reset`, das gibt 404); Beispiel: App beelogger011, Gerät beelogger011. Setzt NS-FCntDown auf 0, AS-Zähler extra per PUT. 06.11. an beelogger011 gemacht.

**Doku / Repo**
- [x] (06.11., README neu) README-Startseite überarbeiten: Ziel des Projekts in 3 Sätzen; **Schritt-für-Schritt „Firmware selbst bauen“** (Repo laden, VS Code + PlatformIO, Vorlagen kopieren, Env wählen BluePill/BlackPill, Schalter ABP/OTAA (`LORA_OTAA`, ab Paket 3), bauen, flashen); Startzeile `Name/UID/DevAddr` erklären (steht bisher nur knapp unter „Änderungen“).
- [ ] DevAddr/DevEUI wieder unverändert lassen (Nutzer 06.11.: nicht geheim, hilft beim Nachvollziehen). Neutralisierung der DevEUIs in `docs/ttn/` beim nächsten Export nicht mehr machen; Standorte/Namen/URLs/Passwörter bleiben draußen.
- [x] (06.11., README) Abschnitt **„Gefundene Fehler“** (Repo-Doku): Aux3 in jedem Uplink (seqnoUp), Register 4 `WeckIntervallMinuten` gespeichert aber unbenutzt, FCntDown > 65535 → Downlink verworfen, AS-Zähler übersteht MAC-Reset, RX2-SF9 vs. TTN-Vorgabe SF12.
- [x] (06.11., README) Abschnitt **Fair Use Policy** (TTN: 30 s Airtime/Tag, 10 Downlinks/Tag) mit Paketgrößen und Intervallen; Material aus Wiki `projekte:beelogger_doku` übernehmen.
- [x] (06.11. Nutzer: **nein**, bleiben im Wiki) Entscheiden: DokuWiki-Seiten zu beelogger (Payload/Bitaufteilung, Verschlüsselung, TTN 06/06a) ins Repo übernehmen (Markdown, ohne Privates)?
- [x] (07.11., `docs/ttn_anleitung.md` Abschnitt 4, `down/replace` getestet: beelogger011-otaa `0F`, 02:08 MEZ `DL Port 10: 15`, Weckzeit +15 min) Downlink aus der Server-Software: Doku für beelogger-Entwickler, wie ihr Server per TTN-API Downlinks schickt (`POST /api/v3/as/applications/<app>/devices/<dev>/down/push`, API-Key mit „Write downlink application traffic“, FPort 10, Base64-Payload) – Beispiel curl/PHP.
- [x] TTN-Anleitung mit Screenshots je Variante (siehe Paket 4). 07.11.: `docs/ttn_anleitung.md`, Bilder 01–09.
- [x] (README, 06.11.) Doku (Nutzer 06.11.): Vor- und Nachteile OTAA vs. ABP erklären – warum es beide Varianten gibt.
- [x] (07.11., `ttn_anleitung.md` Abschnitt 3: Bild 08 Live data, Auszug serielle Konsole mit Join und Downlink) Doku (Nutzer 06.11.): In Beispielen echte Live-Daten aus der TTN-Konsole zeigen (z. B. Uplink von beelogger08), damit neue Nutzer sehen, wie die Daten dort aussehen müssen. Dazu passende Auszüge aus der seriellen Konsole (Startzeile, Join, Uplink, TX done).

**Messen / Hardware**
- [x] Wachzeit und Stromverbrauch pro Zyklus messen, **vor und nach** dem Flash der neuen Firmware: am **beelogger08** (vor dem Update!). Log `t-on` und `TX done`-Zeit (ms) vergleichen; Strom mit Multimeter/USB-Meter. 07.11.: neue FW gleich (Schlaf 18,09 µA, wach 24–34 mA).
  - 06.11. **Basis alte FW, beelogger08** (Black F411CE, Messaufbau zu Hause: 2 HX711 ohne Wägezellen, 4 Waagen konfiguriert, Akku 4,21 V = Anzeige 4,21 V): Wachzeit **18 s** (`t-on: 18`; Waage 1/2 `fail` je ~2 s, Waage 3/4 je ~5 s, Senden ~2 s). Ohne HX711 14 s. Strom (Multimeter in Reihe): wach 24–36 mA, Schlaf 18,2 µA → pro Zyklus ca. 0,15 mAh; bei 5 min ca. 44 mAh/Tag, bei 15 min ca. 15 mAh/Tag (Schlaf nur 0,44 mAh/Tag; Sendespitze vom Multimeter nicht erfasst). Gleicher Aufbau für die Messung nach dem Update.
- [ ] LoRa-Kanäle (`LORA_CHANNELS` 1/3/8): Entscheidung ans Ende, wenn die Firmware fertig ist. Empfehlung bisher 8 (TTN-Standard). Vorher prüfen: Übernimmt die Firmware bei OTAA die Kanäle aus dem Join-Accept (CFList) oder setzt sie danach wieder ihre eigenen (`LMIC_setupChannel`, STM32_LORA.ino ~238)? 8 Kanäle bringen keine zusätzliche Airtime (alle im selben 1-%-Band), nur weniger Kollisionen.
- [ ] Seriell-Log läuft dauerhaft (`scripts/seriell-log.sh`, `~/beelogger-logs/seriell.log`) – vor dem Flashen stoppen.

**Später / Ideen**
- [ ] **beeloggerHexa** (07.11.: Entwurf `docs/vorschlag_payload_erweiterung.md`, wartet auf Rückmeldung der Entwickler): 6 (oder 8) Waagen. Klären: Payload-Größe je Waage/Temperatur, maximale Sendehäufigkeit unter der Fair Use Policy bei SF7…SF12, ob Aufteilung auf mehrere Pakete nötig.
- [ ] Waagenkonfiguration per Downlink.

## Offene Fragen (Planung)

- [x] Serielle Konsole läuft (FTDI, 9600 Baud).
- [x] Flash-Größe des Test-Chips (64 oder 128 KB) und ob der Build mit Join-Code passt. Stand 06.11.: Build F103 = 54 KB; bei C8 (64 KB) bleiben ca. 9 KB für Join-Code + 1 KB Session-Seite.
- [x] Intervall-Fernwert: entschieden 06.11.2026 (Sitzung 2) **Backup-Register 5, Bit 9–14** (F1 und F4), nicht Flash.
- [x] Neu-Join-Regel: entschieden 06.11.2026, siehe Entscheidungen (1 Link-Check/Tag, 3 ohne Antwort → Neu-Join).


## Fragen und Antworten aus der Planung

- **Wo liegt der FCnt?** Im RTC-Backup-Register 3, nicht im Flash. Keine Abnutzung.
- **Kann der BluePill die Session in den Backup-Registern halten?** Nein, zu klein und voll. Daher Flash, nur beim Join beschrieben.
- **Geht Downlink auch mit ABP?** Ja. OTAA ist dafür nicht nötig.
- **Beide Versionen bauen?** Ja, eine Codebasis mit Schalter `LORA_OTAA`.
