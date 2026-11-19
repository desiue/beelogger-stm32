# Fern-Einstellungen: was per Downlink verstellbar ist

[← Übersicht](README.md)

Stand: 19.11.2026 · gilt für: STM32 261119, **nur Branch `s6-sendeplan`** (noch nicht auf `main`) ·
geprüft gegen Code: Commit d3b1d87 und der Commit mit dieser Seite („Plan gewinnt“, `S6_PLAN_GILT`); `0B` und Flash-Format `B2` gegen den Commit „Arbeitsschalter-Dauer per FPort 12“
(`src/STM32_sendeplan.ino`, `src/STM32_LORA.ino:745-777`, `src/STM32_beelogger.h:22`)

Diese Seite ist die Übersicht aller Werte, die sich aus der Ferne über TTN verstellen lassen. Format-Details
der älteren Downlinks: [payload.md](payload.md), Wirkung auf die Intervalle: [intervalle.md](intervalle.md).

## Auf einen Blick

| Was | Downlink | Bereich | Speicher | Auf `main`? |
|---|---|---|---|---|
| Festes Sendeintervall | FPort 10, 1 Byte | 5–180 min, `0` = Firmware-Vorgabe (`src/STM32_LORA.ino:755`) | Backup-Register | ja |
| Uhr stellen + Intervall | FPort 11, 7 Byte (automatisch vom Server) | Intervall 5–60 min (`src/STM32_LORA.ino:772`) | RTC / Backup-Register | ja |
| Wiegeabstand Tag / Nacht | FPort 12, `01` / `02` | 1–60 min (`src/STM32_sendeplan.ino:57`) | Flash | nein, nur Branch |
| Sendeabstand Tag / Nacht | FPort 12, `03` / `04` | 5–180 min, Raster 5 (`src/STM32_sendeplan.ino:58`) | Flash | nein, nur Branch |
| Grenze Tag/Nacht (Solarspannung) | FPort 12, `05` | 0–255 × 0,05 V, `0` = immer Tag (`src/STM32_sendeplan.ino:144`) | Flash | nein, nur Branch |
| Winter-Temperatur | FPort 12, `06` | 0,5-°C-Schritte mit Vorzeichen, `80` = aus (`src/STM32_sendeplan.ino:232`) | Flash | nein, nur Branch |
| Sendeabstand Winter | FPort 12, `07` | 5–180 min, Raster 5 (`src/STM32_sendeplan.ino:58`) | Flash | nein, nur Branch |
| Schwelle Sofort-Senden (Gewichtssprung) | FPort 12, `08`, 2 Byte | `0` = aus, 50–65 535 g (`src/STM32_sendeplan.ino:252`) | Flash | nein, nur Branch |
| Akkuspar-Schwelle | FPort 12, `09` | × 0,05 V, mindestens `VMinimum` + 0,05 V (`src/STM32_sendeplan.ino:59`) | Flash | nein, nur Branch |
| Sendeabstand im Akkusparmodus | FPort 12, `0A` | 30–240 min, Raster 5 (`src/STM32_sendeplan.ino:60`) | Flash | nein, nur Branch |
| Höchstdauer Arbeitsschalter (Pause bei dauernd eingeschaltetem Schalter) | FPort 12, `0B` | 5–240 min, Raster 5 (`src/STM32_sendeplan.ino:61`); ohne Flash-Plan `USER_INT_TM`, gekappt auf 150 (`src/STM32_sendeplan.ino:15`) | Flash | nein, nur Branch; ungeprüft auf Hardware |
| Plan auf Firmware-Vorgabe zurück | FPort 12, `0F` | – (`src/STM32_sendeplan.ino:254`) | Flash-Eintrag gelöscht | nein, nur Branch |
| Für eine Weile anders (Wiegen, Senden, Dauer) | FPort 12, `10`, 3 Byte | 1–60 min, 5–180 min, 0–72 h (`src/STM32_sendeplan.ino:258`) | Backup-Register | nein, nur Branch |
| Neustart / Neustart + Neu-Join | FPort 12, `20`, 3 Byte | Art `01`/`02` + Prüfwert DevAddr (`src/STM32_sendeplan.ino:261`) | – | nein, nur Branch |
| Status senden | FPort 12, `30` | reserviert, bewirkt nur die Quittung (`src/STM32_sendeplan.ino:264`) | – | nein, nur Branch |

**Nicht per Downlink verstellbar** (entschieden 19.11.2026): Waagenkonfiguration (Tara, Kalibrierfaktor),
weil Kalibrieren ein Prüfgewicht am Stand braucht. Ebenso nicht: `VMinimum`, Sensorauswahl, LoRa-Schlüssel –
alles in `Config_LORA.h`/`Config_MULTI.h`, also nur per Flashen.

**FPort 12 gibt es nur auf F4-Loggern** (F401/F411, `SENDEPLAN 1` in `src/STM32_beelogger.h:22`). Auf dem
F103 sind die Backup-Register belegt, dort bleibt nur FPort 10/11 (`src/STM32_beelogger.h:24`). Der Flash-Plan
braucht mindestens 256 KB Flash (`src/STM32_sendeplan.ino:87`).

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
5 min, Quittung 90.

### Kommando 20: Neustart

`20 03 <Art> <DevAddr Byte 3> <DevAddr Byte 4>`. Art `01` = Neustart, `02` = Neustart, OTAA-Session
verwerfen und neu joinen (bei ABP wie `01`). Ausgeführt wird nur, wenn die letzten 2 Byte der DevAddr passen
(TTN-Konsole: *Device address*). Der Neustart läuft erst nach dem Uplink. Der Lauf danach misst und sendet
nicht, der nächste Uplink meldet Service 0, ein laufendes Kommando 10 endet (`src/STM32_sendeplan.ino:314-327`).
Ungeprüft auf Hardware (Test steht aus).

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
(`server/lora_downlink.php:76`, nur Branch).

## Speicher und Flashen

Der Plan liegt im letzten 128-KB-Flash-Sektor (F411: Sektor 7, `src/STM32_sendeplan.ino:85-93`) und übersteht
Akkuwechsel. **Ein Löschen des ganzen Chips löscht auch den Plan**, z. B. `stm32loader -e`. Details:
[speicher.md](speicher.md).

## Teststand

Gemessen 19.11.2026 auf beelogger08 (F411, Testlogger): Plan setzen, Reset (Plan bleibt), ungültiger Wert,
Längenfehler, unbekanntes Kommando, Plan löschen `0F`, Plan neu setzen, Kommando 10 – alle bestanden.
Noch offen: Ablauf von Kommando 10, Neustart `20/01`, Neu-Join `20/02`, FPort 10 bei aktivem Plan,
Akku ab, Sofort-Schwelle `08`, Strom je Wiegung.
