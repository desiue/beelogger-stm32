/**
  @brief Funktion DS18B20 - Temperatur
  @param  none
  @return  none
*/
#include <OneWire.h>

#if Test_SD_Card  // only the first DS18B20 will be tested

void Sensor_DS18B20() {
  uint8_t i, addr[8], data[12];  // für DS1820
  uint16_t temp_ds;
  float f_tmp  = No_Val;

  Serial.println("DS18B20");

  OneWire ds1820(One_Wire_Bus);
  if (ds1820.search(addr)) {
    if (OneWire::crc8( addr, 7) == addr[7]) { // CRC OK
      if (addr[0] == 0x28) {   // is this a DS18_B_20?
        ds1820.reset();
        ds1820.select(addr);
        ds1820.write(0x44, 0);  // start conversion, with parasite power off
        delay(800);             // wait for conversion
        i = ds1820.reset();
        ds1820.select(addr);
        ds1820.write(0xBE);        // read scratchpad
        for ( i = 0; i < 3; i++) { // we need only 3 of 9 bytes
          data[i] = ds1820.read();
        }
        temp_ds = ((int16_t)data[1] << 8) | (int16_t)data[0];  // MSB + LSB
        f_tmp = (float)temp_ds;
        f_tmp = (f_tmp / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
      }
    }  // CRC OK
  } // if ds18b20

  Serial.print(" [C]: ");
  Serial.println(f_tmp);
  Serial.flush();
  pinMode (One_Wire_Bus, INPUT);  // pwrsave
}
#else

void Sensor_DS18B20() {  // search sensors and read out data

#define STARTCONVERSION  0x44  // Tells device to take a temperature reading and put it on the scratchpad
#define READSCRATCH      0xBE  // Read DS18B20
#define CRC_SCRATCH       8
  // #define Sensor_Aufloesung 12//  default is 12 bit

  uint8_t DS18B20_Adresse[8];  // DS18B20 ID
  uint16_t temp_ds;
  float Temperatur_DS;
  uint8_t parasite = 0;  // need 5V on the sensor
  uint8_t scratchPad[9];
  uint8_t devices = 0;
  
  Serial.println(F("DS18B20 ?"));

  OneWire my_O_Wire(One_Wire_Bus);

  //  my_O_Wire.reset_search();

  while (my_O_Wire.search(DS18B20_Adresse)) { // search devices
    switch (DS18B20_Adresse[0]) {
      case 0x10:
        Serial.println(F("DS18 S 20 !"));
        break;
      case 0x22:
        Serial.println(F("DS18 22 !"));
        break;
      case 0x28:
        Serial.println((" "));
        break;
      case 0x3B:
        Serial.println(F("DS18 25 !"));
        break;
      case 0x42:
        Serial.println(F("DS28 EA 00 !"));
        break;
    }

    if (my_O_Wire.crc8(DS18B20_Adresse, 7) == DS18B20_Adresse[7]) {
      devices++;
    }
  }

  // send command for all devices on the bus to perform a temperature conversion
  my_O_Wire.reset();
  my_O_Wire.skip();
  my_O_Wire.write(STARTCONVERSION, parasite);

  delay(1000); // wait for conversion, 12 bit resolution needs at least 750ms

  if (devices > 0) {
    for (uint8_t i = 0; i < devices; i++) {
      if (my_O_Wire.search(DS18B20_Adresse)) { // search next
        if ( DS18B20_Adresse[0] == 0x28) { // DS18B20 only
          if (my_O_Wire.crc8(DS18B20_Adresse, 7) == DS18B20_Adresse[7]) { // valid?
            //print_DS_adress(DS18B20_Adresse);

            my_O_Wire.reset();
            my_O_Wire.select(DS18B20_Adresse);
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
            for (uint8_t k = 0; k < 9; k++) {
              scratchPad[k] = my_O_Wire.read();
            }
            my_O_Wire.reset();

            Serial.print(i + 1);
            if (my_O_Wire.crc8(scratchPad, CRC_SCRATCH) == scratchPad[CRC_SCRATCH]) {  // if CRC o.k.
              temp_ds = ((int16_t)scratchPad[1] << 8) | (int16_t)scratchPad[0];  // MSB + LSB
              Temperatur_DS = (float)temp_ds;
              Temperatur_DS = (Temperatur_DS / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
              Serial.print(F("  "));
              Serial.print(F(":  [C]: "));
              Serial.println(Temperatur_DS);
            }
            else {
              Temperatur_DS = No_Val;
              Serial.println(F("Fehler"));
            }
            Serial.flush();
          } // crc
        } // 18B20
      } // earch
    } // for
  } // devices >0
  else {
    Serial.print(F(" none found."));
  }
  Serial.println(F(" "));
  Serial.flush();
  pinMode (One_Wire_Bus, INPUT);  // pwrsave
}
#endif
