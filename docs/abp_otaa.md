# ABP oder OTAA

Stand: 09.11.2026 · gilt für: STM32 261107 · geprüft gegen Code: Commit 80673f1


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
| Zählerüberlauf | Uplink-Zähler springt nach 16384 auf 0 (bis Firmware 230711: 16382; bei 5 min ca. alle 57 Tage, bei 15 min ca. alle 170 Tage) → „Resets frame counters“ in TTN dauerhaft nötig | Neu-Join bei FCnt 16000, kein Überlauf |
| DevAddr | fest | neu bei jedem Join (beelogger011: 260BF6FE → 260BE069). Für den beelogger-Server unkritisch: Er bekommt die Daten über Payload-Formatter und Webhook der jeweiligen TTN-App, die DevAddr spielt dabei keine Rolle |

**Downlinks gehen auch mit ABP.** LoRaWAN öffnet nach jedem Uplink zwei Empfangsfenster (Rx1, Rx2), egal ob das Gerät per ABP oder OTAA aktiviert wurde. Die Firmware wertet Downlinks auf FPort 10 in beiden Varianten mit demselben Code aus (`onEvent`, `EV_TXCOMPLETE`). Getestet am 06.11.2026 mit ABP: beelogger09 (Black) bekam `0F`, Log `DL Port 10: 15`, nächste Weckzeit 15 min später (`logs/2026-11-06_beelogger09_downlink_15min.log`); beeloggerblue05 (Blue) ebenso. Dass Downlinks mit ABP oft nicht ankommen, liegt an den TTN-Einstellungen, nicht an ABP: Rx1-Delay und Rx2-Datenrate müssen zur Firmware passen, und alte Geräte haben oft einen hohen Downlink-Zähler (FCntDown) aus der Downlink-Schleife – dann verwirft das Gerät den Downlink. Abhilfe: „Reset session and MAC state“ und Downlink-Zähler auf 0 (siehe [TTN-Anleitung](ttn_einrichten.md)). Die alte Firmware (vor 261106) wertet gar keine Downlinks aus.

**Warum beide:** Die vorhandenen Feldgeräte laufen mit ABP und sollen nach dem Update ohne Neuanlage in TTN weiterlaufen. Neue Geräte sollen OTAA nutzen: sicherer, weniger Fehlerquellen bei den TTN-Einstellungen, und ein Netzausfall wird erkannt. Beides aus einer Codebasis, damit Korrekturen nur einmal nötig sind.

OTAA-Gerät in TTN anlegen: LoRaWAN 1.0.2, RP 1.0.2 rev B, Frequenzplan EU_863_870_TTN, ADR aus. Die Session (DevAddr, Nonces, Rx-Werte, 4 × 32 Bit) liegt bei der BlackPill in RTC-Backup-Registern 10–13, beim BluePill in der letzten Flash-Seite laut Chip (F103CB: 0x0801FC00; geschrieben nur bei Änderung: Join, 1 Link-Check/Tag, Löschen; liegt die Seite im Programm, wird nichts gespeichert). Details: [speicher.md](speicher.md). Sie übersteht den Tiefschlaf; die Sitzungsschlüssel werden daraus neu berechnet und nicht gespeichert. Neu-Join nach Stromausfall (POR), unbekanntem Frame-Zähler, FCnt ≥ 16000 (vor dem Überlauf des 14-Bit-Zählers), ungültiger Prüfsumme der Session oder 3 Tagen ohne Link-Check-Antwort.

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

TTN-Live-Daten als Beispiel: OTAA mit zwei Joins (Join-Request, Join-Accept, erster Uplink FCnt 0) in `ttn/beispiele/2026-11-06_beelogger08_otaa_live_data_join.json` (TTN-Ereignis-API, ohne Schlüssel, Standorte und Gateway-Token). ABP: `ttn/beispiele/` – z. B. `2026-11-06_beelogger09_live_data_2h.json` (Uplinks mit dekodierter Payload) und `…_downlink_15min.json` (Intervall-Downlink). Payload-Decoder: `ttn/payload_decoder_ttn.js`.

