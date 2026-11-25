# Fern-Einstellungen: was per Downlink verstellbar ist

[← Übersicht](README.md)

Stand: 20.11.2026 · gilt für: STM32 261119 (seit Merge auf `main`) ·
geprüft gegen Code: Commit d3b1d87 und der Commit mit dieser Seite („Plan gewinnt“, `S6_PLAN_GILT`); `0B` und Flash-Format `B2` gegen den Commit „Arbeitsschalter-Dauer per FPort 12“
(`src/STM32_sendeplan.ino`, `src/STM32_LORA.ino:745-777`, `src/STM32_beelogger.h:22`)

Diese Seite ist die Übersicht aller Werte, die sich aus der Ferne über TTN verstellen lassen. Format-Details
der älteren Downlinks: [payload.md](payload.md), Wirkung auf die Intervalle: [intervalle.md](intervalle.md).

## Kurzanleitung: Was will ich einstellen?

Gilt nur für F4-Logger (F401/F411) mit Firmware 261119 oder neuer. Alle Downlinks gehen an
**FPort 12** (TTN-Konsole → Gerät → *Messaging* → *Schedule downlink*, Payload in Hex, siehe
[Downlink schicken](#downlink-schicken)). Er kommt erst nach dem nächsten Uplink beim Logger an. Jeder Payload
beginnt mit `01`. Vor dem ersten Plan arbeitet der Logger mit den **Vorgaben** (Spalte „Vorgabe“ in der Tabelle
darunter); wer einen Wert setzt, legt damit einen Plan im Flash an, alle anderen Werte bleiben auf der Vorgabe.

| Ich will … | Payload (Hex) | Wirkt |
|---|---|---|
| alle 10 min senden (Tag und Nacht) | `01 03 01 0A 04 01 0A` | dauerhaft |
| nachts seltener senden (30 min), „Nacht“ = Solarspannung unter 0,5 V | `01 04 01 1E 05 01 0A` | dauerhaft |
| öfter wiegen als senden (alle 2 min wiegen, alle 10 min senden) | `01 01 01 02 03 01 0A` | dauerhaft |
| 6 h lang jede Minute wiegen, alle 5 min senden, danach wieder wie vorher | `01 10 03 01 05 06` | befristet (1–72 h) |
| befristete Änderung sofort beenden | `01 10 03 01 05 00` | sofort |
| Arbeitsschalter höchstens 4 h aktiv lassen | `01 0B 01 F0` | dauerhaft |
| alles auf die Vorgabe zurück | `01 0F 00` | dauerhaft |
| Logger neu starten (mit Neu-Join) | `01 20 03 02 <letzte 2 Byte der DevAddr>` | einmalig |

Wichtig:

- **„Nacht“ bedeutet hier: zu wenig Licht**, gemessen an der Solarspannung. Es gibt keine Uhrzeiten. Solange
  Kommando `05` auf `0` steht (Vorgabe), ist immer „Tag“, und die Nacht-Werte `02`/`04` wirken nie.
- Werte außerhalb des Bereichs oder falsche Länge: der **ganze** Downlink wird verworfen, nichts ändert sich.
- Wer sich verrechnet hat: `01 0F 00` stellt die Vorgabe wieder her.

### Gemessene Solarspannung nachts (Grundlage für die Grenze `05`)

Messung 20.11.2026, Server-CSV `Quad<N>/beelogger.csv` auf dem eigenen Server, alle Werte vom 01.11. bis 20.11.2026 09:02,
Mittel je Stunde. Spalte: Solarspannung nach der Server-Formel `x[10+(MultiType-1)*3]` bei 4 Waagen
(`beelogger_show.php`, Zeilen 880–890; Akku davor, Spalte 18). Nur November, Sommerwerte fehlen.

| Standort (Quad) | Dunkelheit (Stunden 18 bis 5), Mittel | Dunkelheit, höchster Wert | 06 Uhr, höchster Wert | Mittag 12 Uhr, Mittel |
|---|---|---|---|---|
| Q1 | 0,03 V | 0,09 V | 0,46 V | 5,34 V |
| Q2 | 0,04–0,05 V | 0,85 V (einmal) | 0,20 V | 4,82 V |
| Q5 (Straßenlaterne in der Nähe) | 0,05 V | 0,08 V | 0,40 V | 4,67 V |
| Q6 | 0,08 V | 0,10 V | 0,50 V | 5,29 V |
| Q7 | 0,04 V | 0,06 V | 0,29 V | 4,44 V |

- Nachts liegt die Solarspannung überall unter 0,1 V; die Straßenlaterne bei Q5 ändert nichts.
- Morgens (06 Uhr) steigt sie auf höchstens 0,5 V, ab 07 Uhr liegt der Mittelwert über 1,4 V.
- Q2 hatte nachts zwei einzelne Ausreißer (19.11. 19:11 mit 0,85 V, 23:06 mit 0,18 V, je ein Messwert). Ursache unbekannt,
  vermutet: Scheinwerfer eines Autos im Moment der Messung. Mit Grenze 0,5 V (`05` = `0A`) würde so ein Wert einmal „Tag“ ergeben.
- Am 01. und 02.11. lag Q2 gegen 18 Uhr in der Dämmerung kurz bis 0,19 V.
- Ergebnis: Grenze 0,5 V trennt Tag und Nacht an allen fünf Ständen sauber; die Messung muss nicht wiederholt werden, außer mit Sommerwerten.
- Beim Neustart prüft der Logger die DevAddr (TTN-Konsole: *Device address*), damit kein falsches Gerät
  neu startet.
- Ohne Plan (F103, `main`, oder noch nie ein Plan gesetzt) bleibt es beim alten Ablauf, siehe
  [Nach welchen Regeln der Logger sendet](#nach-welchen-regeln-der-logger-sendet).

Die technischen Einzelheiten (alle Kommandos, Format, Rangfolge, Messungen) stehen ab hier.

## Auf einen Blick

| Was | Downlink | Bereich | Vorgabe ohne Plan (Config dieses Repos) | Speicher | Auf `main`? |
|---|---|---|---|---|---|
| Festes Sendeintervall | FPort 10, 1 Byte | 5–180 min, `0` = Firmware-Vorgabe (`src/STM32_LORA.ino:755`) | `0` = Firmware-Vorgabe | Backup-Register | ja |
| Uhr stellen + Intervall | FPort 11, 7 Byte (automatisch vom Server) | Intervall 5–60 min (`src/STM32_LORA.ino:772`) | – | RTC / Backup-Register | ja |
| Wiegeabstand Tag / Nacht | FPort 12, `01` / `02` | 1–60 min (`src/STM32_sendeplan.ino:57`) | 5 min (`WeckIntervall`) | Flash | ja |
| Sendeabstand Tag / Nacht | FPort 12, `03` / `04` | 5–180 min, Raster 5 (`src/STM32_sendeplan.ino:58`) | 5 min (`WeckIntervall`) | Flash | ja |
| Grenze Tag/Nacht (Solarspannung) | FPort 12, `05` | 0–255 × 0,05 V, `0` = immer Tag (`src/STM32_sendeplan.ino:144`) | `0` = immer Tag | Flash | ja |
| Winter-Temperatur | FPort 12, `06` | 0,5-°C-Schritte mit Vorzeichen, `80` = aus (`src/STM32_sendeplan.ino:232`) | `80` = aus | Flash | ja |
| Sendeabstand Winter | FPort 12, `07` | 5–180 min, Raster 5 (`src/STM32_sendeplan.ino:58`) | 60 min (`WeckIntervall_Winter`) | Flash | ja |
| Schwelle Sofort-Senden (Gewichtssprung) | FPort 12, `08`, 2 Byte | `0` = aus, 50–65 535 g (`src/STM32_sendeplan.ino:252`) | `0` = aus | Flash | ja |
| Akkuspar-Schwelle | FPort 12, `09` | × 0,05 V, mindestens `VMinimum` + 0,05 V (`src/STM32_sendeplan.ino:59`) | 3,8 V (`VAlternativ`) | Flash | ja |
| Sendeabstand im Akkusparmodus | FPort 12, `0A` | 30–240 min, Raster 5 (`src/STM32_sendeplan.ino:60`) | 120 min (`AlternativIntervallMinuten`) | Flash | ja |
| Höchstdauer Arbeitsschalter (Pause bei dauernd eingeschaltetem Schalter) | FPort 12, `0B` | 5–240 min, Raster 5 (`src/STM32_sendeplan.ino:61`); ohne Flash-Plan `USER_INT_TM`, gekappt auf 150 (`src/STM32_sendeplan.ino:15`) | Config `USER_INT_TM` (180), gekappt auf 150 min | Flash | ja; Hardware-Test 19.11.2026 bestanden |
| Plan auf Firmware-Vorgabe zurück | FPort 12, `0F` | – (`src/STM32_sendeplan.ino:254`) | – | Flash-Eintrag gelöscht | ja |
| Für eine Weile anders (Wiegen, Senden, Dauer) | FPort 12, `10`, 3 Byte | 1–60 min, 5–180 min, 0–72 h (`src/STM32_sendeplan.ino:258`) | – | Backup-Register | ja |
| Neustart / Neustart + Neu-Join | FPort 12, `20`, 3 Byte | Art `01`/`02` + Prüfwert DevAddr (`src/STM32_sendeplan.ino:261`) | – | – | ja |
| Status senden | FPort 12, `30` | reserviert, bewirkt nur die Quittung (`src/STM32_sendeplan.ino:264`) | – | – | ja |

**Nicht per Downlink verstellbar** (entschieden 19.11.2026): Waagenkonfiguration (Tara, Kalibrierfaktor),
weil Kalibrieren ein Prüfgewicht am Stand braucht. Ebenso nicht: `VMinimum`, Sensorauswahl, LoRa-Schlüssel –
alles in `Config_LORA.h`/`Config_MULTI.h`, also nur per Flashen.

**FPort 12 gibt es nur auf F4-Loggern** (F401/F411, `SENDEPLAN 1` in `src/STM32_beelogger.h:22`). Auf dem
F103 sind die Backup-Register belegt, dort bleibt nur FPort 10/11 (`src/STM32_beelogger.h:24`). Der Flash-Plan
braucht mindestens 256 KB Flash (`src/STM32_sendeplan.ino:87`).

## Welcher FPort wofür

| Richtung | FPort | Wer schickt | Wofür | Beleg |
|---|---|---|---|---|
| Uplink | 1 | Logger | Messwerte (Format: [payload.md](payload.md)) | `src/STM32_LORA.ino:433` |
| Downlink | 10 | TTN-Konsole/API oder `server/lora_downlink.php` | festes Sendeintervall | `src/STM32_LORA.ino:755` |
| Downlink | 11 | `server/lora_downlink.php` (automatisch) | Uhr stellen + Intervall vom Server | `src/STM32_LORA.ino:772` |
| Downlink | 12 | TTN-Konsole/API (von Hand) | Sendeplan, Kommandos `01`–`30` (Tabelle oben) | `src/STM32_sendeplan.ino:242-267` |

Downlinks kommen nur nach einem Uplink an (LoRaWAN Class A), siehe [Downlink schicken](#downlink-schicken).

## Nach welchen Regeln der Logger sendet

Es gibt zwei Fälle, je nachdem, ob ein Plan im Flash liegt.

**Mit Flash-Plan** (FPort 12, nur F4):

| Regel | Woran erkannt | Beleg |
|---|---|---|
| Tag/Nacht | Solarspannung unter der Grenze `05` = Nacht, nicht die Uhrzeit | `src/STM32_sendeplan.ino:144` |
| Winter | Temperatur DS18B20 unter `06` = Winter, nicht der Monat | `src/STM32_sendeplan.ino:232` |
| Akku schwach | Spannung unter `09` → Sendeabstand `0A` | `src/STM32_sendeplan.ino:178-184` |
| Für eine Weile anders | Kommando `10`, gilt ab Empfang für 1–72 h, dann wieder Plan | `src/STM32_sendeplan.ino:258` |
| Gewichtssprung | Sofort-Senden ab Schwelle `08` | `src/STM32_sendeplan.ino:205` |

Nach Uhrzeit, Kalender oder zu festen Zeitpunkten (z. B. 7, 12 und 18 Uhr) sendet der Plan **nicht**. Die Uhr
aus FPort 11 wird trotzdem gestellt (`src/STM32_LORA.ino:772`), eine Erweiterung wäre also möglich (siehe
[Möglichkeiten](#möglichkeiten-nicht-geplant)).

**Ohne Flash-Plan** (auch F103 und `main`): Es gilt der alte Ablauf aus `Config_LORA.h`. Nacht nach Uhrzeit
(`WeckIntervall_Nacht_Anfang`/`_Ende`), Winter nach Monat und Temperatur (`src/STM32_LORA_utility.ino:363-386`).
Diese Werte sind beim Bauen fest. Aus der Ferne wirkt nur FPort 10/11; der Server rechnet dafür sein Intervall
nach Uhrzeit, Licht oder Solarspannung (`ServerZeitSteuerung()`, siehe [server.md](server.md)).

## Was starr bleibt

Diese Werte stehen in `Config_LORA.h`/`Config_MULTI.h` und ändern sich nur durch Flashen:

| Wert | Wo | Per Downlink sinnvoll? |
|---|---|---|
| `VMinimum` (Abschaltspannung) | `src/Config_MULTI.h:96` | nein, entschieden 19.11.2026: ein Tippfehler kann den Akku tiefentladen |
| Tara und Skalierung der Waagen | `src/Config_MULTI.h:167-168` | nein, entschieden 19.11.2026: Kalibrieren braucht ein Prüfgewicht am Stand. Denkbar wäre nur, eine kaputte Waage abzuschalten (`Taragewicht = 10` heißt „aus“, `src/beelogger_HX711_STM.ino:31`) |
| Spannungsteiler (`Kalib_Spannung`, `Kalib_Bitwert`) | `src/Config_MULTI.h:169-170` | eher nein, Abgleich braucht ein Messgerät am Logger |
| Sensoren: Anzahl je Typ und Zuordnung (`Aussenwerte`, `Beute1`–`4`, `Temp_Zelle`) | `src/Config_MULTI.h:11-43` | ja, siehe [Möglichkeiten](#möglichkeiten-nicht-geplant) |
| `Korrektur_Luftdruck` | `src/Config_MULTI.h:50` | nicht nötig, der Server korrigiert selbst (`$KorrPressure`, `M41_261110a_ServerSkripte/beelogger1/beelogger_log.php:596`) |
| `Alarm_Gewicht` (1 kg) | `src/STM32_beelogger.h:30` | nicht nötig: Bei LoRa wird der Schwarmalarm der Firmware nicht ausgewertet (`src/STM32_LORA_260705.ino:210-241`), den Gewichtssprung deckt `08` ab, den Schwarmalarm macht der Server |
| LoRa-Schlüssel, `LOGGER_NAME`, Kanäle | `src/Config_LORA.h` | nein, Sicherheit |

Alle Intervalle (`WeckIntervall*`), `VAlternativ`, `AlternativIntervallMinuten` und `USER_INT_TM` gelten nur,
solange kein Flash-Plan da ist. Mit Plan ersetzen sie `01`–`0B` (`src/STM32_sendeplan.ino:73-82`).

## Möglichkeiten (nicht geplant)

Keine Aufgaben, nur festgehalten, was das Format zulassen würde (unbekannte Kommandos überspringt der Logger,
`src/STM32_sendeplan.ino:242-267`):

- **Sensor-Zuordnung per Downlink:** Alle Sensortreiber fest einbauen und per Kommando (z. B. `0C`) wählen,
  welcher Sensor welchen Wert liefert. Beispiel: BME280 außen defekt, DS18B20 nachgerüstet (an PA8 mit Pull-up),
  ein Downlink stellt „außen“ auf den DS18B20 um. I2C-Sensoren und DS18B20 ließen sich auch suchen, ein DHT22 nicht.
  Payload und Server bleiben gleich. Ungeprüft, ob der Platz bei 4 Waagen reicht (q2 heute 71 984 Byte).
- **Senden nach Uhrzeit oder Kalender im Plan:** feste Zeitpunkte oder Nacht/Winter nach Uhr und Monat
  statt nach Solarspannung und Temperatur.
- **Sendeplan auf der Blue Pill (F103):** heute aus, weil der Flash unter 256 KB liegt und die
  Backup-Register belegt sind (`src/STM32_beelogger.h:21-25`, `src/STM32_sendeplan.ino:87`). Ginge nur mit
  einem Plan in einer 1-KB-Flash-Seite und ohne Backup-Register.

## FPort 12: Aufbau

Byte 0 = Format-Version `01`, danach beliebig viele Blöcke `Kommando · Länge · Daten`
(`src/STM32_sendeplan.ino:242-267`). Unbekannte Kommandos überspringt der Logger über ihre Länge, so bleibt
das Format für spätere Firmware erweiterbar.

Regeln (alle aus `src/STM32_sendeplan.ino:237-313`):

- **Erst alles prüfen, dann ausführen.** Ist eine Länge falsch oder ein Wert außerhalb seines Bereichs, wird
  der **ganze** Downlink verworfen (seriell „DL Port 12: verworfen“), ohne Quittung. Gemessen 19.11.2026 auf
  beelogger08 (Tests T3, T4).
- **Einzelwerte gehen:** Nicht genannte Werte bleiben, wie sie sind. Beispiel nur Sendeabstand Tag 10 min:
  `01 03 01 0A`.
- **Ausgeführt nach dem Uplink** (`src/STM32_sendeplan.ino:279`). Neue Werte gelten ab dem nächsten Lauf; die
  Weckzeit direkt nach dem Downlink ist aber noch nach altem Plan gerechnet, der neue Abstand zeigt sich also
  ab dem zweiten Aufwachen (gemessen 19.11.2026, T5).
- **Flash nur bei Änderung**, danach Rücklesen und Prüfsumme (`src/STM32_sendeplan.ino:289-296`). Klappt das
  nicht, gilt wieder der vorige Inhalt bzw. `Config_LORA.h`, ohne Quittung.
- **Im Akkusparmodus** wird kein Plan geschrieben. Enthält der Downlink eine Plan-Änderung, verfällt er dann
  ganz, auch ein mitgeschicktes `10` oder `20` (`src/STM32_sendeplan.ino:288`).
- **Quittung:** Im nächsten Uplink steht Service = 90 (`src/STM32_sendeplan.ino:313`, `:218`), außer der
  Arbeitsschalter ist an (dann bleibt Service 99). Gemessen 19.11.2026 im Server-CSV von Quad8.
- Ein angenommener Plan-Wert setzt ein altes festes Intervall aus FPort 10/11 auf 0 zurück
  (`src/STM32_sendeplan.ino:304`).
- Ein kompletter Plan (`01`–`0B`) hat 35 Byte und passt auch bei SF12 (51 Byte in EU868).
- Schon ein einzelner Plan-Wert (auch nur `0B`) legt einen Flash-Plan an; die übrigen Werte sind dann die
  Firmware-Vorgabe (`src/STM32_sendeplan.ino:73-82`), und FPort 10/11 setzen ab da kein Intervall mehr (siehe Rangfolge).

### Kommando 10: für eine Weile anders

`10 03 <Wiegen min> <Senden min> <Dauer h>`. Danach gilt wieder der Plan. Dauer `0` beendet ein laufendes
Kommando 10 sofort (`src/STM32_sendeplan.ino:306`). Es liegt in den Backup-Registern und übersteht deshalb
Aufwachen und Reset-Taste (gemessen 19.11.2026: nach „RST“ weiter „K10 1/5“). Beendet wird es außerdem durch
Kommando 20 (`src/STM32_sendeplan.ino:318`) und vermutlich durch Akku ab ohne Pufferbatterie (ungeprüft). Gemessen 19.11.2026: `01 10 03 01 05 01` → seriell „K10 1/5“, Uplinks alle
5 min, Quittung 90. Ablauf nach 1 h gemessen 19.11.2026 (beelogger08, Seriell-Log USB2): bis 18:05:19 „K10 1/5“
(Wiegen jede Minute), ab 18:06:19 „K10 0/0“ und wieder der Plan: Wiegen alle 2 min (18:06, 18:08, 18:10, 18:12),
Uplink 18:12:24, also beim ersten Wiegen mindestens 10 min nach dem letzten Uplink (18:01:24;
`s6_senden_faellig()`, `src/STM32_sendeplan.ino:195-199`).

### Kommando 20: Neustart

`20 03 <Art> <DevAddr Byte 3> <DevAddr Byte 4>`. Art `01` = Neustart, `02` = Neustart, OTAA-Session
verwerfen und neu joinen (bei ABP wie `01`). Ausgeführt wird nur, wenn die letzten 2 Byte der DevAddr passen
(TTN-Konsole: *Device address*). Der Neustart läuft erst nach dem Uplink. Der Lauf danach misst und sendet
nicht, der nächste Uplink meldet Service 0, ein laufendes Kommando 10 endet (`src/STM32_sendeplan.ino:314-327`).
Gemessen 19.11.2026 auf beelogger08: `20/01` und `20/02` bestanden (Neustart, Neu-Join mit Plan danach weiter gültig).

## Rangfolge im Logger

Höchste zuerst (`src/STM32_sendeplan.ino:177-184`):

1. Arbeitsschalter (Service-Modus) – jedes Aufwachen sendet (`src/STM32_sendeplan.ino:197`); wie lange die
   Pause bei dauernd eingeschaltetem Schalter höchstens dauert, stellt `0B` ein (`src/STM32_LORA_utility.ino:272-277`)
2. Akkusparmodus – Sendeabstand `0A`
3. Kommando 10 („für eine Weile anders“)
4. Festes Intervall aus FPort 10/11 – **nur ohne gültigen Flash-Plan**: Mit Plan setzen FPort 10/11 kein
   Intervall mehr, die Uhr aus FPort 11 gilt trotzdem (`src/STM32_LORA.ino:755`, `:772`; entschieden
   19.11.2026, damit die täglichen Server-Downlinks den Plan nicht verdecken)
5. Plan aus dem Flash (Winter, sonst Tag/Nacht nach Solarspannung)
6. Ohne Flash-Plan: alter Code-Pfad wie 261107, Wiegen = Senden = `WeckIntervall` (`src/STM32_sendeplan.ino:73-82`)

Sofort-Senden prüft der Logger nur beim Wiegen, frühestens 5 min nach dem letzten Uplink
(`src/STM32_sendeplan.ino:205`, wegen der Server-Sperre von 220 s, siehe [server.md](server.md)).

## Downlink schicken

TTN-Konsole → *End devices* → Gerät → *Messaging* → *Schedule downlink*: FPort `12`, Payload hex. Er kommt
erst beim nächsten Uplink an, also je nach Sendeabstand bis zu 180 min später.

Beispiele:

| Zweck | Payload |
|---|---|
| Wiegen Tag 2 min, Senden Tag 10 min (gemessen 19.11.2026, T7) | `01 01 01 02 03 01 0A` |
| Wiegen 5 min, Senden Tag/Nacht 10 min, Sofort ab 500 g | `01 01 01 05 02 01 05 03 01 0A 04 01 0A 08 02 01 F4` |
| 6 h lang jede Minute wiegen, alle 5 min senden | `01 10 03 01 05 06` |
| Kommando 10 sofort beenden | `01 10 03 01 05 00` |
| Arbeitsschalter höchstens 240 min (4 h) | `01 0B 01 F0` |
| Plan zurück auf Firmware-Vorgabe (gemessen 19.11.2026, T6) | `01 0F 00` |
| Neustart mit Neu-Join, DevAddr endet auf `…3F A1` | `01 20 03 02 3F A1` |

**Achtung beim Server-Downlink:** `server/lora_downlink.php` muss Downlinks einreihen (`x-downlink-push`),
nicht ersetzen (`x-downlink-replace`), sonst löscht er einen wartenden FPort-12-Downlink
(`server/lora_downlink.php:76`).

**Akku-Sparmodus:** Ändert ein FPort-12-Downlink den Plan, während der Akku-Sparmodus aktiv ist, verwirft die Firmware den ganzen Downlink, auch ein mitgeschicktes Kommando 10 oder 20 (`src/STM32_sendeplan.ino:288`, seriell „S6: Sparmodus, Plan nicht geschrieben“). Den Downlink nach Ende des Sparmodus neu schicken.

## Speicher und Flashen

Der Plan liegt im letzten 128-KB-Flash-Sektor (F411: Sektor 7, `src/STM32_sendeplan.ino:85-93`) und übersteht
Akkuwechsel. **Ein Löschen des ganzen Chips löscht auch den Plan**, z. B. `stm32loader -e`. Details:
[speicher.md](speicher.md).

## Teststand

Gemessen 19.11.2026 auf beelogger08 (F411, Testlogger): Plan setzen, Reset (Plan bleibt), ungültiger Wert,
Längenfehler, unbekanntes Kommando, Plan löschen `0F`, Plan neu setzen, Kommando 10 – alle bestanden.
Bestanden außerdem: Neustart `20/01`, Neu-Join `20/02`, FPort 10 bei aktivem Plan wirkungslos, `0B` 240 min, Akku ab (Plan bleibt), Arbeitsschalter 240 min real (20.11.2026). **Noch ungeprüft auf Hardware:** `05`, `06`/`07` (Winter), `08` (Sofort-Senden), `09`/`0A` (Akkusparmodus), Waagen-Pfad, Strom je Wiegung.

### Verbrauch bei minütlichem Wiegen (Akkutest 23.–25.11.2026)

Messung Seriell-Log (Zeitraum 23.11.2026 20:11 bis 25.11.2026 22:02), Wachstrom 24–34 mA aus [hardware.md](hardware.md) (gemessen 06.11.2026), Verbrauch = Wachzeit × Wachstrom:

| Logger | Plan | Wachzeit je Lauf | Wachzeit/Tag | Verbrauch/Tag |
|---|---|---|---|---|
| beelogger08 (Testlogger, ohne Wägezellen) | 1 min wiegen, 180 min senden (`01 10 03 01 B4 48`) | 3 s ohne Senden, 5–6 s mit Senden | ca. 4340 s | 29–41 mAh |
| altes Q5-Board (alte Firmware) | alle 5 min, jedes Mal senden | 20–23 s | ca. 5880 s | 39–56 mAh (Wachstrom dieses Boards nicht gemessen) |

- beelogger08: 2988 Wachläufe in 50 h, alle mit sauberem Ende (kein Power-On-Reset, keine Lücke).
- Feld-Logger Q5 braucht mit Senden 8–11 s (Spalte „Service“ auf majkic, 25.11.2026). Minütliches Wiegen im Feld wäre damit **geschätzt** 52–74 mAh/Tag gegen heute ca. 15–22 mAh/Tag (**ungeprüft**, Schätzung).
- Die Akkuspannung taugt nicht als Beleg: beide Boards hingen am Solarmodul.
- Nebenbefund: Die Uhr von beelogger08 geht ca. 15 s/Tag vor (Sendezeit 20:21:06 → 20:20:35 nach 48 h).
