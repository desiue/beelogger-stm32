# Server: was mit einer Meldung auf dem beelogger-Server passiert

Stand: 08.11.2026 · gilt für: Server-Skripte M.40 (Paket M40_261004), `server/lora_downlink.php` vom 08.11.2026 ·
geprüft gegen Code: `beelogger1/beelogger_log.php` aus M40_261004, Commit f1a9e0c ·
`lora_downlink.php`: Branch `p7-serverquittung`, noch nicht in `main`

Die Server-Skripte stammen von beelogger.de und liegen **nicht** in diesem Repo. Zeilennummern beziehen sich
auf `beelogger_log.php` aus M40_261004. Überblick über den Datenweg: [architektur.md](architektur.md).

## Ordner und Dateien

Jeder Logger hat auf dem Server einen eigenen Ordner. Der **Name** bestimmt, wie viele Waagen erwartet werden:

| Ordner beginnt mit | Waagen |
|---|---|
| `beelogger` | 1 |
| `Duo` | 2 |
| `Triple` | 3 |
| `Quad` | 4 |
| `Penta` | 5 |
| `Hexa` | 6 |

Wichtige Dateien im Ordner:

| Datei | Zweck |
|---|---|
| `beelogger_log.php` | nimmt Meldungen an (WLAN/LTE per GET, LoRa per Webhook-POST) |
| `beelogger_ini.php` | Einstellungen dieses Loggers, geschrieben von der Web-Konfiguration |
| `beelogger_show.php` | Anzeige (Diagramme) |
| `beelogger.csv`, `month.csv`, `week.csv` | Messdaten: alles, letzter Monat, letzte Woche |

## Ablauf bei einer LoRa-Meldung

1. TTN ruft `beelogger_log.php?Passwort=<LogPasswort>&LORA=1` per POST auf, mit dem Uplink als JSON.
2. Passwort prüfen (`$BeeloggerLogPasswort` aus `beelogger_ini.php`).
3. Werte aus `decoded_payload` übernehmen. Nur Zeichen `0-9 - .` sind erlaubt, der RSSI des ersten Gateways
   landet in `Aux2`.
4. **Zeitstempel = Serverzeit** (Europe/Berlin), Sekunden auf 00 gesetzt. Die Uhr im Logger spielt dafür
   keine Rolle.
5. Temperaturkompensation der Waagen (Korrekturwert je °C, in der Web-Konfiguration).
6. **Speicherregel** prüfen (nächster Abschnitt), dann in die CSV-Dateien schreiben.
7. Schwarmalarm, Trigger-Alarme, Weitergabe (z. B. BEEP, ThingSpeak) nach Einstellung.
8. Antwort an den Aufrufer: `<Zeit>T<Intervall>B<Service>ok *`. Ein WLAN-Logger liest daraus Uhrzeit und
   Intervall. **Bei LoRa verwirft TTN diese Antwort**, dafür gibt es `lora_downlink.php` (unten).

## Speicherregel: Mindestabstand

`beelogger_log.php`, ab Zeile 761: Eine Meldung wird nur gespeichert, wenn ihr Zeitstempel weit genug hinter
der zuletzt gespeicherten liegt.

| Bedingung | Mindestabstand |
|---|---|
| normal | **mehr als 200 s** |
| Service-Wert negativ (z. B. RSSI) | mehr als 110 s |
| Service = 99 (Arbeitsschalter) | jede Meldung, die später als die letzte ist |
| Kalibriertemperatur = `99.9` (Einstellung) | mehr als 55 s |

**Community-Server:** Laut beelogger-Team (07.11.2026) gelten dort **Oktober bis März 400 s** statt 200 s.
Die Skripte M40_261004 enthalten nur die 200-s-Regel. Folge: Ein Logger mit 5-min-Intervall verliert im
Winter auf dem Community-Server etwa jede zweite Meldung. Das ist so gewollt. Wer alle sehen möchte, sendet
im Winter alle 10 min (siehe [intervalle.md](intervalle.md)).

`99.9` als Kalibriertemperatur ist nur für Tests und die Kalibrierung gedacht, nicht für den Dauerbetrieb.

## Service-Wert

Der Logger schickt in jeder Meldung ein Byte `Service` (siehe [payload.md](payload.md)). Der Server wertet aus:

| Wert | Wirkung auf dem Server |
|---|---|
| 0 – 98 | normal (Wachzeit des Loggers in s) |
| 99 | sofort speichern, Durchsicht-Symbol setzen (höchstens alle 40 min), Schwarmalarm aus |
| über 240 | Durchsicht-Symbol wie bei 99 |
| über 1000 | wird zu 99 |
| −1 | wird leer gespeichert |
| andere negative | gelten als RSSI |

Der **Schwarmalarm** prüft nur, wenn Service in der aktuellen **und** in der zuletzt gespeicherten Meldung
unter 99 liegt. So löst eine Durchsicht am Volk (Arbeitsschalter, Gewicht ändert sich) keinen Alarm aus.

## Sendeintervall vom Server: `ServerZeitSteuerung()`

Zeile 438. Der Server rechnet aus der Web-Konfiguration ein Intervall aus und schickt es WLAN-Loggern in der
Antwort. Mit `lora_downlink.php` erreicht es auch LoRa-Logger.

| Einstellung (`beelogger_ini.php`) | Wirkung |
|---|---|
| `$IntervallSendeSteuerung` = `zeitgesteuert` | Tag von `$SommerTagZeit` bis `$SommerNachtZeit` (ab 4 min vor der vollen Stunde) |
| … = `lichtgesteuert` | Tag, wenn Licht > 1 Lux. Ohne Lichtwert gilt zeitgesteuert. |
| … = `solarspannungsgesteuert` | Tag, wenn Solarspannung > 0,3 V. Ohne Wert gilt zeitgesteuert. |
| `$SommerSendeIntervallTag` / `…Nacht` | Intervall Tag / Nacht in Minuten |
| `$WinterBeginn`, `$SommerBeginn`, `$WinterSendeIntervall` | außerhalb Sommer: Winterintervall. `deaktiviert` = aus. |

Das Ergebnis ist höchstens 60 min. Ohne Einstellung gilt 15 min.

## Rückkanal für LoRa: `lora_downlink.php` (eigene Erweiterung)

[`server/lora_downlink.php`](../server/lora_downlink.php) schickt das Intervall aus `ServerZeitSteuerung()`
und die Empfangszeit als Downlink FPort 11 an den Logger. Der Logger stellt damit Uhr und Intervall wie ein
WLAN-Logger. Format: [payload.md](payload.md#downlink-fport-11-serverquittung-uhr--intervall).

Die Erweiterung ist ein Vorschlag und läuft **nicht** auf dem Community-Server, sondern nur dort, wo sie
jemand einbindet.

### Einbinden

1. `lora_downlink.php` in den Logger-Ordner kopieren (neben `beelogger_log.php`).
2. In `beelogger_log.php` in der Funktion `Auswertung()` direkt **vor** der Antwortzeile einfügen:
   ```php
   if(isset($_GET["LORA"]) && file_exists("lora_downlink.php")){include_once("lora_downlink.php");lora_downlink($MessIntervall);}
   ```
   `isset($_GET["LORA"])` ist nötig, weil `$LORA` in `Auswertung()` nicht global ist.
3. Im TTN-Webhook, der zu diesem Server führt, einen **Downlink API key** eintragen (Recht
   `traffic down write`, siehe [ttn_einrichten.md](ttn_einrichten.md)). Ohne Key schickt TTN keine
   Push-URL mit, und die Erweiterung schreibt nur einen Hinweis ins Log.

Fehlt die Datei, läuft `beelogger_log.php` unverändert weiter.

### Wann gesendet wird

Bei **jeder** LoRa-Meldung prüft die Funktion, ob ein Downlink nötig ist. Gesendet wird nur, wenn
- sich das Intervall gegenüber dem zuletzt gesendeten geändert hat, oder
- heute noch keine Uhrzeit gesendet wurde.

Im Normalbetrieb ist das **ein Downlink pro Tag** (TTN Fair Use: etwa 10 pro Gerät und Tag).
Nur Intervalle von 5–60 min werden übertragen, sonst steht 0 (unverändert) im Byte.

### Dateien, die die Erweiterung anlegt

| Datei | Inhalt |
|---|---|
| `lora_downlink_state.json` | letzter FCnt, Empfangszeit, gesendetes Intervall, Tag der letzten Uhrzeit |
| `lora_downlink.log` | eine Zeile pro Downlink-Versuch mit HTTP-Antwort von TTN |

Beispielzeile im Log:

```
2026-11-08 15:50:03 FCnt 42 Downlink iv=10 t=2026-11-08 15:50:00 gap=10 -> HTTP/1.1 200 OK
```

Beide Zeiten sind Serverzeit (hier MEZ). `t` ist die Empfangszeit des Uplinks bei TTN. Ist die Antwort nicht `200`, wird beim nächsten Uplink erneut
gesendet.

### Sicherheit

Die Push-URL wird nur angenommen, wenn sie auf `*.cloud.thethings.network` zeigt. Der API-Key kommt bei
jedem Aufruf von TTN im Header mit und wird nicht gespeichert.
