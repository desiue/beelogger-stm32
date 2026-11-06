/****************************************************************/
// LORA Konfiguration
/****************************************************************/
#include <lmic.h>
#include <hal/hal.h>
#include <SPI.h>

// Verbindungsparameter:
// Beispiel für LORA-http-Intergration Setting in der TTN config:
// https://community.beelogger.de/UserName/beelogger1/beelogger_log.php?Passwort=deinpasswort&LORA=1

// "################################################"
// "This code needs payloadformatter_220227.js"
// "################################################"

// LoRaWAN NwkSKey, network session key
// LoRaWAN AppSKey, application session key
static const u1_t PROGMEM NWKSKEY[16] = { 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00 };
static const u1_t PROGMEM APPSKEY[16] = { 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00 };


// LoRaWAN end-device address (DevAddr)
//static const u4_t DEVADDR =   0x12345678;  //   <-- Change this address for every node!  
static const u4_t DEVADDR = 0x12345678;

// LoRaWAN Network Prefix
static const uint16_t PREFIX =   0x13;     //   https://www.thethingsnetwork.org/docs/lorawan/prefix-assignments.html  
// example: 0x13 = TTN
//          0x01 = your own local Lora accesspoint


// number of LORA-Channels to be used  1, 3 or 8
// 1 = Single-Channel-Gateway, may be need to change frequency in STM32_LORA.ino
// 3 = three channels
// 8 = use all channels
#define LORA_CHANNELS 3

// turn LMIC-Lib Debug on/off.  "On" with this line commented out
#define NDEBUG 1


/****************************************************************/
// Allgemeine Konfiguration
/****************************************************************/
#define WeckIntervall       10          //  Weckintervall / Messzyklus

#define WeckIntervall_aktiv  1          // Manuelle Intervallsteuerung 0 = inaktiv, 1 = aktiv

#define WeckIntervall_Tag 10
#define WeckIntervall_Nacht 30
#define WeckIntervall_Nacht_Anfang 23
#define WeckIntervall_Nacht_Ende 6

#define WeckIntervall_Winter_aktiv  0   // Intervall Winter 0 = inaktiv, 1 = aktiv
#define WeckIntervall_Winter       60   // Intervall in Minuten
#define WeckIntervall_Winter_Anfang 9   // Monat Winterintervall Start
#define WeckIntervall_Winter_Ende   4   // Monat Winterintervall Ende

// alternative Messzyklusumschaltung
#define TEMP_ZYKLUS_aktiv    0            //  0 / 1 aktiviert Umschaltung Messzyklus Winter per Temperatur
const float Temperatur_Wintertag = 8.0;   // < = Wintertag
#define SOLAR_ZYKLUS_aktiv   0            //  0 / 1 aktiviert Umschaltung Messzyklus Tag/Nacht per Solarzellenspannung
const float SolarSpannung_Sommertag = 0.1;// < = Nacht



// 
#define AlternativIntervallMinuten  120  // Weckinterval, wenn VAlternativ erreicht
