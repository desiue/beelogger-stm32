# Häufige Fehler: vom Anzeichen zur Ursache

[← Übersicht](README.md)

Stand: 18.11.2026 · gilt für: STM32 261107 (Spannungsgrenzen gleich wie 260705) · geprüft gegen Code: Commit e8aa70d (danach bis 9a536c5 in `src/` nur Kommentare und Solar-Vorgabe in `Config_LORA.h.example`, geprüft 19.11.2026)
(Spannungsgrenzen aus `src/Config_MULTI.h`, `src/STM32_beelogger.h`, `src/STM32_LORA_260705.ino`;
Hardware-Hinweise sind Erfahrungswerte aus dem Betrieb und dem beelogger-Support, nicht aus dem Code)

Diese Seite sammelt Fehler, die im Betrieb schon aufgetreten sind. Sie ist nach dem geordnet, **was man in der
Anzeige sieht**. Meldungen im seriellen Monitor, in TTN und vom Server stehen in der [Fehlersuche](fehlersuche.md).

Neuer Fall? Unten einen Abschnitt im selben Aufbau ergänzen: Anzeichen, Ursachen, Prüfen, Abhilfe.

## Übersicht

| Anzeichen in der Anzeige | wahrscheinliche Ursache | Abschnitt |
|---|---|---|
| Akkuspannung springt oder bricht kurz ein | Akku, Akkuhalter oder Jumper | [Akkuspannung springt](#akkuspannung-springt) |
| nur noch alle 2 Stunden ein Wert | Akku-Sparmodus | [Nur alle 2 Stunden ein Wert](#nur-alle-2-stunden-ein-wert) |
| Akku fällt Tag für Tag, obwohl die Sonne scheint | Ruhestrom zu hoch oder Laden gestört | [Akku wird leer gesaugt](#akku-wird-leer-gesaugt) |
| zwei Werte kurz hintereinander, danach normal | Neustart des Loggers | [Doppelte Werte, Neustart](#doppelte-werte-neustart) |
| Werte passen nicht zum Volk, springen zwischen zwei Niveaus | zwei Geräte schreiben in denselben Ordner | [Werte von zwei Loggern gemischt](#werte-von-zwei-loggern-gemischt) |
| Gewicht springt oder rauscht | Einstreuung auf die Waagenleitungen, HX711 | [Gewicht springt](#gewicht-springt) |
| einzelne Ausreißer bei Temperatur/Feuchte (z. B. −142,78 °C, 0 %), Schwarmalarm ohne Schwarm | Fehlmessung des BME280 | [BME280-Ausreißer](#bme280-ausreißer) |
| im Winter fehlt jeder zweite Wert | Mindestabstand auf dem Server | [Fehlersuche, Abschnitt 4](fehlersuche.md#4-server-wird-gespeichert) |

## Akkuspannung springt

**Anzeichen:** `VBatt` springt von Wert zu Wert um 0,1–0,3 V oder bricht einzeln ein, statt glatt zu verlaufen.

**Ursachen** (Erfahrung beelogger-Support):

- Akku mit hohem Innenwiderstand, z. B. alt, oder die Schutzschaltung im Akku arbeitet fehlerhaft.
- Übergangswiderstand am Akkuhalter.
- Jumper am Akku hochohmig, z. B. Stifte ohne Vergoldung.

**Prüfen:** Spannung mit dem Multimeter direkt am Akku und an der Platine messen, möglichst während der Logger
sendet. Ein Unterschied zeigt einen schlechten Kontakt.

**Abhilfe:** Akku im Halter etwas drehen, Jumper bewegen oder tauschen. Hilft das nicht, Akku tauschen.

Folge: Bricht die Spannung beim Messen unter 3,8 V ein, schaltet der Logger in den
[Akku-Sparmodus](#nur-alle-2-stunden-ein-wert). Bei starken Einbrüchen startet er neu ([Neustart](#doppelte-werte-neustart)).

## Nur alle 2 Stunden ein Wert

**Anzeichen:** Statt alle 5–15 min kommt nur noch alle 120 min ein Wert.

**Ursache:** Akku-Sparmodus. Liegt die gemessene Akkuspannung unter `VAlternativ` (3,8 V), weckt der Logger nur
noch alle `AlternativIntervallMinuten` (120 min). Zurück ins normale Intervall geht er erst über
`VSysnormal` = `VAlternativ` + 0,1 V (3,9 V). Unter `VMinimum` (3,75 V) misst und sendet er gar nicht mehr.
Ein Fern-Intervall per Downlink hebt den Sparmodus nicht auf. Werte für andere Akkus stehen in `src/Config_MULTI.h`.

**Prüfen:** `VBatt` vor der ersten Lücke ansehen. Liegen die gesendeten Werte deutlich über 3,9 V und trotzdem
kommen Lücken, eher an [springende Akkuspannung](#akkuspannung-springt) denken: Ein einzelner Einbruch reicht für
den Sparmodus. Erkennungszeichen: Die Lücke dauert 125 statt 120 min, und der erste Wert danach hat `Service` 1.
`Service` ist die Wachzeit des vorigen Laufs in Sekunden (`src/STM32_LORA_utility.ino:195`), 1 s heißt: Der Logger
ist 5 min nach dem letzten Senden aufgewacht, hat unter `VMinimum` gemessen, nichts gesendet und 120 min geschlafen.
Der LoRa-Zähler läuft über die Lücke durch, es geht also nichts verloren (gemessen 21.11.2026 an einem Feld-Logger:
13 solche Lücken in 8 Tagen bei gesendeten Werten von 3,9–4,0 V).

**Abhilfe:** Ursache für die niedrige Spannung beheben (Laden, Ruhestrom, Kontakte). Der Logger kehrt von selbst
zurück.

## Akku wird leer gesaugt

**Anzeichen:** `VBatt` sinkt über Tage, auch an sonnigen Tagen. Ein Logger daneben mit gleicher Solarzelle hält
seine Spannung.

**Ursachen:**

- **Ruhestrom zu hoch.** Normal sind beim BlackPill F411 etwa 18 µA im Schlaf (gemessen, siehe
  [hardware.md](hardware.md)), beim BluePill F103 weniger. Beim BlackPill-Modul **V3.1** wurden mehrere Hundert µA
  gemessen. Ursache war in einem Fall der Spannungsregler auf dem Modul. Der beelogger versorgt das Modul über den
  eigenen MCP1702-33, der Regler auf dem Modul wird nicht gebraucht. Nach dem Entfernen lag der Ruhestrom bei
  etwa 8 µA. Auf einer anderen Platine blieb der Strom auch ohne Regler hoch. Der Regler ist also nicht in jedem
  Fall die einzige Ursache.
- **Schwebende SPI-Leitungen zum RFM95.** Gemessen auf der Platine BlackPill 1.5.1 (Mike, 20.11.2026, sieben
  BlackPill-Boards, siehe [boards_blackpill_bluepill.md](boards_blackpill_bluepill.md)): Der RFM95 hatte dort auch
  bei gesperrtem Lastschalter 3,28 V (Multimeter), und im Schlaf hält der STM32 NSS, SCK und MOSI nicht fest. Je nach Board flossen dadurch 50 bis
  650 µA. Mit je einem Widerstand 100 kΩ von NSS, SCK und MOSI nach 3,3 V lagen alle sieben Boards bei 9,7 bis
  9,9 µA (Board 1 mit Regler 18,7 µA). Das war ein Aufbau, andere Platinen sind nicht gemessen.
- **Laden gestört:** Solarzelle verschattet oder verschmutzt, Lademodul defekt. `VSolar` im Verlauf ansehen.
  Im Winter reicht die Sonne an manchen Standorten nicht aus.
- Ein Regensensor kann den Ruhestrom ebenfalls erhöhen.

**Prüfen:** Ruhestrom mit dem Multimeter in Reihe zum Akku messen, während der Logger schläft. Das Messgerät senkt
die angezeigte Akkuspannung, das ist normal.

**Abhilfe:** Beim BlackPill V3.1 den Regler auf dem Modul entfernen (vorher Ruhestrom messen). Sonst Baugruppen
einzeln abziehen und messen, bis der Verbraucher gefunden ist.

## Doppelte Werte, Neustart

**Anzeichen:** Zwei Werte im Abstand von weniger als einer Minute. In TTN beginnt der Zähler (`FCnt`) des
Geräts wieder bei 0 (ABP) oder das Gerät meldet sich neu an (OTAA).

**Ursache:** Der Logger ist neu gestartet. Nach dem Start sendet er gleich, unabhängig vom Intervall.
Häufig liegt es an der Stromversorgung: Wackelkontakt am Akku, schwacher Akku, der beim Senden einbricht.

**Prüfen:** In TTN unter *End device → Live data* auf den Zählerstand achten. Am seriellen Monitor steht `POR`
(Stromausfall) oder `WDT: Neustart` ([Fehlersuche](fehlersuche.md#1-logger-serieller-monitor)).

**Abhilfe:** wie bei [Akkuspannung springt](#akkuspannung-springt). Kommen `WDT`-Meldungen, ist es ein
Hänger in der Firmware, kein Stromproblem.

## Werte von zwei Loggern gemischt

**Anzeichen:** Die Werte springen zwischen zwei Niveaus hin und her, oder es kommen deutlich mehr Werte, als das
Intervall erlaubt.

**Ursache:** Zwei TTN-Anwendungen haben einen Webhook auf denselben Logger-Ordner, z. B. ein altes Gerät nach dem
Umzug auf neue Schlüssel.

**Prüfen:**

- In TTN bei **allen** eigenen Anwendungen unter *Integrations → Webhooks* die URLs ansehen.
- Bei jedem dieser Geräte die Live data oder den letzten Empfang prüfen: Sendet mehr als eines?
- Auf dem eigenen Server die Aufrufe von `beelogger_log.php` im Zugriffslog zählen und mit den Zeilen in der CSV
  vergleichen. Zwei Sender zeigen sich als zwei Zeitraster nebeneinander.

**Abhilfe:** Den Webhook des falschen Geräts löschen. Mehrere Webhooks pro Gerät auf verschiedene Server sind
dagegen erlaubt ([TTN einrichten](ttn_einrichten.md)).

## Gewicht springt

**Anzeichen:** Das Gewicht springt ohne Durchsicht, rauscht stark oder hat nach Gewittern Ausreißer.

**Ursachen:**

- Einstreuung auf lange Leitungen zu den Wägezellen. Ein Quad-beelogger ist bis zu 2 m breit, bei Gewitter wird
  darin Spannung induziert, bis hin zum Defekt.
- schlechtes HX711-Modul. In einem Fall wurde es nach dem Tausch der Module deutlich ruhiger.
- Kalibriertemperatur `99.9` im Betrieb, falsche Temperaturkompensation: siehe
  [Fehlersuche, Abschnitt 4](fehlersuche.md#4-server-wird-gespeichert).

**Abhilfe:** Schirme der Wägezellenleitungen EMV-gerecht anschließen, am besten auf ein Blechgehäuse für die
Elektronik. Sensorleitungen geschirmt oder sehr kurz. HX711-Module tauschen.

## BME280-Ausreißer

**Anzeichen:** Einzelne Zeilen mit unmöglichen Werten, etwa −142,78 °C und 0 % Feuchte, in allen Kanälen. Der Server meldet daraufhin Schwarmalarm (Beispiel Q2, 21.11.2026, 19:07 und 19:22 MEZ; Rohpayload in TTN identisch, also Fehlmessung am Logger, nicht Übertragung).

**Ursachen:** Fehllesung des BME280 über I2C, vermutlich Kontakt oder Feuchte am Sensor (ungeprüft, Besuch am Q2 offen).

**Abhilfe:** Der Sketch verwirft seit Commit 25aae17 Temperatur außerhalb −30…60 °C und Feuchte unter 2,0 oder ab 99,9 % (`Sensor_BME280()` in `src/STM32_I2C_sensors.ino`, Grenzen wie beim DHT in `beelogger_DHT.ino:44-45`); statt des Werts geht der Platzhalter `No_Val`/`No_Value` raus. Der Luftdruck wird nicht gefiltert. Gilt erst nach Flashen dieser Firmware (Stand 23.11.2026: gebaut, auf keinem Logger geflasht). Bis dahin hilft nur Sensor tauschen oder Kontakte prüfen.
