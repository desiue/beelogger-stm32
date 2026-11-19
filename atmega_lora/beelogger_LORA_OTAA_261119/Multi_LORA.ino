//----------------------------------------------------------------
// Funktion LORA - Daten Senden
//----------------------------------------------------------------
//05.05.2020 User Service
//29.12.2020 Lora sequence number, LoRa Prefix
//12.02.2021 Lora sequence number in EE-Prom
//27.02.22 optimierte Payload, minimiert Ait-Time
//25.03.22 more debug info
//08.11.2026 desiue: OTAA per #define LORA_OTAA in Config_LORA.h

#include <EEPROM.h>

//----------------------------------------------------------------
// Konfiguration RFM95 / SX1276 LORA
//----------------------------------------------------------------
#define SERVER_TM_OUT 8000 // 10000 = 10 sec TX-Timeout


//Folgende Anpassungen an „arduino-lmic\src\lmic\config.h“ notwendig:
//#define LMIC_DEBUG_LEVEL 0
//#define DISABLE_JOIN      // nicht in src/lmic_otaa (Join für OTAA, ABP joint nie)
//#define DISABLE_PING
//#define DISABLE_BEACONS
//#define DISABLE_MCMD_PING_SET // set ping freq, automatically disabled by DISABLE_PING
//#define DISABLE_MCMD_BCNI_ANS // next beacon start, automatical disabled by DISABLE_BEACON


// LORA PIN-Belegung
#define LMIC_RXTX   LMIC_UNUSED_PIN
#define LMIC_RST    LMIC_UNUSED_PIN
#define LMIC_DIO0   8
#define LMIC_DIO1   9
#define LMIC_DIO2   LMIC_UNUSED_PIN
#define LMIC_NSS    10
#define LMIC_MOSI   11
#define LMIC_MISO   12
#define LMIC_SCK    13


// Pin mapping
const lmic_pinmap lmic_pins = {
  .nss = LMIC_NSS,
  .rxtx = LMIC_RXTX,
  .rst = LMIC_RST,
  .dio = {LMIC_DIO0, LMIC_DIO1, LMIC_DIO2},
};


// LORA Datenhandling
boolean flag_TXCOMPLETE = false;
uint8_t payload[64];

// Payload definitonen
#define TYPE_EXT_TEMP      (((uint16_t)1)<<0) //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_EXT_RH        (((uint16_t)1)<<1) //1 byte  | Humidity   0-100%
#define TYPE_VCC           (((uint16_t)1)<<2) //2 bytes | VCC 0-65535mV
#define TYPE_SOLAR         (((uint16_t)1)<<3) //2 bytes | VCC 0-65535mV
#define TYPE_LIGHT         (((uint16_t)1)<<4) //2 bytes | Light 0-->65535 Lux
#define TYPE_WEIGHT_1      (((uint16_t)1)<<5) //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_TEMP          (((uint16_t)1)<<6) //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_RH            (((uint16_t)1)<<7) //1 byte  | Humidity 0-100%
// 15 byte + 2byte header
#define TYPE_WEIGHT_2      (((uint16_t)1)<<8) //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_TEMP_2        (((uint16_t)1)<<9) //2 bytes | Temp -327.68°C -->327.67°C
#define TYPE_RH_2          (((uint16_t)1)<<10) //1 byte  | Humidity 0-100%
// 21 byte + 2byte header
#define TYPE_WEIGHT_3      (((uint16_t)1)<<11) //3 bytes | 0->0xFFFFFF (0-->16777215g)
#define TYPE_WEIGHT_4      (((uint16_t)1)<<12) //3 bytes | 0->0xFFFFFF (0-->16777215g)
// 27 byte + 2byte header
#define TYPE_AUX1          (((uint16_t)1)<<13) //2 bytes | Luftdruck in hPA * 10.0 ( 9900 - 11000)
#define TYPE_SERVICE       (((uint16_t)1)<<14) //1 byte  | Service   An-Zeit
#define TYPE_AUX3          (((uint16_t)1)<<15) //4 bytes | Sketch ID  4 Byte
// 34 byte + 2byte header

//#define TYPE_AUX2          (((uint16_t)1)<<) //2 bytes | 
//#define TYPE_TEMP_3        (((uint16_t)1)<<) //2 bytes | Temp -327.68°C -->327.67°C
//#define TYPE_TEMP_4        (((uint16_t)1)<<) //2 bytes | Temp -327.68°C -->327.67°C

//----------------------------------------------------------------

void  peripherial_off() {
  digitalWrite (LMIC_NSS, LOW);
  pinMode(LMIC_MOSI, INPUT);          //  SD MOSI
};

void  peripherial_on() {
  digitalWrite (LMIC_NSS, HIGH);
  //pinMode(LMIC_MOSI, OUTPUT);      //  SD MOSI
};

#define eeAddress  0x20 // EE-Prom Adress wear leveling
#define eeAd_data  0x30 // EE-Prom Adress LoRa sequence number
uint8_t data_base;      // EE-Prom data adress
uint8_t data_cnt;       // EE-Prom wear Counter
uint16_t LoRaSeqCounter = 0;      // TTN Sequence numbber

#if LORA_OTAA
// OTAA: Join beim ersten Senden. Session im EE-Prom ab 0x60, die Schlüssel werden aus APPKEY neu berechnet.
// Link-Check alle OTAA_LCHK Uplinks, nach 3 Link-Checks ohne Antwort neuer Join.
#define JOIN_TM_OUT   30000  // Join + erster Uplink; ohne Erfolg neuer Versuch im nächsten Zyklus
#define OTAA_LCHK     144    // Link-Check alle 144 Uplinks (bei 10 min etwa einmal am Tag)
#define OTAA_FCNT_MAX 65000  // vor dem Überlauf von LoRaSeqCounter neu joinen
#define eeAd_otaa     0x60   // EE-Prom Adresse OTAA-Session (15 Byte)

struct otaa_t {
  uint32_t devaddr;
  uint32_t netid;        // Bit 0-23 NetID, Bit 24-31 RxDelay<<4 | Rx2-DR
  uint8_t  appnonce[3];
  uint16_t devnonce;
  uint8_t  fail;         // Link-Checks ohne Antwort
  uint8_t  sum;          // Prüfsumme über Session (bis fail), DEVEUI und APPKEY
} otaa_s;              // global: Arduino setzt Funktionsprototypen vor die Struct-Definition

void os_getArtEui(u1_t* buf) { memcpy_P(buf, APPEUI, 8); }
void os_getDevEui(u1_t* buf) { memcpy_P(buf, DEVEUI, 8); }
void os_getDevKey(u1_t* buf) { memcpy_P(buf, APPKEY, 16); }

uint8_t otaa_sum() {  // neue Schlüssel im Sketch -> Prüfsumme falsch -> neuer Join
  uint8_t x = 0x5A;
  const uint8_t *p = (const uint8_t *)&otaa_s;
  for (uint8_t i = 0; i < offsetof(otaa_t, fail); i++) x = (x << 1 | x >> 7) + p[i];
  for (uint8_t i = 0; i < 16; i++) x = (x << 1 | x >> 7) + pgm_read_byte(APPKEY + i) + (i < 8 ? pgm_read_byte(DEVEUI + i) : 0);
  return x;
}

bool otaa_load() {  // gültige Session -> LMIC, sonst false (LMIC joint beim Senden, DevAddr = 0)
  otaa_t &s = otaa_s;
  EEPROM.get(eeAd_otaa, s);
  if (s.devaddr == 0 || s.sum != otaa_sum() || LoRaSeqCounter >= OTAA_FCNT_MAX) return false;
  u1_t an[6];  // AppNonce + NetID, LSB zuerst wie im Join-Accept
  memcpy(an, s.appnonce, 3);
  memcpy(an + 3, &s.netid, 3);
  LMIC_restoreJoin(s.netid & 0xFFFFFF, s.devaddr, s.devnonce, an);
  return true;
}

void otaa_save_join() {  // bei EV_JOINED: LMIC.frame enthält noch den entschlüsselten Join-Accept
  otaa_t &s = otaa_s;
  s.devaddr = LMIC.devaddr;
  s.netid = (LMIC.netid & 0xFFFFFF) | (uint32_t)(((LMIC.rxDelay & 0x0F) << 4) | (LMIC.dn2Dr & 0x0F)) << 24;
  memcpy(s.appnonce, &LMIC.frame[OFF_JA_ARTNONCE], 3);
  s.devnonce = LMIC.devNonce - 1;  // LMIC zählt nach dem Join-Request hoch
  s.fail = 0;
  s.sum = otaa_sum();
  EEPROM.put(eeAd_otaa, s);
}
#else
// ABP: LMIC enthält den Join-Code, ruft ihn aber nie auf (DevAddr gesetzt)
void os_getArtEui(u1_t*) {}
void os_getDevEui(u1_t*) {}
void os_getDevKey(u1_t*) {}
#endif


//----------------------------------------------------------------
// Setup SX1276 - LORA
//----------------------------------------------------------------
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
  LMIC_setSession (PREFIX, DEVADDR, nwkskey, appskey);
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
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(1, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(2, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band

#elif LORA_CHANNELS == 3
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(1, 868300000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(2, 868500000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band

#else
  LMIC_setupChannel(0, 868100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(1, 868300000, DR_RANGE_MAP(DR_SF12, DR_SF7B), BAND_CENTI);      // g-band
  LMIC_setupChannel(2, 868500000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(3, 867100000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(4, 867300000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(5, 867500000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(6, 867700000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  LMIC_setupChannel(7, 867900000, DR_RANGE_MAP(DR_SF12, DR_SF7),  BAND_CENTI);      // g-band
  //  LMIC_setupChannel(8, 868800000, DR_RANGE_MAP(DR_FSK,  DR_FSK),  BAND_MILLI);      // g2-band
#endif

  // TTN defines an additional channel at 869.525Mhz using SF9 for class B
  // devices' ping slots. LMIC does not have an easy way to define set this
  // frequency and support for class B is spotty and untested, so this
  // frequency is not configured here.

  // Disable link check validation
  LMIC_setLinkCheckMode(0);

  // ADR aus: LMIC_reset() schaltet ADR ein, der Sketch beantwortet aber keine LinkADRReq
  LMIC_setAdrMode(0);

  LMIC_setClockError(MAX_CLOCK_ERROR * 1 / 100);

  // TTN uses SF9 for its RX2 window.
  LMIC.dn2Dr = DR_SF9;
#if LORA_OTAA
  if (otaa_ok) {  // Rx-Werte aus dem Join-Accept
    LMIC.dn2Dr = (otaa_s.netid >> 24) & 0x0F;
    LMIC.rxDelay = otaa_s.netid >> 28;
  }
#endif

  // Set data rate and transmit power for uplink (note: txpow seems to be ignored by the library)
  LMIC_setDrTxpow(DR_SF7, 14);  //Low-Power
  //  LMIC_setDrTxpow(DR_SF8, 14);   // Med-Power
  //  LMIC_setDrTxpow(DR_SF9, 14); // High-Power
}
//----------------------------------------------------------------

void  init_LoRaSeq() {            // init Lora sequence number
  EEPROM.get(eeAddress, data_base);
  if (data_base > 16) data_base = 0;       // Check needed for very first start
  EEPROM.get((eeAddress + 1), data_cnt);
  EEPROM.get((eeAd_data + (data_base * 2)), LoRaSeqCounter); // read,  *2 =  two bytes
  inc_LoRaSeq();  // call to check limits
};

void  inc_LoRaSeq() {        // increment Lora sequence number
  LoRaSeqCounter += 1;
  if (LoRaSeqCounter >= 65535) LoRaSeqCounter = 0;
  if ((LoRaSeqCounter % 128) == 0) { // check wearlevel data every 128 writecycle
    data_cnt += 1;           // inc write counter
    if ( data_cnt > 0xEE) {  // if a cell has been written 128 * 238 (0xEE) times (11300)
      data_cnt = 0;          // reset write counter
      data_base += 1;        // use next element in eeprom
    }
    if (data_base > 16) data_base = 0;       // 16 elements only
    EEPROM.put((eeAddress), data_base);
    EEPROM.put((eeAddress + 1), data_cnt);
  }
  EEPROM.put(eeAd_data + (data_base * 2), LoRaSeqCounter); // *2 =  two bytes
  debugprintF("Cnt:"); //LoRa Counter: ");
  debugprint(LoRaSeqCounter);
  debugprintF(" bs:");   // Base
  debugprint(data_base);
  debugprintF(" wr:");   //writes
  debugprintln(data_cnt);
  debugflush();
}


//----------------------------------------------------------------
// Daten_Senden SX1276 - LORA
//----------------------------------------------------------------
void Daten_Senden() {

  debugprintlnF("\nLoRa:");
  debugflush();
  setup_LORA();  // Setup RFM Modul
  debugprintlnF("!");
  debugflush();

  byte cnt = prep_payload();  // prepare Payload

  if (cnt < sizeof(payload)) {
    // Prepare upstream data transmission at the next possible time.

#if myDEBUG
    debugprintF("len:");
    debugprintln(cnt);
    for (uint8_t i = 0; i < cnt; i++) {
      Serial.print(payload[i], HEX);
      Serial.print(" ");
    }
    debugprintln(" ");
    debugflush();
    debugprintF("LoRa.seqnoUp: ");
    debugprintln(LoRaSeqCounter);
    debugflush();
#endif

    LMIC.seqnoUp = LoRaSeqCounter;
#if LORA_OTAA
    bool lchk = LMIC.devaddr != 0 && (LoRaSeqCounter % OTAA_LCHK) == 0;
    if (lchk) LMIC.lchkReq = 1;  // Link-Check mit diesem Uplink
    uint16_t tm_out = LMIC.devaddr ? SERVER_TM_OUT : JOIN_TM_OUT;
    if (LMIC.devaddr == 0) debugprintlnF("Join");
#else
    uint16_t tm_out = SERVER_TM_OUT;
#endif
    LMIC_setTxData2(1, payload, cnt, 0);

    //Run LMIC loop until he as finish or timeout
    long  send_loop = millis();
    while ((flag_TXCOMPLETE == false) && ((millis() - send_loop) < tm_out))
    {
      os_runloop_once();
    }
    if (flag_TXCOMPLETE == false) {
      debugprintlnF(".tout");
    }
#if LORA_OTAA
    if (lchk) {
      otaa_t &s = otaa_s;
      EEPROM.get(eeAd_otaa, s);
      s.fail = (LMIC.lchkReq == 3) ? 0 : s.fail + 1;
      debugprintF("LCHK fail:");
      debugprintln(s.fail);
      if (s.fail >= 3) s.devaddr = 0;  // nächster Versand joint neu
      EEPROM.put(eeAd_otaa, s);
    }
#endif
  }
  else {
    debugprintlnF("err");
  }

  flag_TXCOMPLETE = false;
  LMIC_reset();
  LMIC_shutdown();
  SPI.end();
}
//----------------------------------------------------------------


//----------------------------------------------------------------
// Prepare Payload
//----------------------------------------------------------------
byte prep_payload() {

  byte cnt_pld = 0;
  int16_t Value_send;
  uint16_t PayloadHeader = 0;

  debugprintlnF("Data");
  debugflush();

  float AussenTemp = SensorTemp[Aussenwerte];
  if (AussenTemp == No_Val) AussenTemp = DS_Temp;
  if (AussenTemp != No_Val) {
    Value_send = (int16_t)(AussenTemp * 100.0);  // in 100stel grad
    //payload[cnt_pld] = TYPE_EXT_TEMP;
    //cnt_pld++;
    PayloadHeader |= TYPE_EXT_TEMP;
    payload[cnt_pld] = highByte(Value_send);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("T-O:");  debugprintln(Value_send);
  }

  if (SensorFeuchte[Aussenwerte] != No_Value) {
    Value_send = (int16_t)SensorFeuchte[Aussenwerte];       // 0 - 100
    //payload[cnt_pld] = TYPE_EXT_RH;
    //cnt_pld++;
    PayloadHeader |= TYPE_EXT_RH;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("F-O:"); debugprintln(Value_send);
  }

  Value_send = (int16_t)(Batteriespannung * 1000.0); // in mV
  //payload[cnt_pld] = TYPE_VCC;
  //cnt_pld++;
  PayloadHeader |= TYPE_VCC;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("V-B:");   debugprintln(Value_send);

  Value_send    = (int16_t)(Solarspannung * 1000.0);    // in mV
  //payload[cnt_pld] = TYPE_SOLAR;
  //cnt_pld++;
  PayloadHeader |= TYPE_SOLAR;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("V-S:");    debugprintln(Value_send);

  if (Licht != No_Value) {
    if (Licht > 65500.0) {
      Licht = 65500.0; // unsigned integer max 65535
    }
    Value_send  = (uint16_t)(Licht);                            // 0 ... 65500
    //payload[cnt_pld] = TYPE_LIGHT;
    //cnt_pld++;
    PayloadHeader |= TYPE_LIGHT;
    payload[cnt_pld] = highByte(Value_send);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("L:");    debugprintln(Value_send);
  }

  Value_send = (int16_t)(Gewicht[0] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_1;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_1;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("G-1:");  debugprintln(Value_send);

#if (Anzahl_Sensoren_Gewicht < 3) // Beutensensor nur wenn bis zu zwei Waagen
  if (SensorTemp[Beute1] != No_Val) {
    Value_send  = (int16_t)(SensorTemp[Beute1] * 100.0);       // in 100stel grad
    //payload[cnt_pld] = TYPE_TEMP;
    //cnt_pld++;
    PayloadHeader |= TYPE_TEMP;
    payload[cnt_pld] = highByte(Value_send);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("T-1:");   debugprintln(Value_send);
  }
  if (SensorFeuchte[Beute1] != No_Value) {
    Value_send  = (int16_t)SensorFeuchte[Beute1];            // 0 - 100
    //payload[cnt_pld] = TYPE_RH;
    //cnt_pld++;
    PayloadHeader |= TYPE_RH;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("F-1:");  debugprintln(Value_send);
  }
#endif

#if (Anzahl_Sensoren_Gewicht > 1)  // zweite Waage
  Value_send = (int16_t)(Gewicht[1] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_2;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_2;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("G-2:");  debugprintln(Value_send);

#if (Anzahl_Sensoren_Gewicht < 3) // Beutensensor nur wenn bis zu zwei Waagen
  if (SensorTemp[Beute2] != No_Val) {
    Value_send  = (int16_t)(SensorTemp[Beute2] * 100.0);       // in 100stel grad
    //payload[cnt_pld] = TYPE_TEMP_2;
    //cnt_pld++;
    PayloadHeader |= TYPE_TEMP_2;
    payload[cnt_pld] = highByte(Value_send);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("T-2:");   debugprintln(Value_send);
  }
  if (SensorFeuchte[Beute2] != No_Value) {
    Value_send  = (int16_t)SensorFeuchte[Beute2];            // 0 - 100
    //payload[cnt_pld] = TYPE_RH_2;
    //cnt_pld++;
    PayloadHeader |= TYPE_RH_2;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("F-2:");  debugprintln(Value_send);
  }
#endif
#endif

#if (Anzahl_Sensoren_Gewicht > 2)  // dritte Waage ohne Sensoren
  Value_send = (int16_t)(Gewicht[2] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_3;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_3;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("G-3:");  debugprintln(Value_send);
#endif

#if (Anzahl_Sensoren_Gewicht == 4)   // vierte Waage ohne Sensoren
  Value_send = (int16_t)(Gewicht[3] * 100.0);                // in 100stel kilogramm
  //payload[cnt_pld] = TYPE_WEIGHT_4;
  //cnt_pld++;
  PayloadHeader |= TYPE_WEIGHT_4;
  payload[cnt_pld] = highByte(Value_send);
  cnt_pld++;
  payload[cnt_pld] = lowByte(Value_send);
  cnt_pld++;
  debugprintF("G-4:");  debugprintln(Value_send);
#endif


  if (Aux[1] != No_Value) {                                 // Wert  Luftdruck vorhanden
    Value_send = (uint16_t)(Aux[1] * 10.0);                 // hPa*10, 9000 - 11000
    //payload[cnt_pld] = TYPE_AUX1;
    //cnt_pld++;
    PayloadHeader |= TYPE_AUX1;
    payload[cnt_pld] = highByte(Value_send);
    cnt_pld++;
    payload[cnt_pld] = lowByte(Value_send);
    cnt_pld++;
    debugprintF("P:"); debugprintln(Value_send);
  }

  int8_t svc = (int8_t)Service;
  if (Service > 255.0) svc = 0xFF;       // max value in payload
  //payload[cnt_pld] = TYPE_SERVICE;
  //cnt_pld++;
  PayloadHeader |= TYPE_SERVICE;
  payload[cnt_pld] = svc & 0xFF ;
  cnt_pld++;
  debugprintF("S:");  debugprintln(svc);

  if (LoRaSeqCounter % 10 == 0 ) { // ID not in every transmission (LMIC.seqnoUp ist hier noch 0)
    //payload[cnt_pld] = TYPE_AUX3;
    //cnt_pld++;
    PayloadHeader |= TYPE_AUX3;
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
//----------------------------------------------------------------


//----------------------------------------------------------------
// Eventhandler  LORA
//----------------------------------------------------------------
void onEvent (ev_t ev) {

  debugprint(os_getTime());
  debugprint(": ");

  switch (ev) {
    case EV_TXCOMPLETE:

      debugprintlnF("TX!");

      /*
        if (LMIC.txrxFlags & TXRX_ACK)

        debugprintlnF("Received ack");

           if (LMIC.dataLen) {

              debugprintlnF("Received ");
              debugprintln(LMIC.dataLen);
              debugprintlnF(" bytes of payload");

            }
      */
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
      debugprintF("Joined ");
      debugprintln(LMIC.devaddr);
      break;
#endif
    case EV_RXCOMPLETE:
      // data received in ping slot
      debugprintlnF("RX!");

      break;
    default:

      debugprintlnF("?");

      break;
  }
  debugflush();
}
//----------------------------------------------------------------
