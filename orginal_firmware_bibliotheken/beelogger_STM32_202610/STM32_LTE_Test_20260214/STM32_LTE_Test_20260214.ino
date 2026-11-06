/*
   (C) 2025 R.Schick / Thorsten Gurzan - beelogger.de

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


//----------------------------------------------------------------
// Allgemeine Konfiguration
//----------------------------------------------------------------

// LTE Konfiguration

// Name des Zugangspunktes des Netzwerkproviders
const char APN[] PROGMEM = {"APN"};
static char APN_Benutzer[] = {""}; // Benutzername des Zugangspunktes
static char APN_Passwort[] = {""}; // Passwort des Zugangspunktes


//Beispiele:
//const char  APN[] PROGMEM ={"iot.1nce.net"};
//static char APN_Benutzer[] = {""}; // 1nce ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM = {"internet.t-mobile"};
//static char APN_Benutzer[] = {"t-mobile"};
//static char  APN[] PROGMEM = {"TM"}; //Thingsmobile

//const char  APN[] PROGMEM ={"web.vodafone.de"};
//static char APN_Benutzer[] = {""}; // vodafone ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM = {"internet.eplus.de"};
//static char APN_Benutzer[] = {"eplus"};
//static char APN_Passwort[] = {"eplus"};


// Domainname zum Webserver mit beelogger-Skript
//const char serverName[] PROGMEM = {"meineDomain.de"};
const char serverName[] PROGMEM = {"community.beelogger.de"};

// Verzeichnis auf dem Webserver mit PHP-Skript für den jeweiligen beelogger
//const char beelogger_pfad[] PROGMEM = {"GET /Account/beelogger1//beelogger_log.php?"};
const char beelogger_pfad[] PROGMEM = {"GET /USERX/beeloggerY/beelogger_log.php?"};  // USERX und Y anpassen

// Passwort vom Webserver-Skript
//const char Passwort[] PROGMEM = {"meinPasswort"};
const char Passwort[] PROGMEM = {"Log"};  // "Log" = default testsystem beelogger

//----------------------------------------------------------------

// SIM76xx / A76xx Modul spezifische Parameter
#define A_7600         0  // 1 = A_7600E

//----------------------------------------------------------------
// Ende Konfiguration
//----------------------------------------------------------------



/****************************************************************/
// SIM7600E - LTE
/****************************************************************/

char ID_ID[] = "STM32_LTE_TEST_260214";

#include "SIM76_stm_beelogger.h"
CLTE_SIM76_STM dfue;  // the LTE interface
/****************************************************************/

//----------------------------------------------------------------
// STM32  RTC, Low-Power
//----------------------------------------------------------------
#define VAlternativ 3.9
#include "STM32_beelogger.h"
#include <STM32LowPower.h>
#include <STM32RTC.h>
#include "time.h"
/*Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();
//----------------------------------------------------------------


//----------------------------------------------------------------
// Variablen
//----------------------------------------------------------------
const float No_Val = 99.9f;  // Vorbelegung, Wert nicht gemessen
const float No_Value = -1.0f;  // Vorbelegung, Wert nicht gemessen

// Test-Messdaten (regulär kommen die Messdaten von den Sensoren)
float SensorTemp[2] = {25.0, 35.0};
float SensorFeuchte[2] = {40.0, 60.0};
float Licht = 5000;
float Gewicht[2] = {2.5, No_Value};
float Batteriespannung = 4.7;
float Solarspannung = 1.7;
float Service = 0.0;
float Aux[3] = {1.0, 2.0, 3.0};
#define Aussenwerte 0
#define Beute1      1

bool OK = true;
uint8_t Verbindungsversuche = 0;

char DatenString[100];  // Datenbuffer
int call_len = 0;

//----------------------------------------------------------------
// Parameter Datenversand via Http // diese nicht modifizieren
//----------------------------------------------------------------
const char Str_Http[]   PROGMEM = " HTTP/1.1\r\n"; // mit Leerzeichen vorne
const char Str_Con_alv[] PROGMEM = "Connection: close\r\n\r\n";
const char Str_Pw[]      PROGMEM = {"PW="};
const char Str_Id[]      PROGMEM = {"&Z=2&ID="};
const char Str_MData[]   PROGMEM = {"&M1="};
//----------------------------------------------------------------


void setup() {
  char parameter[80];

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

  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {}; // Wait for MONITOR_RX_TX port open
  delay(1000); // wait for Monitor ready
  MONITOR_RX_TX.println(ID_ID);
  MONITOR_RX_TX.flush();

#if defined(ARDUINO_BLACKPILL_F411CE)
  debugprintln("STM32F411CE");
#else
#if defined(ARDUINO_BLACKPILL_F401CC)
  debugprintln("STM32F401CC");
#else
#if defined(ARDUINO_BLACKPILL_F401CE)
  debugprintln("STM32F401CE");
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
#endif


  strcpy_P(parameter, beelogger_pfad);
  char *p_buf = strstr(parameter, "/beelogger");  // search first /
  uint8_t n = atoi(p_buf + 10);
  //MONITOR_RX_TX.println(p_buf + 10);
  if (n == 0) {
    MONITOR_RX_TX.println(parameter);
    MONITOR_RX_TX.println(F("\nWLan Test Sketch nur mit beeloggerX,  X = 1...n"));
    MONITOR_RX_TX.println(F("keine DUO, Triple, Quad, usw."));
    MONITOR_RX_TX.flush();
    OK = false;
  }
  strcpy_P(parameter, APN);

  if (strcmp(parameter, "APN") == 0) {
    MONITOR_RX_TX.print(F(" Netzwerk ... "));
    MONITOR_RX_TX.print(parameter);
    MONITOR_RX_TX.print(" ... ");    MONITOR_RX_TX.println(" ... ");
    MONITOR_RX_TX.println(F("Bitte Provider konfigurieren!"));
    MONITOR_RX_TX.flush();
    OK = false;
  }

  if (OK) { // pfad / APN  ok
    MONITOR_RX_TX.println(F("Initialisiere Modul ... "));
    MONITOR_RX_TX.flush();
    pinMode(Power_Pin, OUTPUT);
    digitalWrite(Power_Pin, HIGH);  // Power an
    delay(200);
    pinMode(SIM_Power_Pin, OUTPUT);
    digitalWrite(SIM_Power_Pin, HIGH);  // SIM7600 an

    if (dfue.init()) {
      MONITOR_RX_TX.println("Init OK");
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();
    }
    else {
      MONITOR_RX_TX.println(F("SIM7600E Modul konnte nicht initialisiert werden"));
      MONITOR_RX_TX.println(F("  SIM Karte ?"));
      MONITOR_RX_TX.println(F("  Konfiguration 9600Bd ?"));
      MONITOR_RX_TX.println(F("  Akkuspannung kontrollieren"));
      MONITOR_RX_TX.println(dfue.buffer);
      MONITOR_RX_TX.flush();

      if ( dfue.sendCommand("AT") == 0) {
        MONITOR_RX_TX.println(F("  Fehler in SIM7600E RX / TX Verbindung ? "));
      }
      else {
        dfue.sendCommand("AT+SIMCOMATI");
        MONITOR_RX_TX.println(dfue.buffer);
        MONITOR_RX_TX.println(" ");
      }

      MONITOR_RX_TX.println(" ");
      OK = false;
      LTE_Abschaltung();
    }
  }
  if (OK) { // init ok
    delay (3000);
    strcpy_P(parameter, APN);
    MONITOR_RX_TX.print(F("Verbinde zum Netzwerk ... "));
    MONITOR_RX_TX.print(parameter);
    MONITOR_RX_TX.print(" ... ");
    if (dfue.tcp_start(parameter, APN_Benutzer, APN_Passwort, A_7600) == 0) {
      MONITOR_RX_TX.println("Verbindung zum Netz OK");
      MONITOR_RX_TX.println(" ");

    } else {
      OK = false;
      MONITOR_RX_TX.println(F("Verbindung zum Netz konnte nicht aufgebaut werden"));
      MONITOR_RX_TX.println(" ");
      LTE_Abschaltung();
      while (1) {
        delay(100);
      };
    }
  }
}


int count = 0;
//######################################
void loop() {

  if (OK) {
    send_data_tcp();
  }  // if (OK)
}  // loop


void LTE_Abschaltung() {
  dfue.stop();
  MONITOR_RX_TX.println(" ");
  dfue.shutdown();
  digitalWrite(SIM_Power_Pin, LOW);
  digitalWrite(Power_Pin, LOW);
  MONITOR_RX_TX.println(F("LTE off"));
  MONITOR_RX_TX.println();
}


//####################################
void send_data_tcp() {
  char parameter[64];  // data  http call

  strcpy_P(parameter, serverName);
  MONITOR_RX_TX.print(F("Verbinde zum Server: "));
  MONITOR_RX_TX.print(parameter);
  MONITOR_RX_TX.println(" ... ");
  MONITOR_RX_TX.flush();
  if (dfue.Connect(parameter) == 0) {  // Start TCP
    MONITOR_RX_TX.println(F("\n\rConnect Fehler"));
    OK = false;
  }
  else {
    MONITOR_RX_TX.println(F(" verbunden.\n"));

    if (dfue.prep_send()) {  // Open Server connection
      DatenString_erstellen();

      strcpy_P(parameter, beelogger_pfad); dfue.send(parameter); //GET ...
      MONITOR_RX_TX.println(parameter); MONITOR_RX_TX.flush();

      strcpy_P(parameter, Str_Pw); dfue.send(parameter); strcpy_P(parameter, Passwort); dfue.send(parameter);
      strcpy_P(parameter, Str_Id); dfue.send(parameter);
      MONITOR_RX_TX.print(parameter); MONITOR_RX_TX.flush();

      strcpy(parameter, ID_ID); dfue.send(parameter); // System ID
      MONITOR_RX_TX.print(parameter); MONITOR_RX_TX.flush();//Daten

      strcpy_P(parameter, Str_MData); dfue.send(parameter);
      MONITOR_RX_TX.print(parameter); MONITOR_RX_TX.flush();

      dfue.send(DatenString);  // Datensatz
      MONITOR_RX_TX.println(DatenString); MONITOR_RX_TX.flush();

      delay(50);

      strcpy_P(parameter, Str_Http); dfue.send(parameter);// HTTP/1.1
      dfue.send("Host: "); strcpy_P(parameter, serverName); dfue.send(parameter); dfue.send("\r\n");
      strcpy_P(parameter, Str_Con_alv); dfue.send(parameter);
      dfue.send(0x00); // Startkommando senden
      // http complete

      if (dfue.sendCommand(0, 10000, "ok *")) { // hat Server Anfrage quittiert ?
        OK = true; // success
        char *p_buf = strstr(dfue.buffer, "ok *");  // search string start
        byte cnt = 0;
        do {
          p_buf --;    // search start of line
          cnt++;
        }
        while ( (!(*p_buf == '\n')) && (cnt < 20));

        MONITOR_RX_TX.print(F("Quittung : "));
        MONITOR_RX_TX.println(p_buf);
        MONITOR_RX_TX.println(F("Test erfolgreich\n"));
        MONITOR_RX_TX.flush();

        // Format Quittung aus beelogger-log.php
        // echo "5ok * "  = 5 Minuten
        MONITOR_RX_TX.println(F("OK"));
      }
      else {
        MONITOR_RX_TX.println(F("Fehler!"));
      }
      MONITOR_RX_TX.flush();
      dfue.disConnect(); // cipclose

      delay(200);
      dfue.stop();  //netclose
    }
  } // prep_Send

  int Signal = dfue.getSignalQuality();
  if ((Signal > -10)) {
    MONITOR_RX_TX.println(F("Signalstärke unbrauchbar"));
  }
  if ((Signal <= -88)) {
    MONITOR_RX_TX.print(F("Signal schlecht : "));
    MONITOR_RX_TX.print(Signal);
    MONITOR_RX_TX.println(F(" dB"));
  }
  else {
    if (Signal) {
      MONITOR_RX_TX.print(F("Signalstaerke : "));
      MONITOR_RX_TX.print(Signal);
      MONITOR_RX_TX.println(F(" dB"));
    }
  }

  //  Ende
  dfue.sendCommand("AT+CPSI?");
  MONITOR_RX_TX.println(dfue.buffer);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();

  MONITOR_RX_TX.println(F("SIM7600 Modul Informationen : "));
  MONITOR_RX_TX.flush();
  dfue.sendCommand("AT+SIMCOMATI");
  MONITOR_RX_TX.println(dfue.buffer);
  MONITOR_RX_TX.println(" ");

  dfue.shutdown();
  digitalWrite( SIM_Power_Pin, LOW);
  MONITOR_RX_TX.println(F("Test beendet"));
  OK = false;
}
//####################################

/**
  @brief Funktion DatenString_erstellen()
  erstellt konfigurationsabhängigen Datensatz zum abspeichern im EE-Prom

  @param  none,        globale Variablen
  @return  none
*/
#include <Sodaq_DS3231.h>
void DatenString_erstellen() {

  int count = 0;
  DateTime jetzt = DateTime(__DATE__, __TIME__);
  count = sprintf(DatenString, "%d/", jetzt.year());
  count += sprintf(DatenString + count, "%2.2d/", jetzt.month());
  count += sprintf(DatenString + count, "%2.2d_", jetzt.date());
  count += sprintf(DatenString + count, "%2.2d:", jetzt.hour());
  count += sprintf(DatenString + count, "%2.2d:", jetzt.minute());
  count += sprintf(DatenString + count, "%2.2d",  jetzt.second());

  count = Wert_hinzufuegen(count, SensorTemp[Beute1], 1, No_Val);    // Stocktemperatur 1
  count = Wert_hinzufuegen(count, SensorTemp[Aussenwerte], 1, No_Val);     // Außentemperatur
  count = Wert_hinzufuegen(count, SensorFeuchte[Beute1], 1, No_Value);     // Stockluftfeuchte 1
  count = Wert_hinzufuegen(count, SensorFeuchte[Aussenwerte], 1, No_Value);  // Außenluftfeuchte
  count = Wert_hinzufuegen(count, Licht, 1, No_Value);                 // Licht
  count = Wert_hinzufuegen(count, Gewicht[0], 2, No_Value);            // Gewicht 1

  count = Wert_hinzufuegen(count, Batteriespannung, 2, No_Value); // Akkuspannung
  count = Wert_hinzufuegen(count, Solarspannung, 2, No_Value);    // Solarspannung
  count = Wert_hinzufuegen(count, Service, 2, No_Value);    // Service
  count = Wert_hinzufuegen(count, Aux[1], 1, No_Value);  // Aux 1
  count = Wert_hinzufuegen(count, Aux[2], 2, No_Value);  // Aux 2
  count = Wert_hinzufuegen(count, Aux[0], 2, No_Value);  // Aux 0
  DatenString[count] = 0;
}


/**
  @brief Funktion Wert_hinzufuegen()
  erstellt Stringanteil für Datensatz

  @param
  count: aktuelle Position im String,
  Wert: Messwert,
  Nachkommastellen: Anzahl der Nachkommastellen für diesen Messwert
  Fehler: Vergleichswert für fehlerhaften oder unbenutzten Messwert
  @return  neue Position im Datenstring
*/
int Wert_hinzufuegen(int count, float Wert, uint8_t Nachkommastellen, float Fehler) {
  char Konvertierung[16];
  int count_neu = count;

  if (Wert == Fehler) {
    count_neu += sprintf(DatenString + count, ",%s", "");
  } else {
    dtostrf(Wert, 1, Nachkommastellen, Konvertierung);
    count_neu += sprintf(DatenString + count, ",%s", Konvertierung);
  }
  return count_neu;
}



void read_ser(unsigned long timeout) {
  extern HardwareSerial SIM_SERIAL;
  unsigned long t = millis();
  char c;
  uint8_t f = 0;
  do {
    if (SIM_SERIAL.available()) {
      c = SIM_SERIAL.read();
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
