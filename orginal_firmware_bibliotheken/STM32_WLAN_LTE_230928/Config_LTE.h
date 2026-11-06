/****************************************************************/
// LTE Konfiguration
/****************************************************************/

// Name des Zugangspunktes des Netzwerkproviders
//const char  APN[] PROGMEM = {"APN-Name"};
// Benutzername des Zugangspunktes
//static char APN_Benutzer[] = {""};
// Passwort des Zugangspunktes
//static char APN_Passwort[] = {""};

const char  APN[] PROGMEM = {"internet.telekom"};
static char APN_Benutzer[] = {"congstar"};
static char APN_Passwort[] = {"cs"};							  


//Beispiele:
//const char  APN[] PROGMEM ={"internet.t-mobile"};
//static char APN_Benutzer[] = {"t-mobile"};
//const char  APN[] PROGMEM = {"TM"}; //Thingsmobile

//const char  APN[] PROGMEM ={"web.vodafone.de"};
//static char APN_Benutzer[] = {""}; // vodafone ohne Nutzer/Passwort
//static char APN_Passwort[] = {""};

//const char  APN[] PROGMEM = {"internet.eplus.de"};
//static char APN_Benutzer[] = {"eplus"};
//static char APN_Passwort[] = {"eplus"};


/****************************************************************/
// Verbindung zum Server
// Parameter zu Datenübertragung
/****************************************************************/
#define SIM7600_Baudrate 9600

//Option: Transfer Data via Port 443
#define _LTE_TLS  1           // 0 - Port 80, 1 - Port 443
/****************************************************************/
