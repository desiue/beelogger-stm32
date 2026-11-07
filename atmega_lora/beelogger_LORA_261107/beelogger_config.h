#ifndef __beelogger_config__
#define __beelogger_config__
// 09.03.2020
// 26.03.2020 Konfiguration Regensensor
// 02.04.2020  HEXA, 6 Waagen
// 10.05.2020  Senden bei Änderung Gewicht
// 04.12.2020  Senden bei Schwarmalarm konfigurierbar
// 08.01.2021 Debug Info
// 04.10.2021 HX711 adresss struct
// 10.12.2022 autoconfig Arduino ProMini
// 01.04.2023 Universal Platine 2.x
// 07.06.2023 Uni 2.0.1
// 08.12.2023 modify defines based on beelogger version/uC


//----------------------------------------------------------------
// Konfiguration Version Universal Platine
//----------------------------------------------------------------

#define UNI_2X  // Universal Platine Version 2.x // am Zeilenanfang entfernen


/****************************************************************/
// Konfiguration DEBUG-Information  Serial Monitor via FTDI / USB
// 0 = keine Debug Ausgabe
// 1 = normale Debug Ausgabe
// 2 = weitere Debug Ausgabe
/****************************************************************/
#define myDEBUG 1     // Debug via Serial Monitor ->  0, 1, 2
/****************************************************************/

/****************************************************************/
// Konfiguration Baudrate der seriellen Schnittstelle
/****************************************************************/
#define Serial_Baudrate 9600
/****************************************************************/

/****************************************************************/
// Änderung Gewicht in Kilogramm bei der Daten versenden erfolgt
/****************************************************************/
const float Alarm_Gewicht = 1.0;
#define SCHWARM_ALARM 1  // 1= aktiviere Senden wenn Schwarmalarm erkannt (0 oder 1)
/****************************************************************/


/****************************************************************
  Global Libraries
****************************************************************/
#include "Sodaq_DS3231.h"
#include <LowPower.h>


/****************************************************************
  I2C Adressen
****************************************************************/

/****************************************************************/
// Datenablage EEProm
/****************************************************************/
#define AT24Cxx_CTRL_ID 0x57  // EE-Prom I2C Adresse

#include <beelogger_at24cxx.h>
AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);
/****************************************************************/

/****************************************************************/
// Konfiguration SHT31 - Temperatur und Luftfeuchte
/****************************************************************/
uint8_t SHT31_adresse[2] = {0x44, 0x45};
/****************************************************************/


/****************************************************************/
// Konfiguration BME280 - Temperatur und Luftfeuchte
/****************************************************************/
uint8_t BME280_adresse[2] = {0x76, 0x77};
/****************************************************************/


/****************************************************************/
// Konfiguration Si7021 - Temperatur und Luftfeuchte
/****************************************************************/
// Si7021 I2C-Adresse : 0x40 (64d), Wert ist fix in Library
/****************************************************************/

/****************************************************************/
// Konfiguration BH1750 - Licht
/****************************************************************/
// BH1750,  mögliche I2C Adressen
//   BH1750_DEFAULT_I2CADDR  0x23
//   BH1750_SECOND_I2CADDR   0x5C
/****************************************************************/
#define BH1750_ADRESS    0x23


/****************************************************************
  AT-Mega Pins
****************************************************************/

#if defined (UNI_2X)
#pragma message "  Information: System Pin Konfiguration beelogger-Universal Platine 2.x  "
#else
#if defined(ARDUINO_AVR_PRO)
#if (F_CPU==16000000L)  // Pro Mini Universal 1.x  5V 16MHz
#pragma message "  Information: Konfiguration beelogger Universal 1.x mit ProMini 16MHz  "
#else       // Pro Mini beelogger SMD  3,3V 8MHz
#pragma message "  Information: Konfiguration beelogger SMD mit ProMini 8MHz"
#endif
#endif
#if defined(ARDUINO_AVR_NANO)     // NANO 5V 16MHz Universal Platine 1.x
#pragma message "  Information: Konfiguration beelogger Universal 1.x mit Nano  "
#endif
#endif

/****************************************************************/
// Konfiguration One-Wire-Bus für DS18B20
// Regenmesser verwendet Pin 3, dann DS18B20 auf Pin 6
/****************************************************************/
#if  (Anzahl_Sensor_Regen) || (defined (UNI_2X))
#define ONE_WIRE_BUS 6
#else
#define ONE_WIRE_BUS 3
#endif
/****************************************************************/

/****************************************************************/
// Konfiguration DHT21 / DHT22 - Temperatur und Luftfeuchte
// mit Regenmesser verwendet DS18B20 Pin 6
/****************************************************************/
#if defined (UNI_2X)
uint8_t DHT_Sensor_Pin[2] = {5, 7}; // zwei DHT
#else
#if  Anzahl_Sensor_Regen
uint8_t DHT_Sensor_Pin[2] = {5, 5}; // nur ein DHT
#else
uint8_t DHT_Sensor_Pin[2] = {5, 6}; // zwei DHT
#endif
#endif
/****************************************************************/

/****************************************************************/
// Konfiguration HX711 SCK /Data
/****************************************************************/
#if (defined(ARDUINO_AVR_PRO) && (F_CPU==16000000L) && (not defined UNI_2X)) // Pro Mini 5V 16MHz Universal 1.x
//#pragma message "Information: HX711 Nr. 1 Pin 10/11"
uint8_t HX711_SCK[2] = {10,  A6}; //  S-Clock
uint8_t HX711_DT[2] =  {11,  A7}; //  Data
#else
//#pragma message "Information: HX711 Nr. 1 Pin A0/A1"
uint8_t HX711_SCK[2] = {A0,  10}; //  S-Clock
uint8_t HX711_DT[2] =  {A1,  11}; //  Data
#endif



#define             Beute5                        0    // 0 oder Nr. 1 - 11
#define             Beute6                        0    // 0 oder Nr. 1 - 11
/****************************************************************/

/****************************************************************/
// Konfiguration ESP / SIM800 / LTE
/****************************************************************/
#define ESP_RESET A2
#define GSM_Power_Pin A2

#if (defined(ARDUINO_AVR_PRO) && (F_CPU==16000000L) && (not defined UNI_2X)) // Pro Mini 5V 16MHz Universal 1.x  
#undef ESP_RESET
#undef GSM_Power_Pin
#define ESP_RESET 12
#define GSM_Power_Pin 12
#endif

uint8_t ESP_TX = 9;
uint8_t ESP_RX = 8;    // beelogger_Universal, solar Multishield 2.5, neue Konfiguration
uint8_t GSM_TX = 9;
uint8_t GSM_RX = 8;    // beelogger_Universal, solar Multishield 2.5, neue Konfiguration
//uint8_t GSM_RX = 10; // beelogger Solar (PRO MINI), alte Konfiguration
/****************************************************************/

/****************************************************************/
// Serielle Schnittstelle
/****************************************************************/
#define USB_RX      0          // Pin 0 RX-USB
#define USB_TX      1          // Pin 1 TX-USB
/****************************************************************/

/****************************************************************/
// Interrupt
/****************************************************************/
#define DS3231_Interrupt_Pin 2
#define Rain_Interrupt_Pin  3 // D3 = Interrupt
/****************************************************************/

/****************************************************************/
// Power on/off
/****************************************************************/
#define Power_Pin 4
/****************************************************************/

/****************************************************************/
// I2C
/****************************************************************/
#define SDA   A4      // I2C Daten
#define SCL   A5      // I2C Clock
/****************************************************************/

/****************************************************************/
// Pins Spannungen messen
/****************************************************************/
#define Batterie_messen    A6
#define Solarzelle_messen  A7

#if (defined(ARDUINO_AVR_PRO) && (F_CPU==16000000L) && (not defined UNI_2X)) // Pro Mini 5V 16MHz Universal 1.x  
#undef Batterie_messen
#undef Solarzelle_messen
#define Batterie_messen    A2
#define Solarzelle_messen  A3
#endif

/****************************************************************/


/****************************************************************/
// diverse Parameter
/****************************************************************/
#define WeckIntervall_default     15    // Standart Messintervall ohne Serversteuerung
#define INTERVAL_STUCK_SWITCH      5    // Abfrageintervall Serviceschalter, minimal 5 Minuten


// Size of databuffer
#if (Anzahl_Sensoren_Gewicht == 1 )
#define Daten_Satz_part 80
#define _Num_Retry       3    // Anzahl Sendewiederholungen für den gesamten Datensatz bei Verbindungsfehlern
#else
#if (Anzahl_Sensoren_Gewicht == 2 )
#define Daten_Satz_part 88
#define _Num_Retry       4    // Anzahl Sendewiederholungen für den gesamten Datensatz bei Verbindungsfehlern
#else
#define Daten_Satz_part 96
#define _Num_Retry       5    // Anzahl Sendewiederholungen für den gesamten Datensatz bei Verbindungsfehlern
#endif
#endif

#define _Daten_Satz_Len (Daten_Satz_part + (Anzahl_Sensoren_Gewicht*16))    // Datensatz  16/32byte increment step!!!

#if (((EEProm_Size / _Daten_Satz_Len) - _Num_Retry) < 255)
#define _Num_Measured   ((EEProm_Size / _Daten_Satz_Len) - _Num_Retry)  // maximale Anzahl Messungen pro Uebertragungsserie
#else
#define _Num_Measured 254
#endif



// Anzahl der Messdatensätze pro Datenpaket
#if Anzahl_Sensoren_Gewicht > 4
#define _Send_Sets       6    // 6 Datenpakete pro TCP Send plus http-overhead
#elif Anzahl_Sensoren_Gewicht == 4
#define _Send_Sets       7    // 7 Datenpakete pro TCP Send plus http-overhead
#else
#define _Send_Sets       8    // 8 Datenpakete pro TCP Send plus http-overhead
#endif

// Steuerung Sendezyklen über Webserver
// Sendezyklus in Stunden, wird verdoppelt bei Messzyklen über 30 Minuten
#define SEND_CYC_A      1
#define SEND_CYC_B      2
#define SEND_CYC_C      4
#define SEND_CYC_D      8
// Multiplikator Sendezyklen wenn Messzyklen mehr als 30 min
#define LONG_CYCLE      2

#define SEND_CYC_RTY    2  // wiederhole Senden nach Fehler in Stunden

#define SEND_HOUR 5        // Sendeuhrzeit am Morgen 2 - 10 (Uhr)
/****************************************************************/


/****************************************************************
  Debug Print Functions
****************************************************************/

#if (myDEBUG)
#define debugbegin(x) Serial.begin(x)
#else
#define debugbegin(x)
#endif

#if (myDEBUG)
#define debugprintF(x) Serial.print(F(x))
#define debugprintlnF(x) Serial.println(F(x))
#define debugprint(x) Serial.print(x)
#define debugprintln(x) Serial.println(x)
#define debugflush() Serial.flush()
#define debugend() Serial.end()
#else
#define debugprintF(x)
#define debugprintlnF(x)
#define debugprint(x)
#define debugprintln(x)
#define debugflush()
#define debugend()
#endif

#if (myDEBUG == 2)
#define debugxprintF(x) Serial.print(F(x))
#define debugxprintlnF(x) Serial.println(F(x))
#define debugxprint(x) Serial.print(x)
#define debugxprintln(x) Serial.println(x)
#define debugxflush() Serial.flush()
#else
#define debugxprintF(x)
#define debugxprintlnF(x)
#define debugxprint(x)
#define debugxprintln(x)
#define debugxflush()
#endif

/****************************************************************/
// call delay, strange, but this save programm space
/****************************************************************/
void calldelay(uint16_t wait_wait) {
  delay (wait_wait);
}

/****************************************************************/
// set serial port pins off, i.e. to low power
/****************************************************************/
void Serial_rxtx_off() {
  calldelay(20);
  digitalWrite(USB_RX, LOW);   // Port aus
  pinMode(USB_TX, INPUT);
  digitalWrite(USB_TX, LOW);   // Port aus
}
/****************************************************************/
#endif
