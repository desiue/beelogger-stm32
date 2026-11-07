# Vorschlag: Payload-Erweiterung (Intervall-Rückmeldung, Waage 5–8)

Stand 07.11.2026, **Entwurf zur Abstimmung mit den beelogger-Entwicklern**. Noch nichts davon ist in der Firmware umgesetzt.

## Ausgangslage

- Das Sendeintervall lässt sich jetzt per Downlink ändern (FPort 10, 1 Byte Minuten, siehe [`ttn_anleitung.md`](ttn_anleitung.md) Abschnitt 4). Der Server erfährt aber nicht, welches Intervall der Logger gerade benutzt.
- Der Server liest schon `Gewicht5` und `Gewicht6` und kennt die Ordnernamen `Penta`/`Hexa`. Firmware und TTN-Decoder können bisher nur 4 Waagen.
- **Hardware-Grenze:** Blue- und BlackPill-Platine haben Anschlüsse für 2 HX711 mit je 2 Kanälen (A Verstärkung 128, B Verstärkung 32), also 4 Waagen. Pins: `HX711_DT/SCK` in `STM32_beelogger.h`, 2 Pins je HX711. Für Hexa bräuchte es einen 3. HX711 an zwei freien GPIOs, für Octa einen 4. Die Pins hätte der Controller, aber auf der Platine gibt es keinen Steckplatz dafür. Die Firmware wertet bei `Anzahl_Sensoren_Gewicht > 4` gar keine Waage aus (`Sensor_Gewicht()` ist dann leer); nur der DS18B20-Teil kennt schon Penta/Hexa.
- Die Payload trägt am Ende einen 2-Byte-Header, ein Bit je Feld. **Alle 16 Bits sind belegt** (Bit 15 = Sketch-ID `Aux3`). Für ein neues Feld ist kein Bit mehr frei.

## Vorschlag: ein Erweiterungsbyte vor dem Header

```
[Felder Bit 0–15] [neue Felder] [Erweiterungsbyte] [Header hi] [Header lo]
```

| Bit im Erweiterungsbyte | Feld | Bytes | Umrechnung |
|---|---|---|---|
| 0 | Intervall | 1 | Minuten (aktives Intervall des Loggers) |
| 1 | Gewicht5 | 2 | int16 ÷100 → kg |
| 2 | Gewicht6 | 2 | int16 ÷100 → kg |
| 3, 4 | Gewicht7, Gewicht8 | je 2 | nur wenn der Server sie annimmt (siehe Fragen) |
| 5–7 | frei | | |

Das Erweiterungsbyte fehlt, wenn kein neues Feld drin ist. Die Payload ist dann genau wie heute.

**Abwärtskompatibel:** Der heutige Decoder liest den Header aus den letzten zwei Bytes und die Felder von vorn. Die neuen Bytes dahinter ignoriert er. Ein alter Decoder liefert also weiter alle bisherigen Werte. Ein neuer Decoder erkennt die Erweiterung daran, dass nach den Feldern von Bit 0–15 noch Bytes vor dem Header übrig sind.

Ergänzung im TTN-Decoder (`DecodebeeloggerPayload`, direkt nach der Schleife über die Header-Bits, `i` = bis dahin gelesene Bytes):

```javascript
if (data.length - 2 > i) {                 // Erweiterungsbyte vorhanden
    var ext = data[data.length - 3];
    if (ext & 0x01) { obj.Intervall = data[i]; i += 1; }
    if (ext & 0x02) { obj.Gewicht5 = bin16dec((data[i]<<8)|data[i+1]) / 100; i += 2; }
    if (ext & 0x04) { obj.Gewicht6 = bin16dec((data[i]<<8)|data[i+1]) / 100; i += 2; }
}
```

Mit Node.js geprüft: Der Wiki-Testvektor `04B2…F93F` dekodiert mit altem und neuem Decoder gleich. Derselbe Vektor mit Intervall 15, Gewicht5 12,34 und Gewicht6 −1,00 (32 Byte) liefert beim alten Decoder die bisherigen Werte und beim neuen zusätzlich die drei neuen Felder.

## Wann das Intervall mitkommt

Vorschlag: im selben Uplink wie die Sketch-ID (jeder 10.) und im ersten Uplink nach einem Intervall-Downlink. Das kostet 1 Byte, also etwa 5 ms Airtime bei SF7.

## Airtime und kürzestes Intervall

Berechnet nach Semtech AN1200.13 für EU868, 125 kHz, CR 4/5, 13 Byte LoRaWAN-Overhead. Gegenprobe: 17 Byte bei SF7 = 72 ms, das passt zur Messung (README: ca. 77 ms mit ID).

Payload: Temperatur, Akku, Solar, Waagen, Service, Header. Dazu bei Hexa/Octa das Erweiterungsbyte und alle 10 Uplinks 4 Byte Sketch-ID.

| Variante | Byte (mit ID) | SF7 | SF8 | SF9 | SF10 | SF11 | SF12 |
|---|---|---|---|---|---|---|---|
| Quad (heute) | 17 (21) | 72 ms | 123 ms | 226 ms | 453 ms | 905 ms | 1647 ms |
| Hexa | 22 (26) | 77 ms | 144 ms | 247 ms | 494 ms | 987 ms | 1810 ms |
| Octa | 26 (30) | 82 ms | 154 ms | 267 ms | 494 ms | 1069 ms | 1974 ms |

**Kürzestes Intervall** bei 30 s Airtime am Tag (TTN Fair Use), in Minuten:

| Variante | SF7 | SF8 | SF9 | SF10 | SF11 | SF12 |
|---|---|---|---|---|---|---|
| Quad | 3,5 | 6,0 | 11 | 22 | 44 | 80 |
| Hexa | 3,7 | 7,0 | 12 | 24 | 48 | 88 |
| Octa | 4,0 | 7,4 | 13 | 24 | 52 | 96 |

Ergebnis:
- Bei SF7 (fest in der Firmware) gehen Hexa und Octa auch mit 5 min. Ein Paket reicht, aufteilen ist nicht nötig.
- Bei SF8 reichen 10 min, bei SF9 15 min, bei SF10 30 min.
- Mit Licht, Luftdruck und Außenfeuchte kommen 5 Byte dazu, an der Aussage ändert das wenig.
- Die Firmware sollte zu kurze Intervalle per Downlink nur annehmen, wenn sie zum SF passen. Heute gilt fest 5–180 min bei SF7.

## Fragen an die Entwickler

1. Ist ein Erweiterungsbyte vor dem Header in Ordnung, oder gibt es schon einen Plan für Feld 16+? Im Quelltext ist ein dritter Header-Byte auskommentiert (`0x00FF0000`).
2. Feldname für das Intervall: `Intervall` oder eins der Aux-Felder? Bei LoRa sind Aux1 = Luftdruck, Aux2 = RSSI, Aux3 = Sketch-ID belegt.
3. Ignoriert `beelogger_log.php` unbekannte Felder im Webhook, oder lehnt es den Datensatz dann ab?
4. Soll der Server das Intervall aus seiner Konfiguration selbst per Downlink schicken (wie `T15` in der Antwort an WLAN-Logger)? Dann bräuchte er einen TTN-API-Key, siehe [`ttn_anleitung.md`](ttn_anleitung.md), Abschnitt „Downlink aus der Server-Software“.
5. Waage 7 und 8: Soll der Server `Gewicht7`/`Gewicht8` und einen Ordnernamen (`Hepta`/`Octa`) bekommen, oder ist 6 die Grenze?
6. Hexa-Hardware: Ist ein 3. HX711 geplant (Zusatzplatine, welche Pins)? Oder ist an andere Wägetechnik gedacht? Ohne neue Hardware bleibt der Payload-Teil für Waage 5–8 Vorarbeit.
7. Bei mehr als 2 Waagen fallen die Beutentemperaturen weg (Bit 6/7/9/10). Für Hexa ebenso?
