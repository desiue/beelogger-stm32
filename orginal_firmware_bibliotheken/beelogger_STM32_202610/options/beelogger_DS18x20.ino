/**
  @brief Funktion DS18x20 - Temperatur und Luftfeuchte
  
  auslesen der DS18x20 (DS1820, DS18S20, DS18B20) Sensoren mit der Dallas Temperatur Bibliothek
  
  kann bei Bedarf die zum beelogger-Sketch gehörende Datei beeloggerDS18B20.ino ersetzen.
  
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if (Anzahl_Sensoren_DS18B20 == 0)
void Sensor_DS18B20() { };
#endif


#if ((Anzahl_Sensoren_DS18B20 > 0) && (Anzahl_Sensoren_DS18B20 < 6))
#include <OneWire.h>
#include <DallasTemperature.h>
#define Sensor_Aufloesung 10

void Sensor_DS18B20() {

  debugprintlnF("DS18B20");
  float Temperatur_DS = No_Val;
  DeviceAddress Sensor_Adressen;
  OneWire oneWire(ONE_WIRE_BUS);
  DallasTemperature sensors(&oneWire);

  sensors.begin();

  for (uint8_t i = 0 ; i < sensors.getDeviceCount(); i++) {
    if (sensors.getAddress(Sensor_Adressen, i)) {
      sensors.setResolution(Sensor_Adressen, Sensor_Aufloesung);
    }
  }

  sensors.requestTemperatures();
  delay(800); // additional wait on sensor ready
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_DS18B20; i++) {
    Temperatur_DS = No_Val;
    if (i < sensors.getDeviceCount()) {
      Temperatur_DS = sensors.getTempCByIndex(i);
      if (Temperatur_DS == DEVICE_DISCONNECTED_C) {
        Temperatur_DS = No_Val;
      }
    }

    // assign values
    if (i == 4) { // the fifth DS18B20
      SensorTemp[0] = Temperatur_DS;  // 5. DS18B20 in Temp[0]
    }
    else {
      SensorTemp[8 + i] = Temperatur_DS; // Temp [] 8,9,10,11
    }


#if (Anzahl_Sensoren_Gewicht < 3) // Single/Double System
#if (Anzahl_Sensoren_DS18B20 == 2) // 2 DS18B20
    SensorFeuchte[8] = Temperatur_DS;  // 2. DS18B20 in Feuchte[]
#else
    if (i == 2) { // 3. DS18B20
      SensorFeuchte[8] = Temperatur_DS;  // 3. DS18B20 in Feuchte[]
    }
    if (i == 3) { // 4. DS18B20
      SensorFeuchte[9] = Temperatur_DS;  // 4. DS18B20 in Feuchte[]
    }
#endif
#endif

    debugprintF(" [C]: ");
    debugprintln(Temperatur_DS);
    debugflush();

  }
}
#endif
