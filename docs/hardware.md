# Hardware: Platine, Baugruppen, Pinbelegung

[← Übersicht](README.md)

Stand: 10.11.2026 · gilt für: STM32 261107 (Pinbelegung gleich wie 260705) · geprüft gegen Code: Commit a3ac566

Die **Pinbelegung** stammt aus dem Code (`src/STM32_beelogger.h`, `src/STM32_LORA.ino`) und gilt für alle
Platinen. Die **Bauteilwerte** unter „Baugruppen“ sind aus dem Schaltplanbild `beelogger_STM32F4` gelesen.
Sie stehen nicht im Code und sind nicht nachgemessen. Vor einem Nachbau gegen die Fertigungsdaten von
beelogger.de prüfen.

## Mikrocontroller

| Board | Chip | Flash | PlatformIO-Umgebung |
|---|---|---|---|
| BlackPill (WeAct) | STM32F411CE | 512 KB | `blackpill_f411ce` |
| BlackPill, ältere | STM32F401CC | 256 KB | `blackpill_f401cc` |
| BluePill | STM32F103CB | 128 KB | `bluepill_f103c8` (gebaut als F103CB) |

Der F103C8 (64 KB) reicht für LoRa nicht, der Code bricht mit `#error` ab. Andere Chips ebenfalls
(`#error " Board Part Number / Processor not supported"` in `STM32_beelogger.h`).

Der 32,768-kHz-Uhrenquarz (LSE) ist Pflicht: Die RTC läuft darauf (`rtc_stm.setClockSource(LSE_CLOCK)`)
und weckt den Logger aus dem Standby.

## Pinbelegung (aus dem Code)

| Funktion | Pin | Name im Code |
|---|---|---|
| LoRa NSS (Chip Select) | PA4 | `LMIC_NSS` |
| LoRa SCK / MISO / MOSI | PA5 / PA6 / PA7 | `LMIC_SCK`, `LMIC_MISO`, `LMIC_MOSI` |
| LoRa DIO0 / DIO1 | PA2 / PA3 | `LMIC_DIO0`, `LMIC_DIO1` |
| LoRa DIO2, RXTX, RST | nicht angeschlossen | `LMIC_UNUSED_PIN` |
| Wecken / Arbeitsschalter | PA0 (`INPUT_PULLDOWN`) | `WakeUp_Pin` |
| One-Wire (DS18B20) | PA8 | `ONE_WIRE_BUS` |
| Serielle Konsole, Flashen (FTDI) | PA9 (TX) / PA10 (RX) | `SER_TX`, `SER_RX` |
| Akkuspannung (ADC) | PB0 | `Batterie_messen` |
| Solarspannung (ADC) | PB1 | `Solarzelle_messen` |
| Peripherie ein (HIGH = ein) | PB5 | `Power_Pin` |
| I2C SCL / SDA | PB6 / PB7 | Standard-I2C |
| HX711 Nr. 1 DT / SCK (Waage 1 und 2) | PB8 / PB9 | `HX711_DT[0]`, `HX711_SCK[0]` |
| HX711 Nr. 2 DT / SCK (Waage 3 und 4) | BlackPill PB2 / PB10, BluePill PB10 / PB11 | `HX711_DT[1]`, `HX711_SCK[1]` |
| DHT 1 / DHT 2 | PB12 / PB13 | `DHT_Sensor_Pin[]` |
| LED | PC13 (LOW = an) | `blink_LED()` |
| ESP-Reset / SIM-Power (nicht für LoRa) | PB4 | `ESP_RESET`, `SIM_Power_Pin` |

Achtung bei HX711 Nr. 2: BlackPill und BluePill sind verschieden verdrahtet. Eine Platine für die BluePill
braucht für Waage 3 und 4 andere Leitungen als eine für die BlackPill.

## Baugruppen (laut Schaltplan, nicht im Code prüfbar)

- **Funkmodul:** RFM95 (SX1276, 868 MHz). Hängt an der geschalteten 3,3-V-Schiene, ist also im Schlaf aus.
  Nie ohne Antenne senden.
- **Waagen:** bis zu zwei HX711 mit je zwei Kanälen. Kanal A = Gain 128, Kanal B = Gain 32
  (`beelogger_HX711_STM.ino`). HX711 Nr. 1: Waage 1 (A) und 2 (B), Nr. 2: Waage 3 (A) und 4 (B).
- **Temperatur:** DS18B20 an One-Wire mit Pull-up 4,7 kΩ. Optional zwei DHT22 und I2C-Sensoren
  (BME280, SHT31, Si7021, BH1750).
- **EEPROM:** AT24C-Baustein am I2C. Die Original-Firmware bis 230711 legte darin den Frame-Counter ab.
  Ab 260705 liegt er im RTC-Backup-Register (siehe [speicher.md](speicher.md)), der EEPROM-Code dafür
  steht hinter `#if 0`.
- **Stromversorgung:** eine Li-Ion-Zelle (18650), Solarzelle mit Laderegler SPV1040 (MPPT), zwei
  Spannungsregler MCP1702-33. Einer versorgt den Mikrocontroller dauernd, der zweite die Peripherie.
- **Lastschalter:** PB5 schaltet über Transistor und P-MOSFET die Peripherie-Schiene (Sensoren, HX711,
  RFM95). Vor dem Schlafen setzt die Firmware PB5 auf LOW (`System_ShutDown()`).
- **Spannungsteiler:** 1 MΩ / 470 kΩ mit 100 nF für Akku (PB0) und Solar (PB1). Die Kondensatoren halten
  die Spannung am hochohmigen Teiler während der Wandlung stabil.
- **Optional:** serielle Leiste für FTDI, ESP8266-Fassung, SIM800 (für LoRa nicht nötig).

Strom gemessen an beelogger08 (BlackPill F411): Schlaf 18,1 µA, wach 24–34 mA (siehe
[abp_otaa.md](abp_otaa.md)).

## LoRa-Modul je Platinenversion

Nach der Seite „beelogger – LoRaWAN“ auf beelogger.de:

- **beelogger STM32 Version 2.0:** Das RFM95 wird direkt auf die Platine gelötet. Die 3,3-V-Versorgung, die
  auch für das ESP8266 vorgesehen ist, muss bestückt sein, dazu eine Drahtbrücke zwischen den mit *w*
  beschrifteten Lötpunkten.
- **Platinen bis Version 1.2:** Das RFM95 sitzt auf einem LoRa-Shield (Version 1.4; 1.3 hatte einen
  Layoutfehler bei der 5-V-Versorgung des HX711). Das Shield nimmt auch den zweiten HX711 für Waage 3 und 4 auf.
- **Antenne:** Draht mit 86 mm freier Länge (λ/4 bei 868 MHz), 0,6–0,8 mm stark. Alternativ eine
  868-MHz-Antenne über IPEX- oder SMA-Buchse.

Shield-Platine, Fertigungsdaten und Bauanleitung sind urheberrechtlich geschützt (T. Gurzan,
S. Ritzhaupt) und nur zur privaten Nutzung freigegeben. Sie stehen deshalb nicht hier. Download auf
beelogger.de.

## Spannungsmessung

ADC mit 12 Bit (`analogReadResolution(12)`). `Messe_Spannung()` in `STM32_LORA_utility.ino` mittelt
16 Messungen und rechnet mit dem Kalibrierpaar aus `Config_MULTI.h`:

```
Spannung [V] = map(ADC-Mittelwert, 0, Kalib_Bitwert, 0, Kalib_Spannung) / 1000
```

Die Vorlage hat `Kalib_Spannung = 1000`, `Kalib_Bitwert = 100`. Diese Werte sind Platzhalter und müssen je
Gerät ersetzt werden: [bauen_flashen.md](bauen_flashen.md#akkuspannung-kalibrieren).

Plausibilität: Der Teiler 470 / (1000 + 470) = 0,32 bringt 4,2 V Akku auf etwa 1,34 V am Pin, also rund
1670 Bit bei 3,3 V Referenz. An beelogger08 gemessen: 4,20 V bei 1648 Bit. Das passt zum Teiler.

## Offene Punkte

- Eine Stückliste mit geprüften Bauteilwerten fehlt.
- Ob der Schaltplan `beelogger_STM32F4` zur Platine 2.0 oder zu einem älteren Stand gehört, ist nicht belegt.
  Die Pinbelegung oben gilt unabhängig davon.
