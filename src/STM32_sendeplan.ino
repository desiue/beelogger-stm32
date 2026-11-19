/****************************************************************/
// Sendeplan S6 (docs/planung/idee_sendeplan_schwarmalarm.md, "Entwurf Kommando-Format FPort 12", "Plan S6")
// Wiegen und Senden getrennt, Plan per Downlink FPort 12 im Flash, "für eine Weile anders" (Kommando 10)
// in Backup-Registern. Nur F4 (32-Bit-Backup-Register, 128-KB-Flash-Sektor am Ende).
// Ohne gültigen Flash-Plan und ohne laufendes Kommando 10 bleibt der alte Code-Pfad unverändert:
// jedes Aufwachen misst und sendet, Weckraster 300 s.
/****************************************************************/
#if SENDEPLAN

// Plan-Felder; Kommando 01-07 = Index 0-6, 08 = zwei Byte, 09/0A = Akkusparmodus
enum { P_WT, P_WN, P_ST, P_SN, P_SOLAR, P_WTEMP, P_SW, P_SOF_H, P_SOF_L, P_AKKU_V, P_AKKU_S, P_ANZ };
#define S6_MAGIC 0xB1          // Format-Version des Flash-Eintrags
#define S6_KEIN_GEW (-32768)   // kein gesendetes Gewicht gemerkt

// Backup-Register (14 = Software-Watchdog, 10-13 = OTAA-Session):
// 15: Bit 0-15 Minute der letzten Sendung, Bit 16-31 Minute, zu der Kommando 10 endet
// 16: Bit 0-7 Wiegen [min] und Bit 8-15 Senden [min] aus Kommando 10 (0 = keins), Bit 16 Winter,
//     Bit 17 Quittung offen, Bit 18 letzte Sendung gültig, Bit 19 nach Neustart einen Lauf auslassen
// 17/18: zuletzt gesendetes Gewicht Waage 1/2 und 3/4 [10 g], int16
#define S6_WINTER  (1UL << 16)
#define S6_QUITT   (1UL << 17)
#define S6_GESENDET (1UL << 18)
#define S6_STILL   (1UL << 19)
uint32_t s6_zeit = 0, s6_stat = 0;
uint32_t s6_gew[2] = { 0x80008000, 0x80008000 };

uint8_t s6p[P_ANZ];          // gültiger Plan: Flash oder Vorgabe aus Config
bool s6_aktiv = false;       // Flash-Plan gültig
uint16_t s6_jetzt = 0;       // Minute dieses Aufwachens (Epoche / 60, gerundet)
// letzter angenommener Downlink FPort 12, ausgeführt nach dem Uplink in s6_nach_senden()
uint8_t s6_dl[P_ANZ];
uint8_t s6_dl_art = 0;       // Bit 0 Plan-Wert, Bit 1 0F, Bit 2 Kommando 10, Bit 3 Kommando 30
uint8_t s6_ov[3];            // Kommando 10: Wiegen, Senden, Dauer [h]
uint8_t s6_neustart = 0;     // Kommando 20: 1 Neustart, 2 Neustart + Neu-Join

extern "C" uint32_t _sidata, _sdata, _edata;

void s6_bkp_lesen() {
  s6_zeit = getBackupRegister(15);
  s6_stat = getBackupRegister(16);
  s6_gew[0] = getBackupRegister(17);
  s6_gew[1] = getBackupRegister(18);
}

void s6_bkp_schreiben() {  // nach enableBackupDomain()
  setBackupRegister(15, s6_zeit);
  setBackupRegister(16, s6_stat);
  setBackupRegister(17, s6_gew[0]);
  setBackupRegister(18, s6_gew[1]);
}

bool s6_wert_ok(uint8_t i, uint8_t v) {
  switch (i) {
    case P_WT: case P_WN: return v >= 1 && v <= 60;
    case P_ST: case P_SN: case P_SW: return v >= 5 && v <= 180 && v % 5 == 0;
    case P_AKKU_V: return (uint16_t)v * 5 >= (uint16_t)(VMinimum * 100 + 0.5) + 5;  // mindestens VMinimum + 0,05 V
    case P_AKKU_S: return v >= 30 && v <= 240 && v % 5 == 0;
    default: return true;  // Solargrenze, Winter-Temperatur; Sofort-Schwelle in s6_plan_ok()
  }
}

bool s6_plan_ok(const uint8_t* p) {
  for (uint8_t i = 0; i < P_ANZ; i++)
    if (!s6_wert_ok(i, p[i])) return false;
  uint16_t sof = (uint16_t)p[P_SOF_H] << 8 | p[P_SOF_L];
  return sof == 0 || sof >= 50;
}

void s6_vorgabe(uint8_t* p) {  // ohne Flash-Plan: wie heute, Wiegen = Senden = WeckIntervall
  p[P_WT] = p[P_WN] = p[P_ST] = p[P_SN] = WeckIntervall;
  p[P_SOLAR] = 0;        // immer Tag
  p[P_WTEMP] = 0x80;     // kein Winter
  p[P_SW] = WeckIntervall_Winter;
  p[P_SOF_H] = p[P_SOF_L] = 0;
  p[P_AKKU_V] = (uint8_t)(VAlternativ * 20 + 0.5);
  p[P_AKKU_S] = AlternativIntervallMinuten;
}

// letzter Flash-Sektor (F401/F411: 128 KB); 0 = Chip zu klein oder Programm reicht hinein
uint32_t s6_sektor(uint32_t* nr) {
  uint32_t kb = *(volatile uint16_t*)FLASHSIZE_BASE;
  if (kb < 256) return 0;
  uint32_t a = FLASH_BASE + kb * 1024 - 128UL * 1024;
  uint32_t ende = (uint32_t)&_sidata + ((uint32_t)&_edata - (uint32_t)&_sdata);  // Programmende im Flash
  if (ende > a) return 0;
  if (nr) *nr = 4 + (kb - 128) / 128;  // Sektor 4 = 64 KB, ab 5 je 128 KB
  return a;
}

uint32_t s6_summe(uint32_t a, uint32_t b, uint32_t c) { return ~(a + 3 * b + 7 * c) ^ 0x5E4D3C2B; }

bool s6_flash_lesen(uint8_t* p) {
  uint32_t a = s6_sektor(0);
  if (a == 0) return false;
  const volatile uint32_t* f = (const volatile uint32_t*)a;
  uint32_t w[4] = { f[0], f[1], f[2], f[3] };
  if ((w[0] & 0xFF) != S6_MAGIC || w[3] != s6_summe(w[0], w[1], w[2])) return false;
  uint8_t q[12];
  for (uint8_t i = 0; i < 12; i++) q[i] = w[i / 4] >> (8 * (i % 4));
  if (!s6_plan_ok(q + 1)) return false;
  memcpy(p, q + 1, P_ANZ);
  return true;
}

bool s6_flash_schreiben(const uint8_t* p) {  // p = 0: Eintrag löschen
  uint32_t nr;
  uint32_t a = s6_sektor(&nr);
  if (a == 0) return false;
  uint32_t w[4] = { S6_MAGIC, 0, 0, 0 };
  if (p) {
    for (uint8_t i = 0; i < P_ANZ; i++) w[(i + 1) / 4] |= (uint32_t)p[i] << (8 * ((i + 1) % 4));
    w[3] = s6_summe(w[0], w[1], w[2]);
  }
  FLASH_EraseInitTypeDef e = {};
  e.TypeErase = FLASH_TYPEERASE_SECTORS;
  e.Sector = nr;
  e.NbSectors = 1;
  e.VoltageRange = FLASH_VOLTAGE_RANGE_3;
  uint32_t err;
  HAL_FLASH_Unlock();
  __HAL_FLASH_CLEAR_FLAG(FLASH_FLAG_EOP | FLASH_FLAG_OPERR | FLASH_FLAG_WRPERR | FLASH_FLAG_PGAERR | FLASH_FLAG_PGPERR | FLASH_FLAG_PGSERR);
  bool ok = HAL_FLASHEx_Erase(&e, &err) == HAL_OK;
  for (uint8_t i = 0; ok && p && i < 4; i++) ok = HAL_FLASH_Program(FLASH_TYPEPROGRAM_WORD, a + 4 * i, w[i]) == HAL_OK;
  HAL_FLASH_Lock();
  __HAL_FLASH_DATA_CACHE_DISABLE();  // Rücklesen nicht aus dem Cache
  __HAL_FLASH_DATA_CACHE_RESET();
  __HAL_FLASH_DATA_CACHE_ENABLE();
  return ok;
}

bool s6_ov_aktiv() {  // Kommando 10 läuft (nur plausible Werte, sonst Registerinhalt einer anderen Firmware)
  uint8_t w = s6_stat & 0xFF, s = (s6_stat >> 8) & 0xFF;
  return w >= 1 && w <= 60 && s >= 5 && s <= 180;
}
bool s6_neu() { return s6_aktiv || s6_ov_aktiv(); }  // neuer Code-Pfad
bool s6_nacht() { return s6p[P_SOLAR] && Solarspannung < s6p[P_SOLAR] * 0.05f; }

void s6_start() {  // Anfang loop(): Plan laden, Kommando 10 abgelaufen?
  s6_jetzt = (uint16_t)((rtc_stm.getEpoch() + 30) / 60);
  s6_aktiv = s6_flash_lesen(s6p);
  if (!s6_aktiv) s6_vorgabe(s6p);
  if (s6_ov_aktiv() && (int16_t)((uint16_t)(s6_zeit >> 16) - s6_jetzt) <= 0) s6_stat &= ~0xFFFFUL;
  if (s6_neu()) {
    debugprintF("S6: Plan "); debugprint(s6_aktiv);
    debugprintF(" K10 "); debugprint(s6_stat & 0xFF); debugprintF("/"); debugprintln((s6_stat >> 8) & 0xFF);
  }
}

float s6_akku_schwelle() { return s6_aktiv ? s6p[P_AKKU_V] * 0.05f : VAlternativ; }

bool s6_null = false;        // Lauf nach Neustart: Wachzeit nicht melden

bool s6_time_on_null() { return s6_null; }

bool s6_still() {  // nach Neustart per Downlink einen Lauf auslassen
  if (!(s6_stat & S6_STILL)) return false;
  s6_stat &= ~S6_STILL;
  s6_null = true;
  debugprintlnF("S6: nach Neustart schlafen");
  return true;
}

// Rangfolge: Akkusparmodus -> Kommando 10 -> FPort 10/11 -> Plan (Winter, Tag/Nacht)
uint8_t s6_senden_min() {
  if (akku_save) return s6p[P_AKKU_S];
  if (s6_ov_aktiv()) return (s6_stat >> 8) & 0xFF;
  if (FernIntervall) return FernIntervall;
  if ((s6_stat & S6_WINTER) && s6p[P_WTEMP] != 0x80) return s6p[P_SW];
  return s6_nacht() ? s6p[P_SN] : s6p[P_ST];
}

uint8_t s6_wiegen_min() {
  uint8_t s = s6_senden_min();
  if (akku_save) return s;
  uint8_t w = s6_ov_aktiv() ? (s6_stat & 0xFF) : (s6_nacht() ? s6p[P_WN] : s6p[P_WT]);
  return w < s ? w : s;
}

uint16_t s6_seit_sendung() { return (uint16_t)(s6_jetzt - (uint16_t)s6_zeit); }

bool s6_senden_faellig() {
  if (!s6_neu()) return true;                // alter Pfad: jedes Aufwachen sendet
  if (Service == 99.0) return true;          // Arbeitsschalter
  if (!(s6_stat & S6_GESENDET)) return true;
  return s6_seit_sendung() >= s6_senden_min();
}

bool s6_sofort() {  // Gewicht gegenüber der letzten Sendung um die Schwelle geändert?
  uint16_t sof = (uint16_t)s6p[P_SOF_H] << 8 | s6p[P_SOF_L];
  if (sof == 0 || akku_save) return false;
  if ((s6_stat & S6_GESENDET) && s6_seit_sendung() < 5) return false;  // Server-Sperre 220 s
  for (uint8_t i = 0; i < Anzahl_Sensoren_Gewicht && i < 4; i++) {
    int16_t alt = (int16_t)(s6_gew[i / 2] >> (16 * (i % 2)));
    if (alt == S6_KEIN_GEW || Gewicht[i] == No_Value) continue;
    if (fabsf(Gewicht[i] * 100.0f - alt) * 10.0f >= sof) {
      debugprintF("S6: Sofort, Waage "); debugprintln(i + 1);
      return true;
    }
  }
  return false;
}

void s6_vor_senden() {
  if ((s6_stat & S6_QUITT) && Service != 99.0) Service = 90.0;  // Quittung FPort 12
}

void s6_gesendet() {
  s6_zeit = (s6_zeit & 0xFFFF0000UL) | s6_jetzt;
  s6_stat |= S6_GESENDET;
  if (Service == 90.0) s6_stat &= ~S6_QUITT;
  for (uint8_t i = 0; i < 4; i++) {
    float g = (i < Anzahl_Sensoren_Gewicht) ? Gewicht[i] : No_Value;
    int32_t v = (g == No_Value || g < -300.0f || g > 300.0f) ? S6_KEIN_GEW : (int32_t)lroundf(g * 100.0f);
    uint8_t sh = 16 * (i % 2);
    s6_gew[i / 2] = (s6_gew[i / 2] & ~(0xFFFFUL << sh)) | ((uint32_t)(uint16_t)v << sh);
  }
  // Winter: DS_Temp gibt es nur in Läufen mit Sendung, darum hier merken
  if (s6p[P_WTEMP] != 0x80 && DS_Temp != No_Val && DS_Temp < (int8_t)s6p[P_WTEMP] * 0.5f) s6_stat |= S6_WINTER;
  else s6_stat &= ~S6_WINTER;
}

// FPort 12 aus onEvent(): erst alles prüfen, nur ganz gültige Downlinks werden gemerkt
void s6_downlink(const uint8_t* d, uint8_t n) {
  s6_dl_art = 0;
  s6_neustart = 0;
  uint8_t p[P_ANZ], ov[3] = { 0, 0, 0 }, art = 0, ns = 0, i = 1;
  memcpy(p, s6p, P_ANZ);
  bool ok = n >= 1 && d[0] == 0x01;
  while (ok && i < n) {
    if (i + 2 > n || i + 2 + d[i + 1] > n) { ok = false; break; }
    uint8_t k = d[i], l = d[i + 1];
    const uint8_t* v = d + i + 2;
    if ((k >= 0x01 && k <= 0x07) || k == 0x09 || k == 0x0A) {
      uint8_t x = (k <= 0x07) ? k - 1 : (k == 0x09 ? P_AKKU_V : P_AKKU_S);
      ok = l == 1 && s6_wert_ok(x, v[0]);
      if (ok) { p[x] = v[0]; art |= 1; }
    } else if (k == 0x08) {
      ok = l == 2 && (((uint16_t)v[0] << 8 | v[1]) == 0 || ((uint16_t)v[0] << 8 | v[1]) >= 50);
      if (ok) { p[P_SOF_H] = v[0]; p[P_SOF_L] = v[1]; art |= 1; }
    } else if (k == 0x0F) {
      ok = l == 0;
      if (ok) { s6_vorgabe(p); art |= 2; }
    } else if (k == 0x10) {
      ok = l == 3 && v[0] >= 1 && v[0] <= 60 && v[1] >= 5 && v[1] <= 180 && v[1] % 5 == 0 && v[2] <= 72;
      if (ok) { memcpy(ov, v, 3); art |= 4; }
    } else if (k == 0x20) {
      ok = l == 3 && (v[0] == 1 || v[0] == 2) && ((uint16_t)v[1] << 8 | v[2]) == (uint16_t)LMIC.devaddr;
      if (ok) ns = v[0];
    } else if (k == 0x30) {
      ok = l == 0;
      if (ok) art |= 8;
    }  // unbekannte Kommandos überspringen
    i += 2 + l;
  }
  if (ok && (art & 1)) ok = s6_plan_ok(p);  // ganzer Plan gültig (auch die Vorgaben aus Config)?
  debugprintF("DL Port 12: ");
  if (!ok) { debugprintlnF("verworfen"); return; }
  debugprintln(art);
  memcpy(s6_dl, p, P_ANZ);
  memcpy(s6_ov, ov, 3);
  s6_dl_art = art;
  s6_neustart = ns;
}

void s6_nach_senden() {  // nach Daten_Senden(): Downlink FPort 12 ausführen
  uint8_t art = s6_dl_art;
  s6_dl_art = 0;
  if (art & 3) {  // Plan
    uint8_t vg[P_ANZ];
    s6_vorgabe(vg);
    bool vorgabe = memcmp(s6_dl, vg, P_ANZ) == 0;
    bool ziel = !(vorgabe && (art & 2)) && (s6_aktiv || !vorgabe);  // danach Flash-Plan gültig?
    if (ziel != s6_aktiv || (ziel && memcmp(s6_dl, s6p, P_ANZ) != 0)) {
      if (akku_save) { debugprintlnF("S6: Sparmodus, Plan nicht geschrieben"); s6_neustart = 0; return; }
      uint8_t r[P_ANZ];
      bool ok = s6_flash_schreiben(ziel ? s6_dl : 0);
      bool da = s6_flash_lesen(r);
      ok = ok && (ziel ? (da && memcmp(r, s6_dl, P_ANZ) == 0) : !da);
      debugprintF("S6: Flash "); debugprintln(ok);
      if (!ok) {  // Flash-Inhalt jetzt unklar: neu lesen, sonst gilt Config
        s6_aktiv = s6_flash_lesen(s6p);
        if (!s6_aktiv) s6_vorgabe(s6p);
        s6_neustart = 0;
        return;
      }
      s6_aktiv = ziel;
      if (ziel) memcpy(s6p, s6_dl, P_ANZ);
      else s6_vorgabe(s6p);
    }
    if (art & 1) FernIntervall = 0;  // fester Wert aus FPort 10/11 verdeckt sonst den Plan
  }
  if (art & 4) {  // Kommando 10
    if (s6_ov[2] == 0) s6_stat &= ~0xFFFFUL;
    else {
      s6_stat = (s6_stat & ~0xFFFFUL) | s6_ov[0] | (uint32_t)s6_ov[1] << 8;
      s6_zeit = (s6_zeit & 0xFFFF) | (uint32_t)(uint16_t)(s6_jetzt + 60U * s6_ov[2]) << 16;
    }
  }
  if (art) s6_stat |= S6_QUITT;
  if (s6_neustart) {
#if LORA_OTAA
    if (s6_neustart == 2) otaa_clear();
#endif
    s6_stat &= ~(0xFFFFUL | S6_QUITT);  // Kommando 10 endet, Quittung entfällt (Service 0 nach Neustart)
    s6_stat |= S6_STILL;                 // Lauf nach dem Neustart misst und sendet nicht
    time_on = 0;
    save_data_to_ram();
    debugprintlnF("S6: Neustart");
    debugflush();
    digitalWrite(Power_Pin, LOW);
    delay(5);
    NVIC_SystemReset();
  }
}

#endif  // SENDEPLAN
