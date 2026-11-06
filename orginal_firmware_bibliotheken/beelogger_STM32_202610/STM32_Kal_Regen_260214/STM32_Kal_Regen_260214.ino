/*
   (C) 2020 R.Schick - beelogger.de

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
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de

//----------------------------------------------------------------
// Konfiguration
//----------------------------------------------------------------

#define Anzahl_Sensor_Regen    1    // 0 = aus,  1= An


//----------------------------------------------------------------
// Ende Konfiguration
//----------------------------------------------------------------

#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE))
#define MONITOR_RX_TX  Serial1
#else
#define MONITOR_RX_TX  Serial
#endif


#include <STM32LowPower.h>
#include <STM32RTC.h>
/* Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();

#define Power_Pin PB5  //  PB5   PB7
#define Stop_GSM_WLAN PB6  //PB6  PB10
#define WakeUp_Pin  PA0

extern volatile uint16_t  Rain_Counter;

//volatile int alarmMatch_counter = 0;
//static uint32_t atime = 1;
float Aux[3]; // unused

void setup(void) {

  rtc_stm.setClockSource(STM32RTC::LSE_CLOCK);  // 32kHz source for RTC
  rtc_stm.begin(false);                         // initialize RTC 24H format
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  //  Sleep Mode
  //----------------------------------------------------------------
  pinMode(WakeUp_Pin, INPUT_PULLDOWN);
  delay(10);
  LowPower.begin();
  //LowPower.enableWakeupFrom(&rtc_stm, alarmMatch, &atime);

  
  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {};
  delay(1000);
  MONITOR_RX_TX.println(F("Niederschlagsmesser  Test   Version 14.02.2026"));
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();


  //digitalWrite(Stop_GSM_WLAN, LOW);
  pinMode(Stop_GSM_WLAN, OUTPUT);
  digitalWrite(Stop_GSM_WLAN, LOW);
  
  //digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);
  delay(100);

  if (Anzahl_Sensor_Regen)  {
    Init_Rain();
  }
  else {
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.println("Test Niederschlagsmesser in der Sketch-Konfiguration nicht aktiviert");
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.flush();
  }
}

//#################################################
void loop(void) {
  if (Anzahl_Sensor_Regen)  {
    MONITOR_RX_TX.println("Test Niederschlagsmesser:");
    MONITOR_RX_TX.println("  warte auf Signal / Interrupt: ");
    MONITOR_RX_TX.flush();
    do {
      LowPower.sleep();
      delay(50);
      MONITOR_RX_TX.print("Zähler Niederschlagsmesser: ");
      MONITOR_RX_TX.println(Rain_Counter);
      MONITOR_RX_TX.flush();
    } while (1 == 1);
  }
  else {
    MONITOR_RX_TX.println("Sleep forever! ");
    MONITOR_RX_TX.flush();
    LowPower.sleep();
  }
}
//#################################################
