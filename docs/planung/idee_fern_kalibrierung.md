# Idee: Waagen und Konfiguration aus der Ferne einstellen

[← Übersicht](../README.md)

Stand 20.11.2026, **zur Diskussion**, nichts davon ist umgesetzt (kein Code, keine Messung). Die Idee stammt aus
dem Gespräch vom 20.11.2026; die Zahlen unten sind Rechnung, keine Hardware-Tests.

## Ziel

Alles, wofür man heute einen Laptop am Stand, Software und eine neu gebaute Firmware braucht, soll per Downlink
(FPort 12, siehe [fern_einstellungen.md](../fern_einstellungen.md)) gehen. Heute sind Tara und Skalierung der Waagen feste
Werte in `Config_MULTI.h` (`Taragewicht`, `Skalierung`), gelesen in `src/beelogger_HX711_STM.ino:36-38`.

## Warum

- **Wasserschaden am Logger:** Der Logger wird getauscht, die Wiegebalken bleiben unter den Beuten. Danach
  stimmen Tara und Skalierung nicht mehr, und die Beuten sind inzwischen schwerer.
- **HX711 mit Zittern:** Manche Chips schwanken bei jeder Messung. Das fällt oft erst im Betrieb auf. Der
  HX711 ist gesteckt und wird getauscht; der neue Chip hat andere Rohwerte.
- Weniger Aufwand am Stand: Logger oder HX711 tauschen, Downlink senden, fertig.

## Verfahren A: Stapel (alle 4 Waagen auf einmal)

Die 4 Waagen werden übereinander gestapelt (Waage 1 unten), oben liegt ein bekanntes Prüfgewicht. Jede Waage trägt
alles über ihr. Die Wiegebalken sind laut Mike mit 100 kg je Seite belastbar, eine Waage also mit 200 kg bei
gleichmäßiger Last, die Überlast der untersten Waage ist damit kein Problem.

1. Kommando „ohne Last“: Logger mittelt die Rohwerte aller Waagen und merkt sie (Nullpunkt).
2. Prüfgewicht auflegen, Kommando „mit Last X g“: Logger mittelt erneut. Skalierung je Waage =
   (Rohwert mit Last − Rohwert ohne Last) / X. Das Prüfgewicht wirkt auf alle gleich, die Eigengewichte der Waagen
   spielen für die Skalierung keine Rolle.
3. Tara folgt aus dem Nullpunkt, wenn der Zustand „ohne Last“ wirklich leer war; im Stapel nur, wenn die Gewichte
   darüber auf Gramm genau bekannt sind.

Fehlerquellen: Reibung und Kippen zwischen den Waagen, außermittige Last, Kanal B (Verstärkung 32) gegenüber
Kanal A (128). Letzteres ist unkritisch, weil die Skalierung je Waage gespeichert wird.

## Verfahren B: Waage unter der Beute (nach Loggertausch)

Das Gewicht der Beute ist bekannt (gewogen oder vom letzten Stand). Kommando „Waage n hat jetzt X g“ setzt die
Skalierung neu (oder bei bekannter Skalierung nur die Tara). Das deckt den Fall „Logger/HX711 getauscht, Waagen
bleiben“ ab. Genauigkeit hängt an der Kenntnis des Gewichts.

## Absicherung (Entwurf)

- Neue Werte nur annehmen, wenn sie plausibel sind (Skalierung nicht um Faktor 2 neben dem alten Wert), sonst ganzen
  Downlink verwerfen wie bei den übrigen Kommandos.
- Alten Wert als Rückfall behalten, Rücklesen mit Prüfsumme wie beim Plan (`src/STM32_sendeplan.ino:289-296`).
- Rohwerte und neue Werte im nächsten Uplink zurückmelden (Quittung), damit man am Server sieht, was angekommen ist.
- Während der Kalibrierung kein Sofort-Senden aus dem Gewichtssprung.

## Mehr Werte per Downlink

Mike will so viel wie möglich aus der Ferne einstellen können (auch Sensor-Zuordnung, siehe
[Möglichkeiten](../fern_einstellungen.md#möglichkeiten-nicht-geplant)). Die Entscheidung vom 19.11.2026, Waagen-Kalibrierung
nicht per Downlink, ist damit **überholt** (Mike 20.11.2026). `VMinimum` und LoRa-Schlüssel bleiben starr (Tiefentladung bzw. Sicherheit).

## Offene Punkte

1. **Tag/Nacht nach Solarspannung** ist bei Beleuchtung (z. B. Straßenlaterne am Stand) unsicher. Aufgabe: nächtliche
   Solarspannung im Server-CSV der Stände mit Laterne (Opum) und ohne (Fischeln) vergleichen, danach Grenze `05`
   oder Alternative (Uhrzeit im Plan) wählen.
2. **Blue Pills (F103) weiter unterstützen:** Mike baut weiter Blue Pills, sie haben weniger Ruhestrom als Black
   Pills (Messung steht aus, Mike misst), und die Community nutzt sie. FPort 12 gibt es dort heute nicht (Backup-Register
   belegt, Flash-Plan setzt 256 KB voraus, `src/STM32_beelogger.h:21-25`, `src/STM32_sendeplan.ino:87`). Für die
   Fern-Kalibrierung bräuchte der F103 einen Plan in einer 1-KB-Flash-Seite und Zustand ohne Backup-Register.
3. Platz im Flash bei 4 Waagen (q2 71 984 Byte) und auf dem F103 prüfen.
4. Reihenfolge: erst Stromvergleich Blue/Black Pill (Mike), dann entscheiden, ob F103 mitgezogen wird oder die
   Fern-Kalibrierung zuerst nur auf F4 kommt.
