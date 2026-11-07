# Idee: Sendeplan nach Jahreszeit, Schwarmalarm, was aus der Ferne verstellbar sein soll

Stand 07.11.2026, **zur Diskussion**, nichts davon ist umgesetzt.

## Ausgangsidee

In der Schwarmzeit (etwa April bis September) tagsüber viel öfter senden, damit ein Schwarm schneller gemeldet wird. Nachts reichen 5 Minuten. Dazu soll der Logger melden, welches Intervall er gerade benutzt, und es soll einen Plan geben: je Monat ein Intervall für den Tag und eins für die Nacht.

## Was die Firmware heute schon kann

`IntervallMinuten` wird in `STM32_LORA_utility.ino` (~Z. 344–388) berechnet, die Werte stehen in `Config_LORA.h`:

| Regel | Einstellung | Vorgabe im Beispiel |
|---|---|---|
| Tag/Nacht nach Uhrzeit | `WeckIntervall_Tag`, `_Nacht`, `_Nacht_Anfang`, `_Nacht_Ende` | Tag 10 min, Nacht 30 min, Nacht 23–6 Uhr |
| Tag/Nacht nach Solarspannung (statt Uhrzeit) | `SOLAR_ZYKLUS_aktiv`, `SolarSpannung_Sommertag` | aus |
| Winter nach Monat | `WeckIntervall_Winter_aktiv`, `_Winter`, `_Winter_Anfang`, `_Winter_Ende` | aus, 60 min, Oktober–März |
| Winter nach Temperatur (statt Monat) | `TEMP_ZYKLUS_aktiv`, `Temperatur_Wintertag` | aus, < 8 °C |
| Akku schwach | `AlternativIntervallMinuten` | 120 min, hat Vorrang vor allem |
| Fern-Intervall (neu, Downlink FPort 10) | Backup-Register 5 | ersetzt Tag/Nacht/Winter |

Unten gilt immer: mindestens 5 min, Weckzeit auf 5-Minuten-Raster (`tt - tt % 300`).

Ein Plan „Sommer tagsüber oft, nachts selten, Winter selten“ geht also **heute schon**. Es fehlt nur ein dritter Zeitraum (z. B. Schwarmzeit April–Juli getrennt vom übrigen Sommer). Und alles ist fest beim Flashen eingestellt.

## Wie oft geht es? (Quad, SF7)

Ein Uplink dauert 72 ms (17 Byte, siehe `vorschlag_payload_erweiterung.md`). TTN Fair Use: 30 s Sendezeit pro Gerät und Tag.

| Plan | Uplinks/Tag | Sendezeit | passt? |
|---|---|---|---|
| immer 5 min | 288 | 20,7 s | ja |
| Nacht 5 min (10 h) + Tag 3 min (14 h) | 120 + 280 | 28,8 s | knapp |
| Nacht 5 min + Tag 2 min | 120 + 420 | 38,9 s | nein |
| Tag 1 min | 840 nur tagsüber | 60 s | nein |

Kürzer als 3 min am Tag geht unter Fair Use nicht. Unter 5 min müsste außerdem das 5-Minuten-Raster und das Registerformat (Minuten/5) geändert werden. Strom ist im Sommer tagsüber nach Erfahrung des Nutzers kein Problem, die Solarzelle liefert genug.

Gewinn für den Schwarmalarm: Von 5 auf 3 Minuten wird der Alarm höchstens 2 Minuten früher gemeldet, auf Kosten fast der ganzen Sendezeit.

## Besser: oft wiegen, nur bei Änderung senden

Heute stellt der **Server** die Alarmschwelle (Gewichtsdifferenz) ein und benachrichtigt. Das bleibt so.

Neu wäre: Der Logger wiegt in der Schwarmzeit z. B. **jede Minute**, sendet aber nur im normalen Takt. Fällt eine Waage seit der letzten Wiegung um mehr als eine **Sendeschwelle**, sendet er:

1. **sofort** einen zusätzlichen Uplink (gleiches Payload-Format),
2. danach für eine Weile (z. B. 30 min) schneller, um den Verlauf zu zeigen, dann zurück zum Plan.

Damit kommt der Alarm nach höchstens etwa einer Minute, ohne Ereignis kostet das keine Sendezeit.

Zwei Schwellen, getrennte Aufgaben:

| Schwelle | Wo | Aufgabe |
|---|---|---|
| Alarmschwelle | Server (wie heute) | Ist es ein Schwarm? Benachrichtigen. |
| Sendeschwelle | Logger (neu) | Außer der Reihe senden? Muss **kleiner** als die Alarmschwelle sein (Vorschlag 0,3 kg; ein Schwarm nimmt meist 1–3 kg mit), damit der Server jedes relevante Ereignis sieht. |

Die Sendeschwelle kann fest sein. Aus der Ferne einstellbar lohnt sich vor allem bei unruhigen Waagen (Wind), die zu oft außer der Reihe senden.

Zu klären:
- Letztes Gewicht je Waage muss den Tiefschlaf überstehen. Backup-Register sind knapp (F1: 10 × 16 Bit, mehrere schon belegt). 16 Bit in 10-g-Schritten reichen für 0–655 kg.
- Fehlauslöser: Imker am Stock, Honigernte, Wind, Schnee. Ein zusätzlicher Uplink kostet wenig (72 ms); ob es ein Schwarm ist, entscheidet weiter der Server.
- Kennzeichnung im Uplink, z. B. eigener FPort. Dann kann der Server sofort benachrichtigen. **Betrifft Server und Decoder → mit den Entwicklern abstimmen.**
- Jede Minute wiegen kostet Strom (Aufwachen, Waagen einschwingen), aber keine Sendezeit. Im Sommer tagsüber mit Solar laut Nutzer kein Problem. Messen und Senden müssen in der Firmware getrennt werden, heute ist jede Messung ein Uplink; Weckzeit-Raster 5 min → 1 min.

## Was soll dann noch aus der Ferne verstellbar sein?

Wenn Plan und Schwarmalarm im Logger stecken, ist das Fern-Intervall in seiner heutigen Form (festes Intervall, ersetzt den Plan, gilt bis Akku ab) nur noch selten nötig. Mögliche Nutzen:

| Fern-Einstellung | Nutzen | Bewertung |
|---|---|---|
| Plan-Werte ändern (Tag/Nacht/Winter, Monatsgrenzen, Nachtzeit) | Logger steht am Stand, kein Kabel, kein Neu-Flashen | **hoch**, vor allem für Nutzer ohne Programmiergerät |
| Sendeschwelle (Logger), Schnell-Wiegen ein/aus | unruhige Waagen beruhigen, außerhalb der Schwarmzeit aus | mittel (Alarmschwelle bleibt am Server) |
| vorübergehend schneller, **mit Ablaufzeit** (z. B. 6 h) | Kontrolle nach Eingriff, Wanderung, Fehlersuche; fällt von selbst zurück | mittel |
| vorübergehend langsamer / Pause | Akku schwach im Winter, Fair Use überschritten, Logger im Lager | mittel |
| festes Intervall wie heute (ohne Ablauf) | einfach, gibt es schon | gering, Risiko: bleibt aus Versehen stehen |

Gemeinsame Voraussetzungen:
- **Rückmeldung**, was gilt (aktives Intervall, Plan-Version, Schwellwert). Steht schon im Payload-Vorschlag.
- **Speicher, der den Akku-Wechsel übersteht**: Backup-Register gehen bei Akku ab verloren. Für Plan-Werte, die ein Nutzer bewusst setzt, wäre Flash besser (F1: Seite wie bei der OTAA-Session, F4: eigener Sektor). Nur bei Änderung schreiben.
- Downlinks sind knapp: TTN 10 pro Tag, Class A nur nach einem Uplink. Ein Plan muss in einen Downlink passen und darf nicht ständig gesendet werden müssen.

## Offene Fragen zum Besprechen

1. Reicht der Plan aus dem Original (Tag/Nacht + Winter), oder braucht es einen eigenen Zeitraum Schwarmzeit?
2. Sendeschwelle im Logger: fester Wert oder einstellbar? Wie lange danach schneller, eigener FPort? (mit Entwicklern, Alarmschwelle bleibt am Server)
3. Jede Minute wiegen in der Schwarmzeit: Strom messen (Wachzeit pro Wiegung), auch bei schlechtem Wetter mehrere Tage.
4. Fern-Intervall heute behalten, durch „schneller/langsamer mit Ablauf“ ersetzen oder beides?
5. Welche Fern-Einstellungen müssen den Akku-Wechsel überstehen (→ Flash)?
6. Reihenfolge: zuerst die Rückmeldung (ohne sie weiß niemand, was am Logger gilt), dann Plan per Downlink, dann Schwarmalarm?

## Testaufbau (Vorschlag Nutzer, 07.11.)

Ein eigener Logger mit Waage, Solarzelle und Akku im Freien, **etwa ein Jahr** laufen lassen, bevor etwas davon in die Feldgeräte geht. Messen:
- Akkuspannung über Sommer und Winter (reicht die Solarzelle bei minütlichem Wiegen, auch nach mehreren trüben Tagen?),
- Sendezeit pro Tag in TTN (bleibt sie unter 30 s, auch mit Zusatz-Uplinks?),
- wie oft die Sendeschwelle ohne Schwarm auslöst (Wind, Eingriffe, Regen, Schnee).
