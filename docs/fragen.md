# Häufige Fragen

[← Übersicht](README.md)

Stand: 09.11.2026 · gilt für: STM32 261107, Rückkanal FPort 11 (Branch `p7-serverquittung`, noch nicht in
`main`) · geprüft gegen Code: Commit 863302f

Fragen, die beim Austausch mit dem beelogger-Team und anderen Nutzern aufkamen. Die Antworten sind kurz,
die Links führen zu den Einzelheiten.

## Rückkanal

**TTN verwirft die Antwort von `beelogger_log.php`. Wie kommt dann etwas zum Logger zurück?**
Die Antwort wird tatsächlich nicht weitergereicht. Stattdessen bestellt `lora_downlink.php` den Downlink in
einem zweiten, eigenen HTTP-Aufruf bei TTN. Adresse und Schlüssel dafür schickt TTN bei jedem Webhook-Aufruf
im HTTP-Kopf mit. → [server.md: Wer macht was wo](server.md#wer-macht-was-wo)

**Wer macht was wo, und welche Information geht wohin?**
Tabelle und Ablaufdiagramm: [server.md](server.md#welche-information-wohin-geht). Überblick über den ganzen
Datenweg: [architektur.md](architektur.md).

**Was muss man einrichten?**
In TTN im Webhook einen Downlink API key (Recht `traffic down write`), auf dem Server die Datei
`lora_downlink.php` und eine Zeile in `beelogger_log.php`. → [server.md: Einbinden](server.md#einbinden)

**Was passiert, wenn ein Downlink nicht ankommt?**
Nichts Schlimmes: Der Logger behält Intervall und Uhr, die Messwerte kommen trotzdem an, der Server stempelt
sie mit seiner eigenen Zeit. → [server.md](server.md#welche-information-wohin-geht)

**Wie zuverlässig ist der Rückkanal?**
Im Test seit 08.11.2026 mit zwei Loggern sind bisher (Stand 11.11.) alle Downlinks angekommen, einige erst nach dem
nächsten Uplink. Dafür trägt der Downlink Frame-Counter-Bezug und Abstand mit. Belastbare Zahlen kommen
nach dem Langzeittest.

**Wie viele Downlinks entstehen?**
Mit `Z=2` einer am Tag für die Uhr, dazu einer bei jeder Änderung des Intervalls (z. B. Tag/Nacht) und nach einem Neustart des Loggers. TTN erlaubt etwa
10 pro Gerät und Tag. → [server.md: Wann gesendet wird](server.md#wann-gesendet-wird)

**1 Byte oder 7 Byte?**
Die Firmware versteht beides: FPort 10 mit 1 Byte (nur Intervall) und FPort 11 mit 7 Byte (Intervall und
Uhr). Ausgewählt wird wie bei WLAN über die Webhook-Adresse: mit `Z=2` FPort 11, sonst FPort 10.
→ [server.md: Wann gesendet wird](server.md#wann-gesendet-wird)
→ [payload.md](payload.md), [Planung](planung/planung_otaa_downlink.md)

## Uhr

**Wozu braucht der Logger eine richtige Uhr?**
Nur für die eigene Tag/Nacht- und Winterregel nach Uhrzeit und Monat. Kommt ein Intervall per Downlink, gilt
das ohnehin. Die Messwerte stempelt der Server. → [intervalle.md](intervalle.md)

**Wie genau läuft die Uhr?**
Sie läuft am 32-kHz-Uhrenquarz. Im Langzeittest geht sie an einer BluePill F103 etwa 6 s pro Tag nach
(gemessen 10. bis 13.11.2026). Mit `Z=2` stellt der Server sie täglich. Andere Boards können anders laufen.
→ [architektur.md](architektur.md)

**Was ist nach einem Stromausfall, und dauert es einen Tag, bis die Uhr stimmt?**
Nach dem Stromausfall steht sie auf 01.01.2022. Mit `Z=2` schickt der Server die Uhrzeit gleich nach dem
ersten Uplink, weil er den Neustart am kleineren Frame-Counter erkennt. Ebenso nach der Sommer-/Winterzeit-
Umstellung. Ohne `Z=2` wird die Uhr nie gestellt. → [architektur.md: Uhr](architektur.md#uhr-wann-sie-stimmt-und-wann-nicht)

**Geht es auch ohne Uhr?**
Ja: Tag/Nacht nach Solarspannung (`SOLAR_ZYKLUS_aktiv`) und Winter nach Temperatur (`TEMP_ZYKLUS_aktiv`)
brauchen keine Uhr. Das beelogger-Team empfiehlt die Solarspannung als Standard, mit 1 V als Schwelle; so
steht es in der Vorlage `Config_LORA.h.example`. → [intervalle.md](intervalle.md#tag-und-nacht)

## Speicher

**Welcher Zähler wird bei einer geänderten Register-Belegung zurückgesetzt?**
Der LoRa-Frame-Counter (`LoRaSeqCounter`, Register 3, im Log „LoRaSeq_Nr“). Abhilfe nach so einem Update:
einmal den Strom trennen (Jumper ziehen). Bei ABP muss in TTN „Resets frame counters“ an sein.
→ [speicher.md](speicher.md#update-mit-anderer-belegung)

**Wie funktioniert das mit dem Flash beim F103?**
Nur die OTAA-Session (16 Byte) liegt in der letzten Flash-Seite, weil die 10 Backup-Register voll sind.
Geschrieben wird etwa 365-mal im Jahr; bei 10 000 erlaubten Löschzyklen reicht das für gut 27 Jahre.
→ [speicher.md: Flash](speicher.md#flash-beim-bluepill)

**Warum nicht im RAM?**
Der Logger schläft im Shutdown, dabei ist das RAM aus. → [speicher.md](speicher.md#flash-beim-bluepill)

**Warum ist Backup-Register 0 nicht benutzt?**
Laut Kommentar im Original-Sketch benutzt die STM32-RTC-Bibliothek Register 0 selbst.

**Was, wenn in den Backup-Registern Unsinn steht (neuer Chip, Chip aus anderem Logger, falscher Sketch)?**
Der Logger prüft das Jahr der Uhr (2022–2033). Passt es nicht, startet er wie nach einem Stromausfall. Die
OTAA-Session hat Versionsnummer und Prüfsumme. → [speicher.md](speicher.md#wie-der-logger-falsche-werte-erkennt)

**„Sich überschreibende Code-Teile“: Was war das?**
Kein Stack- oder Compilerproblem, sondern ein eigener Fehler: Die OTAA-Session lag beim F103 zuerst fest bei
63 KB, die Firmware mit BME280 und Lichtsensor ist aber etwa 64 KB groß. Der erste Join schrieb so ins eigene
Programm. Behoben am 07.11.2026. → [speicher.md](speicher.md#flash-beim-bluepill),
[entwicklung.md](entwicklung.md#gefundene-fehler)

## Sonstiges

**Kommen mehr als vier Waagen?**
Nein, nicht in diese Firmware. Ideen dazu stehen nur unter [`planung/`](planung/).
