/**
  @brief Funktion BME280 - Temperatur und Luftfeuchte, Luftdruck
  @param  i2c address
  @return  none
*/

#if _BME_280_

void Sensor_BME280(uint8_t addr) {
  MONITOR_RX_TX.println(" no test for BME280");
}

#else

#include "beelogger_Adafruit_BME280.h"
#define SEALEVELPRESSURE_HPA (1013.25f)

void Sensor_BME280(uint8_t addr) {

  float Temperatur_BME;
  float Luftfeuchte_BME;
  float Luftdruck_BME;
  uint8_t check;

  beelogger_Adafruit_BME280 my_bme;
  check = my_bme.begin(addr); // I2C Adresse
  if (!check) {
    MONITOR_RX_TX.println("Could not find a valid sensor.");
    MONITOR_RX_TX.print("SensorID is: 0x"); Serial.println(my_bme.sensorID(), 16);
    MONITOR_RX_TX.print("  0xFF probably means a bad address, a BMP 180 or BMP 085\n");
    MONITOR_RX_TX.print("  0x56-0x58 represents a BMP 280,\n");
    MONITOR_RX_TX.print("  0x60 represents a BME 280.\n");
    MONITOR_RX_TX.print("  0x61 represents a BME 680.\n");
  }

  delay (100); // time to get sensor ready
  if (check) { // if bme ok
    Temperatur_BME = my_bme.readTemperature();
    Luftfeuchte_BME = my_bme.readHumidity();
    Luftdruck_BME = my_bme.readPressure();
    if (!isnan(Luftdruck_BME)) {
      Luftdruck_BME = Luftdruck_BME / 100.0;
    }
    MONITOR_RX_TX.print(F(" temperature [C]: "));
    MONITOR_RX_TX.println(Temperatur_BME);
    MONITOR_RX_TX.print(F(" humidity [%RH]: "));
    MONITOR_RX_TX.println(Luftfeuchte_BME);
    MONITOR_RX_TX.print(F(" pressure [hPa]: "));
    MONITOR_RX_TX.println(Luftdruck_BME);
  }
  else {
    MONITOR_RX_TX.print(F(" error "));
  }
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
}
#endif
