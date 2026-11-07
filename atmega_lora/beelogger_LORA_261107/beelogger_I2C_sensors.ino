// 08.02.2020
// 08.04.2020  delay in DS18B20
// 15.04.2020  4x DS18B20
// 10.05.2020  Senden bei Änderung Gewicht
// 03.07.2020  Korrektur Luftdruck auf Standort
// 18.07.2020  ignoriere Waagen ohne Kal-Werte
// 02.09.2020  Waagen ohne Kal-Werte
// 25.10.2020  Temperatur Waegezelle
// 03.12.2020  neue HX711 Bibliothek HX711_Arduino_Library-0.7.4
// 30.12.2020  Anpassung DS18B20
// 08.01.2021 Debug Info
// 28.01.2021  Temperatur Waegezelle
// 03.01.2021 Zuordnung DS18B20
// 19.03.2021 DS18B20 No_Val
// 03.04.2021 Aux[0];
// 03.05.2021 delay DS18B20
// 26.06.2021 add DHT11 if defined DHT_DHT11
// 04.10.2021 HX711 adress struct
// 18.11.2021 HX711 timeout retry
// 14.01.2022 DHT value request
// 01.03.2022 DHT value request delay 2100ms
// 08.04.2022 DS18B20 delay
// 06.05.2022 DHT delay
// 04.06.2022 fix calculation weight swarm alarm
// 23.06.2022 SI7021 value check
// 03.08.2022 splitt HX711 and DS18B20
// 23.01.2023 splitt DHT
// 12.04.2023 test I2C device
// 15.05.2023 fix BH1750 adress
// 22.06.2023 fix BH1750 init



/**
  @brief Funktion   Sensor_Temp_Zelle

  @param  set: initialise or set temperature
  @return  none, setzt globale Variablen
*/
void Sensor_Temp_Zelle(uint8_t set) {
  if (set) {
#if Anzahl_Sensoren_Gewicht > 1
    DS_Temp = SensorTemp[Temp_Zelle];    // duo, tripple, quad, ...
    if (DS_Temp == No_Val) {
      DS_Temp = Aux[0];                  // force a value
    }
#else
    if (SensorTemp[Aussenwerte] == No_Val) { // single
      SensorTemp[Aussenwerte] = Aux[0];      // force a value
    }
#endif
  }
  else {
    SensorTemp[0] = No_Val;      // preset for unused values
    SensorFeuchte[0] = No_Value; // preset for unused values
#if Anzahl_Sensoren_Gewicht > 1
    SensorTemp[Temp_Zelle] = No_Val;
#else
    SensorTemp[Aussenwerte] = No_Val;
#endif
    Aux[0] = rtc.getTemperature();     // default Aux[3]
  }
}



/**
  @brief Funktion Si7021 - Temperatur und Luftfeuchte
  @param  globale #define
  @return  none, setzt globale Variablen
*/
// Auswahl der Library
#define SI7021_Type 0  // 0 = Adafruit Lib, 1 = SI7021 Lib Marcus Sorensen


#if (Anzahl_Sensoren_Si7021 == 1)

#if SI7021_Type == 1
#include "SI7021.h"
#else
#include "Adafruit_Si7021.h"
#endif

void Sensor_Si7021() {

  debugprintlnF("Si7021");
  debugflush();
  SensorTemp[3] = No_Val;

  // SI7021 library checks existance of device, no need to do it in the sketch
#if SI7021_Type
  SI7021 sensor7021;
  int check = sensor7021.begin();
  if (check) {
    int temp = sensor7021.getCelsiusHundredths();
    delay(30);
    unsigned int hum = sensor7021.getHumidityPercent();
    SensorTemp[3] = ((float) temp) / 100.0;
    SensorFeuchte[3]  = ((float) hum );
  }
#else
  Adafruit_Si7021 mySI7021 = Adafruit_Si7021();
  //mySI7021.begin(); // not needed
  SensorTemp[3] = mySI7021.readTemperature();
  if (isnan(SensorTemp[3]))  SensorTemp[3] = No_Val;
  SensorFeuchte[3] = mySI7021.readHumidity();
  if (isnan(SensorFeuchte[3])) SensorFeuchte[3]  = No_Value;
#endif

  debugprintF(" [C]: ");
  debugprintln(SensorTemp[3]);
  debugprintF(" [%RH]: ");
  debugprintln(SensorFeuchte[3]);
  debugflush();
}
#else
void Sensor_Si7021() {};
#endif


/**
  @brief Funktion SHT31 - Temperatur und Luftfeuchte
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if ((Anzahl_Sensoren_SHT31 == 1) || (Anzahl_Sensoren_SHT31 == 2))

#include "beelogger_Adafruit_SHT31.h"

void Sensor_SHT31() {

  float Temperatur_SHT31;
  float Luftfeuchte_SHT31;
  uint8_t check;

  debugprintlnF("SHT31");
  debugflush();

  // beelogger_Adafruit SHT31 library checks existance of device, no need to do it in the sketch
  beelogger_Adafruit_SHT31 my_sht;
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_SHT31; i++) {
    Temperatur_SHT31 = No_Val;

    check = my_sht.begin(SHT31_adresse[i]); // I2C Adresse
    calldelay (100); // time to get sensor ready
    if (check) { // if SHT ok
      Temperatur_SHT31 = my_sht.readTemperature();
      if (isnan(Temperatur_SHT31)) Temperatur_SHT31 = No_Val;
      Luftfeuchte_SHT31 = my_sht.readHumidity();
      if (isnan(Luftfeuchte_SHT31)) Luftfeuchte_SHT31 = No_Value;
    }

    if (i == 0) { // first SHT31
      SensorTemp[4] = Temperatur_SHT31;
      SensorFeuchte[4] = Luftfeuchte_SHT31;
    }
    else { // second SHT31
      SensorTemp[5] = Temperatur_SHT31;
      SensorFeuchte[5] = Luftfeuchte_SHT31;
    }

    debugprintF(" [C]: ");
    debugprintln(Temperatur_SHT31);
    debugprintF(" [%RH]: ");
    debugprintln(Luftfeuchte_SHT31);
    debugflush();
  }
}
#else
void Sensor_SHT31() {};
#endif


/**
  @brief Funktion BME280 - Temperatur und Luftfeuchte, Luftdruck
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if ((Anzahl_Sensoren_BME280 == 1) || (Anzahl_Sensoren_BME280 == 2))

//#include "Adafruit_Sensor.h"
#include "beelogger_Adafruit_BME280.h"
#define SEALEVELPRESSURE_HPA (1013.25f)

void Sensor_BME280() {

  float Temperatur_BME;
  float Luftfeuchte_BME;
  float Luftdruck_BME;
  uint8_t check;

  debugprintlnF("BME280");
  debugflush();

  // beelogger_Adafruit_BME280 library checks existance of device, no need to do it in the sketch

  beelogger_Adafruit_BME280 my_bme;
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_BME280; i++) {
    Temperatur_BME = No_Val;

    check = my_bme.begin(BME280_adresse[i]); // I2C Adresse
    calldelay (100); // time to get sensor ready
    if (check) { // if bme ok
      Temperatur_BME = my_bme.readTemperature();
      if (isnan(Temperatur_BME)) {
        Temperatur_BME = No_Val;
      }
      Luftfeuchte_BME = my_bme.readHumidity();
      if (isnan(Luftfeuchte_BME)) {
        Luftfeuchte_BME = No_Value;
      }
      Luftdruck_BME = my_bme.readPressure();
      if (isnan(Luftdruck_BME)) {
        Luftdruck_BME = No_Value;
      }
      else {
        Luftdruck_BME = Luftdruck_BME / 100.0;
        Luftdruck_BME  = Luftdruck_BME + Korrektur_Luftdruck;
      }
    }

    if (i == 0) { // first BME
      SensorTemp[6] = Temperatur_BME;
      SensorFeuchte[6] = Luftfeuchte_BME;

      Aux[1] = Luftdruck_BME;              // Hier kann die Zuordnung der Sensoren geändert werden
      debugprintF(" [hPa]: ");
      debugprintln(Luftdruck_BME);
    }
    else { // second BME
      SensorTemp[7] = Temperatur_BME;
      SensorFeuchte[7] = Luftfeuchte_BME;
      // Aux[1] = Luftdruck_BME;             // Hier kann die Zuordnung der Sensoren geändert werden
    }

    debugprintF(" [C]: ");
    debugprintln(Temperatur_BME);
    debugprintF(" [%RH]: ");
    debugprintln(Luftfeuchte_BME);
    debugflush();
  }
}
#else
void Sensor_BME280() {};
#endif


/**
  @brief Funktion BH1750 - Beleuchtungsstärke
  auslesen eines einzelnen DS18B20 ohne Bibliothek, wg. Speicherbedarf
  @param  globale #define  BH1750_ADRESS
  @return  none, setzt globale Variablen
*/
#if (Anzahl_Sensoren_Licht == 1)

#include <Wire.h>
#include <AS_BH1750.h>

void Sensor_Licht() {

  // BH1750 library checks existance of device, no need to do it in the sketch
  debugprintlnF("BH1750");
  AS_BH1750 sensor(BH1750_ADRESS);
  Licht = No_Value;

  if (sensor.begin()) {
    Licht = sensor.readLightLevel();
    sensor.powerDown();
  }

  debugprintF(" [lux]: ");
  debugprintln(Licht);
  debugflush();

}
#else
void Sensor_Licht() {};
#endif
