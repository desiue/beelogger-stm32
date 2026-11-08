// DS3231-Nachbildung auf einem Arduino Nano – nur zum Testen ohne RTC-Modul.
// I2C-Slave 0x68, Register 0x00–0x12 wie beim DS3231, so weit sie die
// Sodaq_DS3231-Bibliothek des beelogger-Sketches benutzt:
//   Uhrzeit/Datum (BCD, 24 h), Alarm 1 (Sek/Min/Std, AM4), Control, Status,
//   Temperatur (fest 20,00 °C), CONV wird sofort als fertig gemeldet.
// /INT wie beim DS3231 als Open-Drain an D2: LOW solange A1F gesetzt und
// INTCN + A1IE an. Der Logger löscht A1F beim Aufwachen (clearINTStatus).
// Alarm 2 ist nicht nachgebildet.
//
// Verdrahtung: Nano A4 – Logger A4 (SDA), A5 – A5 (SCL), D2 – Logger D2, GND – GND.
// Startzeit = Übersetzungszeit dieses Sketches. Gang = Resonator des Nano.
// Serielle Ausgabe 9600: Uhrzeit jede Minute, Schreibzugriffe, Alarm.

#include <Wire.h>

#define RTC_ADDR 0x68
#define INT_PIN  2
#define NREG     0x13

volatile uint8_t reg[NREG];
volatile uint8_t ptr = 0;
volatile uint8_t wErstes, wAnz;
volatile bool    wNeu = false;

static uint8_t bcd2bin(uint8_t v) { return v - 6 * (v >> 4); }
static uint8_t bin2bcd(uint8_t v) { return v + 6 * (v / 10); }

static uint8_t tageImMonat(uint8_t m, uint8_t j) {   // j = Jahr - 2000
  static const uint8_t t[12] = {31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31};
  if (m < 1 || m > 12) return 31;
  if (m == 2 && (j % 4) == 0) return 29;
  return t[m - 1];
}

// Master schreibt: erstes Byte = Registerzeiger, weitere = Daten
void beiEmpfang(int n) {
  if (n < 1) return;
  ptr = Wire.read() % NREG;
  uint8_t erstes = ptr, anz = 0;
  while (Wire.available()) {
    uint8_t v = Wire.read();
    if (ptr == 0x0E) v &= ~0x20;                                   // CONV sofort fertig
    if (ptr == 0x0F) v = (v & ~0x03) | (reg[0x0F] & v & 0x03);     // A1F/A2F nur löschbar
    if (ptr < 0x11) reg[ptr] = v;                                  // Temperatur nur lesbar
    ptr = (ptr + 1) % NREG;
    anz++;
  }
  if (anz) { wErstes = erstes; wAnz = anz; wNeu = true; }
}

// Master liest: ab Registerzeiger fortlaufend (Master nimmt, was er braucht)
void beiAnfrage() {
  uint8_t buf[NREG];
  uint8_t p = ptr;
  for (uint8_t i = 0; i < NREG; i++) { buf[i] = reg[p]; p = (p + 1) % NREG; }
  Wire.write(buf, NREG);
}

static void sekundeWeiter() {
  uint8_t s = bcd2bin(reg[0]) + 1;
  if (s < 60) { reg[0] = bin2bcd(s); return; }
  reg[0] = 0;
  uint8_t mi = bcd2bin(reg[1]) + 1;
  if (mi < 60) { reg[1] = bin2bcd(mi); return; }
  reg[1] = 0;
  uint8_t h = bcd2bin(reg[2] & 0x3F) + 1;
  if (h < 24) { reg[2] = bin2bcd(h); return; }
  reg[2] = 0;
  reg[3] = (reg[3] % 7) + 1;
  uint8_t mo = bcd2bin(reg[5] & 0x1F), j = bcd2bin(reg[6]);
  uint8_t d = bcd2bin(reg[4]) + 1;
  if (d <= tageImMonat(mo, j)) { reg[4] = bin2bcd(d); return; }
  reg[4] = 1;
  if (++mo <= 12) { reg[5] = bin2bcd(mo); return; }
  reg[5] = 1;
  reg[6] = bin2bcd((j + 1) % 100);
}

static bool alarm1Trifft() {
  uint8_t a1 = reg[7], a2 = reg[8], a3 = reg[9], a4 = reg[10];
  if (!(a1 & 0x80) && (a1 & 0x7F) != reg[0]) return false;
  if (!(a2 & 0x80) && (a2 & 0x7F) != reg[1]) return false;
  if (!(a3 & 0x80) && (a3 & 0x3F) != (reg[2] & 0x3F)) return false;
  if (!(a4 & 0x80)) {
    if (a4 & 0x40) { if ((a4 & 0x0F) != reg[3]) return false; }
    else if ((a4 & 0x3F) != reg[4]) return false;
  }
  return true;
}

static uint8_t monatAus(const char *m) {
  const char *n = "JanFebMarAprMayJunJulAugSepOctNovDec";
  for (uint8_t i = 0; i < 12; i++)
    if (m[0] == n[i * 3] && m[1] == n[i * 3 + 1] && m[2] == n[i * 3 + 2]) return i + 1;
  return 1;
}

static void zeitAusgeben(const __FlashStringHelper *vor) {
  char t[24];
  noInterrupts();
  snprintf(t, sizeof(t), "20%02x-%02x-%02x %02x:%02x:%02x",
           reg[6], reg[5] & 0x1F, reg[4], reg[2] & 0x3F, reg[1], reg[0]);
  interrupts();
  Serial.print(vor);
  Serial.println(t);
}

void setup() {
  pinMode(INT_PIN, INPUT);          // /INT frei (hochohmig)
  digitalWrite(INT_PIN, LOW);
  Serial.begin(9600);

  const char *d = __DATE__;         // "Nov  8 2026"
  const char *z = __TIME__;         // "08:15:30"
  uint8_t tag = (d[4] == ' ' ? 0 : d[4] - '0') * 10 + (d[5] - '0');
  uint8_t jahr = (d[9] - '0') * 10 + (d[10] - '0');
  reg[0] = bin2bcd((z[6] - '0') * 10 + (z[7] - '0'));
  reg[1] = bin2bcd((z[3] - '0') * 10 + (z[4] - '0'));
  reg[2] = bin2bcd((z[0] - '0') * 10 + (z[1] - '0'));
  reg[3] = 1;
  reg[4] = bin2bcd(tag);
  reg[5] = bin2bcd(monatAus(d));
  reg[6] = bin2bcd(jahr);
  for (uint8_t i = 7; i <= 0x0D; i++) reg[i] = 0;
  reg[0x0E] = 0x1C;                 // INTCN=1, Alarme aus (Einschaltwert)
  reg[0x0F] = 0x08;                 // EN32kHz, OSF gelöscht
  reg[0x10] = 0;
  reg[0x11] = 20;                   // 20,00 °C
  reg[0x12] = 0;

  Wire.begin(RTC_ADDR);
  Wire.onReceive(beiEmpfang);
  Wire.onRequest(beiAnfrage);
  zeitAusgeben(F("DS3231-Nachbildung Start "));
}

void loop() {
  static uint32_t letzte = millis();
  static bool intWarAktiv = false;

  if (millis() - letzte >= 1000) {
    letzte += 1000;
    bool alarm;
    noInterrupts();
    sekundeWeiter();
    alarm = alarm1Trifft();
    if (alarm) reg[0x0F] |= 0x01;   // A1F setzen (wie DS3231, unabhängig von A1IE)
    interrupts();
    if (alarm) zeitAusgeben(F("Alarm 1 "));
    if (reg[0] == 0) zeitAusgeben(F("Uhr "));
  }

  if (wNeu) {
    noInterrupts();
    uint8_t e = wErstes, a = wAnz;
    wNeu = false;
    interrupts();
    Serial.print(F("geschrieben ab 0x"));
    Serial.print(e, HEX);
    Serial.print(F(" n="));
    Serial.print(a);
    if (e <= 0x0A && e + a > 0x07) {
      char t[40];
      snprintf(t, sizeof(t), " Alarm1 %02x:%02x:%02x ctrl %02x",
               reg[9] & 0x3F, reg[8] & 0x7F, reg[7] & 0x7F, reg[0x0E]);
      Serial.print(t);
    }
    Serial.println();
  }

  // /INT: LOW (aktiv), solange ein freigegebenes Alarmflag gesetzt ist
  bool intAktiv = (reg[0x0E] & 0x04) && (reg[0x0F] & reg[0x0E] & 0x03);
  if (intAktiv != intWarAktiv) {
    if (intAktiv) { digitalWrite(INT_PIN, LOW); pinMode(INT_PIN, OUTPUT); }
    else pinMode(INT_PIN, INPUT);
    intWarAktiv = intAktiv;
    Serial.println(intAktiv ? F("/INT LOW") : F("/INT frei"));
  }
}
