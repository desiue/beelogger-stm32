/*
   (C) 2020 R.Schick - beelogger.de

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
    http://github.com/stm32duino/wiki/wiki/Libraries

    STM32 Core Version 2.0.0

    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k
              STM32F401CC 256k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE  (WeDoAct Blackpill V 2.0)

    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload methoed "STM32 Cube Programmer Serial"   ( == FTDI)

*/
// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de


char ID[] = "beelogger STM Test RXTX 14.02.2026";




#define Test_Baudrate 9600    //  Baudrate für die serielle Kommunikation/Monitor

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX  Serial1
#else
#define MONITOR_RX_TX  Serial
#endif

HardwareSerial Serial2(USART2);   // or HardWareSerial Serial2 (PA3, PA

void setup() {
#define ESP_RESET PB4
#define POWER PB5
  pinMode(ESP_RESET , OUTPUT);
  digitalWrite(ESP_RESET , LOW);
  pinMode(POWER, OUTPUT);
  digitalWrite(POWER, HIGH);

  my_blink(3, 500);
  Serial2.begin(Test_Baudrate);
  while (!Serial2);

  MONITOR_RX_TX.begin(Test_Baudrate);
  while (!MONITOR_RX_TX);
  MONITOR_RX_TX.println(ID);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println("  Start des SIM 76x0 bzw. ESP8266 beobachten oder ");
  MONITOR_RX_TX.println("  bei Fehlersuche Modul entfernen und RX mit TX an der Buchsenleiste SIM800 oder ESP8266 verbinden. ");
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(" Monitor Einstellung 'Sowohl NL als auch CR' vornehmen");

  MONITOR_RX_TX.println(" Testdaten am Monitor eingeben: ");
  MONITOR_RX_TX.println("   diese werden im Monitor angezeigt. ");
  MONITOR_RX_TX.flush();
  digitalWrite(ESP_RESET , HIGH);
    MONITOR_RX_TX.println("\n<-:");
}

char incomingByte; // for incoming serial data
char c;

void loop() {
  while (MONITOR_RX_TX.available()) {
    c = MONITOR_RX_TX.read();
    Serial2.print(c);
    MONITOR_RX_TX.print(c);
  }
  // reply only when you receive data:
  if (Serial2.available() > 0) {
    while (Serial2.available() > 0) {
      // read the incoming data:
      incomingByte = Serial2.read();
      MONITOR_RX_TX.print(incomingByte);
    }
  }
}


void my_blink(uint8_t nmr, uint16_t howlong) {
  for (uint8_t x = 0; x < nmr; x++) {
    pinMode(LED_BUILTIN, OUTPUT);
    digitalWrite(LED_BUILTIN, HIGH);   // turn the LED on (HIGH is the voltage level)
    delay(howlong);                       // wait for a second
    digitalWrite(LED_BUILTIN, LOW);    // turn the LED off by making the voltage LOW
    delay(howlong);                       // wait for a second
  }
}
