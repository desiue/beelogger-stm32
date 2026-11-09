<?php
/*
 * (C) 2023 Jeremias Bruker,  Rudolf Schick, Thorsten Gurzan - beelogger.de
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

// beelogger:mobile -->
$Softwareversion  =  "M.40";//vom 24.09.2026 - beelogger_mobileWatch.php
$show_version = 0;  //Programmertool
$Sprache = 1; // INIT falls noch keine Speicherung stattfand

header('Content-Type: text/html; charset=UTF-8');

$isHttps = (!empty($_SERVER['HTTPS']));
if($isHttps == 0){
	$local_network="192.168.178.";
	if((strstr($_SERVER['REMOTE_ADDR'],$local_network)==false)&&(strstr($_SERVER['SERVER_NAME'],$local_network)==false)){
		echo "Webseite   ".$_SERVER['SERVER_NAME']." bitte mit https:// aufrufen";
	exit;
	}
}

error_reporting(0);
if ($show_version){
  error_reporting(E_ERROR | E_WARNING | E_PARSE);
  if ($show_version == 2){
    error_reporting(E_ALL);
  }
  ini_set('display_errors', "1");
  ini_set('display_startup_errors', "1");
}

$Autorange=0;
$Range = "a";
if (isset($_POST["Auto_range"])){
    $Range = $_POST["Auto_range"];
    $Autorange =  intval(preg_replace('/[^0-9]+/', '', $Range), 10);
    $Range = $Range[0];
}
date_default_timezone_set('Europe/Berlin');

// PRESET variables
$mW_spalten = "1";
$mW_tage = 14;
$mW_sort = "Ordner";
$mW_twogrid="0";
$show_mini=0;
if( isset($_GET["sml"]))$show_mini=1;

if (file_exists("general_ini.php")) include("general_ini.php");
$file_f= "beelogger_sprachfiles/Mobile_Sprache_".$Sprache.".php";
if (file_exists($file_f)) include($file_f);  // Sprache

if (($mW_tage == "") OR ($mW_tage < 1)) $mW_tage = 14; //default
if (($mW_spalten == "") OR ((int)$mW_spalten > 1)) $mW_spalten = "2"; //mehr als 2 (default) darf nicht gehen
else $mW_spalten = "1";

$ext_autowatch = "";
if(isset($_GET['autowatch']))
  $ext_autowatch = htmlentities(strip_tags(stripslashes($_GET['autowatch']))); // = p oder m oder p+m ? dann soll eine automatische Nachricht ausgesendet werden bei inaktivität von beeloggern?
$jetzt = time();
$WatchCounter=0;

$FetchPushUser = "meinUser"; // Initialiserung User
$FetchPushToken = "meinToken"; // Initialiserung Token

$FetchEmpfaenger_Email = "empfaenger@meineDomain.de"; //Initialiserung Mail
$FetchAbsender_Email = "absender@meineDomain.de"; //Initialiserung Mail
$beeloggerArray = array();
$NewbeeloggerArray = array();

$X_Array = array("");
$NoCsvCounter = 0;
//Array angezeigter beelogger neu anordnen - nach Duo Triple, Quad, Penta und Sept

$file_f="./";
$files = scandir($file_f);
if ($mW_sort != "Bienenvolkbezeichnung"){
  natcasesort($files);
  $ba=0;
  foreach ($files as $file){
   if ($file != "beelogger_icons"  && $file != "beelogger_sprachfiles" && $file != "." && $file != ".." && is_dir($file)){
    $file = str_replace("T","E",$file);
    $file = str_replace("P","R",$file);
    $beeloggerArray[$ba] = $file;
    $ba++;
   }
  }
  natcasesort($beeloggerArray);
  $Newfiles = $beeloggerArray;

  $ba=0;
  foreach ($Newfiles as $file){
    $file = str_replace("E","T",$file);
    $file = str_replace("R","P",$file);
    $NewbeeloggerArray[$ba] = $file;
    $ba++;
  }
}
else{
  foreach ($files as $file){
   if ($file != "beelogger_icons" && $file != "beelogger_sprachfiles"&& $file != "." && $file != ".." && is_dir($file)){
    $SortIniname =  $file."/beelogger_ini.php";
    if (file_exists($SortIniname)) include($SortIniname);
    else $Bienenvolkbezeichnung = "auto";
    if ($Bienenvolkbezeichnung != "auto") $beeloggerArray[$file] = $Bienenvolkbezeichnung;
    else $beeloggerArray[$file] = $file;
   }
  }

  natcasesort($beeloggerArray);
  foreach ($beeloggerArray as $key=>$val){
  $NewbeeloggerArray[] = $key;
  }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="dygraph.js" charset="utf-8"></script>
<link rel="stylesheet" href="dygraph_beelogger.css">

<link rel="icon" type="image/png" sizes="16x16" href="./beelogger_icons/favicon-16x16.png" />
<link rel="icon" type="image/png" sizes="32x32" href="./beelogger_icons/favicon-32x32.png" />
<link rel="icon" type="image/png" sizes="96x96" href="./beelogger_icons/favicon-96x96.png" />

<style type="text/css">
body { font-family: Arial, Helvetica, sans-serif; color: black; background-color: ivory}
table{
 width:100%;
 table-layout:fixed;
 border:5px gray solid;
 border-collapse:collapse;
 text-align:center;
}
td,tr {
 border:5px gray solid;
 border-collapse:collapse;
 text-align:center;
}
.headline_1{font-size: 32px;margin-bottom: 10px;}
.headline_2{font-size: 24px;margin-bottom: 10px;-webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
<?php
$mW_sensor1=1;  $mW_sensor2=1; //INIT
$cnt=0;
foreach ($NewbeeloggerArray as $file){
  $filename = $file."/week.csv";
  $mW_one_y=0;
  $i=0;
  if(file_exists($filename)){  // is there some data
	$array=file($filename);$i=sizeof($array);
  }

  if (is_dir($file)AND ($i > 1)){ // there is data
    $ininame =  "./".$file."/beelogger_ini.php";
    if (file_exists($ininame)){
     include ($ininame); // Sensorinfo
 	 if ((strpos($file,"Xanalyse")===FALSE) && $mobileWatch_Show == "aktiviert"){
        echo "#Hdr_".$cnt." {position:relative;top: 0px;}\n";
        echo "#Graph_".$file." {position:relative;top: 10px;left: 10px;heigth:200px;}\n";
		$cnt++;
		// Gewicht muss immer sensor1 sein
        if ($mW_sensor2 == 6){$help = $mW_sensor1; $mW_sensor1 = 6; $mW_sensor2 = $help;}
	    $Graph = "#Graph_".$file." .dygraph-";
        if(((($mW_sensor2 == "")||($mW_sensor2 == "0"))AND ($mW_tageswertanzeige == "deaktiviert"))OR $mW_one_y==1){
			echo $Graph."ylabel {color:ivory;}\n";echo $Graph."axis-label-y1 {color:ivory;}\n";}
		else {echo $Graph."ylabel {color:".$Sensoren[($mW_sensor2-1)*5+1].";}\n";echo $Graph."axis-label-y1 {color:".$Sensoren[($mW_sensor2-1)*5+1].";}\n";}
        if($mW_one_y==0){echo $Graph."y2label {color:".$Sensoren[($mW_sensor1-1)*5+1].";}\n";
		echo $Graph."axis-label-y2 {color:".$Sensoren[($mW_sensor1-1)*5+1].";}\n";}
     }
	}
  }
}
$cnt--; // beelogger in m-Watch
echo"</style>\n <title>beeloggerMobileWatch</title>\n</head>\n<body>\n";

if ($show_version) echo "\n<td>&nbsp;&nbsp;&nbsp;Version:&nbsp;".$Softwareversion."</td>";
echo"<table>\n";

$AnzahlDarstellungen=0;
$SpalteNr=0;
$InaktivCounter = 0;
$beeloggerCounter = 0;
$PrintArray = array();
$AktuellerAkkustand = 0;
$GesamtFuTr = 0; // INIT   define GesamtFuTr
?>

<script type="text/javascript">
var y1_up = 1.0;
var y1_low = 0.0;
</script>

<?php
 if(file_exists("X_info_to_user.php")){include ("X_info_to_user.php"); if($msg_2_user != "") echo "<br><h1>".$msg_2_user."</h1><br>";}

foreach ($NewbeeloggerArray as $file){
  $X_Array[$NoCsvCounter] = $file;
  $ininame =  $file."/beelogger_ini.php";
  $filename = $file."/week.csv";
  $mW_one_y=0;			  
  $i =0;
  if (file_exists($filename)){  // is there some data
    $array=file($filename);$i=sizeof($array);
  }
  if ($mW_tage > 7) $filename = $file."/month.csv";// max. 30 Tage

  if (is_dir($file)AND ($i > 1) and (strpos($file,"Xanalyse")===FALSE)){ // there is data
  $notename = $file."/notes.csv";
  if (file_exists($filename)){ //eine csv gefunden
    $beeloggerCounter ++;
    $k = 0 ;
    $Aktuell = "";
    $array = file($filename);
    $i = sizeof($array);

    while ($i--){
      $what = trim($array[$i]);
      $x = explode( ",", $what );
      $s = sizeof($x);
      if ($x[$s-1] !=''){
        $AktualisierungsStamp=$x[$s-1];
        if ($Aktuell == ""){
          $Aktuell = $AktualisierungsStamp;
          $AktuellerAkkustand = $x[7];
          if (strpos($file,"Duo") !== FALSE) $AktuellerAkkustand = $x[12];
          if (strpos($file,"Triple") !== FALSE) $AktuellerAkkustand = $x[15];
          if (strpos($file,"Quad") !== FALSE) $AktuellerAkkustand = $x[18];
          if (strpos($file,"Penta") !== FALSE) $AktuellerAkkustand = $x[21];
          if (strpos($file,"Hexa") !== FALSE) $AktuellerAkkustand = $x[24];
        }
        $PrintArray[$k] = $array[$i]; //Printarray für dygraphs sichern
        $k ++;
        if ($AktualisierungsStamp <= ($Aktuell - 86400*$mW_tage)) break;   //ticks = 60*60*24 = 86400  = 1 Tag
      }// if ($x[$s-1] !='')
    } //while

    $Watchdog = "noIni"; //Reset $Watchdogzustand falls er undefiniert ist
    $BeutenLeergewicht = 0; // Reset
    $AkkuLeerSchwelle = 0; // Reset
    $AkkuVollSchwelle = 0; // Reset
    $mobileWatch_Show = "deaktiviert"; //Reset
    $PushToken = "";
    $PushUser  = "";
    $Empfaenger_Email = "";
    $Absender_Email = "";
	$tw_rain=0;

    $mW_twoGrid="0";
    $mW_roll = 1; //INIT
    $mW_legende = "always"; //INIT
    $mW_notes = "deaktiviert"; //INIT
    $mW_tageswertanzeige = "deaktiviert"; //INIT
    $KoeAnzeige = "deaktiviert"; //INIT
    $KoeKaefig = ""; //INIT
    $KoeInfo = ""; //INIT
    $BeutenLeergewicht = 0; //INIT
    $Honigraeume = array(0,0,0,0); //INIT  Script uses [0] ... [3]
    $BeutenUtils =  array(array(0,0),array(0,0),array(0,0)); //INIT setup multidimensional array

	if (file_exists($ininame)) include ($ininame); else continue; // Sensorname holen und Watchdog checken

    // Push User und token aus Ini auslesen
    if ($FetchPushToken == "meinToken"){ //noch keine User-Tokeninfo gefunden
      if (($PushToken != "meinToken") AND ($PushToken != "")) $FetchPushToken = $PushToken;
    }
    if ($FetchPushUser == "meinUser"){//noch keine User-Info gefunden
      if (($PushUser != "meinUser") AND ($PushUser != "")) $FetchPushUser = $PushUser;
    }
    // Ende Push

    // Email Informationen aus Ini auselsen
    if ($FetchEmpfaenger_Email == "empfaenger@meineDomain.de"){ //noch keine Emailempfänger-Info gefunden
      if (($Empfaenger_Email != "empfaenger@meineDomain.de") AND ($Empfaenger_Email != "")) $FetchEmpfaenger_Email = $Empfaenger_Email;
    }
    if ($FetchAbsender_Email == "absender@meineDomain.de"){//noch keine Emailabsender-Info gefunden
      if (($Absender_Email != "absender@meineDomain.de") AND ($Absender_Email != "")) $FetchAbsender_Email = $Absender_Email;
    }

    if ($mobileWatch_Show != "deaktiviert"){ //es liegen aktuelle Daten der letzten 30 Tage vor
      if ($ext_autowatch == ""){ // wenn eine autowatchabfrage kommt muss keine Grafik aufgebaut werden!
        // beginne Tabellenzeile
		$col_red = false;
        if ($mW_spalten == "2"){
          if ($SpalteNr == 0) echo "<tr>";
        } //if
        else echo "<tr>";  // Nur eine Spalte gewünscht ...

        $ininameAkku = ""; //RESET
        if (strpos($file,"beeloggerD") !== FALSE) $ininameAkku = "Duo".intval(substr($file,10,1))."/beelogger_ini.php";
        elseif (strpos($file,"beeloggerT") !== FALSE) $ininameAkku = "Triple".intval(substr($file,10,1))."/beelogger_ini.php";
        elseif (strpos($file,"beeloggerQ") !== FALSE) $ininameAkku = "Quad".intval(substr($file,10,1))."/beelogger_ini.php";
        elseif (strpos($file,"beeloggerP") !== FALSE) $ininameAkku = "Penta".intval(substr($file,10,1))."/beelogger_ini.php";
        elseif (strpos($file,"beeloggerH") !== FALSE) $ininameAkku = "Hexa".intval(substr($file,10,1))."/beelogger_ini.php";
        else $ininameAkku =  $file."/beelogger_ini.php";

	  if($AnzahlDarstellungen==0)echo'<form name="mobilew" action="" method="post">';
        if ($ininameAkku != ""){
          include($ininameAkku);
          if($Aktuell !="") $j_val = $jetzt - $Aktuell;
          else $j_val = 100000;
          if ($j_val < 32400) echo "<td style='color:black;'>\n"; //6 Stunden
          elseif ($j_val < 36000 AND $EESendeIntervall == "B") echo "<td style='color:black;'>\n";
          elseif ($j_val < 72000 AND $EESendeIntervall == "C") echo "<td style='color:black;'>\n";
          elseif ($j_val < 93600 AND $EESendeIntervall == "D") echo "<td style='color:black;'>\n";
          else {echo "<td style='color:red;'>\n"; $col_red = true;}

          echo '<div id="Hdr_'.$AnzahlDarstellungen.'">'."\n";
          if ($mW_spalten == "2") echo '<div class="headline_2">';
          else echo '<div class="headline_1">';

          if ($AkkuLeerSchwelle != "0" && $AkkuVollSchwelle != "0") if ($AktuellerAkkustand > 3.0 && $AktuellerAkkustand != 0){
            if ($AktuellerAkkustand >= $AkkuVollSchwelle) echo'<img src="beelogger_icons/Akku100.png" width="25" height="15" style="margin-right:5px;" title="'.$AktuellerAkkustand.'">'; //Akku 100
            elseif ($AktuellerAkkustand < $AkkuLeerSchwelle) echo'<img src="beelogger_icons/Akku25.png" width="25" height="15" style="margin-right:5px;" title="'.$AktuellerAkkustand.'">'; //Akku leer
            else echo'<img src="beelogger_icons/Akku50.png" width="25" height="15" style="margin-right:5px;" title="'.$AktuellerAkkustand.'">'; //Akku 50
		  }
        }
        else{
          echo "<td style='color:black;'>\n";
          echo '<div id="Hdr_'.$AnzahlDarstellungen.'">'."\n";
          if ($mW_spalten == "2") echo '<div class="headline_2">';
          else echo '<div class="headline_1">';
        }

        include ($ininame); // reinit Sensorname holen und Watchdog checken
		if($mW_twogrid=="")$mW_twogrid="0"; //echo "<BR>// ini:".$ininame."<BR>";echo"//twogrid: ".$mW_twogrid." ".$mW_tageswertanzeige."<BR>";

        if (isset($_GET['roll'])) $mW_roll = $_GET['roll']; // für alle Beelogger
        if($mW_roll == "") $mW_roll = "1";

        if($show_mini ==1) echo'<a href="'.$file.'/beelogger_sml.php">';
        else echo'<a href="'.$file.'/beelogger_show.php">';

        $y1_vis = 1;
        if($mW_one_y==1)$y1_vis=0;  
        if (($mW_sensor1 == "") OR ($mW_sensor1 < 1 )) $mW_sensor1 = 6; //Gewichtssensor ist default
        if (($mW_sensor2 == "") OR ($mW_sensor2 < 1 )) {$mW_sensor2 = 1; $y1_vis = 0;}
        if ($mW_sensor2 == 6){$help = $mW_sensor1; $mW_sensor1 = 6; $mW_sensor2 = $help;} // Gewicht muss immer sensor1 sein

		if ($mW_sensor1==12){if($Icon[$mW_sensor1-1]=="rain.png"){$tw_rain=1;}}


        $ShowFile = str_replace("beelogger","",$file);
		If(strpos($ShowFile,"_")) $ShowFile = str_replace("_","- ",$ShowFile);
        if ($Bienenvolkbezeichnung == "auto") echo "<b>".$file."</b>";
        else{
          if ($mW_sort != "Bienenvolkbezeichnung") echo "<b>".$ShowFile."</b>(".html_entity_decode($Bienenvolkbezeichnung).")";
          else echo "<b>".html_entity_decode($Bienenvolkbezeichnung)."</b>(".$ShowFile.")";
        }
        echo'</a> ';
		if ($mW_twogrid=="1")echo",";

      echo "\n";
        if ($KoeAnzeige != '' AND $KoeAnzeige != "deaktiviert")          {
            if ($KoeKaefig == "aktiviert") $KoeAnzeige = str_replace("zeichnet.png","kaefigt.png",$KoeAnzeige);
            echo "<img src='beelogger_icons/".$KoeAnzeige."' width='25' height='25' style='margin-bottom:-5px;' title='".$KoeInfo."'>";
          }

        if($col_red) echo " ".$MAs[0]." ".date("j/n/y",$Aktuell);
		else if (($jetzt-$Aktuell) > 32400) echo " ".$MAs[0]." ".date($MAs[44],$Aktuell);

        echo $MAs[45].$MAs[1]." ".date("H:i",$Aktuell).$MAs[2];
        if ($mW_spalten == "2")echo"<br>";else echo"&nbsp; ";

      $what = trim($PrintArray[0]);
      $x = explode( ",", $what );
      $Sonderzeichen = array("[","]");

      if ((int)$BeutenLeergewicht > 0){
          $Klammern = array("[", "]");
          $GewichtsEinheit = str_replace($Klammern,"",$Sensoren[5*5+4]);
          if (((int)$Honigraeume[0]+(int)$Honigraeume[2]) > 0){
          for ($i=0; $i < (int)$Honigraeume[0]; $i++){
            if ((int)$Honigraeume[1] >100) $ShowHonigraum = (float)$Honigraeume[1]/1000;// noch in Gramm
            else $ShowHonigraum =  $Honigraeume[1];
            $Honigraeume[1] = round($Honigraeume[1],1);
            echo "&nbsp;<img src='beelogger_icons/honigraum.png' width='15' height='".($ShowHonigraum*2)."' title='".$Honigraeume[1].$GewichtsEinheit."'>";
          }
          for ($j=0; $j < (int)$Honigraeume[2]; $j++){
            if ((int)$Honigraeume[3] >100) $ShowHonigraum = (float)$Honigraeume[3]/1000;// noch in Gramm
            else $ShowHonigraum =  $Honigraeume[3];
            $Honigraeume[3] = round($Honigraeume[3],1);
            echo "&nbsp;<img src='beelogger_icons/honigraum.png' width='15' height='".($ShowHonigraum*2)."' title='".$Honigraeume[3].$GewichtsEinheit."'>";
          }

          if ((int)$BeutenUtils[0][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Varroaeinschub.png' width='15' height='15' title='Gewicht der Varroaschublade = ".round($BeutenUtils[0][1],1).$GewichtsEinheit."'>";
          if ((int)$BeutenUtils[1][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Absperrgitter.png' width='15' height='15' title='Gewicht des Absperrgitters = ".round($BeutenUtils[1][1],1).$GewichtsEinheit."'>";
          if ((int)$BeutenUtils[2][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Futterzarge.png' width='15' height='15' title='Gewicht der Futterzarge = ".round($BeutenUtils[2][1],1).$GewichtsEinheit."'>";
        }

        else{
          echo'<img src="beelogger_icons/honey.png" height="26" style="margin-bottom:-5px;">';
          if ((int)$BeutenUtils[0][0] == 1 OR (int)$BeutenUtils[1][0] == 1 OR (int)$BeutenUtils[2][0] == 1) echo "(";
          if ((int)$BeutenUtils[0][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Varroaeinschub.png' width='20' height='20' title='Gewicht der Varroaschublade = ".round($BeutenUtils[0][1],1).$GewichtsEinheit."'>";
          if ((int)$BeutenUtils[1][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Absperrgitter.png' width='20' height='20' title='Gewicht des Absperrgitters = ".round($BeutenUtils[1][1],1).$GewichtsEinheit."'>";
          if ((int)$BeutenUtils[2][0] == 1) echo "&nbsp;<img src='beelogger_icons/n_Futterzarge.png' width='20' height='20' title='Gewicht der Futterzarge = ".round($BeutenUtils[2][1],1).$GewichtsEinheit."'>";
          if (((int)$BeutenUtils[0][0] == 1 OR (int)$BeutenUtils[1][0] == 1 OR (int)$BeutenUtils[2][0] == 1))echo ")";
          echo ":";
        }
		$FuTr = ((float)$x[6] - (float)$BeutenLeergewicht-((int)$Honigraeume[0]*(float)$Honigraeume[1])-((int)$Honigraeume[2]*(float)$Honigraeume[3]));
        if ($BeutenUtils[0][0] == 1) $FuTr = $FuTr - (float)$BeutenUtils[0][1];
        if ($BeutenUtils[1][0] == 1) $FuTr = $FuTr - (float)$BeutenUtils[1][1];
        if ($BeutenUtils[2][0] == 1) $FuTr = $FuTr - (float)$BeutenUtils[2][1];
        echo "&nbsp;".round($FuTr,1).str_replace($Sonderzeichen,"",$Sensoren[29])."</b>";
        if (((int)$Honigraeume[0]+(int)$Honigraeume[2]) > 0) $GesamtFuTr += (float)$FuTr;
      }
      elseif ($Sensoren[($mW_sensor1-1)*5] != ""){
        if ($Sensoren[($mW_sensor1-1)*5] == "Gewicht") echo'<img src="beelogger_icons/weight.png" width="30" height="20" style="margin-right:5px;">';
        else echo $Sensoren[($mW_sensor1-1)*5];
		if($tw_rain){ echo " 24h: ";
			if (file_exists($file."/beelogger_interface.php")) {
				include($file."/beelogger_interface.php");
				$rain_val = number_format(((float)$rain_for_24),1,".","");
				echo $rain_val.str_replace($Sonderzeichen, "", $Sensoren[($mW_sensor1-1)*5+4]);
				if(isset($rain_for_7d)){ $rain_7d = number_format(((float)$rain_for_7d),1,".",""); echo ", 7d: ".$rain_7d.str_replace($Sonderzeichen, "", $Sensoren[($mW_sensor1-1)*5+4]);}
			}
		}
		else {echo ": "; if ($x[$mW_sensor1] != "") echo $x[$mW_sensor1];echo str_replace($Sonderzeichen, "", $Sensoren[($mW_sensor1-1)*5+4]).'</b>';}
      }
      if($AnzahlDarstellungen==0) {
       echo"&nbsp;&nbsp;&nbsp;";
	   for($X=1;$X < 4;$X++){
         if(file_exists("./Xanalyse".$X)){
			$name_ini =  "./Xanalyse".$X."/beelogger_ini.php";
			if (file_exists($name_ini)){
              include ($name_ini);
              if($mobileWatch_Show == "aktiviert"){
				echo'<a href="./Xanalyse'.$X.'/beelogger_show.php"><img src="./beelogger_icons/Xanalyse.png" title="Xanalyse'.$X.'" style="margin-bottom:-9px" height=30 width=18 ></a>&nbsp;';
              }
			}
		 }
	   }
       echo"\n".'<button type="submit" id="Rng" name="Auto_range" value="'.$Range.'" title="sets y-axes all same scale">___</button>'."\n";
	  }
      echo "\n";
	  echo "</form></div>\n</div>"."\n";// /form

	include ($ininame); // reinit
      $beeloggerGraph = "Graph_".$file;
      echo "\n".'<div id="'.$beeloggerGraph.'"></div>';
      echo"\n";
      

?>

<script type="text/javascript">

var d_names = [<?php echo '"'.$MAs[25].'","'.$MAs[26].'","'.$MAs[27].'","'.$MAs[28].'","'.$MAs[29].'","'.$MAs[30].'","'.$MAs[31].'"'; ?>];
var m_names = [<?php echo '"'.$MAs[32].'","'.$MAs[33].'","'.$MAs[34].'","'.$MAs[35].'","'.$MAs[36].'","'.$MAs[37].'","'.$MAs[38].'","'.$MAs[39].'","'.$MAs[40].'","'.$MAs[41].'","'.$MAs[42].'","'.$MAs[43].'"'; ?>];
var winH = window.innerHeight; // das Fenster an sich
<?php
$mobilePhone =0;// Mobilgeräte
if(preg_match("/(alcatel|amoi|android|avantgo|blackberry|benq|cell|cricket|docomo|elaine
|htc|iemobile|iphone|ipad|ipaq|ipod|j2me|java|midp|mini|mmp|mobi|motorola|nec-|nokia|palm|
panasonic|philips|phone|playbook|sagem|samsung|sharp|sie-|silk|smartphone|sony|symbian|t-mobile|telus
|up\.browser|up\.link|vodafone|wap|webos|wireless|xda|xoom|zte)/i", $_SERVER['HTTP_USER_AGENT']))
{ $mobilePhone=1;}
echo "\n";
$s_1 = ($mW_sensor1-1)*5;
$s_2 = ($mW_sensor2-1)*5;
echo"var g$AnzahlDarstellungen" ?> = new Dygraph(document.getElementById(<?php echo '"'.$beeloggerGraph.'"'?>),"<?php echo $MAs[24].",".html_entity_decode($Sensoren[$s_1]).' '.html_entity_decode($Sensoren[$s_1+4]).','; 
if ($mW_sensor2 != "") echo html_entity_decode($Sensoren[$s_2]).' '.html_entity_decode($Sensoren[$s_2+4]); 
if (($mW_sensor1 == 6) AND $mW_tageswertanzeige != "deaktiviert"){ echo ','.$MAs[3].'+,'.$MAs[3].'-';} 
else if (($mW_sensor1 == 12) AND $mW_tageswertanzeige != "deaktiviert"){ echo ','.$MAs[3].'+,'.'Summe';}?>\n" +
  <?php
  $what = trim($PrintArray[0]);
  for ($l = ($k-1); $l > 0; $l--){
	$what = trim($PrintArray[$l]);
	$x = explode( ",", $what );
	if (($mW_sensor1 != 6)AND($mW_sensor1 != 12)) echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].'\n"+';
	else{
		if ((($mW_sensor1 == 6)OR($mW_sensor1 == 12))AND $mW_tageswertanzeige != "deaktiviert") echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].',0,0\n"+';
		else echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].'\n" + ';
	}
  }
  $what = trim($PrintArray[0]);
  $x = explode( ",", $what );
  if (($mW_sensor1 != 6)AND($mW_sensor1 != 12)) echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].'\n",';
  else{
    if ((($mW_sensor1 == 6)OR($mW_sensor1 == 12)) AND $mW_tageswertanzeige != "deaktiviert") echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].',0,0\n",';
    else echo '"'.$x[0].','.$x[$mW_sensor1].','.$x[$mW_sensor2].'\n",';
  }

  echo"\n  {
  visibility: [1,";
    if($mW_one_y==1) echo"1"; else echo "$y1_vis";
    if ((($mW_sensor1 == 6)OR($mW_sensor1 == 12)) AND $mW_tageswertanzeige != "deaktiviert") echo ",1,1";
    echo "],\n colors: [";
//colors
    if($col_red == true) echo"'red','".$Sensoren[$s_2+1]."'";
    else echo " '".$Sensoren[$s_1+1]."','".$Sensoren[$s_2+1]."'";
    if (($mW_sensor1 == 6) AND $mW_tageswertanzeige != "deaktiviert") echo ",'#00ff01','#ff0001'";
    if (($mW_sensor1 == 12) AND $mW_tageswertanzeige != "deaktiviert") echo ",'#00ff01','#FFC31E'";
    echo "],
  series: {'".html_entity_decode($Sensoren[$s_1]).' '.html_entity_decode($Sensoren[$s_1+4])."':{axis:'y2'";
    if(strlen($Sensoren[$s_1+4])>=2){if(strpos($Sensoren[$s_1+4],"] ",0))  echo',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.2';}
    echo"},";
    if($y1_vis ==0) echo "'".html_entity_decode($Sensoren[$s_2]).' '.html_entity_decode($Sensoren[$s_2+4])."':{axis:'y2'},";
	else {
	  echo "'".html_entity_decode($Sensoren[$s_2]).' '.html_entity_decode($Sensoren[$s_2+4])."': {axis: 'y1'";
	  if(strlen($Sensoren[$s_2+4])>=2){if(strpos($Sensoren[$s_2+4],"] ",0))  echo',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.2';}
	  echo "},";
    }
    if ((($mW_sensor1 == 6)OR($mW_sensor1 == 12)) AND $mW_tageswertanzeige != "deaktiviert") echo "'".$MAs[3]."+':{axis:'y',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.4},";
    if ($mW_sensor1 == 6 AND $mW_tageswertanzeige != "deaktiviert") echo "'".$MAs[3]."-':{axis:'y',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.4},";
    if ($mW_sensor1 == 12 AND $mW_tageswertanzeige != "deaktiviert") echo "'Summe':{axis:'y',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.2},";
    echo '},
  axes:{
    x:{axisLabelFormatter:function(d,gran){
      var curr_day=d_names[d.getDay()];
      var curr_month=m_names[d.getMonth()];
      try{var w= g'.$AnzahlDarstellungen.'.xAxisRange();}
      catch(ignore){var w=[0,1];}
      if((w[1]-w[0])<(86400000*3)){
        var Minuten=d.getMinutes();Minuten=((Minuten<10)?"0"+Minuten:Minuten);
        return curr_day+" "+d.getDate()+"'.$MAs[45].'"+curr_month+" "+d.getHours()+":"+ Minuten+" ";
      }
      else{return curr_day+" "+d.getDate()+"'.$MAs[45].'"+curr_month;}
    },
    axisLabelWidth:160,pixelsPerLabel:110
    },'."\n";
	if(($mW_twogrid=="1")&&($y1_vis ==1)){ // zwei grids, zwei kurven
		echo'  y:{drawGrid:true,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,digitsAfterDecimal:2,
		axisLabelFormatter:function(x){if(Math.abs(x)<0.01)return "0-";if(x>100)return(Math.round(x));return(Math.round(x*10,1)/10);}'."\n";
		echo "   ,gridLineColor:'".$Sensoren[$s_2+1]."',gridLinePattern:[5,10]},\n";
	}
	else if(($mW_twogrid=="1")&&($mW_tageswertanzeige!="deaktiviert")){ // zwei grids tageswert
		echo'  y:{drawGrid:true,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,digitsAfterDecimal:2,
		axisLabelFormatter:function(x){if(Math.abs(x)<0.01)return "0-";if(x>100)return(Math.round(x));return(Math.round(x*10,1)/10);}'."\n";
		echo "   ,gridLineColor:'darkgreen',gridLinePattern:[5,20]},\n";
	}
	else
		echo'  y:{drawGrid:true,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,digitsAfterDecimal:2,
	             axisLabelFormatter:function(x){if(Math.abs(x)<0.01)return "0-";if(x>100)return(Math.round(x));return(Math.round(x*10,1)/10);}},';

	if(($mW_twogrid=="1")&&(($y1_vis==1)OR($mW_tageswertanzeige!="deaktiviert"))){ // zwei grids, zwei kurven
		echo "\n  y2:{drawGrid:true,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,gridLineColor:";
	}
	else echo "\n  y2:{drawGrid:false,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,gridLineColor:";
  if($col_red == true)echo'"red"}},'; else echo'"'.$Sensoren[$s_1+1].'"} },';

  if (($mW_sensor2 != "0")&&($y1_vis)) {
	  echo "\n ylabel:'".html_entity_decode($Sensoren[$s_2])." ".$MAs[4]." ".html_entity_decode($Sensoren[$s_2+4])."',";
  }
  if($mW_one_y) echo"\n y2label:' ".html_entity_decode($Sensoren[$s_1+4]."',");
  else echo"\n y2label:'".html_entity_decode($Sensoren[$s_1])." ".$MAs[4]." ".html_entity_decode($Sensoren[$s_1+4]."',");
  if($mW_legende=="")$mW_legende="always";
  echo "
 legend:'".$mW_legende."',legendFormatter: g".$AnzahlDarstellungen."_mylefo,
 rollPeriod:".$mW_roll.",
 labelsSeparateLines: true,
 hideOverlayOnMouseOut: false,
 strokeWidth:2.0,
 width:200,height:200,\n";
 if($mobilePhone==1) echo" interactionModel: Dygraph.nonInteractiveTouch_"; else echo " interactionModel: Dygraph.defaultInteractionModel";
 echo "});\n\n";

//####### ready
echo"g$AnzahlDarstellungen.ready(function(){\n";
echo " g".$AnzahlDarstellungen."_onresize();\n";

if ((($mW_sensor1 == 6)OR($mW_sensor1 == 12)) AND $mW_tageswertanzeige != "deaktiviert"){
  if ($file != "beelogger_icons" && $file != "beelogger_sprachfiles"&& $file != "." && $file != ".." && is_dir($file)){
    $beeIniname =  $file."/beelogger_ini.php";
    if (file_exists($beeIniname)) include($beeIniname);
  }
  echo " g".$AnzahlDarstellungen."_berechne_tag();\n";
}

if($Range=='a'){?>
 var rng_y2=<?php echo"g".$AnzahlDarstellungen?>.yAxisRange(1);//a
 var rng=rng_y2[1]-rng_y2[0];
 range = rng_y2[0] % 1;
 rng_y2[0]=Math.floor(10*rng_y2[0])/10;
 rng_y2[1]=Math.ceil(10*rng_y2[1])/10;
 <?php echo"g".$AnzahlDarstellungen?>.updateOptions({axes:{y2:{valueRange:rng_y2}}});
 document.mobilew.Auto_range.value ="b1";
<?php }

else if($Range=='b'){
 ?>
 var overall = parseFloat(document.getElementById("Rng").innerText);//b
 if (isNaN(overall))overall=1;
 var rng_y2=<?php echo"g".$AnzahlDarstellungen?>.yAxisRange(1);
 if(rng_y2!==null){var rng=(rng_y2[1]-rng_y2[0])/10;
 rng=10*Math.ceil(rng);if(rng<1)rng=1;if(rng>overall)overall=rng;
 }
 document.getElementById("Rng").innerText=overall;
 if(overall<5)document.mobilew.Auto_range.value='a';else document.mobilew.Auto_range.value='c'+overall;
<?php
 }

 else{
   echo " var overall=".(int)$Autorange.";\n";
   if($AnzahlDarstellungen==0){
   if($Range=='d')$Range='e';
   if($Range=='c')$Range='d';
?>
 overall=Math.floor((overall/2)+0.5);//c
 document.getElementById("Rng").innerText=overall;
<?php
    if($Range=='e') echo"document.mobilew.Auto_range.value='a';";else echo "if(overall<5)document.mobilew.Auto_range.value='a'; else document.mobilew.Auto_range.value='".$Range."'+overall;";
   }
 }
 ?>

 addEventListener("resize", <?php echo"g".$AnzahlDarstellungen?>_onresize);
<?php if($mobilePhone==0){
	if(($mW_sensor2 =="0" AND $mW_tageswertanzeige == "deaktiviert")){echo ' addEventListener("mouseup",g'.$AnzahlDarstellungen.'_my_mouseup);';echo ' addEventListener("dblclick",g'.$AnzahlDarstellungen.'_my_dblclick);';}
}?>
<?php
echo " setTimeout(function(){g".$AnzahlDarstellungen."_onresize();}, 100);\n";

// Anmerkungen einlesen
if ($mW_notes == "aktiviert"){
  $ErstesDatum = "0";
  if (file_exists($filename)) { //Letzte Werte aus .csv auslesen
    $array = file($filename);
    if(sizeof($array) >0){
      $ErsteZeile = trim($array[0]);
      $x = explode( ",", $ErsteZeile);
      $CsvSpaltenAnzahl = sizeof($x);
      $ErstesDatum = $x[$CsvSpaltenAnzahl-1];
    }
  }
  $input = $notename; //"notes.csv";
  $narray = file($input);
  $ni = sizeof($narray);
  $b=0;
  $HelpArraySensoren = $Sensoren;
  for ($a = 0 ; $a < $ni; $a++){
    $what = trim($narray[$a]);
    $x = explode( ",", $what );
    $s = sizeof($x);
    $Sensorx=$x[0];
    if (is_numeric($Sensorx)) {
      $Sensorlbl = html_entity_decode($HelpArraySensoren[$Sensorx*5]).' '.html_entity_decode($HelpArraySensoren[$Sensorx*5+4]);
      $Anmerkung = html_entity_decode($x[$s-2]);
      $AktualisierungStamp=$x[$s-1];
      if ($AktualisierungStamp > $ErstesDatum){
        $DatumAnmerkung = date("Y/m/d H:i:s",$AktualisierungStamp);   //Zeitformat für Dygraph umgestellt !$
        $anmerkungen[$b] = $Sensorlbl;       // Sensor im Array merken
        $b++;
        $anmerkungen[$b] = $DatumAnmerkung;//Datumsstempel im Array merken
        $b++;
        $y =explode("&",$Anmerkung);
        $anmerkungen[$b] = $y[0];          //Anmerkung kurz im Array merken
        $b++;
        $anmerkungen[$b] = $y[1];         //Anmerkung lang im Array merken
        $b++;
      }
    }
  }   //ende for-schleife

// Ende Anmerkungen Array erstellen
echo " g$AnzahlDarstellungen.setAnnotations([  ";
for ($w = 0; $w < $b; $w++){
  echo " {series: '".$anmerkungen[$w]."',";
  $w++;
  echo 'xval: Date.parse(\''.$anmerkungen[$w].'\'),';
  $w++;
  if (!strpos($anmerkungen[$w],".png") === false) echo 'icon: "'.substr($anmerkungen[$w],3).'",width: 30,height: 30';
  else echo 'shortText: "'.$anmerkungen[$w].'",
    width: '.(6+8*strlen($anmerkungen[$w]));
  $w++;
  echo ',text: "'.$anmerkungen[$w].'",';
  echo'attachAtBottom: true,';
  echo "},";
}//for $w
echo"]);\n";
echo " var anns = g$AnzahlDarstellungen.annotations(); ann_count = anns.length+1;";
}
echo "\n});\n";
//####### ready
?>

function <?php echo"g".$AnzahlDarstellungen?>_mylefo(data){
if(data.x==null){return '<br>';}
var d_a=new Date(data.x);var html=d_names[d_a.getDay()]+' '+data.xHTML;
data.series.forEach(function(series){
 if(!series.isVisible)return;if(!series.yHTML)return;
 html+='<br>'+series.dashHTML+' '+ series.labelHTML+': '+ series.yHTML;
});
return html;
}

function <?php echo"g".$AnzahlDarstellungen?>_my_mouseup(e){
<?php if($mW_sensor2 =="0")echo'let y=e.clientY;'."\n".'var rect=document.getElementById("'.$beeloggerGraph.'").getBoundingClientRect();'."\n".'if(y>rect.top){g'.$AnzahlDarstellungen.'_Rng_Axes();}';?>
}
function <?php echo"g".$AnzahlDarstellungen?>_my_dblclick(e){
<?php if($mW_sensor2 =="0")echo'g'.$AnzahlDarstellungen.'_Rng_Axes();';?>
}

function <?php echo"g".$AnzahlDarstellungen?>_range(){
var overall =parseFloat(document.getElementById("Rng").innerText);
var rng=<?php echo"g".$AnzahlDarstellungen?>.yAxisRange(1);
rng[1]=rng[0]+overall;
<?php echo"g".$AnzahlDarstellungen?>.updateOptions({axes:{y2:{valueRange:rng}}});
rng[1] = Math.ceil(y1_up);
rng[0] = Math.floor(y1_low);
<?php echo"g".$AnzahlDarstellungen?>.updateOptions({axes:{y:{valueRange:rng}}});
}

function <?php echo"g".$AnzahlDarstellungen?>_Rng_Axes(){
var rng=<?php echo"g".$AnzahlDarstellungen?>.yAxisRange(1);
var y_offset=(rng[1]-rng[0])*0.1;if(Math.abs(rng[0]) < 0.01){y_offset=0;}rng[1]=rng[1]-rng[0]-y_offset;rng[0]=-y_offset;
<?php echo"g".$AnzahlDarstellungen?>.updateOptions({axes:{y:{valueRange:rng}}});
}

function <?php echo"g".$AnzahlDarstellungen?>_onresize(){
var w = window.innerWidth;
var h = window.innerHeight;
var hh=450; var ww=w;
<?php if ($mW_spalten == "2") echo " hh=400;w=w/2-50;"; else echo "w=w*0.95;"; if($mobilePhone==1) echo "if (ww > h){ if ( (winH - h) >50) winH = h; hh=winH*0.8; };";?>
w=parseInt(w);
if(<?php echo"g$AnzahlDarstellungen" ?>!=='undefined'){<?php echo"g".$AnzahlDarstellungen.".resize(w,hh);"; if(($mW_sensor2 =="0" AND $mW_tageswertanzeige == "deaktiviert"))echo"g".$AnzahlDarstellungen."_Rng_Axes();"?>}
}

<?php if($mW_tageswertanzeige != "deaktiviert"){ ?>
function <?php echo"g".$AnzahlDarstellungen."_berechne_tag()"?>{
var k, i, hr_akt, g_alt, g_neu;
var suche_tag=1, tageswert=0;
var old_d, cur_day, day, df_k, diff_korr;
var datum=new Date();
var boundary=<?php echo"g".$AnzahlDarstellungen?>.numRows();
if(boundary===null){return;}
var sum_grz = <?php if($TageswertAnzeige=='false')echo "2.0"; else echo $TageswertAnzeige;?>;
var data_d=[]; data_d.length=0;
try{g_neu=<?php echo"g".$AnzahlDarstellungen?>.rawData_[0][1];}catch(ignore){};
if(g_neu===null){g_neu=0;}

datum.setTime(<?php echo"g".$AnzahlDarstellungen?>.rawData_[0][0]);
day = datum.getDate();
data_d.push([new Date(<?php echo"g".$AnzahlDarstellungen?>.rawData_[0][0]),
<?php echo"g".$AnzahlDarstellungen?>.rawData_[0][1],
<?php echo"g".$AnzahlDarstellungen?>.rawData_[0][2],0,0]);
var x_ax = boundary-1;
for(i=0;i<boundary;i++){
 datum.setTime(<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][0]);
 hr_akt=datum.getHours();cur_day=datum.getDate();
 <?php if($tw_rain==0){?>
  if(suche_tag){suche_tag=0;k=i;if(k>0){k--;}diff_korr=0.0;

  while((day==cur_day)&&(k<x_ax)){
   if(<?php echo"g".$AnzahlDarstellungen?>.rawData_[k][1]!==null) {
    if (<?php echo"g".$AnzahlDarstellungen?>.rawData_[k+1][1]!==null){
      df_k = <?php echo"g".$AnzahlDarstellungen?>.rawData_[k][1]- <?php echo"g".$AnzahlDarstellungen?>.rawData_[k+1][1];if(Math.abs(df_k)>sum_grz){diff_korr+=df_k;}
    }
    else{var ix=1;
	 while(ix<10){ix++;
	 try{
      if (<?php echo"g".$AnzahlDarstellungen?>.rawData_[k+ix][1]!==null){
       df_k = <?php echo"g".$AnzahlDarstellungen?>.rawData_[k][1] - <?php echo"g".$AnzahlDarstellungen?>.rawData_[k+ix][1];
       if (Math.abs(df_k)>sum_grz){diff_korr += df_k;}
       break;
	  }
     }catch(ignore){};
    }}
   }
   k++;datum.setTime(<?php echo"g".$AnzahlDarstellungen?>.rawData_[k][0]);day=datum.getDate();
  }
  old_d=cur_day;g_alt=g_neu;
  if(<?php echo"g".$AnzahlDarstellungen?>.rawData_[k-1][1]===null){g_neu=g_alt;}
  else{g_neu=<?php echo"g".$AnzahlDarstellungen?>.rawData_[k-1][1];}
  tageswert=g_neu-g_alt+diff_korr;if(Math.abs(tageswert)<0.03)tageswert=0.0;
 }
  <?php } else {?>
  if(suche_tag){suche_tag=0;k=i;if(k>0){k--;}tageswert=0;day=cur_day;
   while((day==cur_day)&&(k<x_ax)){
    if(<?php echo"g".$AnzahlDarstellungen?>.rawData_[k][1]!==null){tageswert+=<?php echo"g".$AnzahlDarstellungen?>.rawData_[k][1];}
     k++;datum.setTime(<?php echo"g".$AnzahlDarstellungen?>.rawData_[k][0]);day=datum.getDate();
    }
   old_d=cur_day;if(Math.abs(tageswert)<0.05)tageswert=0.0;
  }

  <?php }?>
  if(cur_day!=old_d){suche_tag=1;tageswert=0;}
  if((hr_akt>2)&&(hr_akt<23)){
    if(tageswert>0){
      data_d.push([new Date(<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][0]),<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][1],<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][2],tageswert, ]);
    } else if(tageswert<0){
      data_d.push([new Date(<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][0]),<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][1],<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][2], ,tageswert]);
    } else{
      data_d.push([new Date(<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][0]),<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][1],<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][2],0, ]);
    }
    } else{
      data_d.push([new Date(<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][0]),<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][1],<?php echo"g".$AnzahlDarstellungen?>.rawData_[i][2], , ]);
    }
    if(tageswert > y1_up) y1_up = tageswert;
    if(tageswert < y1_low) y1_low = tageswert;
 }
<?php if($tw_rain==1) {?>
    // summe
var sum_gew=0;
try {
once=1;
for(i=0;i<boundary;i++){
  datum.setTime(data_d[i][0]);hr_akt=datum.getHours();
  if(once&&(hr_akt>=4)&&(hr_akt<18)){if (!isNaN(data_d[i+1][3])){sum_gew=sum_gew+data_d[i+1][3];once=0;}
  }else if((hr_akt>=22)||(hr_akt<4)){once=1;}
  data_d[i][4]=sum_gew;
} // end for
}catch(ignore){}//nothing

   <?php }?>
  <?php echo"g".$AnzahlDarstellungen?>.updateOptions({'file':data_d});
}
<?php }  // if tageswertanzeige
?>
window.addEventListener("resize", <?php echo"g".$AnzahlDarstellungen?>_onresize);
<?php if(($Autorange>0)AND($AnzahlDarstellungen==$cnt)){for($w=$AnzahlDarstellungen;$w>=0;$w--){echo"g".$w."_range();\n";}} ?>
</script>

<div id="empty"> &nbsp; </div>
</td>
<?php
$AnzahlDarstellungen++;
// Tabelle Zeilenende
if ($mW_spalten == "2"){
  $SpalteNr ++;
  if ($SpalteNr == 2){
    echo "</tr>";
    $SpalteNr = 0;
  }
}
else echo "</tr>"; // nur eine Spalte
} //if ($ext_autowatch == "")
if ($Watchdog == "deaktiviert") $WatchCounter ++;  //deaktivierte zählen  trotz aktueller Daten

} //((($jetzt-$Aktuell) < 86400)
else { //sammel Daten für heute Inaktive Beuten-Ausgabe
  $NoCsvCounter++;
  if ($Watchdog == "aktiviert"){ //nur wenn der Watchdog aktiviert ist wird reagiert!
    $InaktivArray[$InaktivCounter] = $ininame;
    $InaktivCounter ++;
    $InaktivArray[$InaktivCounter] = $file;
    $InaktivCounter ++;
    if ($Aktuell != "") $InaktivArray[$InaktivCounter] = $Aktuell; //falls kein Wert in beelogger.csv
    else $InaktivArray[$InaktivCounter] = $MAs[5];
    $InaktivCounter ++;
  } //if ($Watchdog == "aktiviert")
  elseif ($Watchdog == "deaktiviert") $WatchCounter ++;  //deaktivierte zählen
} // else { //sammel Daten für Inaktive Beuten-Ausgabe

}  //if (file_exists($filename))
else{
  $NoCsvCounter++; //Es wurde keine beelogger.csv-Datei gefunden
  if (file_exists($ininame)){
    include ($ininame); // Sensorname holen und Watchdog checken
    if ($Watchdog == "aktiviert") { //nur wenn der Watchdog aktiviert ist wird reagiert!
      $InaktivArray[$InaktivCounter] = $ininame;
      $InaktivCounter ++;
      $InaktivArray[$InaktivCounter] = $file;
      $InaktivCounter ++;
      $InaktivArray[$InaktivCounter] = "Nie";
      $InaktivCounter ++;
    } //if ($Watchdog == "aktiviert")
    elseif ($Watchdog == "deaktiviert") $WatchCounter ++;  //deaktivierte zählen
  } // if (file_exists($ininame))
} //Es wurde keine beelogger.csv-Datei gefunden

}  //if is dir file
else  $NoCsvCounter ++;
}// foreach

//Zusatzinfos ausgeben
if ($GesamtFuTr != "" AND $GesamtFuTr > 0){
  echo "<tr style='background-color: lightgreen;'><td";
  if ($mW_spalten == "2") echo " colspan=2";
  echo "><font size='7'><img src='beelogger_icons/n_Honigernte.png' height=40 width=40 > ".$MAs[11].": ".round($GesamtFuTr,0)." kg <img src='beelogger_icons/n_Honigernte.png' height=40 width=40 ></td></tr>";
}
echo "</table>";

if ($beeloggerCounter == 0) {
  echo $MAs[6];
  if($show_mini ==1)echo '<br><h4><a href="./beelogger1/beelogger_sml.hp" >./beelogger1/beelogger_show.php</a></h4>'."\n";
  else echo '<br><h4><a href="./beelogger1/beelogger_show.php" >./beelogger1/beelogger_show.php</a></h4>'."\n";
}else{
  if ($AnzahlDarstellungen == 0) {
    echo $MAs[46];
    if($show_mini ==1) echo '<br><h4><a href="./beelogger1/beelogger_sml.hp" >./beelogger1/beelogger_show.php</a></h4>'."\n";
    else echo '<br><h4><a href="./beelogger1/beelogger_show.php" >./beelogger1/beelogger_show.php</a></h4>'."\n";
  }
}
?>
</body>
</html>
<?php


// Automatischer Watchdogbereich

//Pushnachricht erzeugen
if ($ext_autowatch != "") {
  if ($Message != ""){
    $Message = substr($Message,0,-2); //letztes Komma der Beutenaufzählung entfernen
    if (($ext_autowatch == "p") OR ($ext_autowatch == "pm") OR ($ext_autowatch == "mp")){
      $PushUser = $FetchPushUser;
      $PushToken = $FetchPushToken;
      if (($PushUser != "meinUser") AND ($PushUser != "")){ //falls User eingegeben wurde
        curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
        CURLOPT_POSTFIELDS => array(
        "token" => $PushToken,
        "user" => $PushUser,
        "message" => $Message,
        ),
        CURLOPT_SAFE_UPLOAD => true,
        CURLOPT_RETURNTRANSFER => true,
        ));
        curl_exec($ch);
        curl_close($ch);

        echo "<br><br>".$MAs[15].".";
        echo "<br>".$MAs[16].": ".$Message;
      } //if ($PushUser != ""){ //falls User eingegeben wurde

    } //if (($ext_autowatch == "p") OR ($ext_autowatch == "p+m"))

//Mailnachricht erzeugen

  if (($ext_autowatch == "m") OR ($ext_autowatch == "pm") OR ($ext_autowatch == "mp")){
  $Absender_Email = $FetchAbsender_Email;
  $Empfaenger_Email = $FetchEmpfaenger_Email;
  if (($Empfaenger_Email != "empfaenger@meineDomain.de") AND ($Empfaenger_Email != "") AND ($Absender_Email != "absender@meineDomain.de") AND ($Absender_Email != "")){
    $Mailbetreff = "beelogger-".$MAs[18].": ";
    $Absender_Name = "beelogger-watchdog";
    $Header  = "From:".$Absender_Name." <".$Absender_Email.">\n";
    mail($Empfaenger_Email, $Mailbetreff, $Message, $Header);
    echo "<br><br>".$MAs[19].".";
    echo "<br>".$MAs[20].": ".$Absender_Email." ".$MAs[21].": ".$Empfaenger_Email;
    echo " ".$MAs[22].": <br>".$Message;
  }//if ($Empfaenger_Email != "empfaenger@meineDomain.de")
  else echo "<br>".$MAs[23]."!";
  } // if (($ext_autowatch == "m") OR ($ext_autowatch == "p+m"))

 } //if ($Message != !!)
} //if ($ext_autowatch != "")
?>

