// 1-Wire-Scanner für D6 (lokaler Test). Alle 3 s: Reset-Antwort, gefundene Adressen, Temperatur.
#include <OneWire.h>

OneWire ow(6);

void setup() {
  Serial.begin(9600);
  Serial.println(F("1-Wire-Scan an D6"));
}

void loop() {
  pinMode(6, INPUT);
  Serial.print(F("D6 Ruhepegel: "));
  Serial.println(digitalRead(6));
  Serial.print(F("Reset-Antwort (Presence): "));
  Serial.println(ow.reset());

  uint8_t a[8], n = 0;
  ow.reset_search();
  while (ow.search(a)) {
    n++;
    Serial.print(F("Geraet "));
    for (uint8_t i = 0; i < 8; i++) { if (a[i] < 16) Serial.print('0'); Serial.print(a[i], HEX); }
    Serial.println(OneWire::crc8(a, 7) == a[7] ? F(" CRC ok") : F(" CRC FALSCH"));
    ow.reset(); ow.select(a); ow.write(0x44, 0);
    delay(800);
    ow.reset(); ow.select(a); ow.write(0xBE);
    uint8_t s[9];
    for (uint8_t i = 0; i < 9; i++) s[i] = ow.read();
    Serial.print(F("  Temp [C]: "));
    Serial.println(OneWire::crc8(s, 8) == s[8] ? (int16_t)((s[1] << 8) | s[0]) / 16.0 : -999.0);
  }
  Serial.print(F("Anzahl: "));
  Serial.println(n);
  delay(3000);
}
