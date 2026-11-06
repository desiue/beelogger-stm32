// 19.12.2022 optional DHT bugfix
// 29.01.2023 use Adafruit Lib
// 30.01.2024 fix bad DHT values


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

  debugprintlnF("DHT: ");
  debugflush();
  for (uint8_t i = 0; i < Anzahl_Sensoren_DHT; i++) {
    Temperatur_DHT = No_Val;
    Luftfeuchte_DHT = No_Value;
    beelogger_DHT beeDHT(DHT_Sensor_Pin[i], DHTTYPE);
    beeDHT.begin();  // start measurement

    int x = 0;
    do {
      LogDelay(2100);  // wait for DHT ready
      Temperatur_DHT = beeDHT.readTemperature();
      Luftfeuchte_DHT = beeDHT.readHumidity();
      if ((Luftfeuchte_DHT > 1.0) && (Temperatur_DHT > 1.0)) x = 4;  //force break
      else {
        debugprint(Temperatur_DHT);
        debugprintF("  ");
        debugprintln(Luftfeuchte_DHT);
        debugflush();
        Temperatur_DHT = No_Val;     //99.9
        Luftfeuchte_DHT = No_Value;  //-1.0
      }
      x++;
    } while (x < 4);

    if (isnan(Temperatur_DHT) || (Temperatur_DHT < -30.0) || (Temperatur_DHT > 60.0)) Temperatur_DHT = No_Val;
    if (isnan(Luftfeuchte_DHT) || (Luftfeuchte_DHT < 2.0) || (Luftfeuchte_DHT > 99.9)) Luftfeuchte_DHT = No_Value;

    if ((Luftfeuchte_DHT == No_Value) && (Temperatur_DHT == -1.0)) Temperatur_DHT = No_Val; // no valid values

    if (i == 0) {  // the first DHT
      SensorTemp[1] = Temperatur_DHT;
      SensorFeuchte[1] = Luftfeuchte_DHT;
    } else {  // the second DHT
      SensorTemp[2] = Temperatur_DHT;
      SensorFeuchte[2] = Luftfeuchte_DHT;
    }

    debugprintF(" [C]: ");
    debugprintln(Temperatur_DHT);
    debugprintF(" [% RH]: ");
    debugprintln(Luftfeuchte_DHT);
    debugflush();
  }
}
#else
void Sensor_DHT() {}
#endif
