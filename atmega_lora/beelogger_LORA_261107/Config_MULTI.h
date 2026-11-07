/****************************************************************/
// Sensorkonfiguration
// 1. Sensor für die Abfrage durch den Sketch aktivieren
// 2. aktive Sensoren für Temperatur/Feuchte in der Sensormatrix zuordnen
// 3. Sensor für Temperatur Wägezelle festlegen
/****************************************************************/
#define Anzahl_Sensoren_DHT     0 // Mögliche Anzahl: '0','1','2'  --- Nr 1,2 ---  (Temperatur + Luftfeuchte)
#define DHTTYPE DHT22   // DHT 22  (AM2302), AM2321  // DHT 21 (AM2301)


#define Anzahl_Sensoren_Si7021  0 // Mögliche Anzahl: '0','1'      --- Nr 3 -----  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_SHT31   0 // Mögliche Anzahl: '0','1','2'  --- Nr 4,5 ---  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_BME280  0 // Mögliche Anzahl: '0','1','2'  --- Nr 6,7 ---  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_DS18B20 1 // Mögliche Anzahl: '0','1','2'  --- Nr 8,9 ---  (Nur Temperatur)
//                                   Mögliche Anzahl:     '3','4'  --- Nr 10,11 --- ( Messwert bei Single/DUO als "Luftfeuchte" bei 8,9)
//                                   Mögliche Anzahl:     '5'      --- Nr 0 --- 


// 2. Sensormatrix, hier kann die Zuordnung der Sensoren geändert werden
// Nr 1 - 9 aus Liste oben auswählen, wenn kein Sensor gewünscht ist einfach "0" angeben
// wenn kein Sensor für die Aussentemperatur gesetzt ist, wird automatisch der Temperatursensor der RTC verwendet
#define Aussenwerte              0    // 0 oder Nr. 1 - 11; bei Single:  0 oder Nr. 1 - 8; bei DUO: 0 oder Nr. 1 - 9
// Sensor Beute 1
#define             Beute1       0    // 0 oder Nr. 1 - 11; bei Single:  0 oder Nr. 1 - 8; bei DUO: 0 oder Nr. 1 - 9
// Sensor Beute 2
#define             Beute2       0    // 0 oder Nr. 1 - 11; bei DUO: 0 oder Nr. 1 - 9
// mit drei Waagen
#define             Beute3       0    // 0 oder Nr. 1 - 11
// mit vier Waagen
#define             Beute4       0    // 0 oder Nr. 1 - 11


// Temperatur Wägezelle (nur Duo, Tripple, Quad usw.)
// Sensor, der die Temperatur der Wägezelle erfasst;  vorbelegt der erste DS18B20
// für Systeme mit einer Waage identisch zum Sensor Aussenwerte eintragen
#define             Temp_Zelle   8    // Nr. 0 - 11


/****************************************************************/
// erweiterte Sensoren
/****************************************************************/
// Luftdruck von BME280
#define Korrektur_Luftdruck      0.0 // Korrekturwert um Abweichungen zu offiziellen Wetterstationen bei Bedarf auszugleichen; Aux[1]

#define Anzahl_Sensoren_Licht      0 // Mögliche Anzahl: '0','1'   //BH1750   

// Audio mit Sensor GY-MAX4466   
#define beelogger_Audio            0 // Mögliche Anzahl: '0','1'   // Aux[0], Aux[2]; Sensor GY-MAX4466 

//Niederschlagsensor  ACHTUNG: belegt gleichen Messwert wie AudioSensor
#define Anzahl_Sensor_Regen        0 // Mögliche Anzahl: '0','1'   // Aux[2]; Sensor Regen
const float Rain_Cal = { 1.0 }; // Hier ist der Wert aus der Kalibrierung einzutragen 



/****************************************************************/
// Konfiguration Waage(n)
/****************************************************************/
#define Anzahl_Sensoren_Gewicht 1 // Mögliche Anzahl: '1','2','3','4'

// Anschluss / Konfiguration Wägezellen
// mit Anzahl_Sensoren_Gewicht 1
//   HX711(1) Kanal A =  Waage1;
// mit Anzahl_Sensoren_Gewicht 2 zusätzlich:
//   HX711(1) Kanal B =  Waage2;

// mit Anzahl_Sensoren_Gewicht 3 zusätzlich:
//   HX711(2) Kanal A =  Waage3; 
// mit Anzahl_Sensoren_Gewicht 4 zusätzlich:
//   HX711(2) Kanal B =  Waage4;


/****************************************************************/
// Kalibrierwerte für die Wägezellen
const long Taragewicht[4] = {  10,  10,  10,  10}; // Hier ist der Wert aus der Kalibrierung einzutragen
const float Skalierung[4] = { 1.0, 1.0, 1.0, 1.0}; // Hier ist der Wert aus der Kalibrierung einzutragen


/****************************************************************/
// Kalibrierwerte für die Spannungsmessung Akku
const long Kalib_Spannung = 1000;    // Hier muss der Wert aus der Kalibrierung eingetragen werden, sonst funktioniert der Programmcode nicht
const long Kalib_Bitwert  =  100;    // Hier muss der Wert aus der Kalibrierung eingetragen werden, sonst funktioniert der Programmcode nicht
/****************************************************************/

//Konfiguration Spannungsversorgung
// Li-Ion Akku
const float VAlternativ = 3.8;       // Minimale Spannung ab der automatisch das alternative Intervall aktiviert wird
const float VMinimum    = 3.75;      // Minimale Spannung ab der ab der keine Messungen und auch kein Versand von Daten erfolgt
/****************************************************************/




/****************************************************************/
// Allgemeine Konfiguration
/****************************************************************/
#define AlternativIntervallMinuten  120  // Weckinterval, wenn VAlternativ erreicht = Akku fast leer


// Wartezeit ohne Messungen wenn Schalter Arbeiten am Bienenvolk dauernd aktiv
// Zeit in Minuten, 5 Minuten Schritte
#define USER_INT_TM      30

/****************************************************************/


/****************************************************************/
// Konfiguration Datenablage EEProm
// Anpassungen nur mit Vorsicht.
/****************************************************************/
#define EEProm_Size 32768    // 32kByte EE-Prom  256kbit

// Erläuterungen:
//                  Datensatzgröße    EE-Prom 4kB  8kB  32kByte
//                                    max. Anzahl gespeicherte Datensätze      
// beelogger Single  96 Byte                 42    84   254
// beelogger DUO    120 Byte                 34    68   254
// beelogger Quad   152 Byte                 26    52   208

// mit 4kB EE-Prom
// mögliche Überbrückungszeit ohne Senden bei 5 Minuten Messzyklus
//                                          ca. Stunden   
// beelogger Single                         3,5     7    20
// beelogger DUO                            2,5     5    20
// beelogger Quad                            2      4    17 

// mögliche Überbrückungszeit ohne Senden bei 15 Minuten Messzyklus
//                                          ca. Stunden   
// beelogger Single                         10     20    60
// beelogger DUO                             8     16    60
// beelogger Quad                            6     12    50 


/****************************************************************/
/***************    ENDE Konfiguration     **********************/
/****************************************************************/
