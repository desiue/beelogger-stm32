/**
   BasicHTTPSClientNofingerprint.ino

   1.03.2019

   example from the ESP8226 libraries
   modified / additions 06.06.2024  by beelogger.de

  06.06.2024  by beelogger.de: add WLan RSSI, DisConnect
  14.06.2024  change https parameters
  15.06.2024  use built-in-led
  15.07.2024  send firmware version/date

  this sketch to be loaded into the ESP8266 modul using ESP8266 programming setup
  how to ... see websites in internet


  Installations Information:
  Ardunio IDE für die Programmierung vorbereiten
  Für den ESP8266 werden spezielle Erweiterungen für den Bordverwalter benötigt.
  Dafür wird in der Arduino-IDE im Menu “Datei” unter “Voreinstellungen” im Feld “Zusätzliche Bordverwalter URLs” folgende Zeile eingetragen:

  https://arduino.esp8266.com/stable/package_esp8266com_index.json

  und das Fenster mit “OK” verlassen.

  Danach unter “Werkzeuge”, “Board” den “Boardverwalter” aufrufen.
  Nach Laden der Informationen steht das Paket “esp82662” by 'ESP8266 Community' zur Verfügung.
  Dieses ist zu installieren, das kann einige Zeit dauern.

  Der ESP8266 wird über einen USB-Seriell-Adapter programmiert.
  Beispiele für den Aufbau zur Programmierung (bzw. Firmwareupdate) des ESP8266 finden sich im Netz.
  Im Boradverwalter den ESP8266 wählen und Generic-ESP, den passenden COM-Port für den USB-Seriell-Adapter einstellen.
  Nach dem Einspielen des Sketch ist der ESP8266 für den Einsatz auf dem beelogger (Universal) bereit.
  Achtung: Den so vorbereiteten ESP82666 nur in Verbindung mit dem passenden Sketch im ProMini/Nano verwenden.

  Kommunikation:
  dateVersion*ok* sendet der ESP nach Start an den Host
  OK sendet der ESP nach jeder Datenübertragung
  AP: apname PW: ap-passwort ok sendet der ESP nach Empfang von AP und AP-Passwort
  APOK sendet der ESP nach erfolgreichen Anmelden im WLAN
  Empfang von "+++" startet senden der empfangenen Daten via https client
  Empfang von "+++D" führt DisConnect vom WLan aus
  Empfang von "+++P" messen WLan Pegel, liefert "xxxdBm"
  empfangene Daten haben als letztes Zeichen immer '/r'

  Ablauf:
  ESP-Reset, Host gesteuert
  Host wartet auf *ok*
  Host sendet AP-Name
  Host sendet AP-Passwort
  Host wartet auf Quittung "AP:apname PW:passwort ok"
  Host wartet auf "APOK"

  Host sendet Daten für https Aufruf (ohne "https://")
     Beispiel: https://community.beelogger.de/xyz/beelogger1/beelogger_log?PW=abc&Z=2&A=1&M1=...
  Host sendet "+++" (Aufruf Senden)
  Host wartet auf Quittung vom Webserver
  Host sendet "+++D" ( Abmelden vom WLan)

*/

#include <Arduino.h>

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>

ESP8266WiFiMulti WiFiMulti;

#define AP_Connect_Timeout 10000
char call[1200] = "";
int start_send = 0;


// Fingerprint for demo URL, expires on June 2, 2019, needs to be updated well before this date
//const uint8_t fingerprint[20] = {0x5A, 0xCF, 0xFE, 0xF0, 0xF1, 0xA6, 0xF4, 0x5F, 0xD2, 0x11, 0x11, 0xC6, 0x1D, 0x2F, 0x0E, 0xBC, 0x39, 0x8D, 0x50, 0xE0};


void setup() {
  char ap[256];
  char ap_pw[256];

  Serial.begin(9600);
  Serial.setRxBufferSize(256);
  delay(1000);  // wait to stable
  unsigned long t = millis();
  do {  // empty buffer
    Serial.read();
    delay(5);
  } while ((millis() - t) < 100);
  delay(250);
  Serial.println("20240830V1.1*ok*"); Serial.flush();
  while (Serial.available() == 0) {} // Wait for Data
  read_ser(ap);
  while (Serial.available() == 0) {} // Wait for Data
  read_ser(ap_pw);
  pinMode(LED_BUILTIN, OUTPUT);
  delay(50);
  Serial.print("AP:"); Serial.print(ap); Serial.print(" PW:"); Serial.print(ap_pw); Serial.println("ok");
  Serial.flush();
  delay(50);
  WiFi.mode(WIFI_STA); //Station mode
  WiFiMulti.addAP(ap, ap_pw);

  if (my_connect(AP_Connect_Timeout)) { // connect to AP
    Serial.println("APOK");
    Serial.flush();
    prep_call();
  }
  else Serial.println("AP fail");
}


void loop() {
  char data[256];
  char d[8];
  if (start_send == 0) { // collect data to send
    while (Serial.available() == 0) {} // Wait for Data
    read_ser(data);
    strncpy(d, data, 6);
    if (strcmp(d, "+++") == 0) {
      start_send = 1;
      digitalWrite(LED_BUILTIN, LOW);
    }
    if (strcmp(d, "+++D") == 0) {
      start_send = 2;
      WiFi.disconnect();
    }
    if (strcmp(d, "+++P") == 0) {
      long rssi = WiFi.RSSI();
      Serial.print(rssi); Serial.print("dBm");
    }
    else if (start_send == 0) {
      if ( (strlen(call) + strlen(data)) < (sizeof(call) - 2) ) strcat(call, data);
    }
  }

  if (start_send == 1) { // data complete, send
    std::unique_ptr<BearSSL::WiFiClientSecure>client(new BearSSL::WiFiClientSecure);
    
    //client->setFingerprint(fingerprint); // with valid certificate fingerprint
    client->setInsecure();
    
    HTTPClient https_client;
    if (https_client.begin(*client, call)) {  // HTTPS
      digitalWrite(LED_BUILTIN, HIGH);
      int httpCode = https_client.GET();
      digitalWrite(LED_BUILTIN, LOW);
      if (httpCode > 0) {  // httpCode will be negative on error
        if (httpCode == HTTP_CODE_OK) {         // file found at server
          String payload = https_client.getString();
          int pos = payload.indexOf('<');
          Serial.println(payload.substring(0, pos));
        }
      } else {
        Serial.printf("[HTTPS] GET... failed, error: %s\n", https_client.errorToString(httpCode).c_str());
      }
      https_client.end();
    } else {
      Serial.printf("[HTTPS] Unable to connect\n");
    }
    prep_call();
    start_send = 0;
    digitalWrite(LED_BUILTIN, HIGH);
  }
}

void prep_call() { // prepare array to call https
  strcpy(call, "https://");
}


void read_ser(char *data) { // read data from host system until '/r', i.e. beelogger
  char inChar = 0;
  char inData[255] = "";
  int index = 0;
  int brk = 1;
  unsigned long t = millis();
  digitalWrite(LED_BUILTIN, LOW);
  do {  // read buffers
    while (Serial.available() > 0) {
      delay(2);
      inChar = Serial.read();
      if ((inChar > 0x1F) && (inChar < 0x7B)) { // remove CR,NL
        inData[index] = inChar;
        index++;
        inData[index] = '\0';
      }
      else if (inChar == '\r') {
        brk = 0;
        break; // string ends
      }
    }
  } while (((millis() - t) < 2000) && brk);
  digitalWrite(LED_BUILTIN, HIGH);
  if (brk == 0) {
    Serial.println("OK");
    Serial.flush();
  }
  strcpy(data, inData);
  delay(5);
}

int my_connect(unsigned long timeout) {   // wait for WiFi connection
  unsigned long t = millis();
  do {
    if ((WiFiMulti.run() == WL_CONNECTED)) {
      return 1;
    }
  } while ((millis() - t) < timeout);
  return 0;
}
