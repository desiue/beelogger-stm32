/****************************************************************/
// LTE Konfiguration
/****************************************************************/

// Name des Zugangspunktes des Netzwerkproviders
const char  APN[] PROGMEM = {"APN-Name"};
// Benutzername des Zugangspunktes
static char APN_Benutzer[] = {""};
// Passwort des Zugangspunktes
static char APN_Passwort[] = {""};

//Beispiele:
//const char  APN[] PROGMEM ={"iot.1nce.net"}; // 1nce alt 
//const char  APN[] PROGMEM ={"sensor.net"};   // 1nce neu 

//static char APN_Benutzer[] = {""}; // 1nce ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM ={"internet.telekom"}; // Telekom Prepaid
//static char APN_Benutzer[] = {"t-mobile"};
//static char APN_Passwort[] = {"tm"};

//const char  APN[] PROGMEM ={"internet.telekom"}; // congstar
//static char APN_Benutzer[] = {"congstar"}; 
//static char APN_Passwort[] = {"cs"};

//static char  APN[] PROGMEM = {"TM"}; //Thingsmobile
//static char APN_Benutzer[] = {""}; // ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM ={"web.vodafone.de"};
//static char APN_Benutzer[] = {""}; // vodafone ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM = {"pinternet.interkom.de"}; // O2
//static char APN_Benutzer[] = {""};
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM = {"internet.eplus.de"};
//static char APN_Benutzer[] = {"eplus"};
//static char APN_Passwort[] = {"eplus"};


/****************************************************************/
// Verbindung zum Server
// Parameter zu Datenübertragung
/****************************************************************/

// Domainname zum Webserver mit beelogger-Skript
// Länge maximal 30 Zeichen
const char serverName[] PROGMEM = "community.beelogger.de";

// GET mit Verzeichnis auf dem Webserver und PHP-Skript für den jeweiligen beelogger
// Mit Anzahl_Sensoren_Gewicht  1...4
// 1 - Bsp: "GET /USERX/beeloggerY/beelogger_log.php?"
// 2 - Bsp: "GET /USERX/DuoY/beelogger_log.php?"
// 3 - Bsp: "GET /USERX/TripleY/beelogger_log.php?"
// 4 - Bsp: "GET /USERX/QuadY/beelogger_log.php?"

// Bsp: {"GET /DeinAccount/beelogger1/beelogger_log.php?"};

const char beelogger_pfad[] PROGMEM = {"GET /USERX/SystemY/beelogger_log.php?"};   // "USERX" und "SystemY" ersetzen
// Passwort vom Webserver-Skript
const char Passwort[] PROGMEM = "Log";  // hier dein beelogger-log - Passwort


// Sendebeschränkung während Nachtstunden
#define DO_NOT_SEND_AT_NIGHT 0    // Sendebeschränkung 1 -> aktiv, 0 -> nicht aktiv
#define DO_NOT_SEND_BEGIN   20    // Uhrzeit Beginn Sendebeschränkung Wertebereich 18 - max 23 Uhr!!!
// Ende der Sendebeschränkung ist voreingestellt mit #define SEND_HOUR 6  auf 6 Uhr
// Achtung: automatische Umschaltung der serverseitig eingestellte Mess-/Sendeintervalle erfolgt nur wenn
//  zum Zeitpunkt des Umstellen Daten erfolgreich gesendet wurden
// Beispiel: serverseitige Einstellung zur Umstellung des Messintervalls 20 Uhr und DO_NOT_SEND_BEGIN 22
//           DO_NOT_SEND_BEGIN 20 und serverseitige Einstellung zur Umstellung des Messintervalls 21 Uhr funktioniert nicht.
// Hinweis: Wenn das EE-Prom mit Datensätzen gefüllt ist, wird immer ein Sendeversuch unternommen


//Option: Signalpegel messen und senden mit Arbeitsschalter forcieren
#define CSQ_SWITCH 0           // 0 - nicht verfügbar, 1 - in Sketch integriert

//Option: Transfer Data via Port 443
#define _LTE_TLS 1    // 0 - Port 80, 1 - Port 443
#define SNI_ACTIVE 1  // 0 / 1 - fix http error 421 Misdirected Request  (beelogger-Server -> 1)

// SIM76xx / A76xx Modul spezifische Parameter
#define A_7600 0        // 1 = A_7600E
#define SET_LTE_ONLY 0  // 0 = no  1 -> "AT&F;AT+CNMP=38", 2 -> "AT&F;AT+CNMP=2", 3 -> "AT+CGDCONT=0,"IP",""


/****************************************************************/
