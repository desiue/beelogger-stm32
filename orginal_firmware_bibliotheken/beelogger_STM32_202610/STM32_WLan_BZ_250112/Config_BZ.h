//----------------------------------------------------------------
// Sensorkonfiguration
// 1. Sensor für die Abfrage durch den Sketch aktivieren
// 2. aktive Sensoren für Temperatur/Feuchte in der Sensormatrix zuordnen
// 3. Sensor für Temperatur Wägezelle festlegen
//----------------------------------------------------------------
#define Anzahl_Sensoren_DHT     0 // Mögliche Anzahl: '0','1','2'  --- Nr 1,2 ---  (Temperatur + Luftfeuchte)
#define DHTTYPE DHT22   // DHT 22  (AM2302), AM2321  // DHT 21 (AM2301)

#define Anzahl_Sensoren_Si7021  0 // Mögliche Anzahl: '0','1'      --- Nr 3 -----  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_SHT31   0 // Mögliche Anzahl: '0','1','2'  --- Nr 4,5 ---  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_BME280  0 // Mögliche Anzahl: '0','1','2'  --- Nr 6,7 ---  (Temperatur + Luftfeuchte)

#define Anzahl_Sensoren_DS18B20 1 // Mögliche Anzahl: '0','1','2'  --- Nr 8,9 ---  (Nur Temperatur)
                                  // Mögliche Anzahl:     '3','4'  --- Nr 8,9 ---  (im Messwert Luftfeuchte)


// 2. Sensormatrix, hier kann die Zuordnung der Sensoren geändert werden
// Nr 1 - 9 aus Liste oben auswählen, wenn kein Sensor gewünscht ist einfach "0" angeben
// wenn kein Sensor für die Aussentemperatur gesetzt ist, wird automatisch der Temperatursensor der RTC verwendet
#define Aussenwerte              8    // 0 oder Nr. 1 - 11; bei Single:  0 oder Nr. 1 - 8; bei DUO: 0 oder Nr. 1 - 9
// Sensor Beute 1
#define             Beute1       0    // 0 oder Nr. 1 - 11; bei Single:  0 oder Nr. 1 - 8; bei DUO: 0 oder Nr. 1 - 9
// Sensor Beute 2
#define             Beute2       0    // 0 oder Nr. 1 - 11; bei DUO: 0 oder Nr. 1 - 9


// Temperatur Wägezelle (nur Duo, Tripple, Quad usw.)
// Sensor, der die Temperatur der Wägezelle erfasst;  vorbelegt der erste DS18B20
// für Systeme mit einer Waage identisch zum Sensor Aussenwerte eintragen
#define             Temp_Zelle                    8    // Nr. 0 - 11, nur DUO


//----------------------------------------------------------------
// weitere Sensoren
//----------------------------------------------------------------
// Luftdruck von BME280
// Korrekturwert um Abweichungen zu offiziellen Wetterstationen bei Bedarf auszugleichen; siehe Konfiguration Grafik Server

#define Anzahl_Sensoren_Licht   0  // Mögliche Anzahl: '0','1'  


#define Anzahl_Sensoren_Bienenzaehler 0 // Mögliche Anzahl: '0','1','2','3','4'
#define Neue_Bienenzaehlfunktion      0 // Mögliche Werte: '0','1'  1 = neues Verfahren




//----------------------------------------------------------------
// Konfiguration Waage
//----------------------------------------------------------------

#define Anzahl_Sensoren_Gewicht   1 // Mögliche Anzahl: '1','2'

// Anschluss / Konfiguration Wägezellen-------------------------------------------------------
// mit Anzahl_Sensoren_Gewicht 1    
//   HX711(1) Kanal A = Wägeelement(e) Waage1;   Serverskript: beeloggerY
// mit Anzahl_Sensoren_Gewicht 2
//   HX711(1) Kanal B = Wägeelement(e) Waage2;   Serverskript: DuoY


//--------------------------------------------------------------------------------------------
// Kalibrierwerte für die Wägezellen
const long Taragewicht[2] = { 10, 10 }; // Hier ist der Wert aus der Kalibrierung einzutragen
const float Skalierung[2] = { 1.0, 1.0 }; // Hier ist der Wert aus der Kalibrierung einzutragen


/****************************************************************/
// Allgemeine Konfiguration
/****************************************************************/

// Wartezyklus ohne Messungen wenn Schalter Arbeiten am Bienenvolk dauernd aktiv
// Zeit in Minuten, 5 Minuten Schritte
#define USER_INT_TM      30
