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

    STM32 Core Version ab 2.6.0 / 2.10.1

    https://stm32-base.org/boards/
    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE 512k  (WeAct Blackpill V 3.0/3.1)
              STM32F401CC 256k  (WeAct Blackpill V 2.0)
              STM32F401CE 512k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json


    Boardverwalter: Board STM32Cores
    Bibliotheken:   https://github.com/stm32duino/Arduino_Core_STM32/wiki/Libraries
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE F401CE F401CC
    
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

    STM32F4x1:  Boot drücken und halten, Reset kurz drücken, Boot loslassen.

*/


// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de
// Version 2024    beelogger STM32F103, STM32F4x1, LTE, bis zu vier Waagen
//
// verwendet lowpower.shutdown() (STM32 Standby), Strombedarf ca. 10uA
// System macht Reset bei Wakeup, RTC-RAM und EE-Prom zur Datenspeicherung
// timed sleep wenn User Service
// 19.05.2022 fix Schwarmalarm
// 10.06.2022 swarm alarm intervall
// 01.09.2023 STM32-RTC/LowPower-Lib new Versions
// 15.10.2023 use EE-Prom up to 128kByte in STM32F4x1 systems
// 30.06.2024 Zuordnung Aux[]
// 18.07.2024 USE EEProm "round-robin"
// 15.11.2024 check variable Size
// 29.01.2025 handling recover from low akku voltage "akku_save"
// 26.05.2025 working switch behaviour
// 28.05.2025 reset swarmalarm when switch is set

/****************************************************************/
// Global Libraries
/****************************************************************/
#define __ASSERT_USE_STDERR
#include <assert.h>
#include "Config_LTE.h"    // Konfiguration eintragen
#include "Config_MULTI.h"  // Konfiguration und Kalibrierung eintragen
#include "STM32_beelogger.h"
#include <stm32yyxx_ll_adc.h>  // analog reading
#include <backup.h>            // RTC Backup ram
#include <STM32LowPower.h>
#include <STM32RTC.h>

#if _LTE_TLS == 1
char ID_ID[24] = "STM32_LTES_260705";  //nur Grossbuchstaben,Zahlen, keine Blank
#else
char ID_ID[24] = "STM32_LTER_260705";  //nur Grossbuchstaben,Zahlen, keine Blank
#endif

/*Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();

/****************************************************************/
// Variablen
/****************************************************************/
#define No_Val 99.9f    // Vorbelegung, Wert nicht gemessen
#define No_Value -1.0f  // Vorbelegung, Wert nicht gemessen

//                              DHT1,   DHT2,   Si7021, SHT31a, SHT31b, BMEa,   BMEb,  DS18B20a,DS18B20b,DS18B20c,DS18B20d
float SensorTemp[12] = { No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val };

//                                  DHT1,      DHT2,     Si7021,   SHT31a,   SHT31b,   BMEa,     BMEb    , leer    , leer
float SensorFeuchte[12] = { No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value };

//              Luftdruck, ZählerRegen/ STM32-VCC, Temp. STM32
float Aux[3] = { No_Value, No_Value, No_Value };
float DS_Temp = No_Val;
float Licht = No_Value;
float Gewicht[4] = { No_Value, No_Value, No_Value, No_Value };
float LetztesGewicht[4] = { No_Value, No_Value, No_Value, No_Value };  // store in RTC-BKP
float Batteriespannung = No_Value;
float Solarspannung = No_Value;
float Service = 0.0;


// runtime parameter stored in RTC-BKP
uint32_t time_on = 0;
uint16_t my_counter = 0;        // Anzahl Messungen
uint16_t my_counter_start = 0;  // Anzahl Messungen
uint8_t WeckIntervallMinuten = WeckIntervall_default;
uint8_t next_send_hour = 0;  // "int" wegen Berechnungen
uint8_t report_info = 0;     // 0 = time-On, 1 = Pegel ... always 0 for 7670
uint8_t send_cycle = 1;      // Normaler Sendezyklus
uint8_t snd_dly = 0;         // send delay
uint8_t stuck_switch = 0;    // Service Switch permanent on
uint8_t s_alarm = 0;         // Schwarmalarm
uint8_t akku_save = 0;       // in Akku Save Mode

uint16_t nr_to_measure = 0;  // Testsenden bei Power-On Reset
// Interrupt Variable
static uint32_t wake_up_var = 0;
volatile uint8_t ok_sleep = 1;

char DatenString[256];  // Datenbuffer
/****************************************************************/

void setup() {


  //----------------------------------------------------------------
  // Init system when power-on reset or restart
  //----------------------------------------------------------------
  System_Init();
  //----------------------------------------------------------------
  assert(sizeof(ID_ID) < 20);
  assert(sizeof(serverName) < 32);
  assert(sizeof(beelogger_pfad) < 48);

  //----------------------------------------------------------------
  // Init rain counter
  //----------------------------------------------------------------
  Init_Rain();
}

/**
  @brief Loop()
  not really a loop, because STM32 does a reset on Wakeup
*/
void loop() {
  uint8_t alarm;
  uint8_t user_sw;

  if(Spannungen_messen()){
    //----------------------------------------------------------------
    //  System on
    //----------------------------------------------------------------
    System_On();
    //----------------------------------------------------------------

    user_sw = User_Int_req();
    if (user_sw) {  // 0 = service switch not set or switch time is over
      Sensor_Temp_Zelle(0);
      Sensor_DHT();
      Sensor_DS18B20();
      Sensor_BME280();
      Sensor_Licht();
      Sensor_Si7021();
      Sensor_SHT31();
      alarm = Sensor_Gewicht(0);
      sw_alarm(alarm);
      Sensor_Temp_Zelle(1);
      Sensor_Regen();
      bee_FFT();
    } else {
      s_alarm = 0;  // reset swarmalarm when user switch
      Sensor_Gewicht(true);
    }
    Daten_Senden(user_sw);
    sw_alarm_int();
  }

  System_ShutDown();
  // back from sleep ...
#if Anzahl_Sensor_Regen
  blink_LED(1, 500);  // signal we've noticed
  Setup_Serial();
#endif
}
//#########################################################
