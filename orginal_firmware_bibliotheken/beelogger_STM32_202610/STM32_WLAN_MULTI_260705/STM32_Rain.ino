// 30.06.2024 Zuordnung Aux[]
// 04.09.2024 Input_Pullup entfernt

//----------------------------------------------------------------
// Add-On  Sensor
//
//  Regenmesser
//
//----------------------------------------------------------------


//----------------------------------------------------------------
// Konfiguration Regenmesser
//----------------------------------------------------------------
#if  Anzahl_Sensor_Regen
#define Rain_Interrupt_Pin  PA15 // PA15 = Interrupt
volatile uint16_t  Rain_Counter;
volatile uint32_t  alteZeit;
#endif

/**
  @brief Regenmesser

  stelle Messwert bereit

  @param none
  @return none, sets global var
*/
void Sensor_Regen() {
#if  Anzahl_Sensor_Regen
  uint16_t r_cnt;
  noInterrupts();        // prevent rain-interrupt
  r_cnt = Rain_Counter;
  Rain_Counter = 0;
  interrupts();
  float f_Rain = Rain_Cal * (float)r_cnt;
  Aux[1] = f_Rain;        // Zuordnung Wert Regenmesser zu Sensor
#endif
}


/**
  @brief Init_Rain_Int

  konfiguriere Interrupt

  @param none
  @return none
*/
void Init_Rain() {
#if  Anzahl_Sensor_Regen
  Rain_Counter = 0;
  alteZeit = rtc_stm.getEpoch();
  LowPower.attachInterruptWakeup(Rain_Interrupt_Pin, Rain_Cnt, FALLING, DEEP_SLEEP_MODE);
#endif
}


/**
  @brief Rain_Cnt Interrupt Handler

  setzt globale Variable

  @param none
  @return none
*/
#if  Anzahl_Sensor_Regen
void Rain_Cnt() {
  // innerhalb der entprellZeit nichts machen
  uint32_t curTim = rtc_stm.getEpoch();
  if ((curTim - alteZeit) > 2) {  // entprellzeit 2 Sekunden
    Rain_Counter++;
    alteZeit = curTim;            // letzte Schaltzeit merken
  }
}
#endif
//----------------------------------------------------------------


/*
  Der Regenmesser verwendet ein Messsystem mit einer Wippe.
  Hier eine Beschreibung:
  http://www.elektronik-labor.de/Notizen/Regensensor.html
  Fertig fuer ca. 10Euro mit dem Stichwort "Rain Gauge" bei den diversen Verkaufsplattformen.
  Allgemeine Informationen in Sachen Regen messen:
  - Ausstellungsort
  https://www.wetterstation.net/regenmesser-aufstellen/
  - Art des Regens: Niesel, feiner Nebel oder auch gefroren (Schnee)
  Hier recht brauchbare Informationen zu Stärken und Schwächen der Niederschlagsmessverfahren:
  https://de.wikipedia.org/wiki/Niederschlagsmesser


  Beim beelogger funktioniert das Messen Interrupt gesteuert,
  so dass der Prozessor das Wippenbewegungen zaehlen zu jeder Zeit nebenbei erledigt.
  Damit dies mit der beelogger-Hardware möglich ist, sind kleinere Anpassungen notwendig:
  - Der Reed-Kontakt des Regenmessers wird am Anschluss Data (PA15) und GND angeschlossen.
  - Ein Filter aus einen 1k Widerstand und einem 100nF Kondensator



  Die Kalibrierung des Regenmessers ist recht einfach.

  - 100ml Wasser langsam durch das Messsystem laufen lassen und die Ticks vom beelogger zählen lassen.
     Eine größere Menge Wasser geht auch.

  - Die Wassermenge durch die Anzahl der Ticks teilen, bei mir gibt das dann ca. 5ml.

  - Die Oberfläche des Auffangbehälters berechnen: Oberfläche = 2* PI * (Radius zum Quadrat)
   Im getesten Aufbau ist die 104cm² (Trichter mit 11,5cm Durchmesser).

  Mit diesen Werten kann der Kalibrierwert errechnet werden.
  Kal_Wert =  Wassermenge pro Tick[ml]  / Oberfläche des Auffangbehälters[cm²];
  Der Wert ist auf Liter pro Quadratmeter umzurechnen.
  mit 10000 cm²/m² und 1000 ml/l ergibt sich Faktor 10

  Das ergibt im Beispiel einen Kalibrierfaktor mit (5ml / 104 cm²)*10  von  0,48 l/m².

*/
