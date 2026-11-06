/**
  @brief Funktion BMP280 - Temperatur und Luftfeuchte, Luftdruck
  @param  i2c address
  @return  none
*/

#include <beelogger_Adafruit_BMP280.h>
#define SEALEVELPRESSURE_HPA (1013.25f)

void Sensor_BMP280(uint8_t addr) {

  float Temperatur_BMP = No_Val;
  float Luftdruck_BMP = No_Value;
  uint8_t check;
  delay(200); // wait to boot up the sensor
    
  beelogger_Adafruit_BMP280 my_bmp;

  check = my_bmp.begin(addr, BMP280_CHIPID); // I2C Adress and Chip-ID make sure it's a BMP
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
  }

  Serial.println("BMP280");
  Serial.print(" [C]: ");
  Serial.println(Temperatur_BMP);

  Serial.print(" [hPa]: ");
  Serial.println(Luftdruck_BMP);

  Serial.flush();
}

//----------------------------------------------------------------
