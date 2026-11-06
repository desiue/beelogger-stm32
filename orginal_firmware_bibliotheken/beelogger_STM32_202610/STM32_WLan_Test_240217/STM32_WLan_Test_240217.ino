/*
   (C) 2020 R.Schick

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de
// Version 01.05.2021


//----------------------------------------------------------------
// Konfiguration
//----------------------------------------------------------------

#define DEBUG__ESP 1

// Name des WLAN-Zugangspunkts
const char  Access_Point[] PROGMEM = "AP_Name";

// Passwort des Zugangspunktes
const char AP_Passwort[] PROGMEM =   "AP_Passwort";

// Domainname zum Webserver mit beelogger-Skript, Bsp:"meineDomain.de";
const char serverName[] PROGMEM = "community.beelogger.de";

// GET mit Verzeichnis  Webserver-Skript und das PHP Skript für den jeweiligen beelogger
// Bsp: "GET /USER_X/beelogger6/beelogger_log.php?"
const char pfad[] PROGMEM = "GET /USER/beelogger1/beelogger_log.php?";   //USERX = meinOrdner bzw. Name, Y = beelogger Nummer  1...n

// Passwort vom Webserver-Skript
const char Passwort[] PROGMEM =  "Log"; // Log = Default


//----------------------------------------------------------------
// Ende Konfiguration
//----------------------------------------------------------------

#include <Wire.h>

//----------------------------------------------------------------
// STM32  RTC, Low-Power
//----------------------------------------------------------------
#include "STM32_beelogger.h"
#include <STM32LowPower.h>
#include <STM32RTC.h>
#include "time.h"
/*Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();
//----------------------------------------------------------------

#define Seriell_Baudrate 9600

//----------------------------------------------------------------
// ESP-8266 WLAN Modul
//----------------------------------------------------------------
#define ESP_Baudrate 9600
#include "ESP_STM_beelogger.h"
C_WLAN_ESP01 my_esp;


const char Str_Http[]  PROGMEM = " HTTP/1.1\r\n";
const char Str_Con[]   PROGMEM = "Connection: close\r\n\r\n";
//----------------------------------------------------------------


// Test-Messdaten (regulär kommen die Messdaten von den Sensoren)
float TempIn = 25.00;
float TempOut = 35.00;
float FeuchteIn = 40.00;
float FeuchteOut = 60.00;
float Licht = 50000.00;
float Gewicht = 25.0;
float Batteriespannung = 3.70;
float Solarspannung = 1.70;
float Service = 0.0;

uint8_t OK = true;
char parameter[80], data[64];
uint8_t first_run = 1;
uint8_t Ds3231_ok = 0;


void setup() {

  //----------------------------------------------------------------
  // Setup RTC:
  // Select RTC clock source: LSI_CLOCK, LSE_CLOCK or HSE_CLOCK.
  // By default the LSI is selected as source.
  // LSE = 32kHz clock (RTCCLK),  LSI = 40kHz CPU Clock (IWDGCLK), HSE_Clock = CPU Clock (PLLCLK/128)
  //----------------------------------------------------------------
  rtc_stm.setClockSource(STM32RTC::LSE_CLOCK);  // 32kHz source for RTC
  rtc_stm.begin(false);                         // initialize RTC 24H format
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  //  Sleep Mode Interrupt
  //----------------------------------------------------------------
  pinMode(WakeUp_Pin, INPUT_PULLDOWN);
  delay(10);

  LowPower.begin();
  pinMode(HX711_SCK[0], OUTPUT);
  digitalWrite (HX711_SCK[0], HIGH); //HX711 aus

  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);  // Power an

  pinMode(ESP_RESET, OUTPUT);
  digitalWrite(ESP_RESET, HIGH);  // ESP8266 an

  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {}; // Wait for MONITOR_RX_TX port open
  delay(1000); // wait for Monitor ready
  MONITOR_RX_TX.println(F("ESP 8266 Test Sketch 17.02.2024\n"));
  MONITOR_RX_TX.flush();

#if defined(ARDUINO_BLACKPILL_F411CE)
  debugprintln("STM32F411CE");
#else
#if defined(ARDUINO_BLACKPILL_F401CC)
  debugprintln("STM32F401CC");
#else
#if defined(ARDUINO_BLUEPILL_F103CB)
  debugprintln("STM32F103CB");
#else
#if defined(ARDUINO_BLUEPILL_F103C8)
  debugprintln("STM32F103C8");
#else
 #error "Board, Board-Part Number wrong!"
#endif
#endif
#endif
#endif

  MONITOR_RX_TX.println(F("Abfrage Modul ... "));
  if (my_esp.init(ESP_Baudrate)) {
    MONITOR_RX_TX.println("i.O.");
    my_esp.mode();      // Station Mode permanent
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.println(F("ESP Firmware : "));
    my_esp.firmware();  // firmware abfragen
    MONITOR_RX_TX.println(my_esp.buffer);
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.flush();
    WLan_Info();
  }
  else {
    OK = false;
    MONITOR_RX_TX.println(F("Modul konnte nicht abgefragt werden"));
    MONITOR_RX_TX.println(F("  Konfiguration 9600Bd ?"));
    MONITOR_RX_TX.println(my_esp.buffer);
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.flush();
    ESP_Abschaltung();
  }

  strcpy_P(parameter, pfad);

  char *p_buf = strstr(parameter, "/beelogger");  // search first /
  uint8_t n = atoi(p_buf + 10);
  //MONITOR_RX_TX.println(p_buf + 10);
  if (n == 0) {
    MONITOR_RX_TX.println(parameter);
    MONITOR_RX_TX.println(F("\nWLan Test Sketch nur mit beeloggerX,  X = 1...n"));
    MONITOR_RX_TX.println(F("keine DUO, Triple, Quad, usw."));
    MONITOR_RX_TX.flush();
    OK = false;
    ESP_Abschaltung();
  }

  if (OK) {
    strcpy_P(parameter, Access_Point);
    MONITOR_RX_TX.print(F("Verbinde zum WLAN : "));
    MONITOR_RX_TX.print(parameter);
    MONITOR_RX_TX.println(" ... ");
    MONITOR_RX_TX.flush();
    strcpy_P(data, AP_Passwort);

    if (my_esp.join(parameter, data)) {
      MONITOR_RX_TX.println(F("WLAN Verbindung OK"));
      MONITOR_RX_TX.println("\n");
      MONITOR_RX_TX.flush();
    }
    else {
      OK = false;
      MONITOR_RX_TX.println(F("Verbindungsfehler!\n"));
      MONITOR_RX_TX.flush();
      ESP_Abschaltung();
    }
  }

  if (OK) {
    MONITOR_RX_TX.println(F("Verbinde mit Server ... "));
    strcpy_P(parameter, serverName);
    MONITOR_RX_TX.print(parameter);
    if (my_esp.Connect(parameter)) {
      MONITOR_RX_TX.println(F(" ... connect: OK\n"));
      MONITOR_RX_TX.flush();
    }
    else {
      OK = false;
      MONITOR_RX_TX.println(F(" connect fehlgeschlagen\n"));
      MONITOR_RX_TX.println(my_esp.buffer);
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();
      ESP_Abschaltung();
    }
  }
}




//#########################################
void loop() {
  char KonvertChar[15];
  char data[80];
  char parameter[64];

  if ((!first_run) && (OK)) {
    MONITOR_RX_TX.print("ESP ... ");
    MONITOR_RX_TX.flush();
    if (my_esp.init(ESP_Baudrate)) {  // baud rate
      MONITOR_RX_TX.println("o.k.");
      MONITOR_RX_TX.flush();
      strcpy_P(parameter, Access_Point);
      strcpy_P(data, AP_Passwort);
      if (my_esp.join(parameter, data)) {
        MONITOR_RX_TX.println("..");
        MONITOR_RX_TX.flush();
        strcpy_P(parameter, serverName);
        if (my_esp.Connect(parameter)) {
          MONITOR_RX_TX.println(".");
          MONITOR_RX_TX.flush();
          OK = true;
        }
      }
    }
  }
  else  first_run = 0;

  if (OK) {

    float Check = round(Service + TempIn + TempOut + FeuchteIn + FeuchteOut + Licht + Gewicht + Solarspannung + Batteriespannung);

    if (my_esp.prep_send(300)) { // fiktive Länge, senden wird mit 0x00 gestartet
      MONITOR_RX_TX.println(F("Uebertrage Daten zum Webserver ..."));
      MONITOR_RX_TX.flush();

      //"GET " + url + php + " ? " + Daten + " HTTP / 1.1\r\n" + "Host : " + host + "\r\n" + "Connection : close\r\n\r\n");
      // Wichtig: der String darf von der url bis Ende der Daten keine Leerzeichen enthalten

      strcpy_P(parameter, pfad);
      MONITOR_RX_TX.println(parameter);
      MONITOR_RX_TX.flush();
      my_esp.send(parameter);

      // erstelle Daten
      my_esp.send("Passwort=");
      strcpy_P(parameter, Passwort);
      my_esp.send(parameter);
      my_esp.send("&Z=2");
      my_esp.send("&TempIn=");     dtostrf(TempIn, 4, 2, KonvertChar);     my_esp.send(KonvertChar);
      my_esp.send("&TempOut=");    dtostrf(TempOut, 4, 2, KonvertChar);    my_esp.send(KonvertChar);
      my_esp.send("&FeuchteIn=");  dtostrf(FeuchteIn, 4, 2, KonvertChar);  my_esp.send(KonvertChar);
      my_esp.send("&FeuchteOut="); dtostrf(FeuchteOut, 4, 2, KonvertChar); my_esp.send(KonvertChar);
      my_esp.send("&Licht=");      dtostrf(Licht, 4, 2, KonvertChar);      my_esp.send(KonvertChar);
      my_esp.send("&Gewicht=");    dtostrf(Gewicht, 1, 1, KonvertChar);    my_esp.send(KonvertChar);

      my_esp.send("&VBatt=");      dtostrf(Batteriespannung, 4, 2, KonvertChar); my_esp.send(KonvertChar);
      my_esp.send("&VSolar=");     dtostrf(Solarspannung, 4, 2, KonvertChar);    my_esp.send(KonvertChar);

      my_esp.send("&S=");          dtostrf(Service, 4, 2, KonvertChar);      my_esp.send(KonvertChar);
      my_esp.send("&Check=");      dtostrf(Check, 6, 2, KonvertChar);        my_esp.send(KonvertChar);
      // ende Daten
      //
      strcpy_P(parameter, Str_Http);
      my_esp.send( parameter);
      my_esp.send("Host: "); // Leerzeichen nach :
      strcpy_P(parameter, serverName);
      my_esp.send( parameter);
      my_esp.send("\r\n");
      strcpy_P(parameter, Str_Con);
      my_esp.send( parameter);
      my_esp.send(0x00); // Startkommando senden

      OK = false;
      /*
        if (my_esp.sendCommand(0, 500, "SEND OK")) {
        MONITOR_RX_TX.println(F("SEND OK"));
        }
      */
      if (my_esp.sendCommand(0, 2000, "200 OK")) {
        MONITOR_RX_TX.println(F("HTTP OK"));
        if (my_esp.sendCommand(0, 5000, "ok *")) {
          OK = true;

          MONITOR_RX_TX.print(my_esp.buffer);
          MONITOR_RX_TX.println(" ");
          MONITOR_RX_TX.flush();

          char *p_buf = strstr(my_esp.buffer, "ok *");  // search string start
          uint8_t pos = 0;
          *p_buf = 0; //Stringende
          do {
            p_buf --;    // search start of line
            pos++;
          }
          while ( (!(*p_buf == '\n')) && (pos < 20));

          int n;
          if (char *p_bf = strchr(p_buf, 'T') ) {
            n = atoi(p_bf + 1);  // Konvertiere str in int

            char x = *(p_buf + 1);
            if ((x > 0x2F) && (x < 0x3A)) { // first char a number?
              long l_tm = atol(p_buf);
              uint32_t epochTime = (uint32_t)l_tm ;
              rtc_stm.setEpoch(epochTime);
              MONITOR_RX_TX.println("\nUhrzeit vom Server: ");
              display_time();
            }
          }
          else {
            n = atoi(p_buf); // Konvertiere str in int
          }
          if (n > 0) {
            MONITOR_RX_TX.print("/nIntervallvorgabe vom Server :  ");
            MONITOR_RX_TX.print(n);
            MONITOR_RX_TX.println(" Minuten ");
          }
          else {
            MONITOR_RX_TX.print("Kein Intervall vom Server vorgegeben. ");
            MONITOR_RX_TX.println(my_esp.buffer);
            MONITOR_RX_TX.println(" ");
            MONITOR_RX_TX.println(n);
          }
          MONITOR_RX_TX.print("Vorgabe Sendezyklus vom Server :  ");
          uint8_t std = 0;
          if (strchr(p_buf, 'A') ) {
            std = 1; MONITOR_RX_TX.print(" eine ");
          }
          else if (strchr(p_buf, 'B') ) {
            std = 1; MONITOR_RX_TX.print(" zwei ");
          }
          else if (strchr(p_buf, 'C') ) {
            std = 1; MONITOR_RX_TX.print(" vier ");
          }
          else if (strchr(p_buf, 'D') ) {
            std = 1; MONITOR_RX_TX.print(" acht ");
          }
          if (std) {
            MONITOR_RX_TX.println(" Stunde(n)");
          } else {
            MONITOR_RX_TX.print(" keine ");
          }
          MONITOR_RX_TX.println(" ");
          MONITOR_RX_TX.println(F("\nÜbertragung erfolgreich"));
          MONITOR_RX_TX.println(" ");
        }
        else {
          MONITOR_RX_TX.println(F("Quittung Fehler!"));
          MONITOR_RX_TX.println(my_esp.buffer);
          OK = false;
        }
      }
      else {
        MONITOR_RX_TX.println(F("\nFehler in : "));
        strcpy_P(parameter, serverName);
        MONITOR_RX_TX.println(parameter);
        strcpy_P(parameter, pfad);
        MONITOR_RX_TX.println(parameter);
        MONITOR_RX_TX.flush();
      }
      if (my_esp.quit()) {
        MONITOR_RX_TX.println(F("."));
      }
      else {
        OK = false;
      }
      MONITOR_RX_TX.flush();
      delay(2000);
    } // prep_send

    if (OK) {
      // Test-Messdaten um 1 erhöhen
      TempIn++;
      TempOut++;
      TempOut++;
      FeuchteIn++;
      FeuchteOut++;
      FeuchteOut++;
      Licht++;
      Gewicht++;
      Batteriespannung++;
      Solarspannung++;
      Service = 1.0;
      TempOut = TempOut - 3.0;
      FeuchteIn = FeuchteIn - 2.0;

      // Abschaltung nach 5 Datenübertragungen
      if (TempIn >= 27.0) {
        OK = false;
        ESP_Abschaltung();
      }
      MONITOR_RX_TX.println(F(" ... Sendewiederholung in 5 Minuten"));
      MONITOR_RX_TX.flush();
      for (int i = 0; i < 300; i++) {
        delay(1000);
      }
    }
    else {
      MONITOR_RX_TX.println(F("Fehler im Test!"));
      ESP_Abschaltung();
    }
  }
}
//#########################################

void ESP_Abschaltung() {

  MONITOR_RX_TX.println(F("Test beendet."));
  MONITOR_RX_TX.println(" ");
  my_esp.disConnect();
  my_esp.quit();
  delay(100);
  my_esp.end();
  MONITOR_RX_TX.end();
  digitalWrite (HX711_SCK[0], LOW); //HX711 aus
  digitalWrite(Power_Pin, LOW);  // Power aus
  digitalWrite(ESP_RESET, LOW);  // ESP8266 aus
  delay(50);
  LowPower.sleep(0);      // Serial connection to Arduino IDE still active
}
//----------------------------------------
void WLan_Info() {
  uint8_t st;
  MONITOR_RX_TX.println(F("WLAN Information : \n"));
  MONITOR_RX_TX.flush();
  st = my_esp.espState;
  my_esp.espState = ESP_CONNECTED;
  my_esp.send("AT+CWLAP\r\n");
  read_ser(10000);
  //my_esp.check(10000);
  my_esp.espState = st;
  MONITOR_RX_TX.println("\n");
  MONITOR_RX_TX.flush();

  strcpy_P(parameter, Access_Point);
  MONITOR_RX_TX.print(parameter);
  MONITOR_RX_TX.print(F("   Signalstaerke : "));
  MONITOR_RX_TX.flush();
  if (my_esp.signal(parameter)) {         // hole Signalstärke
    strcpy(parameter, my_esp.buffer);
    char cc = ',';
    char *pos = strchr(parameter, cc); // suche ','
    if (pos) {
      int svc = atoi(pos + 1);
      if ((svc > -10)) {
        MONITOR_RX_TX.println(F("\n###########################"));
        MONITOR_RX_TX.println(F("Signalstärke unklar : "));
      }
      if ((svc < -88)) {
        MONITOR_RX_TX.println(F("\n###########################"));
        MONITOR_RX_TX.println(F("Signal schwach : "));
      }
      Service = (float)svc;
      MONITOR_RX_TX.print(svc);
      MONITOR_RX_TX.println(F(" dBm\n"));
      MONITOR_RX_TX.flush();
    }
  }
  else {
    MONITOR_RX_TX.println(F("\n###########################"));
    MONITOR_RX_TX.println(F("Signalabfrage Fehler"));
  }
  delay(1000);
}
//----------------------------------------

void read_ser(unsigned long timeout) {
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
}


/**
  @brief Funktion display_time() Anzeige Uhrzeit als Debug Info
  @param  none
  @return  none
*/
void display_time() {
#if myDEBUG
  char buff[64];
  time_t wakeup_time = rtc_stm.getEpoch();
  strftime (buff, 60, "%d.%m.%Y %H:%M:%S", localtime (&wakeup_time));
  debugprintF("Datum:    "); debugprintln(buff);
#endif
}
//----------------------------------------------------------------
