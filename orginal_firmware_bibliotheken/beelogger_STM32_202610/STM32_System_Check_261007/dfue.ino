//----------------------------------------------------------------
// Check communication module
//----------------------------------------------------------------

// Serial2
HardwareSerial Serial2(USART2);   // or HardWareSerial Serial2 (PA3, PA2);;

#define dfue_Baudrate 9600    //  Baudrate für die serielle Kommunikation/Monitor


void dfue() {
#if (dfue_Test == 1)
  digitalWrite(SIM_Power_Pin, HIGH);  // dfue on

  MONITOR_RX_TX.println(F("Testing Com.-Modul"));
  Serial2.begin(dfue_Baudrate);
  delay (2000);
  for (uint8_t x = 0; x < 8; x++) {
    Serial2.println("AT");
    uint8_t r_val = read_ser(500);
    if (r_val == 2) {
      MONITOR_RX_TX.println(F(" found"));
      Serial2.println("AT+SIMCOMATI");
      read_ser(1000);
      Serial2.println("AT+GMR");
      read_ser(1000);
      break;
    }
    delay(5000);
    MONITOR_RX_TX.print(".");
  }
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
  Serial2.end();
#endif
  digitalWrite(SIM_Power_Pin , LOW);
}
//--------------------------------------------------------------



uint8_t read_ser(unsigned long timeout) {
  unsigned long t = millis();
  char c;
  uint8_t f = 0;
  do {
    if (Serial2.available()) {
      c = Serial2.read();
      MONITOR_RX_TX.print(c);
      if (f == 0) {
        if (c == 'O') f = 1;
      }
      else if (f == 1) {
        if (c == 'K') f = 2;
        else f = 0;
      }
      else if (f == 2) {
        if (c == '\r') break;
        else f = 0;
      }
    }
  } while ((millis() - t) < timeout);
  return (f); // f = 2 -> "OK"
}
