/*
   (C) 2023 R.Schick / Thorsten Gurzan - beelogger.de

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

// beelogger.de - Arduino LoRaWAN Datenlogger für Imker
// Version 2025
// 26.03.2025 Messzyklussteuerung per Solarspannung / Temperatur
// 01.04.2023 Universal Platine 2.x
// compile error fix oslmic.h line 230 static inline type table_get ## postfix

char ID[] = "MULTI_LORA_";  //nur Grossbuchstaben,Zahlen, keine Blanks
long l_ID = 250326;


#define CFG_noassert 1             // supress error messages in library

#include "Config_LORA.h"       // Konfiguration eintragen
#include "Config_MULTI.h"     // Konfiguration und Kalibrierung eintragen
#include "beelogger_config.h"


//----------------------------------------------------------------
// Variablen
//----------------------------------------------------------------
const float No_Val = 99.9;   // Vorbelegung, Wert nicht gemessen
const float No_Value = 0.0; // Vorbelegung, Wert nicht gemessen

//                     KeinSensor DHT1, DHT1,   Si7021, SHT31a, SHT31b, BMEa,   BMEb,  DS18B20a,DS18B20b
float SensorTemp[12] = {No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val, No_Val};

//                        KeinSensor  DHT1,    DHT2,     Si7021,   SHT31a,   SHT31b,   BMEa,     BMEb.   KeinSensor KeinSensor
float SensorFeuchte[12] = {No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value, No_Value};

float Aux[2] = {No_Value, No_Value};

float DS_Temp = No_Val;
float Licht = No_Value;
float Gewicht[4] = {0.01, 0.01, 0.01, 0.01};
float LetztesGewicht[4] = {No_Value, No_Value, No_Value, No_Value};

float Batteriespannung = No_Val;
float Solarspannung = No_Val;

float Service = No_Value;

uint32_t time_on = 0;
uint8_t WeckIntervallMinuten = WeckIntervall_default;
uint8_t report_info = 0;     // 0 = time-On
uint8_t stuck_switch = 0;          // Service switch

volatile bool ok_sleep = true;


//----------------------------------------------------------------


void setup() {

  Serial.begin(Serial_Baudrate);
  Serial.print(ID);  Serial.println(l_ID);

  //----------------------------------------------------------------
  //  System On
  //----------------------------------------------------------------
  //Spannungen_messen();

  while (Batteriespannung < VMinimum) {
    LowPower.powerStandby(SLEEP_8S, ADC_OFF, BOD_OFF);
    Spannungen_messen();
  }

  init_LoRaSeq(); // init LoRa sequence number

  digitalWrite(Power_Pin, HIGH);  // Power On
  pinMode(Power_Pin, OUTPUT);
  delay(5);

  System_On();

  //Init_Rain_Int();  // init Interrupt Regensensor

  //----------------------------------------------------------------
  //  Setup RTC
  //----------------------------------------------------------------
  DateTime pc_tim = DateTime(__DATE__, __TIME__);
  long l_pczeit = pc_tim.get();
  DateTime aktuell = rtc.now();
  long l_zeit = aktuell.get();
  if (l_pczeit > l_zeit) {
    rtc.setDateTime(l_pczeit);
  }

  //----------------------------------------------------------------
  //  Sleep Mode & Send data Interrupt
  //----------------------------------------------------------------
  pinMode(DS3231_Interrupt_Pin, INPUT_PULLUP);
  delay(5);
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  // Setup Gewicht
  //----------------------------------------------------------------
  //setup_hx711();
  Sensor_Gewicht(1);   // Startwert Gewicht holen
  //----------------------------------------------------------------

  Serial.flush();
  Serial.end();
  Serial_rxtx_off();
}

//##################################################################
void loop() {

  debugbegin(Serial_Baudrate);
  debugprintlnF("Loop");
  debugflush();

  Spannungen_messen();
  if (Batteriespannung > VMinimum) {
    uint8_t ureq = User_Int();
    if (ureq) {  // 1 = Service Switch not set
      Sensor_Temp_Zelle(0);
      Sensor_DHT();
      Sensor_DS18B20();
      Sensor_Si7021();
      Sensor_SHT31();
      Sensor_BME280();
      Sensor_Licht();
      //Sensor_Regen();  //Regenmesser
      Sensor_Temp_Zelle(1);
      Sensor_Gewicht(0);
      Daten_Senden();
    }
    else {
      Sensor_Gewicht(1);
    }
  }
  Alarm_konfigurieren();

  debugflush();
  debugend();
  Serial_rxtx_off();

  System_Off();
  SleepNow();
  System_On();
}
//##################################################################
