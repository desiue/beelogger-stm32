# beelogger LoRa für ATmega328 – Vorschlag 261107

Der ATmega-LoRa-Sketch **beelogger_LORA_250326** von [beelogger.de](https://beelogger.de/?page_id=200563) (beelogger-Universal 1.x und beeloggerSMD) mit **zwei kleinen Änderungen als Vorschlag**. Es sind dieselben zwei Stellen, die mir im STM32-Sketch 260705 aufgefallen sind (siehe [README](../docs/entwicklung.md#gefundene-fehler)). Alles andere ist wie im Original: ABP, Messablauf, Payload, Server.

Dazu als zweiter Ordner eine Fassung mit **OTAA**: [beelogger_LORA_OTAA_261108](#otaa--vorschlag-261108).

**Stand 08.11.2026:** baut für alle drei Boards. Getestet auf einem Steckbrett mit Uno (16 MHz), RFM95 und einem Nano als Uhr ([testaufbau_uno.md](testaufbau_uno.md)). **Auf einem echten beelogger und mit Pro Mini 8 MHz noch nicht getestet**, weil mir kein ATmega-beelogger zur Verfügung steht. Bitte als Vorschlag verstehen. Falls ich etwas übersehen habe, freue ich mich über einen Hinweis.

## Änderungen

| Stelle | Vorher | Nachher | Hintergrund |
|---|---|---|---|
| `Multi_LORA.ino`, `prep_payload()` | `if (LMIC.seqnoUp % 10 == 0)` | `if (LoRaSeqCounter % 10 == 0)` | `LMIC_reset()` löscht die LMIC-Struktur, `seqnoUp` ist danach 0 und wird erst nach `prep_payload()` gesetzt. Nach meinem Verständnis geht die Sketch-ID (4 Byte) deshalb in jedem Uplink mit statt, wie im Kommentar gedacht, in jedem 10. Am STM32 habe ich das in TTN beobachtet, am ATmega nicht. |
| `Multi_LORA.ino`, `setup_LORA()` | – | `LMIC_setAdrMode(0);` | `LMIC_reset()` setzt ADR an. Da der Logger nach jedem Senden schläft, kann er LinkADRReq vermutlich nicht beantworten, und TTN wiederholt sie. Mit der TTN-Einstellung „ADR Disabled“ lässt sich das auch ohne neue Firmware vermeiden. |
| `beelogger_LORA_261107.ino` | `l_ID = 250326` | `l_ID = 261107` | Sketch-ID, damit man am Server sieht, welche Fassung läuft. |

Vollständiger Diff: [`aenderungen_gegen_250326.diff`](aenderungen_gegen_250326.diff). Bibliothek: Original 2026_04, **unverändert** (`DISABLE_JOIN` bleibt gesetzt).

## Speicher (PlatformIO, avr-gcc 7.3, Bibliotheken 2026_04)

| Board | Original 250326 | 261107 |
|---|---|---|
| Pro Mini 8 MHz (beeloggerSMD) | Flash 26 278 B, RAM 1 439 B | Flash 26 264 B, RAM 1 437 B |
| Pro Mini 16 MHz (Universal 1.x) | Flash 26 292 B, RAM 1 439 B | Flash 26 280 B, RAM 1 439 B |
| Nano 16 MHz (Universal 1.x) | Flash 26 292 B, RAM 1 439 B | Flash 26 280 B, RAM 1 439 B |

Verfügbar: 30 720 B Flash (32 KB minus Bootloader), 2 048 B RAM. Der Speicherbedarf bleibt praktisch gleich. Die absoluten Werte hängen auch vom Ablageort ab, weil die LMIC für ihre Fehlermeldungen die Dateipfade einbaut (ein frischer Klon unter einem längeren Pfad: 26 406 B). Auch die Arduino IDE oder eine eigene `Config_MULTI.h` ergeben etwas andere Werte.

## OTAA – Vorschlag 261108

Ordner `beelogger_LORA_OTAA_261108/`: derselbe Sketch wie 261107, zusätzlich mit OTAA. Umschalten in `Config_LORA.h`: `#define LORA_OTAA 1` (OTAA, Vorgabe in diesem Ordner) oder `0` (ABP wie bisher). Sketch-ID 261108.

Die beelogger-LMIC ist mit `DISABLE_JOIN` gebaut. Deshalb liegt eine Kopie der LMIC im Sketch-Ordner unter `src/lmic_otaa/`, die Arduino IDE baut `src/` automatisch mit. Die installierte Bibliothek bleibt unverändert, andere Sketche bauen wie bisher. Die Kopie ist dieselbe wie im STM32-Paket:
- `DISABLE_JOIN` aus,
- `LMIC_restoreJoin()`: stellt die Session aus den gespeicherten Join-Werten wieder her,
- Link-Check-Anfrage über `LMIC.lchkReq`.

Ablauf:
- Erstes Senden ohne gültige Session: Join, danach geht der wartende Uplink mit FCnt 0 raus. Für Join und Uplink wartet der Sketch bis 30 s statt 8 s (`JOIN_TM_OUT`). Klappt es nicht, neuer Versuch im nächsten Messzyklus.
- Nach dem Join speichert der Sketch DevAddr, NetID, AppNonce, DevNonce und die RX-Werte im EEPROM ab 0x60 (15 Byte). Die Session-Schlüssel stehen nicht im EEPROM, sie werden vor jedem Senden aus APPKEY neu berechnet. Der Frame-Counter bleibt wie bei ABP in `LoRaSeqCounter` (EEPROM 0x20–0x52).
- Reset oder Batteriewechsel: kein neuer Join, die Session kommt aus dem EEPROM, FCnt läuft weiter.
- Alle 144 Uplinks (bei 10 min etwa einmal am Tag) hängt der Sketch einen Link-Check an den Uplink. Bleiben drei Link-Checks in Folge ohne Antwort, joint er neu. Das fängt z. B. ein in TTN gelöschtes oder neu angelegtes Gerät ab.
- Neuer Join auch, wenn andere Schlüssel aufgespielt werden (Prüfsumme über DEVEUI und APPKEY), und vor dem Überlauf des 16-Bit-Zählers (bei 65 000).

TTN: Gerät mit OTAA anlegen, LoRaWAN 1.0.2, Regional Parameters 1.0.2 rev B, Frequenzplan Europe 863-870 (TTN). Wie bei ABP: ADR Disabled, Status periodicity 0 ([TTN-Anleitung](../docs/ttn_einrichten.md)). In `Config_LORA.h` DEVEUI und APPEUI (JoinEUI) mit LSB zuerst, APPKEY mit MSB zuerst eintragen (die TTN-Konsole kann die Reihenfolge umschalten).

Speicher (arduino-cli, avr-gcc 7.3, Bibliotheken 2026_04, 1 DS18B20, 1 Waage):

| Board | ABP 261107 | OTAA 261108 |
|---|---|---|
| Pro Mini 8 MHz (beeloggerSMD) | Flash 26 300 B | Flash 28 696 B |
| Pro Mini 16 MHz (Universal 1.x) | Flash 26 314 B | Flash 28 710 B |
| Nano 16 MHz (Universal 1.x) | Flash 26 314 B | Flash 28 710 B |

OTAA braucht etwa 2,4 KB mehr Flash. Bis 30 720 B bleiben damit knapp 2 KB, das reicht für **einen** weiteren Sensor. Gemessen mit OTAA auf Pro Mini 8 MHz (anderer Ablageort, Grundwert 28 788 B):

| Zusätzlich | Flash | passt |
|---|---|---|
| 2. DS18B20 | 28 814 B | ja |
| SHT31 | 29 478 B | ja |
| Si7021 | 29 446 B | ja |
| BH1750 (Licht) | 29 638 B | ja |
| 2. Waage | 30 036 B | ja |
| DHT | 30 066 B | ja |
| BME280 | 32 830 B | **nein** |
| 2 × SHT31 + 2. Waage + Licht | 31 634 B | nein |
| DHT + 2. Waage + Licht | 32 196 B | nein |

RAM: 1 370–1 500 B von 2 048 B, je nach Ablageort (die LMIC legt die Dateipfade für ihre Fehlermeldungen im RAM ab).

**Getestet** auf dem Steckbrett ([testaufbau_uno.md](testaufbau_uno.md)), Uno 16 MHz mit RFM95, TTN, Gerät mit den Einstellungen oben:
- Join beim ersten Senden nach etwa 12 s, Uplink mit FCnt 0 kommt an, RX1-Delay 1 s aus dem Join-Accept.
- Nächster Zyklus und Reset (neu aufgespielt): kein neuer Join, Session aus dem EEPROM, FCnt läuft weiter.
- Link-Check (zum Test bei jedem Uplink): Antwort kommt an. Danach Session in TTN gelöscht: drei Link-Checks ohne Antwort, beim nächsten Senden neuer Join, FCnt wieder ab 0.
- I2C gestört (SDA an GND bzw. offen): Ohne I2C-Timeout hängt der Sketch dauerhaft, auch nach Lösen. Seit 08.11.2026 setzen beide Fassungen (ABP und OTAA) `Wire.setWireTimeout(25000, true)` vor `rtc.begin()` (+34 B bzw. +22 B Flash): Logger sendet weiter, nach Wiederverbinden sofort normal. Bei totem Bus bleibt /INT der Uhr LOW, der Sketch wertet das wie den Service-Schalter (Wecken alle 5 min, Service-Wert 99 bzw. 1).
- Dauerlauf seit 08.11.2026 (10-min-Takt): nach einem Neustart um 11:34 bis 13:03 FCnt 22–31 lückenlos in TTN, kein neuer Join, kein Timeout. Läuft weiter.

**Nicht getestet:** Pro Mini mit 8 MHz. Der Join braucht die Empfangsfenster (Join-Accept nach 5 s). Wenn der Join dort nicht klappt, liegt es vermutlich am RX-Timing. Bitte melden, dann sehe ich es mir an.

Diff gegen das Original 250326 (Sketch und LMIC-Kopie gegen die Bibliothek 2026_04): [`otaa_aenderungen_gegen_250326.diff`](otaa_aenderungen_gegen_250326.diff).

## Selbst bauen

**Arduino IDE** (wie beim Original, siehe [Programme laden](https://beelogger.de/systeme/universal/alle_programmcodes/programme_laden/)):
1. Repo holen: `git clone https://github.com/desiue/beelogger-stm32.git` oder auf GitHub „Code → Download ZIP“.
2. beelogger-Bibliotheken installieren, falls noch nicht vorhanden. Gebaut wurde mit dem Paket 2026_04 von [beelogger.de](https://beelogger.de/?page_id=201159), eine Kopie liegt unter [`orginal_firmware_bibliotheken/beelogger_arduino_libs_2026_04/`](../orginal_firmware_bibliotheken/beelogger_arduino_libs_2026_04/).
3. Ordner `atmega_lora/beelogger_LORA_261107/` öffnen (`beelogger_LORA_261107.ino`). Für OTAA stattdessen `atmega_lora/beelogger_LORA_OTAA_261108/`, die LMIC-Kopie in `src/` wird automatisch mitgebaut.
4. Eigene `Config_LORA.h` und `Config_MULTI.h` hineinkopieren. Die Dateien im Ordner sind die Platzhalter aus dem Download.
5. Board wie beim Original wählen: beeloggerSMD = Arduino Pro Mini 3,3 V 8 MHz, Universal 1.x = Pro Mini 5 V 16 MHz oder Nano. Hochladen.

Wer schon einen eigenen Sketch 250326 hat, kann stattdessen nur die zwei Zeilen aus der Tabelle oben ändern.

**PlatformIO** (nur zum Prüfen, ob es baut und in den Speicher passt): im Ordner `atmega_lora/` `pio run` aufrufen (Umgebungen `promini8`, `promini16`, `nano`). Die Bibliotheken kommen aus dem Repo. Aufgespielt wird mit der Arduino IDE.

Der Frame-Counter liegt im EEPROM. Beim Aufspielen über den Bootloader (USB-Seriell, wie üblich) bleibt er erhalten. Ein ISP-Programmer kann das EEPROM löschen. Dann den Zähler mit `Set_LORA_Seq` aus dem Original-Paket setzen oder in TTN zurücksetzen.

## Test auf einem Logger

1. In TTN unter Live data einen Uplink vor und einen nach dem Aufspielen vergleichen:
   - FCnt läuft weiter (kein Sprung auf 0),
   - der Uplink hat das ADR-Bit nicht mehr (in den Uplink-Details `"adr": false` oder kein `adr`),
   - Payload-Länge: die Sketch-ID kommt nur noch bei jedem 10. FCnt mit, die übrigen Uplinks sind 4 Byte kürzer.
2. Am beelogger-Server: Werte kommen wie bisher, die Sketch-ID zeigt 261107.
3. Einen Tag laufen lassen und in TTN die Zahl der Downlinks ansehen. Mit den Einstellungen aus der [TTN-Anleitung](../docs/ttn_einrichten.md) (ADR Disabled, Status periodicity 0) sollten kaum noch welche kommen.

Wenn etwas anders aussieht als hier beschrieben, bitte melden. Dann habe ich mich vermutlich getäuscht.

Getestet habe ich auf einem Steckbrett mit Uno, RFM95 und einem Nano als Uhr: [testaufbau_uno.md](testaufbau_uno.md).

## Lizenz

GPL v3 wie das Original, © R. Schick / Thorsten Gurzan, beelogger.de.
