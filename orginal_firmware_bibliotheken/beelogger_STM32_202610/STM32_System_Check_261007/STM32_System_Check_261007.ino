/*
   (C) 2023 R.Schick - beelogger.de

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
// Version 2026

#include "STM32_Check_config.h"
// analog reading
#include <stm32yyxx_ll_adc.h>
#include <STM32LowPower.h>
#include <STM32RTC.h>
#include <Wire.h>
#include "DateTime.h"  // to use __DATE__, __TIME__

/* Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();

uint8_t i2c_device[16] = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};
uint8_t System_with_DS3231 = 0;
float TempSys = -99.9;
char DatenString[70];

// Variables for RTC configurations
volatile int alarmMatch_counter = 0;
static uint32_t atime = 1;


//--------------------------------------------------------------
void setup(void) {

  //----------------------------------------------------------------
  // Setup RTC:
  // Select RTC clock source: LSI_CLOCK, LSE_CLOCK or HSE_CLOCK.
  // By default the LSI is selected as source.
  // LSE = 32kHz clock (RTCCLK),  LSI = 40kHz CPU Clock (IWDGCLK), HSE_Clock = CPU Clock (PLLCLK/128)
  //----------------------------------------------------------------
  rtc_stm.setClockSource(STM32RTC::LSE_CLOCK);  // 32kHz source for RTC
  rtc_stm.begin(false);                         // initialize RTC 24H format
  rtc_stm.disableAlarm();                       // may be User WakeupPin Interrupt, disable rtc Alarm
  //----------------------------------------------------------------
  __HAL_PWR_CLEAR_FLAG(PWR_FLAG_WU); // Clear WakeUp Flag
  __HAL_PWR_CLEAR_FLAG(PWR_FLAG_SB); // Clear Standby Flag
  __HAL_RCC_CLEAR_RESET_FLAGS();
  LowPower.begin();
  LowPower.enableWakeupFrom(&rtc_stm, WakeUpRTC, &atime);
  //----------------------------------------------------------------
  //  Sleep Mode Interrupt
  //----------------------------------------------------------------
#if Allow_WakeUp_Switch
  pinMode(WakeUp_Pin, INPUT_PULLDOWN);
  delay(10);
  LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING, SHUTDOWN_MODE);
#endif
  MONITOR_RX_TX.begin(9600);
  blink_LED(3, 500);
  while (!MONITOR_RX_TX) {};

  MONITOR_RX_TX.println(F("\n\nSTM32 System Check  Version 10.07.2026\n\r"));
#if defined(ARDUINO_BLACKPILL_F411CE)
  MONITOR_RX_TX.println("STM32F411CE");
#endif
#if defined(ARDUINO_BLACKPILL_F401CC)
  MONITOR_RX_TX.println("STM32F401CC");
#endif
#if defined(ARDUINO_BLACKPILL_F401CE)
  MONITOR_RX_TX.println("STM32F401CE");
#endif
#if defined(ARDUINO_BLUEPILL_F103CB)
  MONITOR_RX_TX.println("STM32F103CB");
#endif
#if defined(ARDUINO_BLUEPILL_F103C8)
  MONITOR_RX_TX.println("STM32F103C8");
#endif

  MONITOR_RX_TX.println("\n ");

  get_date_from_ram();
  display_time();

  DateTime sketch_create = DateTime(__DATE__, __TIME__);
  uint32_t sk_cr_tim = sketch_create.getEpoch();         // Sketch create_time
  uint32_t sys_tim = rtc_stm.getEpoch();                 // get current System time
  if (sk_cr_tim > (sys_tim + 20)) {
    rtc_stm.setEpoch(sk_cr_tim, 0); // set system time if needed
    MONITOR_RX_TX.println("Sketch Time is set to system");
    display_time();
    save_date_to_ram();  // save Date to backup
  }

  MONITOR_RX_TX.println(" ");

  MONITOR_RX_TX.print(F(" SIM Pin ")); MONITOR_RX_TX.print(SIM_Power_Pin);
  MONITOR_RX_TX.println(F(" low"));
  digitalWrite(SIM_Power_Pin, LOW);  // GSM off
  pinMode(SIM_Power_Pin, OUTPUT);
  digitalWrite(SIM_Power_Pin, LOW);  // GSM off
  delay(5);

  MONITOR_RX_TX.print(F(" Power-On using Pin: ")); MONITOR_RX_TX.println(Power_Pin);
  MONITOR_RX_TX.println(" ");
  digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  delay(5);
  MONITOR_RX_TX.flush();
  analogReadResolution(12);
  delay(100);

  internals();
  bat_adc_read();

  Sensor_HX711();
  Sensor_DHT();

  discoverOneWireDevices();
  Sensor_DS18B20();

  discoverI2CDevices();
  test_i2c_devices();
  dfue();

  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
  //  MONITOR_RX_TX.end();
}
//--------------------------------------------------------------


//#################################################
void loop(void) {

  display_mem_info();

  if (Power_off_at_End == 1) {
    MONITOR_RX_TX.println(F("\nperipherie power off"));
    digitalWrite(Power_Pin, LOW);
    digitalWrite(HX711_SCK[0], LOW);
    digitalWrite(HX711_SCK[1], LOW);
    digitalWrite(DHT_1, LOW);
    digitalWrite(DHT_2, LOW);
    delay(300);
  }


#if 0
  MONITOR_RX_TX.println(F("Sleep forever ! "));
  MONITOR_RX_TX.flush();
  MONITOR_RX_TX.end();
  LowPower.shutdown();   // Serial connection is turned off; causes reset on Wakeup

#else
  MONITOR_RX_TX.println(F("Sleep 30 sec. ! "));
  MONITOR_RX_TX.flush();
  MONITOR_RX_TX.end();
  // Configure alarm in 30 seconds
  rtc_stm.setAlarmEpoch( rtc_stm.getEpoch() + 30);
  LowPower.deepSleep();  // Serial connection to Arduino IDE is lost
  //LowPower.sleep();      // Serial connection to Arduino IDE still active
  //LowPower.idle();       // Serial connection to Arduino IDE still active

  digitalWrite(Power_Pin, HIGH);
  blink_LED(3, 300);
  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {};
  MONITOR_RX_TX.println(F("\nAwake...\n"));

  internals();
  bat_adc_read();
  Sensor_HX711();
  Sensor_DHT();
  Sensor_DS18B20();
  discoverI2CDevices();
  test_i2c_devices();
#endif
}
//#################################################

/**
  @brief Funktion display_time() Anzeige Uhrzeit als Debug Info
  @param  none
  @return  none
*/
#include "time.h"
void display_time() {
  char buff[64];
  time_t wakeup_time = rtc_stm.getEpoch();
  strftime (buff, 60, "%d.%m.%Y %H:%M:%S", localtime (&wakeup_time));
  MONITOR_RX_TX.print("Datum:    "); MONITOR_RX_TX.println(buff);
}

/**
  @brief Funktion bat_adc_read
  read voltage at bat measure pin
  @param  none
  @return  none
*/
void bat_adc_read() {
  int16_t  Bitwert = analogRead(Batterie_messen );
  Bitwert = 0;
  for (byte j = 0 ; j < 16; j++) {
    Bitwert += analogRead(Batterie_messen );
  }
  Bitwert = Bitwert / 16;
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print(F(" ADC-bits: "));
  MONITOR_RX_TX.println(Bitwert);
  MONITOR_RX_TX.print(F(" Akku-Spannung ca: "));
  float spg = 3.3 * (float)Bitwert;
  spg = spg / 1365.0; // 4095.0 / 3.0 => 1Meg / 470K
  MONITOR_RX_TX.print(spg, 1);
  MONITOR_RX_TX.println(" V");
}

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

/**
  @brief Funktion WakeUp,  Interrupt-Routine zu WakeUpPin

  setzt globale Variable

  @param none
  @return none
*/
void WakeUp() {
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  //ok_sleep = 2;
}

/**
  @brief Funktion WakeUpRTC,  Interrupt-Routine zu wakeup by rtc alarm/interrupt

  setzt globale Variable

  @param none
  @return none
*/
void WakeUpRTC(void * data) {
  UNUSED(data);
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  //ok_sleep = 3;
}
/**
  @brief internals()
  measure STM32Fxyz internal voltage and temperature
  @param none
  @return none
*/
void internals() {
  // reading STM32-V-Ref and V-Temperatur
  int32_t bits_ATemp = analogRead(ATEMP);
  int32_t bits_VRef = analogRead(AVREF);

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)
  //#pragma message "Blackpill F4x1C"
  // Temperature (in °C) = {(VSENSE – V25) / Avg_Slope} + 25
  // V25 = VSENSE value for 25° C
  // Avg_Slope = average slope of the temperature vs. VSENSE curve (given in mV/°C or μV/°C)
#define Temp_Sensor_V25 0.76     // typical voltage at 25 deg. celsius 1.43; 0.76
#define Temp_Sens_Slope 0.0025   // slope of the sensor in V/deg. celsius; typ. 2.5mV/deg. cel
#else
  //#pragma message "Bluepill F103C8/CB"
  // Temp_Sensor_V25 = 1.43 and Temp_Sens_Slope = 0.0043 parameters come from F103 datasheet - ch. 5.9.13
  // and need to be calibrated for every chip (large fab parameters variance)
#define Temp_Sensor_V25 1.41     // typical voltage at 25 deg. celsius 1.43; 1.34 - 1.52
#define Temp_Sens_Slope 0.0043   // slope of the sensor in V/deg. celsius; typ. 4.3mV/deg. cel
#endif
  // reading Vdd by utilising the internal 1.20V VREF
  float vdd = 1.20 * 4096.0 / bits_VRef;
  // Temp_Sensor_V25 = 1.43 and Temp_Sens_Slope = 0.0043 parameters come from F103 datasheet - ch. 5.9.13
  // and need to be calibrated for every chip (large fab parameters variance)
  float Temp_STM = (Temp_Sensor_V25 - (vdd / 4096.0 * bits_ATemp)) / 0.0043 + 25.0;

  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print("Vdd. [V]: ");
  MONITOR_RX_TX.println(vdd);
  MONITOR_RX_TX.print("T-STM32 [C]: ");
  MONITOR_RX_TX.println(Temp_STM);
  MONITOR_RX_TX.println(" ");
}

//----------------------------------------------------------------
#include <backup.h>            // RTC Backup ram
void get_date_from_ram() {
  //   uint32_t getBackupRegister(uint32_t index)  // only 16 bit values
  uint32_t reg;  // register: only 16 bits may be used
  uint32_t tty;  // year
  uint32_t ttm;  // month
  uint32_t ttd;  // day

  // register 0 is used by the STM32-Libs
  reg = getBackupRegister(1);
  ttm =  (reg & 0x00ff);
  tty =  (reg >> 8);

  reg = getBackupRegister(2);
  ttd = (uint8_t) (reg >> 8);
  if ((tty > 21) && (tty < 35) && (ttm < 13) && (ttd < 32)) {
    rtc_stm.setDate((uint8_t)ttd, (uint8_t)ttm, (uint8_t)tty); // restore Date from Backup
  }
}

void save_date_to_ram() {
  uint8_t my_counter = 0;

  enableBackupDomain();   // RTC Backup Registers
  // read Date and save since it is not maintained in Shutdown-Mode (STM32-Stop/Standby)
  // Date/Time is requested from server and set upon receive
  uint16_t tty = (uint16_t) rtc_stm.getYear();
  uint16_t ttm = (uint16_t) rtc_stm.getMonth();
  uint16_t ttd = (uint16_t) rtc_stm.getDay();
  // setBackupRegister(uint32_t index, uint32_t value)  // works only with value in uint16, but in uint32_t format
  // setBackupRegister(0, reg); register 0  doesn't work
  uint32_t reg = tty << 8;    // year
  reg = reg | ttm;            // month
  setBackupRegister(1, reg);
  reg = ttd << 8;             // day
  reg = reg | my_counter;     // max 255
  setBackupRegister(2, reg);
  disableBackupDomain();
}
//----------------------------------------------------------------
