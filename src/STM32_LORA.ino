// Geändert gegenüber beelogger.de STM32_LORA_260705 (GPL v3 §5a):
//   2026-11-06, desiue (github.com/desiue/beelogger-stm32): Downlink FPort 10 auswerten (onEvent); Aux3-Fehler behoben (LoRaSeqCounter % 10).
//   2026-11-06, desiue: OTAA per #define LORA_OTAA (Join, Session F4: Backup-Register 10-13, F1: letzte Flash-Seite; Link-Check 1x/Tag).
//   2026-11-07, desiue: F1-Session-Seite aus Flash-Ende statt fest bei 63 KB (Programm wurde überschrieben).
//   2026-11-08, desiue: Downlink FPort 11 (Serverquittung: Uhr stellen + Intervall, Gegenstueck server/lora_downlink.php).
//   Vollständige Änderungen: docs/aenderungen_gegen_260705.diff

/****************************************************************/
// Funktion LORA - Daten Senden
/****************************************************************/
//05.05.2020 User Service
//29.12.2020 Lora sequence number, LoRa Prefix
//12.02.2021 Lora sequence number in EE-Prom
//02.01.2022 use external EE-Prom
//22.03.2022 optimierte Payload, minimiert Air-Time
//08.06.2022 save data_base to eeprom
//08.10.2023 frame counter in backup-ram, don't use eeprom
//12.04.2026 extended debug output


/****************************************************************/
// Konfiguration RFM95 / SX1276 LORA
/****************************************************************/
#define SERVER_TM_OUT 8000  // 10000 = 10 sec TX-Timeout


//Folgende Anpassungen an „arduino-lmic\src\lmic\config.h“ notwendig:
//#define LMIC_DEBUG_LEVEL 0
//#define DISABLE_JOIN
//#define DISABLE_PING
//#define DISABLE_BEACONS
//#define DISABLE_MCMD_PING_SET // set ping freq, automatically disabled by DISABLE_PING
//#define DISABLE_MCMD_BCNI_ANS // next beacon start, automatical disabled by DISABLE_BEACON


// LORA PIN-Belegung
#define LMIC_RXTX LMIC_UNUSED_PIN
#define LMIC_RST LMIC_UNUSED_PIN
#define LMIC_DIO0 PA2
#define LMIC_DIO1 PA3
#define LMIC_DIO2 LMIC_UNUSED_PIN
#define LMIC_NSS PA4
#define LMIC_MOSI PA7
#define LMIC_MISO PA6
#define LMIC_SCK PA5


// Pin mapping
const lmic_pinmap lmic_pins = {
  .nss = LMIC_NSS,
  .rxtx = LMIC_RXTX,
  .rst = LMIC_RST,
  .dio = { LMIC_DIO0, LMIC_DIO1, LMIC_DIO2 },
};


// LORA Datenhandling
bool flag_TXCOMPLETE = false;
uint8_t payload[64];

#ifndef LORA_OTAA
#define LORA_OTAA 0  // 0 = ABP (DEVADDR, NWKSKEY, APPSKEY), 1 = OTAA (DEVEUI, APPEUI, APPKEY)
#endif

#if LORA_OTAA
#define JOIN_TM_OUT 60000    // Join + erster Uplink; ohne Erfolg neuer Versuch im nächsten Zyklus
#define OTAA_VERSION 1       // Version des Session-Blocks
#define OTAA_FCNT_MAX 16000  // vor dem Überlauf von LoRaSeqCounter (16384) neu joinen

void os_getArtEui(u1_t* buf) { memcpy_P(buf, APPEUI, 8); }
void os_getDevEui(u1_t* buf) { memcpy_P(buf, DEVEUI, 8); }
void os_getDevKey(u1_t* buf) { memcpy_P(buf, APPKEY, 16); }

// Session-Block (F4: Backup-Register 10-13, F1: Flash, je 32 Bit), Schlüssel werden aus APPKEY neu berechnet:
// 10 DevAddr | 11 NetID, Bit 24-31 RxDelay<<4 | Rx2-DR | 12 AppNonce, Bit 24-31 Version
// 13 DevNonce, Bit 16-20 Tag des letzten Link-Checks, Bit 21-22 Fehlversuche, Bit 24-31 Prüfsumme
uint8_t otaa_sum(uint32_t a, uint32_t b, uint32_t c, uint32_t d) {
  uint8_t s = 0x5A;
  for (uint8_t i = 0; i < 32; i += 8) s += (uint8_t)(a >> i) + (uint8_t)(b >> i) + (uint8_t)(c >> i) + (uint8_t)(d >> i);
  return s;
}

#if defined(STM32F1xx)
// F1: Backup-Register sind 16 Bit und belegt -> Session in der letzten Flash-Seite (1 KB).
// Geschrieben nur bei Änderung (Join, 1 Link-Check/Tag, Löschen): ca. 365 Löschzyklen/Jahr bei 10 000 erlaubten.
// Seite = Ende des Flash laut Chip (F103CB: 127 KB). Liegt sie im Programm (z. B. 64-KB-Chip mit großer
// Config), wird nichts gespeichert: Der Logger joint dann bei jedem Start neu, statt sein Programm zu löschen.
// (Fehler bis 07.11.2026: feste Seite bei 63 KB, Firmware mit BME + Licht ist 64 KB -> Programm überschrieben.)
extern "C" uint32_t _sidata, _sdata, _edata;
uint32_t otaa_flash() {  // Adresse der Session-Seite, 0 = kein Platz
  uint32_t seite = FLASH_BASE + (uint32_t)(*(volatile uint16_t*)FLASHSIZE_BASE) * 1024 - 1024;
  uint32_t ende = (uint32_t)&_sidata + ((uint32_t)&_edata - (uint32_t)&_sdata);  // Programmende im Flash
  return (ende <= seite) ? seite : 0;
}
uint32_t otaa_rd(uint8_t i) {
  uint32_t a = otaa_flash();
  if (a == 0) return 0;
  uint32_t v = ((volatile uint32_t*)a)[i];
  return v == 0xFFFFFFFF ? 0 : v;  // gelöschte Seite = keine Session
}
void otaa_wr(const uint32_t* r) {
  uint32_t a = otaa_flash();
  if (a == 0) {
    debugprintlnF("OTAA: kein Flash frei fuer Session, Join bei jedem Start");
    return;
  }
  if (memcmp(r, (const void*)a, 16) == 0) return;
  FLASH_EraseInitTypeDef e = {};
  e.TypeErase = FLASH_TYPEERASE_PAGES;
  e.PageAddress = a;
  e.NbPages = 1;
  uint32_t err;
  HAL_FLASH_Unlock();
  if (HAL_FLASHEx_Erase(&e, &err) == HAL_OK)
    for (uint8_t i = 0; i < 4; i++) HAL_FLASH_Program(FLASH_TYPEPROGRAM_WORD, a + 4 * i, r[i]);
  HAL_FLASH_Lock();
}
#else
uint32_t otaa_rd(uint8_t i) { return getBackupRegister(10 + i); }
void otaa_wr(const uint32_t* r) {
  enableBackupDomain();
  for (uint8_t i = 0; i < 4; i++) setBackupRegister(10 + i, r[i]);
}
#endif

void otaa_clear() {  // nächster Versand joint neu
  const uint32_t z[4] = { 0, 0, 0, 0 };
  otaa_wr(z);
}

bool otaa_load() {  // gültige Session -> LMIC, sonst false (LMIC joint dann selbst, DevAddr = 0)
  uint32_t r10 = otaa_rd(0), r11 = otaa_rd(1), r12 = otaa_rd(2), r13 = otaa_rd(3);
  if (r10 == 0 || (r12 >> 24) != OTAA_VERSION || (r13 >> 24) != otaa_sum(r10, r11, r12, r13 & 0xFFFF)) return false;
  if (LoRaSeqCounter >= OTAA_FCNT_MAX) return false;
  u1_t an[6];  // AppNonce + NetID, LSB zuerst wie im Join-Accept
  for (uint8_t i = 0; i < 3; i++) {
    an[i] = r12 >> (8 * i);
    an[3 + i] = r11 >> (8 * i);
  }
  LMIC_restoreJoin(r11 & 0xFFFFFF, r10, (u2_t)r13, an);
  return true;
}

void otaa_save_join() {  // bei EV_JOINED: LMIC.frame enthält noch den entschlüsselten Join-Accept
  const u1_t* f = &LMIC.frame[OFF_JA_ARTNONCE];
  uint32_t r10 = LMIC.devaddr;
  uint32_t r11 = (LMIC.netid & 0xFFFFFF) | (uint32_t)(((LMIC.rxDelay & 0x0F) << 4) | (LMIC.dn2Dr & 0x0F)) << 24;
  uint32_t r12 = f[0] | (uint32_t)f[1] << 8 | (uint32_t)f[2] << 16 | (uint32_t)OTAA_VERSION << 24;
  uint32_t dn = (u2_t)(LMIC.devNonce - 1);  // LMIC zählt nach dem Join-Request hoch
  const uint32_t r[4] = { r10, r11, r12, dn | (uint32_t)rtc_stm.getDay() << 16 | (uint32_t)otaa_sum(r10, r11, r12, dn) << 24 };
  otaa_wr(r);
}
#else
// ABP: LMIC enthält den Join-Code, ruft ihn aber nie auf (DevAddr gesetzt)
void os_getArtEui(u1_t*) {}
void os_getDevEui(u1_t*) {}
void os_getDevKey(u1_t*) {}
#endif

//#define eeAddress  0x20 // EE-Prom Adress wear leveling
//#define eeAd_data  0x30 // EE-Prom Adress LoRa sequence number
//uint16_t LoRaSeqCounter = 0;      // TTN Sequence numbber


// Payload definitonen
#define TYPE_EXT_TEMP (((uint16_t)1) << 0)  //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_EXT_RH (((uint16_t)1) << 1)    //1 byte  | Humidity   0-100%
#define TYPE_VCC (((uint16_t)1) << 2)       //2 bytes | VCC 0-65535mV
#define TYPE_SOLAR (((uint16_t)1) << 3)     //2 bytes | VCC 0-65535mV
#define TYPE_LIGHT (((uint16_t)1) << 4)     //2 bytes | Light 0-->65535 Lux
#define TYPE_WEIGHT_1 (((uint16_t)1) << 5)  //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_TEMP (((uint16_t)1) << 6)      //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_RH (((uint16_t)1) << 7)        //1 byte  | Humidity 0-100%

#define TYPE_WEIGHT_2 (((uint16_t)1) << 8)  //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_TEMP_2 (((uint16_t)1) << 9)    //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_RH_2 (((uint16_t)1) << 10)     //1 byte  | Humidity 0-100%

#define TYPE_WEIGHT_3 (((uint16_t)1) << 11)  //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_WEIGHT_4 (((uint16_t)1) << 12)  //3 bytes | 0->0xFFFFFF (0-->16777215g)

#define TYPE_AUX0 (((uint16_t)1) << 13)     //2 bytes | Luftdruck in hPA * 10.0 ( 9900 - 11000)
#define TYPE_SERVICE (((uint16_t)1) << 14)  //1 byte  | Service   An-Zeit
#define TYPE_AUX2 (((uint16_t)1) << 15)     //4 bytes | Sketch ID  4 Byte

//#define TYPE_AUX1          (((uint16_t)1)<<) //2 bytes |
//#define TYPE_TEMP_3        (((uint16_t)1)<<) //2 bytes | Temp -327.68°C -->327.67°C
//#define TYPE_TEMP_4        (((uint16_t)1)<<) //2 bytes | Temp -327.68°C -->327.67°C

/****************************************************************/



/**
  @brief Utility functions
  peripherial_off()
  peripherial_on()

  @param  none, globale Variablen
  @return  none
*/
void peripherial_off() {
  digitalWrite(LMIC_NSS, LOW);
  pinMode(LMIC_MOSI, INPUT);  //  SD MOSI
};

void peripherial_on() {
  digitalWrite(LMIC_NSS, HIGH);
  //pinMode(LMIC_MOSI, OUTPUT);      //  SD MOSI
};


/**
  @brief Funktion setup_LoRaSeq()
   initialisere RFM95 modul
   setup parameter RFM95 Modul

  @param  none,        globale Variablen
  @return  none
*/
void setup_LORA() {

  // LMIC init
  os_init();
  // Reset the MAC state. Session and pending data transfers will be discarded.
  LMIC_reset();
  // Set static session parameters. Instead of dynamically establishing a session
  // by joining the network, precomputed session parameters are be provided.

  // These values are stored in flash and only copied to RAM once.
  // Copy them to a temporary buffer here, LMIC_setSession will
  // copy them into a buffer of its own again.
#if LORA_OTAA
  bool otaa_ok = otaa_load();
#else
  uint8_t appskey[sizeof(APPSKEY)];
  uint8_t nwkskey[sizeof(NWKSKEY)];
  memcpy_P(appskey, APPSKEY, sizeof(APPSKEY));
  memcpy_P(nwkskey, NWKSKEY, sizeof(NWKSKEY));
  LMIC_setSession(PREFIX, DEVADDR, nwkskey, appskey);
#endif

  //#if defined(CFG_eu868)
  // Set up the channels used by the Things Network, which corresponds
  // to the defaults of most gateways. Without this, only three base
  // channels from the LoRaWAN specification are used, which certainly
  // works, so it is good for debugging, but can overload those
  // frequencies, so be sure to configure the full frequency range of
  // your network here (unless your network autoconfigures them).
  // Setting up channels should happen after LMIC_setSession, as that
  // configures the minimal channel set.
  // NA-US channels 0-71 are configured automatically

#if LORA_CHANNELS == 1
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band
  LMIC_setupChannel(1, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band
  LMIC_setupChannel(2, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band

#elif LORA_CHANNELS == 3
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band
  LMIC_setupChannel(1, 868300000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band
  LMIC_setupChannel(2, 868500000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);  // g-band

#else
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(1, 868300000, DR_RANGE_MAP(DR_SF12, DR_SF7B), BAND_CENTI);  // g-band
  LMIC_setupChannel(2, 868500000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(3, 867100000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(4, 867300000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(5, 867500000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(6, 867700000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
  LMIC_setupChannel(7, 867900000, DR_RANGE_MAP(DR_SF12, DR_SF7), BAND_CENTI);   // g-band
                                                                                //  LMIC_setupChannel(8, 868800000, DR_RANGE_MAP(DR_FSK,  DR_FSK),  BAND_MILLI);      // g2-band
#endif

  // TTN defines an additional channel at 869.525Mhz using SF9 for class B
  // devices' ping slots. LMIC does not have an easy way to define set this
  // frequency and support for class B is spotty and untested, so this
  // frequency is not configured here.

  // Disable link check validation
  LMIC_setLinkCheckMode(0);

  // ADR aus: LMIC_reset() schaltet es ein, die FW beantwortet aber keine LinkADRReq
  LMIC_setAdrMode(0);

  LMIC_setClockError(MAX_CLOCK_ERROR * 1 / 100);

  // TTN uses SF9 for its RX2 window.
  LMIC.dn2Dr = DR_SF9;
#if LORA_OTAA
  if (otaa_ok) {  // Rx-Werte aus dem Join-Accept
    uint8_t dl = otaa_rd(1) >> 24;
    LMIC.dn2Dr = dl & 0x0F;
    LMIC.rxDelay = dl >> 4;
  }
#endif

  // Set data rate and transmit power for uplink (note: txpow seems to be ignored by the library)
  LMIC_setDrTxpow(DR_SF7, 14);  //Low-Power
  //  LMIC_setDrTxpow(DR_SF8, 14);   // Med-Power
  //  LMIC_setDrTxpow(DR_SF9, 14); // High-Power
}


#if 0  // sequence number in backup-ram
/**
  @brief Funktion init_LoRaSeq()
   hole LORA sequence Counter aus ATmega Flash

  @param  none,        globale Variablen
  @return  none
*/
void  init_LoRaSeq() {            // init Lora sequence number
  uint8_t data_base;      // EE-Prom data adress
  char c_data[8];

  AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);
  my_EEPROM.ReadMem(eeAddress, c_data, 1); // read data base address
  data_base = (uint8_t) c_data[0];
  if (data_base > 16) {              // Check needed for very first start
    data_base = 0;
    c_data[0] = data_base;
    my_EEPROM.WriteMem((eeAddress), c_data, 1);
  }
  my_EEPROM.ReadMem((eeAd_data + (data_base * 2)), c_data , 8);
  my_EEPROM.end();
  int seq;
  sscanf(c_data, "%d", &seq);
  LoRaSeqCounter = (uint16_t) seq;
};
#endif

/**
  @brief Funktion incLoRaSeq()
   inkrement LORA sequence Counter in external EE-Prom
   verwalte wear leveling für EE-Prom Schreiboperationen

  @param  none,        globale Variablen
  @return  none
*/
void inc_LoRaSeq() {  // increment Lora sequence number
#if 0                 // sequence number in backup-ram
  uint8_t data_cnt = 0;      // EE-Prom wear Counter
  uint8_t data_base = 0;     // EE-Prom data adress
  char c_data[8];

  AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);

  my_EEPROM.ReadMem(eeAddress, c_data, 1); // read data base address
  data_base = (uint8_t) c_data[0];

  LoRaSeqCounter += 1;
  if (LoRaSeqCounter > 16382) LoRaSeqCounter = 0;
  if ((LoRaSeqCounter % 128) == 0) { // check wearlevel data every 128 writecycle
    my_EEPROM.ReadMem((eeAddress + 1), c_data, 1);  // read write cell counter
    data_cnt = (uint8_t) c_data[0];
    data_cnt += 1;           // inc write counter
    if ( data_cnt > 0xEE) {  // if a cell has been written 128 * 238 (0xEE) times (11300)
      data_cnt = 0;          // reset write counter
      data_base += 1;        // use next element in eeprom
    }
    if (data_base > 16) data_base = 0;       // 16 elements only
    c_data[0] = data_base;
    my_EEPROM.WriteMem((eeAddress), c_data, 1);
    c_data[0] = data_cnt;
    my_EEPROM.WriteMem((eeAddress), c_data, 1);
  }

  sprintf(c_data, "%d", LoRaSeqCounter);
  my_EEPROM.WriteMem(eeAd_data + (data_base * 2), c_data, 8);
  debugprintF(" EE-Prom - base:");   // Base
  debugprint(data_base);
  debugprintF(" - wrt:");   //writes
  debugprintln(data_cnt);
  debugflush();
#endif

  // sequence number in backup-ram
  LoRaSeqCounter += 1;
  if (LoRaSeqCounter > 16384) LoRaSeqCounter = 0;
}


/**
  @brief Funktion Daten_Senden()
  call setup_LORA()
  prepare Payload
  Übergebe Payload an Lib
  Starte LORA os_runlopp. bis Event versendet kommt oder timeout

  @param  none,        globale Variablen
  @return  none
*/
void Daten_Senden() {

  debugprintlnF("\nLoRa:");
  debugflush();
  setup_LORA();  // Setup RFM Modul
  debugprintlnF(" setup done!");
  debugflush();
  byte cnt = prep_payload();  // prepare Payload

  if (cnt < sizeof(payload)) {
    // Prepare upstream data transmission at the next possible time.
#if myDEBUG
    debugprintF("payload length: ");
    debugprintln(cnt);
    debugprintF("payload: ");
    for (uint8_t i = 0; i < cnt; i++) {
      MONITOR_RX_TX.print(payload[i], HEX);
      debugprint(" ");
    }
    debugprintln(" ");
    debugflush();
    debugprintF("LoRa-frame counter: ");
    debugprintln(LoRaSeqCounter);
    debugflush();
#endif

    LMIC.seqnoUp = LoRaSeqCounter;
#if LORA_OTAA
    // 1 Link-Check pro Tag; 3 ohne Antwort -> Neu-Join
    uint8_t heute = rtc_stm.getDay();
    uint32_t r13 = otaa_rd(3);
    bool lchk = LMIC.devaddr != 0 && ((r13 >> 16) & 0x1F) != heute;
    if (lchk) LMIC.lchkReq = 1;
    uint32_t tm_out = LMIC.devaddr ? SERVER_TM_OUT : JOIN_TM_OUT;
    if (LMIC.devaddr == 0) debugprintlnF("OTAA: Join");
#else
    uint32_t tm_out = SERVER_TM_OUT;
#endif
    LMIC_setTxData2(1, payload, cnt, 0);

    //Run LMIC loop until he as finish or timeout
    long send_loop = millis();
    while ((flag_TXCOMPLETE == false) && ((millis() - send_loop) < tm_out)) {
      os_runloop_once();
    }
    if (flag_TXCOMPLETE == false) {
      debugprintlnF(".tout");
    }
#if LORA_OTAA
    if (lchk) {
      uint8_t fail = (LMIC.lchkReq == 3) ? 0 : ((r13 >> 21) & 3) + 1;
      debugprintF("Link-Check Fehlversuche: ");
      debugprintln(fail);
      if (fail >= 3) otaa_clear();
      else {
        const uint32_t r[4] = { otaa_rd(0), otaa_rd(1), otaa_rd(2), (r13 & 0xFF00FFFF) | (uint32_t)heute << 16 | (uint32_t)fail << 21 };
        otaa_wr(r);
      }
    }
#endif
  } else {
    debugprintlnF("err");
  }

  flag_TXCOMPLETE = false;
  LMIC_reset();
  LMIC_shutdown();
  SPI.end();
}


/**
  @brief Funktion prep_payload()
  erstellt konfigurationsabhängigen Datensatz zum versenden

  @param  none,        globale Variablen
  @return  none
*/
//----------------------------------------------------------------
// Prepare Payload
//----------------------------------------------------------------
byte prep_payload() {

  byte cnt_pld = 0;
  int16_t Value_send;
  uint16_t PayloadHeader = 0;

  debugprintlnF("Data:");
  debugflush();

  float AussenTemp = SensorTemp[Aussenwerte];
  if (AussenTemp == No_Val) AussenTemp = DS_Temp;
  if (AussenTemp != No_Val) {
    debugprintF("temperature: ");
    Value_send = (int16_t)(AussenTemp * 100.0);  // in 100stel grad
    //payload[cnt_pld] = TYPE_EXT_TEMP;
    //cnt_pld++;
    PayloadHeader |= TYPE_EXT_TEMP;
    payload[cnt_pld] = highByte(Value_send);
    debugprint(payload[cnt_pld]);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }

  if (SensorFeuchte[Aussenwerte] != No_Value) {
    debugprintF("humditiy: ");
    Value_send = (int16_t)SensorFeuchte[Aussenwerte];  // 0 - 100
    //payload[cnt_pld] = TYPE_EXT_RH;
    //cnt_pld++;
    PayloadHeader |= TYPE_EXT_RH;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }

  Value_send = (int16_t)(Batteriespannung * 1000.0);  // in mV
  //payload[cnt_pld] = TYPE_VCC;
  //cnt_pld++;
  debugprintF("batt.: ");
  PayloadHeader |= TYPE_VCC;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;

  Value_send    = (int16_t)(Solarspannung * 1000.0);    // in mV
  //payload[cnt_pld] = TYPE_SOLAR;
  //cnt_pld++;
  debugprintF("solar: ");
  PayloadHeader |= TYPE_SOLAR;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;

  if (Licht != No_Value) {
    if (Licht > 65500.0) {
      Licht = 65500.0;  // unsigned integer max 65535
    }
    debugprintF("light: ");
    Value_send = (uint16_t)(Licht);  // 0 ... 65500
    //payload[cnt_pld] = TYPE_LIGHT;
    //cnt_pld++;
    PayloadHeader |= TYPE_LIGHT;
    payload[cnt_pld] = highByte(Value_send);
    debugprint(payload[cnt_pld]);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }

  Value_send = (int16_t)(Gewicht[0] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_1;
  //cnt_pld++;
  debugprintF("weight 1: ");
  PayloadHeader |= TYPE_WEIGHT_1;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;

#if (Anzahl_Sensoren_Gewicht < 3)  // Beutensensor nur wenn bis zu zwei Waagen
  if (SensorTemp[Beute1] != No_Val) {
    Value_send = (int16_t)(SensorTemp[Beute1] * 100.0);  // in 100stel grad
    //payload[cnt_pld] = TYPE_TEMP;
    //cnt_pld++;
    debugprintF("temperature 1: ");
    PayloadHeader |= TYPE_TEMP;
    payload[cnt_pld] = highByte(Value_send);
    debugprint(payload[cnt_pld]);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }
  if (SensorFeuchte[Beute1] != No_Value) {
    Value_send = (int16_t)SensorFeuchte[Beute1];  // 0 - 100
    //payload[cnt_pld] = TYPE_RH;
    //cnt_pld++;
    debugprintF("humidity 1: ");
    PayloadHeader |= TYPE_RH;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }
#endif

#if (Anzahl_Sensoren_Gewicht > 1)                            // zweite Waage
  Value_send = (int16_t)(Gewicht[1] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_2;
  //cnt_pld++;
  debugprintF("weight 2: ");
  PayloadHeader |= TYPE_WEIGHT_2;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;

#if (Anzahl_Sensoren_Gewicht < 3)  // Beutensensor nur wenn bis zu zwei Waagen
  if (SensorTemp[Beute2] != No_Val) {
    debugprintF("temperature 2: ");
    Value_send = (int16_t)(SensorTemp[Beute2] * 100.0);  // in 100stel grad
    //payload[cnt_pld] = TYPE_TEMP_2;
    //cnt_pld++;
    PayloadHeader |= TYPE_TEMP_2;
    payload[cnt_pld] = highByte(Value_send);
    debugprint(payload[cnt_pld]);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }
  if (SensorFeuchte[Beute2] != No_Value) {
    debugprintF("humidity 2: ");
    Value_send = (int16_t)SensorFeuchte[Beute2];  // 0 - 100
    //payload[cnt_pld] = TYPE_RH_2;
    //cnt_pld++;
    PayloadHeader |= TYPE_RH_2;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }
#endif
#endif

#if (Anzahl_Sensoren_Gewicht > 2)                            // dritte Waage ohne Sensoren
  debugprintF("weight 3: ");
  Value_send = (int16_t)(Gewicht[2] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_3;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_3;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;
#endif

#if (Anzahl_Sensoren_Gewicht == 4)                           // vierte Waage ohne Sensoren
  debugprintF("weight 4: ");
  Value_send = (int16_t)(Gewicht[3] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_4;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_4;
  payload[cnt_pld] = highByte(Value_send);
  debugprint(payload[cnt_pld]);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  debugprintln(payload[cnt_pld]);
  cnt_pld++;
#endif


  if (Aux[0] != No_Value) {                                 // Wert  Luftdruck vorhanden
    debugprintF("pressure: ");
    Value_send = (uint16_t)(Aux[0] * 10.0);                 // hPa*10, 9000 - 11000
    //payload[cnt_pld] = TYPE_AUX0;
    //cnt_pld++;
    PayloadHeader |= TYPE_AUX0;
    payload[cnt_pld] = highByte(Value_send);
    debugprint(payload[cnt_pld]);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    debugprintln(payload[cnt_pld]);
    cnt_pld++;
  }

  int8_t svc = (int8_t)Service;
  if (Service > 255.0) svc = 0xFF;       // max value in payload
  //payload[cnt_pld] = TYPE_SERVICE;
  //cnt_pld++;
  PayloadHeader |= TYPE_SERVICE;
  payload[cnt_pld] = svc & 0xFF ;
  cnt_pld++;
  debugprintF("info:");  debugprintln(svc);

  if (LoRaSeqCounter % 10 == 0 ) { // ID not in every transmission (seqnoUp ist hier noch nicht gesetzt)
    //payload[cnt_pld] = TYPE_AUX2;
    //cnt_pld++;
    PayloadHeader |= TYPE_AUX2;
    payload[cnt_pld] = (l_ID >> 24) & 0xFF;
    cnt_pld++;
    payload[cnt_pld] = (l_ID >> 16) & 0xFF;
    cnt_pld++;
    payload[cnt_pld] = (l_ID >> 8) & 0xFF;
    cnt_pld++;
    payload[cnt_pld] = lowByte(l_ID);
    cnt_pld++;
  }

  /*payload[cnt_pld] = ((uint16_t)0x00FF0000 & PayloadHeader) >> 16;
    cnt_pld++;*/
  payload[cnt_pld] = ((uint16_t)0xFF00 & PayloadHeader) >> 8;
  cnt_pld++;
  payload[cnt_pld] = ((uint16_t)0x00FF & PayloadHeader);
  cnt_pld++;

  debugprintF("HDR: ");
  debugprintln(PayloadHeader);

  payload[cnt_pld] = 0;
  debugprintln(" ");
  debugflush();

  return (cnt_pld);
}


/**
  @brief Funktion onEvent()
  bearbeite Events aus der LORA LMIC-Lib

  @param  none,        globale Variablen
  @return  none
*/
void onEvent(ev_t ev) {

  debugprint(os_getTime());
  debugprint(": ");

  switch (ev) {
    case EV_TXCOMPLETE:

      debugprintlnF("TX done!");

      /*
        if (LMIC.txrxFlags & TXRX_ACK)

        debugprintlnF("Received ack");

           if (LMIC.dataLen) {

              debugprintlnF("Received ");
              debugprintln(LMIC.dataLen);
              debugprintlnF(" bytes of payload");

            }
      */
      // Downlink FPort 10, 1 Byte: Sendeintervall in Minuten (5..180, 0 = Firmware-Vorgabe)
      if ((LMIC.txrxFlags & TXRX_PORT) && LMIC.dataLen == 1 && LMIC.frame[LMIC.dataBeg - 1] == 10) {
        uint8_t m = LMIC.frame[LMIC.dataBeg];
        debugprintF("DL Port 10: ");
        debugprintln(m);
        if (m == 0 || (m >= 5 && m <= 180)) FernIntervall = m - m % 5;  // Weckzeit rastet auf 5 min
      }
      // Downlink FPort 11, 7 Byte: Serverquittung (server/lora_downlink.php)
      // Byte 0 FCnt-Bezug, 1-4 Empfangszeit des Bezugs-Uplinks (wie WLAN-Quittung), 5 Abstand [min], 6 Intervall (5..60, 0 = unveraendert)
      if ((LMIC.txrxFlags & TXRX_PORT) && LMIC.dataLen == 7 && LMIC.frame[LMIC.dataBeg - 1] == 11) {
        const uint8_t* d = &LMIC.frame[LMIC.dataBeg];
        uint32_t t = ((uint32_t)d[1] << 24) | ((uint32_t)d[2] << 16) | ((uint32_t)d[3] << 8) | d[4];
        debugprintF("DL Port 11: ");
        debugprint(d[0]); debugprintF(" "); debugprint(t); debugprintF(" "); debugprint(d[5]); debugprintF(" "); debugprintln(d[6]);
        uint8_t fc = (uint8_t)LoRaSeqCounter;               // FCnt dieses Uplinks
        if (t && d[0] != fc) t = (d[0] == (uint8_t)(fc - 1) && d[5]) ? t + 60UL * d[5] : 0;  // Downlink zum vorigen Uplink
        if (t) {
          uint32_t alt = rtc_stm.getEpoch();
          rtc_stm.setEpoch(t + 1);                          // ca. 1 s seit Uplink-Ende (RX-Fenster)
          time_on += rtc_stm.getEpoch() - alt;              // Wachzeit bleibt richtig
          debugprintlnF("Uhr gestellt");
        }
        if (d[6] >= 5 && d[6] <= 60) FernIntervall = d[6] - d[6] % 5;
      }
      inc_LoRaSeq();
      flag_TXCOMPLETE = true;
      break;
#if LORA_OTAA
    case EV_JOINING:
      debugprintlnF("Joining");
      break;
    case EV_JOINED:
      otaa_save_join();
      LoRaSeqCounter = 0;  // neue Session, LMIC sendet den wartenden Uplink mit FCnt 0
      debugprintF("Joined DevAddr: ");
      debugprintln(LMIC.devaddr);
      break;
#endif
    case EV_RXCOMPLETE:
      // data received in ping slot
      debugprintlnF("RX !");

      break;
    default:

      debugprintlnF("unknown ?");

      break;
  }
  debugflush();
}
