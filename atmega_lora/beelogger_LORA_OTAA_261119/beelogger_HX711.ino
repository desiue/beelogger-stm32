/**
  @brief Funktion  HX711 measurement and utility functions
  hx711_SCK_High(), hx711_SCK_Low()
  @param  none
  @return  none
*/
// 30.01.2023 Powersave
// 18.02.2023 wait for stable output

#include <HX711.h>

/**
  @brief Funktion   Sensor_Gewicht

  initialisiere HX711, hole Messwerte, berechne Gewicht,
  vergleiche Schwarmalarmgewicht,
  unterdrücke kurzzeitige Messwertfehler

  @param  quick: kein Test auf Veränderungen zum alten Messwert.
  @return  überschreiten Schwarmalarmgewicht = 1
          Messwerte in globalen Variablen
*/
uint8_t Sensor_Gewicht(uint8_t quick) {
  uint8_t ret_val = 0;
#if ((Anzahl_Sensoren_Gewicht > 0) && (Anzahl_Sensoren_Gewicht < 7))

  const float Diff_Gewicht = 0.5;
  HX711 scale;
  scale.begin(HX711_DT[0], HX711_SCK[0], 128); // Nr.1 channel A
  for (uint8_t i = 0; i < Anzahl_Sensoren_Gewicht; i++) {

    if ((i == 1) || (i == 3) || (i == 5)) {
      scale.set_gain(32);  // Nr.1,2,3 channel B
    }
    else if (i == 2) {
      scale.begin(HX711_DT[1], HX711_SCK[1], 128); // Nr.2 channel A
    }
    else if (i == 4) {
      scale.begin(HX711_DT[2], HX711_SCK[2], 128); // Nr.3 channel A
    }

    if (Taragewicht[i] != 10) {
      scale.power_up();
      if (scale.wait_ready_retry(3, 500)) {  //18.11.21
        scale.read(); // set gain using read
        calldelay(500); // wait for stable output
        for (uint8_t j = 0 ; j < 2; j++) { // if weight not stable, repeat measurement
          long l_gew = scale.read_average(10) - Taragewicht[i];
          Gewicht[i] = ((float) l_gew) / Skalierung[i];
          if (quick) {
            LetztesGewicht[i] = Gewicht[i];
            break;
          }
          if (fabs(Gewicht[i] - LetztesGewicht[i]) < Diff_Gewicht) break;  // check for stable value
          calldelay(2000);                                                 // wait between measurements
        }
        if ((LetztesGewicht[i] - Gewicht[i]) > Alarm_Gewicht) ret_val = 1; // swarm alarm

        LetztesGewicht[i] = Gewicht[i];
      }
      else {
        Gewicht[i] = No_Value; // error, no response from HX711
        debugprintF(" W fail");
      }
    }
    if ((i == 1) || (i == 3) || (i == 5)) { // 2, 4 or 6 scales
      scale.power_down();
    }
    else if ((i == 0) && (Anzahl_Sensoren_Gewicht == 1)) { // only one scale
      scale.power_down();
    }
    else if ((i == 2) && (Anzahl_Sensoren_Gewicht == 3)) { // three scales
      scale.power_down();
    }
    else if ((i == 4) && (Anzahl_Sensoren_Gewicht == 5)) { // five scales
      scale.power_down();
    }

    debugprintF("W ");
    debugprint(i + 1);
    debugprintF(" [kg] ");
    debugprintln(Gewicht[i]);
    debugflush();
  }
#endif
  return (ret_val);
}

// HX711 SCK Pins HIGH: used for system powering up
void hx711_SCK_High() {
  pinMode(HX711_SCK[0], OUTPUT);
  digitalWrite (HX711_SCK[0], HIGH);  
#if Anzahl_Sensoren_Gewicht > 2
  pinMode(HX711_SCK[1], OUTPUT);
  digitalWrite(HX711_SCK[1], HIGH);
#endif
#if Anzahl_Sensoren_Gewicht > 4
  pinMode(HX711_SCK[2], OUTPUT);
  digitalWrite(HX711_SCK[2], HIGH);
#endif
}

// HX711 SCK, DT to Input: used for system powerdown
void hx711_SCK_Low() {
  pinMode(HX711_SCK[0], INPUT);
  pinMode(HX711_DT[0], INPUT);
#if Anzahl_Sensoren_Gewicht > 2
  pinMode(HX711_SCK[1], INPUT);
  pinMode(HX711_DT[1], INPUT);
#endif
#if Anzahl_Sensoren_Gewicht > 4
  pinMode(HX711_SCK[2], INPUT);
  pinMode(HX711_DT[2], INPUT);
#endif
}
