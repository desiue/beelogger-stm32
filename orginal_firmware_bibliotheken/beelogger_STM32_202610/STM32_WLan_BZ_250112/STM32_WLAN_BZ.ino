//----------------------------------------------------------------
// Konfiguration ESP-8266-01 WLAN Modul BZ Version
//----------------------------------------------------------------
// 04.03.2020 STM32 adaption
// 30.06.2024 Zuordnung Aux[]
// 12.01.2025 Send A=1 obsolete

#define ESP_Baudrate 9600
#include <ESP_STM_beelogger.h>
C_WLAN_ESP01 my_esp;

//----------------------------------------------------------------

//----------------------------------------------------------------
// Parameter Datenversand via Http // diese nicht modifizieren
//----------------------------------------------------------------
const char Str_Http[]   PROGMEM = " HTTP/1.1\r\n"; // mit Leerzeichen vorne
const char Str_Con[]    PROGMEM = "Connection: close\r\n\r\n";

const char Str_Pw[]     PROGMEM = "PW=";
const char Str_Id[]     PROGMEM = "&ID=";
const char Str_Ds[]     PROGMEM = "&W=";
const char Str_T_In1[]  PROGMEM = "&T1=";
const char Str_T_In2[]  PROGMEM = "&T2=";
const char Str_T_Out[]  PROGMEM = "&TO=";
const char Str_F_In1[]  PROGMEM = "&F1=";
const char Str_F_In2[]  PROGMEM = "&F2=";
const char Str_F_Out[]  PROGMEM = "&FO=";
const char Str_Li[]     PROGMEM = "&L=";
const char Str_Serv[]   PROGMEM = "&S=";
const char Str_Gew1[]   PROGMEM = "&G1=";
const char Str_Gew2[]   PROGMEM = "&G2=";
const char Str_Chk[]    PROGMEM = "&C=";

const char Str_Sens7[] PROGMEM = "&BienenIn=";
const char Str_Sens8[] PROGMEM = "&BienenOut=";
//----------------------------------------------------------------


void stop_DFUe_device() {
  digitalWrite(ESP_RESET, LOW);    // ESP8266 "off"
  pinMode(ESP_RESET, OUTPUT);
}



//----------------------------------------------------------------
// Funktion ESP8266-01 Setup_WLAN
//----------------------------------------------------------------
uint8_t Setup_WLAN() {
  char parameter[80], data[32];
  uint8_t wlan_con = false;

  digitalWrite(ESP_RESET, HIGH);
  LogDelay(500);

  debugprintlnF("WLAN...");
  debugflush();

  for ( int x = 0; x < Anzahl_AP; x++) {
    if (my_esp.init(ESP_Baudrate)) {
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

      if (my_esp.join(parameter, data, 10000)) { // WLAN verbinden
        my_esp.sleep(ESP_SLEEP_MODE); // 0 = NoSleep, 2 = Modem Sleep

        debugprintlnF("WLAN!"); debugflush();

        AP_nr = x;  // AP_nr merken
        wlan_con = true;
        break;
      }
      else {
        LogDelay(500);
        my_esp.quit();
        LogDelay(500);

        debugprintlnF("Fehler.");

      }
    }
  }
  return (wlan_con);
}
//----------------------------------------------------------------


//----------------------------------------------------------------
// Funktion ESP8266-01 WLAN
//----------------------------------------------------------------
void Daten_Senden() {
  char parameter[80], data[24];
  uint8_t data_send = 0;
  uint8_t retry = 0;

  // Signalstärke AP, braucht ca. 10 Sekunden mit 150mA Strom
  if (report_info) {
    if (AP_nr == 2) {
      strcpy_P(parameter, Access_Point2);
    }
    else {
      strcpy_P(parameter, Access_Point1);
    }
    if (my_esp.signal(parameter)) {         // hole Signalstärke
      strcpy(parameter, my_esp.buffer);
      char cc = ',';
      char *pos = strchr(parameter, cc); // suche ','
      if (pos) {
        int pegel = atoi(pos + 1);
        Service = (float) pegel;
      }

      debugprintF("WLAN [dBm] ");
      debugprintln(Service);
      //debugprintln(my_esp.buffer);
      debugflush();

    }
  }

  do {  // Connect and send, one retry
    strcpy_P(parameter, serverName);
    debugprintln(parameter); debugflush();

    if (my_esp.Connect(parameter)) { // Server verbinden

      debugprintlnF("Connect!");
      debugflush();

      float Checksum =  SensorTemp[Beute1]  +  SensorTemp[Aussenwerte];
      Checksum +=  SensorFeuchte[Beute1]  +  SensorFeuchte[Aussenwerte];
      Checksum +=  Licht +  Gewicht[0] + Service;

      if (my_esp.prep_send(400)) { // fiktive Länge, senden wird mit \0 gestartet

        strcpy_P(parameter, beelogger_pfad); // der GET in einen Stück bis zum '?', sonst geht es nicht !!!
        debugprintln(parameter); debugflush();

        my_esp.send(parameter);
        // erstelle Daten
        strcpy_P(parameter, Str_Pw);
        my_esp.send(parameter);
        strcpy_P(parameter, Passwort);
        my_esp.send(parameter);
        // System ID
        strcpy_P(parameter, Str_Id);
        my_esp.send(parameter);
        strcpy(parameter, ID_ID);
        my_esp.send(parameter);
        debugprintln(parameter); debugflush();
        delay(50);

        //Temp_In
        strcpy_P(parameter, Str_T_In1);
        my_esp.send(parameter);
        dtostrf(SensorTemp[Beute1], 3, 1, data);
        my_esp.send(data);
        debugprintln(SensorTemp[Beute1]); debugflush();

        //TempOut
        strcpy_P(parameter, Str_T_Out);
        my_esp.send(parameter);
        dtostrf(SensorTemp[Aussenwerte], 3, 1, data);
        my_esp.send(data);
        debugprintln(SensorTemp[Aussenwerte]); debugflush();

        //FeuchteIn
        strcpy_P(parameter, Str_F_In1);
        my_esp.send(parameter);
        dtostrf(SensorFeuchte[Beute1], 3, 1, data);
        my_esp.send(data);
        debugprintln(SensorFeuchte[Beute1]); debugflush();

        //FeuchteOut
        strcpy_P(parameter, Str_F_Out);
        my_esp.send(parameter);
        dtostrf(SensorFeuchte[Aussenwerte], 3, 1, data);
        my_esp.send(data);
        debugprintln(SensorFeuchte[Aussenwerte]); debugflush();


        //Licht
        strcpy_P(parameter, Str_Li);
        my_esp.send(parameter);
        dtostrf(Licht, 3, 1, data);
        my_esp.send(data);
        debugprintln(Licht); debugflush();

        //Service
        strcpy_P(parameter, Str_Serv);
        my_esp.send(parameter);
        dtostrf(Service, 3, 1, data);
        my_esp.send(data);
        debugprintln(Service); debugflush();

        //Gewicht
        strcpy_P(parameter, Str_Gew1);
        my_esp.send(parameter);
        dtostrf(Gewicht[0], 4, 2, data);
        my_esp.send(data);
        debugprintln(Gewicht[0]); debugflush();
        delay(50);

        if ( Anzahl_Sensoren_Gewicht == 2) {
          // Temperatursensor Waage
          Checksum +=  DS_Temp;
          strcpy_P(parameter, Str_Ds);
          my_esp.send(parameter);
          dtostrf(DS_Temp, 3, 1, data);
          my_esp.send(data);
          debugprintln(DS_Temp); debugflush();

          Checksum +=  SensorTemp[Beute2];
          strcpy_P(parameter, Str_T_In2);
          my_esp.send(parameter);
          dtostrf(SensorTemp[Beute2], 3, 1, data);
          my_esp.send(data);
          debugprintln(SensorTemp[Beute2]); debugflush();

          Checksum +=  SensorFeuchte[Beute2];
          strcpy_P(parameter, Str_F_In2);
          my_esp.send(parameter);
          dtostrf(SensorFeuchte[Beute2], 3, 1, data);
          my_esp.send(data);
          debugprintln(SensorFeuchte[Beute2]); debugflush();

          Checksum +=  Gewicht[1];
          strcpy_P(parameter, Str_Gew2);
          my_esp.send(parameter);
          dtostrf(Gewicht[1], 4, 2, data);
          my_esp.send(data);
          debugprintln(Gewicht[1]); debugflush();
        }

        //my_esp.send("&A=1");  // Aux-Werte senden; obsolete

#if (Anzahl_Sensor_Luftdruck)
        Checksum += Aux[0];
        my_esp.send("&A1=");  // Luftdruck
        dtostrf(Aux[0], 4, 1, data);
        my_esp.send (data);
        debugprintln(Aux[0]); debugflush();
#endif

        long Check = (long)round(Checksum);

        //BienenIn / Out und Tacho
        debugprintln(BienenIn); debugflush();
        debugprintln(BienenOut); debugflush();
        strcpy_P(parameter, Str_Sens7); //&BienenIn=
        my_esp.send(parameter);

#if Neue_Bienenzaehlfunktion
        debugprintln(BienenInTacho); debugflush();
        Check +=  BienenInTacho;
        ltoa(BienenInTacho, data, 10);
#else   // kompatibel mit alten Daten
        Check +=  BienenIn;
        ltoa(BienenIn, data, 10);
#endif
        my_esp.send(data);

        //BienenOut / -Tacho
        strcpy_P(parameter, Str_Sens8); //&BienenOut
        my_esp.send(parameter);

#if (Anzahl_Sensoren_Bienenzaehler == 0)
        //#error " Dieser Sketch nicht für Aufbauten  OHNE  Bienenzähler !"
#endif

#if Neue_Bienenzaehlfunktion
        debugprintln(BienenOutTacho); debugflush();
        Check +=  BienenOutTacho;
        ltoa(BienenOutTacho, data, 10);
#else   // kompatibel mit alten Daten
        Check +=  BienenOut;
        ltoa(BienenOut, data, 10);
#endif
        my_esp.send(data);

        // immer zaehlwerte übertragen
        Check +=  BienenIn;
        ltoa(BienenIn, data, 10);
        my_esp.send ("&A2=");  // BienenIn
        my_esp.send (data);

        Check +=  BienenOut;
        ltoa(BienenOut, data, 10);
        my_esp.send ("&A3=");  // BienenOut
        my_esp.send (data);

        //Checksumme
        strcpy_P(parameter, Str_Chk);
        my_esp.send(parameter);
        ltoa(Check, data, 10);
        my_esp.send(data);
        // ende Daten
        delay(50);

        strcpy_P(parameter, Str_Http);
        my_esp.send(parameter);
        my_esp.send("Host: ");
        strcpy_P(parameter, serverName);
        my_esp.send(parameter);
        my_esp.send("\r\n");

        strcpy_P(parameter, Str_Con);
        my_esp.send(parameter);

        my_esp.send(0x00); // Startkommando senden
        delay(20);

        char ht_ok[8] = "200 OK";
        if (my_esp.sendCommand(0, 5000, ht_ok)) {

          debugprintlnF(" HTTP OK");

          char _ok_[8] = "ok *";
          if (my_esp.sendCommand(0, 8000, _ok_)) {
            data_send = 1;      // erfolgreich
            retry = 0;

            if (Anzahl_Sensoren_Bienenzaehler) {
              BienenIn = 0;
              BienenOut = 0;
              BienenIntervall = millis();  // Marc: TachoZeit auf Reset
            }

            debugprintlnF(" Quittung OK:");
            debugprintln(my_esp.buffer);

            char *p_buf = strstr(my_esp.buffer, _ok_);  // search string start
            *p_buf = 0;
            uint8_t pos = 0;
            do {
              p_buf --;    // search start of line
              pos++;
            }
            while ( (!(*p_buf == '\n')) && (pos < 8));

            int n = atoi(p_buf); // Konvertiere str in int
            if ((n >= 5) && (n <=30 )) {  // max 30 min.
              SpeicherIntervall = n * 60; //in Sekunden
            }
            else {
              SpeicherIntervall = 300; //in Sekunden
            }

            report_info = 0;
            if (strchr(p_buf, 'P') ) {
              report_info = 1;
            }
          }
        } // http ok
      } // prep_send
    } // Connect, Disconnect mit GET Aufruf


    debugflush();

    if (data_send == 0) { // Connect, Send etc. Fehler

      debugprintln(F("ESP Fehler, -> Reset!"));

      my_esp.quit();
      LogDelay(1000);
      digitalWrite (ESP_RESET, LOW);    // Reset Puls erzeugen
      delay(30);
      digitalWrite (ESP_RESET, HIGH);
      LogDelay(500);
      Setup_WLAN();
    }
    retry++;
  }  while ((data_send == 0) && ( retry < 2));
}
//----------------------------------------------------------------
