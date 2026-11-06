ReadMe.txt


Verzeichnisse  STM32_...r...

Die Sketche STM32_GSM/LTE/WLAN r... verwenden das EE-Prom durchgehend.
D.h. nach dem Senden von Daten werden neue Datensätze in EE-Prom-Bereiche nach dem 
letzten gesendten Datensatz geschrieben, 
anstatt nach einem Sendevorgang wieder mit Speicherplatz "0" zu beginnen.
Wenn das EE-Prom vollständig genutzt ist, wird Speicherplatz "0" wieder genutzt.



STM32_WLAN_ mit https:
Die Nutzung ist im "r"-Sketch einstellbar in der Config_WLAN.h:
//Option: Transfer Data via Port 443
#define _WLAN_https  0         // 0 - Port 80, 1 - Port 443
Datenübertragung per WLAN zum Server mit https, Port 443, 
benötigt ESP8266 mit der Firmware esp_https_beelogger aus dem Verzeichnis options
Erläuterungen siehe beelogger.de Webseite


Verzeichnis: options

esp_https_beelogger:
Firmware für den ESP8266 zur Kommunikation zum Server via Port 443

beelogger_bmp280.ino:
option zur Unterstützung des BMP280 in den Multi-Sketchen
Datei ins das Verzeichnis des Multi-Sketch kopieren
weitere Erläuterungen in der beelogger_bmp280.ino


beelogger_DS18x20.ino:
alternative Multi-Sketch Variante zur Unterstrützung der Typen:
DS1820,
DS18S20
kann auch für DS18B20 verwendet werden.
Datei in das Verzeichnis des Sketch kopieren,
vorhandene Datei beelogger_DS18B20.ino löschen.

