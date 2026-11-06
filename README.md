# beelogger STM32 – LoRaWAN-Firmware (PlatformIO)

Arbeitsstand der beelogger-Firmware **STM32_LORA_260705** (beelogger.de) als PlatformIO-Projekt für VS Code.
Ziel: Austausch mit den beelogger-Entwicklern. Erweiterungen: Sendeintervall per Downlink, später OTAA.

**Keine Schlüssel oder Passwörter im Repo** (siehe „Schlüssel“).

## Inhalt

| Pfad | Inhalt |
|---|---|
| `src/` | Firmware (Sketch 260705 mit Änderungen, siehe unten) |
| `src/Config_LORA.h.example`, `src/Config_MULTI.h.example` | Vorlagen für LoRa- und Sensorkonfiguration (nur Platzhalter) |
| `lib/` | beelogger-Bibliotheken 2026_04, unverändert |
| `orginal_firmware_bibliotheken/` | Originale von beelogger.de zum Vergleich (Sketche 202610, Bibliotheken 2026_04, WLAN/LTE 230928) |
| `platformio.ini` | Boards BluePill F103CB, BlackPill F411CE, BlackPill F401CC |
| `docs/flashen.md` | Bauen, Flashen über FTDI (stm32loader), Gerätetabelle |
| `docs/planung_otaa_downlink.md` | Plan, Entscheidungen und Verlauf der Arbeitspakete |
| `docs/aenderungen_gegen_260705.diff` | Alle Änderungen gegenüber dem Original-Sketch 260705 |
| `docs/ttn/` | TTN-Einstellungen (JSON aus der TTN-API), Payload-Decoder, Beispielnachrichten |
| `docs/logs/` | Serielle Mitschnitte (9600 Baud) |

## Bauen

```
cp src/Config_LORA.h.example src/Config_LORA.h   # eigene Schlüssel, LOGGER_NAME
cp src/Config_MULTI.h.example src/Config_MULTI.h # Sensoren, Waagen-Kalibrierung
pio run -e blackpill_f411ce                      # oder bluepill_f103c8, blackpill_f401cc
```

- STM32-Core 2.6.0 (`framework-arduinoststm32@~4.20600`), in `platformio.ini` festgelegt.
- BluePill: Board `bluepill_f103c8_128k` mit `-DARDUINO_BLUEPILL_F103CB` (die Firmware braucht 128 KB).
- Flash-Belegung (Paket 2): F103CB 53 884 B, F411CE 53 176 B.
- Debug seriell: 9600 Baud, BluePill `Serial` (PA9), BlackPill `Serial1`.

Flashen: siehe `docs/flashen.md`. `pio -t upload` geht beim F411 nicht (stm32flash kennt die Chip-ID 0x431 nicht), deshalb stm32loader.

## Änderungen gegenüber 260705

Vollständig in `docs/aenderungen_gegen_260705.diff`. Kurz:

1. **Startzeile** in `System_Init()`: `Name: <LOGGER_NAME> UID: <Chip-UID> DevAddr: <DevAddr>`. Zeigt, welches Gerät am Kabel hängt.
2. **Downlink Sendeintervall** (`onEvent()` in `STM32_LORA.ino`):
   - FPort **10**, 1 Byte = Intervall in Minuten, gültig **5–180**; **0** = zurück zur Firmware-Vorgabe; andere Werte werden ignoriert.
   - Gespeichert in RTC-Backup-Register 5, Bit 9–14 (Minuten/5). Übersteht den Tiefschlaf, nicht aber Akku-ab. Alte Firmware schreibt dort 0, ein Update ist also neutral.
   - Ersetzt Tag/Nacht/Winter-Intervall. Service-Modus und Akkusparmodus haben Vorrang.
   - Class A: wirkt ab dem Zyklus nach dem Uplink, in dessen Empfangsfenster der Downlink kam.
3. **Aux3-Fehler behoben**: `prep_payload()` prüfte `LMIC.seqnoUp % 10`, bevor `seqnoUp` gesetzt war → Sketch-ID in jedem Uplink. Jetzt `LoRaSeqCounter % 10`.
4. `Config_LORA.h`: `LOGGER_NAME` je Geräteblock.

## TTN-Einstellungen (ABP, LMIC dieser Firmware)

Die Firmware startet nach jedem Zyklus neu (`LowPower.shutdown()`) und beantwortet keine MAC-Befehle. TTN muss deshalb genau die Werte kennen, die die Firmware fest benutzt. Sonst hängt TTN an jeden Downlink MAC-Befehle („Downlink-Schleife“).

| Einstellung (Advanced MAC settings) | Wert | Grund |
|---|---|---|
| LoRaWAN-Version | 1.0.2, PHY 1.0.2 rev B | LMIC 1.5 |
| Frequenzplan | EU_863_870_TTN | |
| Frame counter width | 32 bit | |
| Rx1 delay / Desired | 1 / 1 s | LMIC-Vorgabe |
| Rx1 data rate offset / Desired | 0 / 0 | |
| **Rx2 data rate index / Desired** | **3 / 3** (SF9) | `LMIC.dn2Dr = DR_SF9` in `setup_LORA()` |
| Rx2 frequency / Desired | 869,525 / 869,525 MHz | |
| Resets frame counters | an | Zähler fängt nach Akku-ab bei 0 an |
| Factory preset frequencies | 868,1 / 868,3 / 868,5 / 867,1 … 867,9 MHz | muss alle Sendefrequenzen enthalten |
| ADR | aus | |

Links („aktuell“) und rechts („Desired“) müssen gleich sein. Beispiel aus der API: `docs/ttn/beelogger09_ns_einstellungen_abp.json`.

**Achtung:** Geänderte MAC-Einstellungen gelten bei einer laufenden ABP-Session erst nach „Reset MAC state“ in der TTN-Konsole. Bis dahin nutzt TTN die alten Werte. Beispiel beelogger09 am 06.11.: Settings RX2 = DR3, die laufende Session aber noch DR0 (SF12). Der Downlink kam trotzdem an, weil TTN ihn über RX1 schickte (SF7, gleiche Frequenz wie der Uplink). Mitschnitt: `docs/ttn/beispiele/2026-11-06_beelogger09_live_data_downlink_15min.json`.

**Downlink-Zähler (FCntDown):** Die Firmware startet nach jedem Zyklus neu und beginnt dann bei FCntDown 0. LMIC sieht nur die unteren 16 Bit des Zählers. Steht der FCntDown in TTN über 65535, passt die Prüfsumme nicht und der Downlink wird **stillschweigend verworfen**. Das passiert bei Geräten, die lange in der Downlink-Schleife hingen (Beispiel beeloggerblue05: FCntDown 224647). Abhilfe: in der TTN-Konsole „Reset session and MAC state“; danach beginnt FCntDown bei 0.

Kanäle: `LORA_CHANNELS` in `Config_LORA.h` (1, 3 oder 8). Testgerät: 3.

Downlink senden (TTN-Konsole → Gerät → Messaging → Downlink): FPort 10, Payload hex `0F` = 15 min.

## Schlüssel

- `src/Config_LORA.h` (LoRaWAN-Schlüssel) und `src/Config_MULTI.h` (WLAN-/Server-Passwörter, Kalibrierung) stehen in `.gitignore`. Im Repo sind nur die Vorlagen `*.example`.
- Kompilierte Firmware (`*.bin`, `.pio/`, `backup/`) enthält die Schlüssel und ist ebenfalls ausgeschlossen.
- Die LMIC-Beispiele in `lib/…/examples/` enthalten den öffentlichen Testschlüssel der LMIC-Bibliothek, keine echten Schlüssel.

## Lizenz

Firmware: (C) R. Schick – beelogger.de, **GNU GPL v3 oder später**. Die Änderungen hier stehen unter derselben Lizenz ([`LICENSE`](LICENSE)). Geänderte Dateien tragen einen Änderungsvermerk mit Datum. Die Bibliotheken behalten ihre eigenen Lizenzen. Einzelheiten und wie die GPL-Bedingungen erfüllt werden: [`docs/lizenz.md`](docs/lizenz.md).

Kein offizielles Projekt von beelogger.de. Original: https://beelogger.de

## Stand

Siehe `docs/planung_otaa_downlink.md`, Abschnitt „Arbeitspakete“ und „Verlauf“.
Paket 1 (Grundlage) fertig, Paket 2 (Downlink-Intervall) im Test, Paket 3 (OTAA) offen.
