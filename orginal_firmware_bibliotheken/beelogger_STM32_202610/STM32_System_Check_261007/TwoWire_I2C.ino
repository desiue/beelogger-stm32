//----------------------------------------------------------------
// TwoWire
//----------------------------------------------------------------

byte from_address = 8;       // lower addresses are reserved to prevent conflicts with other protocols
byte to_address = 119;       // higher addresses unlock other modes, like 10-bit addressing

void discoverI2CDevices()
{
  uint8_t cnt = 0;

  MONITOR_RX_TX.println(F("\nI2CScanner:"));

  Wire.setSDA(SDA_STM);
  Wire.setSCL(SCL_STM);
  Wire.begin();
  Wire.setClock(100000);  // 100kHz
  //  Wire.setClock(400000);  // 400kHz
  
  MONITOR_RX_TX.print(F("starting scanning of I2C bus from "));
  MONITOR_RX_TX.print(from_address, DEC);
  MONITOR_RX_TX.print(" to ");
  MONITOR_RX_TX.print(to_address, DEC);
  MONITOR_RX_TX.println("...");
  MONITOR_RX_TX.flush();

  for ( byte addr = from_address; addr <= to_address; addr++ ) {
    MONITOR_RX_TX.print(addr, HEX);
    MONITOR_RX_TX.print("  ");
    Wire.beginTransmission(addr);
    uint8_t error = Wire.endTransmission();
    if ((addr % 8) == 0 || error == 0) MONITOR_RX_TX.print("\n");
    if (error == 0) {
      i2c_device[cnt] = addr;
      cnt++;
      if (cnt == 16) break; // array limit
      MONITOR_RX_TX.print("found at address 0x");
      MONITOR_RX_TX.println(addr, HEX);
    }
    else if (error == 4) {
      MONITOR_RX_TX.print("Unknown error at address 0x");
      MONITOR_RX_TX.println(addr, HEX);
    }
  }
  Wire.end();
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
}
//--------------------------------------------------------------
