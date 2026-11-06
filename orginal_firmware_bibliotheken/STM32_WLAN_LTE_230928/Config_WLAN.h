/****************************************************************/
// WLAN Parameter
// Zwei WLAN Zugangspunkte können konfiguriert wählen,
// der jeweils verfügbare wird verwendet
// WLan Daten unbedingt nach Verfügbarkeit und Stärke eintragen (stärkstes in Nr. 1)
// Hinweis: ESP-Testsketch zeigt alle Feldstärken an
/****************************************************************/

#define Anzahl_AP 1  // Mögliche Werte: '1','2'

// Zugangspunkt Nr. 1 Daten  = vorrangiger/primärer Zugangspunkt
const char  Access_Point1[] PROGMEM =  "WLAN-Name_1";
// Passwort des Zugangspunktes Nr. 1
const char AP_Passwort_1[] PROGMEM = "WLAN-Passwort_1";

// Zugangspunkt Nr. 2 Daten
const char  Access_Point2[] PROGMEM =  "WLAN-Name_2";
// Passwort des Zugangspunktes Nr. 2
const char AP_Passwort_2[] PROGMEM = "WLAN-Passwort_2";


/****************************************************************/
// Verbindung zum Server
// Parameter zu Datenübertragung
/****************************************************************/

// Domainname zum Webserver mit beelogger-Skript
// Länge maximal 30 Zeichen
const char serverName[] PROGMEM = "community.beelogger.de";

// GET mit Verzeichnis auf dem Webserver und PHP-Skript für den jeweiligen beelogger
// Mit Anzahl_Sensoren_Gewicht
// 1 - Bsp: "GET /USERX/beeloggerY/beelogger_log.php?"
// 2 - Bsp: "GET /USERX/DuoY/beelogger_log.php?"
// 3 - Bsp: "GET /USERX/TripleY/beelogger_log.php?"
// 4 - Bsp: "GET /USERX/QuadY/beelogger_log.php?"
// 5 - Bsp: "GET /USERX/PentaY/beelogger_log.php?"
// 6 - Bsp: "GET /USERX/HexaY/beelogger_log.php?"

// Bsp: {"GET /DeinAccount/beelogger1/beelogger_log.php?"};

const char beelogger_pfad[] PROGMEM = {"GET /USERX/SystemY/beelogger_log.php?"};   // "USERX" und "SystemY" ersetzen

// Passwort vom Webserver-Skript
const char Passwort[] PROGMEM = "Log";  // hier dein beelogger-log - Passwort

//Option: Signalpegel messen und senden mit Arbeitsschalter forcieren
#define CSQ_SWITCH 0           // 0 - nicht verfügbar, 1 - in Sketch integriert

// Sendebeschränkung während Nachtstunden
#define DO_NOT_SEND_NIGHT 0    // Sendebeschränkung 1 -> aktiv, 0 -> nicht aktiv
#define DO_NOT_SEND_BEGIN 20   // Uhrzeit Beginn Sendebeschränkung Wertebereich 18 - max 23 Uhr!!!
// Ende der Sendebeschränkung ist voreingestellt mit #define SEND_HOUR 5  auf 5 Uhr
// Achtung: automatische Umschaltung der serverseitig eingestellte Mess-/Sendeintervalle erfolgt nur wenn
//  zum Zeitpunkt des Umstellen Daten erfolgreich gesendet wurden
// Beispiel: serverseitige Einstellung zur Umstellung des Messintervalls 20 Uhr und DO_NOT_SEND_BEGIN 22
//           DO_NOT_SEND_BEGIN 20 und serverseitige Einstellung zur Umstellung des Messintervalls 21 Uhr funktioniert nicht.
// Hinweis: Wenn das EE-Prom mit Datensätzen gefüllt ist, wird immer ein Sendeversuch unternommen

#define ESP_Baudrate 9600
/****************************************************************/
