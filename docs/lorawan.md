# LoRaWAN: was auf dem Funkweg passiert

[← Übersicht](README.md)

Stand: 10.11.2026 · gilt für: STM32 261107 · geprüft gegen Code: Commit a3ac566

Diese Seite erklärt das Protokoll so weit, wie man es für Fehlersuche, eigene Netzwerkserver oder eine
eigene Firmware braucht. Für den normalen Betrieb mit TTN reicht [ttn_einrichten.md](ttn_einrichten.md).

## Eckdaten

| | Wert | im Code |
|---|---|---|
| LoRaWAN-Version | 1.0.2, Regional Parameters 1.0.2 rev B | TTN-Einstellung, Hinweis in `Config_LORA.h.example` |
| Region | EU868 | LMIC `CFG_eu868` |
| Geräteklasse | A (hört nur direkt nach dem eigenen Uplink) | |
| Aktivierung | ABP oder OTAA | `#define LORA_OTAA` ([abp_otaa.md](abp_otaa.md)) |
| Uplink | unbestätigt, FPort 1 | `LMIC_setTxData2(1, payload, cnt, 0)` |
| Datenrate | SF7, 125 kHz, fest | `LMIC_setDrTxpow(DR_SF7, 14)` |
| ADR | aus | `LMIC_setAdrMode(0)` |
| RX2 | 869,525 MHz, SF9 (DR 3) | `LMIC.dn2Dr = DR_SF9` |

## Aufbau eines Uplinks

| Feld | Byte | Inhalt |
|---|---|---|
| MHDR | 1 | `0x40` = Unconfirmed Data Up (`0x60` = Unconfirmed Data Down) |
| DevAddr | 4 | Geräteadresse, Little Endian |
| FCtrl | 1 | Bit 7 ADR, Bit 5 ACK, Bit 3–0 Länge FOpts. Firmware 261107: `0x00`, bis 261106 und Original: `0x80` (ADR an) |
| FCnt | 2 | untere 16 Bit des Frame-Counters, Little Endian |
| FOpts | 0–15 | MAC-Befehle. Normal leer; bei OTAA einmal am Tag `LinkCheckReq` (CID `0x02`) |
| FPort | 1 | 1 = Messdaten ([payload.md](payload.md)) |
| FRMPayload | n | Messdaten, verschlüsselt mit dem AppSKey |
| MIC | 4 | Prüfsumme über alles davor, mit dem NwkSKey |

Ein Frame ist also **Payload + 13 Byte** (ohne FOpts). Beispiel: 18 Byte Payload → 31 Byte auf dem Funk.

Downlinks an den Logger: FPort 10 (Fern-Intervall) und FPort 11 (Serverquittung, Branch
`p7-serverquittung`), Format in [payload.md](payload.md#übersicht-der-ports).

## Verschlüsselung und MIC

Beides nach LoRaWAN 1.0.2, die LMIC erledigt es. Zum Nachrechnen (z. B. für einen eigenen Netzwerkserver):

**Nutzdaten:** AES-128 im Zählermodus. Für jeden 16-Byte-Block *i* (ab 1):

```
A_i = 01 | 00 00 00 00 | Dir | DevAddr (LE) | FCnt32 (LE) | 00 | i
S_i = AES128_encrypt(AppSKey, A_i)
Klartext = FRMPayload XOR (S_1 | S_2 | …), auf die Länge der Payload gekürzt
Dir = 0 Uplink, 1 Downlink
```

FPort 0 (nur MAC-Befehle) wird mit dem NwkSKey statt dem AppSKey verschlüsselt.

**MIC:**

```
B0  = 49 | 00 00 00 00 | Dir | DevAddr (LE) | FCnt32 (LE) | 00 | Länge(MHDR … FRMPayload)
MIC = AES128_CMAC(NwkSKey, B0 | MHDR | FHDR | FPort | FRMPayload), die ersten 4 Byte
```

## Frame-Counter: 16 Bit auf dem Funk, 32 Bit in der Rechnung

Im Frame stehen nur die unteren 16 Bit, Verschlüsselung und MIC rechnen mit 32 Bit. Der Empfänger rät die
oberen 16 Bit und prüft mit dem MIC.

- **Uplink:** Der Logger zählt in `LoRaSeqCounter` (RTC-Backup-Register 3) und setzt nach 16384 auf 0 zurück
  (`inc_LoRaSeq()`). Bei Stromausfall beginnt er wieder bei 0. Das Netz muss Zähler-Resets annehmen, bei TTN
  mit der Einstellung *Resets frame counters* (ABP). Mit OTAA joint der Logger ab FCnt 16000 neu
  (`OTAA_FCNT_MAX`), jeder Join setzt die Zähler auf 0.
- **Downlink:** Die LMIC beginnt nach jedem Aufwachen bei FCntDown 0 und vergleicht nur 16 Bit. Liegt der
  Zähler in TTN über 65535, verwirft der Logger jeden Downlink **ohne Meldung**. TTN führt den Zähler
  zweimal (Network Server und Application Server). Abhilfe und Hintergrund:
  [ttn_einrichten.md](ttn_einrichten.md#7-mac-einstellungen-im-detail-abp-lmic-dieser-firmware),
  [entwicklung.md](entwicklung.md#gefundene-fehler).

## OTAA und Link-Check

Nur mit `#define LORA_OTAA 1`:

- **Join** beim ersten Versand, höchstens 60 s pro Zyklus (`JOIN_TM_OUT`). Klappt es nicht, versucht der
  Logger es im nächsten Zyklus wieder. RX1-Verzögerung und RX2-Datenrate übernimmt er aus dem Join-Accept.
- **Session:** Gespeichert werden DevAddr, NetID, Nonces, RX-Werte mit Prüfsumme, die Sitzungsschlüssel
  rechnet der Logger nach jedem Aufwachen aus dem AppKey neu. Wo das liegt (BlackPill/BluePill):
  [speicher.md](speicher.md#otaa-session).
- **Link-Check:** einmal am Tag hängt der Logger einen `LinkCheckReq` an den Uplink. Bleibt die Antwort an
  drei Tagen aus, löscht er die Session und joint neu. Das kostet einen der etwa 10 Downlinks pro Tag, die
  TTN erlaubt.
- **Neu-Join** außerdem nach Stromausfall, bei ungültigem Datum oder ungültiger Session und ab FCnt 16000.

## Kanäle und Empfangsfenster (EU868)

`LORA_CHANNELS` in `Config_LORA.h` (Vorlage: 3):

| `LORA_CHANNELS` | Frequenzen | Zweck |
|---|---|---|
| 1 | dreimal 868,1 MHz | Ein-Kanal-Gateways |
| 3 | 868,1 / 868,3 / 868,5 MHz | die drei Pflichtkanäle |
| sonst (8) | dazu 867,1 / 867,3 / 867,5 / 867,7 / 867,9 MHz | volles TTN-Kanalraster |

Die LMIC wählt pro Uplink einen der Kanäle.

- **RX1:** eine Sekunde nach dem Uplink (ABP; OTAA: Verzögerung aus dem Join-Accept), gleiche Frequenz und
  Datenrate wie der Uplink.
- **RX2:** 869,525 MHz mit **SF9**. Die LoRaWAN-Vorgabe und TTN-Voreinstellung ist SF12 (DR 0). Damit
  Downlinks im RX2 ankommen, muss das Gerät in TTN auf *Rx2 data rate index* 3 stehen
  ([ttn_einrichten.md](ttn_einrichten.md#7-mac-einstellungen-im-detail-abp-lmic-dieser-firmware)).
- Die Firmware wartet höchstens 8 s auf das Ende von Senden und Empfangsfenstern (`SERVER_TM_OUT`), dann
  schaltet sie das Funkmodul ab.

## Sendezeit (Airtime)

Semtech-Formel, SF7, 125 kHz, Coding Rate 4/5, Präambel 8, Header und CRC an. Nachgerechnet am 10.11.2026.

| Payload | Frame | Sendezeit | Beispiel |
|---|---|---|---|
| 9 Byte | 22 Byte | 56,6 ms | kleinste Nachricht |
| 18 Byte | 31 Byte | 71,9 ms | Quad mit Außensensor |
| 22 Byte | 35 Byte | 77,1 ms | dasselbe mit Sketch-ID (jeder 10. Uplink) |
| 26 Byte | 39 Byte | 82,2 ms | Quad mit allen Sensoren und Sketch-ID |
| 28 Byte | 41 Byte | 87,3 ms | Duo mit allen Sensoren und Sketch-ID (längste) |

Zum Vergleich: 22 Byte bei SF8 brauchen 143,9 ms, fast doppelt so viel. Deshalb ist SF7 fest eingestellt.

Pro Tag bei 5 min Intervall (288 Uplinks), Quad mit Außensensor: etwa **20,9 s**. Läge die Sketch-ID wie
im Original in jedem Uplink, wären es 22,2 s.

## Grenzen

- **Duty-Cycle** (gesetzlich): 1 % im Teilband 868,0–868,6 MHz, also 36 s pro Stunde. Ein Uplink alle
  5 min belegt etwa 0,03 %.
- **TTN Fair Use:** im Mittel 30 s Uplink-Sendezeit und 10 Downlinks pro Gerät und Tag. Einzelheiten und
  wie man die Downlink-Schleife vermeidet: [ttn_einrichten.md](ttn_einrichten.md).
- Das Fern-Intervall lässt sich deshalb nicht unter 5 min setzen ([intervalle.md](intervalle.md)).

## Weiterlesen

- [payload.md](payload.md): Inhalt von Uplink und Downlinks
- [abp_otaa.md](abp_otaa.md): ABP oder OTAA wählen
- [speicher.md](speicher.md): wo Zähler und Session liegen
- [ttn_einrichten.md](ttn_einrichten.md): TTN-Einstellungen
