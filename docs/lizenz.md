# Lizenz und Einhaltung der Lizenzbedingungen

Stand: 06.11.2026

## Kurzfassung

- Die beelogger-Firmware (`src/`, `orginal_firmware_bibliotheken/beelogger_STM32_202610/`, `…/STM32_WLAN_LTE_230928/`) ist **(C) R. Schick – beelogger.de** und steht unter der **GNU General Public License v3 oder später** (Kopf von `STM32_LORA_260705.ino`).
- Die Änderungen in diesem Repo stehen unter derselben Lizenz: **GPL v3 oder später**. Volltext: [`LICENSE`](../LICENSE).
- Die Bibliotheken in `lib/` und `orginal_firmware_bibliotheken/beelogger_arduino_libs_2026_04/` sind unverändert übernommen und behalten ihre eigenen Lizenzen (Tabelle unten).

## Wie die Bedingungen der GPL v3 erfüllt werden

| Bedingung (GPL v3) | Umsetzung in diesem Repo |
|---|---|
| §4: Urheberrechts- und Lizenzhinweise unverändert lassen | Alle Köpfe der Originaldateien sind erhalten. Die Originale liegen zusätzlich unverändert in `orginal_firmware_bibliotheken/`. |
| §4: Lizenztext beilegen | `LICENSE` (GPL v3, Volltext) |
| §5a: Geänderte Dateien tragen einen deutlichen Hinweis mit Datum | Kopfzeilen „Geändert gegenüber beelogger.de STM32_LORA_260705 …“ in `STM32_LORA_260705.ino`, `STM32_LORA.ino`, `STM32_LORA_utility.ino`, `Config_LORA.h.example` (`Config_MULTI.h.example` ist das unveränderte Original) |
| §5a: Änderungen nachvollziehbar | `docs/aenderungen_gegen_260705.diff` (vollständig, gegen das Original) und README „Änderungen gegenüber 260705“; dazu die Git-Historie |
| §5c: Gesamtwerk unter GPL v3 | Ja, siehe `LICENSE` und dieser Abschnitt |
| §6: Quellcode verfügbar | Das Repo ist der vollständige Quellcode inkl. Build-Konfiguration (`platformio.ini`). Ausgenommen sind nur die LoRaWAN-Schlüssel (persönliche Zugangsdaten, kein Programmcode); Vorlage `src/Config_LORA.h.example`. |
| §7/§15/§16: kein Ausschluss von Gewährleistungshinweisen | Hinweise der Originale unverändert |

Kompilierte Firmware (`.bin`) wird hier **nicht** verteilt (sie enthielte Schlüssel). Wer Binaries weitergibt, muss nach §6 auch den Quellcode bereitstellen – ein Link auf dieses Repo genügt.

## Bibliotheken (unverändert)

| Bibliothek | Lizenz (laut Lizenzdatei bzw. Dateikopf) |
|---|---|
| beelogger_arduino-lmic-master (IBM LMIC) | Eclipse Public License 1.0 bzw. BSD (Arduino-Port) |
| Adafruit-MCP23017, Adafruit_Si7021, beelogger_BMP280, beelogger_SHT31 | BSD |
| Arduino-Temperature-Control-Library (DallasTemperature) | LGPL 2.1 |
| AS_BH1750 | LGPL |
| beelogger_arduinoFFT, beelogger_BME680, RF24, SdFat | GPL |
| DHTNew, HX711_Arduino_Library, SI7021-master, Sodaq_DS3231 | MIT |
| beelogger_ESP (WLan.txt) | Apache (laut Hinweis) |
| AltSoftSerial, Low-Power, OneWire, STM32duino_Low_Power, STM32RTC, beelogger_EE, beelogger_ESP_STM, beelogger_SIM800_*, beelogger_BME280, beelogger_DHT, New-LiquidCrystal | Lizenz in den Dateien bzw. beim Original-Projekt; hier unverändert wie von beelogger.de verteilt |

Die Tabelle ist eine Übersicht. Maßgeblich ist jeweils die Lizenzangabe in der Bibliothek selbst.

## Hinweis

Dieses Repo ist kein offizielles Projekt von beelogger.de. Original und Dokumentation: https://beelogger.de
