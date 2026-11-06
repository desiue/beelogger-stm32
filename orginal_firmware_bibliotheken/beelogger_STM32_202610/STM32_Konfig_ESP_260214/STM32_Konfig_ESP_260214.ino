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

    http://github.com/stm32duino/wiki/wiki/Libraries

    STM32 Core Version 2.0.0

    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k
              STM32F401CC 256k
              STM32F401CE 512k


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
    BlackPill F411CE F401CC F401CE (WeAct Blackpill)

    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload methoed "STM32 Cube Programmer Serial"   ( == FTDI)
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de


//----------------------------------------------------------------
// Konfiguration
//----------------------------------------------------------------


#define ESP_Setup_Baudrate 115200   // Baudrate für Setup ESP: 115200 oder 57600

#define ESP_Setup 0      // 0 oder 1;  0 = Test only, 1 = set ESP Baudrate to 9600 Baud, followed by Test

// mit 0 kann die serielle Kommunikation mit dem ESP bei 9600Bd getestet werden
//   dieser Sketch sollte bei richtiger Konfiguration des ESP
//   damit eine ähnliche Ausgabe liefern:
/*
  ESP Test Begin
  Baudrate: 9600Bd
  Sende Abfrage 'AT+GMR' an ESP8266
  AT+GMR
  AT version:1.2.0.0(Jul  1 2016 20:04:45)
  SDK version:1.5.4.1(39cb9a32)
  Dec  2 2016 14:21:16
  OK

*/

// mit 1 wird mit ESP_Setup_Baudrate der ESP auf 9600 Baud umgestellt
//   dieser Sketch liefert hierbei folgende Ausgabe;
//   nachfolgend wird automatisch der zuvor dargestellte Test durchgeführt
/*

  ESP Setup to 9600Bd

*/

//----------------------------------------------------------------
// Ende Konfiguration
//----------------------------------------------------------------


char ID[] = "STM32 ESP Konfig 14.02.2026";



HardwareSerial Serial2(USART2);   // or HardWareSerial Serial2 (PA3, PA4)



#define Test_Baudrate 9600    //  Baudrate für die serielle Kommunikation/Monitor

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX  Serial1
#else
#define MONITOR_RX_TX  Serial
#endif


void setup() {
#define ESP_RESET PB4
#define POWER PB5
  pinMode(ESP_RESET , OUTPUT);
  digitalWrite(ESP_RESET , HIGH);
  pinMode(POWER, OUTPUT);
  digitalWrite(POWER, HIGH);
  delay(1000);

#if ESP_Setup
  esp_config();
#endif
  esp_test();
}

void loop() {
  delay(30000);
  digitalWrite(POWER, LOW);
}

// set ESP Baudrate to 9600Bd
void esp_config() {
  MONITOR_RX_TX.begin(Test_Baudrate);
  while (!MONITOR_RX_TX) ; // wait for Arduino MONITOR_RX_TX Monitor to open
  MONITOR_RX_TX.println(ID);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println("ESP Setup to 9600Bd");
  MONITOR_RX_TX.print("ESP Baudrate für Setup: ");
  MONITOR_RX_TX.println(ESP_Setup_Baudrate);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(F(" Weiter mit w"));

  char c = '0';
  while (c != 'w') {
    c = MONITOR_RX_TX.read();
  }
  MONITOR_RX_TX.println(" ");
  char setBaud[] = "AT+UART_DEF=9600,8,1,0,0";
  MONITOR_RX_TX.println("Sende an ESP: ");
  MONITOR_RX_TX.println("AT+RESTORE");
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(setBaud);
  MONITOR_RX_TX.end();     // Altserial exklusiver Betrieb für Setup ESP
  delay(500);

  Serial2.begin(Test_Baudrate);
  while (!Serial2) ; // wait for Arduino Serial Monitor to open
  delay(200);
  Serial2.println("AT+RESTORE");
  delay(2000);
  Serial2.end();
  delay(500);

  Serial2.begin(ESP_Setup_Baudrate);
  while (!Serial2) ; // wait for Arduino Serial Monitor to open
  delay(200);
  Serial2.println("AT+RESTORE");
  delay(2000);
  Serial2.println(setBaud);
  delay(200);
  Serial2.end();
  delay(500);
}

// check communication, read firmware version, set Default-Mode, do some tests
void esp_test() {
  MONITOR_RX_TX.begin(Test_Baudrate);
  while (!Serial) ; // wait for Arduino Serial Monitor to open
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(ID);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(F("ESP Test Begin"));
  MONITOR_RX_TX.print(F("Baudrate: "));
  MONITOR_RX_TX.println(Test_Baudrate);
  MONITOR_RX_TX.println(F("\nSende Abfrage Firmware/Version an ESP8266: 'AT+GMR' "));
  MONITOR_RX_TX.flush();
  delay(50);
  Serial2.begin(Test_Baudrate);
  while (!Serial2) ; // wait for Arduino Serial Monitor to open
  Serial2.println("AT+GMR");
  read_ser(2000);

  MONITOR_RX_TX.println(F("Setze Betriebsart 'Station' in ESP8266"));
  MONITOR_RX_TX.flush();
  delay(50);
  Serial2.println("AT+CWMODE_DEF=1");
  read_ser(2000);

  delay(200);
  MONITOR_RX_TX.println(F("Sende suche Access Points an ESP8266: 'AT+CWLAP'"));
  MONITOR_RX_TX.flush();
  delay(50);
  Serial2.println("AT+CWLAP");
  read_ser(10000);
  Serial2.println("AT+CWLAPOPT=1,6");
  read_ser(2000);
  Serial2.println("AT+CWLAP");
  read_ser(10000);
  MONITOR_RX_TX.println(" ... end ...");
}

void read_ser(unsigned long timeout) {
  unsigned long t = millis();
  char c;
  do {
    while (MONITOR_RX_TX.available()) {
      c = MONITOR_RX_TX.read();
      Serial2.print(c);
    }
    while (Serial2.available()) {
      c = Serial2.read();
      MONITOR_RX_TX.print(c);
    }
  } while ((millis() - t) < timeout);
}
