// Geändert gegenüber beelogger.de STM32_LORA_260705 (GPL v3 §5a):
//   2026-11-06, desiue (github.com/desiue/beelogger-stm32): Variable FernIntervall (Sendeintervall per Downlink); l_ID 261106.
//   2026-11-07, desiue: ADR aus (LMIC_setAdrMode(0) in STM32_LORA.ino); l_ID 261107.
//   Vollständige Änderungen: docs/aenderungen_gegen_260705.diff

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

    Blackpill STM32F411CE 512k  (WeAct Blackpill V 2.0)
              STM32F401CC 256k
              STM32F401CE 512k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:   https://github.com/stm32duino/Arduino_Core_STM32/wiki/Libraries
    STM32duino Low Power 1.2.4
    STM32duinoRTC  1.3.7


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE F401CC F401CE (WeAct Blackpill)
 
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
// Version 2025    beelogger STM32F103 LORA, bis zu vier Waagen
//                 LORA Frame Counter in BackupRam
//
// verwendet lowpower.shutdown() (STM32 Standby), Strombedarf ca. 10uA
// System macht Reset bei Wakeup, RTC-RAM und EE-Prom zur Datenspeicherung
// timed sleep wenn User Service
// 25.03.2022 LORA
// 19.05.2022 fix call Sensor_Gewicht()
// 01.09.2023 STM32-RTC/LowPower-Lib new Versions
// 30.06.2024 Zuordnung Aux[]
// 29.01.2025 handling recover from low akku voltage "akku_save"
// 26.03.2025 Messzyklussteuerung per Solarspannung / Temperatur
// 29.04.2025 Ablauf wenn Arbeitsschalter aktiv



/****************************************************************/
// Global Libraries
/****************************************************************/
#include "Config_LORA.h"   // Konfiguration eintragen
#include "Config_MULTI.h"  // Konfiguration und Kalibrierung eintragen
#include "STM32_beelogger.h"
#include <stm32yyxx_ll_adc.h>  // analog reading
#include <backup.h>            // RTC Backup ram
#include <STM32LowPower.h>
#include <STM32RTC.h>



char ID_ID[16] = "STM32_LORA_";  //nur Grossbuchstaben,Zahlen, keine Blanks
long l_ID = 261107;  // Firmware-Kennung JJMMTT, bei jeder Version anpassen (Original: 260705)

#define CFG_noassert 1  // supress error messages in LMIC-library


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

//              Temp. STM32, Luftdruck, ZählerRegen/ STM32-VCC
float Aux[3] = { No_Value, No_Value, No_Value };
float DS_Temp = No_Val;
float Licht = No_Value;
float Gewicht[4] = { 0.01, 0.01, 0.01, 0.01 };
float LetztesGewicht[4] = { No_Value, No_Value, No_Value, No_Value };  // store in RTC-BKP
float Batteriespannung = No_Value;
float Solarspannung = No_Value;
float Service = No_Value;

// runtime parameter stored in RTC-BKP
static uint32_t time_on = 0;
uint8_t WeckIntervallMinuten = WeckIntervall;
uint8_t report_info = 0;   // 0 = time-On, 1 = Pegel
uint8_t stuck_switch = 0;  // Service Switch permanent on
uint8_t s_alarm = 0;       // Schwarmalarm
uint8_t akku_save = 0;     // in Akku Save Mode
uint8_t FernIntervall = 0; // Sendeintervall per Downlink (FPort 10), 0 = Firmware-Vorgabe, BKP-Reg 5 Bit 9-14

uint8_t nr_to_measure = 1;    // POR_RST
uint16_t LoRaSeqCounter = 0;  // TTN Sequence numbber


// Interrupt Variable
static uint32_t wake_up_var = 0;
volatile uint8_t ok_sleep = 1;

/****************************************************************/


void setup() {

  //----------------------------------------------------------------
  // Init system when power-on reset or restart
  //----------------------------------------------------------------
  System_Init();
  //----------------------------------------------------------------

  Spannungen_messen();

  //----------------------------------------------------------------
  //  System on
  //----------------------------------------------------------------
  System_On();
  //  init_LoRaSeq(); frame counter in backup-ram
  //----------------------------------------------------------------
}

/**
  @brief Loop()
  not really a loop, since STM32 does a reset on Wakeup
*/
void loop() {

  Spannungen_messen();
  if (Batteriespannung < VAlternativ) akku_save = 1;
  if (Batteriespannung > VSysnormal) akku_save = 0;
  if (Batteriespannung > VMinimum) {
    uint8_t ureq = User_Int_req();
    if (ureq) {  // service switch on? or "time is over"
      Sensor_Temp_Zelle(0);
      Sensor_DHT();
      Sensor_DS18B20();
      Sensor_BME280();
      Sensor_Licht();
      Sensor_Si7021();
      Sensor_SHT31();
      Sensor_Gewicht(0);
      Sensor_Temp_Zelle(1);
      Daten_Senden();  // only when service "off" or "time is over"
    } else {
      Sensor_Gewicht(1);
    }
  }


  System_ShutDown();

  blink_LED(10, 500);  // we will never make it to here
}
