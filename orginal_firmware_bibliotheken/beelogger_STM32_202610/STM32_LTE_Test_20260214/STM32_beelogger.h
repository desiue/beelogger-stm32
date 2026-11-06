#ifndef __STM32_beelogger_config__
#define __STM32_beelogger_config__
// 22.04.2020 STM32
// 06.10.2021 HX711 adress
// 04.03.2023 401CC / 401CE
// 15.10.2023 use EE-Prom up to 128kByte in STM32F4x1 systems


/****************************************************************/
// Konfiguration DEBUG-Information
//               Serial Monitor via FTDI / USB
/****************************************************************/
#define myDEBUG 1                  // Debug via Serial Monitor

#define DBG_MEM_INFO 0             // Memory usage info

#define INTERVAL_STUCK_SWITCH 5    // minimal 5 Minuten
#define WeckIntervall_default 15
#define AlternativIntervallMinuten  120  // Weckinterval, wenn VAlternativ erreicht = Akku fast leer
const float VSysnormal = VAlternativ + 0.1; // Spannung ab der in den Normalbetrieb gewechselt wird

/****************************************************************/
// Änderung Gewicht in Kilogramm bei der Daten versenden erfolgt
/****************************************************************/
const float Alarm_Gewicht = 1.0;
#define SCHWARM_ALARM 1  // 1= aktiviere Senden wenn Schwarmalarm erkannt (0 oder 1)
/****************************************************************/

/****************************************************************/
// Konfig der seriellen Schnittstelle
/****************************************************************/
#define Serial_Baudrate 9600
/****************************************************************/

/****************************************************************/
// I2C Adressen
/****************************************************************/

/****************************************************************/
// Datenablage EEProm
/****************************************************************/
#define AT24Cxx_CTRL_ID 0x57  // EE-Prom I2C Adresse
#include <beelogger_at24cxx.h>

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
// Konfiguration BH1750 - Licht
/****************************************************************/
// BH1750,  mögliche I2C Adressen
//   BH1750_DEFAULT_I2CADDR  0x23
//   BH1750_SECOND_I2CADDR   0x5C
/****************************************************************/
#define BH1750_ADRESS    0x23


/****************************************************************/
// Konfiguration Si7021 - Temperatur und Luftfeuchte
/****************************************************************/
// Si7021 I2C-Adresse : 0x40 (64d), Wert ist in Adafruit_Si7021.h
/****************************************************************/


/****************************************************************/
// STM32 Pins
/****************************************************************/

//********
// Port PA
//********

/****************************************************************/
// WakeUp
/****************************************************************/
#define WakeUp_Pin   PA0  //  STM32 Shutdown Wakeup Pin
/****************************************************************/

//#define unused_PA1  // analog Input

// Serielle Schnittstelle Nr.2 Serial2
#define SER2_TX  PA2          // Serial 2
#define SER2_RX  PA3          // Serial 2

// SPI Schnittstelle
//#define SPI_PA4   //  SS
//#define SPI_PA5   //  SCK
//#define SPI_PA6   //  MISO
//#define SPI_PA7   //  MOSI

/****************************************************************/
// Konfiguration One-Wire-Bus für DS18B20
/****************************************************************/
#define ONE_WIRE_BUS PA8
/****************************************************************/

/****************************************************************/
// Serielle Schnittstelle #1  Serial / Serial 1
/****************************************************************/
#define SER_TX      PA9       // Pin TX / FTDI
#define SER_RX      PA10      // Pin RX / FTDI
/****************************************************************/


//#define unused_PA11  // USB
//#define unused_PA12  // USB
//#define unused_PA13  // not available
//#define unused_PA14  // not available
//#define unused_PA15  // Rain sensor


//********
// Port PB
//********

/****************************************************************/
// Pins Spannungen messen
/****************************************************************/
#define Batterie_messen    PB0
#define Solarzelle_messen  PB1
/****************************************************************/

//#define PB2  // Boot1 Jumper
//#define PB3  // unused

/****************************************************************/
// Konfiguration ESP / SIM800
/****************************************************************/
// Reset ESP8266
#define ESP_RESET PB4
// Power SIM800L / SIM7600E
#define SIM_Power_Pin  PB4
/****************************************************************/

/****************************************************************/
// Power on/off
/****************************************************************/
#define Power_Pin PB5
/****************************************************************/

/****************************************************************/
// I2C
/****************************************************************/
//#define SCL   PB6      // I2C Clock
//#define SDA   PB7      // I2C Data
/****************************************************************/

/****************************************************************/
// Konfiguration HX711 SCK /Data
/****************************************************************/
#if (defined(ARDUINO_BLUEPILL_F103CB) || defined(ARDUINO_BLUEPILL_F103C8))
uint8_t HX711_DT[2] =  {PB8, PB10}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = {PB9, PB11}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock
#else

#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE))
uint8_t HX711_DT[2] =  {PB8, PB2}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = {PB9, PB10};  // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock
#else
#error " Board Part Number / Processor not supported"
#endif
#endif
/****************************************************************/

/****************************************************************/
// I2C  Nr.2
/****************************************************************/
//#define SCL_2   PB10    // I2C Clock used for 2. HX
//#define SDA_2   PB11    // I2C Data used for 2. HX
/****************************************************************/

/****************************************************************/
// Konfiguration DHT21 / DHT22 - Temperatur und Luftfeuchte
/****************************************************************/
uint8_t DHT_Sensor_Pin[2] = {PB12, PB13}; // zwei DHT
/****************************************************************/

//#define unused_PB14
//#define unused_PB15




//********
// Port PC
//********
//#define PC13_LED     // LED
//#define unused_PC14  // 32kHz ClockGen
//#define unused_PC15  // 32kHz ClockGen


/****************************************************************/
// diverse Parameter
/****************************************************************/

#if (Anzahl_Sensoren_Gewicht == 1 )
#define Daten_Satz_part 80
#define _Num_Retry       3     // Anzahl Sendewiederholungen für den gesamten Datensatz bei Verbindungsfehlern
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

#if (((EEProm_Size / _Daten_Satz_Len) - _Num_Retry) < 2040)
#define _EE_DATA_SETS   (EEProm_Size / _Daten_Satz_Len)  // maximale Anzahl Messungen pro Uebertragungsserie
#else
#define _EE_DATA_SETS 2040
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

#define SEND_HOUR       6  // 2 - 10 (Uhr)
//----------------------------------------------------------------

/****************************************************************/
// Debug Print Functions
/****************************************************************/
#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX  Serial1
#else
#define MONITOR_RX_TX  Serial
#endif

#if myDEBUG == 1
#define debugbegin(x) MONITOR_RX_TX.begin(x)
#define debugprintF(x) MONITOR_RX_TX.print(F(x))
#define debugprintlnF(x) MONITOR_RX_TX.println(F(x))
#define debugprint(x) MONITOR_RX_TX.print(x)
#define debugprintln(x) MONITOR_RX_TX.println(x)
#define debugflush() MONITOR_RX_TX.flush()
#define debugend() MONITOR_RX_TX.end()
#else
#define debugbegin(x)
#define debugprintF(x)
#define debugprintlnF(x)
#define debugprint(x)
#define debugprintln(x)
#define debugflush()
#define debugend()
#endif

void Serial_rxtx_off() {
  delay(20);
  digitalWrite(SER_RX, LOW);   // Port aus
  pinMode(SER_RX, INPUT);
  digitalWrite(SER_TX, LOW);   // Port aus
  pinMode(SER_TX, INPUT);
}
#endif // beelogger_config.h
