# Entwicklung: Änderungen, gefundene Fehler, Stand

[← Übersicht](README.md)

Stand: 10.11.2026 · gilt für: STM32 261107 · geprüft gegen Code: Commit a3ac566

## Änderungen gegenüber 260705

Vollständig in [`aenderungen_gegen_260705.diff`](aenderungen_gegen_260705.diff). Kurz:

1. **Startzeile** in `System_Init()` (siehe [bauen_flashen.md](bauen_flashen.md#serielle-konsole-und-startzeile)).
2. **Downlink Sendeintervall** (`onEvent()` in `STM32_LORA.ino`):
   - FPort **10**, 1 Byte = Intervall in Minuten, gültig **5–180**; **0** = zurück zur Firmware-Vorgabe; andere Werte werden ignoriert.
   - Gespeichert in RTC-Backup-Register 5, Bit 9–14 (Minuten/5). Übersteht den Tiefschlaf, nicht aber Akku-ab. Alte Firmware schreibt dort 0, ein Update ist also neutral.
   - Ersetzt Tag/Nacht/Winter-Intervall. Service-Modus und Akkusparmodus haben Vorrang.
   - Class A: wirkt ab dem Zyklus nach dem Uplink, in dessen Empfangsfenster der Downlink kam.
   - Getestet auf BlackPill F411 (beelogger09) und BluePill F103 (beeloggerblue05).
3. **Sketch-ID-Fehler behoben** (Feld Aux2, siehe „Gefundene Fehler“; in älteren Notizen „Aux3“ genannt).
4. `Config_LORA.h`: `LOGGER_NAME` je Geräteblock.
5. **Firmware-Kennung** `l_ID` = **261107** (261106: ohne ADR-aus) (Form JJMMTT wie im Original, `STM32_LORA_260705.ino`). Steht in der Startzeile und geht alle 10 Uplinks an den Server. Bei jeder neuen Version auf das Datum setzen.
6. **OTAA** (`#define LORA_OTAA 1`, Werte `DEVEUI`, `APPEUI` = JoinEUI, beide LSB, `APPKEY` MSB; TTN: LoRaWAN 1.0.2, RP 1.0.2 rev B):
   - Join beim ersten Versand. Gespeichert werden nur DevAddr, NetID, AppNonce, DevNonce, Rx1-Delay und Rx2-DR, mit Version und Prüfsumme: BlackPill in RTC-Backup-Registern 10–13, BluePill in der letzten Flash-Seite laut Chip, beim F103CB 0x0801FC00 (nur bei Änderung geschrieben; liegt die Seite im Programm, wird nichts gespeichert und der Logger joint bei jedem Start). Die Session-Schlüssel rechnet der Logger nach jedem Aufwachen aus dem AppKey neu (`LMIC_restoreJoin`).
   - Neu-Join: nach Power-On-Reset (Akku ab), bei ungültigem Datum, ab FCnt 16000 und nach 3 Tagen ohne Antwort auf den täglichen LinkCheckReq (1 Downlink/Tag).
   - Join-Versuch höchstens 60 s pro Zyklus; ohne Erfolg neuer Versuch im nächsten Zyklus.
   - LMIC: Join-Code eingeschaltet (`DISABLE_JOIN` aus), LinkCheckReq/-Ans ergänzt, als Kopie in `src/lmic_otaa/` (die Bibliothek in `lib/` bleibt original). ABP-Geräte joinen nie (DevAddr ist gesetzt), Build ca. 0,7 KB größer.
7. **ADR aus** (`LMIC_setAdrMode(0)` in `setup_LORA()`): Die LMIC schaltet ADR in `LMIC_reset()` ein, die Firmware beantwortet aber keine LinkADRReq. Uplinks tragen kein ADR-Bit mehr. TTN-Einstellung „ADR Disabled“ bleibt empfohlen für Altgeräte. Noch nicht auf Gerät getestet.
8. **Software-Watchdog und I2C-Bus freitakten** (`STM32_LORA_utility.ino`, Aufruf in `loop()`): Hängt ein Sensor oder das Funkmodul, blieb der Logger bisher bis zum Stromausfall stehen (auch im Original). Jetzt zählt TIM4 im Sekundentakt, nur in der Wachphase: Sensoren 60 s, LoRa 100 s, Schlaf vorbereiten 20 s. Läuft eine Phase über, merkt sich der Logger das im Backup-Register (F1: 10, F4: 14) und startet neu. Nach einem Sensor-Hänger misst er einmal ohne I2C-Sensoren und sendet; nach einem LoRa-Hänger oder zwei Neustarts in Folge schläft er bis zum nächsten Termin. Vor der Sensorabfrage wird ein hängender I2C-Bus (SDA low) mit bis zu 9 Takten freigegeben. Der Hardware-Watchdog (IWDG) passt nicht, weil er im Standby weiterläuft und den Logger alle ~26 s wecken würde. Bricht die LMIC mit `hal_failed()` ab (z. B. Funkmodul antwortet beim Start nicht), sperrte sie bisher alle Interrupts und blieb stehen, sodass auch der Watchdog nicht mehr griff; in der LMIC-Kopie ruft `hal_failed()` jetzt `lmic_fehler()` aus dem Sketch auf (Phase merken, Neustart). Build ca. 2 KB größer.
   - Getestet 07.11.2026 auf BluePill F103 mit simulierten Hängern: Sensor-Hänger → Neustart nach 60 s → ohne I2C gemessen und gesendet (in TTN angekommen); zweiter Neustart in Folge → Zyklus ausgelassen; LMIC-Fehler → sofort Neustart, Zyklus ausgelassen. Den LMIC-Totalhänger hat ein defektes RFM95 im Test tatsächlich ausgelöst. SDA fest an GND bzw. SDA mit SCL kurzgeschlossen (BME280, BH1750) führt dagegen zu keinem Hänger: Der STM32-Kern bricht die I2C-Zugriffe per Timeout ab, die Sensoren liefern -1, gesendet wird normal.

## Ältere Stände der Original-Firmware

Die Kennung `l_ID` hat die Form JJMMTT. Grundlage dieses Repos ist **260705** (`src/STM32_LORA_260705.ino`).
Ältere Stände liegen nicht im Repo, belegt ist nur:

| Stand | Unterschied | Beleg |
|---|---|---|
| 220326 | gleiches Bitmasken-Format wie heute | Testpayload von beelogger.de mit Aux3 220326, dekodiert mit dem heutigen Decoder ([payload.md](payload.md)) |
| bis 230711 | Frame-Counter im externen EEPROM, Rücksprung bei 16382 | Code dafür noch in `STM32_LORA.ino` hinter `#if 0` ([doku_pflege.md](doku_pflege.md)) |
| 260705 | Frame-Counter im RTC-Backup-Register 3, Rücksprung nach 16384, 0 nach Stromausfall | `inc_LoRaSeq()`, `System_Init()` |

## Gefundene Fehler

| Fehler | Folge | Stand |
|---|---|---|
| `prep_payload()` prüft `LMIC.seqnoUp % 10`, bevor `seqnoUp` gesetzt ist | Sketch-ID (Aux2, 4 Byte) in **jedem** Uplink statt in jedem 10. → mehr Airtime | behoben: `LoRaSeqCounter % 10` |
| Kein Watchdog: hängt ein I2C-Sensor (gestörter Bus) oder das Funkmodul, bleibt die Firmware stehen | Logger sendet nicht mehr bis zum Stromausfall (auch Original 260705) | Software-Watchdog + I2C freitakten (Änderung 8), getestet |
| LMIC `hal_failed()` (z. B. Funkmodul antwortet nicht) sperrt die Interrupts und hält an | Logger steht bis zum Stromausfall, auch ein Timer-Watchdog greift nicht (auch Original) | `hal_failed()` ruft `lmic_fehler()` → Neustart (Änderung 8) |
| Backup-Register 4 `WeckIntervallMinuten` wird geschrieben, aber nirgends gelesen | toter Code, Register belegt | unverändert, nur dokumentiert |
| TTN-Vorgabe RX2 = SF12 (DR0), Firmware hört auf SF9 (DR3) | TTN schickt Antworten ins Leere und wiederholt MAC-Befehle („Downlink-Schleife“), bis zu 288 Downlinks/Tag | TTN auf Rx2 DR 3 stellen (siehe [ttn_einrichten.md](ttn_einrichten.md#7-mac-einstellungen-im-detail-abp-lmic-dieser-firmware)) |
| FCntDown in TTN > 65535; LMIC nutzt nach jedem Neustart nur 16 Bit | Downlink wird **ohne Meldung verworfen** | Zähler in TTN auf 0 setzen (siehe [ttn_einrichten.md](ttn_einrichten.md#7-mac-einstellungen-im-detail-abp-lmic-dieser-firmware)) |
| „Reset session and MAC state“ setzt nur den Zähler im Network Server zurück, nicht im Application Server | Downlink bleibt verworfen, obwohl die Konsole 0 zeigt | beide Zähler per API zurücksetzen |
| OTAA-Session beim BluePill fest bei 63 KB im Flash (eigener Fehler, 06.11.) | Firmware mit BME280 + Licht ist ca. 64 KB: der erste Join löschte ein Stück des eigenen Programms | behoben 07.11.: letzte Seite laut Chip, Prüfung gegen Programmende ([speicher.md](speicher.md)) |
| `lora_downlink.php` schickte UTC statt Ortszeit (eigener Fehler, nur Branch `p7-serverquittung`) | Uhren nach dem Stellen im Winter 1 h nach | behoben 08.11. abends, Commit 80673f1 ([architektur.md](architektur.md#uhr-wann-sie-stimmt-und-wann-nicht)) |

9. **Serverquittung per Downlink FPort 11** (Branch `p7-serverquittung`, noch nicht in `main`): `onEvent()` wertet 7 Byte aus (FCnt-Bezug, Zeit, Abstand, Intervall), stellt die RTC (`rtc_stm.setEpoch()`) und das Fern-Intervall (5–60 min). Gegenstück auf dem Server: `server/lora_downlink.php`. Damit bekommen LoRa-Logger wie WLAN-Logger Uhrzeit und Intervall vom Server; Nacht- und Winterlogik arbeiten mit der richtigen Uhrzeit. Format: [payload.md](payload.md#downlink-fport-11-serverquittung-uhr--intervall), Einbau: [server.md](server.md#rückkanal-für-lora-lora_downlinkphp-eigene-erweiterung). Getestet am 08.11.2026 mit BlackPill F411 (OTAA) und BluePill F103 (OTAA) über TTN und einen eigenen Server.

**ATmega-LoRa-Sketch 250326:** Die Stellen zu Sketch-ID und ADR sowie Rx2 SF9 sehen dort gleich aus. Die TTN-Einstellungen dürften deshalb auch für ATmega-Logger passen. Vorschlag mit den zwei Änderungen, dazu eine OTAA-Variante (261108): [`atmega_lora/`](../atmega_lora/). Seit 08.11.2026 im Dauerlauf auf einem Arduino Uno (OTAA); Pro Mini 8 MHz ungetestet.

## Stand

| Paket | Inhalt | Stand |
|---|---|---|
| 1 | Grundlage: PlatformIO-Build für Blue und Black, Startzeile | fertig |
| 2 | Sendeintervall per Downlink FPort 10 (ABP), Sketch-ID-Fehler | fertig, auf Blue und Black getestet |
| 3 | OTAA | fertig, auf Blue (F103) und Black (F411) getestet: Join, Neustart ohne Join, Neu-Join nach Stromausfall, Wiederanlauf aus dem Flash |
| – | ADR aus, Software-Watchdog (Kennung 261107) | fertig, im Dauerlauf |
| – | ATmega-LoRa 250326 → 261107 (ABP) und 261108 (OTAA) | baut für Pro Mini 8/16 MHz und Nano; OTAA seit 08.11.2026 im Dauerlauf auf Uno |
| 7/8 | Serverquittung FPort 11: Uhr und Intervall vom Server | Branch `p7-serverquittung`, auf Blue und Black getestet, Dauerlauf läuft; Merge nach `main` nach dem Dauerlauf |
| 4 | Doku: Architektur, Payload, Intervalle, Server, TTN | Text fertig (08.11.2026); Bildschirmfotos folgen |
| 5 | Weitergehende Ideen (Sendeplan, Zusatz-Uplinks, Waage 5–8, Payload) | nicht in `main` ([Idee](planung/idee_sendeplan_schwarmalarm.md), [Payload](planung/vorschlag_payload_erweiterung.md)) |

Einzelheiten, Entscheidungen und Verlauf: [`planung/planung_otaa_downlink.md`](planung/planung_otaa_downlink.md).
