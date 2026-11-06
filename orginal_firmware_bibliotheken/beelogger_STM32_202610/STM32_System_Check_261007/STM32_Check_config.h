//--------------------------------------------------------------
// Sketch  Konfiguration
//--------------------------------------------------------------

#define _BMP280_            0    // 0 = ohne,  1= mit
#define _BME280_            0    // 0 = ohne,  1= mit
#define _BME680_            1    // 0 = ohne,  1= mit


#define DBG_MEM_INFO        0    // Memory usage info

#define Power_off_at_End    1    // 1 = aus (=LowPower),   0= Power an  

#define dfue_Test           1    // Abfrage WLAN/GSM/LTE-Modul  0 oder 1  

#define Allow_WakeUp_Switch 0    // 0 / 1 = aktiviere Wakup mit Arbeitsschalter 


const float No_Value = -1.0;
const float No_Val = 99.9;

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX  Serial1   // FTDI-Port
//#define MONITOR_RX_TX  Serial  // USB-Port
#else
#define MONITOR_RX_TX  Serial
#endif

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
#define One_Wire_Bus        PA8    // OneWire / DS18B20 an Pin PA8
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
//#define unused_PA15  // 


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
//#define PB3  // 

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
#define SCL_STM   PB6      // I2C Clock
#define SDA_STM   PB7      // I2C Data
/****************************************************************/

/****************************************************************/
// Konfiguration HX711 SCK /Data
/****************************************************************/
#if (defined(ARDUINO_BLUEPILL_F103CB) || defined(ARDUINO_BLUEPILL_F103C8))
uint8_t HX711_DT[2] =  {PB8, PB10}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = {PB9, PB11}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock
#else

#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)|| defined(ARDUINO_BLACKPILL_F401CE))
uint8_t HX711_DT[2] =  {PB8, PB2}; // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = {PB9, PB10};  // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock
#else
#error "STM32 Typ / Board part number not supported"
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
#define DHT_1               PB12    // DHT 22 Nummer 1 an Pin B12
#define DHT_2               PB13    // DHT 22 Nummer 2 an Pin B13
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

//--------------------------------------------------------------
// Ende Konfiguration
//--------------------------------------------------------------
