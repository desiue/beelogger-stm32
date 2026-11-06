// 13.09.2021 WakeUp on POR
// 02.11.2021 calc wakeup
// 01.12.2021 Service switch led blink code
// 24.12.2021 led blink codes
// 13.01.2022 Clear Wakeup-Flag before activating interrupt
// 19.01.2022 System_init
// 16.02.2022 System_Init, Alarm_konfiguration, new Pwr_flags()
// 15.03.2022 rain sensor
// 22.05.2022 swarm alarm
// 10.06.2022 swarm alarm intervall
// 13.06.2022 backup Ram usage
// 13.07.2022 Service Switch handling
// 19.12.2022 CPU_Sensors
// 04.03.2023  401CC / 401CE
// 09.05.2023 Weckintervall bat low
// 01.09.2023 STM32-RTC/LowPower-Lib new Versions

#include "time.h"

/**
  @brief Funktion sw_alarm
  forciere Data send, when swarm alarm detected, send data at two following intervalls

  @param alarm swarmalarm
  @return  none
*/
void sw_alarm( uint8_t alarm) {
#if SCHWARM_ALARM
  if (stuck_switch != 0) {
    s_alarm = 0; // reset alarm when user switch
  }
  else {
    if (s_alarm > 0) {    // swarm alarm is aktive
      debugprintF("Alarm."); debugflush();
      s_alarm --;
      nr_to_measure = 1;  //force send
    }
    else {
      if (alarm) {  // alarm, force send, at least 10 min Intervall
        debugprintF("Alarm!"); debugflush();
        nr_to_measure = 1;  //force send
        s_alarm = 2;        // two sends  (max three)
      }
    }
  }
#endif
}
// swarm alarm interval
void sw_alarm_int() {
  if (s_alarm) {
    if (WeckIntervallMinuten > 10)  WeckIntervallMinuten = 10;
  }
}

/**
  @brief Funktion Spannungen_messen(), speichert Messwerte in globalen Variablen
  @param none
  @return none
*/
void Spannungen_messen() {

  Batteriespannung = Messe_Spannung(Batterie_messen);

  debugprintF("Bat. [V]: ");
  debugprintln(Batteriespannung);
  debugflush();

  if (Batteriespannung > VMinimum) {
    Solarspannung = Messe_Spannung(Solarzelle_messen);

    debugprintF("Sol. [V]: ");
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
  float Spannung;
  int32_t bits_Messung;

  bits_Messung = analogRead(Pin);
  bits_Messung = 0;
  for (uint8_t j = 0 ; j < 16; j++) {
    bits_Messung += analogRead(Pin);
  }
  debugflush();
  bits_Messung = bits_Messung / 16;
  debugprintF("ADC-bits: ");  debugprint(bits_Messung);  debugprintF("  ");
  Spannung = (float)map(bits_Messung, 0, Kalib_Bitwert, 0, Kalib_Spannung) / 1000.0;

  return (Spannung);
}


/**
  @brief Funktion System_init()

  konfiguriere Echtzeituhr,
  initialisiere Laufzeitvariablen abhänig von Reset oder PowerOnReset
  Hole und setze Einschaltzeitdauer

  @param none
  @return none
*/
void System_Init() {

  //----------------------------------------------------------------
  //  a real pull-down-resistor should be used in addition
  pinMode(WakeUp_Pin, INPUT_PULLDOWN); // Prepare WakeUp pin PA0
  delay(2);
  //----------------------------------------------------------------

  stop_dfue_device(); // power off or reset
  stop_dfue_deviceLTE(); // power off or reset

  //----------------------------------------------------------------
  // Setup RTC:
  // Select RTC clock source: LSI_CLOCK, LSE_CLOCK or HSE_CLOCK.
  // LSE = 32kHz clock (RTCCLK),  LSI = 40kHz CPU Clock (IWDGCLK), HSE_Clock = CPU Clock (PLLCLK/128)
  // By default the LSI is selected as source.
  //----------------------------------------------------------------
  rtc_stm.setClockSource(STM32RTC::LSE_CLOCK);  // 32kHz source for RTC
  rtc_stm.begin(false);                         // initialize RTC 24H format
  rtc_stm.disableAlarm();                       //  may be User WakeupPin Interrupt, disable rtc Alarm
  LowPower.begin();
  //----------------------------------------------------------------


  //----------------------------------------------------------------
  //  Config ADC to 12 bit
  //----------------------------------------------------------------
  analogReadResolution(12);        // STM32 has 12 bit resolution
  CPU_Sensors();

  //----------------------------------------------------------------
  // Initialize the some variable only on first power-on reset
  //----------------------------------------------------------------
  // restore runtime values and systemtime
  nr_to_measure = _Num_Measured;
  get_data_from_ram();  // needs LowPower.begin() and rtc_stm.begin()

  if (__HAL_RCC_GET_FLAG(RCC_FLAG_PORRST)) {  // Power On Reset
    my_counter = 0;   // Power On Reset
    nr_to_measure = 0; // force communication to server
  }

  //----------------------------------------------------------------
  //  RTC Sleep Mode Interrupt
  //----------------------------------------------------------------
  LowPower.enableWakeupFrom(&rtc_stm, WakeUpRTC, &wake_up_var );
  //----------------------------------------------------------------
  Setup_Serial();

  debugprint(EEProm_Size);
  debugprint(" ");
  debugprint( _Daten_Satz_Len);
  debugprint(" ");
  debugprintln( _Num_Measured);
  debugflush();
  //----------------------------------------------------------------
  if (nr_to_measure == 0) {  //__HAL_RCC_GET_FLAG(RCC_FLAG_PORRST)
    debugprintln("POR");     // Power On Reset
  }
  if (__HAL_RCC_GET_FLAG(RCC_FLAG_PINRST)) {
    debugprintln("RST");
  }
  debugflush();

  //----------------------------------------------------------------
  //  Clear the flags
  //----------------------------------------------------------------
  __HAL_RCC_CLEAR_RESET_FLAGS();
  Pwr_flags();

  display_time();
}

/**
  @brief Funktion Setup_Serial
      start Serial, wait till ready
      STM32-MONITOR_RX_TX for info and debug output
  @param none
  @return none
*/
void Setup_Serial() {
  debugbegin(Serial_Baudrate);
#if myDEBUG
  while (!MONITOR_RX_TX) {};         // Wait for serial port open
  delay(300);
#endif
  debugprintln(" ");
  debugprintln(ID_ID);

#if defined(ARDUINO_BLACKPILL_F411CE)
  debugprintln("STM32F411CE");
#endif
#if defined(ARDUINO_BLACKPILL_F401CE)
  debugprintln("STM32F401CE");
#endif
#if defined(ARDUINO_BLACKPILL_F401CC)
  debugprintln("STM32F401CC");
#endif
#if defined(ARDUINO_BLUEPILL_F103CB)
  debugprintln("STM32F103CB");
#endif
#if defined(ARDUINO_BLUEPILL_F103C8)
  debugprintln("STM32F103C8");
#endif
  debugflush();
}

/**
  @brief Funktion Pwr_flags()
  check and reset STM32 WakeUp/Standby- flags
  @param none
  @return none
*/
void Pwr_flags() {
  if ( __HAL_PWR_GET_FLAG(PWR_FLAG_SB) != RESET)
  { // Wakeup from RTC Standby
    __HAL_PWR_CLEAR_FLAG(PWR_FLAG_SB); // Clear Standby Flag
  }
  __HAL_PWR_CLEAR_FLAG(PWR_FLAG_WU); // Clear WakeUp Flag always
}
/**
  @brief Funktion System_On()

  Schalte Spannung für Peripherie ein,
  aktiviere relevante Pins

  @param none
  @return none
*/
void System_On() {
  debugprint("System On: ");
  debugprint(ok_sleep);
  debugflush();
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);
  setup_hx711();  // hx711 power down
  delay (5);
}

/**
  @brief Funktion System_Shutdown()

  Schalte Spannung für Peripherie aus,
  causes system reset on wakeup, all global vars lost

  @param none
  @return none
*/
void System_ShutDown() {

  display_mem_info();
  display_time();
  Alarm_konfigurieren();
  save_data_to_ram();
  print_info();
  debugend();         // release serial
  blink_LED(2, 100);  // signal

  digitalWrite(Power_Pin, LOW);
  hx711_SCK_Low();    // code order, important!
  delay(5);

  ok_sleep = 1;
  while (ok_sleep == 1) {   // Interrupt from rain counter
#if  (Anzahl_Sensor_Regen || defined (STM32F1xx))
    // STM32F1xx rtc does not keep day/month/year during shutdown
    LowPower.deepSleep();   // STM32-Stop Mode, low µC current
#else
    LowPower.shutdown();    // Mode causes Reset on Alarm or WKUP
#endif
    delay(50);              // debounce switch
  }
  rtc_stm.disableAlarm();   // may be User WakeupPin Interrupt, disable rtc Alarm
}


/**
  @brief Funktion User_Int

  test if switch at WakeUpPin is set
  ret_val = 1: measuremnt and ev. send data
  ret_val = 0: switch is permanent on
  teste ob Servicezyklus abgelaufen
  forciere Daten senden, wenn Service erstmalig aktiv

  @param none
  @return  1 wenn Service inaktiv, 0 wenn Service aktiv
*/
uint8_t User_Int_req() {  // User forced Wakeup
  uint8_t ret_val = 1;

#if (USER_INT_TM > 150)  // Limit Service switch time, important!
#undef USER_INT_TM
#define USER_INT_TM 150
#warning " USER_INT_TM  set to  150 minutes !"
#endif

  Service = (float)time_on;          // store TimeOn from previous run
  time_on = rtc_stm.getEpoch();

  if (digitalRead(WakeUp_Pin) == true) {      // switch on?
    blink_LED(1, 500);  // signal we've noticed
    if (stuck_switch == 0) {                  // first time only
      delay(2000);          // wait
#if CSQ_SWITCH
      if (digitalRead(WakeUp_Pin) == true) {  // still on?
        // User Service, force send data
        blink_LED(3, 200);                    // signal we've noticed
        Service = 999.0;                      // report Signal Quality to server
        nr_to_measure = 1;                    // force send
      }
      delay(1000);          // wait
#endif

      if (digitalRead(WakeUp_Pin) == true) {  // still on?
        // User Service, force send data
        blink_LED(4, 200);                    // signal we've noticed
        Service = 99.0;                       // report User Wakeup to server
        nr_to_measure = 1;                    // force send
        stuck_switch = 1;
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

  debugprint (" sw:");
  debugprintln (stuck_switch);
  return (ret_val);
}

/**
  @brief Funktion Alarm_konfigurieren()

  berechnet Einschaltzeitdauer des Systems
  berechnet nächste Alarmzeit für rtc,
  setzt Alarmzeit in rtc
  take care of midnight not updateting day, month,year

  @param none
  @return none
*/
void Alarm_konfigurieren() {
  uint8_t IntervallMinuten;
  uint32_t ttim;
  uint32_t tt;

  ttim = rtc_stm.getEpoch();
  time_on = ttim - time_on + 1;    // time system has been 'on'
  if (time_on > 255) {             // Power On Reset or invalid date
    time_on = 0;
  }

  if ((digitalRead(WakeUp_Pin) == true))  // service mode ongoing
  { // PA0 Switch on/off only, no interrupt; important!
    HAL_PWR_DisableWakeUpPin( PWR_WAKEUP_PIN1 );
  } else {
    stuck_switch = 0;
    // Attach a wakeup from User Service Switch, WakeUp_Pin must be Pin PA0
#if  (Anzahl_Sensor_Regen || defined (STM32F1xx))
    // STM32F1xx rtc does not keep day/month/year during shutdown
    LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING, DEEP_SLEEP_MODE);
#else
    LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING, SHUTDOWN_MODE);
#endif
  }

  Pwr_flags(); // Clear Flags

  if (Batteriespannung <= VAlternativ) {  // Battery low, use alternativ Interval
    IntervallMinuten = AlternativIntervallMinuten;
    WeckIntervallMinuten = 45; // suppress send delay
  }
  else {
    if ((stuck_switch) && (stuck_switch  < (USER_INT_TM / INTERVAL_STUCK_SWITCH)))   // service mode
    {
      IntervallMinuten = INTERVAL_STUCK_SWITCH;     // Check switch every xx minutes
    } else {
      IntervallMinuten = WeckIntervallMinuten;      // normal interval
    }
  }
  if (IntervallMinuten < 5) IntervallMinuten = 5;
  tt = ttim + (uint32_t)(IntervallMinuten * 60); // calculate next wakeup
  tt = tt - tt % 300;   // synchronize wakeup step, set seconds to zero
  tt--;                 // prevent wakeup at xx:00:00

#if myDEBUG
  char buff[64];
  time_t wakeup_time = tt;
  strftime (buff, 60, "%d.%m.%Y %H:%M:%S", localtime (&wakeup_time));
  debugprintF(" Weckzeit "); debugprintln(buff);  debugflush();
#endif

  // wakeup as calculated
  rtc_stm.setAlarmEpoch(tt, rtc_stm.MATCH_HHMMSS, 0); // calls enableAlarm
}


/**
  @brief Funktion WakeUp,  Interrupt-Routine zu WakeUpPin

  setzt globale Variable

  @param none
  @return none
*/
void WakeUp() {
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  ok_sleep = 2;
}

/**
  @brief Funktion WakeUpRTC,  Interrupt-Routine zu wakeup by rtc alarm/interrupt

  setzt globale Variable

  @param none
  @return none
*/
void WakeUpRTC(void * data) {
  UNUSED(data);
  // This function will be called once on device wakeup
  // You can do some little operations here (like changing variables which will be used in the loop)
  // Remember to avoid calling delay() and long running functions since this functions executes in interrupt context
  ok_sleep = 3;
}


/**
  @brief Funktion get_data_from_ram()

  get data from saved in RTC-RAM
  restore year, month, day since rtc does not keep them during shutdown

  @param none
  @return none
*/
void get_data_from_ram() {
  //   uint32_t getBackupRegister(uint32_t index)  // only 16 bit values
  uint32_t reg;  // register: only 16 bits may be used

  uint16_t tty = (uint16_t) rtc_stm.getYear();

  if ((tty < 22) || (tty > 33)) { // valid year
    // restart w/o valid date, handle like POR
    my_counter = 0;   // Power On Reset
    nr_to_measure = 0; // force communication to server
  }
  else {

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7

    reg = getBackupRegister(3);
    my_counter = (uint8_t)(reg >> 8);       // upper 8 bits 0...255
    send_cycle = (uint8_t) (reg & 0x001f);  // 1,2,4,8,16
    reg = reg  >> 6;
    report_info = (uint8_t) (reg & 0x0003); // 0,1,2,3

    reg = getBackupRegister(4);
    WeckIntervallMinuten = (uint8_t) (reg & 0x00ff); // max. 180 min
    time_on = (uint32_t) (reg >> 8);   // max 255sec.

    reg = getBackupRegister(5);
    stuck_switch = (uint8_t) (reg & 0x001f);    // lower five bits  0 ... 31
    s_alarm = (uint8_t) ((reg >> 5) & 0x0007);  // upper three bits 0 ... 7
    reg = reg >> 8; // high Byte
    next_send_hour = (uint8_t) (reg & 0x001f);  // lower five bits  0 ... 24
    snd_dly = (uint8_t) ((reg >> 5) & 0x0007);  // upper three bits 0 ... 7

    reg = (uint16_t)getBackupRegister(1);    // reg 1
    LetztesGewicht[3] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(2);    // reg 2
    LetztesGewicht[2] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(8);
    LetztesGewicht[1] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(9);
    LetztesGewicht[0] = (float) reg / 100.0;

#else

    reg = getBackupRegister(2);
    my_counter =  (reg & 0x00ff);

    reg = getBackupRegister(3);
    next_send_hour = (uint8_t) (reg >> 8);  // upper 8 bits
    send_cycle = (uint8_t) (reg & 0x001f);  // 1,2,4,8,16
    reg = reg  >> 6;
    report_info = (uint8_t) (reg & 0x0003); // 0,1,2,3

    reg = getBackupRegister(4);
    WeckIntervallMinuten = (uint8_t) (reg & 0x00ff); // max. 180 min
    time_on = (uint32_t) (reg >> 8);   // max 255sec.

    reg = getBackupRegister(5);
    stuck_switch = (uint8_t) (reg & 0x001f);    // lower five bits  0 ... 31
    s_alarm = (uint8_t) ((reg >> 5) & 0x0007);  // upper three bits 0 ... 7
    snd_dly = (uint8_t) (reg >> 8);             // high byte

    reg = (uint16_t)getBackupRegister(6);
    LetztesGewicht[3] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(7);
    LetztesGewicht[2] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(8);
    LetztesGewicht[1] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(9);
    LetztesGewicht[0] = (float) reg / 100.0;
#endif
  }
}

/**
  @brief Funktion save_data_from_ram()

  save globals vars to RTC-RAM
  save year, month, day since rtc does not keep them during shutdown

  @param none
  @return none
*/
void save_data_to_ram() {
  enableBackupDomain();   // RTC Backup Registers
  // setBackupRegister(uint32_t index, uint32_t value)  // works only with value in uint16, but in uint32_t format
  // setBackupRegister(0, reg); register 0 is used by the STM32-RTC Lib V 1.1.0
  uint32_t reg = 0;

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7

  reg = my_counter << 8;            // max 255
  reg = reg | (report_info << 6);   // 0,1,2,3
  reg = reg | (send_cycle & 0x1f);  // 1,2,4,8,16
  setBackupRegister(3, reg);

  reg = time_on << 8;                // max 255 sec.
  reg = reg | WeckIntervallMinuten;  // max. 180 min. (Weckalternativ!)
  setBackupRegister(4, reg);

  reg = next_send_hour;        // lower five bits  0 ... 24
  reg = reg | (snd_dly << 5);  // upper three bits  0 ... 7
  reg = reg << 8;              // high Byte

  reg = reg | stuck_switch;    // lower five bits  0 ... 31
  reg = reg | (s_alarm << 5);  // upper three bits  0 ... 7
  setBackupRegister(5, reg);

  reg = (uint32_t) (LetztesGewicht[3] * 100.0);
  setBackupRegister(1, reg);                    // reg 1
  reg = (uint32_t) (LetztesGewicht[2] * 100.0);
  setBackupRegister(2, reg);                    // reg 2
  reg = (uint32_t) (LetztesGewicht[1] * 100.0);
  setBackupRegister(8, reg);
  reg = (uint32_t) (LetztesGewicht[0] * 100.0);
  setBackupRegister(9, reg);

#else

  reg = my_counter;     // max 255
  setBackupRegister(2, reg);

  reg = next_send_hour << 8;
  reg = reg | (report_info << 6); // 0,1,2,3
  reg = reg | (send_cycle & 0x1f);  // 1,2,4,8,16
  setBackupRegister(3, reg);

  reg = time_on << 8;                // max 255 sec.
  reg = reg | WeckIntervallMinuten;  // max. 180 min. (Weckalternativ!)
  setBackupRegister(4, reg);

  reg = snd_dly << 8;          // high Byte: snd_dly max 255
  reg = reg | stuck_switch;    // lower five bits  0 ... 31
  reg = reg | (s_alarm << 5);  // upper three bits  0 ... 7
  setBackupRegister(5, reg);

  reg = (uint32_t) (LetztesGewicht[3] * 100.0);
  setBackupRegister(6, reg);
  reg = (uint32_t) (LetztesGewicht[2] * 100.0);
  setBackupRegister(7, reg);
  reg = (uint32_t) (LetztesGewicht[1] * 100.0);
  setBackupRegister(8, reg);
  reg = (uint32_t) (LetztesGewicht[0] * 100.0);
  setBackupRegister(9, reg);
#endif
  //disableBackupDomain();   // RTC Backup Registers -> Stop Mode doesn't work
  //HAL_PWR_DisableBkUpAccess();
}


/**
  @brief Funktion blink_LED

  on board LED an Pin PC13 blink

  @param  Anzahl, Zeitdauer an/aus
  @return  none
*/
void blink_LED(uint8_t nmr, uint16_t bl_tm) {
  pinMode(PC13, OUTPUT);
  for (int i = 0; i < nmr; i++) {
    digitalWrite(PC13, LOW);  delay(bl_tm); // LED an
    digitalWrite(PC13, HIGH);  delay(bl_tm);
  }
  pinMode(PC13, INPUT);
}


/**
  @brief Funktion print_info()  debug output sketch status info
  @param  none
  @return  none
*/
void print_info() {
  debugprint("  Nr:");
  debugprint(my_counter);
  debugprint("  nextsend: ");
  debugprint(next_send_hour);
  debugprint("h  send-cyc: ");
  debugprint(send_cycle);
  debugprint("h  Interval: ");
  debugprint(WeckIntervallMinuten);
  debugprint("min  t-on: ");
  debugprint(time_on);
  debugprint("s  dly: ");
  debugprint(snd_dly);
  debugprint("  sw: ");
  debugprint(stuck_switch);
  debugprint("  report:");
  debugprint(report_info);
  debugprint("  al:");
  debugprintln(s_alarm);
  debugflush();
}

/**
  @brief Funktion display_time() Anzeige Uhrzeit als Debug Info
  @param  none
  @return  none
*/
void display_time() {
#if myDEBUG
  char buff[64];
  time_t wakeup_time = rtc_stm.getEpoch();
  strftime (buff, 60, "%d.%m.%Y %H:%M:%S", localtime (&wakeup_time));
  debugprintF("Datum:    "); debugprintln(buff);
#endif
}
