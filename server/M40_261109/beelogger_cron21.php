<?php
set_time_limit(300);  // script may run up to 5 minutes
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$Konfigversion = "M.40";//vom  24.09.2026 - beelogger_cron21.php

$Check_Hour = "21"; // hour or "Alle" to run script
$test = 0;  //  Push/Mail an User = 0
$report = 0; // Monitor Ausgaben  0,1,2
echo "report: ".$report."<br>";

$MainDir = "./";
$Mainfiles = scandir($MainDir);
asort($Mainfiles);
foreach ($Mainfiles as $Mainfile){
  if ($Mainfile != "." && $Mainfile != ".."  && $file != "X_beelogger_icons" && $file != "X_beelogger_sprachfiles"){
    if (is_dir("./".$Mainfile)){
        if (is_dir("./".$Mainfile."/beelogger1")){
          $GeneralAutowatch = ""; // Initialisieren

          if (file_exists($Mainfile."/general_ini.php")) include($Mainfile."/general_ini.php");
          if ((($GeneralAutowatch =="p") OR ($GeneralAutowatch =="m") OR ($GeneralAutowatch =="pm")) AND ($GeneralAutowatchTime == $Check_Hour OR $GeneralAutowatchTime == "Alle")){
            $jetzt = time();
            $dir = $Mainfile;
            if($report>1){
				echo "<br><br><font color='green'>Scanne den Ordner: ".$dir . " ... ";
				if ($GeneralAutowatch == "m") echo "Mailbenachrichtigung ist aktiviert";
				if ($GeneralAutowatch == "p") echo "Pushbenachrichtigung ist aktiviert";
				if ($GeneralAutowatch == "pm") echo "Push- & Mailbenachrichtigung ist aktiviert";
			}

            $files = scandir($dir);
            asort($files);
            $InaktivCounter = 0;
			$InAktivArray = array("");
            $NoCsvCounter = 0;
            $Message = "";
            foreach ($files as $file) {
              if ($file != "." && $file != ".." && $file != "beelogger_icons" && $file != "beelogger_sprachfiles"
			      && (strstr($file,"Xana") == false) ){
                if (is_dir($dir."/".$file)){
                  $TageswertMail ="false"; $tz1="";$tz2="";$tw_1=-1; $tw_2=-1;$tageswertwert = "";		 
                  $Watchdog = "noIni"; //Reset $Watchdogzustand falls er undefiniert ist
                  $ininame =  $dir."/".$file."/beelogger_ini.php";
                  if (file_exists($ininame)) include ($ininame); // Watchdog checken
                  if(!isset($ext_tage)) $ext_tage =1;
                  $filename = $dir."/".$file."/week.csv";
                  if (file_exists($filename)) { //eine week.csv gefunden
                      $sum_grz = 2.0;
                      if(is_numeric($TageswertAnzeige)) $sum_grz = $TageswertAnzeige;
                      $Aktuell = "";
                      $array = file($filename);
                      $i = sizeof($array);
                      while ($i--){
                        $what = trim($array[$i]);
                        $x = explode( ",", $what );
                        $s = sizeof($x);
                        if ($x[$s-1] !=''){
                          if(($TageswertMail == "aktiviert") && $tw_1== -1){$tz1=$x[0]; $tw_1=$i;}
                          $AktualisierungsStamp=$x[$s-1];
                          if ($Aktuell == "") $Aktuell = $AktualisierungsStamp;
                          if ($AktualisierungsStamp <= ($Aktuell - 86400*$ext_tage)) {
                            if($TageswertMail == "aktiviert"){$tz2=$x[0];$tw_2=$i;}
                            break;   //ticks = 60*60*24 = 86400  = 1 Tag
                          }
                        }// if ($x[$s-1] !='')
                      } //while
                      if(($TageswertMail == "aktiviert") && $tw_1 != -1 && $tw_2 != -1){
                          $tageswertwert = 0.0;
                          for ($a = $tw_2; $a<$tw_1;$a++){
                            $w=trim($array[$a]);$x=explode( ",", $w );
                            $w_1=trim($array[$a+1]);$x_1=explode( ",", $w_1 );
                            $d_x= $x_1[6] - $x[6];if(abs($d_x) < $sum_grz)$tageswertwert += $d_x;
                          }
                      }    
                      if ($Aktuell == "") $Aktuell = 0;

					  if (($jetzt-$Aktuell) < 86400) { //es liegen aktuelle Daten der letzten 24 Stunden vor
                        if ($Watchdog == "aktiviert") if($report>1)echo "<br>".$file.": mit Watchdog, aktuelle Daten";
                      } //((($jetzt-$Aktuell) < 86400)
                      else { //sammel Daten für heute Inaktive Beuten-Ausgabe
                        if (($Watchdog == "aktiviert") && (($jetzt-$Aktuell) < 86400*7) ){ // Watchdog aktiviert und max 7 Tage
                          if($report)echo "<br>".$dir." / ".$file.": Aktivierter Watchdog, keine aktuellen Daten";
                          $InaktivArray[$InaktivCounter] = $ininame;
                          $InaktivCounter ++;

                          $InaktivArray[$InaktivCounter] = $file;
                          $InaktivCounter ++;

                          $InaktivArray[$InaktivCounter] = date("Y-m-d H", $Aktuell);
                          $InaktivCounter ++;
                        } //if ($Watchdog == "aktiviert")
                      } // else { //sammel Daten für Inaktive Beuten-Ausgabe
                  } //if exists filename week.csv gefunden
                  else {
                    $NoCsvCounter ++; //Es wurde keine beelogger.csv-Datei gefunden
                    if ($Watchdog == "aktiviert") { //nur wenn der Watchdog aktiviert ist wird reagiert!
                        $InaktivArray[$InaktivCounter] = $ininame;
                        $InaktivCounter ++;

                        $InaktivArray[$InaktivCounter] = $file;
                        $InaktivCounter ++;

                        $InaktivArray[$InaktivCounter] = "Nie";
                        $InaktivCounter ++;
                    } //if ($Watchdog == "aktiviert")
                  } //else {$NoCsvCounter ++; //Es wurde keine beelogger.csv-Datei gefunden
              
              
            if($TageswertMail == "aktiviert"){
              if($report)echo "<br>".$dir." / ".$file.":Tageswert Mail aktiviert. <BR>";
			  // Email Informationen aus Ini auslesen
              if ($TageswertMail_adresse != "empfaenger@meineDomain.de"){ //noch keine Emailempfänger-Info gefunden
				$Empfaenger_Email = $TageswertMail_adresse;
                // Email Informationen aus Ini   $Absender_Email = "test@test.de";
                    
                $Mailbetreff = "Tageswertmeldung ";
                if($Bienenvolkbezeichnung !="auto") $Mailbetreff .=$Bienenvolkbezeichnung;
                $Absender_Name = "beelogger";

                $Message =  $dir."  ".$file."\n";
                if($Bienenvolkbezeichnung !="auto") $Message .= $Bienenvolkbezeichnung."\n";
                $tw_a = substr($tz1,0,10);
                $Message .= "Tageswert vom: ".$tw_a; // 
                $tw_a = substr($tz1,10,6);
                $Message .=  "\nletzte Daten von: ".$tw_a."\n";  //
                $Message .=  "\nTageswert ".$tageswertwert;  //
                if($tageswertwert > 200.0) $Message .= "g ?"; else $Message .= " kg";
                $Message .=  "\n\n\n".$tz2 ." ... ".$tz1;  //

                if($report)echo $Message."<BR>";
                $Header  = "From:".$Absender_Name." <".$Absender_Email.">\r\n"."Reply-To:".$Absender_Email."\r\n"."Return-Path:".$Absender_Email."\r\n";
                if($test ==0){
                  mail($Empfaenger_Email, $Mailbetreff, $Message, $Header, "-f ".$Absender_Email);
                }
              }    
            } 
            
            
                }  //if is dir file
              } // if file is not . or ..
            }// foreach


			$FetchPushUser = "meinUser"; // Initialiserung User
            $FetchPushToken = "meinToken"; // Initialiserung Token

            $FetchEmpfaenger_Email = "empfaenger@meineDomain.de"; //Initialiserung Mail
            $FetchAbsender_Email = "absender@meineDomain.de"; //Initialiserung Mail

            if ($InaktivCounter != 0) { // es gibt inaktive beelogger
                $Message = "Heute wurden folgende beelogger nicht aktualisiert: \n";  // Vorbereitung AutoWatchFunktion
                $Message .= " User/Account:  ".$dir. "\n";
                for ($b=0;$b<$InaktivCounter;$b++) {
                  include ($InaktivArray[$b]); // beeloggername holen über beelogger_ini.php
                  $b++;
                  $beeloggerNum = substr($InaktivArray[$b], -1);
                  if ($Bienenvolkbezeichnung == "auto") {
                    $Message .= $InaktivArray[$b].", ";
                  }
                  else {
                    $Message .= $InaktivArray[$b]." (".$Bienenvolkbezeichnung."), ";
                  }
                  $b++;
				  if($InaktivArray[$b] !="Nie"){
					if($report==1){echo "<br><br>";var_dump($InaktivArray); echo "<br>";}
					// Push User und token aus Ini auslesen
					if ($FetchPushToken == "meinToken") { //noch keine User-Tokeninfo gefunden
						if (($PushToken != "meinToken") AND ($PushToken != "")) $FetchPushToken = $PushToken;
					}
					if ($FetchPushUser == "meinUser") {//noch keine User-Info gefunden
						if (($PushUser != "meinUser") AND ($PushUser != "")) $FetchPushUser = $PushUser;
					}
					// Ende Push

					// Email Informationen aus Ini auselsen
					if ($FetchEmpfaenger_Email == "empfaenger@meineDomain.de"){ //noch keine Emailempfänger-Info gefunden
						if (($Empfaenger_Email != "empfaenger@meineDomain.de") AND ($Empfaenger_Email != "")) $FetchEmpfaenger_Email = $Empfaenger_Email;
					}
					if ($FetchAbsender_Email == "absender@meineDomain.de"){//noch keine Emailabsender-Info gefunden
						if (($Absender_Email != "absender@meineDomain.de") AND ($Absender_Email != "")) $FetchAbsender_Email = 	$Absender_Email;
					}
				  }
                } //for ($b=0;$b<$InaktivCounter;$b++)

// Automatischer Watchdogbereich


              if ($Message != "") {
                $Message = substr($Message,0,-2); //letztes Komma der Beutenaufzählung entfernen

//Pushnachricht erzeugen
                if (($GeneralAutowatch == "p") OR ($GeneralAutowatch == "pm")){

                  if (($FetchPushUser != "meinUser") AND ($FetchPushToken != "meinToken")) { //falls Push-Daten gefunden wurde
if($test ==0){
					curl_setopt_array($ch = curl_init(), array(
                      CURLOPT_URL => "https://api.pushover.net/1/messages.json",
                      CURLOPT_POSTFIELDS => array(
                      "token" => $FetchPushToken,
                      "user" => $FetchPushUser,
                      "message" => $Message
                      ),
                      CURLOPT_SAFE_UPLOAD => true,
                      CURLOPT_RETURNTRANSFER => true
                      )
                    );
                    curl_exec($ch);
                    curl_close($ch);
}
                    if(1==1)echo "<br>Pushnachricht gesendet.<br>Inhalt der Pushnachricht: ".$Message."<br>";
                  } //if ($PushUser != ""){ //falls User eingegeben wurde
                  else if($report)echo "<br>Pushnachricht aktiviert aber KEINE Pushdaten gefunden.";
                } //if (($ext_autowatch == "p") OR ($ext_autowatch == "p+m"))

//Mailnachricht erzeugen
                if (($GeneralAutowatch == "m") OR ($GeneralAutowatch == "pm")) {
                    $Absender_Email = $FetchAbsender_Email;
                    $Empfaenger_Email = $FetchEmpfaenger_Email;
                    if (($Empfaenger_Email != "empfaenger@meineDomain.de") AND ($Empfaenger_Email != "") AND ($Absender_Email != "absender@meineDomain.de") AND ($Absender_Email != "")) {
                        $Mailbetreff = "Beeloggerausfallmeldung: ";
                        $Absender_Name = "beelogger-watchdog";
                        //$Header  = "From:".$Absender_Name." <".$Absender_Email.">\n";
                        $Header  = "From:".$Absender_Name." <".$Absender_Email.">\r\n"."Reply-To:".$Absender_Email."\r\n"."Return-Path:".$Absender_Email."\r\n";//27.01.2023
if($test ==0){
						mail($Empfaenger_Email, $Mailbetreff, $Message, $Header, "-f ".$Absender_Email);
}

                      if($report){
						echo "<br>Mail von: ".$Absender_Email." an : ".$Empfaenger_Email;
						echo " mit folgendem Inhalt: <br>".$Message."<br>";
					  }
                    }//if ($Empfaenger_Email != "empfaenger@meineDomain.de")
                    else if($report)echo "<br>Informationen bzw Daten zur E-Mail wurden nicht gefunden!";
                } // if (($ext_autowatch == "m") OR ($ext_autowatch == "p+m"))
 			  } //if ($Message != !!)
            } //if ($InaktivCounter != 0)
            if($report>1)echo "<br>";

          } //if (($GeneralAutowatch =="p") OR ($GeneralAutowatch =="m") ($GeneralAutowatch =="pm")){
          else if($report>1)echo "<font color='red'><br> Scanne den Ordner: ".$Mainfile."...nichts aktiviert!";
        }
    }
  }
}

?>

