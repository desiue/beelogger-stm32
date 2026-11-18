# Server: Schwarmalarm, Triggeralarme, Tageswächter

[← Übersicht](README.md)

Stand: 10.11.2026 · gilt für: Server-Skripte M.40 (Paket M40_261109) ·
geprüft gegen Code: `server/M40_261109/beelogger1/beelogger_log.php`, `beelogger_cron21.php`,
`beelogger_mobileWatch.php`, Commit a688002

Die Alarme laufen auf dem Server, nicht im Logger. Für LoRa-Logger gilt alles genauso, die Firmware weiß davon
nichts. Eingestellt wird in der Web-Konfiguration des Loggers (`beelogger_ini.php`). Zeilennummern beziehen sich
auf `beelogger1/beelogger_log.php`. Ablauf einer Meldung bis zum Speichern: [server.md](server.md).

| Alarm | wo | löst aus |
|---|---|---|
| [Schwarmalarm](#schwarmalarm) | `beelogger_log.php` bei jeder Meldung | anhaltender Gewichtsverlust einer Waage |
| [Triggeralarme](#triggeralarme) | `Triggeralarme()` nach dem Speichern | eine Datenreihe über oder unter einem Wert |
| [Tageswächter](#tageswächter) | `beelogger_cron21.php` oder `beelogger_mobileWatch.php?autowatch=…`, einmal am Tag | Logger hat seit 24 h nichts gesendet |

## Schwarmalarm

Zeile 871 ff., je Waage. Einstellungen: `$DifferenzGewichtArray` (Mindestverlust), `$DifferenzGewichtMaxArray`
(größere Verluste gelten als Messfehler), `$ReferenzZeitArray` (Minuten), Versand per Mail
(`$EmailSchwarmAlarmArray`) und/oder Push (`$PushSchwarmAlarmArray`). Zustand je Waage in `Schwarminfo.php`
(bei Multi-Loggern im Unterordner der Waage).

Geprüft wird nur, wenn der Service-Wert der aktuellen **und** der zuletzt gespeicherten Meldung unter 99 liegt.
Eine Durchsicht mit Arbeitsschalter löst also keinen Alarm aus ([server.md](server.md#service-wert)).

```
verlust = Gewicht(letzte gespeicherte Meldung) − Gewicht(jetzt, kompensiert)
wenn DifferenzGewicht < verlust < DifferenzGewichtMax:
    Referenz merken: Zeit und Gewicht der letzten Meldung
    wenn jetzt ≥ Zeit der letzten Meldung + ReferenzZeit:        ALARM
wenn eine Referenz gemerkt ist und jetzt ≥ Referenzzeit + ReferenzZeit:
    verlust = Referenzgewicht − Gewicht(jetzt)
    wenn DifferenzGewicht ≤ verlust < DifferenzGewichtMax:          ALARM (Verlust hält an)
    sonst: Referenz löschen (Gewicht wieder erreicht)
```

Bei einem Alarm löscht der Server die Referenz, zählt die laufende Nummer hoch (`No.1`, `No.2` … für
Nachschwärme) und schickt Mail und/oder Push mit Verlust und der Zeit seit dem möglichen Abgang. Ein Honigraum,
der zur Durchsicht abgehoben und vor Ablauf der ReferenzZeit wieder aufgesetzt wird, löst keinen Alarm aus.

**Mit LoRa beachten:** Der Vergleich läuft gegen die zuletzt **gespeicherte** Meldung. Meldungen, die die
[Speicherregel](server.md#speicherregel-mindestabstand) verwirft, zählen nicht. Laut beelogger-Team ist die
Schwarmlogik auf den 10-min-Takt ausgelegt; kürzere Intervalle bringen hier nichts
([ttn_einrichten.md, Abschnitt 8](ttn_einrichten.md#8-ttn-fair-use-policy)).

## Triggeralarme

`Triggeralarme()`, Zeile 1400. Bis zu vier frei einstellbare Alarme (`$TriggerAlarmArray[1..4]`). Zustand in
`Triggeralarm.php` (`$LastTriggerArray`, `$TriggerGesendetArray`).

| Feld | Bedeutung |
|---|---|
| `[1]`, `[2]` | Mail, Push (`aktiviert`) |
| `[3]` | Triggersensor: Nummer der Datenreihe (CSV-Spalte = Nummer + 1, siehe [server.md](server.md#was-gespeichert-wird-csv-dateien)) |
| `[4]`, `[5]` | Vergleich `<` (sonst gilt `>`) und Triggerwert |
| `[6]` | `9999` = nach dem ersten Alarm abschalten; sonst frühestens nach 24 h wieder |
| `[7]` | Anmerkung im Diagramm (`notes.csv`) |
| `[9]` | Push-Priorität |

Ablauf: Für jede neu gespeicherte Zeile in `week.csv` wird der Wert der Reihe verglichen. Ein Alarm geht nur raus,
wenn der Wert die Schwelle neu überschreitet (vorher nicht ausgelöst) **und** der letzte Alarm über 24 h
(86 400 s) zurückliegt. Fällt der Wert zurück, ist der Alarm wieder scharf.

## Tageswächter

Meldet Logger, die nichts mehr senden. Akkuwerte spielen keine Rolle.

**`beelogger_cron21.php`** (im Basisordner, als Cronjob stündlich oder zur Prüfstunde): läuft nur, wenn
`$GeneralAutowatch` in `general_ini.php` `p`, `m` oder `pm` ist und `$GeneralAutowatchTime` gleich der Stunde
`$Check_Hour` im Skript (`"21"`) oder `Alle` ist. Dann prüft es alle Logger-Ordner mit `$Watchdog = "aktiviert"`:

| Zustand von `week.csv` | Meldung |
|---|---|
| letzte Zeile jünger als 24 h | keine |
| älter als 24 h, jünger als 7 Tage | Logger inaktiv |
| älter als 7 Tage | keine (gilt als abgemeldet) |
| Datei fehlt | „Nie“ |

Versand: `m` = Mail, `p` = Pushover, `pm` = beides.

**`beelogger_mobileWatch.php?autowatch=m|p|pm`**: dieselbe Prüfung über die Smartphone-Ansicht, für einen
täglichen Aufruf von außen (Cronjob, geplanter Abruf). Mail an `$Empfaenger_Email`, Push mit `$PushToken`/`$PushUser`.

## Versand: Mail, Pushover, Telegram

- **Mail** über PHP `mail()`. Der Webserver muss Mails senden können; der Absender (`$Absender_Email`) gehört
  meist zu einer Domain auf dem Server, sonst landet die Mail im Spam. Solange die Beispieladressen
  (`…@meineDomain.de`) eingetragen sind, schicken Schwarm- und Triggeralarm keine Mail.
- **Pushover:** `$PushToken`, `$PushUser` aus der Pushover-App.
- **Telegram:** `$T_Token`, `$T_Chat_Id`. Bot beim *BotFather* anlegen (`/newbot`), der Token steht in der
  Antwort. Chat-ID: dem Bot eine Nachricht schicken und `https://api.telegram.org/bot<TOKEN>/getUpdates` aufrufen.

Token, Chat-ID und Mailadressen gehören nur in `beelogger_ini.php` auf dem Server, nicht in Repos oder Foren.

## Weitergabe an andere Dienste

Nach dem Speichern schickt der Server die Werte je Waage an BEEP, iBeekeeper und ThingSpeak, wenn in der
Konfiguration aktiviert (Zeile 760 ff., `ExternDataOut()` Zeile 1218). BEEP und iBeekeeper bekommen
`time, t, t_i, h, h_i, l, bv, weight_kg`, dazu `p` (Luftdruck über 500), `rssi` und Bienenzähler-Werte,
wenn vorhanden.
