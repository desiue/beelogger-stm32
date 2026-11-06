/**
  @brief Funktion BME280 - Temperatur und Luftfeuchte, Luftdruck
  @param  i2c address
  @return  none 
*/
#include <AS_BH1750.h>

void Sensor_Licht(uint8_t addr) {

  float Licht = 0.0;
  AS_BH1750 sensor(addr);
  if (sensor.begin()) {
    Licht = sensor.readLightLevel();
    sensor.powerDown();
  }

  MONITOR_RX_TX.print(F(" [lux]: "));
  MONITOR_RX_TX.println(Licht);
  MONITOR_RX_TX.flush();

}
