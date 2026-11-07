# beelogger STM32 – LoRaWAN-Firmware (PlatformIO)

Die beelogger-Firmware **STM32_LORA_260705** von [beelogger.de](https://beelogger.de) als PlatformIO-Projekt für VS Code, mit Erweiterungen. Gefundene Fehler werden behoben und dokumentiert, damit die beelogger-Entwickler sie übernehmen können.

**Auf einen Blick**

- **Maximal kompatibel zum Original:** Messablauf, Waagenzahl, Payload-Format und beelogger-Server bleiben wie beim Original 260705. Neu sind nur Downlinks (ABP und OTAA) und Fehlerkorrekturen. Weitergehende Ideen (mehr Waagen, andere Payload, eigener Sendeplan) kommen nicht in `main`, sondern in einen eigenen Branch mit eigenem Server ([Idee](docs/idee_sendeplan_schwarmalarm.md), [Payload](docs/vorschlag_payload_erweiterung.md)).
- **Sendeintervall aus der Ferne** per TTN-Downlink (FPort 10, 5–180 min), mit ABP und OTAA.
- **OTAA** für neue Geräte, ABP für vorhandene Feldgeräte – eine Codebasis, umschaltbar per `#define LORA_OTAA`.
- **BluePill (F103) und BlackPill (F411/F401)**, alle vier Kombinationen auf Testgeräten abgenommen.
- **Fehler behoben:** Sketch-ID in jedem Uplink, TTN-Downlink-Schleife (Einstellungen dokumentiert), ADR-Bit.
- **ATmega-Logger (Universal 1.x, beeloggerSMD):** Vorschlag mit denselben zwei kleinen Änderungen (Sketch-ID, ADR) für den Original-Sketch 250326, sonst unverändert (ABP). Baut, auf einem Logger noch ungetestet → [`atmega_lora/`](atmega_lora/).
- **Startzeile** mit Firmware-Kennung, Name, Chip-UID und DevAddr – man sieht sofort, welches Gerät am Kabel hängt.
- **Keine Schlüssel oder Passwörter im Repo** (siehe [Schlüssel](#schlüssel)).

**Inhalt:** [Dateien](#inhalt) · [Selbst bauen](#firmware-selbst-bauen) · [Varianten](#eine-firmware-vier-varianten) · [ABP oder OTAA](#abp-oder-otaa) · [Startzeile](#serielle-konsole-und-startzeile) · [Änderungen](#änderungen-gegenüber-260705) · [Gefundene Fehler](#gefundene-fehler) · [TTN-Einstellungen](#ttn-einstellungen-abp-lmic-dieser-firmware) · [Fair Use](#ttn-fair-use-policy) · [Lizenz](#lizenz) · [Stand](#stand)

## Inhalt

| Pfad | Inhalt |
|---|---|
| **Firmware** | |
| [`src/`](src/) | Sketch 260705 mit Änderungen (siehe [Änderungen](#änderungen-gegenüber-260705)) |
| [`src/Config_LORA.h.example`](src/Config_LORA.h.example), [`src/Config_MULTI.h.example`](src/Config_MULTI.h.example) | Vorlagen für LoRa- und Sensorkonfiguration (nur Platzhalter) |
| [`lib/`](lib/) | beelogger-Bibliotheken 2026_04, **unverändert** |
| [`src/lmic_otaa/`](src/lmic_otaa/) | Kopie der beelogger-LMIC mit unseren Änderungen (Join-Code an, Link-Check, `LMIC_restoreJoin`). Liegt im Sketch-Ordner, damit die gemeinsame Bibliothek für die anderen Sketche unverändert bleibt. Eingebunden über `#include "src/lmic_otaa/lmic.h"` in `Config_LORA.h` |
| [`atmega_lora/`](atmega_lora/) | ATmega328-LoRa-Sketch 250326 (Universal 1.x, beeloggerSMD) mit zwei kleinen Änderungen als Vorschlag (Sketch-ID, ADR), Kennung 261107. Baut, auf einem Logger noch ungetestet |
| [`orginal_firmware_bibliotheken/`](orginal_firmware_bibliotheken/) | Originale von beelogger.de zum Vergleich (Sketche 202610, ATmega-LoRa 250326, Bibliotheken 2026_04, WLAN/LTE 230928) |
| [`platformio.ini`](platformio.ini) | Boards BluePill F103CB, BlackPill F411CE, BlackPill F401CC |
| **Anleitungen** | |
| [`docs/flashen.md`](docs/flashen.md) | Bauen, Flashen über FTDI (stm32loader), Kalibrieren, Gerätetabelle |
| [`docs/ttn_anleitung.md`](docs/ttn_anleitung.md) | TTN Schritt für Schritt: Application, Gerät ABP/OTAA, Prüfen, Downlink, Fehlersuche, API-Keys |
| **Ideen (nicht für `main`)** | |
| [`docs/vorschlag_payload_erweiterung.md`](docs/vorschlag_payload_erweiterung.md) | Entwurf für einen eigenen Branch: Intervall-Rückmeldung und Waage 5–8 per Erweiterungsbyte, Airtime je SF |
| [`docs/idee_sendeplan_schwarmalarm.md`](docs/idee_sendeplan_schwarmalarm.md) | Idee zum Besprechen: Reihenfolge (erst ohne Server-Änderung), Sendeplan über Solarspannung/Temperatur statt Uhr, öfter wiegen statt öfter senden, Service-Quittung, Fragen an die Entwickler, Testaufbau |
| **Nachweise** | |
| [`docs/planung_otaa_downlink.md`](docs/planung_otaa_downlink.md) | Plan, Entscheidungen und Verlauf der Arbeitspakete |
| [`docs/aenderungen_gegen_260705.diff`](docs/aenderungen_gegen_260705.diff) | Alle Änderungen gegenüber dem Original-Sketch 260705 |
| [`docs/ttn/`](docs/ttn/) | TTN-Einstellungen (JSON aus der TTN-API), Payload-Decoder, Beispielnachrichten |
| [`docs/logs/`](docs/logs/) | Serielle Mitschnitte (9600 Baud) |
| [`scripts/`](scripts/) | `seriell-log.sh` (Konsole mitschreiben), `ttn-status.sh` (TTN-Werte eines Geräts prüfen) |

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
9. **TTN einstellen** wie unter [TTN-Einstellungen](#ttn-einstellungen-abp-lmic-dieser-firmware) bzw. in der [TTN-Anleitung](docs/ttn_anleitung.md), sonst kommen Downlinks nicht an.

**ATmega-Logger** (Universal 1.x, beeloggerSMD): eigener Ordner mit eigener Anleitung, siehe [`atmega_lora/README.md`](atmega_lora/README.md#selbst-bauen).

> **Tipp:** Vor dem Flashen an der [Startzeile](#serielle-konsole-und-startzeile) prüfen, welches Gerät am Kabel hängt – sonst landen die Schlüssel des falschen Geräts auf dem Board.

Hinweise:
- STM32-Core 2.6.0 (`framework-arduinoststm32@~4.20600`), in `platformio.ini` festgelegt.
- BluePill: Board `bluepill_f103c8_128k` mit `-DARDUINO_BLUEPILL_F103CB` (die Firmware braucht 128 KB).
- Flash-Belegung (07.11., mit OTAA): F103CB 56 500 B, F411CE 55 308 B. BluePill: muss unter 63 KB bleiben, darüber liegt die OTAA-Session-Seite.
- `pio run -t upload` geht beim F411 nicht (stm32flash kennt die Chip-ID 0x431 nicht), deshalb stm32loader.

## Eine Firmware, vier Varianten

| | ABP (`LORA_OTAA 0`) | OTAA (`LORA_OTAA 1`) |
|---|---|---|
| **BluePill** F103 (`bluepill_f103c8`) | läuft (beeloggerblue05) | läuft (beelogger011) |
| **BlackPill** F411/F401 (`blackpill_f411ce`, `blackpill_f401cc`) | läuft (beelogger09) | läuft (beelogger08) |

Board = PlatformIO-Umgebung, ABP/OTAA = Schalter im Geräteblock von `Config_LORA.h`. Sonst ist der Code gleich, Korrekturen gelten für alle Varianten.

## ABP oder OTAA

**ABP** (Activation by Personalization): DevAddr und Sitzungsschlüssel stehen fest in der Firmware. Das Gerät sendet sofort.
**OTAA** (Over-the-Air Activation): In der Firmware steht nur ein Stammschlüssel (AppKey). Beim Start meldet sich das Gerät an („Join“), TTN vergibt DevAddr und Sitzungsschlüssel neu.

| | ABP | OTAA |
|---|---|---|
| Start | sendet sofort | Join beim ersten Start und nach Stromausfall: ein Downlink, ca. 1 s länger wach (gemessen: 19 s statt sonst ca. 18 s mit 4 Waagen) |
| Gateway beim Start | egal | muss erreichbar sein, sonst kein Join und kein Uplink |
| Schlüssel | Sitzungsschlüssel fest, gelten für immer | bei jedem Join neu; im Gerät nur AppKey |
| Zähler nach Akku-ab | fängt bei 0 an → in TTN „Resets frame counters“ nötig (schwächt den Schutz gegen wiederholte Nachrichten) | neuer Join, neue Session – kein Zähler-Reset in TTN nötig |
| TTN-Einstellungen (Rx1/Rx2) | müssen genau zur Firmware passen, sonst Downlink-Schleife; Änderung erst nach „Reset session and MAC state“ | kommen mit dem Join-Accept ins Gerät |
| Downlinks (Sendeintervall, FPort 10) | ja, mit neuer Firmware (alte FW wertet keine aus) | ja |
| Netzausfall erkennen | nein | ja: 1 Link-Check pro Tag, nach 3 Tagen ohne Antwort Neu-Join (kostet 1 Downlink/Tag von 10 erlaubten) |
| Gerät in andere TTN-App umziehen | neu flashen | in TTN neu anlegen, Join |
| Boards | Blue und Black | Blue und Black |
| Strom | – | gleich (beelogger08: Schlaf 18,1 µA, wach 24–34 mA, wie mit alter FW) |
| Strom ohne Gateway | höchstens 8 s pro Zyklus auf Antwort warten | Join-Versuch bis 60 s in **jedem** Zyklus (nach Stromausfall ohne Gateway oder nach 3 Tagen Netzausfall). Geschätzt ca. 150 statt 44 mAh/Tag bei 5 min, nicht gemessen |
| Zählerüberlauf | Uplink-Zähler springt nach 16382 auf 0 (bei 5 min ca. alle 57 Tage, bei 15 min ca. alle 170 Tage) → „Resets frame counters“ in TTN dauerhaft nötig | Neu-Join bei FCnt 16000, kein Überlauf |
| DevAddr | fest | neu bei jedem Join (beelogger011: 260BF6FE → 260BE069). Für den beelogger-Server unkritisch: Er bekommt die Daten über Payload-Formatter und Webhook der jeweiligen TTN-App, die DevAddr spielt dabei keine Rolle |

**Downlinks gehen auch mit ABP.** LoRaWAN öffnet nach jedem Uplink zwei Empfangsfenster (Rx1, Rx2), egal ob das Gerät per ABP oder OTAA aktiviert wurde. Die Firmware wertet Downlinks auf FPort 10 in beiden Varianten mit demselben Code aus (`onEvent`, `EV_TXCOMPLETE`). Getestet am 06.11.2026 mit ABP: beelogger09 (Black) bekam `0F`, Log `DL Port 10: 15`, nächste Weckzeit 15 min später (`docs/logs/2026-11-06_beelogger09_downlink_15min.log`); beeloggerblue05 (Blue) ebenso. Dass Downlinks mit ABP oft nicht ankommen, liegt an den TTN-Einstellungen, nicht an ABP: Rx1-Delay und Rx2-Datenrate müssen zur Firmware passen, und alte Geräte haben oft einen hohen Downlink-Zähler (FCntDown) aus der Downlink-Schleife – dann verwirft das Gerät den Downlink. Abhilfe: „Reset session and MAC state“ und Downlink-Zähler auf 0 (siehe [TTN-Anleitung](docs/ttn_anleitung.md)). Die alte Firmware (vor 261106) wertet gar keine Downlinks aus.

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
STM32_LORA_261107 - STM32F411CE
Name: beelogger09 UID: 353439323337510600410066 DevAddr: 260BD691
```

- `261107`: Firmware-Kennung (`l_ID`), danach der Chip-Typ.
- `Name`: `LOGGER_NAME` aus `Config_LORA.h`.
- `UID`: 96-Bit-Seriennummer des Chips, eindeutig je Board.
- `DevAddr`: LoRaWAN-Adresse, unter der das Gerät in TTN steht.

Damit sieht man vor dem Flashen, welches Gerät am Kabel hängt und welche Firmware darauf läuft.

## Änderungen gegenüber 260705

Vollständig in [`docs/aenderungen_gegen_260705.diff`](docs/aenderungen_gegen_260705.diff). Kurz:

1. **Startzeile** in `System_Init()` (siehe oben).
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
   - Getestet 07.11.2026 auf BluePill F103 mit simulierten Hängern: Sensor-Hänger → Neustart nach 60 s → ohne I2C gemessen und gesendet (in TTN angekommen); zweiter Neustart in Folge → Zyklus ausgelassen; LMIC-Fehler → sofort Neustart, Zyklus ausgelassen. Den LMIC-Totalhänger hat ein defektes RFM95 im Test tatsächlich ausgelöst. SDA bzw. SCL fest an GND (BME280, BH1750) führt dagegen zu keinem Hänger: Der STM32-Kern bricht die I2C-Zugriffe per Timeout ab, die Sensoren liefern -1, gesendet wird normal.

## Gefundene Fehler

| Fehler | Folge | Stand |
|---|---|---|
| `prep_payload()` prüft `LMIC.seqnoUp % 10`, bevor `seqnoUp` gesetzt ist | Sketch-ID (Aux2, 4 Byte) in **jedem** Uplink statt in jedem 10. → mehr Airtime | behoben: `LoRaSeqCounter % 10` |
| Kein Watchdog: hängt ein I2C-Sensor (gestörter Bus) oder das Funkmodul, bleibt die Firmware stehen | Logger sendet nicht mehr bis zum Stromausfall (auch Original 260705) | Software-Watchdog + I2C freitakten (Änderung 8), getestet |
| LMIC `hal_failed()` (z. B. Funkmodul antwortet nicht) sperrt die Interrupts und hält an | Logger steht bis zum Stromausfall, auch ein Timer-Watchdog greift nicht (auch Original) | `hal_failed()` ruft `lmic_fehler()` → Neustart (Änderung 8) |
| Backup-Register 4 `WeckIntervallMinuten` wird geschrieben, aber nirgends gelesen | toter Code, Register belegt | unverändert, nur dokumentiert |
| TTN-Vorgabe RX2 = SF12 (DR0), Firmware hört auf SF9 (DR3) | TTN schickt Antworten ins Leere und wiederholt MAC-Befehle („Downlink-Schleife“), bis zu 288 Downlinks/Tag | TTN auf Rx2 DR 3 stellen (siehe unten) |
| FCntDown in TTN > 65535; LMIC nutzt nach jedem Neustart nur 16 Bit | Downlink wird **ohne Meldung verworfen** | Zähler in TTN auf 0 setzen (siehe unten) |
| „Reset session and MAC state“ setzt nur den Zähler im Network Server zurück, nicht im Application Server | Downlink bleibt verworfen, obwohl die Konsole 0 zeigt | beide Zähler per API zurücksetzen |

**ATmega-LoRa-Sketch 250326:** Die Stellen zu Sketch-ID und ADR sowie Rx2 SF9 sehen dort gleich aus. Die TTN-Einstellungen dürften deshalb auch für ATmega-Logger passen. Vorschlag mit den zwei Änderungen, auf einem Logger noch ungetestet: [`atmega_lora/`](atmega_lora/).

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
- Kürzere Intervalle in der Schwarmzeit und Senden nur bei Gewichtsänderung: siehe [Idee Sendeplan/Schwarmalarm](docs/idee_sendeplan_schwarmalarm.md). Für `main` nicht vorgesehen: Laut beelogger-Team ist die Schwarmlogik des Servers auf den 10-min-Takt ausgelegt (Windböen), öfter senden bringt nichts.

## beelogger-Server: Mindestabstand

- Der Community-Server speichert eine Meldung nur mit genug Abstand zur letzten: **Oktober–März 400 s**, sonst 200 s (Auskunft beelogger-Team 07.11.2026; die Server-Skripte M40_261004 kennen nur 200 s). Die Serverzeit wird auf die Minute abgerundet, 200 s heißen also praktisch 4 min.
- Bei 5 min Sendeintervall fehlt im Winter am Community-Server jeder zweite Wert. Wer nur den Community-Server nutzt, stellt Okt–März mindestens 10 min ein (Firmware-Vorgabe: 10 min Tag, 30 min Nacht).
- Kalibriertemperatur `99.9` in der Server-Konfiguration lockert die Sperre, ist aber **nur für Tests** bei der Firmware-Entwicklung gedacht: Derselbe Wert steuert die Temperaturkompensation, im Betrieb springt dann das Gewicht.

## Schlüssel

- `src/Config_LORA.h` (LoRaWAN-Schlüssel) und `src/Config_MULTI.h` (WLAN-/Server-Passwörter, Kalibrierung) stehen in `.gitignore`. Im Repo sind nur die Vorlagen `*.example`.
- Kompilierte Firmware (`*.bin`, `.pio/`, `backup/`) enthält die Schlüssel und ist ebenfalls ausgeschlossen.
- Die LMIC-Beispiele in `lib/…/examples/` enthalten den öffentlichen Testschlüssel der LMIC-Bibliothek, keine echten Schlüssel.

## Lizenz

Firmware: (C) R. Schick – beelogger.de, **GNU GPL v3 oder später**. Die Änderungen hier stehen unter derselben Lizenz ([`LICENSE`](LICENSE)). Geänderte Dateien tragen einen Änderungsvermerk mit Datum. Die Bibliotheken behalten ihre eigenen Lizenzen. Einzelheiten und wie die GPL-Bedingungen erfüllt werden: [`docs/lizenz.md`](docs/lizenz.md).

Kein offizielles Projekt von beelogger.de. Original: https://beelogger.de

## Stand

| Paket | Inhalt | Stand |
|---|---|---|
| 1 | Grundlage: PlatformIO-Build für Blue und Black, Startzeile | fertig |
| 2 | Sendeintervall per Downlink (ABP), Sketch-ID-Fehler | fertig, auf Blue und Black getestet |
| 3 | OTAA | fertig: BlackPill (beelogger08: Join, Neustart ohne Join, Neu-Join nach Stromausfall, Strom gleich), BluePill (beelogger011: Join, Wiederanlauf aus dem Flash) |
| 4 | Doku: TTN-Anleitung, Wiki | Anleitung als Text fertig; Bildschirmfotos und Wiki folgen |
| – | ADR aus (Kennung 261107) | gebaut, Test auf Gerät folgt |
| – | ATmega-LoRa 250326 → 261107 (Sketch-ID, ADR) | baut für Pro Mini 8/16 MHz und Nano, Test auf einem ATmega-Logger offen |
| 5 | Weitergehende Ideen (Sendeplan, Zusatz-Uplinks, Waage 5–8, Payload) | nicht in `main`; eigener Branch, Test auf eigenem beelogger-Server ([Idee](docs/idee_sendeplan_schwarmalarm.md), [Payload](docs/vorschlag_payload_erweiterung.md)) |

Einzelheiten, offene Punkte und Verlauf: [`docs/planung_otaa_downlink.md`](docs/planung_otaa_downlink.md).
