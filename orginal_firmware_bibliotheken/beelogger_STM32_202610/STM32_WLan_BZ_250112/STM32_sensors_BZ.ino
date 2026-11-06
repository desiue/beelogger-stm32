#include "STM32_beelogger.h"

#include <Wire.h>
// 04.03.2020 STM32 adaption
// 16.03.2022 fix Ram Use of Wire
// 22.06.2022 SI7021 value check
// 19.12.2022 separate off DHT
// 16.03.2023 debuginfo "HX711"
// 21.04.2023 change to beelogger Libs, check I2C-devices
// 30.06.2024 Zuordnung Aux[]

//----------------------------------------------------------------
// Konfiguration Gewicht
//----------------------------------------------------------------
#include <HX711.h>
//----------------------------------------------------------------


//----------------------------------------------------------------
//Konfiguration Bienenzähler
//----------------------------------------------------------------
#include <Adafruit_MCP23017.h>
Adafruit_MCP23017 mcp[Anzahl_Sensoren_Bienenzaehler];
//----------------------------------------------------------------



/**
  @brief Funktion  setup_sensors()
         Setup the sensors and their instances

  @param  none
  @return  none, setzt globale Variablen
*/
void setup_sensors() {

  //----------------------------------------------------------------
  //  Config ADC to 12 bit
  //----------------------------------------------------------------
  analogReadResolution(12);        // STM32 has 12 bit resolution
  //----------------------------------------------------------------


  //----------------------------------------------------------------
  // Setup Gewicht
  //----------------------------------------------------------------
#if (Anzahl_Sensoren_Gewicht)

  Sensor_Gewicht(true);   // Startwert Gewicht holen

#endif

  //----------------------------------------------------------------
  // Setup Bienenzähler
  //----------------------------------------------------------------
#if ((Anzahl_Sensoren_Bienenzaehler > 0) and (Anzahl_Sensoren_Bienenzaehler < 5))

  debugprint(F("Setup: Bienenzaehler konfiguriert: "));
  debugprintln(Anzahl_Sensoren_Bienenzaehler);
  debugflush();

  for (byte j = 0; j < Anzahl_Sensoren_Bienenzaehler; j++) {
    mcp[j].begin(j + 4);
    for (byte i = 0; i < 16; i++) {
      mcp[j].pinMode(i, INPUT);
      mcp[j].pullUp(i, LOW);
      LetzterSensor[j][i] = 0;
    }
  }
  Sensor_Bienen();
#endif
}
//----------------------------------------------------------------


/**
  @brief Funktion   Sensor_Temp_Zelle

  @param  set: initialise or set temperature
  @return  none, setzt globale Variablen
*/
void Sensor_Temp_Zelle(uint8_t set) {
  if (set) {
#if Anzahl_Sensoren_Gewicht > 1
    DS_Temp = SensorTemp[Temp_Zelle];    // duo, tripple, quad, ...
    if (DS_Temp == No_Val) {
      DS_Temp = Aux[2];                  // force
    }
#else
    if (SensorTemp[Aussenwerte] == No_Val) { // single
      SensorTemp[Aussenwerte] = Aux[2];      // force
    }
#endif
  }
  else {
#if Anzahl_Sensoren_Gewicht > 1
    DS_Temp = No_Val;
#else
    SensorTemp[Aussenwerte] = No_Val;
#endif

    const float CPU_TEMP_CORR = +3.5F; // to set µC dependent offset
    float Temp_STM;
    // reading STM32-V-Ref and V-Temperatur
    int16_t adc_Temp = analogRead(ATEMP);
    int16_t adc_VRef = analogRead(AVREF);

#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC)
    //#pragma message "Blackpill F4x1C"
    // reference Manual STM32F401: Temperature (in °C) = {(VSENSE – V25) / Avg_Slope} + 25
    // V25 = VSENSE value for 25° C parameters come from F401 datasheet - ch. 6.3.21
    // Avg_Slope = average slope of the temperature vs. VSENSE curve (given in mV/°C or μV/°C)
#define VREF_INT   1.21    // typical internal reference voltage at 25 deg. celsius 1.20; 1.18 - 1.24
#define Temp_V25   0.76    // typical voltage at 25 deg. celsius; 0.76
#define Avg_Slope  0.0025  // slope of the sensor in V/deg. celsius; typ. 2.5mV/deg. cel

    float vdd = VREF_INT / adc_VRef;

#if 1 // alternative method
    // see datasheet for position of the calibration values, this is for STM32F401/411
    const uint16_t* const ADC_TEMP_3V3_30C =  reinterpret_cast<uint16_t*>(0x1FFF7A2C);
    const uint16_t* const ADC_TEMP_3V3_110C =  reinterpret_cast<uint16_t*>(0x1FFF7A2E);
    const float CALIBRATION_REFERENCE_VOLTAGE = 3.3F;
    // scale constants to current reference voltage
    float adcCalTemp30C = static_cast<float>(*ADC_TEMP_3V3_30C) * (vdd * 4096.0 / CALIBRATION_REFERENCE_VOLTAGE);;
    float adcCalTemp110C = static_cast<float>(*ADC_TEMP_3V3_110C) * (vdd * 4096.0 / CALIBRATION_REFERENCE_VOLTAGE );
    Temp_STM = (static_cast<float>(adc_Temp) - adcCalTemp30C) / (adcCalTemp110C - adcCalTemp30C) * (110.0F - 30.0F) + 30.0F;
#else

    Temp_STM = ((vdd * adc_Temp) - Temp_V25) / Avg_Slope + 25.0;

#endif

#else // Bluepill F103C8/CB
    // #pragma message "Bluepill F103C8/CB"
    // Reference Manual STM32F103:  Temperature (in °C) = {(V25 - VSENSE) / Avg_Slope} + 25
    // Temp_Sensor_V25 = 1.43 and Temp_Sens_Slope = 0.0043 parameters come from F103 datasheet - ch. 5.3.19
    // and need to be calibrated for every chip (large fab parameters variance)
#define VREF_INT   1.20     // typical internal reference voltage at 25 deg. celsius 1.20; 1.16 - 1.24
#define Temp_V25   1.41     // typical voltage at 25 deg. celsius 1.43; 1.34 - 1.52
#define Avg_Slope  0.0043   // slope of the sensor in V/deg. celsius; typ. 4.3mV/deg. cel

    float vdd = VREF_INT / adc_VRef;
    Temp_STM = (Temp_V25 - (vdd * adc_Temp)) / Avg_Slope + 25.0;
#endif

    Aux[2] = Temp_STM  + CPU_TEMP_CORR;
    Aux[1] = vdd * 4096.0;

    debugprintF("Vdd. [V]: ");
    debugprintln(Aux[1]);
    debugprintF("T - STM32 [C]: ");
    debugprintln(Aux[2]);
    debugflush();
  }
}
//----------------------------------------------------------------



/**
  @brief Funktion   Gewicht()

  @param  quick: initialise weight
  @return  none, setzt globale Variablen
*/
void Sensor_Gewicht(uint8_t quick) {

#if ((Anzahl_Sensoren_Gewicht > 0) && (Anzahl_Sensoren_Gewicht < 3))

  const float Diff_Gewicht = 0.5;

  HX711 scale;
  debugprintlnF("HX711");
  scale.begin(HX711_DT[0], HX711_SCK[0], 128); // Nr.1 Kanal A
  for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
    if (i == 1 ) {
      scale.set_gain(32);  //  Kanal B
    }
    if (Taragewicht[i] != 10) {
      scale.power_up();
      uint8_t count = 0, retries = 5;
      while (count < retries) {
        LogDelay(600);  // wait for HX711
        if (scale.is_ready()) {
          break;
        }
        count++;
      }
      if (count < 5) { // scale found
        scale.read();  // set gain using read
        LogDelay(600);  // wait for stable HX711
        for (uint8_t j = 0 ; j < 2; j++) { // Anzahl der Wiederholungen, wenn Abweichung zum letzten Gewicht zu hoch
          long l_gew = scale.read_average(10) - Taragewicht[i];
          Gewicht[i] = ((float) l_gew) / Skalierung[i];
          if (quick) {
            LetztesGewicht[i] = Gewicht[i];
            break;
          }
          if (fabs(Gewicht[i] - LetztesGewicht[i]) < Diff_Gewicht) break; // Abweichung für Fehlererkennung
          LogDelay(2000); // Wartezeit zwischen Wiederholungen
        }
        LetztesGewicht[i] = Gewicht[i];
      }
      else {
        Gewicht[i] = No_Value; // Fehler
        debugprintF(" W fail");
      }
    }

    if (i == 1) {  // 2 Waagen
      scale.power_down();
    }
    else if ((i == 0) && (Anzahl_Sensoren_Gewicht == 1)) { // nur 1 Waage
      scale.power_down();
    }

    debugprintF(" W ");
    debugprint(i + 1);
    debugprintF(" [kg]: ");
    debugprintln(Gewicht[i]);
    debugflush();

  }
#endif
}

//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion Bienenzähler
//----------------------------------------------------------------
void BienenTachoCalc() {
#if (Anzahl_Sensoren_Bienenzaehler > 0)
  uint32_t tmptime = ( millis() -  BienenIntervall);
  BienenOutTacho = (int)(( -0.49 + (60000.0 / (float)tmptime) * (float)BienenOut));
  BienenInTacho  = (int)(( 0.49 + (60000.0 / (float)tmptime) * (float)BienenIn));

  debugprint((int)(tmptime / 1000)); debugprintln(" sec");
  debugprint("Bienen in Summe (raus | rein): "); debugprint(BienenOut); debugprint(" | "); debugprintln(BienenIn);
  debugprint("Bienen je min   (raus | rein): "); debugprint(BienenOutTacho); debugprint(" | "); debugprintln(BienenInTacho);
#endif
}
//----------------------------------------------------------------

//----------------------------------------------------------------
// Funktion Bienenzähler
//----------------------------------------------------------------
void Sensor_Bienen() {
#if ((Anzahl_Sensoren_Bienenzaehler > 0) and (Anzahl_Sensoren_Bienenzaehler < 5))
  for (uint8_t j = 0; j < Anzahl_Sensoren_Bienenzaehler; j++) {

    for (uint8_t i = 0; i < 16; i++) {
      SensorAktuell[i] = mcp[j].digitalRead(i);
      /*
              debugprint("Bienensensor Nr.:  ");
              debugprint(i + 1);
              debugprint("  Messwert:  ");
              debugprintln(SensorAktuell[i]);
              debugflush();
      */
    }
#if Neue_Bienenzaehlfunktion  // neue Methode  Bienenzählfunktion
    for (uint8_t i = 0; i < 8; i++) {
      TunnelZuvor = TunnelZustand[j][i];
      if ((SensorAktuell[i] == 0) && ( SensorAktuell[i + 8] == 0 )) {
        if ( TunnelZustand[j][i] == 3) {
          BienenOut--;
#if myDEBUG
          debugprintln("Biene raus !");
          BienenTachoCalc();
#endif
        }
        else if ( TunnelZustand[j][i] == 13) {
          BienenIn++;
#if myDEBUG
          debugprintln("Biene rein !");
          BienenTachoCalc();
#endif
        }
        TunnelZustand[j][i] = 0;
      }
      else if ((SensorAktuell[i] == 0) && ( SensorAktuell[i + 8]) == 1 ) {
        if   (( TunnelZustand[j][i] == 0 ) || (TunnelZustand[j][i] == 2 )) TunnelZustand[j][i] = 1;
        else if ( TunnelZustand[j][i] == 12 ) TunnelZustand[j][i] = 13;
      }
      else if ((SensorAktuell[i] == 1) && ( SensorAktuell[i + 8]) == 1 ) {
        if   (( TunnelZustand[j][i] == 1 ) ||  ( TunnelZustand[j][i] == 3 )) TunnelZustand[j][i] = 2;
        else if (( TunnelZustand[j][i] == 11 ) || ( TunnelZustand[j][i] == 13 )) TunnelZustand[j][i] = 12;
      }
      else if ((SensorAktuell[i] == 1) && ( SensorAktuell[i + 8]) == 0 ) {
        if   ( TunnelZustand[j][i] == 2 ) TunnelZustand[j][i] = 3;
        else if (( TunnelZustand[j][i] == 0 ) || (TunnelZustand[j][i] == 12 ))  TunnelZustand[j][i] = 11;
      }
#if myDEBUG
      if (TunnelZustand[j][i] != TunnelZuvor) {
        debugprint(TunnelZuvor); debugprint("->"); debugprintln(TunnelZustand[j][i]);
      }
#endif
    }

#else  // alte Bienenzählfunktion
    for (uint8_t i = 0; i < 8; i++) {
      if (SensorAktuell[i] != LetzterSensor[j][i]) {
        if (SensorAktuell[i] > SensorAktuell[i + 8]) {
          if (BienenIn == -1) BienenIn = 1;
          else  BienenIn++;
        }
      }
      if (SensorAktuell[i + 8] != LetzterSensor[j][i + 8]) {
        if (SensorAktuell[i + 8] > SensorAktuell[i]) {
          if (BienenOut == -1) BienenOut = 1;
          else BienenOut++;
        }
      }
      LetzterSensor[j][i] = SensorAktuell[i];
      LetzterSensor[j][i + 8] = SensorAktuell[i + 8];
    }
#endif
  }
  delay(10);
#endif
}
//----------------------------------------------------------------




/**
  @brief Funktion Si7021 - Temperatur und Luftfeuchte
  @param  globale #define
  @return  none, setzt globale Variablen
*/
// Auswahl der Library
#define SI7021_Type 0  // 0 = Adafruit Lib, 1 = SI7021 Lib Marcus Sorensen


#if (Anzahl_Sensoren_Si7021 == 1)

#if SI7021_Type == 1
#include "SI7021.h"
#else
#include "Adafruit_Si7021.h"
#endif

void Sensor_Si7021() {

  debugprintlnF("Si7021");
  debugflush();
  SensorTemp[3] = No_Val;
  SensorFeuchte[3]  = No_Value;

#if SI7021_Type
  SI7021 sensor7021;
  int check = sensor7021.begin();
  if (check) {
    int temp = sensor7021.getCelsiusHundredths();
    delay(30);
    unsigned int hum = sensor7021.getHumidityPercent();
    SensorTemp[3] = ((float) temp) / 100.0;
    SensorFeuchte[3]  = ((float) hum );
  }
#else
  Adafruit_Si7021 mySI7021 = Adafruit_Si7021();
  //mySI7021.begin(); // not needed
  SensorTemp[3] = mySI7021.readTemperature();
  if (isnan(SensorTemp[3]))  SensorTemp[3] = No_Val;
  SensorFeuchte[3] = mySI7021.readHumidity();
  if (isnan(SensorFeuchte[3])) SensorFeuchte[3]  = No_Value;
#endif

  Wire.end(); // ! important
  debugprintF(" [C]: ");
  debugprintln(SensorTemp[3]);
  debugprintF(" [%RH]: ");
  debugprintln(SensorFeuchte[3]);
  debugflush();
}
#else
void Sensor_Si7021() {};
#endif


/**
  @brief Funktion SHT31 - Temperatur und Luftfeuchte
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if ((Anzahl_Sensoren_SHT31 == 1) || (Anzahl_Sensoren_SHT31 == 2))

#include "beelogger_Adafruit_SHT31.h"

void Sensor_SHT31() {

  float Temperatur_SHT31 = No_Val;
  float Luftfeuchte_SHT31 = No_Value;
  uint8_t check;

  beelogger_Adafruit_SHT31 my_sht;
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_SHT31; i++) {
    check = my_sht.begin((uint8_t)SHT31_adresse[i]); // I2C Adresse
    LogDelay (200); // time til sensor ready
    if (check) { // if SHT ok
      Temperatur_SHT31 = my_sht.readTemperature();
      if (isnan(Temperatur_SHT31)) {
        Temperatur_SHT31 = No_Val;
      }
      Luftfeuchte_SHT31 = my_sht.readHumidity();
      if (isnan(Luftfeuchte_SHT31)) {
        Luftfeuchte_SHT31 = No_Value;
      }
    }
    else {
      Temperatur_SHT31 = No_Val;
      Luftfeuchte_SHT31 = No_Value;
    }
    if (i == 0) { // first SHT31
      SensorTemp[4] = Temperatur_SHT31;
      SensorFeuchte[4] = Luftfeuchte_SHT31;
    }
    else { // second SHT31
      SensorTemp[5] = Temperatur_SHT31;
      SensorFeuchte[5] = Luftfeuchte_SHT31;
    }
    Wire.end();

    debugprintlnF("SHT31");
    debugprintF(" [C]: ");
    debugprintln(Temperatur_SHT31);
    debugprintF(" [ % RH]: ");
    debugprintln(Luftfeuchte_SHT31);
    debugflush();

  }
}
#else
void Sensor_SHT31() {};
#endif


/**
  @brief Funktion BME280 - Temperatur und Luftfeuchte, Luftdruck
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if ((Anzahl_Sensoren_BME280 == 1) || (Anzahl_Sensoren_BME280 == 2))

#include "beelogger_Adafruit_BME280.h"
#define SEALEVELPRESSURE_HPA (1013.25f)

void Sensor_BME280() {

  float Temperatur_BME = No_Val;
  float Luftfeuchte_BME = No_Value;
  float Luftdruck_BME = No_Value;
  uint8_t check;

  beelogger_Adafruit_BME280 my_bme;
  for (uint8_t i = 0 ; i < Anzahl_Sensoren_BME280; i++) {
    check = my_bme.begin(BME280_adresse[i]); // I2C Adresse
    LogDelay (200); // time til sensor ready
    if (check) { // if bme ok
      Temperatur_BME = my_bme.readTemperature();
      if (isnan(Temperatur_BME)) {
        Temperatur_BME = No_Val;
      }
      Luftfeuchte_BME = my_bme.readHumidity();
      if (isnan(Luftfeuchte_BME)) {
        Luftfeuchte_BME = No_Value;
      }
      Luftdruck_BME = my_bme.readPressure();
      if (isnan(Luftdruck_BME)) {
        Luftdruck_BME = No_Value;
      }
      else {
        Luftdruck_BME = Luftdruck_BME / 100.0;
      }
    }
    else {
      Temperatur_BME = No_Val;
      Luftfeuchte_BME = No_Value;
      Luftdruck_BME = No_Value;
    }
    debugprintlnF("BME280");
    if (i == 0) { // first BME
      SensorTemp[6] = Temperatur_BME;
      SensorFeuchte[6] = Luftfeuchte_BME;

      Aux[0] = Luftdruck_BME;     // Hier kann die Zuordnung der Sensoren geändert werden
      debugprintF(" [hPa]: ");
      debugprintln(Luftdruck_BME);
    }
    else { // second BME
      SensorTemp[7] = Temperatur_BME;
      SensorFeuchte[7] = Luftfeuchte_BME;
      // Aux[0] = Luftdruck_BME;             // Hier kann die Zuordnung der Sensoren geändert werden
    }
    Wire.end();

    debugprintF(" [C]: ");
    debugprintln(Temperatur_BME);
    debugprintF(" [ % RH]: ");
    debugprintln(Luftfeuchte_BME);
    debugflush();

  }
}
#else
void Sensor_BME280() {};
#endif


/**
  @brief Funktion DS18B20 - Temperatur und Luftfeuchte
  auslesen eines einzelnen DS18B20 ohne Bibliothek, wg. Speicherbedarf
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if ((Anzahl_Sensoren_DS18B20 > 0) and (Anzahl_Sensoren_DS18B20 < 5))

#include <OneWire.h>
#include <DallasTemperature.h>

#define Sensor_Aufloesung 12

void Sensor_DS18B20() {

  float Temperatur_DS = No_Val;
  DeviceAddress Sensor_Adressen;
  OneWire oneWire(ONE_WIRE_BUS);
  DallasTemperature sensors(&oneWire);

  sensors.begin();

  for (uint8_t i = 0 ; i < sensors.getDeviceCount(); i++) {
    if (sensors.getAddress(Sensor_Adressen, i)) {
      sensors.setResolution(Sensor_Adressen, Sensor_Aufloesung);
    }
  }
  sensors.requestTemperatures();
  LogDelay(800);//Wartezeit!
  for (byte i = 0 ; i < Anzahl_Sensoren_DS18B20; i++) {
    Temperatur_DS = No_Val;
    if (i < sensors.getDeviceCount()) {
      Temperatur_DS = sensors.getTempCByIndex(i);

      debugprint("DS18B20 Nr.: ");
      debugprint(i + 1);
      debugprint("  Messwert:  ");
      debugprintln(Temperatur_DS);
      debugflush();

      if (Temperatur_DS == DEVICE_DISCONNECTED_C) {
        Temperatur_DS = No_Val;
      }
      Sensor_Bienen();
    }

    // assign values
    if (i == 4) { // the fifth DS18B20
      SensorTemp[0] = Temperatur_DS;  // 5. DS18B20 in Temp[0]
    }
    else {
      SensorTemp[8 + i] = Temperatur_DS; // Temp [] 8,9,10,11
    }

#if (Anzahl_Sensoren_Gewicht < 3) // Single/Double System
#if (Anzahl_Sensoren_DS18B20 == 2) // 2 DS18B20
    SensorFeuchte[8] = Temperatur_DS;  // 2. DS18B20 in Feuchte[]
#else
    if (i == 2) { // 3. DS18B20
      SensorFeuchte[8] = Temperatur_DS;  // 3. DS18B20 in Feuchte[]
    }
    if (i == 3) { // 4. DS18B20
      SensorFeuchte[9] = Temperatur_DS;  // 4. DS18B20 in Feuchte[]
    }
#endif
#endif

  }
}
#else
void Sensor_DS18B20() {};
#endif


/**
  @brief Funktion BH1750 - Beleuchtungsstärke
  auslesen eines einzelnen DS18B20 ohne Bibliothek, wg. Speicherbedarf
  @param  globale #define
  @return  none, setzt globale Variablen
*/
#if (Anzahl_Sensoren_Licht == 1)

#include <Wire.h>
#include <AS_BH1750.h>

void Sensor_Licht() {

  AS_BH1750 sensor(BH1750_ADRESS);
  if (sensor.begin()) {
    Licht = sensor.readLightLevel();
    sensor.powerDown();
  }
  Wire.end();

  debugprintlnF("BH1750");
  debugprintF(" [lux]: ");
  debugprintln(Licht);
  debugflush();

}
#else
void Sensor_Licht() {};
#endif
