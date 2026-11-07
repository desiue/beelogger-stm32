# TTN-Anleitung: beelogger in The Things Network anlegen

Für diese Firmware (Kennung 261106, LMIC 1.5). Gilt für alle vier Varianten (BluePill F103, BlackPill F411/F401, jeweils ABP oder OTAA). Welche Variante passt: README, Abschnitt „ABP oder OTAA“.

Konsole: <https://eu1.cloud.thethings.network/console> (Cluster Europa).

> Bilder: Platzhalter `bilder/…` liegen in `docs/ttn/bilder/`. Bildschirmfotos vor dem Einchecken auf Schlüssel prüfen (AppKey, NwkSKey, AppSKey schwärzen).

## 1. Application anlegen (einmal)

1. **Applications → + Create application**, Application ID z. B. `beelogger09`.
   ![Application anlegen](ttn/bilder/01_application.png)
2. **Payload formatters → Uplink**: Formatter type „Custom Javascript formatter“, Inhalt von [`ttn/payload_decoder_ttn.js`](ttn/payload_decoder_ttn.js) einfügen, speichern.
   ![Payload-Formatter](ttn/bilder/02_formatter.png)
3. **Webhooks → + Add webhook → Custom webhook**: Adresse des beelogger-Servers wie bei beelogger.de beschrieben (`…/beelogger_log.php?Passwort=…&LORA=1`), „Uplink message“ an.
   ![Webhook](ttn/bilder/03_webhook.png)

## 2. Gerät anlegen

**End devices → + Register end device → Enter end device specifics manually**

| Feld | Wert |
|---|---|
| Frequency plan | Europe 863-870 MHz (SF9 for RX2 – recommended) = `EU_863_870_TTN` |
| LoRaWAN version | LoRaWAN Specification 1.0.2 |
| Regional Parameters version | RP001 Regional Parameters 1.0.2 **revision B** (nicht „1.0.2“ ohne Zusatz) |
| Activation mode (unter „Show advanced activation …“) | ABP oder OTAA, passend zu `#define LORA_OTAA` |
| Additional LoRaWAN class capabilities | None (class A only) |
| Network defaults: „Use network's default MAC settings“ | **aus** – sonst Rx1 delay 5 s und ADR an, passt nicht zur Firmware. Die MAC-Werte dann wie in 2a Schritt 3 eintragen |
| Cluster settings: „Skip registration on Join Server“ | aus |

![Gerät anlegen, Grunddaten](ttn/bilder/04_register.png)

### 2a. ABP (`#define LORA_OTAA 0`)

1. Device address, NwkSKey, AppSKey mit **Generate** erzeugen (DevEUI ist bei ABP egal). End device ID = Name des Loggers. Im Bild ist „Use network's default MAC settings“ noch an: abwählen, siehe Tabelle oben.
   ![ABP-Schlüssel](ttn/bilder/05_abp_keys.png)
2. In `src/Config_LORA.h` eintragen, beide Schlüssel in **MSB**-Reihenfolge (Schalter `<>` in der Konsole, Format „C-style“):
   - `DEVADDR = 0x260B….` (Device address)
   - `NWKSKEY[16]`, `APPSKEY[16]`
3. **General settings → Network layer → Custom MAC settings** genau wie in der Tabelle im README („TTN-Einstellungen“), dazu wie bei OTAA (2b Schritt 4) Status count/time periodicity 0. Wichtig: **Resets frame counters an**, **Rx2 data rate index 3**, **ADR Disabled**, Rx1 delay 1 s. Aktuelle und „Desired“-Werte gleich.
4. Wurden die MAC-Einstellungen bei einem laufenden Gerät geändert: **Reset session and MAC state** (siehe Abschnitt 5), sonst gelten die alten Werte weiter.

### 2b. OTAA (`#define LORA_OTAA 1`)

Von Hand:

1. Im selben Formular (Haken „Use network's default MAC settings“ aus): **Rx2 data rate 3**, Rx2 frequency 869.525 MHz.
2. JoinEUI `0000000000000000`, **Confirm**. Dann DevEUI und AppKey mit **Generate** erzeugen. End device ID z. B. `<name>-otaa`. **Register end device**.
   ![OTAA-Schlüssel](ttn/bilder/07_otaa_keys.png)
3. In `src/Config_LORA.h` eintragen:
   - `DEVEUI[8]` und `APPEUI[8]` (= JoinEUI) in **LSB**-Reihenfolge
   - `APPKEY[16]` in **MSB**-Reihenfolge
   
   Falsche Reihenfolge ist der häufigste Fehler: Das Gerät sendet Join-Requests, TTN antwortet nicht.
4. Gerät → **General settings → Network layer → Custom MAC settings** (Bild unten), dann **Save changes**:
   - Frame counter width 32 bit
   - **Desired Rx1 delay 1 s**, Rx1 data rate offset 0, Rx2 data rate index 3, Rx2 869,525 MHz
   - **Status count periodicity 0 und Status time periodicity 0** (Vorgabe 200 / 86400: TTN fragt sonst per MAC-Befehl den Gerätestatus ab, die Firmware antwortet nicht, Downlink-Schleife)
   - **Adaptive data rate: Disabled** (Vorgabe ist „Dynamic mode“)

   ![Custom MAC settings](ttn/bilder/06_mac_settings.png)
   
   „Resets frame counters“ ist egal (jeder Join setzt die Zähler zurück). TTN schickt Rx1 delay und Rx2 mit dem Join-Accept; ohne diese Änderung wären es 5 s Wartezeit statt 1 s, der Logger bliebe pro Zyklus länger wach.

Mit Skript (legt Gerät mit allen MAC-Einstellungen an und schreibt die drei Werte in die richtige Reihenfolge in `Config_LORA.h`, ohne Schlüssel auszugeben):

```
scripts/ttn-otaa-anlegen.py <app> <device_id> <LOGGER_NAME> src/Config_LORA.h
```

Braucht einen API-Key in `~/.config/ttn/konto.key` (siehe Abschnitt 6). Der Block mit `#define LOGGER_NAME "<LOGGER_NAME>"` muss in `Config_LORA.h` schon stehen.

## 3. Bauen, flashen, prüfen

Bauen und flashen: README und [`flashen.md`](flashen.md). Danach:

1. **Serielle Konsole** (9600 Baud): Startzeile mit Name und DevAddr. OTAA: nach dem ersten Start Join (einige Sekunden), danach steht die neue DevAddr in der Startzeile.
2. **TTN → Gerät → Live data**: OTAA zeigt „Accept join-request“, dann den ersten Uplink mit FCnt 0. ABP: Uplinks direkt. Live data zeigt nur die letzten Meldungen; den Join sieht man nur, wenn die Seite beim Einschalten des Loggers offen ist. Im Bild (von unten nach oben): Uplinks alle 5 min, Akku ab und an, „Receive join-request“, „Accept join-request“ mit neuer DevAddr, nach etwa 4 s der erste Uplink mit `Aux3: 261106` (Firmware-Kennung nach dem Einschalten).
   ![Live data](ttn/bilder/08_live_data.png)
   Beispiele, wie es aussehen muss: [`ttn/beispiele/`](ttn/beispiele/) (OTAA-Join: `2026-11-06_beelogger08_otaa_live_data_join.json`).
3. **Skript** prüft die wichtigen Werte (ohne Schlüssel):

   ```
   scripts/ttn-status.sh <app> <device_id>
   ```

   Alle Zeilen müssen mit `ok` beginnen. Beispiel OTAA:

   ```
   beelogger011-otaa MAC_V1_0_2 OTAA
   ok FCntDown 0 (muss < 65536 sein, sonst Session/MAC-Reset)
   ok Einstellung rx1_delay: 1 / desired 1 (soll 1)
   ok Einstellung rx2_data_rate_index: 3 / desired 3 (soll 3)
   ok laufende Session RX2-DR: 3 / 3 (nach Settings-Änderung: MAC-Reset)
   ok ADR aus: True
      letzte Uplinks (UTC, FCnt): [('00:18:29', 'Join'), ('00:18:35', 0), ('00:23:29', 1)]
   ok AS-FCntDown 0 (muss < 65536 sein; sonst AS-Zähler zurücksetzen)
   ```

## 4. Sendeintervall per Downlink

**Gerät → Messaging → Downlink**: FPort **10**, Payload hex = Intervall in Minuten (`0F` = 15 min, `00` = Vorgabe aus der Firmware). Kommt beim nächsten Uplink an.

![Downlink](ttn/bilder/09_downlink.png)

Fair Use Policy: höchstens 10 Downlinks am Tag (README, „TTN Fair Use Policy“).

## 5. Wenn etwas nicht geht

| Zeichen | Ursache | Abhilfe |
|---|---|---|
| Unter jedem Uplink ein Downlink, den niemand geschickt hat („Downlink-Schleife“) | MAC-Einstellungen in TTN passen nicht zur Firmware | Advanced MAC settings prüfen, dann Reset session and MAC state |
| Downlink geschickt, kommt nie an | FCntDown in TTN über 65535 | `ttn-status.sh`; beide Zähler zurücksetzen (siehe unten) |
| ABP: Uplinks nach Akkuwechsel verworfen | Resets frame counters aus | einschalten |
| OTAA: Join-Requests ohne Antwort | DevEUI/JoinEUI nicht LSB oder AppKey nicht MSB | Reihenfolge prüfen, neu flashen |
| Geänderte Einstellung wirkt nicht (ABP) | laufende Session nutzt alte Werte | Reset session and MAC state |

**Reset session and MAC state** (setzt den FCntDown im Network Server auf 0):
- Konsole: Gerät → General settings → Network layer → Expand → Knopf „Reset session and MAC state“ (über den Custom MAC settings, siehe Bild in 2b), oder
- API: `PATCH /api/v3/ns/applications/<app>/devices/<dev>` mit Body `{"field_mask":{"paths":["session","mac_state"]}}`. (Der Pfad `/reset` gibt 404.)

Den **zweiten FCntDown im Application Server** setzt das nicht zurück: `PUT /api/v3/as/applications/<app>/devices/<dev>` mit `session.last_a_f_cnt_down = 0` (Feldmaske gleich lautend), oder Gerät neu anlegen. Bei OTAA reicht meist ein Neu-Join (Akku kurz ab).

## 6. API-Keys

Nur für die Skripte nötig, nicht für den Betrieb. Anlegen unter **Application → API keys** bzw. für das Anlege-Skript unter **User → Personal API keys**. Schlüssel nie ins Repo; Ablage:

| Datei | Rechte | genutzt von |
|---|---|---|
| `~/.config/ttn/<app>.key` | View devices, Read application traffic | `ttn-status.sh` |
| `~/.config/ttn/konto.key` | Applications: devices lesen und schreiben | `ttn-otaa-anlegen.py`; Rückfall für `ttn-status.sh` |

Zum Zurücksetzen der Zähler per API zusätzlich „Edit devices“. Keys mit Ablaufdatum anlegen und danach löschen.
