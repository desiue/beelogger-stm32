/*
   (C) 2020 R.Schick / Thorsten Gurzan - beelogger.de

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

    http://github.com/stm32duino/wiki/wiki/Libraries

    STM32 Core Version 1.9.0

    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/master/STM32/package_stm_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8
    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload methoed "STM32 Cube Programmer Serial"   ( == FTDI)

    Java installieren
    Stm32cubeprogrammer installieren

    Programmieren über FTDI
    Boot0 Stecker auf 1
    Danach auf 0
    Boot1 immer auf 0

*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter http://beelogger.de
// Version 2020    beelogger Multi WLAN mit EE-Prom, bis zu vier Waagen
//
// verwendet lowpower.shutdown() (STM32 Standby), Strombedarf ca. 10uA
// System macht Reset bei Wakeup, RTC-RAM und EE-Prom zur Datenspeicherung
// timed sleep wenn User Service


char ID[] = "STM32_read_CPU_Voltage_Temperature";


//--------------------------------------------------------------------------------------------
// Kalibrierwerte für die Spannungsmessung Akku
const int32_t Kalib_Spannung =  4170;    // Hier muss der Wert aus der Kalibrierung eingetragen werden, sonst funktioniert der Programmcode nicht
const int32_t Kalib_Bitwert  =   948;    // Hier muss der Wert aus der Kalibrierung eingetragen werden, sonst funktioniert der Programmcode nicht
//----------------------------------------------------------------

//################################################################
// Global Libraries
//################################################################
#include <STM32LowPower.h>
#include <STM32RTC.h>
/* Get the rtc object */
STM32RTC& rtc_stm = STM32RTC::getInstance();
#include <stm32yyxx_ll_adc.h>  // analog reading
#include <backup.h>            // RTC Backup ram
//----------------------------------------------------------------


//----------------------------------------------------------------
// Variablen
//----------------------------------------------------------------
const float No_Val = 99.9f;  // Vorbelegung, Wert nicht gemessen
const float No_Value = -1.0f;  // Vorbelegung, Wert nicht gemessen

//                              DHT1,   DHT2,   Si7021, SHT31a, SHT31b, BMEa,   BMEb,  DS18B20a,DS18B20b,DS18B20c,DS18B20d
float SensorTemp[12] = {No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val};


//              Kein_Sensor, Luftdruck, ZählerRegen, unbelegt
float Aux[4] = {No_Value, No_Value, No_Value, No_Value};
float DS_Temp = No_Val;
float Batteriespannung = No_Value;
uint32_t time_on = 0;
//----------------------------------------------------------------

//----------------------------------------------------------------
// WakeUp
#define WakeUp_Pin   PA0  //  STM32 Shutdown Wakeup Pin
//----------------------------------------------------------------
// Power on/off
#define Power_Pin PB5
//----------------------------------------------------------------
// Pins Spannungen messen
#define Batterie_messen    PB0
#define Solarzelle_messen  PB1
//----------------------------------------------------------------
// Konfiguration One-Wire-Bus für DS18B20
#define ONE_WIRE_BUS PA8
//----------------------------------------------------------------
#define Serial_Baudrate 9600

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)  || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX  Serial1
#else
#define MONITOR_RX_TX  Serial
#endif

void setup() {

  //----------------------------------------------------------------
  //  Setup RTC
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
  LowPower.enableWakeupFrom(&rtc_stm, WakeUpRTC, &time_on);
  // Attach a wakeup from User Service Switch, WakeUp_Pin must be Pin PA0
  LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING);
  //----------------------------------------------------------------

  // Setup Serial for info and debug output
  MONITOR_RX_TX.begin(Serial_Baudrate);
  blink_STM32(1, 500);    // blink once, we are awake, important, because of midnight
  while (!MONITOR_RX_TX) {};     // Wait for serial port open
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(ID);
  MONITOR_RX_TX.flush();


  //----------------------------------------------------------------
  // Initialize the variable only on first power-on reset
  //----------------------------------------------------------------
  if (__HAL_RCC_GET_FLAG(RCC_FLAG_PORRST)) {
    MONITOR_RX_TX.println("POR");  // Power On Reset
  }
  else { // Reset Wakeup STM32 after shutdown
    MONITOR_RX_TX.println("RST");
  }
  MONITOR_RX_TX.flush();
  __HAL_RCC_CLEAR_RESET_FLAGS();
  __HAL_PWR_CLEAR_FLAG(PWR_FLAG_WU); // Clear WakeUp Flag
  //----------------------------------------------------------------


  //----------------------------------------------------------------
  //  Check battery
  //----------------------------------------------------------------
  analogReadResolution(12);
  Spannungen_messen();
  //----------------------------------------------------------------


  //----------------------------------------------------------------
  //  System on
  //----------------------------------------------------------------
  MONITOR_RX_TX.println("System On");
  MONITOR_RX_TX.flush();
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);
  delay (5);
  //----------------------------------------------------------------

  MONITOR_RX_TX.flush();
}

//#########################################################
void loop() {
  display_time();
  MONITOR_RX_TX.flush();
  Spannungen_messen();

  read_temp();
  Sensor_DS18B20();
  LowPower.deepSleep(10000);
  blink_STM32(1, 200);
  MONITOR_RX_TX.println("\n_____"); MONITOR_RX_TX.flush();
}
//#########################################################

void read_temp() {
  int bits_ATemp, bits_VRef;
  bits_ATemp = analogRead(ATEMP);
  bits_VRef = analogRead(AVREF);

  float vdd;
  // reading Vdd by utilising the internal 1.20V VREF
  vdd = 1.20 * 4096.0 / bits_VRef;
  Aux[2] = vdd;


#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)  || defined(ARDUINO_BLACKPILL_F401CE)
#pragma message "zur Information: Blackpill F4x1C"
    // Temperature (in °C) = {(VSENSE – V25) / Avg_Slope} + 25
    // V25 = VSENSE value for 25° C
    // Avg_Slope = average slope of the temperature vs. VSENSE curve (given in mV/°C or μV/°C)
#define Temp_Sensor_V25 0.76     // typical voltage at 25 deg. celsius 1.43; 0.76
#define Temp_Sens_Slope 0.0025   // slope of the sensor in V/deg. celsius; typ. 2.5mV/deg. cel
#else
#pragma message "zur Information: Bluepill F103C8/CB"
    // Temp_Sensor_V25 = 1.43 and Temp_Sens_Slope = 0.0043 parameters come from F103 datasheet - ch. 5.9.13
    // and need to be calibrated for every chip (large fab parameters variance)
#define Temp_Sensor_V25 1.41     // typical voltage at 25 deg. celsius 1.43; 1.34 - 1.52
#define Temp_Sens_Slope 0.0043   // slope of the sensor in V/deg. celsius; typ. 4.3mV/deg. cel
#endif

  Aux[0] = (Temp_Sensor_V25 - (vdd / 4096.0 * bits_ATemp)) / 0.0043 + 25.0;

  MONITOR_RX_TX.print("STM32-Vdd. [V]: ");
  MONITOR_RX_TX.println(Aux[2]);
  MONITOR_RX_TX.print("T-STM32 [C]: ");
  MONITOR_RX_TX.println(Aux[3]);
  MONITOR_RX_TX.flush();
}

//----------------------------------------------------------------
// Funktion Spannungen messen
//----------------------------------------------------------------
void Spannungen_messen() {

  Batteriespannung = Messe_Spannung(Batterie_messen);

  MONITOR_RX_TX.print("Bat. [V]: ");
  MONITOR_RX_TX.println(Batteriespannung);
  MONITOR_RX_TX.flush();
}

float Messe_Spannung (byte Pin) {
  float Spannung;
  int bits_Messung;

  bits_Messung = analogRead(Pin);
  bits_Messung = 0;
  for (byte j = 0 ; j < 16; j++) {
    bits_Messung += analogRead(Pin);
  }
  MONITOR_RX_TX.flush();
  bits_Messung = bits_Messung / 16;
  // MONITOR_RX_TX.printF("bits: ");  MONITOR_RX_TX.print(bits_Messung);  MONITOR_RX_TX.printlnF("  ");
  Spannung = (float)map(bits_Messung, 0, Kalib_Bitwert, 0, Kalib_Spannung) / 1000.0;

  return (Spannung);
}
//----------------------------------------------------------------

//----------------------------------------------------------------
// Do some blink STM32
//----------------------------------------------------------------
void blink_STM32(uint8_t nmr, uint16_t bl_tm) {
  digitalWrite(PC13, HIGH);  // LED aus
  pinMode(PC13, OUTPUT);  delay(5);
  for (int i = 0; i < nmr; i++) {
    digitalWrite(PC13, LOW);  delay(bl_tm); // LED an
    digitalWrite(PC13, HIGH);  delay(bl_tm);
  }
  pinMode(PC13, INPUT);
}
//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion WakeUp on Interrupt
//----------------------------------------------------------------
void WakeUp() {
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
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
  //rtc_stm.detachInterrupt();
}
//----------------------------------------------------------------
void display_time() {
  MONITOR_RX_TX.print("Datum: ");
  MONITOR_RX_TX.print(rtc_stm.getDay());
  MONITOR_RX_TX.print(".");
  MONITOR_RX_TX.print(rtc_stm.getMonth());
  MONITOR_RX_TX.print(".");
  MONITOR_RX_TX.print(rtc_stm.getYear());
  MONITOR_RX_TX.print(" ");
  MONITOR_RX_TX.print(rtc_stm.getHours());
  MONITOR_RX_TX.print(": ");
  byte x = rtc_stm.getMinutes();
  if (x < 10) MONITOR_RX_TX.print("0");
  MONITOR_RX_TX.print(x);
  MONITOR_RX_TX.print(": ");
  x = rtc_stm.getSeconds();
  if ( x < 10) MONITOR_RX_TX.print("0");
  MONITOR_RX_TX.println(x);
  MONITOR_RX_TX.flush();
}

#include <OneWire.h>

void Sensor_DS18B20() {

  uint8_t i, addr[8], data[12];  // für DS1820
  float f_tmp  = No_Val;
  OneWire ds1820(ONE_WIRE_BUS);
  if (ds1820.search(addr)) {
    if (OneWire::crc8( addr, 7) == addr[7]) { // CRC OK
      if (addr[0] == 0x28) {   // is this a DS18_B_20?
        ds1820.reset();
        ds1820.select(addr);
        ds1820.write(0x44, 0);  // start conversion, with parasite power off
        delay(800);             // wait for conversion
        i = ds1820.reset();
        ds1820.select(addr);
        ds1820.write(0xBE);        // read scratchpad
        for ( i = 0; i < 3; i++) { // we need only 3 of 9 bytes
          data[i] = ds1820.read();
        }
        f_tmp = (float)((data[1] << 8) + data[0]);  // MSB + LSB
        f_tmp = (f_tmp / 16.0);        // 12Bit = 0,0625 C per Bit (-> divide by 16)
      }
    }  // CRC OK
  } // if ds18b20

  MONITOR_RX_TX.print("DS18B20 [C]: ");
  MONITOR_RX_TX.println(f_tmp);
  MONITOR_RX_TX.flush();

}
