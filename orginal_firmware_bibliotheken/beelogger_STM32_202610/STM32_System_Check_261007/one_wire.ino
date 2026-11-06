//----------------------------------------------------------------
// DS18B20 OneWire 
//----------------------------------------------------------------
#include <OneWire.h>

void discoverOneWireDevices(void) {
  byte i;
  byte addr[8];

  OneWire  ds(One_Wire_Bus);  // Connect your 1-wire device to pin #

  MONITOR_RX_TX.println(F("Looking for 1-Wire devices... (i.e. DS18B20)"));
  MONITOR_RX_TX.print(F("at Pin: "));
  MONITOR_RX_TX.println(One_Wire_Bus);
  MONITOR_RX_TX.flush();
  while (ds.search(addr)) {
    MONITOR_RX_TX.print(F("\n\rFound \'1-Wire\' device with address:\n\r"));
    MONITOR_RX_TX.flush();
    for ( i = 0; i < 8; i++) {
      MONITOR_RX_TX.print("0x");
      if (addr[i] < 16) {
        MONITOR_RX_TX.print('0');
      }
      MONITOR_RX_TX.print(addr[i], HEX);
      if (i < 7) {
        MONITOR_RX_TX.print(", ");
      }
    }
    if ( OneWire::crc8( addr, 7) != addr[7]) {
      MONITOR_RX_TX.print(F("CRC is not valid!\n"));
      return;
    }
  }
  MONITOR_RX_TX.println(F("\n\rOne-Wire scan done.\r\n"));
  MONITOR_RX_TX.flush();
  ds.reset_search();
}
//--------------------------------------------------------------
