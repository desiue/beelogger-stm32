# Dokumentation – Übersicht

[← Startseite](../README.md)

Alle Seiten nach Themen. Wer neu ist: oben anfangen.

## Einstieg

| Seite | Inhalt |
|---|---|
| [Architektur](architektur.md) | Datenweg Logger → TTN → Webhooks → Server und zurück, Ablauf im Logger, Mengengerüst; LoRa und WLAN im Vergleich |
| [Häufige Fragen](fragen.md) | Rückkanal, Uhr, Speicher: kurze Antworten mit Links |
| [ABP oder OTAA](abp_otaa.md) | welche Anmeldung passt, Unterschiede, Mitschnitte |

## Einrichten

| Seite | Inhalt |
|---|---|
| [Bauen und flashen](bauen_flashen.md) | PlatformIO, Konfiguration, Varianten, Startzeile, Flashen über FTDI, Akkuspannung kalibrieren |
| [TTN einrichten](ttn_einrichten.md) | Application, Webhooks (auch zweiter Server + Downlink-Key), Gerät ABP/OTAA, Downlinks, MAC-Einstellungen, Fair Use |
| [OTAA-Einrichtung (PDF)](ttn/STM32_LORA_OTAA_Einrichtung_261107.pdf) | Kurzanleitung nur TTN-Konsole, OTAA, mit Bildern |
| [Server](server.md) | `beelogger_log.php`: Speicherregeln (220/520 s), Service-Werte, `ServerZeitSteuerung()`, `lora_downlink.php` einbinden |
| [ATmega-Logger](../atmega_lora/README.md) | Universal 1.x und beeloggerSMD: Vorschlag 261107 (ABP) und 261108 (OTAA), [Testaufbau Uno](../atmega_lora/testaufbau_uno.md) |

## Wie es funktioniert

| Seite | Inhalt |
|---|---|
| [Hardware](hardware.md) | Boards, Pinbelegung, Baugruppen, LoRa-Modul je Platine, Spannungsmessung |
| [LoRaWAN](lorawan.md) | Frame-Aufbau, Verschlüsselung, Frame-Counter, OTAA, Kanäle, Sendezeit |
| [Payload](payload.md) | Byte-Formate Uplink FPort 1, Downlink FPort 10 und 11, mit Hex-Beispielen |
| [Intervalle](intervalle.md) | Tag, Nacht, Winter, Akku, Fern-Intervall: was gilt wann |
| [Speicher](speicher.md) | Backup-Register, Flash beim BluePill, was nach Stromausfall oder Update bleibt |

## Wenn etwas nicht geht

| Seite | Inhalt |
|---|---|
| [Fehlersuche](fehlersuche.md) | Meldungen im seriellen Monitor, TTN, Webhook, Server |

## Über das Projekt

| Seite | Inhalt |
|---|---|
| [Entwicklung](entwicklung.md) | Änderungen gegenüber 260705, ältere Stände, gefundene Fehler, Stand |
| [Doku-Pflege](doku_pflege.md) | welche Quelle gilt, Prüfstempel, Ablauf bei Änderungen |
| [Lizenz](lizenz.md) | GPL und Bibliotheken |
| [`planung/`](planung/) | Planung, Ideen und Vorschläge (nicht in `main`) |

## Was ist anders als beim Original

- **Maximal kompatibel zum Original:** Messablauf, Waagenzahl, Payload-Format und beelogger-Server bleiben wie beim Original 260705. Neu sind nur Downlinks (ABP und OTAA) und Fehlerkorrekturen. Weitergehende Ideen (mehr Waagen, andere Payload, eigener Sendeplan) kommen nicht in `main`, sondern in einen eigenen Branch mit eigenem Server ([Idee](planung/idee_sendeplan_schwarmalarm.md), [Payload](planung/vorschlag_payload_erweiterung.md)).
- **Sendeintervall aus der Ferne** per TTN-Downlink (FPort 10, 5–180 min), mit ABP und OTAA.
- **Uhrzeit und Intervall vom Server** wie bei WLAN-Loggern: Serverquittung per Downlink FPort 11 und `server/lora_downlink.php` (Branch `p7-serverquittung`, noch nicht in `main`).
- **OTAA** für neue Geräte, ABP für vorhandene Feldgeräte – eine Codebasis, umschaltbar per `#define LORA_OTAA`.
- **BluePill (F103) und BlackPill (F411/F401)**, alle vier Kombinationen auf Testgeräten abgenommen.
- **Fehler behoben:** Sketch-ID in jedem Uplink, TTN-Downlink-Schleife (Einstellungen dokumentiert), ADR-Bit.
- **ATmega-Logger (Universal 1.x, beeloggerSMD):** Vorschlag mit denselben zwei kleinen Änderungen (Sketch-ID, ADR) für den Original-Sketch 250326, sonst unverändert (ABP). Dazu eine OTAA-Variante (261108), seit 08.11.2026 im Dauerlauf auf einem Uno → [`atmega_lora/`](../atmega_lora/).
- **Startzeile** mit Firmware-Kennung, Name, Chip-UID und DevAddr – man sieht sofort, welches Gerät am Kabel hängt.
- **Keine Schlüssel oder Passwörter im Repo** (siehe [Schlüssel](#schlüssel)).

## Dateien

| Pfad | Inhalt |
|---|---|
| [`src/`](../src/) | Sketch 260705 mit Änderungen, Vorlagen `Config_*.h.example`, LMIC-Kopie `src/lmic_otaa/` |
| [`lib/`](../lib/) | beelogger-Bibliotheken 2026_04, unverändert |
| [`atmega_lora/`](../atmega_lora/) | ATmega328-LoRa-Sketch (ABP 261107, OTAA 261108) mit eigener Anleitung |
| [`server/`](../server/) | `lora_downlink.php`: Serverquittung für LoRa (Erweiterung für `beelogger_log.php`); `M40_261109/`: unveränderte Kopie der Server-Skripte von beelogger.de (GPLv3) |
| [`scripts/`](../scripts/) | `seriell-log.sh`, `ttn-status.sh`, TTN-Anlege-Skripte, `lieferpaket*.sh` |
| [`orginal_firmware_bibliotheken/`](../orginal_firmware_bibliotheken/) | Originale von beelogger.de zum Vergleich |
| [`docs/ttn/`](ttn/) | Payload-Decoder, TTN-Einstellungen (JSON), Beispielnachrichten, Bilder, OTAA-Anleitung (PDF) |
| [`docs/logs/`](logs/) | serielle Mitschnitte |
| [`platformio.ini`](../platformio.ini) | Board-Umgebungen |

## Schlüssel

- `src/Config_LORA.h` (LoRaWAN-Schlüssel) und `src/Config_MULTI.h` (WLAN-/Server-Passwörter, Kalibrierung) stehen in `.gitignore`. Im Repo sind nur die Vorlagen `*.example`.
- Kompilierte Firmware (`*.bin`, `.pio/`, `backup/`) enthält die Schlüssel und ist ebenfalls ausgeschlossen.
- Die LMIC-Beispiele in `lib/…/examples/` enthalten den öffentlichen Testschlüssel der LMIC-Bibliothek, keine echten Schlüssel.
