//----------------------------------------------------------------
// EE-Prom
//----------------------------------------------------------------
#include <beelogger_at24cxx.h>

void test_EE(uint8_t addr) {

  AT24Cxx  my_EEPROM(addr);
  if (my_EEPROM.isPresent() == 0) {
    MONITOR_RX_TX.print("      is present at: 0x");
    MONITOR_RX_TX.println(addr, HEX);
    eeprom_test(addr);
  }
  else {
    MONITOR_RX_TX.print(" EE-Prom not found at: 0x53 / 0x57");
  }
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
}

void eeprom_test(uint8_t nmr) {
  char mybuffer[32];
  char mybuff[32];
  long i = 0, m;
  MONITOR_RX_TX.print("\n  ");
  AT24Cxx  my_EEPROM(nmr);
  my_EEPROM.WriteStr(0, "Test!");
  // EE-Prom data pointer wraps around, when size exceeded
  // data at "0" is overwritten
  do {
    if ((i % 4) == 0) {
      MONITOR_RX_TX.print((i + 4) / 4);
      MONITOR_RX_TX.print(", ");
    }
    i++;
    ltoa(i, mybuffer, 10);
    my_EEPROM.WriteStr(i * 256, mybuffer); // write data
    my_EEPROM.ReadStr(i * 256, mybuff, 8); // read to verify
    if (strcmp(mybuff, mybuffer) == 0) {
      my_EEPROM.ReadStr(0, mybuff, 8);     // reading at "0"
    }
  } while (strcmp("Test!", mybuff) == 0);  // not yet overwritten?
  Wire.end();
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print(" EE-Prom: ");
  m = i * 256 / 1024;
  MONITOR_RX_TX.print(m);
  MONITOR_RX_TX.println(" kByte\n");
  MONITOR_RX_TX.println("Sketch Konfiguration:");
  if(m == 8)  MONITOR_RX_TX.println("#define EEProm_Size 8192");
  if(m == 16)  MONITOR_RX_TX.println("#define EEProm_Size 16384");
  if(m == 32)  MONITOR_RX_TX.println("#define EEProm_Size 32768");
  MONITOR_RX_TX.flush();
}
//----------------------------------------------------------------
