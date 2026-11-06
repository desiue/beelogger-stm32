//19.04.2023



//----------------------------------------------------------------
// Funktion BMP280 - Temperatur und Luftdruck
//----------------------------------------------------------------

// Konfiguration Mutli-Sketch:
//
// Anzahl_Sensoren_BMP auf "0" 
//
// Sensor Temperatur Nr. 6 zu Aussenwert oder Beute zuordnen
// #define             Aussenwerte                   6    // 0 oder Nr. 1 - 11
//

// im Hauptsketch in der Liste der Sensoraufrufe ergänzen.
//                 Sensor_BMP280();
// 

// I2C Adresse des BMP280 ist 0x76 oder 0x77, 
// verwendet wird aus beelogger_config.h BME280-Adressen



#if ((Anzahl_Sensoren_BME280 == 1) || (Anzahl_Sensoren_BME280 == 2))
void Sensor_BMP280() {};
#else

#include <beelogger_Adafruit_BMP280.h>
#define SEALEVELPRESSURE_HPA (1013.25f)

void Sensor_BMP280() {

  float Temperatur_BMP = No_Val;
  float Luftdruck_BMP = No_Value;
  bool check;
  delay(200); // wait to boot up the sensor
    
  beelogger_Adafruit_BMP280 my_bmp;

  check = my_bmp.begin(BME280_adresse[0], BMP280_CHIPID); // I2C Adresse and Chip-ID make sure it's a BMP
  delay (100); // time to get system ready
  if (check) { // if bme ok
    Temperatur_BMP = my_bmp.readTemperature();
    if (isnan(Temperatur_BMP)) {
      Temperatur_BMP = No_Val;
    }
    Luftdruck_BMP = my_bmp.readPressure();
    if (isnan(Luftdruck_BMP)) {
      Luftdruck_BMP = No_Value;
    }
    else {
      Luftdruck_BMP = Luftdruck_BMP / 100.0;
      Luftdruck_BMP  = Luftdruck_BMP + Korrektur_Luftdruck;
    }
  }
  Wire.end(); // ! important

  debugprintlnF("BMP280");
  SensorTemp[6] = Temperatur_BMP;
  debugprintF(" [C]: ");
  debugprintln(Temperatur_BMP);

  Aux[1] = Luftdruck_BMP;
  debugprintF(" [hPa]: ");
  debugprintln(Luftdruck_BMP);

  debugflush();
}
#endif
//----------------------------------------------------------------
