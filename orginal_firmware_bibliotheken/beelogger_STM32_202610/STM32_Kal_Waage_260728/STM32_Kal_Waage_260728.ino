/*
   (C) 2024 R.Schick / Thorsten Gurzan - beelogger.de

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program. If not, see <http://www.gnu.org/licenses/>.

    http://github.com/stm32duino/wiki/wiki/Libraries

    STM32 Core Version 2.6.0

    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k

    Blackpill STM32F411CE  512k
              STM32F401CC 256k
              STM32F401CE 512k


  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json

    Boardverwalter: Board STM32Cores
    Bibliotheken:
    STM32duino Low Power
    STM32duinoRTC


    Config:
    Generic STM32F1 series
    BluePill F103C8

    Generic STM32F4 series
    BlackPill F411CE / F401CC / F401CE (WeAct Blackpill)

    USART support "Enabled generic serial"
    USB support "keine /none"
    Optimzie "smallest"
    C Runtime Lib "NewLib Nano default"
    Upload methoed "STM32 Cube Programmer Serial"   ( == FTDI)


*/

// beelogger.de - Arduino Datenlogger für Imker
//

//----------------------------------------------------------------
// Allgemeine Konfiguration
//----------------------------------------------------------------

// hier kann das Kalibriergewicht in _Gramm_ eingetragen werden
long KalibrierGewicht = 500;  // 10000 = 10kG Referenzgewicht

//Sensorkonfiguration
#define Anzahl_Sensoren_Gewicht 1  // Mögliche Werte: '1','2','3','4'

// hier können bekannte Kalibrierwerte für Test-/Vergleichsmessung eingetragen werden
long Taragewicht[4] = { 10, 10, 10, 10 };          // Hier ist der Wert aus der Kalibrierung einzutragen
float Skalierung[4] = { 1.00, 1.00, 1.00, 1.00 };  // Hier ist der Wert aus der Kalibrierung einzutragen

#define Daten_speichern_EEPRom 0

// Anschluss / Konfiguration Wägezellen
// mit Anzahl_Sensoren_Gewicht 1
//   HX711(1) Kanal A = Wägeelement(e) Waage1
// mit Anzahl_Sensoren_Gewicht 2
//   HX711(1) Kanal B = Wägeelement(e) Waage2

// mit Anzahl_Sensoren_Gewicht 3
//   HX711(2) Kanal A = Wägeelement(e) Waage3
// mit Anzahl_Sensoren_Gewicht 4
//   HX711(2) Kanal B = Wägeelement(e) Waage4


// hier die Adressen für die/den HX711 eintragen
//                Nr.1: A,  B  Nr.2: A,  B
#if (defined(ARDUINO_BLUEPILL_F103CB) || defined(ARDUINO_BLUEPILL_F103C8))
uint8_t HX711_DT[2] = { PB9, PB10 };   // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = { PB8, PB11 };  // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock
#else
#if (defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE))
uint8_t HX711_DT[2] = { PB8, PB2 };    // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   Data
uint8_t HX711_SCK[2] = { PB9, PB10 };  // HX711 Nr.1: A,B;  HX711 Nr.2: A,B   S-Clock


#else  // any other
uint8_t HX711_DT[2] = { PB8, PB8 };
uint8_t HX711_SCK[2] = { PB9, PB9 };
#error "STM32 Typ / Board part number not supported"
#endif
#endif
//----------------------------------------------------------------


//################################################################
//################################################################
//################################################################


#if defined(ARDUINO_BLACKPILL_F411CE) || defined(ARDUINO_BLACKPILL_F401CC) || defined(ARDUINO_BLACKPILL_F401CE)
#define MONITOR_RX_TX Serial1
#else
#define MONITOR_RX_TX Serial
#endif

//----------------------------------------------------------------
// Aktivierung der Stromversorgung für Sensoren, Module
// WLAN und LTE Modul stopp
//----------------------------------------------------------------
#define Power_Pin PB5      // PB5
#define Stop_LTE_WLAN PB6  // PB6
//----------------------------------------------------------------


#include <STM32LowPower.h>
#include <HX711.h>

#define AT24Cxx_CTRL_ID 0x57  // EE-Prom I2C Adresse
#include <beelogger_at24cxx.h>

//----------------------------------------------------------------
// Variablen
//----------------------------------------------------------------
HX711 scale;
const float No_Val = -1.0;  // Vorbelegung, Wert nicht gemessen
float Gewicht[4] = { No_Val, No_Val, No_Val, No_Val };
float LetztesGewicht[4] = { 0, 0, 0, 0 };
float DS_Temp = No_Val;
//----------------------------------------------------------------

typedef union {
  float number;
  char buffer[sizeof(float)];
} FLOATUNION_t;





void setup() {


  MONITOR_RX_TX.begin(9600);
  while (!MONITOR_RX_TX) {};  // Wait for MONITOR_RX_TX port open
  digitalWrite(Stop_LTE_WLAN, LOW);
  pinMode(Stop_LTE_WLAN, OUTPUT);
  digitalWrite(Stop_LTE_WLAN, LOW);
  digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);
  delay(250);
  MONITOR_RX_TX.println("Waage Kalibrierung 14.02.2026");
  MONITOR_RX_TX.println(" ");

  MONITOR_RX_TX.println(F("Zur Kalibrierung der Stockwaagen bitte den Anweisungen folgen!"));
  MONITOR_RX_TX.println(F("Fehlerhafte und nicht angeschlossene Waagen werden auch angezeigt!"));
  MONITOR_RX_TX.println(F("Eine Waage, die nicht kalibriert werden soll, kann ausgelassen werden."));
  MONITOR_RX_TX.flush();

#if Daten_speichern_EEPRom
  AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);
  FLOATUNION_t val;
  for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
    my_EEPROM.ReadMem(10 + i * 10, val.buffer, sizeof(float));
    Taragewicht[i] = val.number;
    my_EEPROM.ReadMem(50 + i * 10, val.buffer, sizeof(float));
    Skalierung[i] = val.number;
    MONITOR_RX_TX.print(F("   Skalierung: "));
    MONITOR_RX_TX.print(Skalierung[i]);
    MONITOR_RX_TX.print(F("   Taragewicht: "));
    MONITOR_RX_TX.println(Taragewicht[i]);
  }
  my_EEPROM.end();
#endif

  MONITOR_RX_TX.flush();
  MONITOR_RX_TX.println(F(" Werte für Waage 1 verwenden: j/n"));
  MONITOR_RX_TX.flush();

  char c = '#';
  while ((c != 'j') && (c != 'n')) {
    c = MONITOR_RX_TX.read();
  };
  if (c == 'n') {
    kalibrieren();
  }
}

//######################################################
void loop() {
  char buf[16];
  Sensor_Gewicht(1);

  for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
    if (LetztesGewicht[i] == -1000.0) continue;  // keine Messung
    MONITOR_RX_TX.print(F("  Waage "));
    MONITOR_RX_TX.print(i + 1);
    MONITOR_RX_TX.print(F(":        Gewicht: "));
    dtostrf(Gewicht[i], 4, 3, buf);
    MONITOR_RX_TX.print(buf);
    MONITOR_RX_TX.print(" kg");
    delay(500);
    MONITOR_RX_TX.print(F("   Skalierung: "));
    MONITOR_RX_TX.print(Skalierung[i]);
    MONITOR_RX_TX.print(F("   Taragewicht: "));
    MONITOR_RX_TX.println(Taragewicht[i]);
    MONITOR_RX_TX.flush();
    delay(1000);
  }
  MONITOR_RX_TX.println(" ");
  char c = MONITOR_RX_TX.read();
  if (c == 'x') {
    kalibrieren();
  }

  delay(5000);
}
//######################################################


void kalibrieren() {
  char c;
  char buf[16];
  float Kal_Gew = ((float)KalibrierGewicht) / 1000.0;
  float wert;
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.println(F("  Alle Waagen ohne Gewicht!"));
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
  delay(2000);

  scale.begin(HX711_DT[0], HX711_SCK[0]);  // Nr. 1 Kanal A
  for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
    c = '#';
    if ((i == 1) || (i == 3)) {
      scale.set_gain(32);  // Nr.1,2 Kanal B
      scale.read();
    } else if (i == 2) {
      scale.begin(HX711_DT[1], HX711_SCK[1]);  // Nr. 2 Kanal A
      scale.set_gain(128);
      scale.read();
    }

    scale.power_up();
    delay(500);  // wait for stable HX711

    if (scale.wait_ready_retry(10, 500)) {
      MONITOR_RX_TX.println(F("HX711 o.k. \n"));

      MONITOR_RX_TX.println(scale.read());
      MONITOR_RX_TX.print(F("Waage Nummer: "));
      MONITOR_RX_TX.println(i + 1);
      delay(500);  // wait for stable HX711
      MONITOR_RX_TX.println(F(" Kalibrierung der Null-Lage ohne Gewicht mit '1' und 'Enter' starten!"));
      MONITOR_RX_TX.println(F(" Eingabe von 'x': Waage wird nicht kalibriert."));
      MONITOR_RX_TX.flush();
      while ((c != '1') && (c != 'x')) {
        c = MONITOR_RX_TX.read();
      };
      if (c == 'x') {
        MONITOR_RX_TX.println(" ");
        if (Taragewicht[i] == 10) {
          LetztesGewicht[i] = -1000.0;
          MONITOR_RX_TX.println(F(" keine Messung "));
          MONITOR_RX_TX.println(" ");
        } else {
          MONITOR_RX_TX.println(F(" verwende vorgegebene Daten für das Testwiegen"));
        }
        MONITOR_RX_TX.println(" ");
        continue;
      }
      c = '#';
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.print(F("Null-Lage ... / Tara "));
      MONITOR_RX_TX.flush();
      scale.set_scale(1.0);
      scale.read_average(10);

      MONITOR_RX_TX.print(F("  ...  "));
      MONITOR_RX_TX.flush();
      scale.read_average(20);

      MONITOR_RX_TX.print(F("  ...  "));
      MONITOR_RX_TX.flush();
      Taragewicht[i] = scale.read_average(32);
      MONITOR_RX_TX.println(Taragewicht[i]);
      MONITOR_RX_TX.println(" ");

      MONITOR_RX_TX.print(F("Waage Nummer: "));
      MONITOR_RX_TX.println(i + 1);
      MONITOR_RX_TX.print(F("mit genau  "));
      dtostrf(Kal_Gew, 4, 3, buf);
      MONITOR_RX_TX.print(buf);
      MONITOR_RX_TX.println(F("  Kilogramm beschweren - Kalibrierung mit '2' und 'Enter' starten!"));
      MONITOR_RX_TX.flush();

      while (c != '2') {
        c = MONITOR_RX_TX.read();
      };
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.print(F("Kalibriere Waage: "));
      MONITOR_RX_TX.print(i + 1);
      MONITOR_RX_TX.print(F("  ...  "));
      MONITOR_RX_TX.flush();
      scale.read_average(20);

      MONITOR_RX_TX.println(F("  ...  "));
      MONITOR_RX_TX.flush();
      wert = (float)(scale.read_average(32) - Taragewicht[i]);
      Skalierung[i] = wert / Kal_Gew;
      MONITOR_RX_TX.print(F("Taragewicht "));
      MONITOR_RX_TX.print(i + 1);
      MONITOR_RX_TX.print(": ");
      MONITOR_RX_TX.println(Taragewicht[i]);

      MONITOR_RX_TX.print(F("Skalierung  "));
      MONITOR_RX_TX.print(i + 1);
      MONITOR_RX_TX.print(": ");
      MONITOR_RX_TX.println(Skalierung[i]);
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();
      if (Skalierung[i] < 0.0) {
        MONITOR_RX_TX.print(F("\n Skalierwert negativ. Anschluss Wägezelle an HX711 prüfen oder Belastungsrichtung Wägezelle falsch! \n"));
        MONITOR_RX_TX.flush();
        c = 'm';
        while (c != 'w') {
          c = MONITOR_RX_TX.read();
        };
      }
      if (Skalierung[i] < 1000.0) {
        MONITOR_RX_TX.print(F("\n Skalierwert nicht plausibel. Bitte Wägezellen prüfen, Einbaulage und Verdrahtung kontrollieren! \n"));
        MONITOR_RX_TX.flush();
        c = 'm';
        while (c != 'w') {
          c = MONITOR_RX_TX.read();
          delay(50);
        };
      }
      if (Skalierung[i] == 0.0) {
        MONITOR_RX_TX.println(F("\n Fehler in Messwerten:"));
        MONITOR_RX_TX.println(F(" - Verbindung HX711 zu STM32 prüfen."));
        MONITOR_RX_TX.println(F(" - eventuell Wägezelle mit anderen Leiterfarben?"));
        MONITOR_RX_TX.println(F(" - eventuell Wägezelle defekt?"));
        MONITOR_RX_TX.println(F(" - HX711 defekt?"));
        MONITOR_RX_TX.println(F(" - ausschließlich HX711- Bibliothek aus beelogger- Lib-Paket verwenden."));
        MONITOR_RX_TX.flush();
        c = 'm';
        while (c != 'w') {
          c = MONITOR_RX_TX.read();
        };
      }
      MONITOR_RX_TX.print(F("Pruefe Gewicht: "));
      MONITOR_RX_TX.flush();
      long l_gew = scale.read_average(32) - Taragewicht[i];
      float gew = ((float)l_gew) / Skalierung[i];

      if (isnan(gew)) {
        MONITOR_RX_TX.print(F("\n Verbindung HX711 zu STM32Fxyz prüfen oder HX711 defekt! \n"));
        MONITOR_RX_TX.flush();
      }
      dtostrf(gew, 4, 3, buf);
      MONITOR_RX_TX.println(buf);
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();

      if ((i == 1) || (i == 3)) {  // vier Kanal
        scale.power_down();
      }
      if ((i == 0) && (Anzahl_Sensoren_Gewicht == 1)) {  // HX711 Nr. 1 abschalten
        scale.power_down();
      }
      if ((i == 2) && (Anzahl_Sensoren_Gewicht == 3)) {  // HX711 Nr. 2 abschalten
        scale.power_down();
      }

      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.println(F("Kalibriervorgang abgeschlossen. "));
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();

      MONITOR_RX_TX.println(F(" Die Zeile für die Konfiguration:"));
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.print(F("const long Taragewicht[4] = { "));
      MONITOR_RX_TX.print(Taragewicht[0]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Taragewicht[1]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Taragewicht[2]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Taragewicht[3]);

#if Daten_speichern_EEPRom
      AT24Cxx my_EEPROM(AT24Cxx_CTRL_ID);
      FLOATUNION_t val;
      for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
        val.number = Taragewicht[i];
        my_EEPROM.WriteMem(10 + i * 10, val.buffer, sizeof(float));
        val.number = Skalierung[i];
        my_EEPROM.WriteMem(50 + i * 10, val.buffer, sizeof(float));
      }
      my_EEPROM.end();
#endif

      MONITOR_RX_TX.println(F("}; // Hier ist der Wert aus der Kalibrierung einzutragen"));
      MONITOR_RX_TX.print(F("const float Skalierung[4] = { "));
      MONITOR_RX_TX.print(Skalierung[0]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Skalierung[1]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Skalierung[2]);
      MONITOR_RX_TX.print(" , ");
      MONITOR_RX_TX.print(Skalierung[3]);
    } else {
      MONITOR_RX_TX.println(F(" HX711 nicht gefunden. HX711 SCK/DT Konfiguration und/oder Verdrahtung prüfen!"));
      MONITOR_RX_TX.println(F(" uint8_t HX711_SCK[2] = {  ??  }"));
      MONITOR_RX_TX.println(F(" uint8_t HX711_DT[2]  = {  ??  }"));
    }

    MONITOR_RX_TX.println(F("}; // Hier ist der Wert aus der Kalibrierung einzutragen"));
    MONITOR_RX_TX.println(" ");
    MONITOR_RX_TX.println(F(" Weiter mit w"));
    c = '0';
    while (c != 'w') {
      c = MONITOR_RX_TX.read();
    };
    MONITOR_RX_TX.println(F("Starte Testwiegen. "));
    MONITOR_RX_TX.println(F("x = Kalibrierung wiederholen. "));
    MONITOR_RX_TX.println(" ");

    MONITOR_RX_TX.flush();
    delay(200);
  }
}
//----------------------------------------------------------------
// Funktion Gewicht
//----------------------------------------------------------------
void Sensor_Gewicht(byte quick) {
  if ((Anzahl_Sensoren_Gewicht > 0) && (Anzahl_Sensoren_Gewicht < 5)) {
    const float Diff_Gewicht = 0.5;
    //HX711 scale;
    scale.begin(HX711_DT[0], HX711_SCK[0]);  // Nr. 1 Kanal A
    for (int i = 0; i < Anzahl_Sensoren_Gewicht; i++) {
      if (LetztesGewicht[i] == -1000.0) continue;  // keine Messung
      MONITOR_RX_TX.print(" - wiegen mit Waage ");
      MONITOR_RX_TX.print(i + 1);
      MONITOR_RX_TX.print(" ");
      MONITOR_RX_TX.flush();
      if (i == 2) {
        scale.begin(HX711_DT[1], HX711_SCK[1], 128);  // Nr.2 Kanal A
      } else if ((i == 1) || (i == 3)) {
        scale.set_gain(32);  // Nr.1,2 Kanal B
      }
      scale.power_up();
      delay(500);  // wait for stable HX711

      if (scale.wait_ready_retry(10, 500)) {
        scale.read();
        delay(500);                     // wait for stable HX711
        for (byte j = 0; j < 2; j++) {  // Anzahl der Widerholungen, wenn Abweichung zum letzten Gewicht zu hoch
          long l_gew = scale.read_average(10) - Taragewicht[i];
          Gewicht[i] = ((float)l_gew) / Skalierung[i];
          if (quick) {
            LetztesGewicht[i] = Gewicht[i];
            break;
          }
          if (fabs(Gewicht[i] - LetztesGewicht[i]) < Diff_Gewicht) break;  // Abweichung für Fehlererkennung
          delay(1000);                                                     // // Wartezeit zwischen Wiederholungen
          MONITOR_RX_TX.print("..");
        }
        LetztesGewicht[i] = Gewicht[i];
      } else {
        MONITOR_RX_TX.println("fail.. ");
      }

      if ((i == 1) || (i == 3)) {  // vier Kanal
        scale.power_down();
      }
      if ((i == 0) && (Anzahl_Sensoren_Gewicht == 1)) {  // HX711 Nr. 1 abschalten
        scale.power_down();
      }
      if ((i == 2) && (Anzahl_Sensoren_Gewicht == 3)) {  // HX711 Nr. 2 abschalten
        scale.power_down();
      }
      MONITOR_RX_TX.println(" ");
      MONITOR_RX_TX.flush();
    }
  }
}
//----------------------------------------------------------------
