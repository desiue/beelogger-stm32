# beelogger STM32 – LoRaWAN-Firmware (PlatformIO)

Die beelogger-Firmware **STM32_LORA_260705** von [beelogger.de](https://beelogger.de) als PlatformIO-Projekt für VS Code, mit Erweiterungen. Gefundene Fehler werden behoben und dokumentiert, damit die beelogger-Entwickler sie übernehmen können.

**Download für die Arduino IDE:** [beelogger_STM32_LORA_261107.zip](https://github.com/desiue/beelogger-stm32/releases/download/STM32_LORA_261107/beelogger_STM32_LORA_261107.zip) ([Release](https://github.com/desiue/beelogger-stm32/releases/tag/STM32_LORA_261107)) – Sketch-Ordner wie bei beelogger.de, ohne Schlüssel. Neu bauen mit `scripts/lieferpaket.sh`.

**Auf einen Blick**

- **Maximal kompatibel zum Original:** Messablauf, Waagenzahl, Payload-Format und beelogger-Server bleiben wie beim Original 260705. Neu sind nur Downlinks (ABP und OTAA) und Fehlerkorrekturen. Weitergehende Ideen (mehr Waagen, andere Payload, eigener Sendeplan) kommen nicht in `main`, sondern in einen eigenen Branch mit eigenem Server ([Idee](docs/planung/idee_sendeplan_schwarmalarm.md), [Payload](docs/planung/vorschlag_payload_erweiterung.md)).
- **Sendeintervall aus der Ferne** per TTN-Downlink (FPort 10, 5–180 min), mit ABP und OTAA.
- **Uhrzeit und Intervall vom Server** wie bei WLAN-Loggern: Serverquittung per Downlink FPort 11 und `server/lora_downlink.php` (Branch `p7-serverquittung`, noch nicht in `main`).
- **OTAA** für neue Geräte, ABP für vorhandene Feldgeräte – eine Codebasis, umschaltbar per `#define LORA_OTAA`.
- **BluePill (F103) und BlackPill (F411/F401)**, alle vier Kombinationen auf Testgeräten abgenommen.
- **Fehler behoben:** Sketch-ID in jedem Uplink, TTN-Downlink-Schleife (Einstellungen dokumentiert), ADR-Bit.
- **ATmega-Logger (Universal 1.x, beeloggerSMD):** Vorschlag mit denselben zwei kleinen Änderungen (Sketch-ID, ADR) für den Original-Sketch 250326, sonst unverändert (ABP). Dazu eine OTAA-Variante (261108), seit 08.11.2026 im Dauerlauf auf einem Uno → [`atmega_lora/`](atmega_lora/).
- **Startzeile** mit Firmware-Kennung, Name, Chip-UID und DevAddr – man sieht sofort, welches Gerät am Kabel hängt.
- **Keine Schlüssel oder Passwörter im Repo** (siehe [Schlüssel](#schlüssel)).

## Dokumentation

| Seite | Inhalt |
|---|---|
| [Architektur](docs/architektur.md) | Datenweg Logger → TTN → Webhooks → Server und zurück, mit Diagrammen; LoRa und WLAN im Vergleich |
| [Bauen und flashen](docs/bauen_flashen.md) | PlatformIO, Varianten, Startzeile, Flashen über FTDI, Akkuspannung kalibrieren |
| [TTN einrichten](docs/ttn_einrichten.md) | Application, Webhooks (auch zweiter Server + Downlink-Key), Gerät ABP/OTAA, Downlinks, MAC-Einstellungen, Fair Use |
| [OTAA-Einrichtung (PDF)](docs/ttn/STM32_LORA_OTAA_Einrichtung_261107.pdf) | Kurzanleitung nur TTN-Konsole, OTAA, mit Bildern |
| [Payload](docs/payload.md) | Byte-Formate Uplink FPort 1, Downlink FPort 10 und 11, mit Hex-Beispielen |
| [Intervalle](docs/intervalle.md) | Tag, Nacht, Winter, Akku, Fern-Intervall: was gilt wann |
| [Server](docs/server.md) | `beelogger_log.php`: Speicherregeln (200/400 s), Service-Werte, `ServerZeitSteuerung()`, `lora_downlink.php` einbinden |
| [ABP oder OTAA](docs/abp_otaa.md) | Unterschiede, Session-Speicher, Mitschnitte |
| [Fehlersuche](docs/fehlersuche.md) | Meldungen im seriellen Monitor, TTN, Webhook, Server |
| [Entwicklung](docs/entwicklung.md) | Änderungen gegenüber 260705, gefundene Fehler, Stand |
| [Doku-Pflege](docs/doku_pflege.md) | welche Quelle gilt, Prüfstempel, Ablauf bei Änderungen |
| [Lizenz](docs/lizenz.md) | GPL und Bibliotheken |
| [`docs/planung/`](docs/planung/) | Planung, Ideen und Vorschläge (nicht in `main`) |

## Schnellstart

1. Repo holen, in VS Code mit PlatformIO öffnen ([Bauen und flashen](docs/bauen_flashen.md)).
2. `src/Config_LORA.h.example` → `src/Config_LORA.h`, `src/Config_MULTI.h.example` → `src/Config_MULTI.h` kopieren und ausfüllen. ABP oder OTAA mit `#define LORA_OTAA` ([ABP oder OTAA](docs/abp_otaa.md)).
3. Board wählen (`bluepill_f103c8`, `blackpill_f411ce`, `blackpill_f401cc`), bauen, über FTDI flashen.
4. In TTN Application, Payload-Formatter, Webhook und Gerät anlegen, **MAC-Einstellungen genau übernehmen** ([TTN einrichten](docs/ttn_einrichten.md)).
5. Im seriellen Monitor (9600 Baud) und in TTN *Live data* prüfen, ob Uplinks ankommen ([Fehlersuche](docs/fehlersuche.md)).

Ohne eigenen Build: fertiger Sketch-Ordner für die Arduino IDE im Release (Link oben).

## Dateien

| Pfad | Inhalt |
|---|---|
| [`src/`](src/) | Sketch 260705 mit Änderungen, Vorlagen `Config_*.h.example`, LMIC-Kopie `src/lmic_otaa/` |
| [`lib/`](lib/) | beelogger-Bibliotheken 2026_04, unverändert |
| [`atmega_lora/`](atmega_lora/) | ATmega328-LoRa-Sketch (ABP 261107, OTAA 261108) mit eigener Anleitung |
| [`server/`](server/) | `lora_downlink.php`: Serverquittung für LoRa (Erweiterung für `beelogger_log.php`) |
| [`scripts/`](scripts/) | `seriell-log.sh`, `ttn-status.sh`, TTN-Anlege-Skripte, `lieferpaket*.sh` |
| [`orginal_firmware_bibliotheken/`](orginal_firmware_bibliotheken/) | Originale von beelogger.de zum Vergleich |
| [`docs/ttn/`](docs/ttn/) | Payload-Decoder, TTN-Einstellungen (JSON), Beispielnachrichten, Bilder, OTAA-Anleitung (PDF) |
| [`docs/logs/`](docs/logs/) | serielle Mitschnitte |
| [`platformio.ini`](platformio.ini) | Board-Umgebungen |

## Schlüssel

- `src/Config_LORA.h` (LoRaWAN-Schlüssel) und `src/Config_MULTI.h` (WLAN-/Server-Passwörter, Kalibrierung) stehen in `.gitignore`. Im Repo sind nur die Vorlagen `*.example`.
- Kompilierte Firmware (`*.bin`, `.pio/`, `backup/`) enthält die Schlüssel und ist ebenfalls ausgeschlossen.
- Die LMIC-Beispiele in `lib/…/examples/` enthalten den öffentlichen Testschlüssel der LMIC-Bibliothek, keine echten Schlüssel.

## Lizenz

Firmware: (C) R. Schick – beelogger.de, **GNU GPL v3 oder später**. Die Änderungen hier stehen unter derselben Lizenz ([`LICENSE`](LICENSE)). Geänderte Dateien tragen einen Änderungsvermerk mit Datum. Die Bibliotheken behalten ihre eigenen Lizenzen. Einzelheiten und wie die GPL-Bedingungen erfüllt werden: [`docs/lizenz.md`](docs/lizenz.md).

Kein offizielles Projekt von beelogger.de. Original: https://beelogger.de

## Stand

Aktueller Stand der Arbeitspakete: [Entwicklung → Stand](docs/entwicklung.md#stand).
