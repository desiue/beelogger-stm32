/****************************************************************/
// Funktion SIM7600E - Mobilfunk  TCP / SSL-Send  EE-Prom
/****************************************************************/
// 02.01.2022 fix EE-Prom errorhandling
// 16.03.2022 fix Ram Use of Wire
// 09.12.2022 force signalquality
// 06.04.2023 TCP version
// 15.10.2023 use EE-Prom up to 128kByte in STM32F4x1 systems
// 15.11.2023 Send_Data 16 bit parameter
// 14.07.2024 Mx-Strings
// 18.07.2024 USE EEProm "round-robin"
// 10.01.2025 add debugflush, delay
// 12.01.2025 Send A=1 obsolete


#if (_LTE_TLS == 0)

/****************************************************************/
// Kommunikation SIM7600E - Mobilfunk
/****************************************************************/
#include "SIM76_stm_beelogger.h"
CLTE_SIM76_STM dfue;  // the LTE interface

#define SERVER_TM_OUT 10000  // 10000 = 10 sec
/****************************************************************/


/****************************************************************/
// Parameter Datenversand via Http // diese nicht modifizieren
/****************************************************************/
const char Str_Http[] PROGMEM = " HTTP/1.1\r\n";  // ein Leerzeichen vor HTTP
const char Str_Con_alv[] PROGMEM = "Connection: close\r\n\r\n";

const char Str_Pw[] PROGMEM = { "PW=" };
const char Str_Id[] PROGMEM = { "&Z=2&ID=" };
#if (Anzahl_Sensoren_Gewicht == 1)
const char Str_MData[] PROGMEM = { "&M1=" };  // Parameter Multi Data  Senden
#endif
#if (Anzahl_Sensoren_Gewicht == 2)
const char Str_MData[] PROGMEM = { "&M2=" };  // Parameter Multi Data  Senden
#endif
#if (Anzahl_Sensoren_Gewicht == 3)
const char Str_MData[] PROGMEM = { "&M3=" };  // Parameter Multi Data  Senden
#endif
#if (Anzahl_Sensoren_Gewicht == 4)
const char Str_MData[] PROGMEM = { "&M4=" };  // Parameter Multi Data  Senden
#endif
/****************************************************************/


/**
  @brief Utility functions
  stop_DFUe_device(): SIM7600E Reset aktiv oder Power inaktiv

  @param  none, globale Variablen
  @return  none
*/
void stop_DFUe_device() {
  pinMode(SIM_Power_Pin, OUTPUT);
  digitalWrite(SIM_Power_Pin, LOW);  // SIM-Modul "off"
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
uint8_t Send_Data(uint16_t count) {
  char parameter[128];
  uint8_t additional_Data = 0;

  uint8_t sum, test, first;  // EE-Prom Checksumme
  char chk[2];

  uint16_t ee_pos;
  uint16_t s_cnt = 0;  // send counter
  uint16_t x, y;       // Datensatz zaehler

  uint8_t data_send;                  // send status
  uint8_t retry;                      // retry counter
  uint8_t sets_to_Send = _Send_Sets;  // decrease when errors occur

  debugprintlnF("LTEon");
  debugflush();
  pinMode(SIM_Power_Pin, OUTPUT);
  digitalWrite(SIM_Power_Pin, HIGH);

  if (dfue.init()) {  // Init SIM7600 Modul
    debugprintlnF("Net");
    debugflush();
    
#if SET_LTE_ONLY == 1
    dfue.sendCommand("AT&F");
    dfue.sendCommand("AT+CNMP=38");
#endif
#if SET_LTE_ONLY == 2
    dfue.sendCommand("AT&F");
    dfue.sendCommand("AT+CNMP=2");
#endif

#if SET_LTE_ONLY == 3
    dfue.sendCommand("AT+CGDCONT=1,\"IP\",\"\"");
#endif

    strcpy_P(parameter, APN);
    debugprintln(parameter);
    debugflush();
    if (dfue.tcp_start(parameter, APN_Benutzer, APN_Passwort, A_7600) == 0) {  // Start dfue Connection

      // user action, force time_on or signal quality
      if (((report_info == 1) && (Service < 90.0)) || (Service == 999.0)) {
        delay(100);
        int Signal = dfue.getSignalQuality();
        Service = (float)Signal;
        additional_Data = 1;

        debugprint(Signal);
        debugprintlnF(" dBm");
        debugflush();

      } else {
        if (Service == 99.0) {
          additional_Data = 1;
        }
      }

      AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);

      //#################
      data_send = 0;  // send status
      retry = 0;      // retry counter
      do {            // retry loop
        do {          // Blockweise send loop
          data_send = 0;
          // always new connect; session alive not supported by server

          strcpy_P(parameter, serverName);
          if (dfue.Connect(parameter)) {  // Start TCP Connection
            dfue.sendCommand("AT");
            debugprintlnF("TCP");
            debugflush();
            strcpy_P(parameter, serverName);
            debugprintln(parameter);
            debugflush();

            if (dfue.prep_send()) {  // Open Server connection

              strcpy_P(parameter, beelogger_pfad);  // GET ....
              debugprintln(parameter);
              debugflush();
              dfue.send(parameter);
              strcpy_P(parameter, Str_Pw);
              dfue.send(parameter);
              strcpy_P(parameter, Passwort);
              dfue.send(parameter);
              // System ID
              strcpy_P(parameter, Str_Id);
              strcat(parameter, ID_ID);
              dfue.send(parameter);
              delay(50);
              //Daten
              y = 0;
              first = 1;
              do {
                // lese von AT24Cxx
                test = 0;
                do {
                  ee_pos = (my_counter_start + s_cnt + y);
                  //debugprintln(ee_pos);debugflush();
                  if (ee_pos >= _EE_DATA_SETS) ee_pos -= _EE_DATA_SETS;
                  ee_pos *= _Daten_Satz_Len;
                  //debugprintln(ee_pos);debugflush();
                  my_EEPROM.ReadStr(ee_pos, DatenString, _Daten_Satz_Len - 2);
                  DatenString[(_Daten_Satz_Len - 2)] = 0;  // String ende erzwingen
                  sum = 0;
                  for (x = 0; x < strlen(DatenString); x++) {  // EE_Prom Checksum test
                    sum += DatenString[x];
                  }
                  ee_pos = ee_pos + _Daten_Satz_Len - 1;
                  my_EEPROM.ReadStr(ee_pos, chk, 1);
                  x = (uint8_t)chk[0];
                  test++;
                } while ((test < 2) && (sum != x));  // zwei Leseversuche

                if (sum == x) {      // checksumme i.o.
                  if (first == 1) {  // einfügen Satztrenner
                    first = 0;
                    strcpy_P(parameter, Str_MData);
                    debugprint(parameter);
                    debugflush();
                    dfue.send(parameter);
                    delay(50);
                  } else {
                    dfue.send(",");
                  }
                  debugprintln(DatenString);
                  debugflush();
                  dfue.send(DatenString);
                  delay(100);

                } else {  // Datensatz auslassen
                  DatenString[0] = 0;
                  debugprintF("EE?");
                  debugprintln(ee_pos);
                  debugflush();
                }
                y++;
              } while ((y < sets_to_Send) && ((s_cnt + y) < count));


              if ((s_cnt + y) == count) {  // all done, Service ?
                if (additional_Data) {
                  char service_data[16];
                  dtostrf(Service, 1, 3, service_data);
                  sprintf(DatenString, "&S=%s", service_data);
                  debugprintln(DatenString);
                  debugflush();
                  dfue.send(DatenString);
                  delay(50);
                }
              }
              // ende Daten
              strcpy_P(parameter, Str_Http);
              dfue.send(parameter);  // HTTP/1.1
              dfue.send("Host: ");
              strcpy_P(parameter, serverName);
              dfue.send(parameter);
              dfue.send("\r\n");
              delay(50);
              strcpy_P(parameter, Str_Con_alv);
              dfue.send(parameter);
              dfue.send("\r\n");
              dfue.send(0x00);  // Startkommando senden
              // http complete

              if (dfue.sendCommand(0, SERVER_TM_OUT, "ok *")) {  // hat Server Anfrage quittiert ?
                data_send = 1;                                   // success
                retry = 0;
                s_cnt += sets_to_Send;

                char *p_buf = strstr(dfue.buffer, "ok *");  // search string start
                uint8_t pos = 0;
                do {
                  p_buf--;  // search start of line
                  pos++;
                } while ((!(*p_buf == '\n')) && (pos < 20));

                debugprintF("Q: ");
                debugprintln(p_buf);
                debugflush();

                // Format Quittung aus beelogger-log.php
                // echo "5ok *"  = 5 Minuten
                uint8_t n;
                if (char *p_bf = strchr(p_buf, 'T')) {
                  n = atoi(p_bf + 1);  // Konvertiere str in int

                  char x = *(p_buf + 1);
                  if ((x > 0x2F) && (x < 0x3A)) {  // first char a number?
                    long l_tm = atol(p_buf);
                    rtc_stm.setEpoch((uint32_t)l_tm);
                  }
                } else {
                  n = atoi(p_buf);  // Konvertiere str in int
                }
                if ((n > 4) && (n <= 60)) {
                  WeckIntervallMinuten = (uint8_t)n;
                }

                report_info = 0;
                if (strchr(p_buf, 'P')) report_info = 1;
                if (strchr(p_buf, 'A')) send_cycle = SEND_CYC_A;
                else if (strchr(p_buf, 'B')) send_cycle = SEND_CYC_B;
                else if (strchr(p_buf, 'C')) send_cycle = SEND_CYC_C;
                else if (strchr(p_buf, 'D')) send_cycle = SEND_CYC_D;
              }  // received ok *
            }    // Open / Connect
            debugprintF("cls:");
            debugflush();

            dfue.disConnect();  // stop Network  CIPCLOSE

          }  // Connect
          delay(2000);
        } while ((s_cnt < count) && (data_send == 1));  // while my_counter
        //######################
        if (data_send == 0) {  // failed, stop and retry
          debugprintlnF("Retry");
          debugflush();
          delay(5000);
          retry++;
          if (sets_to_Send > 5) sets_to_Send--;
        }
        if (retry == 1) report_info = 0;          // error may be due to fieldstrength measurement
      } while ((retry < 3) && (data_send == 0));  // retry TCP Connect
      my_EEPROM.end();

      dfue.stop();  // stop Network

    }  // start APN
  }    // init

  dfue.shutdown();  // shutdown dfue
  delay(200);
  digitalWrite(SIM_Power_Pin, LOW);
  digitalWrite(SER2_RX, LOW);
  digitalWrite(SER2_TX, LOW);
  delay(500);
#if myDEBUG
  if (s_cnt > count) s_cnt = count;
#endif
  debugprintF("\nsend ");
  debugprintln(s_cnt);
  debugflush();
  return (s_cnt);
}
#endif
