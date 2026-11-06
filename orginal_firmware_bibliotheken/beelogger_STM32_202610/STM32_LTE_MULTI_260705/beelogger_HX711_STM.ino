/**
  @brief Funktion   Sensor_Gewicht

  initialisiere HX711, hole Messwerte, berechne Gewicht,
  vergleiche Schwarmalarmgewicht,
  unterdrücke kurzzeitige Messwertfehler

  @param  quick: kein Test auf Veränderungen zum alten Messwert.
  @return  überschreiten Schwarmalarmgewicht = 1
  Messwerte in globalen Variablen
*/
//18.02.2023 remove scale.read to detect HX711 available

#include <HX711.h>

uint8_t Sensor_Gewicht(uint8_t quick) {
  uint8_t ret_val = 0;
#if ((Anzahl_Sensoren_Gewicht > 0) && (Anzahl_Sensoren_Gewicht < 5))

  const float Diff_Gewicht = 0.5;
  HX711 scale;
  debugprintlnF("HX711");
  scale.begin(HX711_DT[0], HX711_SCK[0], 128); // Nr.1,2 channel A
  for (uint8_t i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
    if ((i == 1) || (i == 3)) {
      scale.set_gain(32);  // Nr.1,2 channel B
    }
    else if (i == 2) {
      scale.begin(HX711_DT[1], HX711_SCK[1], 128); // Nr.1,2 channel A
    }
    if (Taragewicht[i] != 10) {
      scale.power_up();
      delay(200);  // wait for power up
      if (scale.wait_ready_timeout(2000, 100)) {  //timeout, delay HX711
        scale.read(); // set gain using read
        delay(500);   // wait for stable HX711
        for (uint8_t j = 0 ; j < 2; j++) { // if weight not stable, repeat measurement
          long l_gew = scale.read_average(10) - Taragewicht[i];
          Gewicht[i] = ((float) l_gew) / Skalierung[i];
          if (quick) {
            LetztesGewicht[i] = Gewicht[i];
            break;
          }
          if (fabs(Gewicht[i] - LetztesGewicht[i]) < Diff_Gewicht) break;  // check for stable value
          delay(2000);// delay between measurements
        }
        if ((LetztesGewicht[i] - Gewicht[i]) > Alarm_Gewicht)
        {
          debugprintF("W:  ");
          if (Gewicht[i] < -1.0) {  //HX711 read error
            Gewicht[i] = LetztesGewicht[i];
          }
          else ret_val = 1;
        }

        LetztesGewicht[i] = Gewicht[i];
      }
      else {
        Gewicht[i] = No_Value; // error, no response from HX711
        debugprintF(" W fail");
      }
    }
    if ((i == 1) || (i == 3) ) { // 2 or 4 scales
      scale.power_down();
    }
    else if ((i == 0) && (Anzahl_Sensoren_Gewicht == 1)) { // only one scale
      scale.power_down();
    }
    else if ((i == 2) && (Anzahl_Sensoren_Gewicht == 3)) { // three scales
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

/**
  @brief Funktion  HX711 utility functions
  setup_hx711(): HX711 pin are set to power down; used in system setup
  hx711_SCK_Low(): HX711 SCK Pins LOW, DT to Input: used for system powerdown
  @param  none
  @return  none
*/
//
void setup_hx711() {
  pinMode(HX711_SCK[0], OUTPUT);
  digitalWrite (HX711_SCK[0], LOW);
#if Anzahl_Sensoren_Gewicht > 2
  pinMode(HX711_SCK[1], OUTPUT);
  digitalWrite(HX711_SCK[1], LOW);
#endif
  delay(2);
  digitalWrite (HX711_SCK[0], HIGH);
#if Anzahl_Sensoren_Gewicht > 2
  digitalWrite(HX711_SCK[1], HIGH);
#endif
}

// HX711 SCK Pins LOW, DT to Input: used for system powerdown
void hx711_SCK_Low() {
  digitalWrite (HX711_SCK[0], LOW);
  pinMode(HX711_SCK[0], INPUT);       //
  pinMode(HX711_DT[0], INPUT);        // turn off pullup
#if Anzahl_Sensoren_Gewicht > 2
  digitalWrite(HX711_SCK[1], LOW);
  pinMode(HX711_SCK[1], INPUT);       //
  pinMode(HX711_DT[1], INPUT);
#endif
}
