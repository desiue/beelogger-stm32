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

## Besser: Schwarmalarm auf Ereignis

Der Logger wiegt im normalen Takt. Fällt eine Waage seit der letzten Messung um mehr als einen Schwellwert (Vorschlag 0,5 kg; ein Schwarm nimmt meist 1–3 kg mit), sendet er:

1. **sofort** einen zusätzlichen Uplink (gleiches Payload-Format),
2. danach für eine Weile (z. B. 30 min) schneller, um den Verlauf zu zeigen, dann zurück zum Plan.

Vorteile: Ohne Ereignis kostet das keine Sendezeit. Die Reaktionszeit hängt am Messtakt, nicht am Sendetakt. Zum Messtakt siehe die offenen Fragen.

Zu klären:
- Letztes Gewicht je Waage muss den Tiefschlaf überstehen. Backup-Register sind knapp (F1: 10 × 16 Bit, mehrere schon belegt). 16 Bit in 10-g-Schritten reichen für 0–655 kg.
- Fehlalarme: Imker am Stock, Honigernte, Wind, Schnee. Ein Fehlalarm am Tag kostet wenig (72 ms). Wichtig ist, dass der Server ihn nicht als Schwarm meldet, ohne dass es plausibel ist.
- Kennzeichnung im Uplink, z. B. eigener FPort. Dann kann der Server sofort benachrichtigen. **Betrifft Server und Decoder → mit den Entwicklern abstimmen.**
- Schneller *messen* (z. B. alle 2 min, aber nur bei Ereignis senden) kostet Strom, aber keine Sendezeit. Im Sommer tagsüber mit Solar wohl tragbar.

## Was soll dann noch aus der Ferne verstellbar sein?

Wenn Plan und Schwarmalarm im Logger stecken, ist das Fern-Intervall in seiner heutigen Form (festes Intervall, ersetzt den Plan, gilt bis Akku ab) nur noch selten nötig. Mögliche Nutzen:

| Fern-Einstellung | Nutzen | Bewertung |
|---|---|---|
| Plan-Werte ändern (Tag/Nacht/Winter, Monatsgrenzen, Nachtzeit) | Logger steht am Stand, kein Kabel, kein Neu-Flashen | **hoch**, vor allem für Nutzer ohne Programmiergerät |
| Schwellwert Schwarmalarm, ein/aus | an Volk und Waage anpassen, Fehlalarme abstellen | **hoch** |
| vorübergehend schneller, **mit Ablaufzeit** (z. B. 6 h) | Kontrolle nach Eingriff, Wanderung, Fehlersuche; fällt von selbst zurück | mittel |
| vorübergehend langsamer / Pause | Akku schwach im Winter, Fair Use überschritten, Logger im Lager | mittel |
| festes Intervall wie heute (ohne Ablauf) | einfach, gibt es schon | gering, Risiko: bleibt aus Versehen stehen |

Gemeinsame Voraussetzungen:
- **Rückmeldung**, was gilt (aktives Intervall, Plan-Version, Schwellwert). Steht schon im Payload-Vorschlag.
- **Speicher, der den Akku-Wechsel übersteht**: Backup-Register gehen bei Akku ab verloren. Für Plan-Werte, die ein Nutzer bewusst setzt, wäre Flash besser (F1: Seite wie bei der OTAA-Session, F4: eigener Sektor). Nur bei Änderung schreiben.
- Downlinks sind knapp: TTN 10 pro Tag, Class A nur nach einem Uplink. Ein Plan muss in einen Downlink passen und darf nicht ständig gesendet werden müssen.

## Offene Fragen zum Besprechen

1. Reicht der Plan aus dem Original (Tag/Nacht + Winter), oder braucht es einen eigenen Zeitraum Schwarmzeit?
2. Schwarmalarm: welcher Schwellwert, wie lange danach schneller, eigener FPort? (mit Entwicklern)
3. Wie oft wiegen in der Schwarmzeit, wenn nur bei Ereignis gesendet wird? Strom messen.
4. Fern-Intervall heute behalten, durch „schneller/langsamer mit Ablauf“ ersetzen oder beides?
5. Welche Fern-Einstellungen müssen den Akku-Wechsel überstehen (→ Flash)?
6. Reihenfolge: zuerst die Rückmeldung (ohne sie weiß niemand, was am Logger gilt), dann Plan per Downlink, dann Schwarmalarm?
