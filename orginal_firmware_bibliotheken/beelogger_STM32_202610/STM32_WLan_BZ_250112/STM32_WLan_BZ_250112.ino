/*
   (C) 2024 R.Schick - beelogger.de

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.


    https://github.com/stm32duino

    STM32 Core Version 2.6.0

    https://stm32-base.org/boards/
    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k  (WeAct Blackpill V 2.0)
              STM32F401CC 256k
              STM32F401CE 512k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:     https://github.com/stm32duino/Arduino_Core_STM32/wiki/Libraries
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE F401CC F401CE

    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload method "STM32 Cube Programmer Serial"   ( == FTDI)

    Java installieren
    Stm32cubeprogrammer von STM installieren

    Programmieren über FTDI: PA9,PA10
    STM32F103: Boot0 Stecker auf 1, danach Laden auf 0; Boot1 immer auf 0
               Betrieb: Beide Jumper Richtung USB-Buchse gesteckt.

    STM32F4x1:  Boot drücken und halten, Reset kurz drücken, Boot loslassen

*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de
// Version 2024    beelogger STM32F103/F4x1 WLAN Bienenzähler bis zu zwei Waagen
//
// 30.06.2024 Zuordnung Aux[]
// 07.10.2024 setup_Sensors vor Setup_Wlan


/****************************************************************/
// Global Libraries
/****************************************************************/
#include "Config_WLAN_BZ.h"          // Konfiguration eintragen
#include "Config_BZ.h"               // Konfiguration und Kalibrierung eintragen
#include "STM32_beelogger.h"
#include <stm32yyxx_ll_adc.h>  // analog reading


char ID_ID[24] = "STM32_WLAN_BZ_250112";  //nur Grossbuchstaben,Zahlen, keine Blank


//----------------------------------------------------------------
// Variablen
//----------------------------------------------------------------
#define  No_Val    99.9f  // Vorbelegung, Wert nicht gemessen
#define  No_Value  -1.0f  // Vorbelegung, Wert nicht gemessen

//                     KeinSensor DHT1, DHT2,   Si7021, SHT31a, SHT31b, BMEa,   BMEb,  DS18B20a,DS18B20b
float SensorTemp[12] = {No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val};

//                        KeinSensor  DHT1,    DHT2,     Si7021,   SHT31a,   SHT31b,   BMEa,     BMEb.   KeinSensor KeinSensor
float SensorFeuchte[10] = {No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value};

//              Luftdruck, (vdd) ,   Temp
float Aux[3] = {No_Value, No_Value, No_Value};
float DS_Temp = No_Val;
float Licht = No_Value;
float Gewicht[2] = {No_Value, No_Value};
float LetztesGewicht[2] = {No_Value, No_Value};
float Service = No_Value;
//DateTime aktuell;

uint8_t report_info = 0; // 0 = time-On, 1 = WLan-Pegel

uint8_t SensorAktuell[16];
uint8_t LetzterSensor[Anzahl_Sensoren_Bienenzaehler][16];
uint8_t TunnelZustand[Anzahl_Sensoren_Bienenzaehler][8];
uint8_t TunnelZuvor = 0;

uint8_t AP_nr = 0;
uint32_t LetztesIntervall = 0;
uint32_t LogDelayZeit;
uint32_t BienenIntervall;

int BienenIn = 0;
int BienenOut = 0;
int BienenInTacho = 0;
int BienenOutTacho = 0;

uint32_t SpeicherIntervall = 300; // Speicherintervall / Versand
uint32_t Messzyklus;
uint32_t switch_active = 0;

//----------------------------------------------------------------
void setup() {

  debugbegin(Serial_Baudrate);
  debugprintln(ID_ID);

  //----------------------------------------------------------------
  //  a real pull-down-resistor should be used in addition
  pinMode(WakeUp_Pin, INPUT_PULLDOWN); // Prepare WakeUp pin PA0
  delay(2);
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  // System On
  //----------------------------------------------------------------
  digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  delay(5);
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  // Setup Sensors
  //----------------------------------------------------------------
  setup_sensors();
  //----------------------------------------------------------------


  //----------------------------------------------------------------
  // Setup WLAN ESP8266
  //----------------------------------------------------------------
  stop_DFUe_device();

  Setup_WLAN();
  //----------------------------------------------------------------


}

/**
  @brief Loop()
  this is a real loop, because STM32 does not go in any sleep mode
*/

//################################################################
void loop() {

  Sensor_Temp_Zelle(false);
  Messzyklus = millis();
  Sensor_DS18B20();
  Sensor_Bienen();
  Sensor_DHT();
  Sensor_Bienen();
  Sensor_Si7021();
  Sensor_Bienen();
  Sensor_SHT31();
  Sensor_Bienen();
  Sensor_BME280();
  Sensor_Bienen();
  Sensor_Licht();
  Sensor_Bienen();
  Sensor_Gewicht(false);
  Sensor_Bienen();
  BienenTachoCalc();
  Sensor_Temp_Zelle(true);
  Messzyklus = (millis() - Messzyklus) / 1000;
  if (Messzyklus == 0) Messzyklus = 1;
  Service = Service + (float) Messzyklus;
  Daten_Senden();
  Service = 0.0;
#if (Anzahl_Sensoren_Bienenzaehler == 0)
  //#error " Dieser Sketch nicht für Aufbauten  OHNE  Bienenzähler !"
#endif
  LetztesIntervall = millis();
  while ((millis() - LetztesIntervall) <= (SpeicherIntervall * 1000)) {
    User_Switch_req();
    Sensor_Bienen();
  }

  display_mem_info();
}
//################################################################



//----------------------------------------------------------------
// Funktion LogDelay:
// im idle Bienenzähler abfragen
//----------------------------------------------------------------
void LogDelay(unsigned long Zeit) {

  LogDelayZeit = millis();
  while ((millis() - LogDelayZeit) < (Zeit)) {
    Sensor_Bienen();
  }
}
//----------------------------------------------------------------

/**
  @brief Funktion User_Switch_req

  check if User Switch has been set
  wait preconfigured time, just wait
  if still set after that, ignore switch and continue 

  @param  #define USER_INT_TM   xx minutes
  @return  none
*/
void User_Switch_req() {  // User switch is set

#if (USER_INT_TM > 150)  // Limit Service switch time, important!
#undef USER_INT_TM
#define USER_INT_TM 150
#warning " USER_INT_TM  set to  150 minutes !"
#endif
  uint8_t break_info = 1;

  if (digitalRead(WakeUp_Pin) == true) {      // switch on?

    if (switch_active  < ((USER_INT_TM * 60) / 3)) { // Switch was not on?
      // USER_INT_TM in Minuten, umrechnen in Sekunden, teilen durch delay time
      debugprintln("Arbeitsschalter aktiv: System Stopp!");
      blink_LED(1, 500);  // signal we've noticed

      while ((digitalRead(WakeUp_Pin) == true) && break_info) {   // wait for switch off
        delay(3000);                          // wait
        Service += 3.0;                       // increment Service value
        if (switch_active == 0) {             // first time only
          // User Service
          blink_LED(4, 200);                  // signal we've noticed
          switch_active = 1;
          debugprint ("User! ");
        }
        else {
          // "switch is set", when still on and time is not up
          if (switch_active  < ((USER_INT_TM * 60) / 3 )) {
            switch_active++; // only increment within predefined period
          }
          else {  // time exceeded, stop waiting
            break_info = 0; // stopp while
          }
        }
      }  // while
    }    // switch_active ?
  }
  else {
    switch_active = 0;
  }

  debugprint ("sw:");
  debugprintln (switch_active);
}
//----------------------------------------------------------------


/**
  @brief Funktion blink_LED

  on board LED an Pin PC13 blink

  @param  Anzahl, Zeitdauer an/aus
  @return  none
*/
void blink_LED(uint8_t nmr, uint16_t bl_tm) {
  pinMode(PC13, OUTPUT);
  for (int i = 0; i < nmr; i++) {
    digitalWrite(PC13, LOW);  delay(bl_tm); // LED an
    digitalWrite(PC13, HIGH);  delay(bl_tm);
  }
  pinMode(PC13, INPUT);
}
