// 29.04.2021  STM32
// 14.06.2021 Sendezeit verschiebbar, WLan Nachtabschaltung
// 08.09.2021 delay sending data
// 30.09.2021 Send Delay
// 16.03.2022 fix Ram Use of Wire
// 04.03.2023 Intervall >= 30 min
// 10.05.2023 fix Heap Ram usage, eeprom-class
// 29.09.2023 Check DO_NOT_SEND_HOUR
// 15.10.2023 use EE-Prom up to 128kByte in STM32F4x1 systems
// 28.02.2024 action on partly transfered data sets
// 16.07.2024 only one code for all STM32Fxyz
// 18.07.2024 Use EE-Prom 'round robin'
// 15.11.2024 dataset second = 0
// 06.01.2025 fix compress data

/**
  @brief Funktion Daten_Senden
  Daten in EE-Prom ablegen
  wenn Anzahl Messungen oder SendeZyklus erreicht: Senden
  komprimiert Daten wenn Sendefehler und EE-Prom voll

  @param  store=1: Daten abspeichern, verwendet bei aktivem Service
         globale Variablen
  @return  none, setzt globale Variablen
*/
void Daten_Senden(uint8_t store) {

  DatenString_erstellen();

  uint8_t time_to_send = 0;
  uint8_t cur_hour;
  uint8_t x, sum = 0;  // Checksum

  uint16_t ee_pos = 0;  // position in EE-Prom STM32F4xx


  if (store) {  // store in EE-Prom

    AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);
    debugprintln(DatenString);
    debugflush();
    for (x = 0; x < strlen(DatenString); x++) {
      sum += DatenString[x];
    }
    DatenString[_Daten_Satz_Len - 1] = sum;  // EE_Prom Checksum

    ee_pos = my_counter_start + my_counter;
    if (ee_pos >= _EE_DATA_SETS) ee_pos -= _EE_DATA_SETS;
    debugprintF("data_start: "); debugprintln(my_counter_start);
    debugprintF("ee_store: "); debugprintln(ee_pos);
    my_EEPROM.WriteMem(ee_pos * _Daten_Satz_Len, DatenString, _Daten_Satz_Len);
    my_EEPROM.end();
    my_counter++;
  }

  cur_hour = rtc_stm.getHours();

  if ((next_send_hour > SEND_HOUR) || (cur_hour <= SEND_HOUR)) {  // schedule past 6 or time before 6
    if (cur_hour >= next_send_hour) {                             // current hour is send-time
      time_to_send = 1;                                           // send
    }
  }
  if ((next_send_hour == SEND_HOUR) && (cur_hour > (SEND_HOUR - 1)) && (cur_hour < 12)) {  // schedule between 6 to 11 hour
    time_to_send = 1;                                                                      // force send
  }

  //* do not send at night time
#if DO_NOT_SEND_AT_NIGHT
#if (DO_NOT_SEND_BEGIN > 23)
#undef DO_NOT_SEND_BEGIN
#define DO_NOT_SEND_BEGIN 23
#warning " DO_NOT_SEND_BEGIN set to  23 !"
#endif
#if (DO_NOT_SEND_BEGIN < 18)
#undef DO_NOT_SEND_BEGIN
#define DO_NOT_SEND_BEGIN 23
#warning " DO_NOT_SEND_BEGIN set to  23 !"
#endif
  if ((cur_hour >= DO_NOT_SEND_BEGIN) || (cur_hour < SEND_HOUR)) {
    time_to_send = 0;
    calc_send_hour(cur_hour, 0);
  }
#endif


  if (time_to_send) {                                        // delay sending data within the hour
    if (snd_dly == 0) {                                      // no delay active
      snd_dly = 0x07 & (uint8_t)(analogRead(Batterie_messen)); // lower three bits 'random value'
      if (WeckIntervallMinuten <= 10) {
        if (snd_dly > 5) snd_dly = 5;  // send within the hour
      } else if (WeckIntervallMinuten <= 15) {
        if (snd_dly > 3) snd_dly = 3;  // send within the hour
      } else if (WeckIntervallMinuten <= 30) {
        snd_dly = 1;  // send within the hour
      } else {
        snd_dly = 0;  // immediate send
      }
    } else {
      snd_dly--;  // count down
    }
    if (snd_dly > 0) {
      time_to_send = 0;
    }
  }

  if ((my_counter >= nr_to_measure) ||  //  numer of measurements reached
      (time_to_send)) {                 //  it's time to send data


    uint16_t mysend = Send_Data(my_counter);
    if (mysend > 0) {
      cur_hour = rtc_stm.getHours();  // maybe new time from server
      if (mysend < my_counter) {      // only some data sent
        my_counter -= mysend;
        calc_send_hour(cur_hour, 1);
      } else {
        my_counter = 0;
        calc_send_hour(cur_hour, 0);
      }
    } else {  // error sending data
      debugprintlnF("error");
      calc_send_hour(cur_hour, 1);
      if (my_counter >= (_EE_DATA_SETS - 20)) {
        arrange_data();  // compress data
      }
    }
    my_counter_start = my_counter_start + mysend;
    if (my_counter_start >= _EE_DATA_SETS) my_counter_start -= _EE_DATA_SETS;
    debugprintF("data_start: "); debugprintln(my_counter_start);
    debugprintF("Counter: "); debugprintln(my_counter);

    nr_to_measure = _EE_DATA_SETS;  // set to normal number to measure

  }  // if counter
}

/**
  @brief Funktion arrange_data(uint16_t count, uint16_t comp)
  move or compress data in EE-Prom
  @param  count: Anzahl Daten oder gesendete Datensätze
          comp: Compress (2) or move (1)
          div. globale Variablen
  @return  none, setzt my_counter
*/
void arrange_data() {  //nn; 1 or 2

  AT24Cxx the_EEPROM(AT24Cxx_CTRL_ID);

  if (my_counter >= _EE_DATA_SETS) {
    my_counter /= 2;
    uint16_t cnts, to_pos, from_pos;
    debugprintln("\nCheck:"); debugflush();
#if 0
    char TODO_TODO;
    for (uint16_t a = 0; a < _EE_DATA_SETS; a++) {
      the_EEPROM.ReadMem(a * _Daten_Satz_Len, DatenString, _Daten_Satz_Len);
      debugprintln(DatenString); debugflush();
    }
#endif

    for (cnts = 0; cnts < my_counter; cnts++) { // compress data, elimiate every second measurement
      to_pos = (cnts + my_counter_start);
      if ( to_pos >= _EE_DATA_SETS) to_pos -=  _EE_DATA_SETS;
      from_pos = (cnts * 2 + my_counter_start + 1);
      if ( from_pos >= _EE_DATA_SETS) from_pos -=  _EE_DATA_SETS;
#if 0
      debugprint("from: "); debugprint(from_pos); debugprint("  to: "); debugprintln(to_pos); debugflush();
#endif
      the_EEPROM.ReadMem(from_pos * _Daten_Satz_Len, DatenString, _Daten_Satz_Len);
      the_EEPROM.WriteMem(to_pos * _Daten_Satz_Len, DatenString, _Daten_Satz_Len);
    }

#if 0
    debugprintln("compressed:");
    for (uint16_t b = 0; b < _EE_DATA_SETS; b++) {
      the_EEPROM.ReadMem(b * _Daten_Satz_Len, DatenString, _Daten_Satz_Len);
      debugprintln(DatenString); debugflush();
    }
    debugprintln(" ");
#endif
  }

  the_EEPROM.end();
}
/**
  @brief Funktion calc_send_hour()
  calculate next time to send data
  @param  c_hour: aktuelle Stunde, failed: Fehler beim Daten senden
         globale Variablen
  @return  none, setzt globale Variablen
*/
void calc_send_hour(uint8_t c_hour, uint8_t failed) {
  uint8_t s_cycle;

#if (SEND_CYC_RTY < 1)
#undef SEND_CYC_RTY
#define SEND_CYC_RTY 2
#endif
#if (LONG_CYCLE < 1)
#undef LONG_CYCLE
#define LONG_CYCLE 2
#endif

  if (failed) {
    s_cycle = SEND_CYC_RTY;  // error sending data, set retry cycle
  } else {
    s_cycle = send_cycle;  // use cycle information from server
    if (WeckIntervallMinuten >= 30) s_cycle = send_cycle * LONG_CYCLE;
  }

  if (s_cycle < 1) s_cycle = 1;    // minimum 1 hour
  if (s_cycle > 24) s_cycle = 24;  // at least once a day

  next_send_hour = (c_hour + s_cycle) % 24;  // next time to send
  if (next_send_hour <= c_hour) {            // next day
    if (next_send_hour > SEND_HOUR) {        // beyond 6 hours
      next_send_hour = SEND_HOUR;            // force send at 6 hours
    }
  }

  if ((c_hour < SEND_HOUR) && ((c_hour + s_cycle) > SEND_HOUR)) {  // before 6 hours
    next_send_hour = SEND_HOUR;                                    // force send at 6 hours
  }
}


/**
  @brief Funktion DatenString_erstellen()
  erstellt konfigurationsabhängigen Datensatz zum abspeichern im EE-Prom

  @param  none,        globale Variablen
  @return  none
*/
void DatenString_erstellen() {

  int count = 0;
  count = sprintf(DatenString, "20%2.2d/", rtc_stm.getYear());
  count += sprintf(DatenString + count, "%2.2d/", rtc_stm.getMonth());
  count += sprintf(DatenString + count, "%2.2d_", rtc_stm.getDay());
  count += sprintf(DatenString + count, "%2.2d:", rtc_stm.getHours());
  count += sprintf(DatenString + count, "%2.2d:00", rtc_stm.getMinutes());
//  count += sprintf(DatenString + count, "%2.2d", rtc_stm.getSeconds());

  if (Anzahl_Sensoren_Gewicht > 1) {
    count = Wert_hinzufuegen(count, DS_Temp, 1, No_Val);  // Wägezellentemperatur
  }
  count = Wert_hinzufuegen(count, SensorTemp[Beute1], 1, No_Val);  // Stocktemperatur 1
  if (Anzahl_Sensoren_Gewicht > 1) {
    count = Wert_hinzufuegen(count, SensorTemp[Beute2], 1, No_Val);  // Stocktemperatur 2
  }
  if (Anzahl_Sensoren_Gewicht > 2) {
    count = Wert_hinzufuegen(count, SensorTemp[Beute3], 1, No_Val);  // Stocktemperatur 3
  }
  if (Anzahl_Sensoren_Gewicht > 3) {
    count = Wert_hinzufuegen(count, SensorTemp[Beute4], 1, No_Val);  // Stocktemperatur 4
  }
  count = Wert_hinzufuegen(count, SensorTemp[Aussenwerte], 1, No_Val);  // Außentemperatur

  count = Wert_hinzufuegen(count, SensorFeuchte[Beute1], 1, No_Value);  // Stockluftfeuchte 1
  if (Anzahl_Sensoren_Gewicht > 1) {
    count = Wert_hinzufuegen(count, SensorFeuchte[Beute2], 1, No_Value);  // Stockluftfeuchte 2
  }
  if (Anzahl_Sensoren_Gewicht > 2) {
    count = Wert_hinzufuegen(count, SensorFeuchte[Beute3], 1, No_Value);  // Stockluftfeuchte 3
  }
  if (Anzahl_Sensoren_Gewicht > 3) {
    count = Wert_hinzufuegen(count, SensorFeuchte[Beute4], 1, No_Value);  // Stockluftfeuchte 4
  }
  count = Wert_hinzufuegen(count, SensorFeuchte[Aussenwerte], 1, No_Value);  // Außenluftfeuchte

  count = Wert_hinzufuegen(count, Licht, 1, No_Value);       // Licht
  count = Wert_hinzufuegen(count, Gewicht[0], 3, No_Value);  // Gewicht 1
  if (Anzahl_Sensoren_Gewicht > 1) {
    count = Wert_hinzufuegen(count, Gewicht[1], 2, No_Value);  // Gewicht 2
  }
  if (Anzahl_Sensoren_Gewicht > 2) {
    count = Wert_hinzufuegen(count, Gewicht[2], 2, No_Value);  // Gewicht 3
  }
  if (Anzahl_Sensoren_Gewicht > 3) {
    count = Wert_hinzufuegen(count, Gewicht[3], 2, No_Value);  // Gewicht 4
  }

  count = Wert_hinzufuegen(count, Batteriespannung, 2, No_Value);  // Akkuspannung
  count = Wert_hinzufuegen(count, Solarspannung, 2, No_Value);     // Solarspannung
  count = Wert_hinzufuegen(count, Service, 2, No_Value);           // Service
  count = Wert_hinzufuegen(count, Aux[0], 1, No_Value);            // Aux 0
  count = Wert_hinzufuegen(count, Aux[1], 2, No_Value);            // Aux 1
  count = Wert_hinzufuegen(count, Aux[2], 2, No_Value);            // Aux 2
  DatenString[count] = 0;
}


/**
  @brief Funktion Wert_hinzufuegen()
  erstellt Stringanteil für Datensatz

  @param
  count: aktuelle Position im String,
  Wert: Messwert,
  Nachkommastellen: Anzahl der Nachkommastellen für diesen Messwert
  Fehler: Vergleichswert für fehlerhaften oder unbenutzten Messwert
  @return  neue Position im Datenstring
*/
int Wert_hinzufuegen(int count, float Wert, uint8_t Nachkommastellen, float Fehler) {
  char Konvertierung[16];
  int count_neu = count;

  if (Wert == Fehler) {
    count_neu += sprintf(DatenString + count, ",%s", "");
  } else {
    dtostrf(Wert, 1, Nachkommastellen, Konvertierung);
    count_neu += sprintf(DatenString + count, ",%s", Konvertierung);
  }
  return count_neu;
}
//----------------------------------------------------------------
