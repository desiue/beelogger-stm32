//----------------------------------------------------------------
// Test I2C devices found
//----------------------------------------------------------------
void test_i2c_devices() {

  uint8_t cnt = 0;

  Wire.begin();
  Wire.setClock(100000);

  while ((  i2c_device[cnt] != 0) && (cnt < 16)) {

    switch (i2c_device[cnt]) {
      case 0x20:
      case 0x21:
      case 0x22:
        MONITOR_RX_TX.println(" MCP23017");
        MONITOR_RX_TX.println("    found.");
        break;

      case 0x5C:
      case 0x23:
        MONITOR_RX_TX.println(" BH 1750");
        if (i2c_device[cnt] == 0x5C) MONITOR_RX_TX.println(" at alternativ adress 0x5C:");
        Sensor_Licht(i2c_device[cnt]);
        break;
      case 0x40:
        MONITOR_RX_TX.println(" SI7021");
        Sensor_Si7021();
        break;
      case 0x44:
      case 0x45:
        MONITOR_RX_TX.println(" SHT 31");
        Sensor_SHT31(i2c_device[cnt]);
        break;
      case 0x53:
      case 0x54:
      case 0x55:
      case 0x56:
      case 0x57:
        MONITOR_RX_TX.println(" EE-Prom");
        test_EE(i2c_device[cnt]);
        break;
      case 0x68:
        MONITOR_RX_TX.println(" DS3231");
        MONITOR_RX_TX.println("    found.");
        break;
      case 0x76:
      case 0x77:
        MONITOR_RX_TX.println(" BME/BMP");
        Sensor_BME280(i2c_device[cnt]);    
        Sensor_BMP280(i2c_device[cnt]);
        Sensor_BME680(i2c_device[cnt]);
        break;
      default:
        MONITOR_RX_TX.print(" no test available for 0x");
        MONITOR_RX_TX.println(i2c_device[cnt]);
        break;
    }
    cnt++;
  }
  Wire.end();
}
