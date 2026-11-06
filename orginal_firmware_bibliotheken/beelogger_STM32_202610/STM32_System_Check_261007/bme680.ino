//----------------------------------------------------------------
// Funktion BME680
//----------------------------------------------------------------
//----------------------------------------------------------------

//----------------------------------------------------------------
#if (_BMP680_ == 0)

void Sensor_BME680(uint8_t addr) {
  UNUSED (addr);
  Serial.println("no test for BME 680");
}

#else

#include "Zanshin_BME680.h"

void Sensor_BME680(uint8_t addr) {
  int32_t  temp, humidity, pressure, gas;  // BME readings
  float t,h,p,g;

  BME680_Class BME680;  ///< Create an instance of the BME680 class
  MONITOR_RX_TX.print(F("BME680\n"));
  delay(2000);
  if (BME680.begin(I2C_STANDARD_MODE,addr)) {  // Start BME680 using I2C
    //("- Setting 16x oversampling for all sensors\n"));
    BME680.setOversampling(TemperatureSensor, Oversample16);  // Use enumerated type values
    BME680.setOversampling(HumiditySensor, Oversample16);     // Use enumerated type values
    BME680.setOversampling(PressureSensor, Oversample16);     // Use enumerated type values
    //("- Setting IIR filter to a value of 4 samples\n"));
    BME680.setIIRFilter(IIR4);  // Use enumerated type values
    //("- Setting gas measurement to 320\xC2\xB0\x43 for 150ms\n"));  // "°C" symbols
    BME680.setGas(320, 150);  // 320°c for 150 milliseconds

    BME680.getSensorData(temp, humidity, pressure, gas);  // Get readings
    delay(2000);
    BME680.getSensorData(temp, humidity, pressure, gas);  // Get readings
  }

  t = (float)temp / 100.0;
  h = (float)humidity / 1000.0;
  p = (float)pressure / 100.0;
  g = (float)gas / 100.0;

  MONITOR_RX_TX.print(F(" BME680 data: "));
  MONITOR_RX_TX.print(F(" temperature [C]: "));
  MONITOR_RX_TX.println(t);
  MONITOR_RX_TX.print(F(" humidity [%RH]: "));
  MONITOR_RX_TX.println(h);
  MONITOR_RX_TX.print(F(" pressure [hPa]: "));
  MONITOR_RX_TX.println(p);
  MONITOR_RX_TX.println(g);
  MONITOR_RX_TX.flush();
}
#endif
//----------------------------------------------------------------
