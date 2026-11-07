// Hier die LORA Sequence  / Counter eintreagen

uint16_t LoRaSeqCounter = 1030;   // TTN Sequence numbber (cnt)


#include <EEPROM.h>

#define eeAddress  0x20 // EE-Prom Adress wear leveling
#define eeAd_data  0x30 // EE-Prom Adress LoRa sequence number
#define debugbegin(x) Serial.begin(x)
#define debugprintF(x) Serial.print(F(x))
#define debugprintlnF(x) Serial.println(F(x))
#define debugprint(x) Serial.print(x)
#define debugprintH(x) Serial.print(x,HEX)
#define debugprintln(x) Serial.println(x)
#define debugflush() Serial.flush()
#define debugend() Serial.end()


uint8_t data_base = 0;      // EE-Prom data adress  (bs)
uint8_t data_cnt;           // EE-Prom wear Counter  (wr)

void setup() {

  debugbegin(9600);
  debugprintlnF("set LORA Counter");
  debugflush();
  EEPROM.put((eeAddress), data_base);

  EEPROM.put(eeAd_data + (data_base * 2), LoRaSeqCounter); // *2 =  two bytes
  inc_LoRaSeq();
  debugprintlnF(" all done");
}

void loop() {
  // put your main code here, to run repeatedly:

}
void  inc_LoRaSeq() {        // increment Lora sequence number
  LoRaSeqCounter += 1;
  if (LoRaSeqCounter > 16382) LoRaSeqCounter = 0;
  if ((LoRaSeqCounter % 128) == 0) { // check wearlevel data every 128 writecycle
    data_cnt += 1;           // inc write counter
    if ( data_cnt > 0xEE) {  // if a cell has been written 128 * 238 (0xEE) times (11300)
      data_cnt = 0;          // reset write counter
      data_base += 1;        // use next element in eeprom
    }
    if (data_base > 16) data_base = 0;       // 16 elements only
    EEPROM.put((eeAddress), data_base);
    EEPROM.put((eeAddress + 1), data_cnt);
  }
  EEPROM.put(eeAd_data + (data_base * 2), LoRaSeqCounter); // *2 =  two bytes
  debugprintF("Cnt:"); //LoRa Counter: ");
  debugprint(LoRaSeqCounter);
  debugprintF(" bs:");   // Base
  debugprint(data_base);
  debugprintF(" wr:");   //writes
  debugprintln(data_cnt);
  debugflush();
}
