/**
  @brief Funktion SHT31 - Temperatur und Luftfeuchte
  @param  i2c address
  @return  none
*/

#include "beelogger_Adafruit_SHT31.h"

void Sensor_SHT31(uint8_t addr) {
/*
#if Test_SD_Card
  MONITOR_RX_TX.println(" SD-card test active, no SHT31");
#else
*/
#if 1

  float Temperatur_SHT31;
  float Luftfeuchte_SHT31;
  uint8_t check;

  beelogger_Adafruit_SHT31 my_sht;
  check = my_sht.begin(addr); // I2C Adresse
  delay (100); // time to get sensor ready
  if (check) { // if SHT ok
    Temperatur_SHT31 = my_sht.readTemperature();
    Luftfeuchte_SHT31 = my_sht.readHumidity();
    MONITOR_RX_TX.print(F(" temperature [C]: "));
    MONITOR_RX_TX.println(Temperatur_SHT31);
    MONITOR_RX_TX.print(F(" humidity [%RH]: "));
    MONITOR_RX_TX.println(Luftfeuchte_SHT31);
  }
#endif
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();

}
