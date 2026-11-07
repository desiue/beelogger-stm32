// 19.12.2022 optional DHT bugfix
// 28.01.2023 use adafruit lib
// 30.01.2023 Powersave
// 15.04.2023 use beelogger DHT-Lib							 
/**
  @brief Funktion  DHT21 / DHT22 - Temperatur und Luftfeuchte
  @param  globale #define
  @return  none, setzt globale Variablen
*/


#if ((Anzahl_Sensoren_DHT > 0) && (Anzahl_Sensoren_DHT < 3))

#include <beelogger_DHT.h>  //modified Adafruit DHT-Lib

void Sensor_DHT() {

  float Temperatur_DHT;
  float Luftfeuchte_DHT;
  debugprintlnF("DHT:");
  debugflush();

  for (uint8_t i = 0 ; i < Anzahl_Sensoren_DHT; i++) {
    Temperatur_DHT = No_Val;
    beelogger_DHT beeDHT(DHT_Sensor_Pin[i], DHTTYPE);
    beeDHT.begin(); // start measurement
    uint8_t x = 4;
    do {
      delay(2100);     // Wait a few seconds between measurements.
      Temperatur_DHT = beeDHT.readTemperature();    // Read temperature as Celsius (the default)
      Luftfeuchte_DHT = beeDHT.readHumidity();

      x--;
      if (x == 0) break;
      debugprintF("*");
    } while (((Temperatur_DHT == 1.0) && (Luftfeuchte_DHT == 1.0)) ||
             (Temperatur_DHT < -30.0) || (Luftfeuchte_DHT == 0.0));
    
    pinMode (DHT_Sensor_Pin[i], INPUT);  // pwrsave

    // Check if any reads failed
    if (isnan(Temperatur_DHT) || (Temperatur_DHT < -30.0) || (Temperatur_DHT > 60.0)) Temperatur_DHT = No_Val;
    if (isnan(Luftfeuchte_DHT) || (Luftfeuchte_DHT < 2.0) || (Luftfeuchte_DHT >= 99.9)) Luftfeuchte_DHT = No_Value;

    if (i == 0) {  // the first DHT
      SensorTemp[1] = Temperatur_DHT;
      SensorFeuchte[1] = Luftfeuchte_DHT;
    } else {  // the second DHT
      SensorTemp[2] = Temperatur_DHT;
      SensorFeuchte[2] = Luftfeuchte_DHT;
    }

    debugprintF(" [C]: ");
    debugprintln(Temperatur_DHT);
    debugprintF(" [%RH]: ");
    debugprintln(Luftfeuchte_DHT);
    debugflush();
  }
}
#else
void Sensor_DHT() {}
#endif
