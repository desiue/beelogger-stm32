/*
   (C) 2018 Thorsten Gurzan - beelogger.de

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de


// ***************** Start Konfiguration **********************

#define Anzahl_Sensoren_DS18B20  2 // Anzahl der angeschlossenen Sensoren - Mögliche Werte: '1','2', ..., 20


// ***************** Ende Konfiguration **********************

// beelogger: Aktivierung der Stromversorgung für Sensoren, Module und DS3231
#ifdef ARDUINO_ARCH_AVR
#define Power_Pin 4
#define ONE_WIRE_BUS   3

#else
#define Power_Pin PB5   // for STM32Fxyz
#define ONE_WIRE_BUS   PA8
#endif


#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE))
#define MONITOR_RX_TX  Serial1  // STM32F4x1C  FTDI-Serial
#else
#define MONITOR_RX_TX  Serial
#endif


#include <OneWire.h>

//#define DS18B20_Aufloesung 12
uint8_t DS18B20_Adressen[20][8];  // DS18B20 ID Array

OneWire oneWire(ONE_WIRE_BUS);

const float No_Val = 999.99;
uint8_t devices = 0;


void setup(void) {
  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {};
  MONITOR_RX_TX.println("DS18B20 Test  05.07.2026");
  MONITOR_RX_TX.println(" ");
  digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);
  delay(1000);

  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print("Anzahl Sensoren konfiguriert: ");
  MONITOR_RX_TX.println(Anzahl_Sensoren_DS18B20);
  MONITOR_RX_TX.flush();


  OneWire my_O_Wire(ONE_WIRE_BUS);
  uint8_t x = 0, k;
  do {
    if (my_O_Wire.search(DS18B20_Adressen[devices])) {
      if (devices) {
        k = 0;
        do {
          if (DS18B20_Adressen[devices][k] != DS18B20_Adressen[0][k]) {
            k = 10;
          }
          k++;
        }
        while (k < 8);
        if (k == 8) {
          x = 1; devices --;
        }
      }
      if (my_O_Wire.crc8(DS18B20_Adressen[devices], 7) == DS18B20_Adressen[devices][7]) {
        devices++;  // valid CRC
      }
    }
  }
  while ((devices < 20) && (x == 0)); // Limit to Array size

  MONITOR_RX_TX.print("\nAnzahl vom System erkannter Sensoren: ");
  MONITOR_RX_TX.println(devices, DEC);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();

  MONITOR_RX_TX.println("Das DS18B20 Adressarray für Abfragemethode via Adressen: \n");
  MONITOR_RX_TX.print(" #define Anzahl_Sensoren_DS18B20  ");
  MONITOR_RX_TX.println(Anzahl_Sensoren_DS18B20);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();


  MONITOR_RX_TX.print("uint8_t DS18B20_Adressen[");
  MONITOR_RX_TX.print(Anzahl_Sensoren_DS18B20);
  MONITOR_RX_TX.println("][8] = {");
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_DS18B20; i++) {
    for (uint8_t k = 0; k < 8; k++) {
      MONITOR_RX_TX.print("0x");
      if (DS18B20_Adressen[i][k] < 16) MONITOR_RX_TX.print("0"); // zero pad the address if necessary
      MONITOR_RX_TX.print(DS18B20_Adressen[i][k], HEX);
      if ( k < 7)MONITOR_RX_TX.print(",");
    }
    MONITOR_RX_TX.println(",");
  }
  MONITOR_RX_TX.println("};");
  MONITOR_RX_TX.println(" ");

  MONITOR_RX_TX.println("Die Reihenfolge der Zeilen im Array kann nach Bedarf getauscht werden,");
  MONITOR_RX_TX.println("damit kann die Zuordnung eines Sensors in der Messwertverarbeitung angepasst werden.");
  MONITOR_RX_TX.println(" ");

}


void loop(void) {
  ds18B20(devices);
  delay(10000);
}

void print_DS_adress(uint8_t x) {
  for (uint8_t k = 0; k < 8; k++)
  {
    MONITOR_RX_TX.print("0x");
    if (DS18B20_Adressen[x][k] < 16) MONITOR_RX_TX.print("0"); // zero pad the address if necessary
    MONITOR_RX_TX.print(DS18B20_Adressen[x][k], HEX);
    if ( k < 7)MONITOR_RX_TX.print(",");
  }
}

void ds18B20(uint8_t cnt) {
#define STARTCONVERSION 0x44  // Tells device to take a temperature reading and put it on the scratchpad
#define READSCRATCH     0xBE  //  DS18B20 
#define CRC_SCRATCH       8

  uint8_t parasite = 0;
  uint8_t scratchPad[9];
  int16_t temp_ds;
  float Temperatur;

  OneWire my_O_Wire(ONE_WIRE_BUS);

  MONITOR_RX_TX.println("requesting temperature: ");
  // sends command for all devices on the bus to perform a temperature conversion
  my_O_Wire.reset();
  my_O_Wire.skip();
  my_O_Wire.write(STARTCONVERSION, parasite);

  delay(1000); // wait for conversion, 12 bit resolution needs at least 750ms

  for (uint8_t i = 0; i < cnt; i++) {
    MONITOR_RX_TX.print("Sensor ");
    if (i < 9) MONITOR_RX_TX.print(" "); // zero pad the address if necessary
    MONITOR_RX_TX.print(i + 1);
    //MONITOR_RX_TX.print(" One-Wire Adresse: ");
    MONITOR_RX_TX.print("  : ");
    my_O_Wire.reset();
    my_O_Wire.select(DS18B20_Adressen[i]);
    my_O_Wire.write(READSCRATCH);        // read scratchpad
    // Read all registers in a simple loop
    // byte 0: temperature LSB
    // byte 1: temperature MSB
    // byte 2: high alarm temp
    // byte 3: low alarm temp
    // byte 4: DS18S20: store for crc
    //         DS18B20 & DS1822: configuration register
    // byte 5: internal use & crc
    // byte 6: DS18S20: COUNT_REMAIN
    //         DS18B20 & DS1822: store for crc
    // byte 7: DS18S20: COUNT_PER_C
    //         DS18B20 & DS1822: store for crc
    // byte 8: SCRATCHPAD_CRC
    for (uint8_t i = 0; i < 9; i++) {
      scratchPad[i] = my_O_Wire.read();
    }
    my_O_Wire.reset();

    if (my_O_Wire.crc8(scratchPad, CRC_SCRATCH) == scratchPad[CRC_SCRATCH]) {  // if CRC o.k.
      temp_ds = (int16_t)(scratchPad[1] << 8) | (int16_t)(scratchPad[0]);  // MSB + LSB
      Temperatur = (float)temp_ds;
      Temperatur = (Temperatur / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
      MONITOR_RX_TX.print(Temperatur);
      MONITOR_RX_TX.print(" 'C     ");
      print_DS_adress(i);
      MONITOR_RX_TX.println(" ");
    }
    else {
      Temperatur = No_Val;
      MONITOR_RX_TX.println("Fehler");
    }
  }
  MONITOR_RX_TX.println(" ");
}
