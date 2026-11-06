/****************************************************************/
// Funktion ESP8266 WLAN   TCP / CIP-Send  EE-Prom
/****************************************************************/
// 08.04.2020 Zugriff AccessPoint List geändert
// 22.04.2020 STM32 adaption
// 02.01.2022 fix EE-Prom errorhandling
// 16.03.2022 fix Ram Use of Wire
// 09.12.2022 force signalquality

/****************************************************************/
// Konfiguration ESP-8266-01 WLAN
/****************************************************************/

#include <ESP_STM_beelogger.h>
C_WLAN_ESP01 dfue;

#define SERVER_TM_OUT 10000 // 10000 = 10 sec
/****************************************************************/

/****************************************************************/
// Parameter Datenversand via Http // diese nicht modifizieren
/****************************************************************/
const char Str_Con_cls[] PROGMEM = "Connection: close\r\n\r\n";

#if 0
const char Str_Http[]    PROGMEM = " HTTP/1.1\r\n";
const char Str_Pw[]      PROGMEM = {"PW="};
const char Str_Id[]      PROGMEM = {"&ID="};
const char Str_Opt[]     PROGMEM = {"&Z=2&A=1"};
const char Str_Serv[]    PROGMEM = {"&S="};
const char Str_ServX[]   PROGMEM = {"&SX="};
const char Str_MData[]   PROGMEM = {"&Mx_Data="};   // Parameter Multi Data  Senden
#endif
/****************************************************************/


/**
  @brief Utility functions
  stop_dfue_device(): ESP8266 Reset aktiv

  @param  none, globale Variablen
  @return  none
*/
void stop_dfue_device() {
  pinMode(ESP_RESET, OUTPUT);
  digitalWrite(ESP_RESET, LOW);    // ESP8266 "off"
}


/**
  @brief Funktion ESP8266 Setup_WLAN()
  initialisere ESP8266,
  Verbinde zum WLAN AP

  @param  none, globale Variablen
  @return  number of Access Point used
*/
uint8_t Setup_WLAN() {
  char parameter[80], data[32];
  uint8_t AP_Nr = 0;

  digitalWrite(ESP_RESET, HIGH);
  delay(500);

  debugprintlnF("ESP?");
  debugflush();

  for ( int x = 0; x < Anzahl_AP; x++) {
    if (dfue.init(ESP_Baudrate)) {
      if (x == 1) {
        strcpy_P(parameter, Access_Point2);
        strcpy_P(data, AP_Passwort_2);
      }
      else {
        strcpy_P(parameter, Access_Point1);
        strcpy_P(data, AP_Passwort_1);
      }

      debugprintlnF("Init!");
      debugprintln(parameter);
      debugflush();

      if (dfue.join(parameter, data, 10000)) { // WLAN verbinden
        //dfue.sleep(0); // 0 = NoSleep, 2 = Modem Sleep

        debugprintlnF("WLAN!");

        AP_Nr = x + 1; // AP_nr merken
        break;
      }
      else {
        delay(500);
        dfue.quit();

        debugprintlnF("Fehler");

      }
    }
  }
  return (AP_Nr);
}


/**
  @brief Funktion ESP8266 Send_data()
  verbinde zum Server, lese Daten aus EE-Prom,
  übertrage Daten, Server-Quittung auswerten
  Fehlerbehandlung bei der Übertragung
  Setze aus Serverquittung:
  - Zeit in DS3231
  - Messintervall
  - Sendeintervall

  @param  count:: Anzahl Datensätze zu übertragen, globale Variablen
  @return  senden erfolgreich > 0
*/
uint8_t Send_Data(uint8_t count) {
  char parameter[80];
  uint8_t additonal_Data = 0; // erzeuge zusätzlichen Datensatz
  uint8_t i = 0, s_cnt = 0;          // send counter
  uint8_t AP_Nr;

  uint8_t x, y;       // Datensatz zaehler

  uint8_t sum, test, first; // EE-Prom Checksumme
  char chk[2];

  uint16_t pos;
  uint8_t data_send;    // send status
  uint8_t retry;        // retry counter

  AP_Nr = Setup_WLAN();
  if (AP_Nr != 0) { // Connect to access point

    strcpy_P(parameter, ESP_STATUS);
    while ( i < 5 ) { // WLAN abfragen
      if (dfue.sendCommand(parameter, 3000, "S:2")) {

        debugprintlnF("WLAN*");
        debugflush();

        dfue.espState = ESP_WLAN_READY;
        break;
      }

      debugprintF("WLan?");

      delay(2000);
      i++;
    }

    // user action, force time_on or signal quality
    if (((report_info == 1) && (Service < 90.0)) || (Service == 999.0)) {
      delay(100);
      if (AP_Nr == 2) {
        strcpy_P(parameter, Access_Point2);
      }
      else {
        strcpy_P(parameter, Access_Point1);
      }
      if (dfue.signal(parameter)) {         // hole Signalstärke
        strcpy(parameter, dfue.buffer);
        char cc = ',';
        char *pos = strchr(parameter, cc); // suche ','
        if (pos) {
          int pegel = atoi(pos + 1);
          Service = (float) pegel;
          additonal_Data = 1;
        }
        debugprintF("WLAN [dBm]: ");
        debugprintln(Service);
        debugflush();
      }
    }
    else {
      if (Service == 99.0) {
        additonal_Data = 1;
      }
    }

    AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);


    //*************************
    data_send = 0;
    retry = 0;
    do {                   // Block send loop
      do {                 // Connect and send loop
        data_send = 0;
        strcpy_P(parameter, serverName);
        debugprintln(parameter);
        debugflush();
        if (dfue.Connect(parameter)) { // Server TCP session verbinden

          debugprintlnF(" Connect");
          debugflush();

          int send_size = (_Send_Sets + 2) * _Daten_Satz_Len;
          if (dfue.prep_send(send_size)) { // fiktive Länge, senden wird mit \0 gestartet
            strcpy_P(parameter, beelogger_pfad); // der GET in einen Stück bis zum '?', sonst geht es nicht !!!
            debugprintln(parameter);
            debugflush();
            dfue.send(parameter);
            strcpy_P(parameter, Str_Pw);           // erstelle Daten
            dfue.send(parameter);
            strcpy_P(parameter, Passwort);
            dfue.send(parameter);
            strcpy_P(parameter, Str_Opt);         //
            dfue.send(parameter);
            // System ID
            strcpy_P(parameter, Str_Id);
            dfue.send(parameter);
            strcpy(parameter, ID_ID);
            dfue.send(parameter);

            //Daten
            y = 0; first = 1;
#if 1
            do {
              // lese von AT24Cxx
              test = 0;
              do {
                pos = (s_cnt + y) * _Daten_Satz_Len;
                my_EEPROM.ReadStr(pos, DatenString, _Daten_Satz_Len - 2);
                DatenString[(_Daten_Satz_Len - 2)] = 0;  // String ende erzwingen
                sum = 0;
                for (x = 0; x < strlen(DatenString); x++) { // EE_Prom Checksum test
                  sum += DatenString[x];
                }
                pos = pos + _Daten_Satz_Len - 1;
                my_EEPROM.ReadStr(pos, chk, 1);
                x = (uint8_t)chk[0];
                test++;
              } while ((test < 2) && ( sum != x)); // zwei Leseversuche

              if (sum == x) {           // checksumme i.o.
                if (first == 1) {       // einfügen Satztrenner
                  first = 0;
                  strcpy_P(parameter, Str_MData);
                  char nmr = Anzahl_Sensoren_Gewicht + 0x30; // itoa
                  parameter[2] = nmr;  // set number in string
                  dfue.send(parameter);

                  debugprint(parameter);
                }
                else                 {
                  dfue.send(",");
                }
                dfue.send(DatenString);
                delay(50);
                debugprintln(DatenString);
                debugflush();

              }
              else {                    // Datensatz auslassen
                DatenString[0] = 0;
                debugprintF("EE?");
                debugprintln(pos);
                debugflush();
              }
              y++;

            } while ( (y < _Send_Sets) && ((s_cnt + y) < count) );
#else
            strcpy_P(parameter, Str_MData);
            char nmr = Anzahl_Sensoren_Gewicht + 0x30; // itoa
            parameter[2] = nmr;  // set number in string
            dfue.send(parameter);

            debugprint(parameter);
            dfue.send(DatenString);
            delay(50);
            debugprintln(DatenString);
            debugflush();
#endif

            if ( (additonal_Data) && ((s_cnt + y) == count) ) { // all done, Service ?
              char service_data[16];
              int count = sprintf(DatenString, "%s", "&S=");
              dtostrf(Service, 1, 6, service_data);
              count += sprintf(DatenString + count, "%s", service_data);
              dfue.send(DatenString);

              debugprintln(DatenString);
              debugflush();
            }
            // ende Daten
            strcpy_P(parameter, Str_Http);
            dfue.send(parameter);
            strcpy_P(parameter, serverName);
            dfue.send("Host: ");
            dfue.send(parameter);
            dfue.send("\r\n");
            strcpy_P(parameter, Str_Con_cls);
            dfue.send(parameter);
            dfue.send(0x00); // Startkommando senden

            debugprintlnF("http");
            debugflush();

            if (dfue.sendCommand(0, SERVER_TM_OUT, "ok *")) { // hat Server Anfrage quittiert ?
              data_send = 1;               // success
              retry = 0;
              s_cnt += _Send_Sets;         // have been send

              char *p_buf = strstr(dfue.buffer, "ok *");  // search string start
              *p_buf = 0;  // Stringende setzen
              uint8_t pos = 0;
              do {
                p_buf --;    // search start of line
                pos++;
              }
              while ( (!(*p_buf == '\n')) && (pos < 20));

              debugprintF(" Q: "); debugprintln(p_buf); debugflush();

              int n;
              if (char *p_bf = strchr(p_buf, 'T') ) {
                n = atoi(p_bf + 1);  // Konvertiere str in int

                char x = *(p_buf + 1);
                if ((x > 0x2F) && (x < 0x3A)) { // first char a number?
                  long l_tm = atol(p_buf);
                  uint32_t epochTime = (uint32_t)l_tm ;
                  rtc_stm.setEpoch(epochTime);
                }
              }
              else {
                n = atoi(p_buf); // Konvertiere str in int
              }
              if ((n > 4) && (n <= 60)) {
                WeckIntervallMinuten = (uint8_t)n;
              }

              report_info = 0;
              if (strchr(p_buf, 'P') ) {
                report_info = 1;
              }
              if (strchr(p_buf, 'A') ) {
                send_cycle = SEND_CYC_A;
              }
              else if (strchr(p_buf, 'B') ) {
                send_cycle = SEND_CYC_B;
              }
              else if (strchr(p_buf, 'C') ) {
                send_cycle = SEND_CYC_C;
              }
              else if (strchr(p_buf, 'D') ) {
                send_cycle = SEND_CYC_D;
              }
            }
            // received ok *
          }
          // Prep Send
        }
        // Connect
        if (data_send == 0) {  // failed, terminate http session
          retry++;
          dfue.disConnect();

          debugprintF("Retry: ");
          debugprint(retry);

          delay(3000);         // wait
        }
      } while ((retry < 3) && (data_send == 0));         // retry TCP Connect and send
    } while ((s_cnt < count) && (data_send == 1));  // while my_counter
    my_EEPROM.end();
    //*************************

    if (dfue.quit()) {     // Wlan trennen, Restart ESP
      //      debugprintln(F("end!"));
      //      debugflush();
    }
#if myDEBUG
    if (s_cnt > count) s_cnt = count;
#endif
    debugprintF("\nSend: ");  debugprintln(s_cnt); debugflush();
  }
  else {
    s_cnt = 255;
  }

  delay(200);
  dfue.end();
  delay(100);
  digitalWrite(ESP_RESET, LOW);
  return (s_cnt);  // return Sets send, 0 = failed,
}
