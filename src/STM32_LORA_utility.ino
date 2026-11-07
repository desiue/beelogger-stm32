// Geändert gegenüber beelogger.de STM32_LORA_260705 (GPL v3 §5a):
//   2026-11-06, desiue (github.com/desiue/beelogger-stm32): Startzeile Name/UID/DevAddr; FernIntervall in Backup-Register 5 Bit 9-14; Vorrang in Alarm_konfigurieren().
//   2026-11-06, desiue: OTAA - Session nach Power-On-Reset/ungültigem Datum verwerfen, DevAddr in Startzeile aus Join.
//   2026-11-07, desiue: Software-Watchdog (TIM4, Backup-Register F1: 10, F4: 14) und i2c_bus_frei().
//   Vollständige Änderungen: docs/aenderungen_gegen_260705.diff

//20.10.2021 STM32_LORA
// 02.11.2021 calc wakeup
// 01.12.2021 Service switch led blink code
// 24.12.2021 led blink codes
// 16.02.2022 System_Init, Alarm_konfiguration, new Pwr_flags()
// 19.12.2022 CPU_Sensors
// 04.03.2023 401CC / 401CE
// 30.05.2023 EE-Prom Lib, stuck-switch in Backup-Ram
// 10.07.2023 Fix data read BackupRam, print_info in System_Init
// 02.10.2023 wakeup time force at least 5 min
// 08.10.2023 frame counter in backup-ram



#define Interval_Stuck_Switch 5  // minimal 5 Minuten

#include "time.h"

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
  // Initialize the variable only on first power-on reset
  //----------------------------------------------------------------
  if (__HAL_RCC_GET_FLAG(RCC_FLAG_PORRST)) {
    nr_to_measure = 0;
    rtc_stm.setYear(22);  // LORA w/o server response
    rtc_stm.setMonth(1);
    rtc_stm.setDay(1);
    LoRaSeqCounter = 0;   // reset framecounter / sequence number
#if LORA_OTAA
    otaa_clear();         // Stromausfall -> Neu-Join
#endif
  }
  else { // Reset Wakeup STM32 after shutdown
    nr_to_measure = 1;    // LORA send immediate
    get_data_from_ram();  // always, because of LoRaSeq; needs LowPower.begin() and rtc_stm.begin()
    // runtime values and systemtime have been restored
  }

  //----------------------------------------------------------------
  //  Sleep Mode Interrupt
  //----------------------------------------------------------------
  // Attach a wakeup from RTC. call of deepsleep, shutdown must not use any time
  LowPower.enableWakeupFrom(&rtc_stm, WakeUpRTC, &wake_up_var );
  //----------------------------------------------------------------

  //----------------------------------------------------------------
  // Setup Serial (STM32-MONITOR_RX_TX) for info and debug output
  debugbegin(Serial_Baudrate);
#if myDEBUG
  while (!MONITOR_RX_TX) {};         // Wait for serial port open
#endif
  debugprintln(" "); debugprint(ID_ID); debugprint(l_ID); debugprint(" - ");
#if defined(ARDUINO_BLACKPILL_F411CE)
  debugprintln("STM32F411CE");
#endif
#if defined(ARDUINO_BLACKPILL_F401CC)
  debugprintln("STM32F401CC");
#endif
#if defined(ARDUINO_BLACKPILL_F401CE)
  debugprintln("STM32F401CE");
#endif
#if defined(ARDUINO_BLUEPILL_F103CB)
  debugprintln("STM32F103CB");
#endif
#if defined(ARDUINO_BLUEPILL_F103C8)
  //debugprintln("STM32F103C8");
#error " STM32F103C8 not enough memory for LORA"
#endif
#ifndef LOGGER_NAME
#define LOGGER_NAME "?"
#endif
  {  // Geräte-Kennung: Name, 96-Bit-UID, DevAddr
    char id_line[80];
    snprintf(id_line, sizeof(id_line), "Name: %s UID: %08lX%08lX%08lX DevAddr: %08lX", LOGGER_NAME,
             (unsigned long)HAL_GetUIDw2(), (unsigned long)HAL_GetUIDw1(), (unsigned long)HAL_GetUIDw0(),
#if LORA_OTAA
             (unsigned long)otaa_rd(0));  // DevAddr des letzten Join, 0 = Join steht aus
#else
             (unsigned long)DEVADDR);
#endif
    debugprintln(id_line);
  }

  /*
    debugprint(EEProm_Size);
    debugprint(" ");
    debugprint( _Daten_Satz_Len);
    debugprint(" ");
    debugprintln( _Num_Measured);
  */
  debugflush();
  //----------------------------------------------------------------
  if (nr_to_measure == 0) {  //__HAL_RCC_GET_FLAG(RCC_FLAG_PORRST)
    debugprintln("POR");  // Power On Reset
  }
  else
  {
    debugprintln("RST");
  }
  Pwr_flags();

  __HAL_RCC_CLEAR_RESET_FLAGS();

  print_info();
  debugflush();

  display_time();

  Service = (float)time_on;          // store TimeOn from previous run
  time_on = rtc_stm.getEpoch();
}
//----------------------------------------------------------------


/**
  @brief Funktion Pwr_flags()
  check and reset STM32 WakeUp/Standby- flags
  @param none
  @return none
*/
void Pwr_flags() {
  if ( __HAL_PWR_GET_FLAG(PWR_FLAG_SB) != RESET) { // Wakeup from RTC Standby
    __HAL_PWR_CLEAR_FLAG(PWR_FLAG_SB); // Clear Standby Flag
  }
  __HAL_PWR_CLEAR_FLAG(PWR_FLAG_WU); // Clear WakeUp Flag
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
  debugprintln(ok_sleep); //wakeup from:  0 = Reset, 1 = Shutdown, 2 = Interrupt, 3 = RTC
  print_info();
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

  Alarm_konfigurieren();
  save_data_to_ram();
  print_info();
  debugflush();
  debugend();         // release serial
  blink_LED(2, 100);  // signal

  digitalWrite(Power_Pin, LOW);
  hx711_SCK_Low();    // code order, important!
  delay(5);
  LowPower.shutdown();    // STM32-Standby Mode causes Reset on Alarm or WKUP
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
  if (digitalRead(WakeUp_Pin) == true) {      // switch on?
    blink_LED(1, 500);  // signal we've noticed
    if (stuck_switch == 0) {                  // first time only
      delay(2000);          // wait
      if (digitalRead(WakeUp_Pin) == true) {  // still on?
        // User Service, force send data
        blink_LED(4, 200);                    // signal we've noticed
        Service = 99.0;                       // report User Wakeup to server
        stuck_switch = 1;
        debugprint ("User! ");
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
  uint8_t h_now;
  uint8_t month_now;

  ttim = rtc_stm.getEpoch() + 1;   // at least one second -> +1
  time_on = ttim - time_on;        // time system has been 'on'
  //  debugprintF("t-on: "); debugprintln(time_on); debugflush();
  if (time_on > 255) {             // Power On Reset or invalid date
    time_on = 0;
  }

  if (digitalRead(WakeUp_Pin) == true)  // service mode ongoing
  { // PA0 Switch on/off only, no interrupt; important!
    HAL_PWR_DisableWakeUpPin( PWR_WAKEUP_PIN1 );
  } else {
    stuck_switch = 0;
    // Attach a wakeup from User Service Switch, WakeUp_Pin must be Pin PA0
    LowPower.attachInterruptWakeup(WakeUp_Pin, WakeUp, RISING, SHUTDOWN_MODE);
  }
  Pwr_flags(); // Clear Flags

  if ((stuck_switch) && (stuck_switch  < (USER_INT_TM / INTERVAL_STUCK_SWITCH)))   // service mode
  {
    IntervallMinuten = INTERVAL_STUCK_SWITCH;     // Check switch every xx minutes
  } else {
    IntervallMinuten = WeckIntervall;

    // Lora spezial Intervall konfig
    if (WeckIntervall_aktiv) {  // Manuelle Intervalsteuerung aktiviert
      h_now = rtc_stm.getHours();
      month_now = rtc_stm.getMonth();
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
    if (FernIntervall) IntervallMinuten = FernIntervall;  // Downlink ersetzt Tag/Nacht/Winter, nicht Akkusparmodus
  }
  if (akku_save == 1) {
    IntervallMinuten = AlternativIntervallMinuten;
  }

  if (IntervallMinuten < 5) IntervallMinuten = 5;  // LoRaWan usage restrictions
  tt = ttim + (uint32_t)(IntervallMinuten * 60);   // calculate next wakeup
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
  //rtc_stm.detachInterrupt();
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

  // register 0 is used by the STM32-RTC Lib V 1.1.0
  // STM32 rtc lib with STM32F1xx uses register 6 / 7

  uint16_t tty = (uint16_t) rtc_stm.getYear();

  if ((tty < 22) || (tty > 33)) { // valid year in backup register?
    // restart w/o valid date, handle like POR
    rtc_stm.setYear(22);  // LORA w/o server response
    rtc_stm.setMonth(1);
    rtc_stm.setDay(1);
#if LORA_OTAA
    otaa_clear();  // FCnt unbekannt -> Neu-Join
#endif
  }
  else {

    reg = getBackupRegister(3);
    LoRaSeqCounter = (uint16_t)reg;      // frame counter / TTN Sequence numbber

    reg = getBackupRegister(4);
    WeckIntervallMinuten = (uint8_t) (reg & 0x00ff);
    time_on = (uint32_t) (reg >> 8);   // max 255sec.

    reg = getBackupRegister(5);
    stuck_switch = (uint8_t) (reg & 0x001f);    // lower five bits  0 ... 31
    if (reg & 0x0100) akku_save = 1;            // upper one bit 0 / 1
    FernIntervall = ((reg >> 9) & 0x3f) * 5;    // bit 9-14: Downlink-Intervall / 5 (alte Firmware: 0)

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7
    reg = (uint16_t)getBackupRegister(1);
    LetztesGewicht[0] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(2);
    LetztesGewicht[1] = (float) reg / 100.0;
#else
    reg = (uint16_t)getBackupRegister(6);
    LetztesGewicht[0] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(7);
    LetztesGewicht[1] = (float) reg / 100.0;
#endif
    reg = (uint16_t)getBackupRegister(8);
    LetztesGewicht[2] = (float) reg / 100.0;
    reg = (uint16_t)getBackupRegister(9);
    LetztesGewicht[3] = (float) reg / 100.0;

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
  // STM32 rtc lib with STM32F1xx uses register 6 / 7
  uint32_t reg = 0;

  reg = LoRaSeqCounter;      // frame counter / TTN Sequence number
  setBackupRegister(3, reg);

  reg = time_on << 8;             // max 255 sec.
  reg = reg | WeckIntervallMinuten;  // max. 180 min. (Weckalternativ!)
  setBackupRegister(4, reg);

  reg = 0; if (akku_save)reg = 0x0100;      // set upper bit
  reg = reg | (stuck_switch & 0x001f);      // lower five bits  0 ... 31
  reg = reg | ((uint32_t)(FernIntervall / 5) << 9);  // bit 9-14
  setBackupRegister(5, reg);

#if defined (STM32F1xx)  // STM32 rtc lib with STM32F1xx uses register 6 / 7
  reg = (uint32_t) (LetztesGewicht[0] * 100.0);
  setBackupRegister(1, reg);
  reg = (uint32_t) (LetztesGewicht[1] * 100.0);
  setBackupRegister(2, reg);
#else
  reg = (uint32_t) (LetztesGewicht[0] * 100.0);
  setBackupRegister(6, reg);
  reg = (uint32_t) (LetztesGewicht[1] * 100.0);
  setBackupRegister(7, reg);
#endif
  reg = (uint32_t) (LetztesGewicht[2] * 100.0);
  setBackupRegister(8, reg);
  reg = (uint32_t) (LetztesGewicht[3] * 100.0);
  setBackupRegister(9, reg);

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
  debugprint("  LoRaSeq_Nr:");
  debugprint(LoRaSeqCounter);
  //  debugprint("  nextsend: ");
  //  debugprint(next_send_hour);
  //  debugprint("h  send-cyc: ");
  //  debugprint(send_cycle);
  debugprint(" Interval: ");
  debugprint(WeckIntervallMinuten);
  debugprint("min  DL: ");
  debugprint(FernIntervall);
  debugprint("min  t-on: ");
  debugprint(time_on);
  //  debugprint("s  dly: ");
  //  debugprint(snd_dly);
  debugprint("  sw: ");
  debugprint(stuck_switch);
  //  debugprint("  report:");
  //  debugprintln(report_info);
  debugprint(" ");
  debugprintln(akku_save);
  debugflush();
}

void display_time() {
#if myDEBUG
  char buff[64];
  time_t wakeup_time = rtc_stm.getEpoch();
  strftime (buff, 60, "%d.%m.%Y %H:%M:%S", localtime (&wakeup_time));
  debugprintF("Datum:    "); debugprintln(buff);
#endif
}


/****************************************************************/
// Software-Watchdog und I2C-Bus freitakten (desiue, 2026-11-07)
// Der Hardware-Watchdog (IWDG) passt nicht: Er läuft im Standby weiter und würde den
// Logger alle ~26 s wecken. Stattdessen zählt TIM4 im 1-s-Takt, nur in der Wachphase.
// Dauert eine Phase zu lange (z. B. I2C-Sensor hängt), merkt sich der Logger die Phase
// im Backup-Register und startet neu. Im nächsten Lauf:
//   Phase 1 (Sensoren) hing -> ohne I2C-Sensoren messen und senden,
//   Phase 2 (LoRa) hing oder zweiter Neustart in Folge -> gleich schlafen bis zum nächsten Termin.
/****************************************************************/
#if defined(STM32F1xx)
#define WDT_REG 10      // F1: DR10 frei (1-9 Sketch, 6/7 RTC-Datum)
#else
#define WDT_REG 14      // F4: 10-13 OTAA-Session
#endif
#define WDT_MAGIC 0xA500  // Bit 4-7: Neustarts in Folge, Bit 0-3: Phase

HardwareTimer *wdt_tim = nullptr;
volatile uint16_t wdt_rest = 0;
volatile uint8_t wdt_phase = 0;

void wdt_tick() {
  if (wdt_rest && --wdt_rest == 0) {
    uint8_t n = (wdt_anzahl < 15) ? wdt_anzahl + 1 : 15;
    setBackupRegister(WDT_REG, WDT_MAGIC | (n << 4) | wdt_phase);
    NVIC_SystemReset();
  }
}

void wdt_init() {  // in setup(): Merker vom letzten Lauf lesen und löschen
  enableBackupDomain();
  uint32_t reg = getBackupRegister(WDT_REG) & 0xFFFF;
  setBackupRegister(WDT_REG, 0);
  if ((reg & 0xFF00) == WDT_MAGIC) {
    wdt_vorher = reg & 0x0F;
    wdt_anzahl = (reg >> 4) & 0x0F;
    debugprintF("WDT: Neustart, Phase "); debugprint(wdt_vorher);
    debugprintF(" hing, in Folge: "); debugprintln(wdt_anzahl);
  }
}

void wdt_start(uint8_t phase, uint16_t sekunden) {  // neue Phase mit eigenem Zeitlimit
  noInterrupts();
  wdt_phase = phase;
  wdt_rest = sekunden;
  interrupts();
  if (wdt_tim == nullptr) {
    wdt_tim = new HardwareTimer(TIM4);
    wdt_tim->setOverflow(1, HERTZ_FORMAT);
    wdt_tim->attachInterrupt(wdt_tick);
    wdt_tim->resume();
  }
}

void i2c_bus_frei() {  // hängt ein Slave mit SDA auf low, bis zu 9 Takte auf SCL und STOP
  pinMode(PIN_WIRE_SDA, INPUT_PULLUP);
  pinMode(PIN_WIRE_SCL, INPUT_PULLUP);
  delayMicroseconds(10);
  if (digitalRead(PIN_WIRE_SDA)) return;  // Bus frei
  debugprintlnF("I2C: SDA low, takte frei");
  pinMode(PIN_WIRE_SCL, OUTPUT_OPEN_DRAIN);
  for (uint8_t i = 0; i < 9 && !digitalRead(PIN_WIRE_SDA); i++) {
    digitalWrite(PIN_WIRE_SCL, LOW);  delayMicroseconds(5);
    digitalWrite(PIN_WIRE_SCL, HIGH); delayMicroseconds(5);
  }
  pinMode(PIN_WIRE_SDA, OUTPUT_OPEN_DRAIN);  // STOP: SDA low -> high bei SCL high
  digitalWrite(PIN_WIRE_SDA, LOW);  delayMicroseconds(5);
  digitalWrite(PIN_WIRE_SCL, HIGH); delayMicroseconds(5);
  digitalWrite(PIN_WIRE_SDA, HIGH); delayMicroseconds(5);
  pinMode(PIN_WIRE_SDA, INPUT_PULLUP);
  pinMode(PIN_WIRE_SCL, INPUT_PULLUP);
}
