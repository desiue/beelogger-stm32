// 03.08.2022 up to 9 DS18B20
// 30.01.2023 Powersave

/**
  @brief Funktion DS18B20 - Temperatur und Luftfeuchte
  auslesen eines einzelnen DS18B20 ohne Bibliothek, wg. Speicherbedarf
  zwei Auslesemethoden verfügbar
   0 - Sensorsuche bei jeder Messwertabfrage
   1 - Abfrage der Messwerte auf Basis der vorher ermittelten und im Array eingetragenen Sensoradressen

  @param  globale #define
  @return  none, setzt globale Variablen
*/

#define DS18B20_Adress_Mode  0    // DS18B20 via Adressen = 1,  DS18B20 per Sensorsuche = 0

// Anzahl_Sensoren_DS18B20 0      // Mögliche Anzahl: '0' ... '9'
//                                   Anzahl:    '0'      --- deaktiviert ---

//                                   Anzahl:    '1','2'  --- Nr 8,9 ---  (Temperatur Innen/Aussen)

//                                   Anzahl:    '3','4'  --- Nr 10,11 ---(Triple,Quad: Temperatur Innen),
//                                                       -  Messwert von Sensor 3,4 mit:
//                                                              - Single in "Luftfeuchte" zu 8,9 (nicht konfigurierbar *)
//                                                              - Duo in Innen-Temp 10, "Luftfeuchte" zu 8 (nicht konfigurierbar *)
//                                                              - Triple in Innen-Temp 10,11
//                                                              - Quad in Innen-Temp 10,11

//                                   Anzahl:    '5','6'  --- Nr 12 --- (Temperatur Innen mit Quad)
//                                                       -  Messwerte von Sensor 5,6 mit:
//                                                              - Single in "Aux1, Aux2" (nicht konfigurierbar *)
//                                                              - Duo in "Luftfeuchte" zu 9,10 (nicht konfigurierbar *)
//                                                              - Triple in "Luftfeuchte" zu 8,9 (nicht konfigurierbar *)
//                                                              - Quad in Innen-Temp 12, "Luftfeuchte" zu 8 (nicht konfigurierbar *)

//                                   Anzahl:    '7','8'    -  Messwert von Sensor 7,8 bei:
//                                                              - Single in "Aux0, Licht" (nicht konfigurierbar *)
//                                                              - Duo in "Aux1, Aux2" (nicht konfigurierbar *)
//                                                              - Triple als "Luftfeuchte" zu 10,11 (nicht konfigurierbar *)
//                                                              - Quad in "Luftfeuchte" zu 9,10 (nicht konfigurierbar *)

//                                   Anzahl:    '9'        -  Messwert von Sensor 9 mit:
//                                                              - Single in Solarspannung (nicht konfigurierbar *)
//                                                              - DUO in "Aux0" (nicht konfigurierbar *)
//                                                              - Triple in "Aux1" (nicht konfigurierbar *)
//                                                              - Quad in "Luftfeuchte" bei 11 (nicht konfigurierbar *)
// (*nicht konfigurierbar) == nur anzupassen durch Sensorzuweisung im Sketch, siehe nachstehenden code "assign values"



#if (Anzahl_Sensoren_DS18B20 > 0)

#if DS18B20_Adress_Mode

/*
   DS18B20 Adress Mode benötigt Eintrag der Adressen der eingesetzten DS18B20 Sensoren
   DS18B20 Adress Mode: adress of each sensor is needed
*/
uint8_t DS18B20_Adressen[9][8] = {
  0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x03, 0x00, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x04, 0x00, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x05, 0x00, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x06, 0x00, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x07, 0x00,
  0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x08,
  0x09, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
};


#include <OneWire.h>

void Sensor_DS18B20() {

#define STARTCONVERSION  0x44  // Tells device to take a temperature reading and put it on the scratchpad
#define READSCRATCH      0xBE  // Read DS18B20
#define CRC_SCRATCH       8
  // #define Sensor_Aufloesung 12//  default is 12 bit
  float Temperatur_DS;
  uint8_t scratchPad[9];

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
      Temperatur_DS = (float)((scratchPad[1] << 8) + scratchPad[0]);  // MSB + LSB
      Temperatur_DS = (Temperatur_DS / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
    }

#else  // end address Mode, continue at "assign values"

//#############################
//#############################
//#############################

#include <OneWire.h>

void Sensor_DS18B20() {  // search sensors and read out data

#define STARTCONVERSION  0x44  // Tells device to take a temperature reading and put it on the scratchpad
#define READSCRATCH      0xBE  // Read DS18B20
#define CRC_SCRATCH       8
  // #define Sensor_Aufloesung 12//  default is 12 bit

  uint8_t DS18B20_Adresse[8];  // DS18B20 ID
  float Temperatur_DS;
  uint8_t parasite = 0;  // need 5V on the sensor
  uint8_t scratchPad[9];  uint8_t devices = 0;

  debugprintlnF("DS18B20");

  OneWire my_O_Wire(ONE_WIRE_BUS);

  //  my_O_Wire.reset_search();

  while (my_O_Wire.search(DS18B20_Adresse)) { // search devices
    if (my_O_Wire.crc8(DS18B20_Adresse, 7) == DS18B20_Adresse[7]) {
      devices++;
    }
  }

  // send command for all devices on the bus to perform a temperature conversion
  my_O_Wire.reset();
  my_O_Wire.skip();
  my_O_Wire.write(STARTCONVERSION, parasite);

  delay(1000); // wait for conversion, 12 bit resolution needs at least 750ms

  if (devices >= Anzahl_Sensoren_DS18B20) {
    devices = Anzahl_Sensoren_DS18B20;
  }

  for (uint8_t i = 0; i < devices; i++) {
    if (my_O_Wire.search(DS18B20_Adresse)) { // search next
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

        if (my_O_Wire.crc8(scratchPad, CRC_SCRATCH) == scratchPad[CRC_SCRATCH]) {  // if CRC o.k.
          Temperatur_DS = (float)((scratchPad[1] << 8) + scratchPad[0]);  // MSB + LSB
          Temperatur_DS = (Temperatur_DS / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
        }
        else {
          Temperatur_DS = No_Val;
          debugprintln("Fehler");
        }
      }
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
          SensorTemp[0] = Temperatur_DS;    // DS18B20 in Temp[0]
          break;
      } // end switch (i)
    }  // end No_Val
    debugprintF(" [C]: ");
    debugprintln(Temperatur_DS);
    debugflush();
    
  }

  pinMode (ONE_WIRE_BUS, INPUT);  // pwrsave

}
#endif

#if (Anzahl_Sensoren_DS18B20 == 0)// no DS18B20 configured
void Sensor_DS18B20() { };
#endif
