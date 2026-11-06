/****************************************************************/
// Funktion ESP8266 WLAN   TCP / CIP-Send  EE-Prom
/****************************************************************/
// 08.04.2020 Zugriff AccessPoint List geändert
// 22.04.2020 STM32 adaption
// 02.01.2022 fix EE-Prom errorhandling
// 16.03.2022 fix Ram Use of Wire
// 09.12.2022 force signalquality
// 15.10.2023 use EE-Prom up to 128kByte in STM32F4x1 systems
// 15.11.2023 Send_Data 16 bit parameter

// 10.06.2024 Daten via https, beelogger https WLan Sketch im ESP8266
// 16.07.2024 only one code for all STM32Fxyz
// 12.01.2025 Send A=1 obsolete

#if (_WLAN_https == 1)
#pragma message " information: using WLAN https needs ESP8266 with esp_https_beelogger firmware"

/****************************************************************/
// Konfiguration ESP-8266-01 WLAN
/****************************************************************/
#define ESP_Baudrate 9600
HardwareSerial Serial2(USART2);  // or HardWareSerial Serial2 (PA3, PA2);
char buffer[128];
#define DEB_buff 0           // debug flag buffer from ESP
#define DEB_cmd 0            // debug flag data to ESP
#define SERVER_TM_OUT 10000  // 10000 = 10 sec
/****************************************************************/

/****************************************************************/
// Parameter Datenversand via Http // diese nicht modifizieren
/****************************************************************/
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
  stop_DFUe_device(): ESP8266 Reset aktiv

  @param  none, globale Variablen
  @return  none
*/
void stop_DFUe_device() {
  pinMode(ESP_RESET, OUTPUT);
  digitalWrite(ESP_RESET, LOW);  // ESP8266 "off"
}


/**
  @brief Funktion ESP8266 Setup_WLAN()
  initialisere ESP8266,
  Verbinde zum WLAN AP

  @param  none, globale Variablen
  @return  number of Access Point used
*/
bool Setup_WLAN() {
  char parameter[192];

  pinMode(ESP_RESET, OUTPUT);
  digitalWrite(ESP_RESET, HIGH);

  for (int x = 0; x < Anzahl_AP; x++) {
    Serial2.begin(ESP_Baudrate);
    delay(500);  //important
    debugprintlnF("ESP?");
    debugflush();
    if (com_2_esp(0, 5000, "*ok*")) {  // wait or timeout
      char *slen = strstr(buffer, "20");
      debugprintln(slen);
      debugflush();

      delay(200);

      if (x == 1) {
        strcpy_P(parameter, Access_Point2);
        debugprintln(parameter);
        debugflush();
        if (com_2_esp(parameter, 2000, 0)) {
          strcpy_P(parameter, AP_Passwort_2);
          com_2_esp(parameter, 2000, 0);
        }
      } else {
        strcpy_P(parameter, Access_Point1);
        debugprintln(parameter);
        debugflush();
        if (com_2_esp(parameter, 2000, 0)) {
          strcpy_P(parameter, AP_Passwort_1);
          com_2_esp(parameter, 2000, 0);
        }
      }

      if (!com_2_esp(0, 5000, "ok")) {  // read echo params
        debugprintln(buffer);
        debugprintln("??");
        debugflush();
      }
      if (com_2_esp(0, 15000, "APOK")) {
        debugprintlnF("WLAN!");
        debugflush();
        return true;
      }
    }
  }
  return false;
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
uint8_t Send_Data(uint16_t count) {
  char parameter[128];
  char para[128];
  uint8_t additional_Data = 0;  // erzeuge zusätzlichen Datensatz

  uint8_t sum, test, first;  // EE-Prom Checksumme
  char chk[2];

  uint16_t ee_pos;
  uint16_t s_cnt = 0;  // send counter
  uint16_t x, y;       // Datensatz zaehler

  uint16_t data_send;  // send status
  uint16_t retry;      // retry counter

  if (Setup_WLAN()) {  // Connect to access point

    AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);


    // user action, force time_on or signal quality
    if (((report_info == 1) && (Service < 90.0)) || (Service == 999.0)) {
      strcpy(parameter, "+++P");                // WLan sgnal strength
      if (com_2_esp(parameter, 5000, "dBm")) {  //
        char cc = 'd';
        char *pos = strchr(buffer, cc);  // suche 'd'
        if (pos) {
          pos = 0;
          int pegel = atoi(buffer);
          Service = (float)pegel;
          additional_Data = 1;
        }
        debugprintF("WLAN [dBm]: ");
        debugprintln(Service);
        debugflush();
      }
    } else {
      if (Service == 99.0) {
        additional_Data = 1;
      }
    }


    //*************************
    retry = 0;
    do {    // Block send loop
      do {  // Connect and send loop
        data_send = 0;
        strcpy_P(parameter, serverName);
        strcpy_P(para, beelogger_pfad);
        strcat(parameter, &para[4]);
        com_2_esp(parameter, 2000, 0);
        debugprintln(parameter);
        debugflush();
        delay(50);

        strcpy_P(parameter, Str_Pw);  // erstelle Daten
        strcpy_P(para, Passwort);
        strcat(parameter, para);
        strcpy_P(para, Str_Id);
        strcat(parameter, para);
        strcat(parameter, ID_ID);
        com_2_esp(parameter, 2000, 0);
        debugprintln(parameter);
        debugflush();
        delay(50);

        //Daten
        y = 0;
        first = 1;
#if 1
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
              com_2_esp(parameter, 500, 0);
              debugprintln(parameter);
            } else {
              strcpy(parameter, ",");
              com_2_esp(parameter, 500, 0);
              debugprint(parameter);
              debugflush();
            }
            debugprintln(DatenString);
            debugflush();
            delay(50);
            com_2_esp(DatenString, 2000, 0);  // Datensatz
          } else {                            // Datensatz auslassen
            DatenString[0] = 0;
            debugprintF("EE?");
            debugprintln(ee_pos);
            debugflush();
          }
          y++;

        } while ((y < _Send_Sets) && ((s_cnt + y) < count));
#else
        strcpy_P(parameter, Str_MData);
        char nmr = Anzahl_Sensoren_Gewicht + 0x30;  // itoa
        parameter[2] = nmr;                         // set number in string
        com_2_esp(parameter, 2000, 0);
        debugprint(parameter);
        com_2_esp(DatenString, 2000, 0);  // Datensatz
        delay(50);
        debugprintln(DatenString);
        debugflush();
#endif

        if ((additional_Data) && ((s_cnt + y) == count)) {  // all done, Service ?
          char service_data[16];
          dtostrf(Service, 1, 1, service_data);
          sprintf(DatenString, "&S=%s", service_data);
          com_2_esp(DatenString, 2000, 0);
          debugprintln(DatenString);
          debugflush();
        }
        // ende Daten
        strcpy(parameter, "+++");                           // send data
        if (com_2_esp(parameter, SERVER_TM_OUT, "ok *")) {  // hat Server Anfrage quittiert ?
          data_send = 1;                                    // success
          retry = 0;
          s_cnt += _Send_Sets;  // have been send
          debugprintln(s_cnt);
          debugflush();

          char *p_buf = strstr(buffer, "ok *");  // search string start
          *p_buf = 0;                            // Stringende setzen
          uint8_t pos = 0;
          do {
            p_buf--;  // search start of line
            pos++;
          } while ((!(*p_buf == '\n')) && (pos < 20));

          debugprintF(" Q: ");
          debugprintln(p_buf);
          debugflush();

          int n;
          if (char *p_bf = strchr(p_buf, 'T')) {
            n = atoi(p_bf + 1);  // Konvertiere str in int

            char x = *(p_buf + 1);
            if ((x > 0x2F) && (x < 0x3A)) {  // first char a number?
              long l_tm = atol(p_buf);
              uint32_t epochTime = (uint32_t)l_tm;
              rtc_stm.setEpoch(epochTime, 0);
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
        }
        // received ok *

        if (data_send == 0) {  // failed, terminate http session
          retry++;
          debugprintF("Retry: ");
          debugprintln(retry);
          delay(3000);  // wait
        } else {
          debugprintlnF("i.o.");
          debugflush();
          //display_mem_info();
        }
      } while ((retry < 3) && (data_send == 0));    // retry TCP Connect and send
    } while ((s_cnt < count) && (data_send == 1));  // while my_counter

    //*************************
    my_EEPROM.end();
    strcpy(parameter, "+++D");
    com_2_esp(parameter, 1000, 0);  // disconnect
    delay(200);
  }
  if (s_cnt > count) s_cnt = count;
  debugprintF("\nSend: ");
  debugprintln(s_cnt);
  debugflush();
  delay(200);
  Serial2.end();  // calls Softwareserial StopListening
  delay(100);
  digitalWrite(ESP_RESET, LOW);
  return (s_cnt);  // return Sets send, 0 = failed,
}

// com_2_esp: send data to ESP
// if parameter 'cmd' is 0 (don't use "" or "0"), nothing is send,
// the data from ESP is checked against data in 'expected'
// if 'expected' is 0, then string is "OK\r"
int com_2_esp(char *cmd, unsigned long timeout, const char *expected) {

  if (cmd) {
    empty_rcv();
    strcat(cmd, "\r");  // always append CR
#if DEB_cmd
    Serial.print(F("\r\nCmd:  "));
    Serial.println(cmd);
    Serial.flush();
#endif
    Serial2.print(cmd);
    Serial2.flush();  // wait for data to be transmitted
  }
  unsigned long t = millis();
  uint8_t n = 0;
  do {
    if (Serial2.available()) {
      char c = Serial2.read();
#if DEB_buff
      //Serial.print(c,HEX);
      Serial.print(c);
#endif
      if (n >= sizeof(buffer) - 1) {
        // buffer full, discard first half
        n = sizeof(buffer) / 2 - 1;
        memcpy(buffer, buffer + sizeof(buffer) / 2, n);
      }
      // copy received character and terminate string
      buffer[n++] = c;
      buffer[n] = 0;
      // compare buffer to expected string or "OK" if an empty one is given
      if (strstr(buffer, expected ? expected : "OK\r")) {
        return n;
      }
      if (c == '\r') n = 0;  // if newline reset counter, check a single line only
    }
  } while ((millis() - t) < timeout);
  return 0;  // not found
}

// empty RX Buffer
void empty_rcv() {
  while (Serial2.available() > 0) {
    Serial2.read();
  }
  delay(50);
}
#endif  //_WLAN_https
