//----------------------------------------------------------------
// DHT OneWire
//----------------------------------------------------------------
// 28.091.2023 adafruit lib

#include <beelogger_DHT.h>

void Sensor_DHT() {
  float Temperatur_DHT;
  float Luftfeuchte_DHT;

  MONITOR_RX_TX.println(" DHT 21/22:\n");
  MONITOR_RX_TX.flush();
  MONITOR_RX_TX.print(F(" DHT at pin: "));
  MONITOR_RX_TX.println(DHT_1);
  MONITOR_RX_TX.flush();

  beelogger_DHT beeDHT(DHT_1, 22);
  beeDHT.begin();  // start measurement
  
    int x = 0;
    do {
      delay(2100);  // wait for DHT ready
      Temperatur_DHT = beeDHT.readTemperature();
      Luftfeuchte_DHT = beeDHT.readHumidity();
      if ((Luftfeuchte_DHT > 1.0) && (Temperatur_DHT > 1.0)) x = 4;  //force break
      else {
        MONITOR_RX_TX.print(Temperatur_DHT);
        MONITOR_RX_TX.print("  ");
        MONITOR_RX_TX.println(Luftfeuchte_DHT);
        MONITOR_RX_TX.flush();
        Temperatur_DHT = No_Val;     //99.9
        Luftfeuchte_DHT = No_Value;  //-1.0
      }
      x++;
    } while (x < 4);

	if (isnan(Temperatur_DHT) || (Temperatur_DHT < -20.0) || (Temperatur_DHT > 60.0)) {
    MONITOR_RX_TX.println(F("  DHT not found or not installed. "));
  } else {
    if (isnan(Luftfeuchte_DHT) || (Luftfeuchte_DHT < 2.0) || (Luftfeuchte_DHT > 100.0)) Luftfeuchte_DHT = -1.0;
    MONITOR_RX_TX.print(F(" temperature [C]: "));
    MONITOR_RX_TX.println(Temperatur_DHT);
    MONITOR_RX_TX.print(F(" humidity [%RH]: "));
    MONITOR_RX_TX.println(Luftfeuchte_DHT);
  }
  // der zweite DHT
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
  MONITOR_RX_TX.print(F(" DHT at pin: "));
  MONITOR_RX_TX.println(DHT_2);
  MONITOR_RX_TX.flush();

  beelogger_DHT beeDHT2(DHT_2, 22);
  beeDHT2.begin();  // start measurement
  delay(2100);
  Luftfeuchte_DHT = beeDHT2.readHumidity();
  Temperatur_DHT = beeDHT2.readTemperature();
  if (isnan(Temperatur_DHT) || (Temperatur_DHT < -20.0) || (Temperatur_DHT > 60.0)) {
    MONITOR_RX_TX.println(F("  DHT not found or not installed. "));
  } else {
    if (isnan(Luftfeuchte_DHT) || (Luftfeuchte_DHT < 2.0) || (Luftfeuchte_DHT > 100.0)) Luftfeuchte_DHT = -1.0;

    MONITOR_RX_TX.print(F(" temperature [C]: "));
    MONITOR_RX_TX.println(Temperatur_DHT);
    MONITOR_RX_TX.print(F(" humidity [%RH]: "));
    MONITOR_RX_TX.println(Luftfeuchte_DHT);
  }
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
}
//--------------------------------------------------------------
