/*
   (C) 2022 R.Schick - beelogger.de

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

    STM32 Core Version 2.1.0

    https://stm32-base.org/boards/
    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k  (WeAct Blackpill V 2.0)
              STM32F401CC 256k


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
    BlackPill F411CE

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

    STM32F4x1:  Boot drücken und alten, Reset kurz drücken, Boot loslassen
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de
// Version 2023    beelogger STM32F103/F4x1 WLAN bis zu vier Waagen
//
// verwendet lowpower.shutdown() (STM32 Standby), Strombedarf ca. 10uA
// System macht Reset bei Wakeup, RTC-RAM und EE-Prom zur Datenspeicherung
// timed sleep wenn User Service
// 19.05.2022 fix Schwarmalarm
// 10.06.2022 swarm alarm intervall
// 01.09.2023 STM32-RTC/LowPower-Lib new Versions


/****************************************************************/
// Global Libraries
/****************************************************************/
#include "Config_WLAN.h"     // Konfiguration eintragen
#include "Config_LTE.h"     // Konfiguration eintragen
#include "Config_MULTI.h"    // Konfiguration und Kalibrierung eintragen
#include "STM32_beelogger_twin.h"
#include <stm32yyxx_ll_adc.h>  // analog reading
#include <backup.h>            // RTC Backup ram
#include <STM32LowPower.h>
#include <STM32RTC.h>


char ID_ID[] = "STM32_WLAN_LTE_230928";  //nur Grossbuchstaben,Zahlen, keine Blank


/*Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();

/****************************************************************/
// Variablen
/****************************************************************/
const float No_Val = 99.9f;  // Vorbelegung, Wert nicht gemessen
const float No_Value = -1.0f;  // Vorbelegung, Wert nicht gemessen

//                              DHT1,   DHT2,   Si7021, SHT31a, SHT31b, BMEa,   BMEb,  DS18B20a,DS18B20b,DS18B20c,DS18B20d
float SensorTemp[12] = {No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val};

//                                  DHT1,      DHT2,     Si7021,   SHT31a,   SHT31b,   BMEa,     BMEb    , leer    , leer
float SensorFeuchte[12] = {No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value};

//              Temp. STM32, Luftdruck, ZählerRegen/ STM32-VCC
float Aux[3] = {No_Value, No_Value, No_Value};
float DS_Temp = No_Val;
float Licht = No_Value;
float Gewicht[4] = {No_Value, No_Value, No_Value, No_Value};
float LetztesGewicht[4] = {No_Value, No_Value, No_Value, No_Value};
float Batteriespannung = No_Value;
float Solarspannung = No_Value;
float Service = 0.0;


// runtime parameter stored in RTC-ram
static uint32_t time_on = 0;
static uint32_t wake_up_var = 0;
uint8_t WeckIntervallMinuten = WeckIntervall_default;
uint8_t my_counter = 0;      // Anzahl Messungen
uint8_t next_send_hour = 0;  // "int" wegen Berechnungen
uint8_t report_info = 0;     // 0 = time-On, 1 = Pegel
uint8_t send_cycle = 1;      // Normaler Sendezyklus
uint8_t snd_dly = 0;         // send delay
uint8_t stuck_switch = 0;    // Service Switch permanent on
uint8_t s_alarm = 0;         // Schwarmalarm

uint8_t nr_to_measure = 0;   // Testsenden bei Power-On Reset
// Interrupt Variable
volatile uint8_t ok_sleep;

char DatenString[_Daten_Satz_Len];  // Datenbuffer
/****************************************************************/



void setup() {

  //----------------------------------------------------------------
  // Init system when power-on reset or restart
  //----------------------------------------------------------------
  System_Init();
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  // Init rain counter
  //----------------------------------------------------------------
  Init_Rain();
}

/**
  @brief Loop()
  Systems using Rain detectors, really loop, because STM32 is "only" in deepSleep
*/

void loop() {
  uint8_t  s_alarm;
  
  Spannungen_messen();

  if (Batteriespannung > VMinimum) {
    //----------------------------------------------------------------
    //  System on
    //----------------------------------------------------------------
    System_On();
    //----------------------------------------------------------------

    uint8_t ureq = User_Int_req();
    if ( ureq ) {         // service switch on?
      Sensor_Temp_Zelle(0);
      Sensor_DHT();
      Sensor_DS18B20();
      Sensor_BME280();
//      Sensor_BMP280();
      Sensor_Licht();
      Sensor_Si7021();
      Sensor_SHT31();
      s_alarm = Sensor_Gewicht(0);
      sw_alarm(s_alarm);
      Sensor_Temp_Zelle(1);
      Sensor_Regen();
      bee_FFT();
    }
    else {
      Sensor_Gewicht(true);
    }
    Daten_Senden(ureq);
    sw_alarm_int();
  }

  System_ShutDown();
  // back from sleep ...
  Setup_Serial();
}
//#########################################################
