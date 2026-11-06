//----------------------------------------------------------------
// HX711
//----------------------------------------------------------------
#include <HX711.h>

void Sensor_HX711() {
  long val;
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.print(F(" HX711 Nummer 1: "));
  MONITOR_RX_TX.flush();
  HX711 scale;
  scale.begin(HX711_DT[0], HX711_SCK[0], 128); // Nr.1 channel A
  scale.power_up();
  if (scale.wait_ready_retry(3, 500)) {
    scale.read();
    MONITOR_RX_TX.println(F(" found."));
    MONITOR_RX_TX.println(" databits:");
    MONITOR_RX_TX.flush();
    val = scale.read_average(10);
    MONITOR_RX_TX.print(val, HEX);
    MONITOR_RX_TX.print("  ");
    scale.set_gain(32);
    scale.read();
    val = scale.read_average(10);
    MONITOR_RX_TX.println(val, HEX);
  }
  else {
    MONITOR_RX_TX.println(F(" not found or defect!"));
    MONITOR_RX_TX.println(F(" please check wiring."));
  }
  MONITOR_RX_TX.flush();
  scale.power_down();

  scale.begin(HX711_DT[1], HX711_SCK[1], 128); // Nr.2 channel A
  scale.power_up();
  if (scale.wait_ready_retry(3, 500)) {
    scale.read();
    MONITOR_RX_TX.print(F(" HX711 Nummer 2: "));
    MONITOR_RX_TX.println(F(" found."));
    MONITOR_RX_TX.println(" databits:");
    MONITOR_RX_TX.flush();
    val = scale.read_average(10);
    MONITOR_RX_TX.print(val, HEX);
    MONITOR_RX_TX.print("  ");
    scale.set_gain(32);
    scale.read();
    val = scale.read_average(10);
    MONITOR_RX_TX.println(val, HEX);
  }
  MONITOR_RX_TX.flush();

  scale.power_down();
  MONITOR_RX_TX.println(" ");
  MONITOR_RX_TX.flush();
}
