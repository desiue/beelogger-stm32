/****************************************************************/
// Funktion SIM7600E - Mobilfunk  TCP / SSL-Send  EE-Prom
/****************************************************************/
// 02.01.2022 fix EE-Prom errorhandling
// 16.03.2022 fix Ram Use of Wire
// 09.12.2022 force signalquality
// 28.09.2023 WLAN/LTE - System: SIM Power Pin: PB3, SIM-Serial: PB14/PB15
//              use special Lib-Files included in this Sketch


#if (_LTE_TLS == 1)

/****************************************************************/
// Kommunikation SIM7600E - Mobilfunk
/****************************************************************/
#include <SIM76_stm_beeloggerx.h>
CLTE_SIM76_STMx dfueLTE;  // the LTE interface

#define SERVER_TM_OUT 10000 // 10000 = 10 sec
/****************************************************************/


/****************************************************************/
// Parameter Datenversand via Http // diese nicht modifizieren
/****************************************************************/
const char Str_Http[]    PROGMEM = " HTTP/1.1\r\n"; // ein Leerzeichen vor HTTP
const char Str_Con_alv[] PROGMEM = "Connection: keep-alive\r\n";

const char Str_Pw[]      PROGMEM = {"PW="};
const char Str_Opt[]     PROGMEM = {"&Z=2&A=1"};
const char Str_Id[]      PROGMEM = {"&ID="};
const char Str_Serv[]    PROGMEM = {"&S="};
const char Str_ServX[]   PROGMEM = {"&SX="};
const char Str_MData[]   PROGMEM = {"&Mx_Data="};  // Parameter Mutli Data  Senden
/****************************************************************/


/**
  @brief Utility functions
  stop_DFUe_device(): SIM7600E Reset aktiv oder Power inaktiv

  @param  none, globale Variablen
  @return  none
*/
void stop_dfue_deviceLTE() {
  pinMode(SIM_Power_Pin, OUTPUT);
  digitalWrite(SIM_Power_Pin, LOW);    // SIM-Modul "off"
}


/**
  @brief Funktion SIM7600E Send_Data()
  initialisiere SIM7600E, vebinde ins Mobilfunknetz
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
uint8_t Send_Data_LTE(uint8_t count) {
  char parameter[80];
  uint8_t s_cnt = 0;            // send counter
  uint8_t additonal_Data = 0;
  float ServiceX = No_Value;

  uint8_t x, y;       // Datensatz zaehler

  uint8_t sum, test, first; // EE-Prom Checksumme
  char chk[2];

  uint16_t pos;
  uint8_t data_send;    // send status
  uint8_t retry;        // retry counter

  debugprintlnF("LTEon");
  debugflush();

  digitalWrite(SIM_Power_Pin, HIGH);
  delay(4000); // Important, wait for SIM7600coming up

  if (dfueLTE.init(6000)) {  // Init SIM7600 Modul
    dfueLTE.sendCommand("AT");
    debugprintlnF("Init!");
    debugflush();

#if StandortBestimmung
    if ((report_info == 3) && (Service < 90.0)) {
      //debugprintlnF("Lat,Lon ?");
      //debugflush();
      GSM_LOCATION Location;
      Location.lat = 0.0;
      Location.lon = 0.0;
      if (dfueLTE.getLocation(&Location)) {
        Service = Location.lat;
        ServiceX = Location.lon;
        /*
          debugprint(Location.lat);
          debugprintF(",");
          debugprint(Location.lon);
          debugprintln(" ");
        */
        additonal_Data = 1;
        debugprintln(dfueLTE.buffer);
        debugflush();
      }
    }
#endif

    strcpy_P(parameter, APN);
    debugprintln(parameter);
    debugflush();
    if (dfueLTE.start(parameter, APN_Benutzer, APN_Passwort) == 0) { // Start dfue Connection

      // user action, force time_on or signal quality
      if (((report_info == 1) && (Service < 90.0)) || (Service == 999.0)) {
        delay(100);
        int Signal = dfueLTE.getSignalQuality();
        Service = (float)Signal;
        additonal_Data = 1;

        debugprint(Signal);
        debugprintlnF(" dBm");
        debugflush();

      }
      else {
        if (Service == 99.0) {
          additonal_Data = 1;
        }
      }

      if (dfueLTE.ssl_init()) {  // Init SSL

        if (dfueLTE.ssl_start()) {  // Start SSL
          dfueLTE.sendCommand("AT");
          debugprintlnF("SSL!");
          debugflush();

          AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);


          //#################
          data_send = 0;    // send status
          retry = 0;        // retry counter
          do {  // retry loop
            do {  // Blockweise send loop
              data_send = 0;
              // always new connect; session alive not supported by server

              strcpy_P(parameter, serverName);
              debugprintln(parameter);
              debugflush();
              if (dfueLTE.ssl_open(parameter)) {  // Open Server connection

                strcpy_P(parameter, beelogger_pfad);    // GET ....
                debugprintln(parameter);
                debugflush();
                dfueLTE.ssl_data(parameter);
                strcpy_P(parameter, Str_Pw); dfueLTE.ssl_data(parameter); strcpy_P(parameter, Passwort); dfueLTE.ssl_data(parameter);
                strcpy_P(parameter, Str_Opt); dfueLTE.ssl_data(parameter);
                // System ID
                strcpy_P(parameter, Str_Id); dfueLTE.ssl_data(parameter); strcpy(parameter, ID_ID); dfueLTE.ssl_data(parameter);
                delay(50);

                //Daten
                y = 0; first = 1;
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
                      char nmr = Anzahl_Sensoren_Gewicht + 0x30;  //itoa
                      parameter[2] = nmr;  // set number in string
                      dfueLTE.ssl_data(parameter);

                      debugprint(parameter);
                      debugflush();
                    }
                    else                 {
                      dfueLTE.ssl_data(",");
                    }
                    dfueLTE.ssl_data(DatenString);
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


                if ( (s_cnt + y) == count) { // all done, Service ?
                  if (additonal_Data) {
                    char service_data[16];
                    uint16_t count = sprintf(DatenString, "%s", "&S=");
                    dtostrf(Service, 1, 6, service_data);
                    count += sprintf(DatenString + count, "%s", service_data);
                    if (ServiceX != No_Value) {
                      count += sprintf(DatenString + count, "%s", "&SX=");
                      dtostrf(ServiceX, 1, 6, service_data);
                      sprintf(DatenString + count, "%s", service_data);
                    }
                    dfueLTE.ssl_data(DatenString);
                    delay(50);
                    debugprintln(DatenString);
                    debugflush();
                  }
                }
                // ende Daten
                strcpy_P(parameter, Str_Http); dfueLTE.ssl_data(parameter); // HTTP/1.1
                dfueLTE.ssl_data("Host: "); strcpy_P(parameter, serverName); dfueLTE.ssl_data(parameter); dfueLTE.ssl_data("\r\n");
                delay(50);
                strcpy_P(parameter, Str_Con_alv); dfueLTE.ssl_data(parameter);
                dfueLTE.ssl_data("\r\n");
                // http complete

                if (dfueLTE.sendCommand(0, SERVER_TM_OUT, "ok *")) { // hat Server Anfrage quittiert ?
                  data_send = 1; // success
                  retry = 0;
                  s_cnt += _Send_Sets;

                  char *p_buf = strstr(dfueLTE.buffer, "ok *");  // search string start
                  uint8_t pos = 0;
                  do {
                    p_buf --;    // search start of line
                    pos++;
                  }
                  while ( (!(*p_buf == '\n')) && (pos < 20));

                  debugprintF("Q: "); debugprintln(p_buf); debugflush();

                  // Format Quittung aus beelogger-log.php
                  // echo "5ok *"  = 5 Minuten
                  uint8_t n;
                  if (char *p_bf = strchr(p_buf, 'T') ) {
                    n = atoi(p_bf + 1);  // Konvertiere str in int

                    char x = *(p_buf + 1);
                    if ((x > 0x2F) && (x < 0x3A)) { // first char a number?
                      long l_tm = atol(p_buf);
                      rtc_stm.setEpoch((uint32_t)l_tm);
                    }
                  }
                  else {
                    n = atoi(p_buf); // Konvertiere str in int
                  }
                  if (n <= 60) {
                    WeckIntervallMinuten = (uint8_t) n;
                  }

                  report_info = 0;
                  if (strchr(p_buf, 'P') ) {
                    report_info = 1;
                  }
                  else if (strchr(p_buf, 'L') ) {
                    report_info = 3;
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
                }  // received ok *
              }  // Open / Connect
              // dfueLTE.ssl_data("+++");// back to command mode
              debugprintF("cls:");
              debugflush();
              if (!dfueLTE.sendCommand(0, 3000, "PEER_CLOSED")) { // wait for connection terminate
                dfueLTE.ssl_close();
              }
              debugprintlnF("ok");
              debugflush();
            }
            while ((s_cnt < count) && (data_send == 1)); // while my_counter
            //######################
            if (data_send == 0) {  // failed, stop and retry
              debugprintlnF("Retry");
              debugflush();
              delay(2000);
              retry ++;
            }
          }
          while ((retry < 3) && (data_send == 0)); // retry TCP Connect
          my_EEPROM.end();

        } // ssl_start
        dfueLTE.ssl_stop();        // stop Network
      } // ssl_init
    } // start APN
  } // init

#if myDEBUG
  if (s_cnt > count) s_cnt = count;
#endif
  debugprintF("\nsend "); debugprintln(s_cnt);
  debugflush();

  dfueLTE.shutdown();    // shutdown dfue
  delay(200);
  digitalWrite(SIM_Power_Pin, LOW);
  delay(1000);
  return (s_cnt);
}
#endif
