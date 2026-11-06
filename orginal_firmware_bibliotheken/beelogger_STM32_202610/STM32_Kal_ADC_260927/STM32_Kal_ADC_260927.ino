/*
   (C) 2024 R.Schick  - beelogger.de

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program. If not, see <http://www.gnu.org/licenses/>.


    http://github.com/stm32duino/wiki/wiki/Libraries

    STM32 Core Version 2.6.0

    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k
              STM32F401CC 256k
              STM32F401CE 512k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE F401CC F401CE (WeAct Blackpill)

    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload methoed "STM32 Cube Programmer Serial"   ( == FTDI)

  // beelogger.de - Arduino Datenlogger für Imker
  // Erläuterungen dieses Programmcodes unter http://beelogger.de
*/


long Kalib_Spannung = 4040; //Hier ist der gemessene Wert der Akkuspannung in Millivolt einzutragen


// ******************** Ende Konfiguration ***************

#define Batt_Pin  PB0
#define Solar_Pin  PB1

//----------------------------------------------------------------
// Aktivierung der Stromversorgung für Sensoren, Module
// WLAN und LTE Modul stopp
//----------------------------------------------------------------
#define Power_Pin     PB5  // PB5
#define Stop_LTE_WLAN PB6  // PB6
//----------------------------------------------------------------

#define WakeUp_Pin  PA0

#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE))
#define MONITOR_RX_TX  Serial1  // STM32F4x1C  FTDI-Serial
#else
#define MONITOR_RX_TX  Serial
#endif


// analog reading
#include "stm32yyxx_ll_adc.h"
#include <STM32LowPower.h>
#include <STM32RTC.h>
/* Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();

float Batteriespannung = 999.99;
int32_t Kalib_Bitwert;
uint32_t time_on = 0;


//-----------------------------------------------------------------------------
void setup() {
  //char s[128]; // for sprintf
  uint8_t j;
  int32_t val;

  //----------------------------------------------------------------
  // Setup RTC:
  // Select RTC clock source: LSI_CLOCK, LSE_CLOCK or HSE_CLOCK.
  // By default the LSI is selected as source.
  // LSE = 32kHz clock (RTCCLK),  LSI = 40kHz CPU Clock (IWDGCLK), HSE_Clock = CPU Clock (PLLCLK/128)
  //----------------------------------------------------------------
  rtc_stm.setClockSource(STM32RTC::LSE_CLOCK);  // 32kHz source for RTC
  rtc_stm.begin(false);                         // initialize RTC 24H format
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  //  Sleep Mode Interrupt
  //----------------------------------------------------------------
  pinMode(WakeUp_Pin, INPUT_PULLDOWN);
  delay(10);

  LowPower.begin();
  // Attach a wakeup from RTC. call of deepsleep, shutdown must not use any time
  //LowPower.enableWakeupFrom(&rtc_stm, WakeUpRTC, &time_on);
  // Attach a wakeup from User Service Switch, WakeUp_Pin must be Pin PA0
  LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING);
  //----------------------------------------------------------------

  digitalWrite(Stop_LTE_WLAN, LOW);
  pinMode(Stop_LTE_WLAN, OUTPUT);
  digitalWrite(Stop_LTE_WLAN, HIGH);

  digitalWrite(Power_Pin, LOW);
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);


  MONITOR_RX_TX.begin(9600);
  blink_STM32(2);
  while (!MONITOR_RX_TX) {}; // Wait for serial port open
  MONITOR_RX_TX.println("beelogger STM32 Kalibrierung ADC 27.09.2026\n");
  MONITOR_RX_TX.print("AD-Wandler Resolution (bits): 12");
  MONITOR_RX_TX.flush();

  delay(500);

  MONITOR_RX_TX.println("\n ");

  reset_rtc_ram();  // preset backup ram

  MONITOR_RX_TX.println(" ");

  if (Kalib_Spannung == 0) {
    MONITOR_RX_TX.println("Kein Wert fuer 'Kalib_Spannung' eingetragen.");
    MONITOR_RX_TX.println("Die Kalibrierung kann nicht durchgefuehrt werden.");
    MONITOR_RX_TX.println("System sleep;  Reset to wake up");
    MONITOR_RX_TX.flush();
    LowPower.sleep(0);      // Serial connection to Arduino IDE still active
  }

  analogReadResolution(12);
  delay(100);

  internals();

  val = 0;
  for (j = 0 ; j < 16; j++) {
    val += analogRead(Batt_Pin);
    MONITOR_RX_TX.print(val);
    MONITOR_RX_TX.print("  ");
  }
  Kalib_Bitwert = val / 16;
  MONITOR_RX_TX.println();  MONITOR_RX_TX.println();
  MONITOR_RX_TX.print("Hinterlegter Wert fuer 'Kalib_Spannung': ");
  MONITOR_RX_TX.println(Kalib_Spannung);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print("Gemessener Wert fuer 'Kalib_Bitwert': ");
  MONITOR_RX_TX.println(Kalib_Bitwert);
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();

}
//-----------------------------------------------------------------------------


//#################################
void loop() {
  MONITOR_RX_TX.println (" Die Zeile für die Konfiguration:");
  MONITOR_RX_TX.println();
  MONITOR_RX_TX.print ("const long Kalib_Spannung =  ");
  MONITOR_RX_TX.print (Kalib_Spannung);
  MONITOR_RX_TX.println (";    // Hier ist der Wert aus der Kalibrierung einzutragen");
  MONITOR_RX_TX.print ("const long Kalib_Bitwert  =  ");
  MONITOR_RX_TX.print (Kalib_Bitwert);
  MONITOR_RX_TX.println (";    // Hier ist der Wert aus der Kalibrierung einzutragen");
  MONITOR_RX_TX.println ("\n");
  delay(7000);
  blink_STM32(3);
  internals();
  bat_spg();
  MONITOR_RX_TX.println("System sleep;  Reset to wake up");
  MONITOR_RX_TX.flush();
  LowPower.shutdown(0);      // Serial lost
}
//#################################


void bat_spg() {
  int32_t val;
  int32_t bits_Messung;

  val = analogRead(Solar_Pin);
  val = 0;
  for ( uint8_t j = 0 ; j < 16; j++) {
    val += analogRead(Solar_Pin);
    //Serial.print(val);
    //Serial.print("   ");
  }
  bits_Messung = val / 16;
  MONITOR_RX_TX.print(bits_Messung);
  MONITOR_RX_TX.print("   ");

  val = analogRead(Batt_Pin);
  val = 0;
  for ( uint8_t j = 0 ; j < 16; j++) {
    val += analogRead(Batt_Pin);
    //Serial.print(val);
    //Serial.print("  ");
  }
  MONITOR_RX_TX.print(" ");

  bits_Messung = val / 16;
  MONITOR_RX_TX.println(bits_Messung);
  MONITOR_RX_TX.println(" ");
  float Batteriespannung = (map(bits_Messung, 0, Kalib_Bitwert, 0, Kalib_Spannung)) / 1000.0;

  MONITOR_RX_TX.print("Die mit dieser Kalibrierung ermittelte Akkuspannung betraegt: ");
  MONITOR_RX_TX.print(Batteriespannung);
  MONITOR_RX_TX.println(" V");
  MONITOR_RX_TX.println(); MONITOR_RX_TX.println();
}

void internals() {

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)
  //#pragma message "Blackpill F4x1C"
  // Temperature (in °C) = {(VSENSE – V25) / Avg_Slope} + 25
  // V25 = VSENSE value for 25° C
  // Avg_Slope = average slope of the temperature vs. VSENSE curve (given in mV/°C or μV/°C)
#define VREF_INT        1.21    // typical internal reference voltage at 25 deg. celsius 1.20; 1.18 - 1.24
#define Temp_Sensor_V25 0.76     // typical voltage at 25 deg. celsius; 0.76
#define Temp_Sens_Slope 0.0025   // slope of the sensor in V/deg. celsius; typ. 2.5mV/deg. cel
#else
  //#pragma message "Bluepill F103C8/CB"
  // Temp_Sensor_V25 = 1.43 and Temp_Sens_Slope = 0.0043 parameters come from F103 datasheet - ch. 5.9.13
  // and need to be calibrated for every chip (large fab parameters variance)
#define VREF_INT        1.20    // typical internal reference voltage at 25 deg. celsius 1.20; 1.16 - 1.24
#define Temp_Sensor_V25 1.41     // typical voltage at 25 deg. celsius 1.43; 1.34 - 1.52
#define Temp_Sens_Slope 0.0043   // slope of the sensor in V/deg. celsius; typ. 4.3mV/deg. cel
#endif

  // reading STM32-V-Ref and V-Temperatur
  int32_t bits_ATemp = analogRead(ATEMP);
  int32_t bits_VRef = analogRead(AVREF);
  // reading Vdd by utilising the internal 1.20V VREF
  float vdd = VREF_INT * 4096.0 / bits_VRef;
  float Temp_STM = (Temp_Sensor_V25 - (vdd / 4096.0 * bits_ATemp)) / Temp_Sens_Slope + 25.0;

  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print("STM32-Vdd. [V]: ");
  MONITOR_RX_TX.println(vdd);
  MONITOR_RX_TX.print("STM32-Temperatur [C]: ");
  MONITOR_RX_TX.println(Temp_STM);
  MONITOR_RX_TX.println(" ");
}
//----------------------------------------------------------------
// Do some blink STM32
//----------------------------------------------------------------
void blink_STM32(byte nmr) {
  digitalWrite(PC13, HIGH);  // LED aus
  pinMode(PC13, OUTPUT);  delay(5);
  for (int i = 0; i < nmr; i++) {
    digitalWrite(PC13, LOW);  delay(500); // LED an
    digitalWrite(PC13, HIGH);  delay(500);
  }
  pinMode(PC13, INPUT);
}
//----------------------------------------------------------------


//----------------------------------------------------------------
#include <backup.h>            // RTC Backup ram

void reset_rtc_ram() {

  enableBackupDomain();   // RTC Backup Registers
  uint32_t reg = 0;

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7

  setBackupRegister(1, reg);
  setBackupRegister(2, reg);
  setBackupRegister(3, reg);
  setBackupRegister(4, reg);
  setBackupRegister(5, reg);

  setBackupRegister(8, reg);
  setBackupRegister(9, reg);

#else  // STM32F4x1

  setBackupRegister(1, reg);
  setBackupRegister(2, reg);
  setBackupRegister(3, reg);
  setBackupRegister(4, reg);
  setBackupRegister(5, reg);
  setBackupRegister(6, reg);
  setBackupRegister(7, reg);
  setBackupRegister(8, reg);
  setBackupRegister(9, reg);
#endif
}
//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion WakeUp on Interrupt
//----------------------------------------------------------------
void WakeUp() {
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  rtc_stm.detachInterrupt();
}
//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion WakeUp on RTC Interrupt
//----------------------------------------------------------------
void WakeUpRTC(void * data) {
  UNUSED(data);
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  rtc_stm.detachInterrupt();
}
//----------------------------------------------------------------

/**
  @brief Funktion save_data_from_ram()

  save globals vars to RTC-RAM
  save year, month, day since rtc does not keep them during shutdown

  @param none
  @return none
*/


void preset_backup_ram() {
  enableBackupDomain();   // RTC Backup Registers
  // setBackupRegister(uint32_t index, uint32_t value)  // works only with value in uint16, but in uint32_t format
  // setBackupRegister(0, reg); register 0 is used by the STM32-RTC Lib V 1.1.0
  uint32_t reg = 0;

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7

  setBackupRegister(1, reg);
  setBackupRegister(2, reg);
  setBackupRegister(3, reg);
  setBackupRegister(4, reg);
  setBackupRegister(5, reg);

  setBackupRegister(8, reg);
  setBackupRegister(9, reg);

#else  // STM32F4x1

  setBackupRegister(1, reg);
  setBackupRegister(2, reg);
  setBackupRegister(3, reg);
  setBackupRegister(4, reg);
  setBackupRegister(5, reg);
  setBackupRegister(6, reg);
  setBackupRegister(7, reg);
  setBackupRegister(8, reg);
  setBackupRegister(9, reg);
#endif
}
