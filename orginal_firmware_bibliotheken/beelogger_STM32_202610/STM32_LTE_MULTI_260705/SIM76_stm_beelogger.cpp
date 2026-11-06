/*************************************************************************
  SIM7600E TCPIP Library
  based on SIM800 GPRS/HTTP Library
  Distributed under GPL v2.0
  Written by Stanley Huang <stanleyhuangyc@gmail.com>
  For more information, please visit http://arduinodev.com

  Modified for use with SIM7600E

  fixed reading signal level
  Version 1.0.1  10102021 R.Schick
     wait til port ready

  Version 1.0.2 01122021 R.Schick
     add delay when purging data

  Version 1.0.3 21022022 R.Schick
          CGPADDR response


    Version 1.0.6 05042023 R.Schick
           modify CGREG mode roaming

    Version 1.0.7 16.09.2023 R.Schick
           allow init with baudrate, hardwareserial is able 115200bd
    Version 1.1 23.03.2025 R.Schick sendCommand buffers
    V 1.2    26.03.2025 modify init / start functions
    V 1.3    11.04.2025 modify init,A7670 / start,A7600 functions
    V 1.4    15.04.2025 strings in program space
    V 1.5    17.04.2025 LTE Only mode, CREG 
    V 1.6    07.05.2025 startup checking CPIN READY
    V 1.7    --
    V 1.8    22.08.2025  change SSL to handle SNI


*************************************************************************/

#include "SIM76_STM_beelogger.h"

// the software serial interface
// Serial2
HardwareSerial SIM_SERIAL(USART2);  // or HardWareSerial Serial2 (PA3, PA2);


const char SIM7600_CPIN[] PROGMEM = "AT+CPIN?";    // y76xxE SIM card ok?
const char SIM7600_RDY[] PROGMEM = " READY";       // SIMcard is ok
const char SIM7600_REG[] PROGMEM = "AT+CREG?";     // SIM76xx GPRS registration
const char SIM7600_CGREG[] PROGMEM = "AT+CGREG?";  // SIM76xx LTE registration
const char SIM7600_CFUN0[] PROGMEM = "AT+CFUN=0";  // SIM76xx functions off
const char SIM7600_CPOF[] PROGMEM = "AT+CPOF";     // SIM76xx power off

const char SIM7600_CGD[] PROGMEM = "AT+CGDCONT=1,\"IP\",\"";  // open IP-Socket
const char SIM7600_CGDREQ[] PROGMEM = "AT+CGDCONT?";

//TCP
const char SIM7600_CIPTMO[] PROGMEM = "AT+CIPTIMEOUT=20000,20000,15000";  // timeout netopen, cipopen, cipsend
const char SIM7600_NETOPN[] PROGMEM = "AT+NETOPEN";
const char SIM7600_NETCLS[] PROGMEM = "AT+NETCLOSE";
const char SIM7600_CIPMOD[] PROGMEM = "AT+CIPMODE=0";
const char SIM7600_CIPOPN[] PROGMEM = "AT+CIPOPEN=1,\"TCP\",\"";
const char SIM7600_CIPCLS[] PROGMEM = "AT+CIPCLOSE";  // ? or 0,1

//SSL
const char SIM7600_ADR[] PROGMEM = "AT+CGPADDR";                      // check local IP-Adress
const char SIM7600_MODE[] PROGMEM = "AT+CCHMODE=1";                   // select Mode Transparent
const char SIM7600_SET[] PROGMEM = "AT+CCHSET=0,0";                   // set Mode
const char SIM7600_CSSLs[] PROGMEM = "AT+CSSLCFG=\"enableSNI\",0,1";  // set CSSL Config enable SNI 0,1 (disable 0,0)
const char SIM7600_START[] PROGMEM = "AT+CCHSTART";                   // Start SSL
const char SIM7600_CFG[] PROGMEM = "AT+CCHSSLCFG=0,0";                // Set SSL Config to session
const char SIM7600_OPEN[] PROGMEM = "AT+CCHOPEN=0,\"";                // Open Session to server
const char SIM7600_CON[] PROGMEM = "T 96";                            // check Connect 9600
const char SIM7600_CLOSE[] PROGMEM = "AT+CCHCLOSE=0";                 // Close Session
const char SIM7600_STOP[] PROGMEM = "AT+CCHSTOP";                     // Stop SSL

//location
const char SIM7600_NETSTA[] PROGMEM = "AT+CNETSTART";
const char SIM7600_NETSTO[] PROGMEM = "AT+CNETSTOP";

/**************************************************************************/
/*!
    @brief   setup Serial and check SIM7600E booting up
*/
/**************************************************************************/
bool CLTE_SIM76_STM::init() {
#define try_count 30

  char buf[16], buff[8];
  uint8_t i = 0;
  SIM_SERIAL.begin(9600);

  //pinMode(GSM_RX, INPUT);  // Turn pullup off   pinMode(INPUT_CAPTURE_PIN, INPUT_PULLUP);
  strcpy(buf, "AT");
  do {  // Important, wait for 76xx  coming up
    if (sendCommand(buf, 2000)) {
      break;
    }
    i++;
  } while (i < try_count);

  strcpy_P(buf, SIM7600_CPIN);
  strcpy_P(buff, SIM7600_RDY);
  i = 0;
  do {  // Important, wait for 76xx  coming up
    if (sendCommand(buf, 2000, buff)) {
      break;
    }
    i++;
  } while (i < try_count);
  if (i == try_count) return (false);

  delay(1000);

#if DEB_data
  _RX_TX.println(F("SIM_A_76xx 07.05.2025"));
  _RX_TX.flush();
#endif

#if (SIM7600__DEBUG == 0)
  strcpy(buf, "ATE0");
  if (sendCommand(buf) == false) return (false);
#endif

  return (true);
}

/**************************************************************************/
/*!
    @brief    turn off services and end Serial
*/
/**************************************************************************/
void CLTE_SIM76_STM::shutdown() {
  char buf[16];
#if DEB_more
  strcpy(buf, "AT+SIMCOMATI");  // module info
  sendCommand(buf);
  strcpy(buf, "AT+CPSI?");  // net status
  sendCommand(buf);
#endif
  strcpy_P(buf, SIM7600_CFUN0);  // AT+CFUN=0
  sendCommand(buf);
  strcpy_P(buf, SIM7600_CPOF);  // AT+CPOF
  sendCommand(buf);
  SIM_SERIAL.end();  // stop listening
}


/**************************************************************************/
/*!
    @brief  SSL functions, transparent Mode
*/
/**************************************************************************/

/**************************************************************************/
/*!
    @brief   check network and connect to APN
*/
/**************************************************************************/
uint8_t CLTE_SIM76_STM::start(const char* apn, const char* usr, const char* pw) {
  bool success = false;
  uint8_t n = 0;
  char buf[64];
  char mode = '0';
  strcpy_P(buf, SIM7600_REG);
  do {
    if (sendCommand(buf, 2000)) {  // are we registerd to a gsm-network
      char* p = strstr(buffer, "0,");
      if (p) {
        char mode = *(p + 2);
        if ((mode == '1' || mode == '5' || mode == '6' || mode == '7')) {  // 0,1 - homenet, 0,5 - roaming, 0,6 - sms only, 0,7 - sms roaming
          success = true;                                                  // 0,0 - not searching; 0,2 - searching; 0,3 - denied ; 0,4 - unknown
          break;                                                           // 0,6 - 0,7 für A7670E
        }
      }
    }
    delay(2000);
    n++;
  } while (n < 30);
  if (!success) return mode;  // not registered to network

  success = false;
  delay(1000);
  n = 0;
  strcpy_P(buf, SIM7600_CGREG);
  do {
    if (sendCommand(buf, 2000)) {  // are we registerd to a 4G-network
      char* p = strstr(buffer, "0,");
      if (p) {
        char mode = *(p + 2);
        if (mode == '1' || mode == '5') {  // net or roaming
          success = true;
          break;
        }
      }
    }
    delay(2000);
    n++;
  } while (n < 5);
  if (!success) return mode;  // not registered to gprs network

  delay(1000);

  strcpy_P(buf, SIM7600_CGDREQ);  // AT+CGDCONT?
  sendCommand(buf);

  //activate context with APN
  strcpy_P(buf, SIM7600_CGD);  // AT+CGDCONT=1,\"IP\",\"
  strcat(buf, apn);
  strcat(buf, "\"");
  if (sendCommand(buf)) {
    delay(1000);  // wait on SIM7600 to get ready
#if 0
    if (strlen(usr) > 0) {
      strcpy(buf, "AT+CGAUTH=1,2,\"");
      strcat(buf, usr);
      strcat(buf, "\",\"");
      strcat(buf, pw);
      strcat(buf, "\"");
      sendCommand(buf, 5000);
      delay (500);
    }
#endif
    strcpy_P(buf, SIM7600_ADR);
    n = 0;
    do {
      if (sendCommand(buf)) {  // +CGPADDR: 1,0.0.0.0, are we done internally?
        return (0);
      }
      delay(1000);
      n++;
    } while (n < 5);
    return (4);  // no IP Adress
  }
  return (3);  // no APN
}

/**************************************************************************/
/*!
    @brief  SSL functions, transparent Mode

*/
/**************************************************************************/
uint8_t CLTE_SIM76_STM::ssl_init() {  //prepare SSL, set transparent mode
  char buf[32];
  strcpy_P(buf, SIM7600_MODE);
  if (sendCommand(buf)) {  // 1 = transparent Mode
    delay(500);            // give SIM7600 some time to proceed

    strcpy_P(buf, SIM7600_SET);
    if (sendCommand(buf)) {
      delay(500);  // give SIM7600 some time to proceed
      return (1);
    }
  }
  return (0);
}
//start SSL, activate PDP context
uint8_t CLTE_SIM76_STM::ssl_start() {
  char buf[20];
  strcpy_P(buf, SIM7600_START);
  if (sendCommand(buf)) {
    delay(500);  // give SIM7600 some time to proceed
    strcpy_P(buf, SIM7600_CFG);
    if (sendCommand(buf)) {
      delay(500);  // give SIM7600 some time to proceed
      return (1);
    }
  }
  return (0);
}
//stop SSL
uint8_t CLTE_SIM76_STM::ssl_stop() {
  char buf[16];
  strcpy_P(buf, SIM7600_STOP);
  if (sendCommand(buf)) {
    delay(500);  // give SIM7600 some time to proceed
    return (1);
  }
  return (0);
}
//connect SSL
uint8_t CLTE_SIM76_STM::ssl_open(const char* url) {
  char buf[80];  // AT+CCHOPEN=0," server_name ",443,2
  char cmp[16];
  strcpy_P(cmp, SIM7600_CON);  // CONNECT
  strcpy_P(buf, SIM7600_OPEN);
  strcat(buf, url);
  strcat(buf, "\",443,2");

  if (sendCommand(buf, 10000, cmp)) {  // check CONNECT
    // Check CONNECT "FAIL"
    //strcpy(cmp, "FAIL");
    //if(sendCommand(0,1000,cmp) == 0){
    return (1);  // FAIL not found -> success
    //}
  }
  return (0);
}
//close connection
uint8_t CLTE_SIM76_STM::ssl_close() {
  char buf[16];
  strcpy_P(buf, SIM7600_CLOSE);
  if (sendCommand(buf)) {
    return (1);
  }
  return (0);
}

//write data to module GET / ...
void CLTE_SIM76_STM::ssl_data(const char* data) {
#if DEB_data
  _RX_TX.println(data);
  _RX_TX.flush();
#endif
  SIM_SERIAL.print(data);
  SIM_SERIAL.flush();
}
//#####################################
// end SSL functions
//#####################################


//#####################################
// start TCP/IP functions
//#####################################
uint8_t CLTE_SIM76_STM::tcp_start(const char* apn, const char* usr, const char* pw, uint8_t a_7600) {
  bool success = false;
  uint8_t n = 0;
  char buf[64];
  char mode = '0';
  strcpy_P(buf, SIM7600_REG);
  do {
    if (sendCommand(buf, 2000)) {  // are we registerd to a gsm-network
      char* p = strstr(buffer, "0,");
      if (p) {
        char mode = *(p + 2);
        if ((mode == '1' || mode == '5' || mode == '6' || mode == '7')) {  // 0,1 - homenet, 0,5 - roaming, 0,6 - sms only, 0,7 - sms roaming
          success = true;                                                  // 0,0 - not searching; 0,2 - searching; 0,3 - denied ; 0,4 - unknown
          break;                                                           // 0,6 - 0,7 für A7670E
        }
      }
    }
    delay(2000);
    n++;
  } while (n < 30);
  if (!success) return mode;

  delay(1000);
  n = 0;
  strcpy_P(buf, SIM7600_CGREG);
  do {
    if (sendCommand(buf, 2000)) {      // are we registerd to a 4G-network
      char* p = strstr(buffer, "0,");  // expected 0,1 or 0,5
      if (p) {
        char mode = *(p + 2);
        if (mode == '1' || mode == '5') {  // net or roaming
          success = true;
          break;
        }
      }
    }
    delay(2000);
    n++;
  } while (n < 10);
  if (!success) return mode;

  delay(1000);

  strcpy_P(buf, SIM7600_CGDREQ);  // AT+CGDCONT=?
  sendCommand(buf);

#if 0
  if (strlen(usr) > 0) {
    strcpy(buf, "AT+CGAUTH=1,2,\"");
    strcat(buf, usr);
    strcat(buf, "\",\"");
    strcat(buf, pw);
    strcat(buf, "\"");
    sendCommand(buf, 5000);
    delay (500);
  }
#endif
  //activate PDP Context with APN
  strcpy_P(buf, SIM7600_CGD);  // AT+CGDCONT=1,\"IP\",\"
  strcat(buf, apn);
  strcat(buf, "\"");
  if (sendCommand(buf, 20000)) {
    delay(1000);
    strcpy_P(buf, SIM7600_CIPMOD);  // AT+CIPMODE=0
    sendCommand(buf);
    strcpy_P(buf, SIM7600_CIPTMO);  // AT+CIPTIMEOUT
    sendCommand(buf);
    if (a_7600 == 1) {
      sendCommand("AT+CGACT=0,1");
    }

    n = 0;
    strcpy_P(buf, SIM7600_NETOPN);
    if (sendCommand(buf, 20000, ": 0")) {  // activate Context +NETOPEN: 0
      strcpy(buf, "AT+IPADDR");
      do {
        if (sendCommand(buf)) {  // are we done internally
          return (0);
        }
        delay(1000);
        n++;
      } while (n < 3);
      return (5);
    }
    return (4);
  }
  return (3);
}

//#####################################
//Deactivate Context
//makes context come back to original state
void CLTE_SIM76_STM::stop() {
  char buf[16];
  strcpy_P(buf, SIM7600_NETCLS);
  sendCommand(buf);
}

//#####################################
// CIP start connection to host
bool CLTE_SIM76_STM::Connect(const char* host_name) {
  char buf[64];
  strcpy_P(buf, SIM7600_CIPOPN);
  strcat(buf, host_name);
  strcat(buf, "\",80");  // port 80
  // set up or check for a connection +CIPOPEN: 1,0
  if (sendCommand(buf, 20000, "N: 1,0")) {
    return true;
  }
  disConnect();
  return false;
}

//#####################################
//close TCP Connection
void CLTE_SIM76_STM::disConnect() {
  char buf[16];
  strcpy_P(buf, SIM7600_CIPCLS);
  strcat(buf, "?");
#if 0
  if (sendCommand(buf, 5000, " 0,1")) {  //already closed?
    strcpy_P(buf, SIM7600_CIPCLS);
    strcat(buf, "=1");
    sendCommand(buf, 10000, "1,0");
  }
#else
  if (sendCommand(buf, 5000)) {       //already closed?
    char* p = strstr(buffer, " 0,");  // expected 0,1 or 0,0
    if (p) {
      char mode = *(p + 3);
      if (mode == '1') {
        strcpy_P(buf, SIM7600_CIPCLS);
        strcat(buf, "=1");
        sendCommand(buf, 10000, "1,0");
      }
    }
  }
#endif
}



//#####################################
// prepare Sending data using CIPSEND
// and wait for being ready by returning ">"
bool CLTE_SIM76_STM::prep_send() {
  char buf[16] = "AT+CIPSEND=1,";
  if (sendCommand(buf, 15000, ">")) {  // receive ready?
    return (true);
  }
  return (false);
}
//#####################################
// start sending data by calling function: send(0x00)
void CLTE_SIM76_STM::send(const char* data) {
  if (data == 0x00) {
    SIM_SERIAL.write(0x1A);  // transmission of data start
  } else {
#if DEB_data
    _RX_TX.print(data);
    _RX_TX.flush();
#endif
    SIM_SERIAL.print(data);
  }
}
//#####################################
// end TCP/IP functions
//#####################################



/**************************************************************************/
/*!
    @brief   get signal quality on the given entwork
*/
/**************************************************************************/
int CLTE_SIM76_STM::getSignalQuality() {
  char buf[8] = "AT+CSQ";
  sendCommand(buf, 10000, ",");  // suche nach Komma zw. rssi und ber
  char* p = strstr(buffer, "CSQ: ");
  if (p) {
    int n = atoi(p + 5);
    if (n == 99 || n == -1) return -1;
    return n * 2 - 114;
  }
  return -2;
}
/**************************************************************************/
/*!
    @brief   get location data of the base station connected to
*/
/**************************************************************************/
// Location command:
//AT+CLBS=4
//OK
//+CLBS: 0,31.228525,121.380295,500,2025/06/07,10:49:08
//
bool CLTE_SIM76_STM::getLocation(GSM_LOCATION* loc) {
  bool x_val = false;
  char buf[16];
  strcpy_P(buf, SIM7600_NETSTA);
  if (sendCommand(buf, 15000)) {
    delay(1000);  // wait for SIM7600 to get ready
    strcpy(buf, "AT+CLBS=4");
    if (sendCommand(buf, 15000, "+CLBS")) {
      uint32_t t = millis();
      uint8_t n = 0;
      do {  // read til EOL
        if (SIM_SERIAL.available()) {
          char c = SIM_SERIAL.read();
          buffer[n++] = c;
          if (c == '\r') {
            break;  // EOL
          }
        }
      } while ((n < 50) && (millis() - t < 3000));
      buffer[n] = 0;
      char* p = buffer;
      do {
        if (!(p = strchr(p, ','))) break;
        loc->lon = atof(++p);
        if (!(p = strchr(p, ','))) break;
        loc->lat = atof(++p);
        /*
          if (!(p = strchr(p, ','))) break;
          loc->year = atoi(++p) - 2000;
          if (!(p = strchr(p, '/'))) break;
          loc->month = atoi(++p);
          if (!(p = strchr(p, '/'))) break;
          loc->day = atoi(++p);
          if (!(p = strchr(p, ','))) break;
          loc->hour = atoi(++p);
          if (!(p = strchr(p, ':'))) break;
          loc->minute = atoi(++p);
          if (!(p = strchr(p, ':'))) break;
          loc->second = atoi(++p);
        */
        x_val = true;
        break;
      } while (0);
    }
#if DEB_data
    _RX_TX.print(buffer);
    _RX_TX.flush();
#endif
    strcpy_P(buf, SIM7600_NETSTO);
    sendCommand(buf);
  }
  return x_val;
}

/**************************************************************************/
/*!
    @brief    Utility functions
*/
/**************************************************************************/
uint8_t CLTE_SIM76_STM::sendCommand(const char* cmd, unsigned int timeout, const char* expected) {
  if (cmd != 0) {
#if DEB_cmd
    _RX_TX.print(F("\n**"));
    _RX_TX.println(cmd);
    _RX_TX.flush();
#endif
    purgeSerial();
    SIM_SERIAL.println(cmd);
  }
  uint32_t t = millis();
  uint8_t n = 0;
  char c;
  do {
    if (SIM_SERIAL.available()) {
      c = SIM_SERIAL.read();
      if (n >= (sizeof(buffer) - 1)) {  // buffer full, discard first half
        n = (sizeof(buffer) / 2) - 1;
        memcpy(buffer, buffer + (sizeof(buffer) / 2), n);
      }
      buffer[n++] = c;
      buffer[n] = 0;
#if DEB_more
      _RX_TX.print(c);
#endif
      if (strstr(buffer, expected ? expected : "OK\r")) {
        return n;
      }
      //if( c == '\r') n = 0; // do not use here, causes major trouble
    }
  } while (millis() - t < timeout);
#if DEB_more
  _RX_TX.print(F("-t/o"));
  _RX_TX.flush();
#endif
  return 0;
}

//#####################################
void CLTE_SIM76_STM::purgeSerial() {
  while (SIM_SERIAL.available()) {
    SIM_SERIAL.read();
    delay(5);
  }
}
