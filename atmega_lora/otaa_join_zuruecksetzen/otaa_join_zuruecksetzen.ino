// OTAA-Join zurücksetzen (ATmega328, Sketch beelogger_LORA_OTAA_261119, gleiche EEPROM-Belegung wie 261108)
//
// Einmal hochladen, kurz laufen lassen, danach wieder den OTAA-Sketch aufspielen.
// Der Logger joint dann beim nächsten Senden neu. Der Frame-Zähler (EEPROM 0x20-0x52) bleibt unverändert.
//
// Der OTAA-Sketch legt die Join-Daten ab EEPROM-Adresse 0x60 ab (struct otaa_t, 15 Byte).
// Das erste Feld ist die DevAddr. DevAddr = 0 heißt "keine gültige Session" -> neuer Join.
//
// Ausgabe über die serielle Schnittstelle (9600 Baud). Ohne Serial-Monitor reicht es, den Sketch
// zwei Sekunden laufen zu lassen.

#include <EEPROM.h>

#define eeAd_otaa 0x60  // wie im OTAA-Sketch (Multi_LORA.ino)

void setup() {
  Serial.begin(9600);
  uint32_t vorher;
  EEPROM.get(eeAd_otaa, vorher);
  Serial.print(F("DevAddr im EEPROM vorher: 0x"));
  Serial.println(vorher, HEX);

  for (uint8_t i = 0; i < 4; i++) EEPROM.update(eeAd_otaa + i, 0);

  uint32_t nachher;
  EEPROM.get(eeAd_otaa, nachher);
  Serial.print(F("DevAddr im EEPROM nachher: 0x"));
  Serial.println(nachher, HEX);
  Serial.println(nachher == 0 ? F("OK: beim naechsten Senden neuer Join. Jetzt den OTAA-Sketch aufspielen.")
                              : F("FEHLER: EEPROM nicht geschrieben"));
}

void loop() {}
