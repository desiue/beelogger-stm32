//----------------------------------------------------------------
// WLAN Parameter
// Zwei WLAN Zugangspunkte können konfiguriert wählen,
// der jeweils verfügbare wird verwendet
// WLan Daten unbedingt nach Verfügbarkeit und Stärke eintragen (stärkstes in Nr. 1)
// Hinweis: ESP-Testsketch zeigt alle Feldstärken an
//----------------------------------------------------------------
#define Anzahl_AP 1   // Mögliche Werte: '1','2'

// Zugangspunkt Nr. 1 Daten  = vorrangiger/primärer Zugangspunkt
const char  Access_Point1[] PROGMEM =  "WLAN-Name_1";
// Passwort des Zugangspunktes Nr. 1
const char AP_Passwort_1[] PROGMEM = "WLAN-Passwort_1";

// Zugangspunkt Nr. 2 Daten
const char  Access_Point2[] PROGMEM =  "WLAN-Name_2";
// Passwort des Zugangspunktes Nr. 2
const char AP_Passwort_2[] PROGMEM = "WLAN-Passwort_2";

#define ESP_SLEEP_MODE 2 // Mögliche Werte: '0','2'; Sleep Mode des WLAN-Modul 0 = NoSleep, 2 = Modem Sleep


// Domainname zum Webserver mit beelogger-Skript
const char serverName[] PROGMEM = "community.beelogger.de"; //"meineDomain.de";

// GET mit Verzeichnis auf dem Webserver und PHP-Skript für den jeweiligen beelogger
// Mit Anzahl_Sensoren_Gewicht
// 1 - Bsp: "GET /USERX/beelogger1/beelogger_log.php?"
// 2 - Bsp: "GET /USERX/Duo1/beelogger_log.php?"

const char beelogger_pfad[] PROGMEM = {"GET /USERX/beeloggerY/beelogger_log.php?"};   // "USERX" und "beeloggerY" ersetzen

// Passwort vom Webserver-Skript
const char Passwort[] PROGMEM = "Log";  // hier dein beelogger-log - Passwort
//----------------------------------------------------------------
