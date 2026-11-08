# Idee: Sendeplan, Schwarmalarm, was aus der Ferne verstellbar sein soll

Stand 07.11.2026, **zur Diskussion**, nichts davon ist umgesetzt.

**Antwort beelogger-Team (07.11.):** LoRa sendet ohnehin mindestens alle 10 min, forciertes Senden braucht es nicht. Öfter bringt nichts: Der Server ignoriert kurze Abstände (Okt–März 400 s), und die Schwarmlogik ist auf den 10-min-Takt ausgelegt (Windböen). Dabei bleibt es. **Status: nicht für `main`**, höchstens als Experiment im eigenen Branch mit eigenem Server.

**Einordnung (07.11.):** `main` bleibt maximal kompatibel zum Original-beelogger und zum Community-Server. Was hier beschrieben ist und Daten oder Verhalten am Server berührt (Zusatz-Uplinks, Service-Quittung, eigener Sendeplan), wird zuerst in einem **eigenen Branch** gebaut und auf einem eigenen beelogger-Server getestet. In `main` kommt es nur, wenn die Entwickler es ausdrücklich wollen.

**Korrektur 07.11. (nach Lesen der Server-Skripte M40_261004):** Die frühere Annahme „ein Zusatz-Uplink ab 200 s wird gespeichert“ war falsch. Der Server stempelt jede Meldung mit seiner eigenen Uhr, **auf die Minute abgerundet**. Sicher gespeichert wird erst ab **240 s** Abstand, wir planen mit **250 s**. Außerdem hat der Server schon eine eigene Sendeintervall-Steuerung, die bei LoRa nur nicht beim Logger ankommt (Abschnitt „Was die Firmware heute schon kann“).

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
