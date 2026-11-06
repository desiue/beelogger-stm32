/**
  @brief Funktion Si7021 - Temperatur und Luftfeuchte
  @param  none
  @return  none
*/
#define SI7021_Type 0  // 0 = use adafruit lib;  1 = use alternative lib


#if SI7021_Type == 1
#include "SI7021.h"
#else
#include "Adafruit_Si7021.h"
#endif

void Sensor_Si7021() {
  /*
    #if Test_SD_Card
    MONITOR_RX_TX.println(" SD-card test active, no SI7021");
    #else
  */

  float Temperatur_SI;
  float Luftfeuchte_SI;

#if SI7021_Type
  SI7021 sensor7021;
  int check = sensor7021.begin();
  if (check) {
    int temperature = sensor7021.getCelsiusHundredths();
    Temperatur_SI = ((float) temperature) / 100.0;
    int humidity = sensor7021.getHumidityPercent();
    Luftfeuchte_SI = ((float) humidity );
  }
  else {
    MONITOR_RX_TX.print(F(" error "));
  }
#else
  Adafruit_Si7021 mySI7021 = Adafruit_Si7021();
  mySI7021.reset();
  Temperatur_SI = mySI7021.readTemperature();
  Luftfeuchte_SI = mySI7021.readHumidity();
#endif
  MONITOR_RX_TX.print(F(" temperature [C]: "));
  MONITOR_RX_TX.println(Temperatur_SI);
  MONITOR_RX_TX.print(F(" humidity [%RH]: "));
  MONITOR_RX_TX.println(Luftfeuchte_SI);

  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();

}
