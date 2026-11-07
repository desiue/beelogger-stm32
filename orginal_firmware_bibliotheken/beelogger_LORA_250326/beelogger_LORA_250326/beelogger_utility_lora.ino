#include "beelogger_config.h"
//14.01.2020 Lora-Version
//25.04.2020 User Service
//03.05.2020 User Service Schalter
//14.07.2020 Servicewert minimal 1
//13.08.2020 clear rtc-int in User int
//04.05.2021 alarmtime
// 14.11.2021 service switch handling
// 18.11.2021 stray signal at D2 handling
// 16.05.2022 DHT Pinmode
// 13.07.2022 Service Switch handling
// 30.01.2023 change calls to save power

/**
  @brief Funktion Spannungen_messen(), speichert Messwerte in globalen Variablen
  @param none
  @return none
*/
void Spannungen_messen() {
  Batteriespannung = Messe_Spannung(Batterie_messen);

  debugprintF("Bat.[V]:");
  debugprintln(Batteriespannung);
  debugflush();

  if (Batteriespannung > VMinimum) {
    Solarspannung = Messe_Spannung(Solarzelle_messen);

    debugprintF("Sol.[V]:");
    debugprintln(Solarspannung);
    debugflush();

  }
}

/**
  @brief Funktion Messe_Spannung(), verwendet globale Kalibrierdaten
  @param ATmega Pin
  @return float Messwert
*/
float Messe_Spannung (uint8_t Pin) {
  int Messung_Spannung;
  float Spannung;
  Messung_Spannung = analogRead(Pin);
  Messung_Spannung = 0;
  for (uint8_t j = 0 ; j < 16; j++) {
    Messung_Spannung += analogRead(Pin);
  }
  Messung_Spannung = Messung_Spannung >> 2;
  Spannung = (float)map(Messung_Spannung, 0, Kalib_Bitwert, 0, Kalib_Spannung) / 1000.0;
  return (Spannung);
}

/**
  @brief Funktion Time_from_RTC(), holt Zeitstempel von RTC
  @param none
  @return Zeit im uint32_t Format = Unixtime
*/
uint32_t Time_from_RTC() {
  DateTime aktuell = rtc.now();
  uint32_t l_tm = aktuell.get();
  return (l_tm);
}


/**
  @brief Funktion Alarm_konfigurieren() LORA VERSION

  berechnet Einschaltzeitdauer des Systems
  berechnet nächste Alarmzeit für DS3231,
  setzt Alarmzeit in DS3231

  @param none
  @return none
*/
long IntervallMinuten = 10;

void Alarm_konfigurieren() {

  uint32_t l_tm = Time_from_RTC();
  uint32_t tt = l_tm - time_on;
  if (tt == 0) tt = 1;   // minimum 1 -> not a reset
  time_on = tt;          // store total TimeOn

  DateTime aktuell = rtc.now();
  uint8_t h_now = aktuell.hour();
  uint8_t month_now = aktuell.month();

  if (Batteriespannung > VAlternativ) {
    IntervallMinuten = WeckIntervall_default;
    // Marc: neue Konfig Winter/Sommer und Tag/Nacht
    if (WeckIntervall_aktiv) {  // Manuelle Intervalsteuerung aktiviert
#if  SOLAR_ZYKLUS_aktiv
      if (Solarspannung < SolarSpannung_Sommertag) { // es ist "Tag"
        IntervallMinuten = WeckIntervall_Nacht;
      }
      else {
        IntervallMinuten = WeckIntervall_Tag;
      }
#else
      if ( (h_now >= WeckIntervall_Nacht_Anfang ) || (h_now < WeckIntervall_Nacht_Ende) ) {
        IntervallMinuten = WeckIntervall_Nacht;
      } else {
        IntervallMinuten = WeckIntervall_Tag;
      }
#endif
#if  TEMP_ZYKLUS_aktiv
      if ( (WeckIntervall_Winter_aktiv == 1 ) && (DS_Temp < Temperatur_Wintertag) ) {  // es ist ein Wintertag
        IntervallMinuten = WeckIntervall_Winter;
      }
#else
      if (WeckIntervall_Winter_aktiv == 1) {  // Winterintervall aktiviert
        if ((month_now > WeckIntervall_Winter_Anfang) || (month_now < WeckIntervall_Winter_Ende)) {
          IntervallMinuten = WeckIntervall_Winter;
        }
      }
#endif
    }
  }
  else {
    IntervallMinuten = AlternativIntervallMinuten;
  }
  if (IntervallMinuten < 5) IntervallMinuten = 5;  // LoRaWan usage restrictions

  aktuell = (DateTime) ((long)l_tm + IntervallMinuten * 60); // xx Minuten später

  rtc.enableInterrupts(aktuell.hour(), aktuell.minute(), 0); //interrupt at  hour, minute, second

  debugprintF("Wakeup:");
  debugprint(aktuell.hour());
  debugprint(":");
#if myDEBUG
  if (aktuell.minute() < 10) debugprint("0");
#endif
  debugprintln(aktuell.minute());
  debugflush();
}


/**
  @brief Funktion Sleep_now()

  wenn Service Schalter gesetzt,
   dann wird ein ca. 5 Minuten Schlafintervall mit 8 Sekunden Schritten durchgeführt
   dies ignoriert auch Interrupts des DS3231 oder defekte DS3231 mit Daueralarm
  ansonsten wird
   der Interrupt an Pin D2 aktiviert,
   und wenn kein Interrupt erfolgt ist, der ATmega in dauerhaften LowPower versetzt

  @param none
  @return none
*/
void SleepNow() {
  LowPower.powerDown(SLEEP_2S, ADC_OFF, BOD_OFF); // wait if stray low-signal

  if (req_svc_sw() == 0) { // switch on, warte 5 Minuten
    uint8_t i, k;
    k = 1;  // default wait 5min
    if (stuck_switch  >= (USER_INT_TM / INTERVAL_STUCK_SWITCH)) {
      k = (IntervallMinuten / 5); // Intervall in  5min step
    }
    do {
      i = 33; // 33 = 5 Minuten Ruhe
      do {
        LowPower.powerDown(SLEEP_8S, ADC_OFF, BOD_OFF);
        calldelay(50);  // debounce switch at interrupt pin.
        i--;
      } while (i);
      k--;
    } while ((k) && (req_svc_sw() == 0));

  }
  else { // wait on DS3231 alarm or service switch
    stuck_switch = 0;
    ok_sleep = true;
    attachInterrupt(digitalPinToInterrupt(DS3231_Interrupt_Pin), WakeUp, LOW);  // D2 = Interrupt
    blink_LED(2, 100);  // signal
    while (ok_sleep) {
      LowPower.powerDown(SLEEP_FOREVER, ADC_OFF, BOD_OFF);
      calldelay(30);  // debounce switch at interrupt pin.
    }
  }
}

/**
  @brief Funktion WakeUp,  Interrupt-Routine zu attachInterrupt

  setzt globale Variable zurück,
  deaktiviert interrupt

  @param none
  @return none
*/
void WakeUp() {
  ok_sleep = false;
  detachInterrupt(digitalPinToInterrupt(DS3231_Interrupt_Pin));
}

/**
  @brief Funktion System_Off

  Schalte Spannung für Peripherie aus,
  rücksetzen CPU Bits für I2C ( see gammon.com.au )
  setzte alle benutzten Pins inaktiv

  @param none
  @return none
*/
void System_Off() {
  hx711_SCK_Low();    // code order, important!
  digitalWrite(Power_Pin, LOW);
  calldelay(5);

  TWCR &= ~(bit(TWEN) | bit(TWIE) | bit(TWEA));
  digitalWrite (A4, LOW);
  digitalWrite (A5, LOW);

  peripherial_off();                           // Lora SPI
}


/**
  @brief Funktion System_On

  Schalte Spannung für Peripherie ein,
  aktiviere relevante Pins
  initialisiere Uhr, DS3231
  Einschaltzeitdauer sichern und Startzeit setzen

  @param none
  @return none
*/
void System_On() {
  peripherial_on();                            // Lora SPI
  hx711_SCK_High();
#if (Anzahl_Sensoren_DHT > 0 )
  pinMode (DHT_Sensor_Pin[0], INPUT);
#endif
#if (Anzahl_Sensoren_DHT > 1)
  pinMode (DHT_Sensor_Pin[1], INPUT);
#endif

  digitalWrite(Power_Pin, HIGH);
  calldelay(10);             // vcc stable

  rtc.begin();
  rtc.clearINTStatus();
  calldelay(5);                 // wait clear Int

  Service = (float)time_on; //saving last TimeOn in Service
  time_on = Time_from_RTC();    // Set time_on

}

/**
  @brief Funktion User_Int

  teste ob Schalter an D2 hat Interrupt gesetzt hat
  teste ob Servicezyklus abgelaufen
  forciere Daten senden, wenn Service erstmalig aktiv

  @param none
  @return  1 wenn Service inaktiv, 0 wenn Service aktiv
*/
uint8_t User_Int() {             // User forced Interrupt ?
  uint8_t ret_val = 1;

#if (USER_INT_TM > 150)  // Limit Service switch time, important!
#undef USER_INT_TM
#define USER_INT_TM 150
#warning " USER_INT_TM  set to  150 minutes !"
#endif

  if (req_svc_sw() == 0) {      // switch on?
    blink_LED(1, 500);  // signal we've noticed
    if (stuck_switch == 0) {                  // first time only
      LowPower.powerStandby(SLEEP_2S, ADC_OFF, BOD_OFF);          // wait
      if (req_svc_sw() == 0) {  // still on?
        // User Service, force send data
        blink_LED(4, 200);  // signal we've noticed
        Service = 99.0;                       // report User Wakeup to server
        stuck_switch = 1;
        debugprintln ("User!");
      }
    }
    else {
      // return "switch is set", when still on and time is not up
      if (stuck_switch  < (USER_INT_TM / INTERVAL_STUCK_SWITCH) ) {
        ret_val = 0;
        stuck_switch++; // only increment within predefined period
      }
    }
  }
  else {
    stuck_switch = 0;
  }

  return (ret_val);
}


/**
  @brief Funktion blink_LED

  LED an Pin 13 blinken

  @param  Anzahl, Zeitdauer an/aus
  @return  none
*/
void blink_LED(uint8_t nmr, uint16_t bl_tm) {
  pinMode(13, OUTPUT);
  for (int i = 0; i < nmr; i++) {
    digitalWrite(13, LOW);  calldelay(bl_tm); // LED an
    digitalWrite(13, HIGH);  calldelay(bl_tm);
  }
  pinMode(13, INPUT);
}

/**
  @brief Funktion req_svc_sw

  auslesen Status des Interrupt Pin D2 für Service
  Achtung: auch der DS3231 kann den Pin dauerhaft setzen

  @param  none
  @return  Status Pin: 0 oder 1
*/
uint8_t req_svc_sw() {
  return (digitalRead(DS3231_Interrupt_Pin));
}


//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion freeRam
//----------------------------------------------------------------
int freeRam ()
{
  extern int __heap_start, *__brkval;
  int v;
  return (int) &v - (__brkval == 0 ? (int) &__heap_start : (int) __brkval);
}
//----------------------------------------------------------------
