// 03.08.2022 up to 9 DS18B20
// 18.01.2023 negative temperature

/**
  @brief Funktion DS18B20 - Temperatur und Luftfeuchte
  auslesen eines einzelnen DS18B20 ohne Bibliothek, wg. Speicherbedarf
  @param  globale #define
  @return  none, setzt globale Variablen
*/

#define DS18B20_Adress_Mode  0  // DS18B20 via Adressen = 1,  DS18B20 per Sensorsuche = 0



#if (Anzahl_Sensoren_DS18B20 > 0)
#include <OneWire.h>
#define STARTCONVERSION  0x44  // Tells device to take a temperature reading and put it on the scratchpad
#define READSCRATCH      0xBE  // Read DS18B20
#define CRC_SCRATCH       8
// #define Sensor_Aufloesung 12//  default is 12 bit


#if DS18B20_Adress_Mode

/*
   DS18B20 Adress Mode benötigt Eintrag der Adressen der eingesetzten DS18B20 Sensoren
*/
uint8_t DS18B20_Adressen[9][8] = {
  0x28, 0x88, 0xF5, 0x49, 0xF6, 0x59, 0x3C, 0xD6,
  0x28, 0x98, 0xFD, 0x49, 0xF6, 0x7C, 0x3C, 0x8D,
  0x28, 0xD8, 0xE3, 0x49, 0xF6, 0x36, 0x3C, 0x5A,
  0x28, 0x8E, 0xE0, 0x49, 0xF6, 0x4A, 0x3C, 0x73,
  0x28, 0xF1, 0x27, 0x49, 0xF6, 0x32, 0x3C, 0x7C,
  0x28, 0xED, 0xE2, 0x49, 0xF6, 0x59, 0x3C, 0xD3,
  0x28, 0x83, 0xBF, 0x49, 0xF6, 0x4E, 0x3C, 0xF8,
  0x28, 0xB3, 0xF0, 0x49, 0xF6, 0xFA, 0x3C, 0xAA,
  0x28, 0xBB, 0x47, 0x49, 0xF6, 0x48, 0x3C, 0x86,
};




void Sensor_DS18B20() {

  uint8_t scratchPad[9];
  float Temperatur_DS;

  debugprintlnF("DS18B20");
  OneWire myoneWire(ONE_WIRE_BUS);

  // sends command for all devices on the bus to perform a temperature conversion
  myoneWire.reset();
  myoneWire.skip();
  myoneWire.write(STARTCONVERSION, 0);  // 0 = Versorgung mit 5V (no parasite)

  delay(1000); // wait for conversion, 12 bit resolution needs at least 750ms

  for (uint8_t i = 0 ; i < Anzahl_Sensoren_DS18B20; i++) {
    debugprint(i + 1);
    Temperatur_DS = No_Val;

    myoneWire.reset();
    myoneWire.select(DS18B20_Adressen[i]);
    myoneWire.write(READSCRATCH);        // read scratchpad
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
      scratchPad[k] = myoneWire.read();
    }
    myoneWire.reset();

    if (myoneWire.crc8(scratchPad, CRC_SCRATCH) == scratchPad[CRC_SCRATCH]) {  // if CRC o.k.
      int16_t ds_temp = (scratchPad[1] << 8) | scratchPad[0];  // MSB + LSB
      Temperatur_DS = (float)(ds_temp);
      Temperatur_DS = (Temperatur_DS / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
    }

#else  // end address Mode, continue at "assign values"

//#############################
//#############################
//#############################

void Sensor_DS18B20() {  // search sensors and read out data

  uint8_t parasite = 0;  // need 5V on the sensor
  uint8_t scratchPad[9];
  uint8_t DS18B20_Adressen[Anzahl_Sensoren_DS18B20][8];  // DS18B20 ID Array
  float Temperatur_DS;
  uint8_t devices = 0;

  debugprintlnF("DS18B20");

  OneWire my_O_Wire(ONE_WIRE_BUS);

  while (my_O_Wire.search(DS18B20_Adressen[devices]) && (devices < Anzahl_Sensoren_DS18B20 )) {
    if (my_O_Wire.crc8(DS18B20_Adressen[devices], 7) == DS18B20_Adressen[devices][7]) {
      devices++;
    }
  }
  // send command for all devices on the bus to perform a temperature conversion
  my_O_Wire.reset();
  my_O_Wire.skip();
  my_O_Wire.write(STARTCONVERSION, parasite);

  delay(1000); // wait for conversion, 12 bit resolution needs at least 750ms

  for (uint8_t i = 0; i < devices; i++) {
    debugprint(i + 1);
    Temperatur_DS = No_Val;

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
    for (uint8_t k = 0; k < 9; k++) {
      scratchPad[k] = my_O_Wire.read();
    }
    my_O_Wire.reset();

    if (my_O_Wire.crc8(scratchPad, CRC_SCRATCH) == scratchPad[CRC_SCRATCH]) {  // if CRC o.k.
      int16_t ds_temp = (scratchPad[1] << 8) | scratchPad[0];  // MSB + LSB
      Temperatur_DS = (float)(ds_temp);
      Temperatur_DS = (Temperatur_DS / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
    }
    else {
      Temperatur_DS = No_Val;
      debugprintln("Fehler");
    }
#endif  // endif search Mode



    //**********************
    // assign values
    //**********************

    if (Temperatur_DS != No_Val) {
      switch (i) {
        case 0:
          SensorTemp[8] = Temperatur_DS; // Temp [] innen
          break;
        case 1:
          SensorTemp[9] = Temperatur_DS; // Temp [] aussen
          break;


#if (Anzahl_Sensoren_Gewicht == 1) // Single System
        case 2:
          SensorFeuchte[8] = Temperatur_DS; // Feuchte [] innen
          break;
        case 3:
          SensorFeuchte[9] = Temperatur_DS; // Feuchte [] aussen
          break;
        case 4:
          Aux[1] = Temperatur_DS; // Aux
          break;
        case 5:
          Aux[2] = Temperatur_DS; // Aux
          break;
        case 6:
          Aux[0] = Temperatur_DS; // Aux
          break;
        case 7:
          Licht = Temperatur_DS; // Aux
          break;
        case 8:
          Solarspannung = Temperatur_DS; // Aux
          break;
#endif


#if (Anzahl_Sensoren_Gewicht == 2) // Duo System
        case 2:
          SensorTemp[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 3:
          SensorFeuchte[8] = Temperatur_DS; // Feuchte [] aussen
          break;
        case 4:
          SensorFeuchte[9] = Temperatur_DS; // Feuchte [] aussen
          break;
        case 5:
          SensorFeuchte[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 6:
          Aux[1] = Temperatur_DS; // Aux
          break;
        case 7:
          Aux[2] = Temperatur_DS; // Aux
          break;
        case 8:
          Aux[0] = Temperatur_DS; // Aux
          break;
#endif


#if (Anzahl_Sensoren_Gewicht == 3) // Triple System
        case 2:
          SensorTemp[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 3:
          SensorTemp[11] = Temperatur_DS; // Feuchte [] innen
          break;
        case 4:
          SensorFeuchte[8] = Temperatur_DS; // Feuchte [] aussen
          break;
        case 5:
          SensorFeuchte[9] = Temperatur_DS; // Feuchte [] innen
          break;
        case 6:
          SensorFeuchte[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 7:
          SensorFeuchte[11] = Temperatur_DS; // Feuchte [] innen
          break;
        case 8:
          Aux[1] = Temperatur_DS; // Aux
          break;
#endif


#if (Anzahl_Sensoren_Gewicht > 3) // Quad System
        case 2:
          SensorTemp[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 3:
          SensorTemp[11] = Temperatur_DS; // Feuchte [] innen
          break;
        case 4:
          SensorTemp[12] = Temperatur_DS; // Feuchte [] innen
          break;
        case 5:
          SensorFeuchte[8] = Temperatur_DS; // Feuchte [] aussen
          break;
        case 6:
          SensorFeuchte[9] = Temperatur_DS; // Feuchte [] innen
          break;
        case 7:
          SensorFeuchte[10] = Temperatur_DS; // Feuchte [] innen
          break;
        case 8:
          SensorFeuchte[11] = Temperatur_DS; // Feuchte [] innen
          break;

#endif

        default:
          SensorTemp[0] = Temperatur_DS;  // 5. DS18B20 in Temp[0]
          break;
      } // end switch (i)
    }  // end No_Val
    debugprintF(" [C]: ");
    debugprintln(Temperatur_DS);
    debugflush();

  }
}
#endif

#if (Anzahl_Sensoren_DS18B20 == 0)// no DS18B20 configured
void Sensor_DS18B20() { };
#endif
