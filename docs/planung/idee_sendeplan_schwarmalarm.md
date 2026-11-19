# Idee: Sendeplan, Schwarmalarm, was aus der Ferne verstellbar sein soll

[← Übersicht](../README.md)

Stand 07.11.2026, ergänzt 19.11.2026 (Variante 10 min senden / 5 min wiegen), **zur Diskussion**, nichts davon ist umgesetzt.

**Antwort beelogger-Team (07.11.):** LoRa sendet ohnehin mindestens alle 10 min, forciertes Senden braucht es nicht. Öfter bringt nichts: Der Server ignoriert kurze Abstände (Okt–März 400 s; *Stand Code M41: 220 s, auf community.beelogger.de Okt–Feb 520 s, `beelogger_log.php` Z. 573–574, vom Team am 18.11. bestätigt*), und die Schwarmlogik ist auf den 10-min-Takt ausgelegt (Windböen). Dabei bleibt es. **Status: nicht für `main`**, höchstens als Experiment im eigenen Branch mit eigenem Server.

**Einordnung (07.11.):** `main` bleibt maximal kompatibel zum Original-beelogger und zum Community-Server. Was hier beschrieben ist und Daten oder Verhalten am Server berührt (Zusatz-Uplinks, Service-Quittung, eigener Sendeplan), wird zuerst in einem **eigenen Branch** gebaut und auf einem eigenen beelogger-Server getestet. In `main` kommt es nur, wenn die Entwickler es ausdrücklich wollen. **Ergänzung 19.11.:** Reine Parameteränderungen (Intervalle) brauchen keinen eigenen Branch, die Original-Firmware hat schon einstellbare Zeiten (Nutzer 19.11.).

**Korrektur 07.11. (nach Lesen der Server-Skripte M40_261004):** Die frühere Annahme „ein Zusatz-Uplink ab 200 s wird gespeichert“ war falsch. Der Server stempelt jede Meldung mit seiner eigenen Uhr, **auf die Minute abgerundet**. Sicher gespeichert wird erst ab **240 s** Abstand, wir planen mit **250 s**. Außerdem hat der Server schon eine eigene Sendeintervall-Steuerung, die bei LoRa nur nicht beim Logger ankommt (Abschnitt „Was die Firmware heute schon kann“).

## Überblick und offene Aufgaben (Stand 19.11.2026)

Roter Faden für alles zu Sendeplan, Schwarm- und Diebstahlerkennung. Die Abschnitte darunter sind die Einzelheiten dazu.

### Ziele

1. **Schwarmalarm:** Gewichtsverlust schnell melden. Der Server alarmiert bei Verlust über `DifferenzGewicht` innerhalb `ReferenzZeit` (M40 `beelogger_log.php` Z. 927–955).
2. **Diebstahl- und Manipulationsschutz:** Völker werden umgeworfen oder gestohlen, vor allem nachts. Bienenstände wurden schon gestohlen (Nutzer 19.11.). Die schnelle Erkennung muss deshalb auch nachts laufen, nicht nur in der Schwarmzeit (Nutzer 19.11.).
3. **Sparen:** seltener senden (Akku, Funkzeit, Speicherplatz am Server), ohne die schnelle Erkennung zu verlieren (Nutzer 19.11.).
4. **Nur einmal flashen:** Die Feld-Logger stehen verteilt an mehreren Ständen. Was später verstellbar sein soll, muss die Firmware beim nächsten Feld-Flash schon können, damit danach Downlinks reichen (Nutzer 19.11.).

### Was die Firmware heute kann

- Jede Wiegung ist ein Uplink, Wiegen und Senden sind nicht getrennt.
- Per Downlink (FPort 10) ist nur der Sendeabstand verstellbar: 5–180 min im 5-min-Raster (`src/STM32_LORA.ino` Z. 750). Unter 5 min sperrt die Firmware (`src/STM32_LORA_utility.ino` Z. 387).
- Eine Wiegung alle Minute oder Senden bei Gewichtsänderung braucht also neue Firmware und damit einen Feld-Flash.

### Varianten

| Variante | Wiegen | Senden regulär | Senden bei Änderung | Erkennung nach | Offene Punkte |
|---|---|---|---|---|---|
| A (heute) | 5 min | 5 min | – | ≤ 5 min | – |
| B | 5 min | 10 min | sofort | ≤ 5 min | S1, S2, S5, S6 |
| C | 5 min | tags 10 min, nachts 15 min | sofort | ≤ 5 min | S1, S2, S5, S6 |
| D (Diebstahl-Test) | 1 min | 10–15 min | sofort | ≤ 1 min | S1–S6 |

Bei B–D kann ein Sofort-Uplink kurz nach dem regulären kommen. Der Server ignoriert kurze Abstände (siehe Kopf: 220 s, auf community.beelogger.de Okt–Feb 520 s, `beelogger_log.php` Z. 573–574). Bei B–D kommen die Zeilen außerdem unregelmäßig an, was die beelogger-App betrifft (S1).

### Offene Aufgaben

| Nr. | Aufgabe | Wo | Nötig für | Stand |
|---|---|---|---|---|
| S1 | **App bewerten:** Ist die Auswirkung (Tabelle unten) schlimm, oder lässt sich die App anpassen (Zeitabstände statt Zeilen zählen)? | App-Projekt `beelogger_app`, übergeben 19.11. | Wahl B/C/D | offen |
| S2 | **Akku messen:** Ladung je Wiegung ohne Uplink und je Wiegung mit Uplink (Multimeter am Testlogger), daraus der Tagesbedarf von A–D. Erwartung Nutzer: seltener senden schont den Akku (ungeprüft). | Testlogger | Wahl der Variante | offen |
| S3 | **Solarstrom im Sommer** für Wiegen jede Minute (Abschnitt „Offene Frage: Reicht im Sommer der Strom …“) | Testlogger | D | offen |
| S4 | **Diebstahl-Test:** jede Minute wiegen, nur bei Änderung senden | Testlogger, eigener Server | D | offen |
| S5 | **Server:** Verwirft beelogger.de die Sofort-Uplinks? Wenn ja, nimmt der eigene Server sie an (Sperre dort für diese Quads anpassen), der Community-Server bleibt unverändert | eigener Server | B–D | offen |
| S6 | **Firmware:** Wiegen und Senden trennen, alles per Downlink verstellbar: Wiegeabstand (ab 1 min), Sendeabstand Tag/Nacht, Grenze Tag/Nacht, Schwelle für Sofort-Senden. Vorgabe = heutiges Verhalten (A). Format und Bauschritte: „Entwurf Kommando-Format FPort 12“ und „Plan S6“ unten. | Repo, Testlogger | Feld-Flash | Entwurf 19.11., nicht gebaut |
| S7 | **Feld-Flash erst nach S6** (auf Testloggern geprüft), damit die Feld-Logger nur einmal geflasht werden | Nutzer | Feld-Flash | entschieden 19.11. (Nutzer): erst nach S6, bis dahin laufen die Feld-Logger unverändert |
| S9 | **Weitere Fern-Einstellungen per eigenem Downlink-Kommando:** Was soll außer Wiegen/Senden noch verstellbar sein? Kandidaten: Tabelle im Abschnitt „Was soll dann noch aus der Ferne verstellbar sein?“, Waagenkonfiguration (Nutzer 06.11.). Muss vor S6 feststehen: Das Kommando-Format kommt mit dem einen Feld-Flash ins Feld und muss erweiterbar sein. | Gespräch mit Nutzer | S6, Feld-Flash | entschieden 19.11. (Nutzer), siehe „Ergebnis S9“ unten |
| S8 | **Branch:** Parameteränderungen (Intervalle) brauchen keinen eigenen Branch (Nutzer 19.11.), Absatz „Einordnung“ oben ergänzt | – | – | erledigt 19.11. |

### Ergebnis S9: Fern-Kommandos der neuen Firmware (entschieden 19.11.2026)

Der Nutzer hat jeden Punkt einzeln entschieden. Das ist die Vorgabe für S6, noch nicht gebaut.

| Punkt | Entscheidung | Speicher |
|---|---|---|
| Plan-Werte: Wiegeabstand, Sendeabstand Tag/Nacht, Solargrenze Tag/Nacht, Winter-Temperatur, Schwelle Sofort-Senden | per Downlink verstellbar, dazu ein Kommando „auf Vorgabe zurücksetzen“ | **Flash**, eigener Sektor, nur bei Änderung schreiben, mit Prüfsumme; bei ungültigem Inhalt gilt die Vorgabe der Firmware (heute 5/5) |
| Für eine Weile anders: Wiege- und Sendeabstand für 1–72 h, danach zurück zum Plan | ja; ersetzt auch „Pause/langsamer bei schwachem Akku“ | Backup-Register, ein Neustart beendet es (gewollt) |
| Neustart, wahlweise mit Verwerfen der OTAA-Session und neuem Join | ja; wird nur ausgeführt, wenn ein Prüfwert passt (z. B. die letzten 2 Byte der DevEUI) | – |
| Rückmeldung | Service-Wert 90 im ersten Uplink nach einem angenommenen Downlink (Wert mit den Entwicklern abstimmen, siehe „Fragen an die Entwickler“ Nr. 1), dazu der Zeitabstand der Uplinks. Kommando „Status senden“ auf eigenem FPort ist im Format vorgesehen, wird aber erst freigeschaltet, wenn Decoder und Webhook auf Testloggern geprüft sind: Der TTN-Decoder wertet heute jeden FPort als Messwert aus (`docs/ttn/payload_decoder_ttn.js:120`). | – |
| Uhr stellen, festes Intervall | unverändert über FPort 11 und FPort 10, weil `lora_downlink.php` auf dem Server darauf aufbaut | wie heute |
| Waagenkonfiguration (Tara, Kalibrierfaktor) | **nicht** per Downlink, bleibt vor Ort (Prüfgewicht, Flashen bzw. seriell). Grund: Kalibrieren braucht ein Prüfgewicht am Stand, ein Wechsel am laufenden Logger macht Sprünge in der Gewichtskurve | – |

### Entwurf Kommando-Format FPort 12 (19.11.2026, noch nicht gebaut)

Grundlage im Code: FPort 10 (1 Byte Intervall) und FPort 11 (7 Byte Serverquittung) werten nur genau ihre Länge aus (`src/STM32_LORA.ino:746`, `:754`). FPort 12 ist im Repo nicht belegt (grep 19.11.). FPort 10 und 11 bleiben unverändert.

**Aufbau:** Byte 0 = Format-Version (`01`), danach beliebig viele Blöcke `Kommando (1 Byte) · Länge (1 Byte) · Daten`. Über die Länge springt der Logger über Kommandos, die er nicht kennt. So bleibt das Format für spätere Firmware erweiterbar, ohne dass eine alte Firmware stolpert.

**Regeln:**
- Erst alles prüfen, dann ausführen. Passt eine Länge nicht oder liegt ein Wert außerhalb seines Bereichs, verwirft der Logger den **ganzen** Downlink und sendet keine Quittung. Es gibt also nie einen halb übernommenen Plan.
- Angenommen → im nächsten Uplink Service = 90 (Wert noch mit den Entwicklern abstimmen).
- Plan-Werte (`01`–`08`) werden gesammelt und nur einmal am Ende in den Flash geschrieben, und nur, wenn sich etwas geändert hat.
- Größe: Ein kompletter Plan (`01`–`08`) hat 26 Byte. Das passt auch bei SF12 (höchstens 51 Byte Nutzdaten in EU868).

| Kom. | Länge | Inhalt | Bereich | Speicher |
|---|---|---|---|---|
| `01` | 1 | Wiegeabstand Tag [min] | 1–60 | Flash |
| `02` | 1 | Wiegeabstand Nacht [min] | 1–60 | Flash |
| `03` | 1 | Sendeabstand Tag [min] | 5–180, Raster 5 | Flash |
| `04` | 1 | Sendeabstand Nacht [min] | 5–180, Raster 5 | Flash |
| `05` | 1 | Solargrenze Tag/Nacht [0,05 V] | 0–255 (0 = immer Tag) | Flash |
| `06` | 1 | Winter-Temperatur [0,5 °C, mit Vorzeichen] | −64…+63,5 °C, `80` = aus | Flash |
| `07` | 1 | Sendeabstand Winter [min] | 5–180, Raster 5 | Flash |
| `08` | 2 | Schwelle Sofort-Senden [g], big endian | 0 = aus, 50–65 535 | Flash |
| `0F` | 0 | Plan auf Vorgabe der Firmware zurücksetzen (Flash-Eintrag löschen) | – | Flash |
| `10` | 3 | Für eine Weile anders: Wiegen [min], Senden [min], Dauer [h] | 1–60, 5–180, 0–72 (0 = sofort beenden) | Backup-Register |
| `20` | 3 | Neustart: Art (`01` = Neustart, `02` = Neustart + Session verwerfen und neu joinen), Prüfwert = letzte 2 Byte der DevAddr | Prüfwert muss passen; bei ABP wirkt `02` wie `01` | – |
| `30` | 0 | Status senden | reserviert, ohne Wirkung, bis Decoder und Webhook geprüft sind | – |

Der Neustart läuft erst nach dem Uplink-Ende (nicht im RX-Fenster), damit die Quittung nicht verloren geht: Die Quittung 90 kommt nach dem Neustart im ersten Uplink. Den Prüfwert zeigt die TTN-Konsole unter *Device address*, und die Firmware kennt ihn bei ABP und OTAA (`LMIC.devaddr`).

**Beispiele**
- Variante B (wiegen 5, senden Tag 10 / Nacht 10, Sofort ab 500 g): `01 01 01 05 02 01 05 03 01 0A 04 01 0A 08 02 01 F4`
- Kontrolle nach Eingriff, 6 h lang jede Minute wiegen und alle 5 min senden: `01 10 03 01 05 06`
- Neustart mit neuem Join, DevAddr endet auf `…3F A1`: `01 20 03 02 3F A1`

**Rangfolge im Logger** (höchste zuerst): Arbeitsschalter (Service-Modus) → Akkusparmodus (`AlternativIntervallMinuten`) → „für eine Weile anders“ → FPort 10/11 (festes Intervall, wie heute) → Plan aus dem Flash → Vorgabe aus `Config_LORA.h`. Heute ersetzt FPort 10 den Plan, der Akkusparmodus bleibt vorrangig (`src/STM32_LORA_utility.ino:381`, `:383`).

**Speicher:** EEPROM-Emulation des STM32-Cores (letzter Flash-Sektor, auf dem F411 sind das 128 KB), mit Format-Version und Prüfsumme. Ist der Eintrag ungültig oder fehlt er, gilt `Config_LORA.h`. Ungeprüft, auf dem Testlogger messen: Löschzeit und Strom beim Schreiben, und ob der Eintrag ein Firmware-Update per DFU übersteht (gewollt, die Version im Eintrag fängt Formatwechsel ab).

### Plan S6: was in der Firmware zu bauen ist

1. **Weckraster 1 min:** Heute rastet die Weckzeit auf 5 min (`tt - tt % 300`, `src/STM32_LORA_utility.ino:389`) und ist mindestens 5 min (`:387`). Neu: Weckraster = Wiegeabstand (60-s-Raster). Die 5-min-Grenze gilt nur noch für den Sendeabstand.
2. **Wiegen ohne Senden:** Aufwachen, messen, Werte merken, nur bei fälligem Sendeabstand oder Sofort-Schwelle senden. Bei Sofort-Senden höchstens alle 250 s (Server-Sperre, siehe „Fragen an die Entwickler“ Nr. 3 und S5).
3. **Sofort-Schwelle:** Gewichtsänderung gegenüber dem zuletzt **gesendeten** Wert, je Waage, ab Schwelle (`08`). An `Sensor_Gewicht()` (`src/beelogger_HX711_STM.ino:16`) anbinden.
4. **Tag/Nacht/Winter aus dem Flash-Plan**, unabhängig von `WeckIntervall_aktiv`/`SOLAR_ZYKLUS_aktiv`. Die Feld-Konfigurationen haben beide auf 0 (geprüft 19.11., `lokal/feld-kit/cfg/q*/Config_LORA.h`), ohne Flash-Eintrag bleibt ihr Verhalten also wie heute 5/5.
5. **FPort-12-Auswertung** nach obiger Tabelle, Quittung 90, Neustart nach Uplink-Ende, Neu-Join über `otaa_clear()` (`src/STM32_LORA.ino:126`).
6. **Tests auf Testloggern** (nicht Q1/2/5/6/7): jedes Kommando einzeln, ungültige Downlinks (falsche Länge, Wert außerhalb, unbekanntes Kommando dazwischen), Akku ab → Plan bleibt, „für eine Weile“ läuft ab, Neustart/Neu-Join, Strom je Wiegung (S2). Bauen für F411 und F103, Flash-Größe notieren.
7. **Doku:** `payload.md` (FPort 12), `intervalle.md`, `speicher.md`, TTN-Anleitung mit Beispielen.

### Auswirkung auf die beelogger-App (Code gelesen 19.11., Repo `beelogger_app`)

| Erkennung | Stelle | Senden alle 10 min | Senden alle 15 min |
|---|---|---|---|
| Nachtrauschen | `lib.php` Z. 113: nur Zeilenpaare 240–360 s auseinander | fällt aus | fällt aus |
| Spikes | `lib.php` Z. 119: Puffer aus 7 Zeilen, Lücke > 360 s leert ihn | fällt aus | fällt aus |
| „Wert hing fest“ | `lib.php` Z. 74: ab 48 Zeilen (24 bei Tag), Stunden = Zeilen / 12 | erst nach 8 statt 4 h, zeigt halbe Stunden | erst nach 12 h, zeigt ein Drittel |
| Abdeckung (Lückenwarnung) | `lib.php` Z. 151, `config.php` Z. 110: Anteil 15-min-Fenster, gelb unter 0,90 | ok | knapp, einzelne leere Fenster möglich (ungeprüft) |
| Schwarm, Schritte | `auswertung.php`: 900-s-Fenster | ok | ok |

## Grundsatz und Reihenfolge

1. **Jetzt (fertig): minimal-invasiv.** Die Firmware entspricht in Funktion, Messablauf, Waagenzahl und Payload dem Original 260705. Neu sind nur Downlinks (Sendeintervall, FPort 10) mit ABP und OTAA, dazu Fehlerkorrekturen (Sketch-ID, ADR-Bit) und die Startzeile. Server und Decoder bleiben unverändert.
2. **Als Nächstes: was ohne Änderung an Server und Payload geht** (Abschnitt „Ohne Server-Änderung“). Vorher mit den Entwicklern abstimmen, weil es Felder berührt, die der Server auswertet.
3. **Später: was Server oder Payload ändert** (Rückmeldung mit Inhalt, eigener FPort für Ereignisse, Waage 5–8). Siehe [`vorschlag_payload_erweiterung.md`](vorschlag_payload_erweiterung.md).

## Ausgangsidee

In der Schwarmzeit (etwa April bis September) tagsüber viel öfter senden, damit ein Schwarm schneller gemeldet wird. Nachts reichen 5 Minuten. Dazu soll der Logger melden, welches Intervall er gerade benutzt, und es soll einen Plan geben: je Monat ein Intervall für den Tag und eins für die Nacht.

Ergebnis der Überlegung unten: Nicht die **Sendezeiten** verkürzen, sondern öfter **wiegen** und nur bei Änderung zusätzlich senden. Den Plan nicht nach Uhrzeit und Monat, sondern nach **Solarspannung und Temperatur** steuern.

## Was die Firmware heute schon kann

`IntervallMinuten` wird in `STM32_LORA_utility.ino` (~Z. 344–388) berechnet, die Werte stehen in `Config_LORA.h`:

| Regel | Einstellung | Vorgabe im Beispiel | beim LoRa-Logger brauchbar? |
|---|---|---|---|
| Tag/Nacht nach Uhrzeit | `WeckIntervall_Tag`, `_Nacht`, `_Nacht_Anfang`, `_Nacht_Ende` | Tag 10 min, Nacht 30 min, Nacht 23–6 Uhr | **nein**, keine echte Uhrzeit (siehe unten) |
| Tag/Nacht nach Solarspannung (statt Uhrzeit) | `SOLAR_ZYKLUS_aktiv`, `SolarSpannung_Sommertag` | aus | ja |
| Winter nach Monat | `WeckIntervall_Winter_aktiv`, `_Winter`, `_Winter_Anfang`, `_Winter_Ende` | aus, 60 min, Oktober–März | **nein**, kein echtes Datum |
| Winter nach Temperatur (statt Monat) | `TEMP_ZYKLUS_aktiv`, `Temperatur_Wintertag` | aus, < 8 °C | ja |
| Akku schwach | `AlternativIntervallMinuten` | 120 min, hat Vorrang vor allem | ja |
| Fern-Intervall (neu, Downlink FPort 10) | Backup-Register 5 | ersetzt Tag/Nacht/Winter | ja |

Unten gilt immer: mindestens 5 min, Weckzeit auf 5-Minuten-Raster (`tt - tt % 300`).

**Schwarm-Erkennung steckt schon in der Firmware, wird aber nicht genutzt:** `Sensor_Gewicht()` (`beelogger_HX711_STM.ino` Z. 47) vergleicht mit `Alarm_Gewicht = 1.0` kg (`STM32_beelogger.h`) und gibt 1 zurück, wenn eine Waage mehr verloren hat. Der Aufruf in `STM32_LORA_260705.ino` Z. 189/193 wirft den Rückgabewert weg, die Variable `s_alarm` wird nie benutzt. Laut Hilfetext der Server-Skripte senden andere beelogger nach mehr als 1 kg Verlust die nächsten 5 Messungen sofort. Ein Zusatz-Uplink bei Gewichtsverlust wäre also kein neues Konzept, sondern das, was beim LoRa-Logger fehlt.

**Keine Uhrzeit beim LoRa-Logger:** Die RTC startet bei jedem Power-On-Reset am 01.01.2022 um 00:00 und läuft von dort. WLAN- und LTE-Logger stellen ihre Uhr aus der Server-Antwort, LoRa hat keine solche Antwort. „Nacht 23–6 Uhr“ heißt also „23–6 Stunden nach dem Einschalten“, „Oktober–März“ heißt „die ersten drei Monate nach dem Einschalten“. Möglichkeiten:

| Weg | Aufwand | Bewertung |
|---|---|---|
| Solarspannung (Tag/Nacht) und Temperatur (Winter) statt Uhr | keiner, gibt es in 260705 schon, nur in `Config_LORA.h` einschalten | **empfohlen**, folgt dem Wetter statt dem Kalender |
| Uhrzeit per Downlink (z. B. 1× pro Woche, 4 Byte Unix-Zeit, eigener FPort) | klein in der Firmware; jemand muss den Downlink schicken (TTN-Konsole, Skript oder Server) | später, falls Uhrzeit wirklich gebraucht wird |
| DeviceTimeReq (LoRaWAN-MAC-Befehl) | kennt LMIC 1.5 nicht | nein |

Ein Plan „Sommer tagsüber oft, nachts selten, Winter selten“ geht also heute schon, wenn er über Solarspannung und Temperatur läuft. Es fehlt nur ein eigener Zeitraum Schwarmzeit, und alles ist fest beim Flashen eingestellt.

**Der Server hat so einen Plan schon** (`ServerZeitSteuerung()` in `beelogger_log.php`, eingestellt in der Web-Konfiguration, `$IntervallSendeSteuerung`): zeit-, licht- oder solarspannungsgesteuert je ein Intervall für Tag und Nacht, dazu Winter nach Datum (Sommer-/Winterbeginn), höchstens 60 min. Der Server hat eine echte Uhr, Uhrzeit und Datum gehen dort also. WLAN- und GSM-Logger bekommen das Ergebnis in der HTTP-Antwort (`<Zeit>T<Intervall>B<Service>ok *`) und stellen sich danach. Beim LoRa-Logger geht diese Antwort an den TTN-Webhook und wird verworfen. Den Plan des Servers per Downlink zum Logger zu bringen, bräuchte eine Server-Änderung (TTN-API-Key am Server, Downlink schicken) → Frage an die Entwickler.

## Wie oft geht es? (Quad, SF7)

Ein Uplink dauert 72 ms (17 Byte, siehe `vorschlag_payload_erweiterung.md`). TTN Fair Use: 30 s Sendezeit pro Gerät und Tag.

| Plan | Uplinks/Tag | Sendezeit | passt? |
|---|---|---|---|
| immer 5 min | 288 | 20,7 s | ja |
| Nacht 5 min (10 h) + Tag 3 min (14 h) | 120 + 280 | 28,8 s | knapp |
| Nacht 5 min + Tag 2 min | 120 + 420 | 38,9 s | nein |
| Tag 1 min | 840 nur tagsüber | 60 s | nein |

Kürzer als 3 min am Tag geht unter Fair Use nicht. Unter 5 min müsste außerdem das 5-Minuten-Raster und das Registerformat (Minuten/5) geändert werden. Strom ist im Sommer tagsüber nach Erfahrung des Nutzers kein Problem, die Solarzelle liefert genug.

Gewinn für den Schwarmalarm: Von 5 auf 3 Minuten wird der Alarm höchstens 2 Minuten früher gemeldet, auf Kosten fast der ganzen Sendezeit. **Deshalb nicht die Sendezeit verkürzen, sondern öfter wiegen** (nächster Abschnitt).

## Besser: oft wiegen, nur bei Änderung senden

Heute stellt der **Server** die Alarmschwelle (Gewichtsdifferenz) ein und benachrichtigt. Das bleibt so.

Neu wäre: Der Logger wiegt in der Schwarmzeit z. B. **jede Minute**, sendet aber nur im normalen Takt. Fällt eine Waage seit der letzten Wiegung um mehr als eine **Sendeschwelle**, sendet er:

1. einen zusätzlichen Uplink (gleiches Payload-Format), sobald es die Server-Regel erlaubt (unten),
2. danach für eine Weile (z. B. 30 min) schneller, um den Verlauf zu zeigen, dann zurück zum Plan.

Damit kommt der Alarm nach wenigen Minuten, ohne Ereignis kostet das keine Sendezeit.

Zwei Schwellen, getrennte Aufgaben:

| Schwelle | Wo | Aufgabe |
|---|---|---|
| Alarmschwelle | Server (wie heute) | Ist es ein Schwarm? Benachrichtigen. |
| Sendeschwelle | Logger (neu) | Außer der Reihe senden? Muss **kleiner** als die Alarmschwelle sein (Vorschlag 0,3 kg; ein Schwarm nimmt meist 1–3 kg mit), damit der Server jedes relevante Ereignis sieht. |

**Server-Regel „mindestens 4 Minuten“:** `beelogger_log.php` speichert eine Meldung nur, wenn ihr Zeitstempel mehr als 200 s nach dem letzten gespeicherten liegt. Beide Zeitstempel sind Serverzeit **auf die Minute abgerundet**, also braucht es 4 volle Minuten: ab 240 s echtem Abstand immer, bei 181–239 s nur je nach Sekunde. Ausnahmen: Service = 99 (ab der nächsten Minute), Service < 0 (ab 110 s), Kalibriertemperatur `99.9` in der Server-Konfiguration (ab 55 s). Auch der **Schwarmalarm** wird nur bei mehr als 200 s Abstand geprüft, unabhängig von diesen Ausnahmen. Ein Zusatz-Uplink eine Minute nach dem regulären würde also verworfen, und der Schwarmalarm sähe ihn nicht. Ohne Server-Änderung:
- **Empfohlen:** Der Logger sendet den Zusatz-Uplink frühestens **250 s** nach dem letzten Uplink (240 s plus Luft für Laufzeit-Schwankungen). Bei 5-min-Takt bringt das wenig, bei 10–30 min Takt viel. Der Server bleibt unverändert.
- Nicht empfohlen: Kalibriertemperatur `99.9` am Server. Derselbe Wert steuert auch die Temperaturkompensation: `Gewicht_korr = Gewicht_roh + (Temperatur − KalibrierTemperatur) × Korrekturwert` (`beelogger_log.php` Z. 527–535, bei LoRa ist die Temperatur TempOut). Im Minutenbetrieb läuft außerdem der Schwarmalarm praktisch nie (Prüfung erst ab 200 s Abstand). Mit 99,9 °C und dem Beispiel-Korrekturwert 0,0355 kg/°C von beelogger.de wird das Gewicht bei 15 °C um rund 3 kg zu leicht und wandert mit der Temperatur. Unschädlich nur mit Korrekturwert 0 (Kompensation aus). Also: Minutenbetrieb **oder** Temperaturkompensation, nicht beides.

Die Sendeschwelle kann fest sein. Aus der Ferne einstellbar lohnt sich vor allem bei unruhigen Waagen (Wind), die zu oft außer der Reihe senden.

Zu klären:
- Letztes Gewicht je Waage muss den Tiefschlaf überstehen. Backup-Register sind knapp (F1: 10 × 16 Bit, mehrere schon belegt). 16 Bit in 10-g-Schritten reichen für 0–655 kg.
- Fehlauslöser: Imker am Stock, Honigernte, Wind, Schnee. Ein zusätzlicher Uplink kostet wenig (72 ms); ob es ein Schwarm ist, entscheidet weiter der Server.
- Kennzeichnung im Uplink, z. B. eigener FPort. Dann kann der Server sofort benachrichtigen. **Betrifft Server und Decoder → später, mit den Entwicklern abstimmen.** Ohne Kennzeichnung sieht der Server einen normalen Datensatz und prüft wie immer.
- Jede Minute wiegen kostet Strom (Aufwachen, Waagen einschwingen), aber keine Sendezeit. Im Sommer tagsüber mit Solar laut Nutzer kein Problem. Messen und Senden müssen in der Firmware getrennt werden, heute ist jede Messung ein Uplink; Weckzeit-Raster 5 min → 1 min.

## Variante 19.11.: alle 10 min senden, alle 5 min wiegen

Vorschlag Nutzer (19.11.2026), Stand **zur Diskussion, nicht umgesetzt**. Ziel: weniger Sendezeit, Akku und Server-Daten, ohne die heutige 5-Minuten-Alarmierung zu verlieren.

**Warum die Idee oben bei 5-min-Takt kaum etwas bringt:** Der Server speichert eine Meldung erst, wenn ihr Zeitstempel mindestens 4 min nach dem letzten gespeicherten liegt (Zeitstempel auf die Minute abgerundet, `beelogger_log.php` M40 Z. 295 und 761). Ein Zusatz-Uplink käme also frühestens rund 4 min nach dem regulären, das ist kaum früher als der nächste reguläre nach 5 min. Sinnvoll wird das Wiegen ohne Senden erst bei einem längeren Sendetakt.

Ablauf:

1. Der Logger wacht wie heute alle 5 min auf und wiegt.
2. Jede zweite Wiegung geht als Uplink raus (10 min, laut beelogger-Team der übliche LoRa-Takt, Antwort 07.11.).
3. Ist bei einer Wiegung dazwischen eine Waage um mehr als die **Sendeschwelle** leichter als beim **zuletzt gesendeten** Gewicht, sendet der Logger sofort. Der Vergleich geht gegen das gesendete Gewicht, nicht gegen die letzte Wiegung, weil auch der Server gegen sein zuletzt gespeichertes Gewicht vergleicht. Sonst würde ein Verlust übersehen, der sich über zwei Wiegungen verteilt.

Passt zum Server ohne Änderung:

- Der Zusatz-Uplink kommt 5 min nach dem letzten, also über der 4-min-Speicherregel.
- Der Schwarmalarm des Servers schlägt an, wenn eine Waage mehr als `DifferenzGewicht` verloren hat und seit der letzten Speicherung `ReferenzZeit` vergangen ist (`beelogger_log.php` M40 Z. 927–941). Mit `ReferenzZeit` 5 min kommt der Alarm also mit dem Zusatz-Uplink, wie heute bei 5-min-Takt.
- Randfall, ungeprüft: Beide Zeitstempel sind auf die Minute abgerundet. Kommt der Zusatz-Uplink knapp unter 300 s nach dem letzten an (kürzere Wachzeit als beim vorigen), kann der Server 4 min sehen. Dann merkt er sich den Verlust und meldet ihn erst beim nächsten Uplink, wenn er noch anhält (Z. 955), also bis zu 5 min später. Im Test prüfen.
- Die Sendeschwelle im Logger muss unter `DifferenzGewicht` am Server liegen (Server-Vorgabe etwa 1 kg je Waage).

Ersparnis, gerechnet mit 72 ms je Uplink (Abschnitt „Wie oft geht es?“): 288 → 144 Uplinks am Tag, Sendezeit 20,7 → 10,4 s, halb so viele Datenzeilen am Server. Gewogen wird gleich oft wie heute, beim Akku spart nur der Funkteil (Senden, Empfangsfenster). Wie viel das ausmacht: **ungeprüft, messen.**

Firmware: Heute ist jede Wiegung ein Uplink. Messen und Senden müssen getrennt werden. `LetztesGewicht[4]` liegt schon im RTC-Backup-RAM (`STM32_LORA_260705.ino` Z. 128), es müsste das zuletzt *gesendete* Gewicht werden. Das ändert das Verhalten des Loggers, deshalb eigener Branch und Testlogger, nicht im Feld-Flash ABP → OTAA (Entscheidung 19.11.: dabei ändert sich nur die Anmeldung).

### Offene Frage: Reicht im Sommer der Strom für minütliches Wiegen?

Gestellt vom Nutzer am 19.11.2026, **noch nicht geklärt**. Tagsüber liefert die Solarzelle laut Nutzer genug, belegt ist das nicht. Klären:

- am Testlogger mit Solarzelle und Akku messen: Wachzeit und Strom je Wiegung, Tagesbilanz bei 1-, 5- und 10-min-Takt, auch über mehrere trübe Tage,
- das beelogger-Team nach seiner Erfahrung fragen (mit den übrigen Fragen, wenn die Tests Ergebnisse haben).

Ergebnis hier eintragen.

## Ohne Server-Änderung: Rückmeldung, was gilt

Wer per Downlink etwas umstellt, will sehen, ob es angekommen ist. Ohne neues Payload-Feld gibt es zwei Wege.

**1. Zeitabstand der Uplinks.** Der Server sieht das tatsächliche Intervall an den Zeitstempeln. Geht heute schon, ohne jede Änderung. Grenze: Man sieht nicht, *warum* das Intervall gilt (Downlink, Plan oder Akkusparmodus).

**2. Quittung im Service-Feld.** Service ist 1 Byte in jeder Nachricht. Heutige Bedeutung (beelogger.de):

| Wert | Bedeutung |
|---|---|
| 0 | nach einem Reset des Loggers |
| 1 – ca. 12 | Einschaltdauer in s eines normalen Messzyklus |
| 15 – ca. 80 | Einschaltdauer in s eines Sendezyklus |
| 99 | Arbeitsschalter aktiv erkannt |

Der Server wertet Service aus (`beelogger_log.php`, geprüft 07.11.): Bei **99** speichert er jede Meldung ab der nächsten Minute. **99 oder über 240** setzt das Durchsicht-Icon (höchstens alle 40 min). Der **Schwarmalarm** und die Auto-Anmerkungen laufen nur, wenn Service in der aktuellen **und** der letzten gespeicherten Meldung **unter 99** liegt. Werte über 1000 werden zu 99, −1 wird leer, andere negative Werte gelten als RSSI. Ein Wert wie 90 hat keine Sonderwirkung.

Vorschlag: In der ersten Nachricht nach einem empfangenen Downlink sendet der Logger statt der Einschaltdauer den festen Wert **90** (Bereich 81–98 ist frei; 90 liegt mit Abstand über den längsten Sendezyklen und unter 99; den endgültigen Wert legen die Entwickler fest). Das bestätigt „Downlink angekommen“, den Inhalt zeigt Weg 1. Kosten: eine Einschaltdauer fehlt in der Grafik. Schwarmalarm und Speicherung bleiben unberührt.

Nicht empfohlen: mehrere Codes in 81–98 (zu eng, zu nah an echten Einschaltzeiten, ein OTAA-Join mit Zeitüberschreitung kann länger dauern), Werte ab 99 (schalten den Schwarmalarm für zwei Meldungen ab), Aux3/Sketch-ID umdeuten (der Server zeigt sie als Sketch-Version an).

## Was soll dann noch aus der Ferne verstellbar sein?

Wenn Plan und Schwarmalarm im Logger stecken, ist das Fern-Intervall in seiner heutigen Form (festes Intervall, ersetzt den Plan, gilt bis Akku ab) nur noch selten nötig. Mögliche Nutzen:

| Fern-Einstellung | Nutzen | Bewertung | ohne Server-Änderung? |
|---|---|---|---|
| Plan-Werte ändern (Solar-/Temperaturschwellen, Intervalle Tag/Nacht/Winter) | Logger steht am Stand, kein Kabel, kein Neu-Flashen | **hoch**, vor allem für Nutzer ohne Programmiergerät | ja (Rückmeldung nur über Zeitabstand + Quittung) |
| Wiegetakt und Sendeschwelle (Logger), Schnell-Wiegen ein/aus | unruhige Waagen beruhigen, außerhalb der Schwarmzeit aus | mittel (Alarmschwelle bleibt am Server) | ja |
| vorübergehend schneller, **mit Ablaufzeit** (z. B. 6 h) | Kontrolle nach Eingriff, Wanderung, Fehlersuche; fällt von selbst zurück | mittel | ja |
| vorübergehend langsamer / Pause | Akku schwach im Winter, Fair Use überschritten, Logger im Lager | mittel | ja |
| festes Intervall wie heute (ohne Ablauf) | einfach, gibt es schon | gering, Risiko: bleibt aus Versehen stehen | ja (umgesetzt) |
| Uhrzeit setzen | nur nötig, wenn Plan nach Uhr/Kalender | gering, Solar/Temperatur reicht | ja |

Gemeinsame Voraussetzungen:
- **Rückmeldung**, was gilt. Ohne Server-Änderung: Zeitabstand + Service-Quittung (oben). Mit Inhalt (aktives Intervall, Plan-Version, Schwellwert): Payload-Vorschlag, später.
- **Speicher, der den Akku-Wechsel übersteht**: Backup-Register gehen bei Akku ab verloren. Für Plan-Werte, die ein Nutzer bewusst setzt, wäre Flash besser (F1: Seite wie bei der OTAA-Session, F4: eigener Sektor). Nur bei Änderung schreiben.
- Downlinks sind knapp: TTN 10 pro Tag, Class A nur nach einem Uplink. Ein Plan muss in einen Downlink passen und darf nicht ständig gesendet werden müssen.

## Fragen an die Entwickler (ohne Server-Änderung)

1. **Service-Quittung:** Darf die Firmware nach einem Downlink einmal einen festen Wert senden? Unser Vorschlag: **90**. Passt das, oder ist ein anderer Wert aus 81–98 besser? Sind 81–98 oder Werte ab 100 schon verplant?
2. Wertet der Server Service noch außerhalb von `beelogger_log.php` aus (Watchdog, mobileWatch, Beep)? In `beelogger_log.php` haben wir es geprüft (siehe oben).
3. **Mindestabstand:** Wir würden Zusatz-Uplinks frühestens 250 s nach dem letzten schicken (Server: > 200 s auf Minuten abgerundet = 4 min), damit der Server sie ohne Änderung speichert und den Schwarmalarm prüft. Einverstanden? Kalibriertemperatur `99.9` kommt für uns nicht in Frage (Temperaturkompensation, Schwarmalarm).
4. **Plan ohne Uhr:** Sind `SOLAR_ZYKLUS` und `TEMP_ZYKLUS` bei LoRa-Loggern erprobt? Bewährte Schwellen (Solarspannung Tag/Nacht, Temperatur Winter)?
5. Ist ein Zusatz-Uplink bei Gewichtsverlust (gleiches Format, ohne Kennzeichnung) für den Server in Ordnung, oder stört das Anzeige, Tageswerte oder Statistik? Die Firmware erkennt 1 kg Verlust schon (`Sensor_Gewicht()`), nutzt es aber nicht; andere beelogger senden dann 5 Messungen sofort. Ist das für LoRa so gewollt oder vergessen?
6. **Sendeplan des Servers für LoRa:** Die Intervall-Steuerung in der Web-Konfiguration erreicht LoRa-Logger nicht. Wäre es denkbar, dass der Server sie später per TTN-Downlink (FPort 10) schickt? Bis dahin würden wir den Plan im Logger lassen (Solar/Temperatur).
7. **Strom für häufigeres Wiegen (19.11.):** Reicht im Sommer die Solarzelle, um alle 1 bis 5 min zu wiegen und nur alle 10 min zu senden (Abschnitt „Variante 19.11.“)? Gibt es dazu Erfahrungen oder Messwerte (Wachzeit und Strom je Wiegung)?

## Offene Fragen (für uns)

1. Reicht der Plan aus dem Original (Tag/Nacht + Winter, über Solar/Temperatur), oder braucht es einen eigenen Zeitraum Schwarmzeit?
2. Sendeschwelle im Logger: fester Wert oder einstellbar? Wie lange danach schneller?
3. Jede Minute wiegen in der Schwarmzeit: Strom messen (Wachzeit pro Wiegung), auch bei schlechtem Wetter mehrere Tage.
4. Fern-Intervall heute behalten, durch „schneller/langsamer mit Ablauf“ ersetzen oder beides?
5. Welche Fern-Einstellungen müssen den Akku-Wechsel überstehen (→ Flash)?
6. Reihenfolge: zuerst Service-Quittung, dann Plan per Downlink, dann öfter wiegen/Zusatz-Uplink?

## Testaufbau (Vorschlag Nutzer, 07.11.)

Ein eigener Logger mit Waage, Solarzelle und Akku im Freien, **einige Wochen** (Vorschlag 4–6) laufen lassen, bevor etwas davon in die Feldgeräte geht. Messen:
- Strom und Akkuspannung bei minütlichem Wiegen, auch über mehrere trübe Tage,
- Sendezeit pro Tag in TTN (bleibt sie unter 30 s, auch mit Zusatz-Uplinks?),
- wie oft die Sendeschwelle ohne Schwarm auslöst (Wind, Eingriffe, Regen),
- ob die Zusatz-Uplinks am Server ankommen und gespeichert werden (mindestens 4 Minuten Abstand, Ziel 250 s),
- ob Solar- und Temperatursteuerung sinnvoll umschalten.

Einen Schwarm kann man im Herbst nicht abwarten: Gewicht von Hand wegnehmen (z. B. 2 kg) und prüfen, ob Zusatz-Uplink und Schwarmalarm des Servers kommen. Das Verhalten im Winter (Akku, Schnee) läuft danach nebenher weiter und ist keine Voraussetzung für den ersten Einsatz.
