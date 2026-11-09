<?php 
/*
 * (C) 2022 Thorsten Gurzan - beelogger.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
*/

// beelogger.de - Arduino Datenlogger für Imker
// Erläuterungen dieses Programmcodes unter https://beelogger.de

$Softwareversion = "M.40";//vom 24.09.2026 - scraper.php

if(isset($_GET["pfad"])&&preg_match("#^[a-zA-Z0-9äöüÄÖÜ _\!\-]+$#",$_GET["pfad"])){
  $pfad = htmlentities(strip_tags(stripslashes($_GET['pfad'])));
  if (file_exists($pfad."/beelogger_interface.php")) {
    include($pfad."/beelogger_interface.php");   
    
    $sensor_aktualisierung_real = date("d.m.Y - H:i:s", $sensor_aktualisierung);  
    echo "<div class='".$pfad."_Sensor_Aktualisierung'>".$sensor_aktualisierung_real."</div><br><hr>";  
  
    for($i=0; $i<$sensor_anzahl; $i++) {
     $anzeige_anzahl = $i+1;
     echo "Sensor: ".$anzeige_anzahl;  
     echo "<div class='".$pfad."_Sensor_".$anzeige_anzahl."_Bezeichnung'>".$sensor_bezeichnung[$i]."</div>";   
     echo "<div class='".$pfad."_Sensor_".$anzeige_anzahl."_Wert'>".$sensor_wert[$i]."</div>";   
     echo "<div class='".$pfad."_Sensor_".$anzeige_anzahl."_Wert_1'>".$sensor_wert_1[$i]."</div>"; 
     echo "<div class='".$pfad."_Sensor_".$anzeige_anzahl."_Wert_24'>".$sensor_wert_24[$i]."</div><br><hr>"; 
    }
  }
}
?>