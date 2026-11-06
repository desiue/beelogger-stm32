/*************************************************************************
  ESP-01 HTTP Library
  use with ESP8266_NONOS_SDK_V2.0.0_16_07_19 based on ESP Binaries
   - esp_init_data_v08
   - boot_v1.6.bin or boot_v1.7.bin
   - user1.1024.new2.bin
   - user2.1024.new2.bin
   
  see
  https://github.com/espressif/ESP8266_NONOS_SDK/tree/master/bin

  Important:
  ESP-01 must be configured fixed to 9600 or 19200Baud using:
  AT+UART_DEF=9600,8,1,0,0  or  AT+UART_DEF=19200,8,1,0,0
  AT+RESTORE must not be used after setting UART.
  Default Mode must be set to station, use function C_WLAN_ESP01::mode();
  AT+CWMODE_DEF=1

  Distributed under GPL v2.0
  For more information, please visit http://arduinodev.com

		   
  Version 1.0 08.08.2018			
  Version 1.1 28.09.2019          modified join   
  Version ESP_STM 1.0 23.02.2020
  
  Version 1.2 10.01.2021          modified signal   
  Version 1.3 26.02.2024   remove espState, byte/int -> uint...
  Version 1.4 23.03.2024   sendCommand buffers

  
*************************************************************************/				   

#include "ESP_STM_beelogger.h"


extern uint8_t ESP_RX; 
extern uint8_t ESP_TX; 

// the software serial interface														
// ESP_SERIAL = STM32 Serial 2
HardwareSerial ESP_SERIAL(USART2);   // or HardWareSerial ESP_SERIAL (PA3, PA2);
  
// Initialize Class
C_WLAN_ESP01::C_WLAN_ESP01() {}


// setup ESP in Station Mode only and single Connection
bool C_WLAN_ESP01::init(long baudrate)
{
  char c_cmd[24];
  ESP_SERIAL.begin(baudrate);
  while (!ESP_SERIAL); // Wait for serial port open
  delay(250);                       // Wait ESP8266 boot
  strcpy(c_cmd,"AT");  				// "AT"
  sendCommand(c_cmd); 				// dummy send for restart needed
  if (sendCommand(c_cmd)) { 		// AT ... "OK"
    strcpy_P(c_cmd, ESP_MUX);
    if (sendCommand(c_cmd)) {		// Connection mode
	  strcpy_P(c_cmd,ESP_ATE0);
      sendCommand(c_cmd);			// Echo Off
      return true;
    }
  }
  return false;
}

// end / stop serial, interupts may prevent sleep
void C_WLAN_ESP01::end(){
	ESP_SERIAL.end();		// calls Softwareserial StopListening
}

// Join WLAN Access Point
bool C_WLAN_ESP01::join(const char* access_point, const char* ap_passwort, unsigned long timeout){
  char buf_[100];
  timeout = 30000;
	//example AT+CWJAP_CUR=“my_access_point”,“0123456789”
    strcpy_P(buf_, ESP_WJAP);		// Connect to Access Point
    strcat(buf_, access_point);
    strcat(buf_, "\",\"");
    strcat(buf_, ap_passwort);
    strcat(buf_, "\"");
    if (sendCommand(buf_, timeout)) {
      return true;
    }
  return false;
}


// Quit from Access Point and Restart the ESP
bool C_WLAN_ESP01::quit(){
  char c_cmd[24];
  strcpy_P(c_cmd,ESP_WQAP);			// disconnect from Access Point
  sendCommand(c_cmd);     
  strcpy_P(c_cmd,ESP_RST); 			// Restart ESP
  if(sendCommand(c_cmd,5000)){
    return(true);
  }
  return(false);
}

// Connect to Host at Port 80
bool C_WLAN_ESP01::Connect(const char* host_name){
    strcpy_P(buffer, ESP_START); 	// connect to hostname
    strcat(buffer, host_name);
    strcat(buffer, "\",80");     	// port 80
    if (sendCommand(buffer, 10000, "CONNECT")) {
      return true;
    }
  return false;
}

// Terminate Server Connection
void C_WLAN_ESP01::disConnect(){
  char c_cmd[24];
  strcpy_P(c_cmd, ESP_CLOSE);		// end TCP Connection
  sendCommand(c_cmd); 
}

// prepare Sending data using CIPSENDEX
// and wait for ESP being ready by returning ">"
bool C_WLAN_ESP01::prep_send(uint16_t length){
	char c_len[8];
    //    itoa(length, c_len, 10);   	// convert len to char Base 10
    sprintf(c_len,"%3d",length);
    strcpy_P(buffer, ESP_SENDEX);		// CIPSENDEX
    strcat(buffer, c_len);
    if (sendCommand(buffer, 2000, ">")) { // receive ready?
      return (true);
    }
  return (false);
}

// send data to ESP-buffer, used with CIPSENDEX
// if 0x00 is received, ESP is asked to send data by sending "\\0"
void C_WLAN_ESP01::send(const char* data){
    if (data == 0x00) {
      ESP_SERIAL.write("\\0");  	// transmission of data start
    }
    else {
      ESP_SERIAL.print(data);
      ESP_SERIAL.flush(); 		 	// Software Serial has only a 64byte buffer,  
 #if DEB_send // Debug 
        Serial.print(data);
        Serial.flush();
#endif
	}
}

// send Command to ESP
// if parameter 'cmd' is 0 (don't use "" or "0"), nothing is send,
// default 'timeout' is set in '.h' to 2000 milliseconds
// the data from ESP is checked against data in 'expected'
// if 'expected' is omitted, default string is "OK\r"
uint8_t C_WLAN_ESP01::sendCommand(char* cmd, unsigned long timeout, const char* expected){
  if (cmd) {
    empty_rcv();
	strcat(cmd,"\r\n");  		// always append CR LF
#if DEB_cmd
    Serial.print(F("\r\nCmd:  "));
    Serial.println(cmd);
    Serial.flush();
#endif
    ESP_SERIAL.print(cmd);
    ESP_SERIAL.flush();		// wait for data to be transmitted
  }
  unsigned long t = millis();
  uint8_t n = 0;
  do {
    if (ESP_SERIAL.available()) {
      char c = ESP_SERIAL.read();
#if DEB_buff
    Serial.print(c);
#endif
      if (n >= (sizeof(buffer) - 1) ) {
        // buffer full, discard first half
        n = (sizeof(buffer) / 2) - 1;
        memcpy(buffer, buffer + (sizeof(buffer) / 2), n);
      }
	  // copy received character and terminate string
      buffer[n++] = c;
      buffer[n] = 0;
	  // compare buffer to expected string or "OK" if an empty one is given
      if (strstr(buffer, expected ? expected : "OK\r")) {
        return n;
      }
	  if( c == '\r') n = 0; // if newline reset counter, check a single line only
    }
  } while ((millis() - t) < timeout);
  return 0;  // not found
}

// empty RX Buffer
void C_WLAN_ESP01::empty_rcv() {
  while (ESP_SERIAL.available() > 0) {
    ESP_SERIAL.read();
  }
  delay(50);
}


// activate light sleep mode
void C_WLAN_ESP01::sleep(uint8_t sleep_mode){
  char c_cmd[24];
  strcpy_P(c_cmd, ESP_SLP);
  if (sleep_mode == 2) {
	  strcat(c_cmd,"2");  // modem sleep
  }
#if 0  // use only with hardware wakeup
  else if (sleep_mode == 1)	{
	  strcat(c_cmd,"1");  // light sleep
  }
#endif
  else strcat(c_cmd,"0");
  sendCommand(c_cmd);
  delay(200);
}


//*************************************************
// follwing functions only for initialising and testing the hardware	


// request the firmware information of the ESP
// return values in  variable 'buffer'
void C_WLAN_ESP01::firmware(unsigned long timeout)
{
  unsigned long t = millis();
  char c;
  uint8_t n = 0;
  empty_rcv();
  strcpy_P(buffer,ESP_GMR);
  strcat(buffer,"\r\n");
  ESP_SERIAL.print(buffer);		// firmware Information
  ESP_SERIAL.flush();		 

  do {
    if (ESP_SERIAL.available() > 0) {
      c = ESP_SERIAL.read();
      buffer[n] = c;
      buffer[n+1] = 0;
	  if (n < (sizeof(buffer)-2)) n++;
    }
  } while ((millis() - t) < timeout);
}


// request the AP information from the ESP
// return values for a given AP in var "buffer"
// w/o AP (AP = 0) data is printed to Serial.
uint8_t C_WLAN_ESP01::signal(const char* ap,unsigned long timeout)
{
  char buf[8];
	  
  empty_rcv();
  strcpy_P(buffer,ESP_WLAPOPT);	// signal information parameter
  if(sendCommand(buffer, timeout)){
    strcpy_P(buffer,ESP_WLAP);	// signal information parameter
    strcat(buffer,"=\"");  		// only the given AP
    strcat(buffer, ap);
    strcat(buffer,"\"");
    strcat(buffer,"\r\n");
    strcpy(buf,")");//ap);
    if(sendCommand(buffer, timeout, buf)){
      return(1);
    }
  }
  return(0);
}	  
	  
// set station Mode
bool C_WLAN_ESP01::mode(){
  char c_cmd[24];
  strcpy_P(c_cmd, ESP_MODE);
  if (sendCommand(c_cmd)) {
	return 1;
  }
  return 0;
}

// show serial TX data from ESP, for debug only, need Serial.begin somewhere in a sketch
void C_WLAN_ESP01::check(unsigned long timeout){
  unsigned long t = millis();
  char c;
  do {
    if (ESP_SERIAL.available() > 0) {
      c = ESP_SERIAL.read();
      Serial.print(c);
    }
  } while ((millis() - t) < timeout);
}	
