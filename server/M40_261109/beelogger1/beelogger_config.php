<?php
/*
 * (C) 2022 Jeremias Bruker, Thorsten Gurzan, Rudolf Schick - beelogger.de
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
*/
//  beelogger.de - Arduino Datenlogger für Imker
//  Erläuterungen dieses Programmcodes unter https://beelogger.de

$Konfigversion="M.40";//vom 07.11.2026 - beelogger_config.php
$show_version=0; //Programmertool

if(!isset($ConfigSichern)){echo "sorry!";return;}

if($show_version){
 error_reporting(E_ALL);
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
}else error_reporting(0);

$file_a="../beelogger_sprachfiles/Show_Sprache_".$Sprache.".php";
if(file_exists($file_a)) include($file_a); // Sprache
$file_b="../beelogger_sprachfiles/Konfig_Sprache_".$Sprache.".php";
if(file_exists($file_b)) include($file_b); // Sprache
$file_c="../beelogger_sprachfiles/Hilfe_Sprache_".$Sprache.".php";
if(file_exists($file_c)) include($file_c); // Sprache

$cfg_Message=""; // Config Message for beelogger_show


$NeuAbsender_Email="absender@meineDomain.de";
$NeuEmpfaenger_Email="empfaenger@meineDomain.de";
$reinit_emailindataphp=0;

$CommunityUser=false;
if($_SERVER['SERVER_NAME']=="community.beelogger.de"){
  $CommunityUser=true;
  //Email von beeloggerserver nur für CommunityUser
  include("../data.php"); // holt Anmeldedaten
  if(isset($Email) and $Email!="")$NeuEmpfaenger_Email=$Email;//aus data.php
  $NeuAbsender_Email="alarm@beelogger.de";
}

if(isset($_POST['neuempfaenger_email']))if (filter_var($_POST['neuempfaenger_email'], FILTER_VALIDATE_EMAIL)) {$NeuEmpfaenger_Email=$_POST['neuempfaenger_email'];
str_replace(array("#", "'", ";"), '', $NeuEmpfaenger_Email);$reinit_emailindataphp=1;}
if(isset($_POST['neuabsender_email']))if (filter_var($_POST['neuabsender_email'], FILTER_VALIDATE_EMAIL)) {$NeuAbsender_Email=$_POST['neuabsender_email'];
str_replace(array("#", "'", ";"), '', $NeuAbsender_Email);}

if($reinit_emailindataphp and $CommunityUser){
  //set new Email Adresse von beeloggerserver nur für CommunityUser
  $str=file_get_contents("../data.php");
  $str=str_replace($Email, $NeuEmpfaenger_Email,$str);
  file_put_contents("../data.php", $str);
}

//######################################
if(!file_exists("beelogger_ini.php") OR (file_exists("pw.php")) OR ($ConfigSichern=="1")){

//##### INIT ALL VARS
 $NeuBeelogger1_List="aktiviert";
 $NeuBienenvolkbezeichnung="auto";
 $NeuStandardCSVDatei="week.csv";
 $NeuPunktAnzeige="false";
 $NeuUnderlay="7";
 $NeuUnderlay_Mo="6";
 $NeuUnderlay_Ab="18";
 $NeuUnderlay_Col="FFFFA4";
 $NeuTageswertOption=array("");
 $NeuTageswertAnzeige="false";
 $NeuTageswertAchse="y";
 $NeuTageswertMail="deaktiviert";
 $NeuTageswertMail_adresse="empfaenger@meineDomain.de";
 $NeuLegende="immer";
 $NeuRollPeriod="1";
 $NeuTwoGrid="false";
 $NeuChartMobile="3";
 $NeuZoom="0";
 $NeuSumme="false";
 $NeuBhome=0;
 $NeuGTS_color="false";
 $NeuBasisGewichtBeute="";
 $NeuBasisGewichtBeute1="";
 $NeuBasisGewichtBeute2="";
 $NeuBasisGewichtBeute3="";
 $NeuBasisGewichtBeute4="";
 $NeuBasisGewichtBeute5="";
 $NeuBasisGewichtBeute6="";

 $NeuAutoAnmerkungenErzeugen="deaktiviert";
 $NeuAnmerkungGewichtsDifferenz="1.0";
 $NeuAnmerkungGewichtsDifferenzMax="5.0";
 $NeuAnmerkungZeitDifferenz="15";
 $NeuAutoServiceAnmerkung=false;
 $NeuAutoServiceAnmerkungZeit=5;

 $NeuIntervallSendeSteuerung="zeitgesteuert";
 $NeuSommerBeginn="3";
 $NeuSommerTagZeit="6";
 $NeuSommerSendeIntervallTag="15";
 $NeuSommerNachtZeit="22";
 $NeuSommerSendeIntervallNacht="60";
 $NeuWinterBeginn="deaktiviert";
 $NeuWinterSendeIntervall="90";

 $NeuEESendeIntervall="B";

 $NeuKorrekturwert="0.00";
 $NeuKorrekturwert1="0.00";
 $NeuKorrekturwert2="0.00";
 $NeuKorrekturwert3="0.00";
 $NeuKorrekturwert4="0.00";
 $NeuKorrekturwert5="0.00";
 $NeuKorrekturwert6="0.00";
 $NeuKorrPressure="0.0";

 $NeuKalibrierTemperatur="";

 $NeuWatchdog="deaktiviert";
 $NeuGeneralAutoWatch="deaktiviert";
 $NeuGeneralAutoWatchTime="Alle";

 $NeuBeutenLeergewicht="0.00";
 $NeuHonigraum1Anzahl="0";
 $NeuHonigraum1Leergewicht="0.00";
 $NeuHonigraum2Anzahl="0";
 $NeuHonigraum2Leergewicht="0.00";

 $NeuBeutenGewichtAktiviert0="";
 $NeuBeutenGewichtAktiviert1="";
 $NeuBeutenGewichtAktiviert2="";
 $NeuBeutenGewichtAktiviert3="";
 $NeuBeutenGewichtAktiviert4="";
 $NeuBeutenGewichtAktiviert5="";
 $NeuBeutenGewichtAktiviert6="";
 $NeuBeutenGewichtAktiviert7="";
 $NeuBeutenGewichtAktiviert8="";
 $NeuBeutenGewicht0="";
 $NeuBeutenGewicht1="";
 $NeuBeutenGewicht2="";
 $NeuBeutenGewicht3="";
 $NeuBeutenGewicht4="";
 $NeuBeutenGewicht5="";
 $NeuBeutenGewicht6="";
 $NeuBeutenGewicht7="";
 $NeuBeutenGewicht8="";
 $NeuBeutenGewichtBezeichner0="";
 $NeuBeutenGewichtBezeichner1="";
 $NeuBeutenGewichtBezeichner2="";
 $NeuBeutenGewichtBezeichner3="";
 $NeuBeutenGewichtBezeichner4="";
 $NeuBeutenGewichtBezeichner5="";
 $NeuBeutenGewichtBezeichner6="";
 $NeuBeutenGewichtBezeichner7="";
 $NeuBeutenGewichtBezeichner8="";

 $NeuUtil1="";
 $NeuUtil2="";
 $NeuUtil3="";
 $NeuUtil1Leergewicht="0.0";
 $NeuUtil2Leergewicht="0.0";
 $NeuUtil3Leergewicht="0.0";

 $NeuNextService="10";

 $NeuAkkuLeerSchwelle="3.8";
 $NeuAkkuVollSchwelle="4.0";

 $NeuEmailSchwarmAlarm="deaktiviert";
 $NeuEmailSchwarmAlarm1="deaktiviert";
 $NeuEmailSchwarmAlarm2="deaktiviert";
 $NeuEmailSchwarmAlarm3="deaktiviert";
 $NeuEmailSchwarmAlarm4="deaktiviert";
 $NeuEmailSchwarmAlarm5="deaktiviert";
 $NeuEmailSchwarmAlarm6="deaktiviert";

 $NeuPushSchwarmPriority1="0";
 $NeuPushSchwarmPriority2="0";
 $NeuPushSchwarmPriority3="0";
 $NeuPushSchwarmPriority4="0";
 $NeuPushSchwarmPriority5="0";
 $NeuPushSchwarmPriority6="0";

 $NeuReferenzZeit="5";
 $NeuReferenzZeit1="6";
 $NeuReferenzZeit2="7";
 $NeuReferenzZeit3="8";
 $NeuReferenzZeit4="9";
 $NeuReferenzZeit5="10";
 $NeuReferenzZeit6="11";

 $NeuDifferenzGewicht="1.00";
 $NeuDifferenzGewicht1="1.01";
 $NeuDifferenzGewicht2="1.02";
 $NeuDifferenzGewicht3="1.03";
 $NeuDifferenzGewicht4="1.04";
 $NeuDifferenzGewicht5="1.05";
 $NeuDifferenzGewicht6="1.06";

 $NeuDifferenzGewichtMax="4.00";
 $NeuDifferenzGewicht1Max="4.01";
 $NeuDifferenzGewicht2Max="4.02";
 $NeuDifferenzGewicht3Max="4.03";
 $NeuDifferenzGewicht4Max="4.04";
 $NeuDifferenzGewicht5Max="4.05";
 $NeuDifferenzGewicht6Max="4.06";

 $NeuPushSchwarmAlarm="deaktiviert";
 $NeuPushSchwarmAlarm1="deaktiviert";
 $NeuPushSchwarmAlarm2="deaktiviert";
 $NeuPushSchwarmAlarm3="deaktiviert";
 $NeuPushSchwarmAlarm4="deaktiviert";
 $NeuPushSchwarmAlarm5="deaktiviert";
 $NeuPushSchwarmAlarm6="deaktiviert";
 $NeuPushSchwarmPriority0="0";
 $NeuPushSchwarmPriority1="0";
 $NeuPushSchwarmPriority2="0";
 $NeuPushSchwarmPriority3="0";
 $NeuPushSchwarmPriority4="0";
 $NeuPushSchwarmPriority5="0";
 $NeuPushSchwarmPriority6="0";

 $NeuTriggerAlarmAnmerkung1="";
 $NeuTriggerAlarmAnmerkung2="";
 $NeuTriggerAlarmAnmerkung3="";
 $NeuTriggerAlarmAnmerkung4="";
 $NeuTriggerAlarmPause1="";
 $NeuTriggerAlarmPause2="";
 $NeuTriggerAlarmPause3="";
 $NeuTriggerAlarmPause4="";
 $NeuLastTriggerOption1="";
 $NeuLastTriggerOption2="";
 $NeuLastTriggerOption3="";
 $NeuLastTriggerOption4="";
 $NeuTriggerAlarmEmail1="";
 $NeuTriggerAlarmEmail2="";
 $NeuTriggerAlarmEmail3="";
 $NeuTriggerAlarmEmail4="";
 $NeuTriggerAlarmPush1="";
 $NeuTriggerAlarmPush2="";
 $NeuTriggerAlarmPush3="";
 $NeuTriggerAlarmPush4="";
 $NeuTriggerAlarmSensor1="";
 $NeuTriggerAlarmSensor2="";
 $NeuTriggerAlarmSensor3="";
 $NeuTriggerAlarmSensor4="";
 $NeuTriggerAlarmZeichen1="";
 $NeuTriggerAlarmZeichen2="";
 $NeuTriggerAlarmZeichen3="";
 $NeuTriggerAlarmZeichen4="";
 $NeuTriggerAlarmWert1="";
 $NeuTriggerAlarmWert2="";
 $NeuTriggerAlarmWert3="";
 $NeuTriggerAlarmWert4="";
 $NeuTriggerAlarmZeichen1="";
 $NeuTriggerAlarmZeichen2="";
 $NeuTriggerAlarmZeichen3="";
 $NeuTriggerAlarmZeichen4="";

 $NeuPushToken="PushToken";
 $NeuPushUser="PushUser";
 $NeuT_Token="Token";
 $NeuT_Chat_Id="Id";

 $NeuPushPriority1="0";
 $NeuPushPriority2="0";
 $NeuPushPriority3="0";
 $NeuPushPriority4="0";

 $NeuBeep="deaktiviert";
 $NeuBeep1="deaktiviert";
 $NeuBeep2="deaktiviert";
 $NeuBeep3="deaktiviert";
 $NeuBeep4="deaktiviert";
 $NeuBeep5="deaktiviert";
 $NeuBeep6="deaktiviert";

 $NeuBeepId="meine BeepID";
 $NeuBeepId1="meine BeepID";
 $NeuBeepId2="meine BeepID";
 $NeuBeepId3="meine BeepID";
 $NeuBeepId4="meine BeepID";
 $NeuBeepId5="meine BeepID";
 $NeuBeepId6="meine BeepID";

 $NeuKeeper="deaktiviert";
 $NeuKeeper1="deaktiviert";
 $NeuKeeper2="deaktiviert";
 $NeuKeeper3="deaktiviert";
 $NeuKeeper4="deaktiviert";
 $NeuKeeper5="deaktiviert";
 $NeuKeeper6="deaktiviert";

 $NeuKeeperId="meine KeeperID";
 $NeuKeeperId1="meine KeeperID";
 $NeuKeeperId2="meine KeeperID";
 $NeuKeeperId3="meine KeeperID";
 $NeuKeeperId4="meine KeeperID";
 $NeuKeeperId5="meine KeeperID";
 $NeuKeeperId6="meine KeeperID";

 $NeuThingsPeakChannel="meine ChannelID";
 $NeuThingsPeak="deaktiviert";
 $NeuThingsPeak1="deaktiviert";
 $NeuThingsPeak2="deaktiviert";
 $NeuThingsPeak3="deaktiviert";
 $NeuThingsPeak4="deaktiviert";
 $NeuThingsPeak5="deaktiviert";
 $NeuThingsPeak6="deaktiviert";

 $NeuThingsPeakApi="meine Api";
 $NeuThingsPeakApi1="meine Api";
 $NeuThingsPeakApi2="meine Api";
 $NeuThingsPeakApi3="meine Api";
 $NeuThingsPeakApi4="meine Api";
 $NeuThingsPeakApi5="meine Api";
 $NeuThingsPeakApi6="meine Api";

 $NeuThingsPeakSensor1=0;
 $NeuThingsPeakSensor2=0;
 $NeuThingsPeakSensor3=0;
 $NeuThingsPeakSensor4=0;
 $NeuThingsPeakSensor5=0;
 $NeuThingsPeakSensor6=0;
 $NeuThingsPeakSensor7=0;
 $NeuThingsPeakSensor8=0;

 $NeubeeloggerMap2="";
 $NeubeeloggerMap2Location="";
 $NeubeeloggerMap2Status="";

 $NeubeeloggerMap2Teilnahme="";
 $NeubeeloggerMap2Lat="00.00";
 $NeubeeloggerMap2Lon="00.00";
 $NeubeeloggerMap2BeeloggerType="";
 $NeubeeloggerMap2Connect="";
 $NeubeeloggerMap2Email="";
 $NeubeeloggerMap2URL="";
 $NeubeeloggerMap2Sensor1="";
 $NeubeeloggerMap2Sensor2="";
 $NeubeeloggerMap2Sensor3="";
 $NeubeeloggerMap2Sensor4="";
 $NeubeeloggerMap2Sensor5="";
 $NeubeeloggerMap2Sensor6="";
 $NeubeeloggerMapWaage="0";
 $NeubeeloggerMap1ToMap2="0";
 $beeloggerMap2LoescheBeeID="";

 $NeuInfo=""; //Informationen und Notizen
 $NeuKoeAnzeige="deaktiviert";
 $NeuKoeInfo="";
 $NeuKoeKaefig="";

 $NeumobileWatch_Show="";
 $NeumobileWatch_Sort="Ordner";
 $NeumobileWatch_tage="7";
 $NeumobileWatch_spalten="2";
 $NeumobileWatch_notes="";
 $NeumobileWatch_tageswertanzeige="deaktiviert";
 $NeumobileWatch_roll="1";
 $NeumobileWatch_sensor1="6";
 $NeumobileWatch_sensor2="";
 $NeumobileWatch_legende="always";
 $NeumobileWatch_twogrid="0";
 $NeumobileWatch_one_y="0";
 $NeumobileCheck_Show="";
 $NeuMobileCheckSensor=array();
 $NeubeeloggerNamenSortierung="auto";

 $NeuSprache="1"; //default Deutsch
 $NeubeeloggerSketchID="?";

 $NeuSensorNeu="";
 $NeuSensor=array();
 $NeuUseIcon=array();
 $NeuFillGraph=array();

 $NeuWetterIcons="";
 $NeuOpenweathermapKey="";
 $NeuXanalyseArray=array("","","","","");
 $NeuXanalyseNeu="";

 $SchwarmalarmLoeschenArray=array("0","0","0","0","0","0");
 $AnzahlSensoren=0;
 $CsvLoeschDatei="";
 $CsvDownloadDatei="";
 $StandortLoeschen="";
 // end init vars


if($ConfigSichern=="1"){ //configsichern Teil 1 Daten lesen, wenn Konfig komplett gespeichert wurde

if(isset($_POST['neubeeloggershowpasswort']))$NeuBeeloggerShowPasswort=$_POST['neubeeloggershowpasswort'];
$CheckNeuBeeloggerShowPasswort=htmlentities(strip_tags(stripslashes($NeuBeeloggerShowPasswort)));

if(!preg_match("#^[a-zA-Z0-9\-\!\*\(\)\;\:\.\@\&\=\+\,\/\?\%\[\]\_\#]+$#", $NeuBeeloggerShowPasswort) OR ($NeuBeeloggerShowPasswort !=$CheckNeuBeeloggerShowPasswort) ){
 msg_box_box( $NeuBeeloggerShowPasswort);
 if(file_exists("beelogger_ini.php")) include("beelogger_ini.php"); // Werte neu einlesen
 $NeuBeeloggerShowPasswort=$BeeloggerShowPasswort; // zurücksetzen
}
if($NeuBeeloggerShowPasswort==""){
 $NeuBeeloggerShowPasswort="Show";
}
if($NeuBeeloggerShowPasswort=="Show"){
 $ConfigSichern=0;
 echo'<script language="javascript">';
 echo'alert("Configuration stop\n\nPasswort: >>> Show <<< not allowed")';
 echo'</script>';
}



if(isset($_POST['neubeeloggerlogpasswort'])){
 $NeuBeeloggerLogPasswort=$_POST['neubeeloggerlogpasswort'];
 $CheckLogPasswort=htmlentities(strip_tags(stripslashes($NeuBeeloggerLogPasswort)));
 if(!preg_match("#^[a-zA-Z0-9\!\*\=\+\-\[\]]+$#", $NeuBeeloggerLogPasswort) OR ($NeuBeeloggerLogPasswort !=$CheckLogPasswort) ){
 msg_box_box( $NeuBeeloggerLogPasswort.'\n\n ... : _ ! * ( ) ; : @ &=+ $ , / ? % # [ ] § ~ \\ ä ü ö ...\n\n set to >>Log<<');
 $NeuBeeloggerLogPasswort="Log"; // zurücksetzen
 }
}

//POST-Variablen zum Logdateisplitten
if(isset($_POST['erstessplitdatum']))$ErstesSplitDatum=$_POST['erstessplitdatum'];
if(isset($_POST['letztessplitdatum']))$LetztesSplitDatum=$_POST['letztessplitdatum'];
if(isset($_POST['aktuellessplitdatum']))$AktuellesSplitDatum=$_POST['aktuellessplitdatum'];
if(isset($_POST['splittensichern']))$SplittenSichern=$_POST['splittensichern'];
else $SplittenSichern="0";
if(isset($_POST['archivdateiname'])){
	$ArchivDateiName=$_POST['archivdateiname'];
 if(!preg_match("#^[a-zA-Z0-9,\#\_]+$#", $_POST['archivdateiname'])) {
	 msg_box_box( $ArchivDateiName);
	 $SplittenSichern="0";
}}
//POST-Variablen zum beelogger anlegen / loeschen
$BeeloggerLoeschen="0";
$BeeloggerCSVReset="0";
$NeubeeloggerAnlage="";
if(isset($_POST['beeloggerloeschen']))$BeeloggerLoeschen=$_POST['beeloggerloeschen'];
if(isset($_POST['csvreset']))$BeeloggerCSVReset=$_POST['csvreset'];
if(isset($_POST['beeloggeranlage']))$NeubeeloggerAnlage=$_POST['beeloggeranlage'];
if(isset($_POST['beeloggeranlagepasswort']))$NeubeeloggerAnlagePasswort=$_POST['beeloggeranlagepasswort'];

if(isset($_POST['alteanzahlsensoren']))$AnzahlSensoren=$_POST['alteanzahlsensoren'];

if(isset($_POST['schwarmalarmloeschen']))$SchwarmalarmLoeschen=$_POST['schwarmalarmloeschen'];
if(isset($_POST['schwarmalarmloeschen0']))$SchwarmalarmLoeschenArray[0]=$_POST['schwarmalarmloeschen0'];
if(isset($_POST['schwarmalarmloeschen1'])){
 $SchwarmalarmLoeschenArray[1]=$_POST['schwarmalarmloeschen1'];
 if($SchwarmalarmLoeschen=="1")$SchwarmalarmLoeschenArray[1]="1";
}
if(isset($_POST['schwarmalarmloeschen2']))$SchwarmalarmLoeschenArray[2]=$_POST['schwarmalarmloeschen2'];
if(isset($_POST['schwarmalarmloeschen3']))$SchwarmalarmLoeschenArray[3]=$_POST['schwarmalarmloeschen3'];
if(isset($_POST['schwarmalarmloeschen4']))$SchwarmalarmLoeschenArray[4]=$_POST['schwarmalarmloeschen4'];
if(isset($_POST['schwarmalarmloeschen5']))$SchwarmalarmLoeschenArray[5]=$_POST['schwarmalarmloeschen5'];
if(isset($_POST['schwarmalarmloeschen6']))$SchwarmalarmLoeschenArray[6]=$_POST['schwarmalarmloeschen6'];

if(isset($_POST['neubienenvolkbezeichnung'])) $NeuBienenvolkbezeichnung=htmlentities(strip_tags(stripslashes($_POST['neubienenvolkbezeichnung'])));
if(isset($_POST['neustandardcsvdatei']))$NeuStandardCSVDatei=htmlentities(strip_tags(stripslashes($_POST['neustandardcsvdatei'])));
if(isset($_POST['neupunktanzeige'])) $NeuPunktAnzeige=htmlentities(strip_tags(stripslashes($_POST['neupunktanzeige'])));
if(isset($_POST['neuunderlay'])) $NeuUnderlay=htmlentities(strip_tags(stripslashes($_POST['neuunderlay'])));
if(isset($_POST['neuunderlaymo'])) $NeuUnderlay_Mo=htmlentities(strip_tags(stripslashes($_POST['neuunderlaymo'])));
if(isset($_POST['neuunderlayab'])) $NeuUnderlay_Ab=htmlentities(strip_tags(stripslashes($_POST['neuunderlayab'])));
if(isset($_POST['neuunderlaycol'])&&preg_match("#^[0-9,A-F,a-f]+$#",$_POST['neuunderlaycol'])) {if(strlen($_POST['neuunderlaycol'])==6) $NeuUnderlay_Col=htmlentities(strip_tags(stripslashes($_POST['neuunderlaycol'])));}

if(isset($_POST['tageswertmail'])) $NeuTageswertMail=$_POST['tageswertmail'];
if(isset($_POST['tageswertmail_adresse'])) if (filter_var($_POST['tageswertmail_adresse'], FILTER_VALIDATE_EMAIL)) {$NeuTageswertMail_adresse=$_POST['tageswertmail_adresse'];
str_replace(array("#", "'", ";"), '', $NeuTageswertMail_adresse);}
if($NeuTageswertMail_adresse=="empfaenger@meineDomain.de")$NeuTageswertMail="deaktiviert";
if(isset($_POST['tageswertanzeige'])) $NeuTageswertAnzeige=htmlentities(strip_tags(stripslashes($_POST['tageswertanzeige'])));
if(isset($_POST['tageswertachse'])) $NeuTageswertAchse=htmlentities(strip_tags(stripslashes($_POST['tageswertachse'])));
if(isset($_POST['neulegende']))$NeuLegende=htmlentities(strip_tags(stripslashes($_POST['neulegende'])));
if(isset($_POST['neutwogrid']))$NeuTwoGrid=htmlentities(strip_tags(stripslashes($_POST['neutwogrid'])));
if(isset($_POST['neuchartmobile']))$NeuChartMobile=htmlentities(strip_tags(stripslashes($_POST['neuchartmobile'])));
if(isset($_POST['neugts_color']))$NeuGTS_color=htmlentities(strip_tags(stripslashes($_POST['neugts_color'])));if(isset($_POST['neugts_color']))$NeuGTS_color=htmlentities(strip_tags(stripslashes($_POST['neugts_color'])));
if(isset($_POST['neuzoom']))$NeuZoom=htmlentities(strip_tags(stripslashes($_POST['neuzoom'])));
if(isset($_POST['neusumme']))$NeuSumme=htmlentities(strip_tags(stripslashes($_POST['neusumme'])));
if(isset($_POST['neubhome']))$NeuBhome=htmlentities(strip_tags(stripslashes($_POST['neubhome'])));
if(isset($_POST['rollperiod']))$NeuRollPeriod=htmlentities(strip_tags(stripslashes($_POST['rollperiod'])));
if(isset($_POST['csvloeschdatei'])) $CsvLoeschDatei=htmlentities(strip_tags(stripslashes($_POST['csvloeschdatei'])));
if(isset($_POST['csvdownloaddatei'])) $CsvDownloadDatei=htmlentities(strip_tags(stripslashes($_POST['csvdownloaddatei'])));
if(isset($_POST['neuintervallsendesteuerung'])) $NeuIntervallSendeSteuerung=htmlentities(strip_tags(stripslashes($_POST['neuintervallsendesteuerung'])));
if(isset($_POST['neusommerbeginn'])) $NeuSommerBeginn=htmlentities(strip_tags(stripslashes($_POST['neusommerbeginn'])));
if(isset($_POST['neusommertagzeit'])) $NeuSommerTagZeit=htmlentities(strip_tags(stripslashes($_POST['neusommertagzeit'])));
if(isset($_POST['neusommersendeintervalltag'])) $NeuSommerSendeIntervallTag=htmlentities(strip_tags(stripslashes($_POST['neusommersendeintervalltag'])));
if(isset($_POST['neusommernachtzeit'])) $NeuSommerNachtZeit=htmlentities(strip_tags(stripslashes($_POST['neusommernachtzeit'])));
if(isset($_POST['neusommersendeintervallnacht'])) $NeuSommerSendeIntervallNacht=htmlentities(strip_tags(stripslashes($_POST['neusommersendeintervallnacht'])));
if(isset($_POST['neuwinterbeginn'])) $NeuWinterBeginn=htmlentities(strip_tags(stripslashes($_POST['neuwinterbeginn'])));
if(isset($_POST['neuwintersendeintervall'])) $NeuWinterSendeIntervall=htmlentities(strip_tags(stripslashes($_POST['neuwintersendeintervall'])));
if(isset($_POST['neueesendeintervall'])) $NeuEESendeIntervall=htmlentities(strip_tags(stripslashes($_POST['neueesendeintervall'])));

if(isset($_POST['neukorrekturwert'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert'],$val)){
$NeuKorrekturwert=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert=str_replace(",", ".",$NeuKorrekturwert);}
if(isset($_POST['neukorrekturwert1'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert1'],$val)){
$NeuKorrekturwert1=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert1=str_replace(",", ".",$NeuKorrekturwert1);}

if(isset($_POST['neukorrekturwert2'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert2'],$val)){
$NeuKorrekturwert2=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert2=str_replace(",", ".",$NeuKorrekturwert2);}

if(isset($_POST['neukorrekturwert3'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert3'],$val)){
$NeuKorrekturwert3=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert3=str_replace(",", ".",$NeuKorrekturwert3);}

if(isset($_POST['neukorrekturwert4'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert4'],$val)){
$NeuKorrekturwert4=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert4=str_replace(",", ".",$NeuKorrekturwert4);}

if(isset($_POST['neukorrekturwert5'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert5'],$val)){
$NeuKorrekturwert5=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert5=str_replace(",", ".",$NeuKorrekturwert5);}

if(isset($_POST['neukorrekturwert6'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrekturwert6'],$val)){
$NeuKorrekturwert6=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrekturwert6=str_replace(",", ".",$NeuKorrekturwert6);}

if(isset($_POST['neukalibriertemperatur'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukalibriertemperatur'],$val)){ $NeuKalibrierTemperatur=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKalibrierTemperatur=str_replace(",", ".",$NeuKalibrierTemperatur);}
if(isset($_POST['neukorrpressure'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neukorrpressure'],$val)){
$NeuKorrPressure=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuKorrPressure=str_replace(",", ".",$NeuKorrPressure);}


if(isset($_POST['neuwatchdog'])) $NeuWatchdog=htmlentities(strip_tags(stripslashes($_POST['neuwatchdog'])));
if(isset($_POST['neugeneralautowatch'])) $NeuGeneralAutoWatch=htmlentities(strip_tags(stripslashes($_POST['neugeneralautowatch'])));
if(isset($_POST['neugeneralautowatchtime'])) $NeuGeneralAutoWatchTime=htmlentities(strip_tags(stripslashes($_POST['neugeneralautowatchtime'])));

//$NeuBeutenLeergewicht=htmlentities(strip_tags(stripslashes($_POST['neubeutenleergewicht'])));
//$NeuBeutenLeergewicht=str_replace(",", ".",$NeuBeutenLeergewicht);

if(isset($_POST['neubeutengewicht0'])){ $NeuBeutenGewicht0=htmlentities(strip_tags(stripslashes($_POST['neubeutengewicht0'])));
$NeuBeutenGewicht0=str_replace(",",".",$NeuBeutenGewicht0);}

if(isset($_POST['neubeutengewichtbezeichner1'])) $NeuBeutenGewichtBezeichner1=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner1'])));
if(isset($_POST['neubeutengewichtaktiviert1'])) $NeuBeutenGewichtAktiviert1=$_POST['neubeutengewichtaktiviert1'];
if(isset($_POST['neubeutengewicht1'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht1'],$val)){
 $NeuBeutenGewicht1=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht1=str_replace(",",".",$NeuBeutenGewicht1);}

if(isset($_POST['neubeutengewichtbezeichner2'])) $NeuBeutenGewichtBezeichner2=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner2'])));
if(isset($_POST['neubeutengewichtaktiviert2'])) $NeuBeutenGewichtAktiviert2=$_POST['neubeutengewichtaktiviert2'];
if(isset($_POST['neubeutengewicht2'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht2'],$val)){
 $NeuBeutenGewicht2=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht2=str_replace(",",".",$NeuBeutenGewicht2);}

if(isset($_POST['neubeutengewichtbezeichner3'])) $NeuBeutenGewichtBezeichner3=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner3'])));
if(isset($_POST['neubeutengewichtaktiviert3'])) $NeuBeutenGewichtAktiviert3=$_POST['neubeutengewichtaktiviert3'];
if(isset($_POST['neubeutengewicht3'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht3'],$val)){
 $NeuBeutenGewicht3=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht3=str_replace(",",".",$NeuBeutenGewicht3);}

if(isset($_POST['neubeutengewichtbezeichner4'])) $NeuBeutenGewichtBezeichner4=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner4'])));
if(isset($_POST['neubeutengewichtaktiviert4'])) $NeuBeutenGewichtAktiviert4=$_POST['neubeutengewichtaktiviert4'];
if(isset($_POST['neubeutengewicht4'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht4'],$val)){
 $NeuBeutenGewicht4=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht4=str_replace(",",".",$NeuBeutenGewicht4);}

if(isset($_POST['neubeutengewichtbezeichner5'])) $NeuBeutenGewichtBezeichner5=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner5'])));
if(isset($_POST['neubeutengewichtaktiviert5'])) $NeuBeutenGewichtAktiviert5=$_POST['neubeutengewichtaktiviert5'];
if(isset($_POST['neubeutengewicht5'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht5'],$val)){
 $NeuBeutenGewicht5=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht5=str_replace(",",".",$NeuBeutenGewicht5);}

if(isset($_POST['neubeutengewichtbezeichner6'])) $NeuBeutenGewichtBezeichner6=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner6'])));
if(isset($_POST['neubeutengewichtaktiviert6'])) $NeuBeutenGewichtAktiviert6=$_POST['neubeutengewichtaktiviert6'];
if(isset($_POST['neubeutengewicht6'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht6'],$val)){
 $NeuBeutenGewicht6=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht6=str_replace(",",".",$NeuBeutenGewicht6);}

if(isset($_POST['neubeutengewichtbezeichner7'])) $NeuBeutenGewichtBezeichner7=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner7'])));
if(isset($_POST['neubeutengewichtaktiviert7'])) $NeuBeutenGewichtAktiviert7=$_POST['neubeutengewichtaktiviert7'];
if(isset($_POST['neubeutengewicht7'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht7'],$val)){
 $NeuBeutenGewicht7=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht7=str_replace(",",".",$NeuBeutenGewicht7);}if(isset($_POST['neubeutengewichtbezeichner7'])) $NeuBeutenGewichtBezeichner7=htmlentities(strip_tags(stripslashes($_POST['neubeutengewichtbezeichner7'])));

if(isset($_POST['neubeutengewichtaktiviert8'])) $NeuBeutenGewichtAktiviert6=$_POST['neubeutengewichtaktiviert8'];
if(isset($_POST['neubeutengewicht8'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeutengewicht8'],$val)){
 $NeuBeutenGewicht8=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuBeutenGewicht8=str_replace(",",".",$NeuBeutenGewicht8);}

if(isset($_POST['neuhonigraum1anzahl'])) $NeuHonigraum1Anzahl=htmlentities(strip_tags(stripslashes($_POST['neuhonigraum1anzahl'])));
if(isset($_POST['neuhonigraum1leergewicht'])&&preg_match("#^[0-9,\.]+$#",$_POST['neuhonigraum1leergewicht'],$val)){
 $NeuHonigraum1Leergewicht=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuHonigraum1Leergewicht=str_replace(',' , "." ,$NeuHonigraum1Leergewicht);}

if(isset($_POST['neuhonigraum2anzahl'])) $NeuHonigraum2Anzahl=htmlentities(strip_tags(stripslashes($_POST['neuhonigraum2anzahl'])));
if(isset($_POST['neuhonigraum2leergewicht'])&&preg_match("#^[0-9,\.]+$#",$_POST['neuhonigraum2leergewicht'],$val)){
 $NeuHonigraum2Leergewicht=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuHonigraum2Leergewicht=str_replace(",", ".",$NeuHonigraum2Leergewicht);}


if(isset($_POST['neuutil1']))$NeuUtil1=htmlentities(strip_tags(stripslashes($_POST['neuutil1'])));
if(isset($_POST['neuutil1leergewicht'])&&preg_match("#^[0-9,\.]+$#",$_POST['neuutil1leergewicht'],$val)){
 $NeuUtil1Leergewicht=htmlentities(strip_tags(stripslashes(implode(",",$val)))); $NeuUtil1Leergewicht=str_replace(",", ".",$NeuUtil1Leergewicht);}

if(isset($_POST['neuutil2']))$NeuUtil2=htmlentities(strip_tags(stripslashes($_POST['neuutil2'])));
if(isset($_POST['neuutil2leergewicht'])&&preg_match("#^[0-9,\.]+$#",$_POST['neuutil2leergewicht'],$val)){
 $NeuUtil2Leergewicht=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuUtil2Leergewicht=str_replace(",", ".",$NeuUtil2Leergewicht);}

if(isset($_POST['neuutil3']))$NeuUtil3=htmlentities(strip_tags(stripslashes($_POST['neuutil3'])));
if(isset($_POST['neuutil3leergewicht'])&&preg_match("#^[0-9,\.]+$#",$_POST['neuutil3leergewicht'],$val)){
 $NeuUtil3Leergewicht=htmlentities(strip_tags(stripslashes(implode(",",$val))));$NeuUtil3Leergewicht=str_replace(",", ".",$NeuUtil3Leergewicht);}

if(isset($_POST['neuakkuschwellen'])){ $NeuAkkuSchwellen=htmlentities(strip_tags(stripslashes($_POST['neuakkuschwellen'])));
 $Zeichenzahl=strpos($NeuAkkuSchwellen,"/");
 $NeuAkkuLeerSchwelle=substr($NeuAkkuSchwellen,0,$Zeichenzahl); //ersten 3 oder 4 Zeichen
 $NeuAkkuVollSchwelle=substr($NeuAkkuSchwellen,-$Zeichenzahl); //letzten 3 oder 4 Zeichen
}

if(isset($_POST['neusprache'])) $NeuSprache=htmlentities(strip_tags(stripslashes($_POST['neusprache'])));
 if($NeuSprache=="")$NeuSprache=1;

if(isset($_POST['neunextservice'])) $NeuNextService=htmlentities(strip_tags(stripslashes($_POST['neunextservice'])));
if(isset($_POST['standortloeschen'])) $StandortLoeschen=htmlentities(strip_tags(stripslashes($_POST['standortloeschen'])));
if(isset($_POST['neuautoanmerkungenerzeugen'])) $NeuAutoAnmerkungenErzeugen=htmlentities(strip_tags(stripslashes($_POST['neuautoanmerkungenerzeugen'])));
if(isset($_POST['neuanmerkunggewichtsdifferenz'])){
 $NeuAnmerkungGewichtsDifferenz=htmlentities(strip_tags(stripslashes($_POST['neuanmerkunggewichtsdifferenz'])));
 $NeuAnmerkungGewichtsDifferenz=str_replace(",", ".",$NeuAnmerkungGewichtsDifferenz);}

if(isset($_POST['neuanmerkunggewichtsdifferenzmax'])){
 $NeuAnmerkungGewichtsDifferenzMax=htmlentities(strip_tags(stripslashes($_POST['neuanmerkunggewichtsdifferenzmax'])));
 $NeuAnmerkungGewichtsDifferenzMax=str_replace(",", ".",$NeuAnmerkungGewichtsDifferenzMax);}

if(isset($_POST['neuanmerkungzeitdifferenz']))$NeuAnmerkungZeitDifferenz=htmlentities(strip_tags(stripslashes($_POST['neuanmerkungzeitdifferenz'])));
if(isset($_POST['neuautoserviceanmerkung'])) $NeuAutoServiceAnmerkung=htmlentities(strip_tags(stripslashes($_POST['neuautoserviceanmerkung'])));
if(isset($_POST['neuautoserviceanmerkungzeit']))$NeuAutoServiceAnmerkungZeit=htmlentities(strip_tags(stripslashes($_POST['neuautoserviceanmerkungzeit'])));


if(isset($_POST['neuemailschwarmalarm']))$NeuEmailSchwarmAlarm=$_POST['neuemailschwarmalarm'];
if(isset($_POST['neuemailschwarmalarm1'])) $NeuEmailSchwarmAlarm1=$_POST['neuemailschwarmalarm1'];
if(isset($_POST['neuemailschwarmalarm2'])) $NeuEmailSchwarmAlarm2=$_POST['neuemailschwarmalarm2'];
if(isset($_POST['neuemailschwarmalarm3'])) $NeuEmailSchwarmAlarm3=$_POST['neuemailschwarmalarm3'];
if(isset($_POST['neuemailschwarmalarm4'])) $NeuEmailSchwarmAlarm4=$_POST['neuemailschwarmalarm4'];
if(isset($_POST['neuemailschwarmalarm5'])) $NeuEmailSchwarmAlarm5=$_POST['neuemailschwarmalarm5'];
if(isset($_POST['neuemailschwarmalarm6'])) $NeuEmailSchwarmAlarm6=$_POST['neuemailschwarmalarm6'];

if(isset($_POST['neureferenzzeit'])) $NeuReferenzZeit=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit'])));
if(isset($_POST['neureferenzzeit1'])) $NeuReferenzZeit1=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit1'])));
if(isset($_POST['neureferenzzeit2'])) $NeuReferenzZeit2=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit2'])));
if(isset($_POST['neureferenzzeit3'])) $NeuReferenzZeit3=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit3'])));
if(isset($_POST['neureferenzzeit4'])) $NeuReferenzZeit4=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit4'])));
if(isset($_POST['neureferenzzeit5'])) $NeuReferenzZeit5=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit5'])));
if(isset($_POST['neureferenzzeit6'])) $NeuReferenzZeit6=htmlentities(strip_tags(stripslashes($_POST['neureferenzzeit6'])));

if(isset($_POST['neudifferenzgewicht'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht'],$val)){ $NeuDifferenzGewicht=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht=str_replace(",",".",$NeuDifferenzGewicht);
 if($NeuDifferenzGewicht < 1.0)$NeuDifferenzGewicht=1.0;}

if(isset($_POST['neudifferenzgewichtmax'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax'],$val)){ $NeuDifferenzGewichtMax=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewichtMax=str_replace(",",".",$NeuDifferenzGewichtMax);
 if($NeuDifferenzGewichtMax=="")$NeuDifferenzGewichtMax=4.0;}

if(isset($_POST['neudifferenzgewicht1'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht1'],$val)){ $NeuDifferenzGewicht1=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht1=str_replace(",",".",$NeuDifferenzGewicht1);
 if($NeuDifferenzGewicht1 < 1.0)$NeuDifferenzGewicht1=1.0;}

if(isset($_POST['neudifferenzgewichtmax1'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax1'],$val)){ $NeuDifferenzGewicht1Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht1Max=str_replace(",",".",$NeuDifferenzGewicht1Max);
 if($NeuDifferenzGewicht1Max=="")$NeuDifferenzGewicht1Max=4.0;}

if(isset($_POST['neudifferenzgewicht2'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht2'],$val)){ $NeuDifferenzGewicht2=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht2=str_replace(",",".",$NeuDifferenzGewicht2);
 if($NeuDifferenzGewicht2 < 1.0)$NeuDifferenzGewicht2=1.0;}

if(isset($_POST['neudifferenzgewichtmax2'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax2'],$val)){ $NeuDifferenzGewicht2Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht2Max=str_replace(",",".",$NeuDifferenzGewicht2Max);
 if($NeuDifferenzGewicht2Max=="")$NeuDifferenzGewicht2Max=4.0;}

if(isset($_POST['neudifferenzgewicht3'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht3'],$val)){ $NeuDifferenzGewicht3=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht3=str_replace(",",".",$NeuDifferenzGewicht3);
 if($NeuDifferenzGewicht3 < 1.0)$NeuDifferenzGewicht3=1.0;}

if(isset($_POST['neudifferenzgewichtmax3'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax3'],$val)){ $NeuDifferenzGewicht3Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht3Max=str_replace(",",".",$NeuDifferenzGewicht3Max);
 if($NeuDifferenzGewicht3Max=="")$NeuDifferenzGewicht3Max=4.0;}

if(isset($_POST['neudifferenzgewicht4'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht4'],$val)){ $NeuDifferenzGewicht4=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht4=str_replace(",",".",$NeuDifferenzGewicht4);
 if($NeuDifferenzGewicht4 < 1.0)$NeuDifferenzGewicht4=1.0;}

if(isset($_POST['neudifferenzgewichtmax4'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax4'],$val)){ $NeuDifferenzGewicht4Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht4Max=str_replace(",",".",$NeuDifferenzGewicht4Max);
 if($NeuDifferenzGewicht4Max=="")$NeuDifferenzGewicht4Max=4.0;}

if(isset($_POST['neudifferenzgewicht5'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht5'],$val)){ $NeuDifferenzGewicht5=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht5=str_replace(",",".",$NeuDifferenzGewicht5);
 if($NeuDifferenzGewicht5 < 1.0)$NeuDifferenzGewicht5=1.0;}

if(isset($_POST['neudifferenzgewichtmax5'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax5'],$val)){ $NeuDifferenzGewicht5Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht5Max=str_replace(",",".",$NeuDifferenzGewicht5Max);
 if($NeuDifferenzGewicht5Max=="")$NeuDifferenzGewicht5Max=4.0;}

if(isset($_POST['neudifferenzgewicht6'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewicht6'],$val)){ $NeuDifferenzGewicht6=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht6=str_replace(",",".",$NeuDifferenzGewicht6);
 if($NeuDifferenzGewicht6 < 1.0)$NeuDifferenzGewicht6=1.0;}

if(isset($_POST['neudifferenzgewichtmax6'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neudifferenzgewichtmax6'],$val)){ $NeuDifferenzGewicht6Max=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuDifferenzGewicht6Max=str_replace(",",".",$NeuDifferenzGewicht6Max);
if($NeuDifferenzGewicht6Max=="")$NeuDifferenzGewicht6Max=4.0;}

if(isset($_POST['neupushschwarmalarm']))$NeuPushSchwarmAlarm=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm'])));
if(isset($_POST['neupushschwarmalarm1']))$NeuPushSchwarmAlarm1=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm1'])));
if(isset($_POST['neupushschwarmalarm2']))$NeuPushSchwarmAlarm2=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm2'])));
if(isset($_POST['neupushschwarmalarm3']))$NeuPushSchwarmAlarm3=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm3'])));
if(isset($_POST['neupushschwarmalarm4']))$NeuPushSchwarmAlarm4=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm4'])));
if(isset($_POST['neupushschwarmalarm5']))$NeuPushSchwarmAlarm5=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm5'])));
if(isset($_POST['neupushschwarmalarm6']))$NeuPushSchwarmAlarm6=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmalarm6'])));

if(isset($_POST['neutriggeralarmemail1']))$NeuTriggerAlarmEmail1=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmemail1'])));
if(isset($_POST['neutriggeralarmemail2']))$NeuTriggerAlarmEmail2=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmemail2'])));
if(isset($_POST['neutriggeralarmemail3']))$NeuTriggerAlarmEmail3=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmemail3'])));
if(isset($_POST['neutriggeralarmemail4']))$NeuTriggerAlarmEmail4=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmemail4'])));

if(isset($_POST['neutriggeralarmpush1']))$NeuTriggerAlarmPush1=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpush1'])));
if(isset($_POST['neutriggeralarmpush2']))$NeuTriggerAlarmPush2=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpush2'])));
if(isset($_POST['neutriggeralarmpush3']))$NeuTriggerAlarmPush3=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpush3'])));
if(isset($_POST['neutriggeralarmpush4']))$NeuTriggerAlarmPush4=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpush4'])));

if(isset($_POST['neutriggeralarmsensor1']))$NeuTriggerAlarmSensor1=$_POST['neutriggeralarmsensor1'];
if(isset($_POST['neutriggeralarmsensor2']))$NeuTriggerAlarmSensor2=$_POST['neutriggeralarmsensor2'];
if(isset($_POST['neutriggeralarmsensor3']))$NeuTriggerAlarmSensor3=$_POST['neutriggeralarmsensor3'];
if(isset($_POST['neutriggeralarmsensor4']))$NeuTriggerAlarmSensor4=$_POST['neutriggeralarmsensor4'];

if(isset($_POST['neutriggeralarmzeichen1']))$NeuTriggerAlarmZeichen1=$_POST['neutriggeralarmzeichen1'];
if(isset($_POST['neutriggeralarmzeichen2']))$NeuTriggerAlarmZeichen2=$_POST['neutriggeralarmzeichen2'];
if(isset($_POST['neutriggeralarmzeichen3']))$NeuTriggerAlarmZeichen3=$_POST['neutriggeralarmzeichen3'];
if(isset($_POST['neutriggeralarmzeichen4']))$NeuTriggerAlarmZeichen4=$_POST['neutriggeralarmzeichen4'];

if(isset($_POST['neutriggeralarmwert1'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neutriggeralarmwert1'],$val)){ $NeuTriggerAlarmWert1=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuTriggerAlarmWert1=str_replace(",", ".",$NeuTriggerAlarmWert1);}
if(isset($_POST['neutriggeralarmwert2'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neutriggeralarmwert2'],$val)){ $NeuTriggerAlarmWert2=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuTriggerAlarmWert2=str_replace(",", ".",$NeuTriggerAlarmWert2);}
if(isset($_POST['neutriggeralarmwert3'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neutriggeralarmwert3'],$val)){ $NeuTriggerAlarmWert3=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuTriggerAlarmWert3=str_replace(",", ".",$NeuTriggerAlarmWert3);}
if(isset($_POST['neutriggeralarmwert4'])&&preg_match("#^[0-9,\.\-]+$#",$_POST['neutriggeralarmwert4'],$val)){ $NeuTriggerAlarmWert4=htmlentities(strip_tags(stripslashes(implode(",",$val))));
 $NeuTriggerAlarmWert4=str_replace(",", ".",$NeuTriggerAlarmWert4);}

if(isset($_POST['neutriggeralarmpause1'])){ $NeuTriggerAlarmPause1=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpause1'])));
 if((int)$NeuTriggerAlarmPause1 < 1000)$NeuTriggerAlarmEmail1="deaktiviert";}
if(isset($_POST['neutriggeralarmpause2'])){ $NeuTriggerAlarmPause2=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpause2'])));
 if((int)$NeuTriggerAlarmPause2 < 1000)$NeuTriggerAlarmEmail2="deaktiviert";}
if(isset($_POST['neutriggeralarmpause3'])){ $NeuTriggerAlarmPause3=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpause3'])));
 if((int)$NeuTriggerAlarmPause3 < 1000)$NeuTriggerAlarmEmail3="deaktiviert";}
if(isset($_POST['neutriggeralarmpause4'])){ $NeuTriggerAlarmPause4=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmpause4'])));
 if((int)$NeuTriggerAlarmPause4 < 1000)$NeuTriggerAlarmEmail4="deaktiviert";}

if(isset($_POST['neulasttriggeroption1']))$NeuLastTriggerOption1=htmlentities(strip_tags(stripslashes($_POST['neulasttriggeroption1'])));
if(isset($_POST['neulasttriggeroption2']))$NeuLastTriggerOption2=htmlentities(strip_tags(stripslashes($_POST['neulasttriggeroption2'])));
if(isset($_POST['neulasttriggeroption3']))$NeuLastTriggerOption3=htmlentities(strip_tags(stripslashes($_POST['neulasttriggeroption3'])));
if(isset($_POST['neulasttriggeroption4']))$NeuLastTriggerOption4=htmlentities(strip_tags(stripslashes($_POST['neulasttriggeroption4'])));

if(isset($_POST['neupushpriority1']))$NeuPushPriority1=htmlentities(strip_tags(stripslashes($_POST['neupushpriority1'])));
if(isset($_POST['neupushpriority2']))$NeuPushPriority2=htmlentities(strip_tags(stripslashes($_POST['neupushpriority2'])));
if(isset($_POST['neupushpriority3']))$NeuPushPriority3=htmlentities(strip_tags(stripslashes($_POST['neupushpriority3'])));
if(isset($_POST['neupushpriority4']))$NeuPushPriority4=htmlentities(strip_tags(stripslashes($_POST['neupushpriority4'])));

if(isset($_POST['neupushschwarmpriority1']))$NeuPushSchwarmPriority1=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmpriority1'])));
if(isset($_POST['neupushschwarmpriority2']))$NeuPushSchwarmPriority2=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmpriority2'])));
if(isset($_POST['neupushschwarmpriority3']))$NeuPushSchwarmPriority3=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmpriority3'])));
if(isset($_POST['neupushschwarmpriority4']))$NeuPushSchwarmPriority4=htmlentities(strip_tags(stripslashes($_POST['neupushschwarmpriority4'])));

if(isset($_POST['neutriggeralarmanmerkung1']))$NeuTriggerAlarmAnmerkung1=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmanmerkung1'])));
if(isset($_POST['neutriggeralarmanmerkung2']))$NeuTriggerAlarmAnmerkung2=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmanmerkung2'])));
if(isset($_POST['neutriggeralarmanmerkung3']))$NeuTriggerAlarmAnmerkung3=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmanmerkung3'])));
if(isset($_POST['neutriggeralarmanmerkung4']))$NeuTriggerAlarmAnmerkung4=htmlentities(strip_tags(stripslashes($_POST['neutriggeralarmanmerkung4'])));

if(isset($_POST['neupushtoken'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neupushtoken'],$val)) $NeuPushToken=$_POST['neupushtoken'];
if(isset($_POST['neupushuser'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neupushuser'],$val)) $NeuPushUser=$_POST['neupushuser'];
if(isset($_POST['neut_token']))$NeuT_Token=htmlentities(strip_tags(stripslashes($_POST['neut_token'])));
if(isset($_POST['neut_chatid']))$NeuT_Chat_Id=htmlentities(strip_tags(stripslashes($_POST['neut_chatid'])));

if(isset($_POST['neubeep'])) $NeuBeep=htmlentities(strip_tags(stripslashes($_POST['neubeep'])));
if(isset($_POST['neubeep1']))$NeuBeep1=htmlentities(strip_tags(stripslashes($_POST['neubeep1'])));
if(isset($_POST['neubeep2']))$NeuBeep2=htmlentities(strip_tags(stripslashes($_POST['neubeep2'])));
if(isset($_POST['neubeep3']))$NeuBeep3=htmlentities(strip_tags(stripslashes($_POST['neubeep3'])));
if(isset($_POST['neubeep4']))$NeuBeep4=htmlentities(strip_tags(stripslashes($_POST['neubeep4'])));
if(isset($_POST['neubeep5']))$NeuBeep5=htmlentities(strip_tags(stripslashes($_POST['neubeep5'])));
if(isset($_POST['neubeep6']))$NeuBeep6=htmlentities(strip_tags(stripslashes($_POST['neubeep6'])));

if(isset($_POST['neubeepid'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid'],$val)) $NeuBeepId=htmlentities(strip_tags(stripslashes($_POST['neubeepid'])));
if(isset($_POST['neubeepid1'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid1'],$val))$NeuBeepId1=htmlentities(strip_tags(stripslashes($_POST['neubeepid1'])));
if(isset($_POST['neubeepid2'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid2'],$val))$NeuBeepId2=htmlentities(strip_tags(stripslashes($_POST['neubeepid2'])));
if(isset($_POST['neubeepid3'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid3'],$val))$NeuBeepId3=htmlentities(strip_tags(stripslashes($_POST['neubeepid3'])));
if(isset($_POST['neubeepid4'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid4'],$val))$NeuBeepId4=htmlentities(strip_tags(stripslashes($_POST['neubeepid4'])));
if(isset($_POST['neubeepid5'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid5'],$val))$NeuBeepId5=htmlentities(strip_tags(stripslashes($_POST['neubeepid5'])));
if(isset($_POST['neubeepid6'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neubeepid6'],$val))$NeuBeepId6=htmlentities(strip_tags(stripslashes($_POST['neubeepid6'])));

if(isset($_POST['neuKeeper'])) $NeuKeeper=htmlentities(strip_tags(stripslashes($_POST['neuKeeper'])));
if(isset($_POST['neuKeeper1']))$NeuKeeper1=htmlentities(strip_tags(stripslashes($_POST['neuKeeper1'])));
if(isset($_POST['neuKeeper2']))$NeuKeeper2=htmlentities(strip_tags(stripslashes($_POST['neuKeeper2'])));
if(isset($_POST['neuKeeper3']))$NeuKeeper3=htmlentities(strip_tags(stripslashes($_POST['neuKeeper3'])));
if(isset($_POST['neuKeeper4']))$NeuKeeper4=htmlentities(strip_tags(stripslashes($_POST['neuKeeper4'])));
if(isset($_POST['neuKeeper5']))$NeuKeeper5=htmlentities(strip_tags(stripslashes($_POST['neuKeeper5'])));
if(isset($_POST['neuKeeper6']))$NeuKeeper6=htmlentities(strip_tags(stripslashes($_POST['neuKeeper6'])));

if(isset($_POST['neuKeeperid'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid'],$val)) $NeuKeeperId=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid'])));
if(isset($_POST['neuKeeperid1'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid1'],$val))$NeuKeeperId1=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid1'])));
if(isset($_POST['neuKeeperid2'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid2'],$val))$NeuKeeperId2=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid2'])));
if(isset($_POST['neuKeeperid3'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid3'],$val))$NeuKeeperId3=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid3'])));
if(isset($_POST['neuKeeperid4'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid4'],$val))$NeuKeeperId4=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid4'])));
if(isset($_POST['neuKeeperid5'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid5'],$val))$NeuKeeperId5=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid5'])));
if(isset($_POST['neuKeeperid6'])&&preg_match("#^[0-9A-Za-z,\-]+$#",$_POST['neuKeeperid6'],$val))$NeuKeeperId6=htmlentities(strip_tags(stripslashes($_POST['neuKeeperid6'])));

// ThingsPeak
if(isset($_POST['neuthingspeak']))$NeuThingsPeak=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak'])));
if(isset($_POST['neuthingspeak1']))$NeuThingsPeak1=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak1'])));
if(isset($_POST['neuthingspeak2']))$NeuThingsPeak2=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak2'])));
if(isset($_POST['neuthingspeak3']))$NeuThingsPeak3=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak3'])));
if(isset($_POST['neuthingspeak4']))$NeuThingsPeak4=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak4'])));
if(isset($_POST['neuthingspeak5']))$NeuThingsPeak5=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak5'])));
if(isset($_POST['neuthingspeak6']))$NeuThingsPeak6=htmlentities(strip_tags(stripslashes($_POST['neuthingspeak6'])));

if(isset($_POST['neuthingspeakapi'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi'],$val))$NeuThingsPeakApi=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi'])));
if(isset($_POST['neuthingspeakapi1'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi1'],$val))$NeuThingsPeakApi1=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi1'])));
if(isset($_POST['neuthingspeakapi2'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi2'],$val))$NeuThingsPeakApi2=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi2'])));
if(isset($_POST['neuthingspeakapi3'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi3'],$val))$NeuThingsPeakApi3=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi3'])));
if(isset($_POST['neuthingspeakapi4'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi4'],$val))$NeuThingsPeakApi4=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi4'])));
if(isset($_POST['neuthingspeakapi5'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi5'],$val))$NeuThingsPeakApi5=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi5'])));
if(isset($_POST['neuthingspeakapi6'])&&preg_match("#^[0-9A-Za-z]+$#",$_POST['neuthingspeakapi6'],$val))$NeuThingsPeakApi6=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakapi6'])));

if(isset($_POST['neuthingspeakchannel'])) $NeuThingsPeakChannel=htmlentities(strip_tags(stripslashes($_POST['neuthingspeakchannel'])));

if(isset($_POST['neuthingspeaksensor1']))$NeuThingsPeakSensor1=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor1'])));
if(isset($_POST['neuthingspeaksensor2'])) $NeuThingsPeakSensor2=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor2'])));
if(isset($_POST['neuthingspeaksensor3'])) $NeuThingsPeakSensor3=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor3'])));
if(isset($_POST['neuthingspeaksensor4'])) $NeuThingsPeakSensor4=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor4'])));
if(isset($_POST['neuthingspeaksensor5'])) $NeuThingsPeakSensor5=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor5'])));
if(isset($_POST['neuthingspeaksensor6'])) $NeuThingsPeakSensor6=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor6'])));
if(isset($_POST['neuthingspeaksensor7'])) $NeuThingsPeakSensor7=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor7'])));
if(isset($_POST['neuthingspeaksensor'])) $NeuThingsPeakSensor8=htmlentities(strip_tags(stripslashes($_POST['neuthingspeaksensor8'])));

//Openweathermap für externe User
if(isset($_POST['neuopenweathermapkey'])){
 if(!$CommunityUser)$NeuOpenweathermapKey=htmlentities(strip_tags(stripslashes($_POST['neuopenweathermapkey'])));}
 $NeuWetterIcons=$_POST['neuwettericons'];

//MAP2--------------------------------------------------------------
if(isset($_POST['neubeeloggermap2']))$NeubeeloggerMap2=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2'])));
if(isset($_POST['neubeeloggermap2id']))$NeubeeloggerMap2ID=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2id'])));

if(isset($_POST['neubeeloggermap2key']))$NeubeeloggerMap2Key=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2key'])));
if(isset($_POST['neubeeloggermap2beeid']))$NeubeeloggerMap2BeeID=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2beeid'])));
if(isset($_POST['neubeeloggermap2location'])){
 $NeubeeloggerMap2Location=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2location'])));
 $NeubeeloggerMap2Location=str_replace(array("#", "'", ";"), '', $NeubeeloggerMap2Location);}

if(isset($_POST['neubeeloggermap2lat'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeeloggermap2lat'],$val)) $NeubeeloggerMap2Lat=$_POST['neubeeloggermap2lat'];
if(isset($_POST['neubeeloggermap2lon'])&&preg_match("#^[0-9,\.]+$#",$_POST['neubeeloggermap2lon'],$val)) $NeubeeloggerMap2Lon=$_POST['neubeeloggermap2lon'];
if(isset($_POST['beeloggermap2beeloggertype']))$NeubeeloggerMap2BeeloggerType=htmlentities(strip_tags(stripslashes($_POST['beeloggermap2beeloggertype'])));
if(isset($_POST['beeloggermap2connect']))$NeubeeloggerMap2Connect=htmlentities(strip_tags(stripslashes($_POST['beeloggermap2connect'])));
if(isset($_POST['neubeeloggermap2url']))$NeubeeloggerMap2URL=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2url'])));
if(isset($_POST['neubeeloggermap2email']))if (filter_var($_POST['neubeeloggermap2email'], FILTER_VALIDATE_EMAIL)) {$NeubeeloggerMap2Email=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2email'])));str_replace(array("#", "'", ";"), '', $NeubeeloggerMap2Email);}
if(isset($_POST['neubeeloggermap2teilnahme']))$NeubeeloggerMap2Teilnahme=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2teilnahme'])));
if(isset($_POST['neubeeloggermap2status']))$NeubeeloggerMap2Status=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2status'])));
if(isset($_POST['neubeeloggermapwaage']))$NeubeeloggerMapWaage=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermapwaage'])));
if(isset($_POST['neubeeloggermap2sensor1']))$NeubeeloggerMap2Sensor1=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor1'])));
if(isset($_POST['neubeeloggermap2sensor2']))$NeubeeloggerMap2Sensor2=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor2'])));
if(isset($_POST['neubeeloggermap2sensor3']))$NeubeeloggerMap2Sensor3=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor3'])));
if(isset($_POST['neubeeloggermap2sensor4']))$NeubeeloggerMap2Sensor4=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor4'])));
if(isset($_POST['neubeeloggermap2sensor5']))$NeubeeloggerMap2Sensor5=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor5'])));
if(isset($_POST['neubeeloggermap2sensor6']))$NeubeeloggerMap2Sensor6=htmlentities(strip_tags(stripslashes($_POST['neubeeloggermap2sensor6'])));

if(isset($_POST['neubeeloggermap2loeschebeeid']))$beeloggerMap2LoescheBeeID=htmlentities(strip_tags(stripslashes($_POST['beeloggermap2loeschebeeid'])));

//--------------------------------------------------------------------------
if(isset($_POST['neubeeloggernamensortierung']))
 $NeubeeloggerNamenSortierung=htmlentities(strip_tags(stripslashes($_POST['neubeeloggernamensortierung'])));
if(isset($_POST['neuinfo']))
 $NeuInfo=htmlentities(strip_tags(stripslashes($_POST['neuinfo'])));
if(isset($_POST['beeloggersketchid']))
 $NeubeeloggerSketchID=htmlentities(strip_tags(stripslashes($_POST['beeloggersketchid'])));

if(isset($_POST['neumobilewatch_show']))
 $NeumobileWatch_Show=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_show'])));
if(isset($_POST['neumobilewatch_sort']))
 $NeumobileWatch_Sort=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_sort'])));
if(isset($_POST['neumobilewatch_sensor1']))
 $NeumobileWatch_sensor1=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_sensor1'])));
if(isset($_POST['neumobilewatch_sensor2']))
 $NeumobileWatch_sensor2=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_sensor2'])));
if(isset($_POST['neumobilewatch_roll']))
 $NeumobileWatch_roll=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_roll'])));
if(isset($_POST['neumobilewatch_tageswertanzeige']))
 $NeumobileWatch_tageswertanzeige=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_tageswertanzeige'])));
if(isset($_POST['neumobilewatch_tage']))
 $NeumobileWatch_tage=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_tage'])));
if(isset($_POST['neumobilewatch_spalten']))
 $NeumobileWatch_spalten=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_spalten'])));
if(isset($_POST['neumobilewatch_legende']))
 $NeumobileWatch_legende=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_legende'])));
if(isset($_POST['neumobilewatch_notes']))
 $NeumobileWatch_notes=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_notes'])));
if(isset($_POST['neumobilewatch_twogrid']))
 $NeumobileWatch_twogrid=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_twogrid'])));
if(isset($_POST['neumobilewatch_one_y']))
 $NeumobileWatch_one_y=htmlentities(strip_tags(stripslashes($_POST['neumobilewatch_one_y'])));

if(isset($_POST['neumobilecheck_show']))
 $NeumobileCheck_Show=htmlentities(strip_tags(stripslashes($_POST['neumobilecheck_show'])));

  if(!isset($_POST['neubeelogger1_list']))$NeuBeelogger1_List="deaktiviert";
  else $NeuBeelogger1_List="aktiviert";

 for($a=1;$a<=$AnzahlSensoren;$a++){
 if(isset($_POST["xanalystbeelogger".$a]))
 $NeuXanalyseArray[$a]=htmlentities(strip_tags(stripslashes($_POST["xanalystbeelogger".$a])));
 if(isset($_POST["sensor".$a])){
 $NeuSensor[$a]=htmlentities(strip_tags(stripslashes($_POST["sensor".$a])));
 $NeuSensor[$a]=str_replace("'", "",$NeuSensor[$a]);
 if(($MultiType==-1) AND ($NeuXanalyseArray[$a]==""))$NeuSensor[$a]="";
 } else $NeuSensor[$a]="";
 if(isset($_POST["mobilechecksensor".$a]))$NeuMobileCheckSensor[$a]=htmlentities(strip_tags(stripslashes($_POST["mobilechecksensor".$a])));
 else $NeuMobileCheckSensor[$a]="false";
 if(isset($_POST["useicon".$a]))$NeuUseIcon[$a]=htmlentities(strip_tags(stripslashes($_POST["useicon".$a])));
 else $NeuUseIcon[$a]="false";
 if(isset($_POST["icon".$a]))$NeuIcon[$a]=htmlentities(strip_tags(stripslashes($_POST["icon".$a])));
 else $NeuIcon[$a]="no.png";
 if(isset($_POST["farbe".$a]))$NeuFarbe[$a]=htmlentities(strip_tags(stripslashes($_POST["farbe".$a])));
 else $NeuFarbe[$a]="black";
 if(isset($_POST["anzeige".$a]))$NeuAnzeige[$a]=htmlentities(strip_tags(stripslashes($_POST["anzeige".$a])));
 else $NeuAnzeige[$a]="false";
 if(isset($_POST["achse".$a])){
 $NeuAchse[$a]=htmlentities(strip_tags(stripslashes($_POST["achse".$a])));
 $NeuAchse[$a]="'".$NeuAchse[$a]."'";}
 else $NeuAchse[$a]='y';
 if(isset($_POST["einheit".$a]))$NeuEinheit[$a]=htmlentities(strip_tags(stripslashes($_POST["einheit".$a])));
 else $NeuEinheit[$a]="";
 if(isset($_POST["fillgraph".$a]))$NeuFillGraph[$a]=htmlentities(strip_tags(stripslashes($_POST["fillgraph".$a])));
 else $NeuFillGraph[$a]="";
 if(isset($_POST["loeschen".$a]))$SensorLoeschen[$a]=htmlentities(strip_tags(stripslashes($_POST["loeschen".$a])));
 else $SensorLoeschen[$a]="0";
 if(isset($_POST["tageswertoption".$a]))$NeuTageswertOption[$a]=htmlentities(strip_tags(stripslashes($_POST["tageswertoption".$a])));
 else $NeuTageswertOption[$a]="";
 }

 //falls neuer Sensor eingegeben wurde
if(isset($_POST['sensorneu']))$NeuSensorNeu=htmlentities(strip_tags(stripslashes($_POST["sensorneu"])));
if(isset($_POST["xanalystbeeloggerneu".$a])){
 $NeuXanalyseNeu=htmlentities(strip_tags(stripslashes($_POST["xanalystbeeloggerneu".$a])));
}
if($NeuSensorNeu !="" OR $NeuXanalyseNeu !="")$AnzahlSensoren=$AnzahlSensoren +1 ; //es gibt einen neuen Sensor
if(isset($_POST["xanalystbeeloggerneu".$a]))
 $NeuXanalyseArray[$a]=htmlentities(strip_tags(stripslashes($_POST["xanalystbeeloggerneu".$a])));

if(isset($_POST["sensorneu"])) $NeuSensor[$a]=htmlentities(strip_tags(stripslashes($_POST["sensorneu"])));

if($MultiType==-1){$NeuSensor[$a]=$NeuXanalyseNeu; // für Xanalyse
 $NeuSensor[$a]=str_replace("'", "",$NeuSensor[$a]);}

if(isset($_POST["mobilechecksensorneu"]))$NeuMobileCheckSensor[$a]=htmlentities(strip_tags(stripslashes($_POST["mobilechecksensorneu"])));
if(isset($_POST["useiconneu"]))$NeuUseIcon[$a]=htmlentities(strip_tags(stripslashes($_POST["useiconneu"])));
if(isset($_POST["iconneu"]))$NeuIcon[$a]=htmlentities(strip_tags(stripslashes($_POST["iconneu"])));
if(isset($_POST["farbeneu"])) $NeuFarbe[$a]=htmlentities(strip_tags(stripslashes($_POST["farbeneu"])));
if(isset($_POST["anzeigeneu"]))$NeuAnzeige[$a]=htmlentities(strip_tags(stripslashes($_POST["anzeigeneu"])));
if(isset($_POST["achseneu"])){ $NeuAchse[$a]=htmlentities(strip_tags(stripslashes($_POST["achseneu"])));
 $NeuAchse[$a]="'".$NeuAchse[$a]."'";
 if((strlen($NeuAchse[$a]) > 10) AND ($MultiType !=-1))$NeuAchse[$a]="'".$NeuAchse[$a]."',showInRangeSelector: true";
 } else $NeuAchse[$a]='y';
if(isset($_POST["einheitneu"]))$NeuEinheit[$a]=htmlentities(strip_tags(stripslashes($_POST["einheitneu"])));
if(isset($_POST["fillgraphneu"]))$NeuFillGraph[$a]=htmlentities(strip_tags(stripslashes($_POST["fillgraphneu"])));
if(isset($_POST["tageswertoptionneu"]))$NeuTageswertOption[$a]=htmlentities(strip_tags(stripslashes($_POST["tageswertoptionneu"])));
else $NeuTageswertOption[$a]="";

if(isset($_POST["neukoeanzeige"]))$NeuKoeAnzeige=$_POST['neukoeanzeige'];
if(isset($_POST["neukoeinfo"]))$NeuKoeInfo=htmlentities(strip_tags(stripslashes($_POST['neukoeinfo'])));
if(isset($_POST["neukoekaefig"]))$NeuKoeKaefig=htmlentities(strip_tags(stripslashes($_POST['neukoekaefig'])));

}
//^^^configsichern Teil 1 Daten Lesen

// Neuer beelogger default Config setzen
if(!file_exists("beelogger_ini.php") OR (file_exists("pw.php"))){ // noch keine beelogger_ini.php vorhanden
 $ConfigSichern="1"; // damit Speicherung stattfindet
 // DefaultDaten für beelogger_ini.php Speicherung einsetzen
 $AnzahlSensoren=13; //default für beelogger und Unterordnerbeelogger

 switch ($MultiType){
 case (2): //beelogger Multi
 $AnzahlSensoren=18;
 break;
 case (3): //beelogger Multi
 $AnzahlSensoren=22;
 break;
 case (4): //beelogger Multi
 $AnzahlSensoren=26;
 break;
 case (5): //beelogger Multi
 $AnzahlSensoren=30;
 break;
 case (6): //beelogger Multi
 $AnzahlSensoren=34;
 break;
 case (-1): //Xanalyse
 $AnzahlSensoren=5;
 break;
 }

 $NeuBeeloggerShowPasswort="Show";
 $NeuBeeloggerLogPasswort="Log";
 if(file_exists("../beelogger1/beelogger_ini.php")){ // nicht der erste beelogger
 if(file_exists("pw.php")){
 include("pw.php");
 $NeuBeeloggerShowPasswort=$pw;
 unlink("pw.php");
 }
 }
 else{
  if($CommunityUser){ include("../data.php");} //neuer User
 }
 $NeuInfo=$SAs[0]; //Informationen und Notizen
 //default
 $NeuSensor=array("",$SAs[1],$SAs[2],$SAs[3],$SAs[4],$SAs[5],$SAs[6],$SAs[7],$SAs[8],"Service","G","11","12","13");//"Temperatur Beute","Aussen-Temperatur","Luftfeuchte Beute","Luftfeuchte aussen","Lichtstärke","Gewicht","Spannung Akku","Spannung Solarzelle"
 $NeuFarbe=array("","blue","deepskyblue","red","orangered","gold","black","teal","purple","fuchsia","maroon","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","true","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[%]","[lux]","[kg]","[V]","[V]","[s]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuIcon=array('','temp_i.png','temp_o.png','hum_i.png','hum_o.png','sun.png','weight.png','bat.png','solar.png','service.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','true','true','true','true','true','true','true','true');

 $NeuSensor1=array("",$SAs[1],$SAs[2],$SAs[3],$SAs[4],$SAs[5],$SAs[6],$SAs[7],$SAs[8],"Service","G","11","12","13");//"Temperatur Beute","Aussen-Temperatur","Luftfeuchte Beute","Luftfeuchte aussen","Lichtstärke","Gewicht","Spannung Akku","Spannung Solarzelle"
 $NeuFarbe1=array("","blue","deepskyblue","red","orangered","gold","black","teal","purple","fuchsia","maroon","gray","gray","gray");
 $NeuAnzeige1=array("","true","true","true","true","true","true","true","true","true","false","false","false","false","false");
 $NeuAchse1=array ("","'y'","'y'","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit1=array ("","[°C]","[°C]","[%]","[%]","[lux]","[kg]","[V]","[V]","[s]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","");
 $NeuIcon1=array('','temp_i.png','temp_o.png','hum_i.png','hum_o.png','sun.png','weight.png','bat.png','solar.png','service.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon1=array('false','false','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor1=array('false','false','true','true','true','true','true','true','true','true');

 if($MultiType==2){
 $NeuSensor=array("",$SAs[9],$SAs[2],$SAs[4],"Service",$SAs[5],$SAs[6]."-D_1",$SAs[6]."-D_2",$SAs[1]."-D_1",$SAs[1]."-D_2",$SAs[3]."-D_1",$SAs[3]."-D_2",$SAs[7],$SAs[8],$SAs[6]."(1A) unkomp.",$SAs[6]."(1B) unkomp.","Aux1","Aux2","Aux3");
 $NeuFarbe=array("","orange","red","blue","teal","yellow","black","black","orangered","orangered","deepskyblue","deepskyblue","fuchsia","purple","gray","gray","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","true","false","false","false","false","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y'","'y'","'y'","'y'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[s]","[lux]","[kg]","[kg]","[°C]","[°C]","[%]","[%]","[V]","[V]","[kg]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","","","","","");
 $NeuIcon=array('','temp_w.png','temp_o.png','hum_o.png','service.png','sun.png','weight.png','weight.png','temp_i.png','temp_i.png','hum_i.png','hum_i.png','bat.png','solar.png','weight.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','true','true','true','true','true','true','true','true','true','true','true','true');
 }

 if($MultiType==3){
 $NeuSensor=array("",$SAs[9],$SAs[2],$SAs[4],"Service",$SAs[5],$SAs[6]."-T_1",$SAs[6]."-T_2",$SAs[6]."-T_3",$SAs[1]."-T_1",$SAs[1]."-T_2",$SAs[1]."-T_3",$SAs[3]."-T_1",$SAs[3]."-T_2",$SAs[3]."-T_3",$SAs[7],$SAs[8],$SAs[6]."(1A) unkomp.",$SAs[6]."(1B) unkomp.",$SAs[6]."(2A) unkomp.","Aux1","Aux2","Aux3");
 $NeuFarbe=array("","orange","red","blue","teal","yellow","black","black","black","orangered","orangered","orangered","deepskyblue","deepskyblue","deepskyblue","fuchsia","purple","gray","gray","gray","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","true","false","false","false","false","false","false","false","false","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[s]","[lux]","[kg]","[kg]","[kg]","[°C]","[°C]","[°C]","[%]","[%]","[%]","[V]","[V]","[kg]","[kg]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","","","","","","","","","","","","","");
 $NeuIcon=array('','temp_w.png','temp_o.png','hum_o.png','service.png','sun.png','weight.png','weight.png','weight.png','temp_i.png','temp_i.png','temp_i.png','hum_i.png','hum_i.png','hum_i.png','bat.png','solar.png','weight.png','weight.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 }

 if($MultiType==4){
 $NeuSensor=array("",$SAs[9],$SAs[2],$SAs[4],"Service",$SAs[5],$SAs[6]."-Q_1",$SAs[6]."-Q_2",$SAs[6]."-Q_3",$SAs[6]."-Q_4",$SAs[1]."-Q_1",$SAs[1]."-Q_2",$SAs[1]."-Q_3",$SAs[1]."-Q_4",$SAs[3]."-Q_1",$SAs[3]."-Q_2",$SAs[3]."-Q_3",$SAs[3]."-Q_4",$SAs[7],$SAs[8],$SAs[6]."(1A) unkomp.",$SAs[6]."(1B) unkomp.",$SAs[6]."(2A) unkomp.",$SAs[6]."(2B) unkomp.","Aux1","Aux2","Aux3");
 $NeuFarbe=array("","orange","red","blue","teal","yellow","black","black","black","black","orangered","orangered","orangered","orangered","deepskyblue","deepskyblue","deepskyblue","deepskyblue","fuchsia","purple","gray","gray","gray","gray","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y2'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[s]","[lux]","[kg]","[kg]","[kg]","[kg]","[°C]","[°C]","[°C]","[°C]","[%]","[%]","[%]","[%]","[V]","[V]","[kg]","[kg]","[kg]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");
 $NeuIcon=array('','temp_w.png','temp_o.png','hum_o.png','service.png','sun.png','weight.png','weight.png','weight.png','weight.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','bat.png','solar.png','weight.png','weight.png','weight.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 }

 if($MultiType==5){
 $NeuSensor=array("",$SAs[9],$SAs[2],$SAs[4],"Service",$SAs[5],$SAs[6]."-P_1",$SAs[6]."-P_2",$SAs[6]."-P_3",$SAs[6]."-P_4",$SAs[6]."-P_5",$SAs[1]."-P_1",$SAs[1]."-P_2",$SAs[1]."-P_3",$SAs[1]."-P_4",$SAs[1]."-P_5",$SAs[3]."-P_1",$SAs[3]."-P_2",$SAs[3]."-P_3",$SAs[3]."-P_4",$SAs[3]."-P_5",$SAs[7],$SAs[8],$SAs[6]."(1A) unkomp.",$SAs[6]."(1B) unkomp.",$SAs[6]."(2A) unkomp.",$SAs[6]."(2B) unkomp.",$SAs[6]."(3A) unkomp.","Aux1","Aux2","Aux3");
 $NeuFarbe=array("","orange","red","blue","teal","yellow","black","black","black","black","black","orangered","orangered","orangered","orangered","orangered","deepskyblue","deepskyblue","deepskyblue","deepskyblue","deepskyblue","fuchsia","purple","gray","gray","gray","gray","gray","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[s]","[lux]","[kg]","[kg]","[kg]","[kg]","[kg]","[°C]","[°C]","[°C]","[°C]","[°C]","[%]","[%]","[%]","[%]","[%]","[V]","[V]","[kg]","[kg]","[kg]","[kg]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");
 $NeuIcon=array('','temp_w.png','temp_o.png','hum_o.png','service.png','sun.png','weight.png','weight.png','weight.png','weight.png','weight.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','bat.png','solar.png','weight.png','weight.png','weight.png','weight.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 }

 if($MultiType==6){
 $NeuSensor=array("",$SAs[9],$SAs[2],$SAs[4],"Service",$SAs[5],$SAs[6]."-S_1",$SAs[6]."-S_2",$SAs[6]."-S_3",$SAs[6]."-S_4",$SAs[6]."-S_5",$SAs[6]."-S_6",$SAs[1]."-S_1",$SAs[1]."-S_2",$SAs[1]."-S_3",$SAs[1]."-S_4",$SAs[1]."-S_5",$SAs[1]."-S_6",$SAs[3]."-S_1",$SAs[3]."-S_2",$SAs[3]."-S_3",$SAs[3]."-S_4",$SAs[3]."-S_5",$SAs[3]."-S_6",$SAs[7],$SAs[8],$SAs[6]."(1A) unkomp.",$SAs[6]."(1B) unkomp.",$SAs[6]."(2A) unkomp.",$SAs[6]."(2B) unkomp.",$SAs[6]."(3A) unkomp.",$SAs[6]."(3B) unkomp.","Aux1","Aux2","Aux3");
 $NeuFarbe=array("","orange","red","blue","teal","yellow","black","black","black","black","black","black","orangered","orangered","orangered","orangered","orangered","orangered","deepskyblue","deepskyblue","deepskyblue","deepskyblue","deepskyblue","deepskyblue","fuchsia","purple","gray","gray","gray","gray","gray","gray","gray","gray","gray");
 $NeuAnzeige=array("","true","true","true","true","true","true","true","true","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y2'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'","'y2'");
 $NeuEinheit=array ("","[°C]","[°C]","[%]","[s]","[lux]","[kg]","[kg]","[kg]","[kg]","[kg]","[kg]","[°C]","[°C]","[°C]","[°C]","[°C]","[°C]","[%]","[%]","[%]","[%]","[%]","[%]","[V]","[V]","[kg]","[kg]","[kg]","[kg]","[kg]","[kg]","[hPa]","[l/m^2]","[]");
 $NeuFillGraph=array ("","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");
 $NeuIcon=array('','temp_w.png','temp_o.png','hum_o.png','service.png','sun.png','weight.png','weight.png','weight.png','weight.png','weight.png','weight.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','temp_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','hum_i.png','bat.png','solar.png','weight.png','weight.png','weight.png','weight.png','weight.png','weight.png','press.png','rain.png','no.png');
 $NeuUseIcon=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 $NeuMobileCheckSensor=array('false','false','false','false','true','true','true','true','true','true','true','true','true','true','true','true','true','true','true');
 }

 if($MultiType==-1){ //Xanalyse
 $NeuSensor=array("","1","2","3","4","5");
 $NeuFarbe=array("","orangered","black","black","black","black");
 $NeuAnzeige=array("","false","false","false","false","false");
 $NeuAchse=array ("","'y'","'y'","'y'","'y'","'y'");
 $NeuEinheit=array ("","[]","[]","[]","[]","[]");
 $NeuFillGraph=array ("","","","","","");
 $NeuIcon=array('','no.png','no.png','no.png','no.png','no.png');
 $NeuUseIcon=array('','true','true','true','true','true');
 $NeuMobileCheckSensor=array('true','true','true','true','true','true');
 }

 if(file_exists("../general_ini.php")){
 include("../general_ini.php"); //falls bereits general_ini.php vorhanden
 //- dann die Werte übernehmen....
 $NeuBeelogger1_List=$beelogger1_List;
 $NeuSprache=$Sprache;
 $NeumobileWatch_tage=$mW_tage;
 $NeumobileWatch_spalten=$mW_spalten;
 $NeumobileWatch_Sort=$mW_sort;
 }
} // neue beelogger.ini


//################################# configsichern Teil2 & Aktionen

// CSV-Datei download
if($Csvdownload=="1"){ //download Button benutzt
 $ConfigSichern="0"; //keine weitere Aktion
 if($CsvDownloadDatei !=""){
  if(file_exists($CsvDownloadDatei)){
   header("Content-Description: File Transfer");
   header("Content-Type: text/csv");
   header("Content-Disposition: attachment; filename=\"". basename($CsvDownloadDatei) ."\"");
   ob_clean();
   flush();
   readfile ($CsvDownloadDatei);
  }
   $cfg_Message=$KAs[27].": ".$CsvDownloadDatei;
 }
}

if($ConfigSichern=="1"){

 if(file_exists('beelogger_map.php')) include('beelogger_map.php');
 if(!isset($CurlArray))$CurlArray=array("","","","","","","","","");

 function Curler($ServerCurl){
 global $CurlArray;
 for($c=6;$c >=0;$c--){
 $CurlArray[$c+1]=$CurlArray[$c]; //verschieben
 }
 $ServerCurl=str_replace('"','', $ServerCurl); //Anführungszeichen verboten
 $CurlArray[0]=$ServerCurl;
 }

 //Map2URL-link ermitteln, Single oder Sub
 if(($NeubeeloggerMap2URL=="aktiviert") AND (isset($beeloggerMapWaage))){
 $actual_linkpos=strrpos(strstr("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE),"/");
 $NeubeeloggerMap2URL=substr(strstr("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE), 0,$actual_linkpos);
 if((int)$beeloggerMapWaage==0){
 $NeubeeloggerMap2URL .='/beelogger'.$MultiSign.$ServerMultiNumber.'/beelogger_sml.php';
 }
 else if((int)$beeloggerMapWaage >=1){
 $NeubeeloggerMap2URL .='/beelogger'.$MultiSign.$ServerMultiNumber."_".$NeubeeloggerMapWaage.'/beelogger_sml.php';
 }
 }
 else $NeubeeloggerMap2URL="deaktiviert";

 //beeid ermitteln
 $NeubeeloggerMap2BeeID=sprintf("%06d",($MultiType.sprintf("%02d",$ServerMultiNumber)));

if(!isset($beeloggerMap2ID))$beeloggerMap2ID="";
 $NeubeeloggerMap2ID=$beeloggerMap2ID; //aus general_ini falls da
if(!isset($beeloggerMap2Key))$beeloggerMap2Key="";
 $NeubeeloggerMap2Key=$beeloggerMap2Key; //aus general_ini falls da

 $NeubeeloggerMap2Sensoren=array ($NeubeeloggerMap2Sensor1,$NeubeeloggerMap2Sensor2,$NeubeeloggerMap2Sensor3,$NeubeeloggerMap2Sensor4,$NeubeeloggerMap2Sensor5,$NeubeeloggerMap2Sensor6);

 if($NeubeeloggerMap2Teilnahme=="yes"){ //diese Aktion gibt es nur einmal pro beelogger
 //beeloggerMAP-Teilnahme erwünscht
 if(strlen($beeloggerMap2ID) !=8 OR strlen($beeloggerMap2Key) !=6){ // general_ini.php bereits included
 //Noch keine mapid und mapkey in der general_ini vorhanden
 $ch=curl_init("https://map2.beelogger.de/log.php?mapid=7259464&do=994561"); // ueber cURL Initialisieren
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $CurlAntwort=curl_exec($ch); //
 curl_close($ch); // Objekt schliessen und Ressourcen freigeben
 Curler("beelogger_show:{".htmlentities($CurlAntwort)."}_vom :".date("m/d H:i:s")."_neue_IDs_bezogen"); //Curlarray befüllen
 $NeubeeloggerMap2ID=substr($CurlAntwort,0,8);
 $NeubeeloggerMap2Key=substr($CurlAntwort,8);
 }
 }

//Standardabfrage falls auf MAP2 - checke, ob Map2 aktiviert werden darf?
 $helpme=$NeubeeloggerMap2Location;
 if($NeubeeloggerMap2Lat !="" AND $NeubeeloggerMap2Lat !="00.00" AND $NeubeeloggerMap2Lon !="00.00" AND $NeubeeloggerMap2BeeloggerType !="" AND $NeubeeloggerMap2Connect !="" AND $NeubeeloggerMap2=="aktiviert" AND $helpme !="")$NeubeeloggerMap2="aktiviert";

//Nutzer-Daten auf Ewig löschen - BeeID löschen
 if($beeloggerMap2LoescheBeeID=="loeschen"){
 //BeeID und Daten auf MapServer löschen
 $curler="https://map2.beelogger.de/log.php?mapid=".$NeubeeloggerMap2ID."&key=".$NeubeeloggerMap2Key."&do=957248&beeid=".$NeubeeloggerMap2BeeID;
 $ch=curl_init($curler); // ueber cURL Initialisieren
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $CurlAntwort=curl_exec($ch); // [DISABLED] - beeid deaktiviert
 curl_close($ch); // Objekt schliessen und Ressourcen freigeben
 Curler("beelogger_show:{".$curler."}_{".htmlentities($CurlAntwort)."}_vom :".date("m/d H:i:s")."_BeeID_gelöscht"); //Curlarray befüllen
 // Daten aus beelogger_map.php löschen
 $NeubeeloggerMap2BeeID="";
 $NeubeeloggerMap2Lat="";
 $beeloggerMap2Status="";
 $NeubeeloggerMap2Lon="";
 $NeubeeloggerMap2Location="";
 $NeubeeloggerMap2="";
 $NeubeeloggerMap2BeeloggerType="";
 $NeubeeloggerMap2Connect="";
 $NeubeeloggerMap2URL="";
 $NeubeeloggerMap2Email="";
 $NeubeeloggerMap2Sensor1="";
 $NeubeeloggerMap2Sensor2="";
 $NeubeeloggerMap2Sensor3="";
 $NeubeeloggerMap2Sensor4="";
 $NeubeeloggerMap2Sensor5="";
 $NeubeeloggerMap2Sensor6="";
 $NeubeeloggerMap2Status=$CurlAntwort;
 }

//alle Daten vorhanden und beelogger aktiviert!beeID anmelden und Daten übertragen
 if($NeubeeloggerMap2=="aktiviert"){
 //beeID Update-Daten übertragen
 $curlerString="https://map2.beelogger.de/log.php?mapid=".$NeubeeloggerMap2ID."&key=".$NeubeeloggerMap2Key."&do=348527&beeid=".$NeubeeloggerMap2BeeID."&name=".html_entity_decode(str_replace(' ', '%20',$NeubeeloggerMap2Location))."&lat=".$NeubeeloggerMap2Lat."&lon=".$NeubeeloggerMap2Lon."&typ=".$NeubeeloggerMap2BeeloggerType."&connect=".$NeubeeloggerMap2Connect."&url=".str_replace(' ', '%20',$NeubeeloggerMap2URL)."&email=".str_replace(' ','%20',$NeubeeloggerMap2Email);
 $ch=curl_init($curlerString); // ueber cURL Initialisieren
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $CurlAntwort=curl_exec($ch); // [NEW] - beeid mit neuen Daten angelegt ODER [CHANGED] - Daten der beeid geändert
 $NeubeeloggerMap2Status=$CurlAntwort;
 curl_close($ch); // Objekt schliessen und Ressourcen freigeben
 Curler("beelogger_show:{".$curlerString."}_{".htmlentities($CurlAntwort)."}_vom :".date("m/d H:i:s")."_BeeID_mit_aktuellen_Daten_angelegt"); //Curlarray befüllen
 if($CurlAntwort=="[INCOMPLETE]")$NeubeeloggerMap2="deaktiviert"; //Daten waren unvollständig
 else{ //alles ok mit der Anmeldung
 if($beeloggerMap2=="deaktiviert" OR $beeloggerMap2==""){
 //BeeID- aktivieren falls (wieder) aktiviert wurde
 $curler="https://map2.beelogger.de/log.php?mapid=".$NeubeeloggerMap2ID."&key=".$NeubeeloggerMap2Key."&do=140849&beeid=".$NeubeeloggerMap2BeeID;
 $ch=curl_init($curler);
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $CurlAntwort=curl_exec($ch); // [ENABLED] - beeid aktiviert
 curl_close($ch);
 Curler("beelogger_show:{".$curler."}_{".htmlentities($CurlAntwort)."}_vom :".date("m/d H:i:s")."_BeeID_aktiviert"); //Curlarray befüllen
 $NeubeeloggerMap2Status=$CurlAntwort;

 //einmalige Übertragung der Map2-Daten ----------------------------
 //Trachtsituation der letzten 7 Tage berechnen
 $Wochendatei="week.csv";
 if($NeubeeloggerMapWaage !=""){
 if(file_exists('../beelogger'.$MultiSign.$ServerMultiNumber."_".$NeubeeloggerMapWaage.'/week.csv'))$Wochendatei='../beelogger'.$MultiSign.$ServerMultiNumber."_".$NeubeeloggerMapWaage.'/week.csv';
 }
 $Trachtdurchschnitt="";
 $LastTrachtGewicht="";
 $FirstTrachtGewicht="";
 if(file_exists($Wochendatei)){ //eine week.csv gefunden
 $array=file($Wochendatei);
 $i=sizeof($array);
 $what=trim($array[$i-1]); //help curler
 $LetzteZeile=explode( ",", $what); //help curler
 if($i>0){
 while ($i--){
 $what=trim($array[$i]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 if($x[$s-1] !=''){
 $LastTrachtGewicht=$x[6]; //letzter Wert
 break;
 }
 } //while

 $what=trim($array[0]); //erster Wert
 $x=explode( ",", $what );
 $FirstTrachtGewicht=$x[6];
 if(($LastTrachtGewicht !="") AND($FirstTrachtGewicht !="")){
 if($LastTrachtGewicht > 150 )$Trachtdurchschnitt=round((($LastTrachtGewicht - $FirstTrachtGewicht)/7000),2);
 else $Trachtdurchschnitt=round(($LastTrachtGewicht - $FirstTrachtGewicht)/7,2);
 }
 }
 }
 $curler="https://map2.beelogger.de/log.php?mapid=".$NeubeeloggerMap2ID."&beeid=".$NeubeeloggerMap2BeeID."&do=164052&tracht=".$Trachtdurchschnitt;
 for($c=0;$c < 6;$c++){
 if($NeubeeloggerMap2Sensoren[$c] !="")$curler .="&s".($c+1)."=*";//.$LetzteZeile[($NeubeeloggerMap2Sensoren[$c]+1)];
 }

 $ch=curl_init($curler);
 curl_setopt($ch, CURLOPT_HEADER, 0); // Header soll nicht in Ausgabe enthalten sein
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $ServerAntwort=curl_exec($ch); // Ausführen
 curl_close($ch); // Objekt schließen und Ressourcxen freigeben
 Curler("beelogger_show:{".$curler."}_{".$ServerAntwort."}_vom :".date("m/d H:i:s")."_Daten_ausgesendet"); //Curlarray befüllen
 $beeloggerMap2Status=$ServerAntwort;

 }
 }
 }
 //beelogger noch aktiviert - wieder deaktivieren
 if($NeubeeloggerMap2=="deaktiviert" AND $beeloggerMap2=="aktiviert"){ //BeeID deaktivieren
 $curler="https://map2.beelogger.de/log.php?mapid=".$NeubeeloggerMap2ID."&key=".$NeubeeloggerMap2Key."&do=528145&beeid=".$NeubeeloggerMap2BeeID;
 $ch=curl_init($curler); // ueber cURL Initialisieren
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 curl_setopt($ch, CURLOPT_TIMEOUT, 5);
 $CurlAntwort=curl_exec($ch); // [DELETED] - beeid deaktiviert
 curl_close($ch); // Objekt schliessen und Ressourcen freigeben
 Curler("beelogger_show:{".$curler."}_{".$CurlAntwort."}_vom :".date("m/d H:i:s")."_Map2_deaktiviert"); //Curlarray befüllen
 $NeubeeloggerMap2Status=$CurlAntwort;
 }
//ende beeloggerMapInitialsierung

 $i=0;
 $IniArray[$i]="<?php\n";$i++;
 $IniArray[$i]="// ---------- Benutzereinstellungen: ---------\n\n";$i++;
 $IniArray[$i]='$BeeloggerShowPasswort="'.$NeuBeeloggerShowPasswort.'";'."\n";$i++;

 if($MultiType > 0){
 $IniArray[$i]='$BeeloggerLogPasswort="'.$NeuBeeloggerLogPasswort.'";'."\n\n";$i++;
 }
 if(htmlentities($NeuBienenvolkbezeichnung)=="")$NeuBienenvolkbezeichnung="auto";
 $IniArray[$i]='$Bienenvolkbezeichnung='."'".htmlentities($NeuBienenvolkbezeichnung)."';\n";$i++;
 $IniArray[$i]='$StandardCSV='."'".$NeuStandardCSVDatei."';\n";$i++;
 $IniArray[$i]='$KoeAnzeige='."'".$NeuKoeAnzeige."';\n";$i++;
 $IniArray[$i]='$KoeInfo='."'".$NeuKoeInfo."';\n";$i++;
 $IniArray[$i]='$KoeKaefig='."'".$NeuKoeKaefig."';\n";$i++;
 if($NeuPunktAnzeige !="true")$NeuPunktAnzeige="false";
 $IniArray[$i]='$PunktAnzeige='."'".$NeuPunktAnzeige."';\n";$i++;
 $IniArray[$i]='$Underlay='."'".$NeuUnderlay."';\n";$i++;
 $IniArray[$i]='$Underlay_Mo='."'".$NeuUnderlay_Mo."';\n";$i++;
 $IniArray[$i]='$Underlay_Ab='."'".$NeuUnderlay_Ab."';\n";$i++;
 $IniArray[$i]='$Underlay_Col='."'".$NeuUnderlay_Col."';\n";$i++;
 $IniArray[$i]='$TageswertAnzeige='."'".$NeuTageswertAnzeige."';\n";$i++;
 $IniArray[$i]='$TageswertAchse='."'".$NeuTageswertAchse."';\n";$i++;
 $IniArray[$i]='$TageswertMail='."'".$NeuTageswertMail."';\n";$i++;
 $IniArray[$i]='$TageswertMail_adresse='."'".$NeuTageswertMail_adresse."';\n";$i++;
 $IniArray[$i]='$Legende='."'".$NeuLegende."';\n";$i++;
 $IniArray[$i]='$TwoGrid='."'".$NeuTwoGrid."';\n";$i++;
 $IniArray[$i]='$chartMobile='."'".$NeuChartMobile."';\n";$i++;
 $IniArray[$i]='$GTS_color='."'".$NeuGTS_color."';\n";$i++;
 $IniArray[$i]='$Summe='."'".$NeuSumme."';\n";$i++;
 $IniArray[$i]='$bhome='."'".$NeuBhome."';\n";$i++;
 $IniArray[$i]='$Zoom='."'".$NeuZoom."';\n";$i++;
 $IniArray[$i]='$RollPeriod='.'"'.$NeuRollPeriod.'";'."\n\n";$i++;

 if(($MultiType==0) OR ($MultiType==1)){ //für alle Einzelansichten
 $IniArray[$i]='$AutoAnmerkungenErzeugen="'.$NeuAutoAnmerkungenErzeugen.'";'."\n";$i++;
 $IniArray[$i]='$AnmerkungGewichtsDifferenz="'.$NeuAnmerkungGewichtsDifferenz.'";'."\n";$i++;
 $IniArray[$i]='$AnmerkungGewichtsDifferenzMax="'.$NeuAnmerkungGewichtsDifferenzMax.'";'."\n";$i++;
 $IniArray[$i]='$AnmerkungZeitDifferenz="'.$NeuAnmerkungZeitDifferenz.'";'."\n";$i++;
 }

 $IniArray[$i]='$AutoServiceAnmerkung="'.$NeuAutoServiceAnmerkung.'";'."\n";$i++;
 $IniArray[$i]='$AutoServiceAnmerkungZeit="'.$NeuAutoServiceAnmerkungZeit.'";'."\n\n";$i++;

 if($MultiType > 0){
 $IniArray[$i]='$IntervallSendeSteuerung="'.$NeuIntervallSendeSteuerung.'";'."\n";$i++;
 $IniArray[$i]='$SommerBeginn="'.$NeuSommerBeginn.'";'."\n";$i++;
 $IniArray[$i]='$SommerTagZeit="'.$NeuSommerTagZeit.'";'."\n";$i++;
 $IniArray[$i]='$SommerSendeIntervallTag="'.$NeuSommerSendeIntervallTag.'";'."\n";$i++;
 $IniArray[$i]='$SommerNachtZeit="'.$NeuSommerNachtZeit.'";'."\n";$i++;
 $IniArray[$i]='$SommerSendeIntervallNacht="'.$NeuSommerSendeIntervallNacht.'";'."\n";$i++;
 $IniArray[$i]='$WinterBeginn="'.$NeuWinterBeginn.'";'."\n";$i++;
 $IniArray[$i]='$WinterSendeIntervall="'.$NeuWinterSendeIntervall.'";'."\n\n";$i++;
 $IniArray[$i]='$EESendeIntervall="'.$NeuEESendeIntervall.'";'."\n\n";$i++;
 }


 if($MultiType==1){
 $IniArray[$i]='$Korrekturwert="'.$NeuKorrekturwert.'";'."\n";$i++;
 $IniArray[$i]='$KalibrierTemperatur="'.$NeuKalibrierTemperatur.'";'."\n";$i++;
 $IniArray[$i]='$KorrPressure="'.$NeuKorrPressure.'";'."\n\n";$i++;
 }

 if($MultiType > 1){
 $IniArray[$i]='$KorrekturwertArray=array("'.$NeuKorrekturwert1.'","'.$NeuKorrekturwert2.'","'.$NeuKorrekturwert3.'","'.$NeuKorrekturwert4.'","'.$NeuKorrekturwert5.'","'.$NeuKorrekturwert6.'");'."\n";$i++;
 $IniArray[$i]='$KalibrierTemperatur="'.$NeuKalibrierTemperatur.'";'."\n";$i++;
 $IniArray[$i]='$KorrPressure="'.$NeuKorrPressure.'";'."\n\n";$i++;
 }

 if(($MultiType==0) OR ($MultiType==1)){
 //Berechnung der Leergewichts
 $NeuBeutenLeergewicht=$NeuBeutenGewicht0;
 if ($NeuBeutenGewicht1=="") $NeuBeutenGewichtAktiviert1="";
 if(($NeuBeutenGewichtAktiviert1)&& ($NeuBeutenGewicht1 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht1;
 if ($NeuBeutenGewicht2=="") $NeuBeutenGewichtAktiviert2="";
 if(($NeuBeutenGewichtAktiviert2)&& ($NeuBeutenGewicht2 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht2;
 if ($NeuBeutenGewicht3=="") $NeuBeutenGewichtAktiviert3="";
 if(($NeuBeutenGewichtAktiviert3)&& ($NeuBeutenGewicht3 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht3;
 if ($NeuBeutenGewicht4=="") $NeuBeutenGewichtAktiviert4="";
 if(($NeuBeutenGewichtAktiviert4)&& ($NeuBeutenGewicht4 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht4;
 if ($NeuBeutenGewicht5=="") $NeuBeutenGewichtAktiviert5="";
 if(($NeuBeutenGewichtAktiviert5)&& ($NeuBeutenGewicht5 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht5;
 if ($NeuBeutenGewicht6=="") $NeuBeutenGewichtAktiviert6="";
 if(($NeuBeutenGewichtAktiviert6)&& ($NeuBeutenGewicht6 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht6;
 if ($NeuBeutenGewicht7=="") $NeuBeutenGewichtAktiviert7="";
 if(($NeuBeutenGewichtAktiviert7)&& ($NeuBeutenGewicht7 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht7;
 if ($NeuBeutenGewicht8=="") $NeuBeutenGewichtAktiviert8="";
 if(($NeuBeutenGewichtAktiviert8)&& ($NeuBeutenGewicht8 !=""))$NeuBeutenLeergewicht +=$NeuBeutenGewicht8;

 $IniArray[$i]='$BeutenLeergewicht="'.$NeuBeutenLeergewicht.'";'."\n";$i++;
 $IniArray[$i]='$BeutenGewicht=array("'.$NeuBeutenGewicht0.'","'.$NeuBeutenGewicht1.'","'.$NeuBeutenGewicht2.'","'.$NeuBeutenGewicht3.'","'.$NeuBeutenGewicht4.'","'.$NeuBeutenGewicht5.'","'.$NeuBeutenGewicht6.'","'.$NeuBeutenGewicht7.'","'.$NeuBeutenGewicht8.'");'."\n";$i++;
 $IniArray[$i]='$BeutenGewichtAktiviert=array("'.$NeuBeutenGewichtAktiviert0.'","'.$NeuBeutenGewichtAktiviert1.'","'.$NeuBeutenGewichtAktiviert2.'","'.$NeuBeutenGewichtAktiviert3.'","'.$NeuBeutenGewichtAktiviert4.'","'.$NeuBeutenGewichtAktiviert5.'","'.$NeuBeutenGewichtAktiviert6.'","'.$NeuBeutenGewichtAktiviert7.'","'.$NeuBeutenGewichtAktiviert8.'");'."\n";$i++;
 $IniArray[$i]='$BeutenGewichtBezeichner=array("'.$NeuBeutenGewichtBezeichner0.'","'.$NeuBeutenGewichtBezeichner1.'","'.$NeuBeutenGewichtBezeichner2.'","'.$NeuBeutenGewichtBezeichner3.'","'.$NeuBeutenGewichtBezeichner4.'","'.$NeuBeutenGewichtBezeichner5.'","'.$NeuBeutenGewichtBezeichner6.'","'.$NeuBeutenGewichtBezeichner7.'","'.$NeuBeutenGewichtBezeichner8.'");'."\n";$i++;
 if($NeuHonigraum1Leergewicht=="")$NeuHonigraum1Leergewicht=0.0; if($NeuHonigraum2Leergewicht=="")$NeuHonigraum2Leergewicht=0.0;
 $IniArray[$i]='$Honigraeume=array("'.$NeuHonigraum1Anzahl.'","'.(round ($NeuHonigraum1Leergewicht,2)).'","'.$NeuHonigraum2Anzahl.'","'.(round ($NeuHonigraum2Leergewicht,2)).'");'."\n";$i++;
 if($NeuUtil1Leergewicht=="")$NeuUtil1Leergewicht=0.0; if($NeuUtil2Leergewicht=="")$NeuUtil2Leergewicht=0.0; if($NeuUtil3Leergewicht=="")$NeuUtil3Leergewicht=0.0;
 $IniArray[$i]='$BeutenUtils=array(array("'.$NeuUtil1.'","'.(round ($NeuUtil1Leergewicht,2)).'"),array("'.$NeuUtil2.'","'.(round ($NeuUtil2Leergewicht,2)).'"),array("'.$NeuUtil3.'","'.(round ($NeuUtil3Leergewicht,2)).'"));'."\n";$i++;
 }
 else $IniArray[$i]='$BeutenLeergewicht="";'."\n";$i++;

 $IniArray[$i]='$Watchdog="'.$NeuWatchdog.'";'."\n\n";$i++;
 if($MultiType > 1){
 if($NeuEmailSchwarmAlarm1 !="aktiviert")$NeuEmailSchwarmAlarm1="deaktiviert";
 if($NeuEmailSchwarmAlarm2 !="aktiviert")$NeuEmailSchwarmAlarm2="deaktiviert";
 if($NeuEmailSchwarmAlarm3 !="aktiviert")$NeuEmailSchwarmAlarm3="deaktiviert";
 if($NeuEmailSchwarmAlarm4 !="aktiviert")$NeuEmailSchwarmAlarm4="deaktiviert";
 if($NeuEmailSchwarmAlarm5 !="aktiviert")$NeuEmailSchwarmAlarm5="deaktiviert";
 if($NeuEmailSchwarmAlarm6 !="aktiviert")$NeuEmailSchwarmAlarm6="deaktiviert";

 $IniArray[$i]='$EmailSchwarmAlarmArray=array("'.$NeuEmailSchwarmAlarm1.'","'.$NeuEmailSchwarmAlarm2.'","'.$NeuEmailSchwarmAlarm3.'","'.$NeuEmailSchwarmAlarm4.'","'.$NeuEmailSchwarmAlarm5.'","'.$NeuEmailSchwarmAlarm6.'");'."\n";$i++;
 $IniArray[$i]='$ReferenzZeitArray=array("'.$NeuReferenzZeit1.'","'.$NeuReferenzZeit2.'","'.$NeuReferenzZeit3.'","'.$NeuReferenzZeit4.'","'.$NeuReferenzZeit5.'","'.$NeuReferenzZeit6.'");'."\n";$i++;
 $IniArray[$i]='$DifferenzGewichtArray=array("'.$NeuDifferenzGewicht1.'","'.$NeuDifferenzGewicht2.'","'.$NeuDifferenzGewicht3.'","'.$NeuDifferenzGewicht4.'","'.$NeuDifferenzGewicht5.'","'.$NeuDifferenzGewicht6.'");'."\n";$i++;

 $IniArray[$i]='$DifferenzGewichtMaxArray=array("'.$NeuDifferenzGewicht1Max.'","'.$NeuDifferenzGewicht2Max.'","'.$NeuDifferenzGewicht3Max.'","'.$NeuDifferenzGewicht4Max.'","'.$NeuDifferenzGewicht5Max.'","'.$NeuDifferenzGewicht6Max.'");'."\n";$i++;

 if($NeuPushSchwarmAlarm1 !="aktiviert")$NeuPushSchwarmAlarm1="deaktiviert";
 if($NeuPushSchwarmAlarm2 !="aktiviert")$NeuPushSchwarmAlarm2="deaktiviert";
 if($NeuPushSchwarmAlarm3 !="aktiviert")$NeuPushSchwarmAlarm3="deaktiviert";
 if($NeuPushSchwarmAlarm4 !="aktiviert")$NeuPushSchwarmAlarm4="deaktiviert";
 if($NeuPushSchwarmAlarm5 !="aktiviert")$NeuPushSchwarmAlarm5="deaktiviert";
 if($NeuPushSchwarmAlarm6 !="aktiviert")$NeuPushSchwarmAlarm6="deaktiviert";
 $IniArray[$i]='$PushSchwarmAlarmArray=array("'.$NeuPushSchwarmAlarm1.'","'.$NeuPushSchwarmAlarm2.'","'.$NeuPushSchwarmAlarm3.'","'.$NeuPushSchwarmAlarm4.'","'.$NeuPushSchwarmAlarm5.'","'.$NeuPushSchwarmAlarm6.'");'."\n";$i++;
 }

 if($MultiType==1){
 if($NeuEmailSchwarmAlarm !="aktiviert")$NeuEmailSchwarmAlarm="deaktiviert";
 if($NeuPushSchwarmAlarm !="aktiviert")$NeuPushSchwarmAlarm="deaktiviert";
 $IniArray[$i]='$EmailSchwarmAlarm="'.$NeuEmailSchwarmAlarm.'";'."\n";$i++;
 $IniArray[$i]='$ReferenzZeit="'.$NeuReferenzZeit.'";'."\n";$i++;
 $IniArray[$i]='$DifferenzGewicht="'.$NeuDifferenzGewicht.'";'."\n";$i++;
 $IniArray[$i]='$DifferenzGewichtMax="'.$NeuDifferenzGewichtMax.'";'."\n";$i++;
 $IniArray[$i]='$PushSchwarmAlarm="'.$NeuPushSchwarmAlarm.'";'."\n";$i++;
 }

 $IniArray[$i]='$PushSchwarmPriorityArray=array("'.$NeuPushSchwarmPriority0.'","'.$NeuPushSchwarmPriority1.'","'.$NeuPushSchwarmPriority2.'","'.$NeuPushSchwarmPriority3.'","'.$NeuPushSchwarmPriority4.'");'."\n";$i++;

 if($MultiType > 0){
 $IniArray[$i]='$TriggerAlarmArray=array(
 array("Triggernummer","per Mail?","per Pushover?","Triggersensor","<,=oder >","Triggerwert","Sendepause","Autoanmerkung?","LastTriggerOption","PushPriorität"),
 array("Triggeralarm1","'.$NeuTriggerAlarmEmail1.'","'.$NeuTriggerAlarmPush1.'","'.$NeuTriggerAlarmSensor1.'","'.$NeuTriggerAlarmZeichen1.'","'.$NeuTriggerAlarmWert1.'","'.$NeuTriggerAlarmPause1.'","'.$NeuTriggerAlarmAnmerkung1.'","'.$NeuLastTriggerOption1.'","'.$NeuPushPriority1.'"),
 array("Triggeralarm2","'.$NeuTriggerAlarmEmail2.'","'.$NeuTriggerAlarmPush2.'","'.$NeuTriggerAlarmSensor2.'","'.$NeuTriggerAlarmZeichen2.'","'.$NeuTriggerAlarmWert2.'","'.$NeuTriggerAlarmPause2.'","'.$NeuTriggerAlarmAnmerkung2.'","'.$NeuLastTriggerOption2.'","'.$NeuPushPriority2.'"),
 array("Triggeralarm3","'.$NeuTriggerAlarmEmail3.'","'.$NeuTriggerAlarmPush3.'","'.$NeuTriggerAlarmSensor3.'","'.$NeuTriggerAlarmZeichen3.'","'.$NeuTriggerAlarmWert3.'","'.$NeuTriggerAlarmPause3.'","'.$NeuTriggerAlarmAnmerkung3.'","'.$NeuLastTriggerOption3.'","'.$NeuPushPriority3.'"),
 array("Triggeralarm4","'.$NeuTriggerAlarmEmail4.'","'.$NeuTriggerAlarmPush4.'","'.$NeuTriggerAlarmSensor4.'","'.$NeuTriggerAlarmZeichen4.'","'.$NeuTriggerAlarmWert4.'","'.$NeuTriggerAlarmPause4.'","'.$NeuTriggerAlarmAnmerkung4.'","'.$NeuLastTriggerOption4.'","'.$NeuPushPriority4.'"),
 );'."\n";$i++;
 $IniArray[$i]='$PushToken="'.htmlentities($NeuPushToken).'";'."\n";$i++;
 $IniArray[$i]='$PushUser="'.htmlentities($NeuPushUser).'";'."\n";$i++;
 $IniArray[$i]='$T_Token="'.htmlentities($NeuT_Token).'";'."\n";$i++;
 $IniArray[$i]='$T_Chat_Id="'.htmlentities($NeuT_Chat_Id).'";'."\n\n";$i++;
 $IniArray[$i]='$Empfaenger_Email="'.htmlentities($NeuEmpfaenger_Email).'";'."\n";$i++;
 $IniArray[$i]='$Absender_Email="'.htmlentities($NeuAbsender_Email).'";'."\n\n";$i++;

 $IniArray[$i]='$NextService="'.$NeuNextService.'";'."\n";$i++;
 if($NeuNextService=="P"){
 $IniArray[$i]='$NextServiceP_Time="'.time().'";'."\n\n";$i++;
 } // Pegelabfrage nur für 24 Stunden aktivieren
 else{
 $IniArray[$i]='$NextServiceP_Time="0";'."\n\n";$i++;
 }
 }

 if($MultiType > 1){
 if($NeuBeep1 !="aktiviert")$NeuBeep1="deaktiviert";
 if($NeuBeep2 !="aktiviert")$NeuBeep2="deaktiviert";
 if($NeuBeep3 !="aktiviert")$NeuBeep3="deaktiviert";
 if($NeuBeep4 !="aktiviert")$NeuBeep4="deaktiviert";
 if($NeuBeep5 !="aktiviert")$NeuBeep5="deaktiviert";
 if($NeuBeep6 !="aktiviert")$NeuBeep6="deaktiviert";

 $IniArray[$i]='$BeepArray=array("'.$NeuBeep1.'","'.$NeuBeep2.'","'.$NeuBeep3.'","'.$NeuBeep4.'","'.$NeuBeep5.'","'.$NeuBeep6.'");'."\n";$i++;
 $IniArray[$i]='$BeepIdArray=array("'.$NeuBeepId1.'","'.$NeuBeepId2.'","'.$NeuBeepId3.'","'.$NeuBeepId4.'","'.$NeuBeepId5.'","'.$NeuBeepId6.'");'."\n\n";$i++;
 }
 elseif($MultiType==1){
 if($NeuBeep !="aktiviert")$NeuBeep="deaktiviert";
 $IniArray[$i]='$Beep="'.htmlentities($NeuBeep).'";'."\n";$i++;
 $IniArray[$i]='$BeepId="'.htmlentities($NeuBeepId).'";'."\n\n";$i++;
 }

 if($MultiType > 1){
 if($NeuKeeper1 !="aktiviert")$NeuKeeper1="deaktiviert";
 if($NeuKeeper2 !="aktiviert")$NeuKeeper2="deaktiviert";
 if($NeuKeeper3 !="aktiviert")$NeuKeeper3="deaktiviert";
 if($NeuKeeper4 !="aktiviert")$NeuKeeper4="deaktiviert";
 if($NeuKeeper5 !="aktiviert")$NeuKeeper5="deaktiviert";
 if($NeuKeeper6 !="aktiviert")$NeuKeeper6="deaktiviert";

 $IniArray[$i]='$KeeperArray=array("'.$NeuKeeper1.'","'.$NeuKeeper2.'","'.$NeuKeeper3.'","'.$NeuKeeper4.'","'.$NeuKeeper5.'","'.$NeuKeeper6.'");'."\n";$i++;
 $IniArray[$i]='$KeeperIdArray=array("'.$NeuKeeperId1.'","'.$NeuKeeperId2.'","'.$NeuKeeperId3.'","'.$NeuKeeperId4.'","'.$NeuKeeperId5.'","'.$NeuKeeperId6.'");'."\n\n";$i++;
 }
 elseif($MultiType==1){
 if($NeuKeeper !="aktiviert")$NeuKeeper="deaktiviert";
 $IniArray[$i]='$Keeper="'.htmlentities($NeuKeeper).'";'."\n";$i++;
 $IniArray[$i]='$KeeperId="'.htmlentities($NeuKeeperId).'";'."\n\n";$i++;
 }

 //ThingsPeak für jeden
 if($MultiType==1 OR $MultiType==0 ){
 if($NeuThingsPeak !="aktiviert")$NeuThingsPeak="deaktiviert";
 $IniArray[$i]='$ThingsPeak="'.htmlentities($NeuThingsPeak).'";'."\n";$i++;
 $IniArray[$i]='$ThingsPeakApi="'.htmlentities($NeuThingsPeakApi).'";'."\n";$i++;
 $IniArray[$i]='$ThingsPeakChannel="'.htmlentities($NeuThingsPeakChannel).'";'."\n";$i++;
 $IniArray[$i]='$ThingsPeakSensoren=array("Sensoren","'.$NeuThingsPeakSensor1.'","'.$NeuThingsPeakSensor2.'","'.$NeuThingsPeakSensor3.'","'.$NeuThingsPeakSensor4.'","'.$NeuThingsPeakSensor5.'","'.$NeuThingsPeakSensor6.'","'.$NeuThingsPeakSensor7.'","'.$NeuThingsPeakSensor8.'");'."\n\n";$i++;
 }
 $IniArray[$i]='$WetterIcons="'.$NeuWetterIcons.'";'."\n";$i++;
// $IniArray[$i]='$ExWetterDaten="'.$NeuExWetterDaten .'";'."\n\n";$i++;

 if($MultiType > 0){
 $IniArray[$i]='$AkkuLeerSchwelle="'.$NeuAkkuLeerSchwelle.'";'."\n";$i++;
 $IniArray[$i]='$AkkuVollSchwelle="'.$NeuAkkuVollSchwelle.'";'."\n";$i++;

 $m=0;
 $MapArray[$m]="<?php\n";$m++;
 //MAP2
 $MapArray[$m]="//MAP2:\n".'$beeloggerMap2="'.htmlentities($NeubeeloggerMap2).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2Location="'.htmlentities($NeubeeloggerMap2Location).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2BeeID="'.htmlentities($NeubeeloggerMap2BeeID).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2Status="'.htmlentities($NeubeeloggerMap2Status).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2Lat="'.htmlentities($NeubeeloggerMap2Lat).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2Lon="'.htmlentities($NeubeeloggerMap2Lon).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2BeeloggerType="'.htmlentities($NeubeeloggerMap2BeeloggerType).'";'."\n";$m++;
 $MapArray[$m]='$beeloggerMap2Connect="'.htmlentities($NeubeeloggerMap2Connect).'";'."\n";$m++;

 //if($NeubeeloggerMap2URL !="aktiviert")$NeubeeloggerMap2URL="deaktiviert";
 $MapArray[$m]='$beeloggerMap2URL="'.htmlentities($NeubeeloggerMap2URL).'";'."\n";$m++;
 // 18.03.2024 if(substr(htmlentities($NeubeeloggerMap2URL), -19) !="/beelogger_show.php" AND htmlentities($NeubeeloggerMap2URL) !=""){
 if(strlen(htmlentities($NeubeeloggerMap2URL))>20){
 $MapArray[$m]='$beeloggerMap2URLfail="true";'."\n";$m++;
 }
 $MapArray[$m]='$beeloggerMap2Email="'.htmlentities($NeubeeloggerMap2Email).'";'."\n";$m++;
 if($MultiType > 1){
 $MapArray[$m]='$beeloggerMapWaage="'.htmlentities($NeubeeloggerMapWaage).'";'."\n\n";$m++;
 } //Variable für MAP1 und MAP2
 else { $MapArray[$m]='$beeloggerMapWaage="0";'."\n\n";$m++;} // Single
 $MapArray[$m]='$beeloggerMap2Sensoren=array("'.$NeubeeloggerMap2Sensor1.'","'.$NeubeeloggerMap2Sensor2.'","'.$NeubeeloggerMap2Sensor3.'","'.$NeubeeloggerMap2Sensor4.'","'.$NeubeeloggerMap2Sensor5.'","'.$NeubeeloggerMap2Sensor6.'");'."\n";$m++;
 $MapArray[$m]='$CurlArray=array('."\n".'""'.",\n".'""'.",\n".'""'.",\n".'""'.",\n".'""'.",\n".'""'.",\n".'""'.",\n".'""'.",\n".'"");'."\n";$m++;
 $MapArray[$m]='$beeloggerSketchID="'.htmlentities($NeubeeloggerSketchID).'";'."\n";$m++;
 $MapArray[$m]='$InfoSketchID="";'."\n";$m++;
 $fp=fOpen("beelogger_map.php", "w+");
 foreach($MapArray as $values){ fputs($fp, $values);}
 $OK=fputs($fp,"?>");
 fclose($fp);

 $IniArray[$i]='$Info="'.htmlentities($NeuInfo).'";'."\n\n";$i++;
 }

 // General_ini.php
 $w=0;
 $general_ini_Array[$w]='<?php'."\n";$w++;
 $general_ini_Array[$w]='$mW_tage="'.htmlentities($NeumobileWatch_tage).'";'."\n";$w++;
 $general_ini_Array[$w]='$mW_spalten="'.htmlentities($NeumobileWatch_spalten).'";'."\n";$w++;
 $general_ini_Array[$w]='$mW_sort="'.htmlentities($NeumobileWatch_Sort).'";'."\n\n";$w++;
 $general_ini_Array[$w]='$beeloggerMap2ID="'.htmlentities($NeubeeloggerMap2ID).'";'."\n";$w++;
 $general_ini_Array[$w]='$beeloggerMap2Key="'.htmlentities($NeubeeloggerMap2Key).'";'."\n\n";$w++;
 $general_ini_Array[$w]='$GeneralAutowatch="'.$NeuGeneralAutoWatch.'";'."\n";$w++;
 $general_ini_Array[$w]='$GeneralAutowatchTime="'.$NeuGeneralAutoWatchTime.'";'."\n\n";$w++;

 if(!$CommunityUser){
 $general_ini_Array[$w]='$OpenweathermapKey="'.$NeuOpenweathermapKey.'";'."\n\n";$w++;
 }

 $general_ini_Array[$w]='$Sprache="'.$NeuSprache.'";'."\n\n";$w++;
 $general_ini_Array[$w]='$beeloggerNamenSortierung="'.$NeubeeloggerNamenSortierung.'";'."\n\n";$w++;

 if($NeuBeelogger1_List !="aktiviert")$NeuBeelogger1_List="deaktiviert";
 $general_ini_Array[$w]='$beelogger1_List="'.$NeuBeelogger1_List.'";'."\n";$w++;
 $fp=fOpen("../general_ini.php", "w+");
 foreach($general_ini_Array as $values){ fputs($fp, $values);}
 $OK=fputs($fp,"?>");
 fclose($fp);

// Triggeralarme.php
 if($MultiType > 0){
 if(file_exists("Triggeralarm.php")){ include("Triggeralarm.php");}
 else{
 $TriggerGesendetArray=array("WannGesendet?","","","","");
 $LastTriggerArray=array("LastTrigger","false","false","false","false");
 }
 if($NeuTriggerAlarmEmail1=="aktiviert" OR $NeuTriggerAlarmPush1=="aktiviert" AND $TriggerGesendetArray[1]=="deaktiviert")$TriggerGesendetArray[1]="";
 if($NeuTriggerAlarmEmail2=="aktiviert" OR $NeuTriggerAlarmPush2=="aktiviert" AND $TriggerGesendetArray[2]=="deaktiviert")$TriggerGesendetArray[2]="";
 if($NeuTriggerAlarmEmail3=="aktiviert" OR $NeuTriggerAlarmPush3=="aktiviert" AND $TriggerGesendetArray[3]=="deaktiviert")$TriggerGesendetArray[3]="";
 if($NeuTriggerAlarmEmail4=="aktiviert" OR $NeuTriggerAlarmPush4=="aktiviert" AND $TriggerGesendetArray[4]=="deaktiviert")$TriggerGesendetArray[4]="";

 $TriggerDatei [0]="<?php // Hilfs-Datei zur Steuerung der Triggeralarm\n";
 $TriggerDatei [1]='$TriggerGesendetArray=array("WannGesendet?","'.$TriggerGesendetArray[1].'","'.$TriggerGesendetArray[2].'","'.$TriggerGesendetArray[3].'","'.$TriggerGesendetArray[4].'");'."\n";
 $TriggerDatei [2]='$LastTriggerArray=array("LastTrigger","'.$LastTriggerArray[1].'","'.$LastTriggerArray[2].'","'.$LastTriggerArray[3].'","'.$LastTriggerArray[4].'")'.";\n?>";

 $aktion=fOpen("Triggeralarm.php", "w+");
 foreach($TriggerDatei as $values) fputs($aktion,$values);
 fClose($aktion);
 }else {if(file_exists("Triggeralarm.php"))unlink ("Triggeralarm.php");}

 if($NeumobileWatch_sensor1==""){ //falls Init nötig
 $NeumobileWatch_sensor1=6;
 $NeumobileWatch_sensor2=0;
 $NeumobileWatch_roll=3;
 }

 if($NeumobileWatch_Show !="aktiviert")$NeumobileWatch_Show="deaktiviert";
 if($NeumobileWatch_tageswertanzeige !="aktiviert")$NeumobileWatch_tageswertanzeige="deaktiviert";
 if($NeumobileWatch_notes !="aktiviert")$NeumobileWatch_notes="deaktiviert";

 $IniArray[$i]='$mobileWatch_Show="'.$NeumobileWatch_Show.'";'."\n";$i++;
 $IniArray[$i]='$mW_sensor1="'.htmlentities($NeumobileWatch_sensor1).'";'."\n";$i++;
 $IniArray[$i]='$mW_sensor2="'.htmlentities($NeumobileWatch_sensor2).'";'."\n";$i++;
 $IniArray[$i]='$mW_roll="'.htmlentities($NeumobileWatch_roll).'";'."\n";$i++;
 $IniArray[$i]='$mW_tageswertanzeige="'.htmlentities($NeumobileWatch_tageswertanzeige).'";'."\n";$i++;
 $IniArray[$i]='$mW_legende="'.htmlentities($NeumobileWatch_legende).'";'."\n";$i++;
 $IniArray[$i]='$mW_twogrid="'.htmlentities($NeumobileWatch_twogrid).'";'."\n";$i++;
 $IniArray[$i]='$mW_one_y="'.htmlentities($NeumobileWatch_one_y).'";'."\n";$i++;
 $IniArray[$i]='$mW_notes="'.htmlentities($NeumobileWatch_notes).'";'."\n";$i++;

 if(($MultiType==0)||($MultiType==1)){ //single / sub only
 if($NeumobileCheck_Show !="aktiviert")$NeumobileCheck_Show="deaktiviert";
 $IniArray[$i]='$mobileCheck_Show="'.$NeumobileCheck_Show.'";'."\n";$i++;
 }

  // Sensor in mobileCheck anzeigen
  $IniArray[$i]='$MobileCheckSensor='."array(";
  for($b=1;$b<$AnzahlSensoren;$b++){$IniArray[$i] .="'".$NeuMobileCheckSensor[$b]."',";}
  if($NeuMobileCheckSensor[$b] !="")$IniArray[$i] .="'".$NeuMobileCheckSensor[$b]."');\n";
  else $IniArray[$i] .="'no.png');\n"; //für neuen Sensor als Default
  $i++;

 if($MultiType==-1){
 $IniArray[$i]="\n\n".'$XanalyseArray=array('."\n";$i++;
 for($b=1;$b<=$AnzahlSensoren;$b++){//31.12.22
 $IniArray[$i]='"'.$NeuXanalyseArray[$b].'",';$i++;
 if($NeuXanalyseArray[$b] !="" AND is_numeric($NeuSensor[$b]))$NeuSensor[$b]=$NeuXanalyseArray[$b];
 if($NeuXanalyseArray[$b]=="")$NeuSensor[$b]=$b;
 }
 $IniArray[$i]='"'.'"'.");\n";$i++;
 }

 $IniArray[$i]="\n\n".'$Sensoren=array('."\n";$i++;
 // Sensorcheck: Es darf keine Sensoren mit gleichem Namen geben !

 for($b=1;$b<=$AnzahlSensoren;$b++){
 if($NeuSensor[$b]=="")$NeuSensor[$b]=$b; // falls leer --> Zahl
 for($c=1;$c<=$AnzahlSensoren;$c++){
 if(($NeuSensor[$b]==$NeuSensor[$c]) AND ($b !=$c))$NeuSensor[$b] .=$b; //falls gleicher Sensorname? - Index dran
 }
 } //Ende Sensorcheck

 for($b=1;$b<=$AnzahlSensoren;$b++){
 $x=0;
 if(isset($SensorLoeschen[$b])){
 if($SensorLoeschen[$b] !="1")$x=0;
 else $x=1;
 }
 if($x==0){
 if($NeuAnzeige[$b] !="true")$NeuAnzeige[$b]="false"; //aus Checkbox kein false
 if((int)$NeuSensor[$b]==$b){ // wenn Sensorname eine Zahl, dann Sensoranzeige aus
 $NeuAnzeige[$b]="false"; // Anzeige aus
 }
 if($NeuEinheit[$b]=="")$NeuEinheit[$b]=" "; // RS leere einheit
 if($b==6 AND ($MultiType !=-1)){
 $HELP=$NeuAchse[6].",showInRangeSelector: true";
 $IniArray[$i]='"'.$NeuSensor[$b].'","'.$NeuFarbe[$b].'","'.$NeuAnzeige[$b].'","'.$HELP.'","'.$NeuEinheit[$b].'",'."\n";
 $i++;
 }
 else{
 $IniArray[$i]='"'.$NeuSensor[$b].'",';
 $i++;
 $IniArray[$i]='"'.$NeuFarbe[$b].'",';$i++;
 if($MultiType==-1 AND !is_numeric($NeuSensor[$b]) AND $NeuSensor[$b] !="")$IniArray[$i]='"true",';
 else $IniArray[$i]='"'.$NeuAnzeige[$b].'",';
 $i++;
 $IniArray[$i]='"'.$NeuAchse[$b].'",';$i++;
 $IniArray[$i]='"'.$NeuEinheit[$b].'",'."\n";$i++;
 }
 }
 else{//der Sensor wird gelöscht...
 }
 }

 $IniArray[$i]=");\n";

 //Tageswertoptionen
 $i++;
 $IniArray[$i]='$TageswertOptionArray='."array(";
 $tw="false";
 for($b=1;$b<=$AnzahlSensoren;$b++){
 $tw=$NeuTageswertOption[$b];
 if(is_numeric ($NeuSensor[$b]))$tw="false"; // sensor not active
 $IniArray[$i] .="'".$tw."',";
 }
 if($tw !="")$IniArray[$i] .="'".$tw."');\n\n";
 else $IniArray[$i] .="'false');\n\n"; //für neuen Sensor als Default

 // Icon vom Sensor festlegen
 $i++;
 $IniArray[$i]='$Icon='."array(";
 for($b=1;$b<$AnzahlSensoren;$b++){$IniArray[$i] .="'".$NeuIcon[$b]."',";}
 if($NeuIcon[$b] !="")$IniArray[$i] .="'".$NeuIcon[$b]."');\n\n";
 else $IniArray[$i] .="'no.png');\n\n"; //für neuen Sensor als Default

// Icon statt Name verwenden
 $i++;
 $IniArray[$i]='$UseIcon='."array(";
 for($b=1;$b<=$AnzahlSensoren;$b++){
 if(!isset($NeuUseIcon[$b])) array_push ($NeuUseIcon,"false");
 if($NeuUseIcon[$b] !="true")$NeuUseIcon[$b]="false";
 $IniArray[$i] .="'".$NeuUseIcon[$b]."',";
 }
 $IniArray[$i] .="'false');\n\n"; //für neuen Sensor als Default

 // FillGraph vom Sensor festlegen
 $i++;
 $IniArray[$i]='$FillGraph='."array(";
 for($b=1;$b<$AnzahlSensoren;$b++){
 if(($NeuFillGraph[$b] !="-")&&($NeuFillGraph[$b] !="")){$fg=(float)$NeuFillGraph[$b];
 if(is_float($fg)==false) $NeuFillGraph[$b]="";else{ if(($fg<0.1)||($fg > 1.0)) $NeuFillGraph[$b]="0.1";}
 }
	$IniArray[$i] .="'".$NeuFillGraph[$b]."',";
 }
 if(($NeuFillGraph[$b] !="-")&&($NeuFillGraph[$b] !="")){$fg=(float)$NeuFillGraph[$b];
 if(is_float($fg)==false) $NeuFillGraph[$b]="";else{ if(($fg<0.1)||($fg > 1.0)) $NeuFillGraph[$b]="0.1";}
 }
 $IniArray[$i] .="'".$NeuFillGraph[$b]."');\n\n";
 $fp=fOpen("beelogger_ini.php", "w+");
 foreach($IniArray as $values){ fputs($fp, $values);}
 $OK=fputs($fp,"?>"); //test ob Datei zu öffnen war
 fclose($fp);

 if($OK==false )$cfg_Message=$SAs[10]."!";

 //Formular zum Splitten der beeloger.csv Datei wurde abgesendet
 if($SplittenSichern=="1"){
 $input='beelogger.csv';
 $SplitArray=file($input);
 $i=sizeof($SplitArray);
 if($i > 5){ // is data?
 while ($i--){
 $what=trim($SplitArray[$i]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 if($x[$s-1] !=''){ //letzte Spalte=Zeitstempel abfragen
 $AktualisierungDate=$x[$s-1];
 break;
 }
 }

if(($ArchivDateiName=="") OR ($ArchivDateiName==$SAs[129]))$ArchivDateiName="Archiv".date("ymd",$ErstesSplitDatum)."-".date("ymd",$LetztesSplitDatum); //Datei-Name wurde nicht eingegeben
 $nDatei=$ArchivDateiName.".csv";
 if(file_exists($nDatei)) $ArchivDateiName=$ArchivDateiName."z";

 $filename=$ArchivDateiName.".csv";
 $fp=fOpen($filename, "w"); // Archiv schreiben
 foreach($SplitArray as $val){
  $what=trim($val);
  $x=explode( ",", $what );
  $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv
  if((int)$x[$s-1] >=(int)$ErstesSplitDatum) fputs($fp, $val);
  if((int)$x[$s-1]==(int)$LetztesSplitDatum){fputs($fp, $val);break;}
 }
 fclose($fp);

 $filename="beelogger.csv"; //Daten zurückschreiben
 $fp=fOpen($filename, "w");
 foreach($SplitArray as $val){
  $what=trim($val);
  $x=explode( ",", $what );
  $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv
  if((int)$x[$s-1] >=(int)$AktuellesSplitDatum) fputs($fp, $val);
  if((int)$x[$s-1]==(int)$AktualisierungDate){fputs($fp, $val);break;}
 }
 fclose($fp);

 // splitt notes
 $input='notes.csv';
 $SplitArray=file($input);
 $i=sizeof($SplitArray);
 if($i > 0){ // any?
 while ($i--){
  $what=trim($SplitArray[$i]);
  $x=explode( ",", $what );
  $s=sizeof($x);
  if($x[$s-1] !=''){ //letzte Spalte=Zeitstempel abfragen
    $AktualisierungDate=$x[$s-1];
    break;
  }
 }

 $NotesArchivName="notes".$ArchivDateiName.".csv";
 $fp=fOpen($NotesArchivName, "w"); // Archiv schreiben
 foreach($SplitArray as $val){
  $what=trim($val);
  $x=explode( ",", $what );
  $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv
  if((int)$x[$s-1] >=(int)$ErstesSplitDatum) fputs($fp, $val);
  if((int)$x[$s-1] <=(int)$LetztesSplitDatum)fputs($fp, $val);
 }
 fclose($fp);

 $NotesDateiName="notes.csv"; //Daten zurückschreiben
 $fp=fOpen($NotesDateiName, "w");
 foreach($SplitArray as $val){
  $what=trim($val);
  $x=explode( ",", $what );
  $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv
  if((int)$x[$s-1] >=(int)$AktuellesSplitDatum) fputs($fp, $val);
  if((int)$x[$s-1] <=(int)$AktualisierungDate) fputs($fp, $val);
 }
 fclose($fp);
 } // splitt notes

 CSVbuilder();
 $cfg_Message=$SAs[11].": ".$filename." ".$SAs[12];
 }
}
 //Ende Splitten der beeloger.csv Datei

//Resetten der beelogger.csv und speichern der bisherigen Daten im Archiv
 if($BeeloggerCSVReset=="1"){
 $ArchivName="Archiv".date("Ymd").".csv";
 rename("beelogger.csv",$ArchivName);
 $NotesDateiName="notes".$ArchivName;
 $input="notes.csv";  // Anmerkungen in notes_old schieben
 if(file_exists($input)){
   $array=file($input);
   $asize=count($array);  //Grösse des Arrays
   $fp=fOpen($NotesDateiName, "w");
   if($asize>0){
      foreach($array as $val){fwrite($fp,$val);}
   }
   fclose($fp);
  unlink("notes.csv");
 }
 sleep(2); // give server time to do filesystem actions
 if(file_exists("beelogger.csv")) unlink("beelogger.csv");
 if(file_exists("month.csv")) unlink("month.csv");
 if(file_exists("week.csv")) unlink("week.csv");
 }
 //Ende Reset


// CSV-Dateiloeschen
 if($CsvLoeschDatei !=""){
 if(file_exists($CsvLoeschDatei)) unlink($CsvLoeschDatei);
 $cfg_Message=$SAs[13].": ".$CsvLoeschDatei." ".$SAs[14];
 }
 //bestehenden beelogger löschen (Werte ankommend: N+Zahl und MDuo1 oder MTriple3)
function OrdnerLoeschen($verzeichnis){
 $help=opendir($verzeichnis);
 if($help){
 while ( false !==($file=readdir($help))){
 if( $file !="." and $file !=".." ){
 unlink($verzeichnis."/".$file); //alle Dateien aus dem Ordner löschen
 }}}
 rmdir($verzeichnis); //Den Ordner selbst löschen
}

function CsvLoeschen($verzeichnis){
   $fileList=glob($verzeichnis.'/*');
   foreach ($fileList as $file) {
      if(strpos($file, "csv") > 10){
         //unlink($file);
      }
   }
}

if(strpos($BeeloggerLoeschen, "B1")===0){ //beelogger1 Daten
 if(file_exists("../beelogger1")) {
   $fileList=glob("../beelogger1/*");
   foreach ($fileList as $file) {
      if(strpos($file, "csv") > 10){
         echo".";//unlink($file);
      }
   }
   $cfg_Message=" beelogger1 ... ".$KAs[13];
 }
}

 if(strpos($BeeloggerLoeschen, "N")===0){
 $LoeschNummer=intval(str_replace("N", "", $BeeloggerLoeschen));
 if(file_exists("../beelogger".$LoeschNummer)) OrdnerLoeschen("../beelogger".$LoeschNummer);
 $cfg_Message=$SAs[15].": beelogger".$LoeschNummer." ".$SAs[14];
 }

 if(strpos($BeeloggerLoeschen, "M")===0){
 $BeeloggerLoeschen=substr($BeeloggerLoeschen, 1); //ohne M
 $BeeloggerLoeschenSign=substr($BeeloggerLoeschen,0,1); //erster Buchstabe
 $BeeloggerLoeschenNumber=intval(preg_replace('![^0-9]!','',$BeeloggerLoeschen));
 // $BeeloggerLoeschenNumber=substr($BeeloggerLoeschen,2); //ab 2temZeichen=Zahl
 if(file_exists("../".$BeeloggerLoeschen)) OrdnerLoeschen("../".$BeeloggerLoeschen);
 $i=1;
 while (file_exists("../beelogger".$BeeloggerLoeschenSign.$BeeloggerLoeschenNumber."_".$i)){
 OrdnerLoeschen("../beelogger".$BeeloggerLoeschenSign.$BeeloggerLoeschenNumber."_".$i);
 $i++;
 }
 $cfg_Message=$SAs[16].": ".$BeeloggerLoeschen." ".$SAs[17]." beelogger".$BeeloggerLoeschenSign.$BeeloggerLoeschenNumber."_1 ".$SAs[18]." beelogger".$BeeloggerLoeschenSign.$BeeloggerLoeschenNumber."_".($i-1)." ".$SAs[19];
 }
 if(strpos($BeeloggerLoeschen,'Xanalyse')===0){
 $XanalyseLoeschNummer=intval(substr($BeeloggerLoeschen, -1));
 if(file_exists("../Xanalyse".$XanalyseLoeschNummer)) OrdnerLoeschen("../Xanalyse".$XanalyseLoeschNummer);
 $cfg_Message=$SAs[15].": Xanalyse".$XanalyseLoeschNummer." ".$SAs[14];
 }

// Neuen beeloggerOrdner anlegen
 if($NeubeeloggerAnlage=="N"){
 if($NeubeeloggerAnlagePasswort !=""){
 if(file_exists("../general_ini.php")) include ("../general_ini.php");//Sprache festlegen
 $beeloggerNumber=2;
 while (file_exists("../beelogger".$beeloggerNumber)){
 $beeloggerNumber++;
 }
 mkdir("../beelogger".($beeloggerNumber), 0777, true);
 copy('../beelogger1/beelogger_show.php', "../beelogger".$beeloggerNumber."/beelogger_show.php");
 copy('../beelogger1/beelogger_sml.php', "../beelogger".$beeloggerNumber."/beelogger_sml.php");
 copy('../beelogger1/beelogger_config.php', "../beelogger".$beeloggerNumber."/beelogger_config.php");
 copy('../beelogger1/beelogger_log.php', "../beelogger".$beeloggerNumber."/beelogger_log.php");
 copy('../beelogger1/beelogger_wetter.php', "../beelogger".$beeloggerNumber."/beelogger_wetter.php");
 if(file_exists('../beelogger1/CSVedit.php')) copy('../beelogger1/CSVedit.php', "../beelogger".$beeloggerNumber."/CSVedit.php");

 $pw='<?php $pw="'.$NeubeeloggerAnlagePasswort.'"; ?>';
 $aktion=fOpen('../beelogger'.$beeloggerNumber.'/pw.php',"w");
 fWrite($aktion , $pw);
 fClose($aktion);

 $cfg_Message=$SAs[20].": beelogger".$beeloggerNumber." ".$SAs[21];
 }
 else $cfg_Message=$SAs[134];
 }
 elseif($NeubeeloggerAnlage=="X"){
 if($NeubeeloggerAnlagePasswort !=""){
 if(file_exists("../general_ini.php")) include ("../general_ini.php");//Sprache festlegen
 $NeuXanalyseNumber=1;
 while (file_exists("../Xanalyse".$NeuXanalyseNumber)){
 $NeuXanalyseNumber++;
 }
 if(!file_exists("../Xanalyse".$NeuXanalyseNumber)){
 mkdir("../Xanalyse".$NeuXanalyseNumber, 0777, true);
 copy('../beelogger1/beelogger_show.php', "../Xanalyse".$NeuXanalyseNumber."/beelogger_show.php");
 copy('../beelogger1/beelogger_config.php', "../Xanalyse".$NeuXanalyseNumber."/beelogger_config.php");

 $pw='<?php $pw="'.$NeubeeloggerAnlagePasswort.'"; ?>';
 $aktion=fOpen('../Xanalyse'.$NeuXanalyseNumber.'/pw.php',"w");
 fWrite($aktion , $pw);
 fClose($aktion);

 $cfg_Message=$SAs[20].": Xanalyse".$NeuXanalyseNumber." ".$SAs[21];
 }
 else $cfg_Message=$SAs[134];
 }
 }
 elseif((int)$NeubeeloggerAnlage > 1){
 if($NeubeeloggerAnlagePasswort !=""){
 switch ($NeubeeloggerAnlage){
 case '2':
 $NeuMultiType="Duo";
 break;
 case '3':
 $NeuMultiType="Triple";
 break;
 case '4':
 $NeuMultiType="Quad";
 break;
 case '5': // take care of old option
 $NeuMultiType="Penta";
 break;
 case '6': // user servers only
 $NeuMultiType="Hexa";
 break;
 }
 if(file_exists("../general_ini.php")) include ("../general_ini.php");//Sprache festlegen
 $NeuMultiNumber=1;
 while (file_exists("../".$NeuMultiType.$NeuMultiNumber)){
 $NeuMultiNumber++;
 }
 mkdir("../".$NeuMultiType.$NeuMultiNumber, 0777, true);
 copy('../beelogger1/beelogger_show.php', "../".$NeuMultiType.$NeuMultiNumber."/beelogger_show.php");
 copy('../beelogger1/beelogger_sml.php', "../".$NeuMultiType.$NeuMultiNumber."/beelogger_sml.php");
 copy('../beelogger1/beelogger_log.php', "../".$NeuMultiType.$NeuMultiNumber."/beelogger_log.php");
 copy('../beelogger1/beelogger_config.php', "../".$NeuMultiType.$NeuMultiNumber."/beelogger_config.php");
 copy('../beelogger1/beelogger_wetter.php', "../".$NeuMultiType.$NeuMultiNumber."/beelogger_wetter.php");
 if(file_exists('../beelogger1/CSVedit.php')) copy('../beelogger1/CSVedit.php', "../".$NeuMultiType.$NeuMultiNumber."/CSVedit.php");
 $pw='<?php $pw="'.$NeubeeloggerAnlagePasswort.'"; ?>';
 $aktion=fOpen('../'.$NeuMultiType.$NeuMultiNumber.'/pw.php',"w");
 fWrite($aktion , $pw);
 fClose($aktion);

 for($i=1;$i <=(int)$NeubeeloggerAnlage;$i++){
 mkdir("../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i, 0777, true);
 copy('../beelogger1/beelogger_show.php', "../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i."/beelogger_show.php");
 copy('../beelogger1/beelogger_sml.php', "../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i."/beelogger_sml.php");
 copy('../beelogger1/beelogger_config.php', "../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i."/beelogger_config.php");
 copy('../beelogger1/beelogger_wetter.php', "../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i."/beelogger_wetter.php");
 if(file_exists('../beelogger1/CSVedit.php')) copy('../beelogger1/CSVedit.php', "../beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i."/CSVedit.php");
 $pw='<?php $pw="'.$NeubeeloggerAnlagePasswort.'"; ?>';
 $aktion=fOpen('../beelogger'.substr($NeuMultiType,0,1).$NeuMultiNumber."_".$i.'/pw.php',"w");
 fWrite($aktion , $pw);
 fClose($aktion);

 }
 $cfg_Message=$SAs[22].": ".$NeuMultiType.$NeuMultiNumber." ".$SAs[17]." beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_1 ".$SAs[18]." beelogger".substr($NeuMultiType,0,1).$NeuMultiNumber."_".$NeubeeloggerAnlage." ".$SAs[23]."!";
 }
 else $cfg_Message=$SAs[134];
 }


// Standort Loeschen
 if($StandortLoeschen=="1"){
 if(file_exists("loc.php")) unlink ("loc.php");
 }

// Schwarmalarmloeschen
 for($is=1;$is <=$MultiType;$is++){
 if($SchwarmalarmLoeschenArray[$is]=="1"){
 if($MultiType <=1)$WarnFile='Schwarminfo.php';//für Unter- und Normalbeelogger
 else $WarnFile='../beelogger'.$MultiSign.$ServerMultiNumber."_".$is.'/Schwarminfo.php';
 if(file_exists($WarnFile)) unlink ($WarnFile);
 }
 }
 } //if configsichern Teil2 & aktionen
 $Message=$cfg_Message;
}
//###################################

else{ // config html page

if (file_exists("Triggeralarm.php")){ include("Triggeralarm.php");}
else {
 $TriggerGesendetArray=array("WannGesendet?","","","","");
 $LastTriggerArray=array("LastTrigger","false","false","false","false");
}

$Farben=array($KAs[209],$KAs[210],$KAs[211],$KAs[212],$KAs[213],$KAs[214],$KAs[215],$KAs[216],$KAs[217],$KAs[218],$KAs[219],$KAs[220],$KAs[221],$KAs[222],$KAs[223],$KAs[224],$KAs[225],$KAs[226],$KAs[227]);

$HelpBackColor="#F2F2F2";
$HelpColor="blue";

$RealFarben=array("black","gray","silver","maroon","red","orange","green","lime","olive","orangered","yellow","gold","navy","blue","deepskyblue","purple","fuchsia","teal","aqua");


echo '<div id="KonfigAnzeige"> <!------- FORM für Neue beelogger_ini.php -------->';
echo"<form action='beelogger_show.php' name='ConfigMainForm' method='post'>";

if($MultiType==-1){//Xanalyse Array mit beeloggern und Sensoren erstellen
 $files=scandir("../");
 $AnzahlBeelogerCount=0;
 $NewCount=1;
 foreach ($files as $file){
 if($file !="beelogger_icons" && $file !="beelogger_sprachfiles" && is_dir("../".$file) && preg_match("/beelogger/",$file) ){
 $datei="../".$file."/week.csv";
 $iniDatei="../".$file."/beelogger_ini.php";

 if(file_exists($datei)){// beelogger existiert und hat Daten
 $Bienenvolkbezeichnung="auto"; //INIT
 include($iniDatei);
 $Anzahl=sizeof($Sensoren)/5;
 for($ac=0;$ac < $Anzahl;$ac++){
 $AngebotArray[$AnzahlBeelogerCount][0]=$file; //Name und ...

 if($Bienenvolkbezeichnung !="auto" AND $Bienenvolkbezeichnung !="") $AngebotArray[$AnzahlBeelogerCount][3]=$Bienenvolkbezeichnung;
 else $AngebotArray[$AnzahlBeelogerCount][3]="";

 $AngebotArray[$AnzahlBeelogerCount][1]=$Anzahl; // Anzahl Senoren
 if(!is_numeric($Sensoren[0+$ac*5])){
 $AngebotArray[$AnzahlBeelogerCount][2][$ac]=$Sensoren[0+$ac*5];//Sensorname
 $AngebotArray[$AnzahlBeelogerCount][4][$ac]=$Sensoren[4+$ac*5];//Sensoreinheit
 }
 }
 $AnzahlBeelogerCount++;
 }
 }
 }
 include("beelogger_ini.php"); //Reinit
}

if($MultiType >=1){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[0].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[0].'.</td></tr><tr><td>
 '.$HAs[1].'.</td></tr>
 </table>
 </details></td></table>';

 echo"<table border='4'><tr><td><img src='../beelogger_icons/info.png' width='50'<br><br><b>&nbsp;".$KAs[1]."&nbsp;&nbsp;</b></td>";
 echo'<td><form method="POST" action=""><p><textarea rows="6" name="neuinfo" cols="120" font size=6>'.html_entity_decode($Info).'</textarea></p></form></td></tr>';

 if($beeloggerSketchID !="" AND $beeloggerSketchID !="EE_"){
 $AktuelleServerSketchVersion=file_get_contents('https://www.community.beelogger.de/AktuelleSketchVersion.php');
 $AktuelleBeeloggerSketchVersionNummer="0";
 $AktuelleServerSketchVersionNummer="0";
 if(strlen ($beeloggerSketchID)>7){
 $AktuelleServerSketchVersionNummer=strstr($AktuelleServerSketchVersion,substr($beeloggerSketchID,0,-6));
 if($AktuelleServerSketchVersionNummer=="") $AktuelleServerSketchVersionNummer=strstr($AktuelleServerSketchVersion,substr($beeloggerSketchID,0,-8)); //2 Jahreszahlen kappen
 $AktuelleServerSketchVersionNummer=substr(strstr($AktuelleServerSketchVersionNummer,"+",true),-6);
 $AktuelleBeeloggerSketchVersionNummer=substr($beeloggerSketchID,-6);
 }
 echo"<tr><td>".$KAs[2].":&nbsp;</td>";
 if($AktuelleServerSketchVersionNummer > $AktuelleBeeloggerSketchVersionNummer){
 echo"<td><input type='text' name='beeloggersketchid' value='".$beeloggerSketchID."' size='30' maxlength='30'>";
 echo '<a href="https://beelogger.de/atmega328_stm32/downloads/" target="_blank" rel="noopener"> ...'.$KAs[3].' ('.substr($beeloggerSketchID,0,-6).$AktuelleServerSketchVersionNummer.")</a>";
 }
 else echo"<td><input type='text' name='beeloggersketchid' value='".$beeloggerSketchID."' size='30' maxlength='30'>";
 }
 echo"</td></tr>";
 echo '</table><br>';
}
elseif($MultiType==0){
 //Ermittlung des Links für den MultiHauptbeelogger
 $actual_linkpos=strrpos(strstr((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? "https" : "https") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE),"/");
 $NeubeeloggerURL=substr(strstr((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? "https" : "https") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE), 0,$actual_linkpos);
 $NeubeeloggerURL .='/'.$MultiTypeName.$ServerMultiNumber.'/beelogger_show.php';

 //Hinweis Info
echo'<table><td style="font-size:20px; color:blue;">'.$KAs[0].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[0].'.</td></tr><tr><td>
 '.$HAs[1].'.</td></tr>
 </table>
</details></td></table>';
 echo"<table border='2' style='color:black; background-color:white'><td><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';></td><td>&nbsp;".$KAs[241]."&nbsp;</td>";

 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";

 include ($WechselIniName);
 echo"<tr>";
 echo"<td><img src='../beelogger_icons/info.png' width='18' height='18' style='margin-bottom:-4px';></td>";
 echo'<td colspan=2 style="color:black; background-color:white"><form method="POST" action=""><p><textarea disabled="disabled" rows="6" name="nix" cols="80" font size=6>'.html_entity_decode($Info).'</textarea></p></form></td>';
 echo"</td></tr>";
 echo"</table><br>";
 include ("beelogger_ini.php");
}

//Sprache einstellen
echo'<table><td style="font-size:20px; color:blue;">'.$KAs[256].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[2].'.</td></tr><tr><td>
 '.$HAs[3].'.</td></tr>
 </table>
</details></td></table>';


echo"<table border='4'>";
echo"<td>&nbsp;".$KAs[144].": <select name='neusprache'>";
echo"<option value='1'";
if($Sprache=="1") echo" selected";
echo">🇩🇪".$KAs[145]."</option>";
echo"<option value='2'";
if($Sprache=="2") echo" selected";
echo">🇬🇧".$KAs[146]."</option>";
echo"<option value='3'";
if($Sprache=="3") echo" selected";
echo">🇫🇷".$KAs[147]."</option>";
echo"</select>&nbsp;</td>";
echo"</table><br>";


//Leergewichte / Futter / Tracht
if($MultiType >=0 && $MultiType < 2){
 echo'<br><table><td style="font-size:20px; color:blue;">'.$KAs[128].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><td>
 '.$HAs[19].'.</td>
 </table>
 </details></td></table>';

 echo"<table border='4' style='color:lightgray; background-color: #424949;'>";
 echo"\n<td rowspan=2><b>&nbsp;".$KAs[129].":&nbsp;</b></td>\n";
 echo"\n<td rowspan=2 style='background-color: white';>&nbsp;<img src='../beelogger_icons/Beute.png' height=40 width=40 style='margin-bottom:-4px';>&nbsp;</td>";
 if($BeutenLeergewicht !="" AND $BeutenLeergewicht > 1) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2>";

 echo"<table border=2>"; //Table für Beutengewichtberechnung
 echo"<tr><td><b>".$KAs[299]."</td><td>&nbsp;&nbsp;".$BeutenLeergewicht."&nbsp;".$GewichtsEinheit."&nbsp;</b></td></tr>";

 if($BeutenGewichtBezeichner[0]=="") $BeutenGewichtBezeichner[0]=$KAs[300];
 if($BeutenGewicht[0]==""){$BeutenGewicht[0]=$BeutenLeergewicht; $BeutenGewichtAktiviert[0]="x";}
 echo"<tr><td><b>=&nbsp;".$BeutenGewichtBezeichner[0]."</td><td><input type='text' style='width:40px;' style='display:inline;' name='neubeutengewicht0' value='".$BeutenGewicht[0]."' >&nbsp;".$GewichtsEinheit."&nbsp;</b></td></tr>";
 $b_gewicht=1;
 for($i=1;$i < 9;$i++){
 if($BeutenGewicht[$i] !=""){
 $b_gewicht++;
 if($BeutenGewichtBezeichner[$i]=="") $BeutenGewichtBezeichner[$i]=$KAs[301];
 if($BeutenGewichtAktiviert[$i]) echo"<tr style='color:black; background-color: #a9dfbf;'";
 else echo"<tr style='color:lightgray; background-color: #424949;'";
 echo"><td><b>+&nbsp;<input type='text' style='width:100px;' style='display:inline;' name='neubeutengewichtbezeichner".$i."' value='".$BeutenGewichtBezeichner[$i]."' >";
 echo"<input style='display:inline;' type='checkbox' name='neubeutengewichtaktiviert".$i."'";
 if($BeutenGewichtAktiviert[$i]) echo" checked";
 echo">&nbsp;</td>";
 echo"<td>&nbsp;<input type='text' style='width:40px;' style='display:inline;' name='neubeutengewicht".$i."' value='".$BeutenGewicht[$i]."' >&nbsp;".$GewichtsEinheit."&nbsp;</b></td>";
 echo"</tr>";
 }
 }
 if($b_gewicht<9){ // any unused?
 for($i=1;$i < 9;$i++){
 if($BeutenGewicht[$i]==""){
 if($BeutenGewichtBezeichner[$i]=="") $BeutenGewichtBezeichner[$i]=$KAs[301];
 echo"<tr style='color:lightgray; background-color: #424949;'";
 echo"><td><b>+&nbsp;<input type='text' style='width:100px;' style='display:inline;' name='neubeutengewichtbezeichner".$i."' value='".$BeutenGewichtBezeichner[$i]."' >";
 echo"<input style='display:inline;' type='checkbox' name='neubeutengewichtaktiviert".$i."'";
 echo">&nbsp;</td>";
 echo"<td>&nbsp;<input type='text' style='width:40px;' style='display:inline;' name='neubeutengewicht".$i."' value='".$BeutenGewicht[$i]."' >&nbsp;".$GewichtsEinheit."&nbsp;</b></td>";
 echo"</tr>";
 break; // just one
 }
 }
 }

 echo"</table>";
 echo"</td>";

 if($Honigraeume[0]=="")$hr_a=0;
 else $hr_a=(int)$Honigraeume[0];
 if($Honigraeume[1]=="")$hr_b=0;
 else $hr_b=(float)$Honigraeume[1];
 if(($hr_a + $hr_b) > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><b>&nbsp;+&nbsp;</b></td>";
 if(($hr_a + $hr_b) > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo"><b><input type='number' style='width:30px;' min='0' max='5' step='1' style='display:inline;' name='neuhonigraum1anzahl' value='".$hr_a."'>";
 echo"\n&nbsp;x&nbsp;".$KAs[131]."&nbsp;<input type='text' style='width:30px;' style='display:inline;' name='neuhonigraum1leergewicht' value='".$hr_b."' size='3'>&nbsp;".$GewichtsEinheit."&nbsp;</b></td>";

 //Varroaeinschub
 if($BeutenUtils[0][0]=="")$bt_a=0;
 else $bt_a=(int)$BeutenUtils[0][0];
 if($bt_a==1 AND $BeutenUtils[0][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><b>&nbsp;+&nbsp;</b></td>";
 echo"\n<td rowspan=2 style='background-color: white';>&nbsp;<img src='../beelogger_icons/n_Varroaeinschub.png' height=40 width=40 style='margin-bottom:-4px';>&nbsp;</td>";
 if($bt_a==1 AND $BeutenUtils[0][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><input type='checkbox' name='neuutil1' value='1'";
 if($bt_a==1) echo" checked";
 echo">\n&nbsp;".$KAs[269]."&nbsp;<input type='text' style='width:30px;' style='display:inline;' name='neuutil1leergewicht' value='".$BeutenUtils[0][1]."'>&nbsp;".$GewichtsEinheit."</b>";
 echo"&nbsp;</td>";
 // Absperrgitter
 if($BeutenUtils[1][0]=="")$bt_b=0;
 else $bt_b=(int)$BeutenUtils[1][0];
 if($bt_b==1 AND $BeutenUtils[1][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><b>&nbsp;+&nbsp;</b></td>";
 echo"\n<td rowspan=2 style='background-color: white';>&nbsp;<img src='../beelogger_icons/n_Absperrgitter.png' height=40 width=40 style='margin-bottom:-4px';>&nbsp;</td>";
 if($bt_b==1 AND $BeutenUtils[1][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><input type='checkbox' name='neuutil2' value='1'";
 if($bt_b==1) echo" checked";
 echo">\n&nbsp;".$KAs[270]."&nbsp;<input type='text' style='width:30px;' style='display:inline;' name='neuutil2leergewicht' value='".$BeutenUtils[1][1]."'>&nbsp;".$GewichtsEinheit."</b>";
 echo"&nbsp;</td>";
 // Futterzarge
 if($BeutenUtils[2][0]=="")$bt_c=0;
 else $bt_c=(int)$BeutenUtils[2][0];
 if($bt_c==1 AND $BeutenUtils[2][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><b>&nbsp;+&nbsp;</b></td>";
 echo"\n<td rowspan=2 style='background-color: white';>&nbsp;<img src='../beelogger_icons/n_Futterzarge.png' height=40 width=40 style='margin-bottom:-4px';>&nbsp;</td>";
 if($bt_c==1 AND $BeutenUtils[2][1] > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" rowspan=2><input type='checkbox' name='neuutil3' value='1'";
 if($bt_c==1) echo" checked";
 echo">\n&nbsp;".$KAs[271]."&nbsp;<input type='text' style='width:30px;' style='display:inline;' name='neuutil3leergewicht' value='".$BeutenUtils[2][1]."'>&nbsp;".$GewichtsEinheit."</b>";
 echo"&nbsp;</td>";
 echo"</tr><tr>";

 //Honigraum2
 if($Honigraeume[2]=="")$hr_a=0;
 else $hr_a=(int)$Honigraeume[2];
 if($Honigraeume[3]=="")$hr_b=0;
 else $hr_b=(float)$Honigraeume[3];
 if(($hr_a + $hr_b) > 0) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo"><b><input type='number' style='width:30px;' min='0' max='5' step='1' style='display:inline;' name='neuhonigraum2anzahl' value='".$hr_a."'>";
 echo"\n&nbsp;x&nbsp;".$KAs[131]."&nbsp;<input type='text' style='width:30px;' style='display:inline;' name='neuhonigraum2leergewicht' value='".$hr_b."'>&nbsp;".$GewichtsEinheit."&nbsp;</b></td>";
 echo"</tr>";

 echo"</table><br><br>";
}

// Autoanmerkungen ----------------------------------
if($MultiType > -1 && $MultiType < 2){ // für alle Einzelansichten
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[83].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[15].'.</td></tr>
 </table>
</details></td></table>';

 echo"<table border='4' style='color:black; background-color:#909497;'>";

 if($AutoAnmerkungenErzeugen=="aktiviert") echo"<td style='color:black; background-color:#a9dfbf;''>";
 else echo"<td>";

 echo"<b>\n&nbsp;".$KAs[85].":&nbsp;";
 echo"<select name='neuautoanmerkungenerzeugen'>";
 echo"<option value='aktiviert'";

 if($AutoAnmerkungenErzeugen=="aktiviert") echo" selected";
 echo">".$KAs[89]."</option>";

 echo"<option value='deaktiviert'";
 if($AutoAnmerkungenErzeugen=="deaktiviert") echo" selected";
 echo">".$KAs[33]."</option>";
 echo"</select>&nbsp;</td>";

 if($AutoAnmerkungenErzeugen=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"\n<b>&nbsp;".$KAs[86].": <input type='text' style='width:30px;' style='display:inline;' name='neuanmerkunggewichtsdifferenz' value='".$AnmerkungGewichtsDifferenz."'>&nbsp;".$GewichtsEinheit."&nbsp;</td>";

 if($AutoAnmerkungenErzeugen=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 if($AnmerkungGewichtsDifferenzMax=="") $AnmerkungGewichtsDifferenzMax=5.0;
 echo"\n<b>&nbsp;".$KAs[304].": <input type='text' style='width:30px;' style='display:inline;' name='neuanmerkunggewichtsdifferenzmax' value='".$AnmerkungGewichtsDifferenzMax."'>&nbsp;".$GewichtsEinheit."&nbsp;</td>";

 if($AutoAnmerkungenErzeugen=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;' colspan=2>";
 else echo"<td colspan=2>";
 echo"\n<b>&nbsp;".$KAs[87].":&nbsp;<input type='number' min='5' step='1' style='width:40px;' style='display:inline;' name='neuanmerkungzeitdifferenz' value='".$AnmerkungZeitDifferenz."' size='3' maxlength='3'>&nbsp;min&nbsp;</td>";

 if($MultiType <=1){
 echo '<tr><td colspan=3> <img src="../beelogger_icons/n_Durchsicht.png" height=20 width=20 style="margin-bottom:-3px";> '.$KAs[260].' ?';
 if($AutoServiceAnmerkung=="true") echo"<td style='color:black ; background-color: #a9dfbf;'>";
 else echo"<td style='color:black ; background-color: #e7846f;' colspan=2>";

 echo"&nbsp;<input type='checkbox' value='true' name='neuautoserviceanmerkung'";
 if($AutoServiceAnmerkung=="true") echo" checked";
 echo">&nbsp;</td>";
 //echo"<td>".$KAs[261]." </td>";
 //if($AutoServiceAnmerkung=="true") echo"<td style='color:black; background-color: #a9dfbf;' >";
 //else echo"<td>";
 //if($AutoServiceAnmerkungZeit=="") $AutoServiceAnmerkungZeit=5;
 //echo"\n<b>&nbsp;<input type='number' style='width:40px;' style='display:inline;' name='neuautoserviceanmerkungzeit' value='".$AutoServiceAnmerkungZeit."' min='5' max='90' step='1'>&nbsp;min&nbsp;</td>";
 echo"<tr>";
 }
 echo"</table><br>";
 }

 if($MultiType > 1)
 {
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[83].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[15].'.</td></tr>
 </table>
</details></td></table>';

 echo"<table border='4' style='color:black; background-color:#909497;'>";
 echo '<tr><td colspan=2> <img src="../beelogger_icons/n_Durchsicht.png" height=20 width=20 style="margin-bottom:-3px";> '.$KAs[260].'?';
 if($AutoServiceAnmerkung=="true") echo"<td style='color:black ; background-color: #a9dfbf;'>";
 else echo"<td style='color:black ; background-color: #e7846f;' colspan=2>";

 echo"&nbsp;<input type='checkbox' value='true' name='neuautoserviceanmerkung'";
 if($AutoServiceAnmerkung=="true") echo" checked";
 echo">&nbsp;</td>";
 //echo"<td> ".$KAs[261]." </td>";
 //if($AutoServiceAnmerkung=="true") echo"<td style='color:black; background-color: #a9dfbf;' >";
 //else echo"<td>";
 //if($AutoServiceAnmerkungZeit=="") $AutoServiceAnmerkungZeit=5;
 //echo"\n<b>&nbsp;<input type='number' style='width:40px;' style='display:inline;' name='neuautoserviceanmerkungzeit' value='".$AutoServiceAnmerkungZeit."' min='3' max='90' step='1'>&nbsp;min&nbsp;</td>";
 echo"<tr>";
 }
 echo"</table><br>";

//Watchdog
 if($MultiType >=0){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[132].'</td>';
 echo'<td><details><summary><img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary><table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[35].'.</td></tr></table></details></td></table>';

 echo"<table border='4' style='color:lightgray; background-color: #212f3c;'><td><b>&nbsp;".$KAs[133].":&nbsp;</b></td>";
 if($Watchdog=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>\n&nbsp;Watchdog:&nbsp;";
 echo"<select name='neuwatchdog'>";
 echo"<option value='aktiviert'";
 if($Watchdog=="aktiviert") echo" selected";
 echo">".$KAs[89]."</option>";
 echo"<option value='deaktiviert'";
 if($Watchdog=="deaktiviert") echo" selected";
 echo">".$KAs[33]."</option>";
 echo"</select>&nbsp;</td>";

 if(($GeneralAutowatch=="p") OR ($GeneralAutowatch=="m") OR ($GeneralAutowatch=="pm"))echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";

 echo"<b>\n&nbsp;GeneralAutoWatch:&nbsp;";
 if(($GeneralAutowatch=="p" OR $GeneralAutowatch=="pm") AND ($PushToken=="meinToken" OR $PushToken=="" OR $PushToken=="PushToken") AND ($PushUser=="meinUser" OR $PushUser=="" OR $PushUser=="PushUser")) echo $KAs[134];
 echo"<select name='neugeneralautowatch'>";

 echo"<option value='p'";
 if($GeneralAutowatch=="p") echo" selected";
 echo">Push (Push-".$KAs[242];
 if($MultiType==0) echo"<a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a>";
 echo")</option>";

 echo"<option value='m'";
 if($GeneralAutowatch=="m") echo" selected";
 echo">Mail (Mail-".$KAs[242];
 if($MultiType==0) echo"<a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a>";
 echo")</option>";

 echo"<option value='pm'";
 if($GeneralAutowatch=="pm") echo" selected";
 echo">Push & Mail (".$KAs[242];
 if($MultiType==0) echo"<a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a>";
 echo")</option>";

 echo"<option value='deaktiviert'";
 if($GeneralAutowatch=="deaktiviert") echo" selected";
 echo">".$KAs[33]."</option>";

 echo"</select>";

 echo"<b>\n&nbsp;".$KAs[136].":&nbsp;";
 echo"<select name='neugeneralautowatchtime'>";

 echo"<option value='9'";
 if($GeneralAutowatchTime=="9") echo" selected";
 echo">9 ".$KAs[137]."</option>";

 echo"<option value='15'";
 if($GeneralAutowatchTime=="15") echo" selected";
 echo">15 ".$KAs[137]."</option>";

 echo"<option value='21'";
 if($GeneralAutowatchTime=="21") echo" selected";
 echo">21 ".$KAs[137]."</option>";

 echo"<option value='Alle'";
 if($GeneralAutowatchTime=="Alle") echo" selected";
 echo">".$KAs[135]."</option>";

 echo"</select></td>";
echo"</table>";
echo"&nbsp;<br>";
}
// Schwarmalarm ----------------------------------
if($MultiType >=1){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[88].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[16].'</td></tr><tr><td>'.$HAs[36].'<img src="../beelogger_icons/h_Triggeralarm.png" height=51 style="margin-bottom:-6px";><br>'.$HAs[37].'<img src="../beelogger_icons/n_Triggeralarm.png" height=50 style="margin-bottom:-12px";>'.$HAs[38].'</td></tr>
 </table>
 </details></td></table>';
 }

if($MultiType > 1){
 echo"<table border='4' style='width:90%; color:black; background-color: #a4a4a4;'>";
 for($iS=1;$iS <=$MultiType;$iS++){

 echo"<tr><td style='background-color: white;' rowspan=2><img src='../beelogger_icons/n_Schwarmabgang.png' height=40 width=40 style='margin-bottom:-3px';></td><td><b>&nbsp;".$KAs[91].": ";
 if($MultiType > 1)echo" beelogger".$MultiSign.$ServerMultiNumber."_".$iS."&nbsp;";
 echo"</b></td>";

 if($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo" rowspan=2><b>\n&nbsp;".$KAs[92].": ";

 echo"<input type='checkbox' name='neuemailschwarmalarm".$iS."' value='aktiviert'";
 if($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if($PushSchwarmAlarmArray[$iS-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo" rowspan=1><b>\n&nbsp;".$KAs[93].": ";
 echo"<input type='checkbox' name='neupushschwarmalarm".$iS."' value='aktiviert'";
 if($PushSchwarmAlarmArray[$iS-1]=="aktiviert") echo" checked";
 echo">&nbsp;</td>";


 if(($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") OR ($PushSchwarmAlarmArray[$iS-1]=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo"\n rowspan=2><b>&nbsp;".$KAs[94]."_".$iS;
 if($ReferenzZeitArray[$iS-1] > 15) $ReferenzZeitArray[$iS-1]=15;
 echo": <input type='number' min='5' max='15' step='1' style='width:40px;' style='display:inline;' name='neureferenzzeit".$iS."' value='".$ReferenzZeitArray[$iS-1]."'>&nbsp;".$KAs[55]."&nbsp;</td>";

 if(($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") OR ($PushSchwarmAlarmArray[$iS-1]=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo"\n rowspan=2><b>&nbsp;".$KAs[95]."_".$iS;
 echo": <input type='text' style='width:30px;' style='display:inline;' name='neudifferenzgewicht".$iS."' value='".$DifferenzGewichtArray[$iS-1]."' size='4' maxlength='4'>&nbsp;".$GewichtsEinheit."&nbsp;&nbsp;&nbsp;</td>";

 if(($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") OR ($PushSchwarmAlarmArray[$iS-1]=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo"\n rowspan=2><b>&nbsp;".$KAs[304]."_".$iS;
 if($DifferenzGewichtMaxArray[$iS-1]=="") $DifferenzGewichtMaxArray[$iS-1]=4.0; //START
 echo": <input type='text' style='width:30px;' style='display:inline;' name='neudifferenzgewichtmax".$iS."' value='".$DifferenzGewichtMaxArray[$iS-1]."' size='4' maxlength='4'>&nbsp;".$GewichtsEinheit."&nbsp;&nbsp;&nbsp;</td>";

 if($SchwarmAlarmMessageArray[$iS] !='-'){
 echo"<td style='color:black; background-color: #a9dfbf;' rowspan=2>";
 echo"<b>\n".$SchwarmAlarmMessageArray[$iS]."&nbsp;-&nbsp;".$KAs[96].": ";
 echo '<input type="checkbox" name="schwarmalarmloeschen'.$iS.'" value="1">';
 }
 echo"</tr>";
 echo"<tr>";
 if($EmailSchwarmAlarmArray[$iS-1]=="aktiviert" OR $PushSchwarmAlarmArray[$iS-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>&nbsp;".$KAs[89];
 else echo"<td>&nbsp;".$KAs[33];
 echo"</td>";
 if(!isset($PushSchwarmPriorityArray[$iS])) $PushSchwarmPriorityArray[$iS]=0;
 else {if($PushSchwarmPriorityArray[$iS]=="") $PushSchwarmPriorityArray[$iS]=0;}
 if(($EmailSchwarmAlarmArray[$iS-1]=="aktiviert") OR ($PushSchwarmAlarmArray[$iS-1]=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>\n&nbsp;".$KAs[306].": "."&nbsp;";
 echo"<input type='number' style='width:40px;' min='-2' max='2' step='1' name='neupushschwarmpriority".$iS."' value='".$PushSchwarmPriorityArray[$iS]."'";
 echo">&nbsp;</td>";

 echo"</tr>";
 }
 echo"</table>";
}

elseif($MultiType==1)
 {
 echo"<table border='4' style='width:90%; color:black; background-color: #a4a4a4;'><td style='background-color: white;' rowspan=2><img src='../beelogger_icons/n_Schwarmabgang.png' height=40 width=40 style='margin-bottom:-3px';></td><td><b>&nbsp;".$KAs[91].": &nbsp;</b></td>";

 if($EmailSchwarmAlarm=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo" rowspan=2><b>\n&nbsp;".$KAs[92].":&nbsp;";

 echo"<input type='checkbox' name='neuemailschwarmalarm' value='aktiviert'";
 if($EmailSchwarmAlarm=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if($PushSchwarmAlarm=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo" rowspan=1><b>\n&nbsp;".$KAs[93].":&nbsp;";

 echo"<input type='checkbox' name='neupushschwarmalarm' value='aktiviert'";
 if($PushSchwarmAlarm=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if(($EmailSchwarmAlarm=="aktiviert") OR ($PushSchwarmAlarm=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 if($ReferenzZeit > 15) $ReferenzZeit=15;
 echo"\n rowspan=2><b>&nbsp;".$KAs[94].": <input type='number' step='1' min='5' min='15' style='width:40px;' style='display:inline;' name='neureferenzzeit' value='".$ReferenzZeit."'>&nbsp;".$KAs[55]."&nbsp;</td>";

 if(($EmailSchwarmAlarm=="aktiviert") OR ($PushSchwarmAlarm=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo"\n rowspan=2><b>&nbsp;".$KAs[95].": <input type='text' style='width:30px;' style='display:inline;' name='neudifferenzgewicht' value='".$DifferenzGewicht."'>&nbsp;".$GewichtsEinheit."&nbsp;&nbsp;</td>";

 if(($EmailSchwarmAlarm=="aktiviert") OR ($PushSchwarmAlarm=="aktiviert")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 if($DifferenzGewichtMax=="") $DifferenzGewichtMax=4.0;

 echo"\n rowspan=2><b>&nbsp;".$KAs[304].": <input type='text' style='width:30px;' style='display:inline;' name='neudifferenzgewichtmax' value='".$DifferenzGewichtMax."'>&nbsp;".$GewichtsEinheit."&nbsp;&nbsp;</td>";

 if($SchwarmAlarmMessageArray[0] !='-'){
 echo"<td style='color:black; background-color: #a9dfbf;' rowspan=2>";
 echo"<b>\n".$SchwarmAlarmMessageArray[0]."&nbsp;-&nbsp;".$KAs[96].": ";
 echo"<select name='schwarmalarmloeschen'>";
 echo"<option value='0' selected >".$KAs[10]."</option>";
 echo"<option value='1' >".$KAs[24]."</option>";
 echo"&nbsp;&nbsp;";
 echo"</select></td>";
 }

 echo"</tr>";
 echo"<tr>";
 if($EmailSchwarmAlarm=="aktiviert" OR $PushSchwarmAlarm=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>&nbsp;".$KAs[89];
 else echo"<td>&nbsp;".$KAs[33]."</td>";

 if(isset($PushSchwarmPriorityArray[1])){if($PushSchwarmPriorityArray[1]=="") $PushSchwarmPriorityArray[1]=0;}
 else {$PushSchwarmPriorityArray[0]=0;$PushSchwarmPriorityArray[1]=0;}
 if($EmailSchwarmAlarm=="aktiviert" OR $PushSchwarmAlarm=="aktiviert") echo"<td style='color:black; background-color: #a4a4a4;'>";
 else echo"<td>";
 echo"<b>\n&nbsp;".$KAs[306].": "."&nbsp;";
 echo"<input type='number' style='width:40px;' min='-2' max='2' step='1' name='neupushschwarmpriority1' value='".$PushSchwarmPriorityArray[1]."'";
 echo">&nbsp;</td>";

 echo"</tr>";
 echo"</table>";
}

elseif($MultiType==0){ //beelogger D, T oder Q-Typ
 //Hinweis Schwarmalarm
 echo'<span style="font-size:20px; color:blue;">'.$KAs[88].'</span>';
 echo"<table border='2' style='color:black; background-color: white'><td align=middle><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';</td><td>&nbsp;".$KAs[241]."&nbsp;</td>";
 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";
 include ($WechselIniName);
 echo"<tr>";
 echo"<td><img src='../beelogger_icons/off_bienen_out.png' width='18' height='18' style='margin-bottom:-4px';</td>";
 echo"<td colspan=3 align=middle style='color:black; ";

 $Aktive=0;
 for($i=1;$i<5;$i++){
 if($TriggerAlarmArray[$i][1]=="aktiviert" OR $TriggerAlarmArray[$i][2]=="aktiviert") $Aktive ++;
 }

 if($EmailSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert" OR $PushSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert" OR $Aktive > 0) echo"background-color: #a9dfbf;'>";
 else echo"background-color:#a4a4a4;'>";
 echo $KAs[248]."&nbsp;";
 if($EmailSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo $KAs[92];
 if($EmailSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert" AND $PushSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo" und ";
 if($PushSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo $KAs[93];
 if($EmailSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert" OR $PushSchwarmAlarmArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo"&nbsp;".$KAs[274].": ".$ReferenzZeitArray[$ServerMultiUnterordnerNummer-1]."&nbsp;".$KAs[275].":".$DifferenzGewichtArray[$ServerMultiUnterordnerNummer-1]." ".$GewichtsEinheit."&nbsp;".$KAs[89].".";
 else echo $KAs[33].".";

 $Aktive=0;
 for($i=1;$i < 5;$i++){
 if($TriggerAlarmArray[$i][1]=="aktiviert" OR $TriggerAlarmArray[$i][2]=="aktiviert"){
 if($Aktive > 0) echo" + Triggeralarm".$i;
 else echo" Triggeralarm".$i;
 $Aktive ++;
 }
 }
 if($Aktive==1) echo $KAs[252];
 elseif($Aktive > 1) echo $KAs[292].".";
 else echo" - ".$KAs[293].".";

 echo"</td></tr>";
 echo"</table><br>";
 include ("beelogger_ini.php");
}

// Triggeralarm -----------------------------------------
if($MultiType > 0){// Triggeralarmeinstellungen
 echo"<table border='4' style='width:90%; color:black; background-color: #a4a4a4;'>";
 $t=1;
 while ($t<5){
 if($t==1 OR $TriggerAlarmArray[$t-1][1]=="aktiviert" OR $TriggerAlarmArray[($t-1)][2]=="aktiviert" OR $TriggerAlarmArray[($t-1)][5] !="" OR $TriggerAlarmArray[$t][1]=="aktiviert" OR $TriggerAlarmArray[$t][2]=="aktiviert" OR $TriggerAlarmArray[$t][5] !=""){
 $pm_sel=true;
echo"\n\n";
 if(($TriggerAlarmArray[$t][1]=="aktiviert" OR $TriggerAlarmArray[$t][2]=="aktiviert" OR $TriggerAlarmArray[$t][7]=="aktiviert") AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<tr style='color:black; background-color: #a9dfbf;'>";
 else echo"<tr>";
 echo"<td style='background-color: white;' rowspan=2><img src='../beelogger_icons/n_Triggeralarm.png' height=40 width=40 style='margin-bottom:-3px';></td>";

 echo"<td style='color:black; background-color: #a4a4a4;'><b>&nbsp;".$KAs[263].$t.": &nbsp;</b></td>";

 if($TriggerAlarmArray[$t][1] !="aktiviert" AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<td style='color:black; background-color: #a4a4a4;' ";else echo"<td";

 echo" rowspan=2><b>\n&nbsp;".$KAs[92].":"."&nbsp;";

 echo"<input type='checkbox' name='neutriggeralarmemail".$t."' value='aktiviert'";
 if($TriggerAlarmArray[$t][1]=="aktiviert" AND $TriggerGesendetArray[$t] !="deaktiviert"){$pm_sel=false;echo" checked";}
 echo">&nbsp;</td>";

 if($TriggerAlarmArray[$t][2] !="aktiviert" AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<td style='color:black; background-color: #a4a4a4;'>";else echo"<td>";
 echo"<b>\n&nbsp;".$KAs[93]."&nbsp;";
 echo"<input type='checkbox' name='neutriggeralarmpush".$t."' value='aktiviert'";
 if($TriggerAlarmArray[$t][2]=="aktiviert" AND $TriggerGesendetArray[$t] !="deaktiviert"){$pm_sel=false;echo" checked";}
 echo">&nbsp;</td>";

 echo"\n<td rowspan=2><b>&nbsp;"."Sensor: "."<select style='display:inline;' name='neutriggeralarmsensor".$t."'>";
 for($i=0;$i < $AnzahlSensoren;$i++){
 if(!is_numeric($Sensoren[($i)*5])){
 echo"<option value='".($i)."'";
 if($TriggerAlarmArray[$t][3]==$i) echo" selected";
 echo">".$Sensoren[($i)*5]."</option>";
 }
 }
 echo"</select></td>";

 echo"\n<td rowspan=2>&nbsp;<select style='display:inline;' name='neutriggeralarmzeichen".$t."' >&nbsp;";
 echo"<option value='<' ";if($TriggerAlarmArray[$t][4]=="<") echo" selected";
 echo">".$KAs[276]."</option>";
 echo"<option value='>' ";if($TriggerAlarmArray[$t][4]==">") echo" selected";
 echo">".$KAs[277]."</option>";
 echo"</select>&nbsp;</td>";

 echo"\n<td rowspan=2>&nbsp;<input type='text' style='width:40px;' style='display:inline;' name='neutriggeralarmwert".$t."' value='".$TriggerAlarmArray[$t][5]."'>&nbsp;</td>";
 if( $TriggerAlarmArray[$t][5] !="")$pm_sel=false;
 echo"\n<td rowspan=1>&nbsp;".$KAs[278].": <select style='display:inline;' name='neutriggeralarmpause".$t."' >&nbsp;";

 echo"<option value='1440' ";if($TriggerAlarmArray[$t][6]=="1440") echo" selected";
 if($pm_sel) echo" selected";
 echo">"."24 ".$KAs[282]." (Pushover/Telegram & Mail)"."</option>";
 echo"<option value='9999' ";if($TriggerAlarmArray[$t][6]=="9999") echo" selected";
 echo">".$KAs[283]."</option>";
 echo"</select>&nbsp;</td>";


 if($TriggerAlarmArray[$t][7] !="aktiviert") echo"<td style='color:black; background-color: #a4a4a4;'>";
 else echo"<td>";
 echo"\n&nbsp;".$KAs[284]."&nbsp;";
 echo"<input style='display:inline;' type='checkbox' name='neutriggeralarmanmerkung".$t."' value='aktiviert'";
 if($TriggerAlarmArray[$t][7]=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 echo"</tr>";

 //zeile 2
 if(($TriggerAlarmArray[$t][1]=="aktiviert" OR $TriggerAlarmArray[$t][2]=="aktiviert") AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<tr style='color:black; background-color: #a9dfbf;'><td><b>\n&nbsp;"."Aktiviert"."&nbsp;</td>";
 else echo"<tr><td><b>\n&nbsp;".$KAs[33]."&nbsp;</td>";

 if($TriggerAlarmArray[$t][2] !="aktiviert" AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<td style='color:black; background-color: #a4a4a4;'>";else echo"<td>";
 echo"<b>\n&nbsp;".$KAs[306].": "."&nbsp;";
 if($TriggerAlarmArray[$t][9]=="")$TriggerAlarmArray[$t][9]=0;
 echo"<input type='number' style='width:40px;' min='-2' max='2' step='1' name='neupushpriority".$t."' value='".$TriggerAlarmArray[$t][9]."'";
 echo">&nbsp;</td>";


 if(!$TriggerAlarmArray[$t][8]) echo"<td style='color:black; background-color: #a4a4a4;'>";
 else echo"<td>";
 echo"\n&nbsp;".$KAs[305]."&nbsp;";
 echo"<input style='display:inline;' type='checkbox' name='neulasttriggeroption".$t."' value='true'";
 if($TriggerAlarmArray[$t][8]) echo" checked";
 echo">&nbsp;</td>";


 if($TriggerGesendetArray[$t] !="" AND $TriggerGesendetArray[$t] !="deaktiviert") echo"<td>".$KAs[286].": ".date("d.m H:i",$TriggerGesendetArray[$t])." Uhr";
 elseif($TriggerGesendetArray[$t]=="deaktiviert") echo"<td>&nbsp;".$KAs[285];
 else echo"<td>&nbsp;".$KAs[287];
 echo"</td>";
 echo"</tr>";
 }
 $t++;
 } //while
 echo"</table><br>";

// Alarm Daten
 if($CommunityUser){ include("../data.php");} //Email nur aus ini
 if($CommunityUser && $TestAccountUser) echo"<br>".$KAs[97]; //Info für Testaccountuser
 echo"<table border='4' style='width:90%; color:black; background-color: #a4a4a4;'><td rowspan=2><b>&nbsp;".$KAs[98]."&nbsp;&nbsp;</b></td>";
 echo"<td rowspan=2>&nbsp;&nbsp;E-Mail:&nbsp;&nbsp;</td>";

 if(($Empfaenger_Email !="empfaenger@meineDomain.de")&&(strlen($Empfaenger_Email) > 7)) echo"<td style='color:black; background-color:#a9dfbf;'";
 else {
   echo"<td";
   if($Empfaenger_Email=="empfaenger@meineDomain.de"){if(isset($Email))$Empfaenger_Email=$Email;}//aus data.php
 }
 echo" align=right>\n<b>&nbsp;".$KAs[99].": <input type='text' style='display:inline;' name='neuempfaenger_email' value='".$Empfaenger_Email."' size='30' maxlength='100'>&nbsp;&nbsp;</td>";

 // pushover
 echo"<td rowspan=2>&nbsp;&nbsp;Pushover:&nbsp;&nbsp;</td>";
 if($PushUser !="meinUser" AND $PushUser !="" AND $PushUser !="PushUser") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;Push-User-Key: <input type='text' style='display:inline;' name='neupushuser' value='".$PushUser."' size='12' maxlength='50'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";

 // Telegramm
 echo "<td rowspan=2>&nbsp;&nbsp;Telegram:&nbsp;&nbsp;</td>";
 if(!isset($T_Token)) $T_Token="Token";
 if(($T_Token !="Token") AND ($T_Token !="")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;Telegram-Token: <input type='text' style='display:inline;' name='neut_token' value='".$T_Token."' size='12' maxlength='50'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";

 echo"</tr><tr>";

 if($Absender_Email !="absender@meineDomain.de") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;".$KAs[100].": <input type='text' style='display:inline;' name='neuabsender_email' value='".$Absender_Email."' size='25' maxlength='100'>&nbsp;&nbsp;</td>";

 if($PushToken !="meinToken" AND $PushToken !="" AND $PushToken !="PushToken") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;Push-Application-Token: <input type='text' style='display:inline;' name='neupushtoken' value='".$PushToken."' size='12' maxlength='50'>&nbsp;&nbsp;&nbsp;</td>";

 if(!isset($T_Chat_Id)) $T_Chat_Id="Id";
 if(($T_Chat_Id !="Id") AND ($T_Chat_Id !="")) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;Chat Id: <input type='text' style='display:inline;' name='neut_chatid' value='".$T_Chat_Id."' size='12' maxlength='50'>&nbsp;&nbsp;&nbsp;</td>";
 echo"</tr>";
 echo"</table><br>";
}
echo"<br>&nbsp;";

 //Tageswert als Mail
if(($MultiType==0)||($MultiType==1)){ //single / sub only
 echo'<table><td style="font-size:20px; color:blue;"> '.$SAs[64].' Mail</td>
 <td><details>
 <summary><img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";></summary>
 <table style="font-size:15px; color:blue; background-color: #F2F2F2;"><tr><td>'.$HAs[42].'</td></tr></table>
 </details></td></table>';
 echo"<table border='4' style='color:lightgray; background-color: #0a0a0f;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/watch.png' height=30 width=20><td><b>&nbsp; ".$KAs[198].": &nbsp;</b></td> ";
 if(!isset($TageswertMail)){$TageswertMail="deaktiviert";$TageswertMail_adresse="";}

 if($TageswertMail=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;".$SAs[64]." Mail ... &nbsp;&nbsp; ? &nbsp;";

 echo"<input type='checkbox' name='tageswertmail' value='aktiviert'";
 if($TageswertMail=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if(($TageswertMail_adresse !="empfaenger@meineDomain.de")&&(strlen($TageswertMail_adresse) > 5)) echo"<td style='color:black; background-color:#a9dfbf;'";
 else echo"<td";
 echo" align=right>\n<b>&nbsp;".$KAs[99].": <input type='text' style='display:inline;' name='tageswertmail_adresse' value='".$TageswertMail_adresse."' size='25' maxlength='100'>&nbsp;&nbsp;</td>";
 echo '</table><br><br><br>';
}

//Symbole: Daten speichern/verwerfen und zurück zur Show
echo"<table>";
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/arrow.png" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/konfig1.png" height="50" ><img src="../beelogger_icons/konfig.png" value="1" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/cancel.png" height="50" ></td></tr>';

echo"<tr><td><button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='configsichern' value='1'><b>".$KAs[203]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: red;' name='configsichern' value='0'><b>".$KAs[205]."</b></button>&nbsp;&nbsp;</td>";
echo"</table>";

echo "<br><hr><hr><br>";

//Show Passwort
echo '<table><td style="font-size:20px; color:blue;">'.$KAs[4].'</td>';

echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[4].'.</td>';

if($MultiType > 0) echo '<td>'.$HAs[5].': ! * ‘ ( ) ; : @ &=+ $ , / ? % # [ ]'.$HAs[6].'.</td>';
else echo"<td></td>";

echo' </tr>
<tr><td colspan=2>'.$HAs[7].'.</td></tr>
 </table>
</details></td></table>';

echo"<table border='4' style='color:black; background-color: #f8f9f9;'><tr>";

if($BeeloggerShowPasswort=="Show") echo"<td style='color:black; background-color:#e7846f;'>";
else echo"<td style='color:black; background-color:#a9dfbf;'>";
echo"<b>&nbsp;".$SAs[43]." (beelogger_show) - ".$KAs[7].": <input type='text' name='neubeeloggershowpasswort' value='".$BeeloggerShowPasswort."' size='15' maxlength='20'>&nbsp;</td>";

/*if($MultiType > 0){
 if($BeeloggerLogPasswort=="Log") echo"<td style='color:grey; background-color:#e7846f'>";
 else echo"<td style='color:grey; background-color:#a9dfbf;'>";
 echo '<b>&nbsp;beelogger_log - '.$KAs[7].': '.$BeeloggerLogPasswort.'</td>';
}*/

echo"</table>";
echo"<br>&nbsp;";


//Grafikbereich Konfiguration
echo'<table><td style="font-size:20px; color:blue;">'.$KAs[138].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[21].'.<img src="../beelogger_icons/n_Koenigin weiss gezeichnet.png" height=40 width=40 style="margin-bottom:-11px";></td></tr>
 </table>
</details></td></table>';
echo "<br>";
echo"<table border=5 style='color:white; background-color: black'><tr>";
echo"\n<td><b>&nbsp;".$KAs[139].":&nbsp; <input type='text' style='display:inline;' name='neubienenvolkbezeichnung' value='".html_entity_decode($Bienenvolkbezeichnung)."' size='15' maxlength='20'>&nbsp;</td></tr>";

if($beeloggerNamenSortierung=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'";
else echo"<tr><td";
echo">&nbsp;".$KAs[291]."?&nbsp;<input type='checkbox' name='neubeeloggernamensortierung' value='aktiviert'";
if($beeloggerNamenSortierung=="aktiviert") echo" checked";
echo">&nbsp;</td></tr>";
echo"</table>";

echo"<table border=5 style='color:white; background-color: black'>";
echo"<td><b>&nbsp;".$KAs[140].": <select name='neustandardcsvdatei'>";
if($MultiType==0 ||$MultiType==1){echo "<option value='beelogger.csv'"; if($StandardCSV=="beelogger.csv") echo" selected"; echo">".$KAs[141]."</option>";}
echo"<option value='month.csv'";
if($StandardCSV=="month.csv") echo" selected";
echo">".$KAs[142]."</option>";
echo"<option value='week.csv'";
if($StandardCSV=="week.csv") echo" selected";
echo">".$KAs[143]."</option>";
echo"</select>&nbsp;</td>";

if(($StandardCSV=="month.csv")||($StandardCSV=="week.csv")){
 echo"<td><b>&nbsp;Zoom: <select name='neuzoom'>";
 echo"<option value='0'";
 if($Zoom=="0") echo" selected";
 echo">-</option>";
 echo"<option value='1'";
 if($Zoom=="1") echo" selected";
 echo">1 ".$KAs[281]."</option>";
 echo"<option value='4'";
 if($Zoom=="4") echo" selected";
 echo">4 ".$KAs[282]."</option>";
 echo"<option value='8'";
 if($Zoom=="8") echo" selected";
 echo">8 ".$KAs[282]."</option>";
 echo"<option value='24'";
 if($Zoom=="24") echo" selected";
 echo">24 ".$KAs[282]."</option>";
 echo"<option value='84'";
 if($Zoom=="84") echo" selected";
 echo">1/2 ".$SAs[70]."</option>";
 echo"</select>&nbsp;</td>";
}
else{
 echo"<input type='hidden' name='neuzoom' value='0'>";
}
echo"</table>";

// KoeInformationen
if($MultiType >=0 && $MultiType < 2){
 $KoeArrayIcon=array( // nach Modulo 5 geordnet
 "n_Koenigin blau gezeichnet.png",
 "n_Koenigin weiss gezeichnet.png",
 "n_Koenigin gelb gezeichnet.png",
 "n_Koenigin rot gezeichnet.png",
 "n_Koenigin gruen gezeichnet.png"
 );
 $KoeFarbText=array(222,257,219,213,215,222); //blau=0,5; weiss=1,6; gelb=2,7; rot=3,8; gruen=4,9;
 echo"<table border=5 style='color:white; background-color: black;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/n_Koenigin weiss gezeichnet.png' height=20 width=20 style='margin-bottom:-4px';></td>";
 echo"<td>&nbsp;".$KAs[268]."&nbsp;</td>";

 if($KoeAnzeige !="deaktiviert" AND $KoeAnzeige !="") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo"><b>&nbsp;".$KAs[258].": <select name='neukoeanzeige'>";
 if($KoeAnzeige=="") $KoeAnzeige="deaktiviert"; //INIT

 echo"<option value='deaktiviert'";
 if($KoeAnzeige=="deaktiviert") echo" selected";
 echo">".$KAs[33]."</option>";

 $cur_year=date("Y");
 //$cur_year=2026; // test
 $Startjahr=$cur_year % 5; // Mod 5 -> 2020,2025=0
 for($i=0; $i <=4; $i++){
 $q_col=$Startjahr + $i + 1; if ($q_col>4) $q_col-=5; // im Farb/Icon Array

 echo"<option value='".$KoeArrayIcon[$q_col]."'";
 if($KoeAnzeige==$KoeArrayIcon[$q_col]) echo" selected";

 echo">".$KAs[$KoeFarbText[$q_col]]." (".($cur_year + $i -4).")</option>";
 }

 echo"</select>&nbsp;</td>";

 if($KoeAnzeige !="deaktiviert" AND $KoeAnzeige !="") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo">&nbsp;Gekäfigt:&nbsp;<img src='../beelogger_icons/kaefig.png' height=20 width=20 style='margin-bottom:-4px';> <input type='checkbox' style='display:inline;' name='neukoekaefig' value='aktiviert'";
 if($KoeKaefig=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if($KoeAnzeige !="deaktiviert" AND $KoeAnzeige !="") echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";
 echo">&nbsp;Info:&nbsp;<input type='text' style='display:inline;' name='neukoeinfo' value='".$KoeInfo."' size='50' maxlength='50'>&nbsp;</td>";

 echo"</table>";
}
echo"<br>";

// Konfiguration Kurven Tabelle
$SensorCounter=$AnzahlSensoren;
if($Sensoren[($AnzahlSensoren-1)*5]=="Summe") $SensorCounter=($AnzahlSensoren-2);

echo"<table border=5 style=' width: 90%;'><tr><td>";
echo"<b>".$KAs[153]." ".$KAs[154];
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[22].'.</td></tr>
 </table>
</details></td>';

if($MultiType==-1){
echo"<td>".$KAs[296];
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[40].'.</td></tr>
 </table>
</details></td>';
}

echo"<td> &nbsp;&nbsp;".$SAs[87].":<br>mobileCheck";//MobileCheck Sensor Anzeigen
echo '<details>
  <summary>
    <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
  </summary>
  <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[43].'
</td></tr>
  </table>
</details></td>';
echo"</td>";

echo"<td>".$KAs[155]; // Icon statt Button
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[23].'.</td></tr>
 </table>
</details></td>';


echo"<td>".$KAs[156]; //Iconauswahl
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[24].'.</td></tr>
 </table>
</details></td>';


//echo"<td>".$KAs[157]."</td>"; //Icon

echo" <td>".$KAs[158];
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[25].'.</td></tr>
 </table>
</details>';
echo"</td>";


if($MultiType !=-1){ //Direktanzeige
 echo"<td>".$KAs[159];
 echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[26].'.</td></tr>
 </table>
 </details>';
 echo"</td>";

 echo"<td>".$KAs[160];//Tageswertanzeige
 echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[27].'.</td></tr>
 </table>
 </details>';
 echo"</td>";
}

echo"<td>".$KAs[161];//Achse
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[34].'.</td></tr>
 </table>
</details>';
echo"</td>";

echo"<td>&nbsp;Fill";//Fillgraph
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td> fill line in graph to "0"  </td></tr>
 </table>
</details>';
echo"</td>";

echo"<td>".$KAs[162];//Einheit
echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[28].'.</td></tr>
 </table>
</details>';
echo"</td>";

if(!isset($LetzteZeile)){
 $LetzteZeile=array (""); //dummy
 for($x=0;$x<=$SensorCounter;$x++){ array_push($LetzteZeile,""); }
}

if((($MultiType==-1)&&($SensorCounter >0))||($SensorCounter > $Sensor_max_count)){
 echo"<td>".$KAs[163]; //löschen
 echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[29].'.</td></tr>
 </table>
 </details>';
 echo"</td>";
}

if($MultiType !=-1){
 echo"<td>".$KAs[164]." ".$LetzteZeile[0]."</b>";
 echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[30].'.</td></tr>
 </table>
 </details>';
 echo"</td>";
}
echo"</tr><tr>";

  //Sensor in mobileCheck preset new array
if(!isset($MobileCheckSensor)){
    $MobileCheckSensor=array();
    for($a=0;$a<$SensorCounter;$a++){
      $showSensor="false";
      if(($Icon[$a]!="no.png") and ($UseIcon[$a]!=""))$showSensor="true";
      if(is_numeric($Sensoren[$a*5]))$showSensor="false";
      array_push($MobileCheckSensor,$showSensor);
    }
}

//Sensornamen
for($a=0;$a<$SensorCounter;$a++){
 if($Sensoren[$a*5+1]=="white") echo"<td style='color:black ; background-color: ".$Sensoren[$a*5+1].";''>".$KAs[165]." ".str_pad(($a+1), 2, 0, STR_PAD_LEFT);
 else echo"\n<td style='color:white; background-color:".$Sensoren[$a*5+1].";'>".$KAs[165]." ".str_pad(($a+1), 2, 0, STR_PAD_LEFT);
 echo": <input type='text' name='sensor".($a+1)."' value='".$Sensoren[$a*5]."' size='18' maxlength='30'></td>";

//XanalyseNamen
 if($MultiType==-1){
 echo"<td style='color:black ; background-color:white;'>";
 echo"Sensor ".str_pad(($a+1), 2, 0, STR_PAD_LEFT).": ";
 echo"<select name='xanalystbeelogger".($a+1)."'>";
 echo"<option value=''"; if($XanalyseArray[$a]=="") echo" selected"; echo">".$KAs[296]."</option>";

 for($h=0;$h < $AnzahlBeelogerCount;$h++){
 for($i=0;$i < $AngebotArray[$h][1];$i++){
 if(isset($AngebotArray[$h][2][$i])){
 if($AngebotArray[$h][2][$i] !=""){
 $Helper=$AngebotArray[$h][0].'/'.$AngebotArray[$h][2][$i].'/'.$i;
 echo"<option value='".$Helper."'"; if($XanalyseArray[$a]==$Helper) echo" selected"; echo">";
 if($AngebotArray[$h][3] !="") echo $AngebotArray[$h][0]."(".$AngebotArray[$h][3].")";
 else echo $AngebotArray[$h][0];
 echo '/'.$AngebotArray[$h][2][$i]."</option>";
 }
 }
 }
 }
 echo"</select></td>";
 }

  //Sensor in mobileCheck true oder false
  if($MobileCheckSensor[$a]=="true") echo"<td style='color:#a9dfbf; background-color: #a9dfbf;'>";
  else echo"<td style='color:#e7846f; background-color: #e7846f;'>";
  echo"<input type='checkbox' value='true' name='mobilechecksensor".($a+1)."'";
  if($MobileCheckSensor[$a]=="true") echo" checked";
  echo"></td>";

//Icon statt Sensorname anzeigen?
 if($UseIcon[$a]=="true") echo"<td style='color:#a9dfbf; background-color: #a9dfbf;'>";
 else echo"<td style='color:#e7846f; background-color: #e7846f;'>";
 echo"<input type='checkbox' value='true' name='useicon".($a+1)."'"; if($UseIcon[$a]=="true") echo" checked"; echo">";
 if(file_exists('../beelogger_icons/'.$Icon[$a])) echo"<img src='../beelogger_icons/off_".$Icon[$a]."' width='30' height='30' style='margin-bottom:-4px';";
 else echo"<img src='../beelogger_icons/no.png' width='30' height='30' style='margin-bottom:-4px';";
 echo"</td>";

 array_push($Icon,"no.png");

//Iconauswahl
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''>";
 echo"<select name='icon".($a+1)."'>";
 echo"<option value='temp_w.png'"; if($Icon[$a]=="temp_w.png") echo" selected"; echo">".$KAs[166]."</option>";
 echo"<option value='temp_i.png'"; if($Icon[$a]=="temp_i.png") echo" selected"; echo">".$KAs[167]."</option>";
 echo"<option value='temp_o.png'"; if($Icon[$a]=="temp_o.png") echo" selected"; echo">".$KAs[168]."</option>";
 echo"<option value='hum_i.png'"; if($Icon[$a]=="hum_i.png") echo" selected"; echo">".$KAs[169]."</option>";
 echo"<option value='hum_o.png'"; if($Icon[$a]=="hum_o.png") echo" selected"; echo">".$KAs[170]."</option>";
 echo"<option value='sun.png'"; if($Icon[$a]=="sun.png") echo" selected"; echo">".$KAs[171]."</option>";
 echo"<option value='weight.png'"; if($Icon[$a]=="weight.png") echo" selected"; echo">".$KAs[172]."</option>";
 echo"<option value='bat.png'"; if($Icon[$a]=="bat.png") echo" selected"; echo">".$KAs[173]."</option>";
 echo"<option value='solar.png'"; if($Icon[$a]=="solar.png") echo" selected"; echo">".$KAs[174]."</option>";
 echo"<option value='wifi.png'"; if($Icon[$a]=="wifi.png") echo" selected"; echo">".$KAs[175]."</option>";
 echo"<option value='press.png'"; if($Icon[$a]=="press.png") echo" selected"; echo">".$KAs[176]."</option>";
 echo"<option value='rain.png'"; if($Icon[$a]=="rain.png") echo" selected"; echo">".$KAs[177]."</option>";
 echo"<option value='info.png'"; if($Icon[$a]=="info.png") echo" selected"; echo">".$KAs[178]."</option>";
 echo"<option value='service.png'"; if($Icon[$a]=="service.png") echo" selected"; echo">".$KAs[179]."</option>";
 echo"<option value='bienen_in.png'"; if($Icon[$a]=="bienen_in.png") echo" selected"; echo">".$KAs[180]."</option>";
 echo"<option value='bienen_out.png'"; if($Icon[$a]=="bienen_out.png") echo" selected"; echo">".$KAs[181]."</option>";
 echo"<option value='honey.png'"; if($Icon[$a]=="honey.png") echo" selected"; echo">".$KAs[182]."</option>";
 echo"<option value='frequenz.png'"; if($Icon[$a]=="frequenz.png") echo" selected"; echo">".$KAs[297]."</option>";
 echo"<option value='volume.png'"; if($Icon[$a]=="volume.png") echo" selected"; echo">".$KAs[298]."</option>";
 echo"<option value='no.png'"; if($Icon[$a]=="no.png" OR $Icon[$a]=="") echo" selected"; echo">".$KAs[33]."</option>";
 echo"</select></td>";

// Farbe des Sensors
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";'>";
 echo"<select name='farbe".($a+1)."'>";
 for($f=0;$f < sizeof($Farben);$f++){
 echo"<option style='color:".$RealFarben[$f]."'; value='".$RealFarben[$f]."'";
 if($Sensoren[$a*5+1]==$RealFarben[$f]) echo" selected";
 echo">".$Farben[$f]."</option>";
 }
 echo"</select></td>";

 if($MultiType !=-1){
 //Sensoranzeige direkt? true oder false
 if($Sensoren[$a*5+2]=="true") echo"<td style='color:#a9dfbf; background-color: #a9dfbf;'>";
 else echo"\n<td style='color:#e7846f; background-color: #e7846f;'>";
 echo"<input type='checkbox' value='true' name='anzeige".($a+1)."'";
 if($Sensoren[$a*5+2]=="true") echo" checked";
 echo"></td>";

 //Tageswertoption aktivieren true oder false
 $txt="";
 if($MultiType>1){
 $mt_tw_opt=1;
 if ($MultiType==2) if ($a==5 OR $a==6 OR ($a>12 AND $a<15))$mt_tw_opt=0;
 if ($MultiType==3) if (($a>4 AND $a<8) OR ($a>15 AND $a<19))$mt_tw_opt=0;
 if ($MultiType==4) if (($a>4 AND $a<9) OR ($a>18 AND $a <23))$mt_tw_opt=0;
 if ($MultiType==5) if (($a>4 AND $a<10) OR $a>19 )$mt_tw_opt=0;
 if ($MultiType==6) if (($a>4 AND $a<11) OR $a>22 )$mt_tw_opt=0;
 if($mt_tw_opt==1){
 if(($a==0)||($a==1)) $txt="<label for='tageswertoption".($a+1)."'> GTS</label>";
 echo"<td style='color:#e7846f; background-color: #a9dfbf;'><input type='checkbox' value='true' id='tageswertoption".($a+1)."' name='tageswertoption".($a+1)."'";
 if($TageswertOptionArray[$a]=="true") echo" checked";
 } else{
 echo"\n<td style='color:#c0c0c0; background-color: #c0c0c0;'><input type='checkbox' value='false' style='display:none' name='tageswertoption".($a+1)."' ";
 }
 }
 else {
	if($TageswertOptionArray[$a]=="true" OR ($a==5)) echo"<td style='color:#e7846f; background-color: #a9dfbf;'>";
	else echo"<td style='color:#a9dfbf; background-color: #e7846f;'>";
 if(($a==1)||($a==12)) $txt="<label for='tageswertoption".($a+1)."'> GTS</label>";
 if($a==11) $txt="<label for='tageswertoption".($a+1)."'>".$KAs[177]."</label>";
	echo"<input type='checkbox' value='true' id='tageswertoption".($a+1)."' name='tageswertoption".($a+1)."'";
 if($TageswertOptionArray[$a]=="true") echo" checked";
 }
 if($a==5){
 $tw_g=0;
 for($ax=0;$ax<$SensorCounter;$ax++){
 if($TageswertOptionArray[$ax]=="true") $tw_g=1;
 }
 if($tw_g==0){echo" checked";} // Tageswert Gewicht, wenn keiner aktiv
 }
 echo">".$txt."</td>";
 }

//Achse
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";'>";
 echo"<select name='achse".($a+1)."'>";
 echo"<option value='y'"; if(strpos($Sensoren[$a*5+3],"'y'") !==false) echo" selected"; echo">y</option>";
 echo"<option value='y2'"; if(strpos($Sensoren[$a*5+3],"'y2'") !==false) echo" selected"; echo">y2</option>";
 echo'</select></td>';

//FillGraph
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''>";
 echo"<select name='fillgraph".($a+1)."'>";
 echo"<option value='-'"; if($FillGraph[$a]=="") echo" selected"; echo">-</option>";
 echo"<option value='0.1'"; if($FillGraph[$a]=="0.1") echo" selected"; echo">0.1</option>";
 echo"<option value='0.2'"; if($FillGraph[$a]=="0.2") echo" selected"; echo">0.2</option>";
 echo"<option value='0.3'"; if($FillGraph[$a]=="0.3") echo" selected"; echo">0.3</option>";
 echo"<option value='0.4'"; if($FillGraph[$a]=="0.4") echo" selected"; echo">0.4</option>";
 echo"<option value='0.5'"; if($FillGraph[$a]=="0.5") echo" selected"; echo">0.5</option>";
 echo"<option value='0.6'"; if($FillGraph[$a]=="0.6") echo" selected"; echo">0.6</option>";
 echo"<option value='0.8'"; if($FillGraph[$a]=="0.8") echo" selected"; echo">0.8</option>";
 echo"<option value='1.0'"; if($FillGraph[$a]=="1.0") echo" selected"; echo">1.0</option>";
 echo"</td>";

//Einheit des Sensors
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''><input type='text' name='einheit".($a+1)."' value='".$Sensoren[$a*5+4]."' size='5' maxlength='8'></td>";


 if($MultiType !=-1){
 //Sensorlöschen
 if($SensorCounter > $Sensor_max_count){
 if($a >=$Sensor_max_count) echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''><input type='checkbox' name='loeschen".($a+1)."' value='1'> </td>";
 else echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";'></td>";
 }

 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''><input type='text' name='' value='".$LetzteZeile[$a+1]."' size='4'>";
 if($MultiType==2) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[167]."1",$KAs[167]."2",$KAs[169]."1",$KAs[169]."2",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==3) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[184]."3",$KAs[167]."1",$KAs[167]."2",$KAs[167]."3",$KAs[169]."1",$KAs[169]."2",$KAs[169]."3",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[185]." HX711(2)".$KAs[208]."-A",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==4) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[184]."3",$KAs[184]."4",$KAs[167]."1",$KAs[167]."2",$KAs[167]."3",$KAs[167]."4",$KAs[169]."1",$KAs[169]."2",$KAs[169]."3",$KAs[169]."4",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[185]." HX711(2)".$KAs[208]."-A",$KAs[185]." HX711(2)".$KAs[208]."-B",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==5) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[184]."3",$KAs[184]."4",$KAs[184]."5",$KAs[167]."1",$KAs[167]."2",$KAs[167]."3",$KAs[167]."4",$KAs[167]."5",$KAs[169]."1",$KAs[169]."2",$KAs[169]."3",$KAs[169]."4",$KAs[169]."5",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[185]." HX711(2)".$KAs[208]."-A",$KAs[185]." HX711(2)".$KAs[208]."-B",$KAs[185]." HX711(3)".$KAs[208]."-A",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==6) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[184]."3",$KAs[184]."4",$KAs[184]."5",$KAs[184]."6",$KAs[167]."1",$KAs[167]."2",$KAs[167]."3",$KAs[167]."4",$KAs[167]."5",$KAs[167]."6",$KAs[169]."1",$KAs[169]."2",$KAs[169]."3",$KAs[169]."4",$KAs[169]."5",$KAs[169]."6",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[185]." HX711(2)".$KAs[208]."-A",$KAs[185]." HX711(2)".$KAs[208]."-B",$KAs[185]." HX711(3)".$KAs[208]."-A",$KAs[185]." HX711(3)".$KAs[208]."-B",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==7) $Werte=array($KAs[166],$KAs[168],$KAs[170],$KAs[179],$KAs[171],$KAs[184]."1",$KAs[184]."2",$KAs[184]."3",$KAs[184]."4",$KAs[184]."5",$KAs[184]."6",$KAs[184]."7",$KAs[167]."1",$KAs[167]."2",$KAs[167]."3",$KAs[167]."4",$KAs[167]."5",$KAs[167]."6",$KAs[167]."7",$KAs[169]."1",$KAs[169]."2",$KAs[169]."3",$KAs[169]."4",$KAs[169]."5",$KAs[169]."6",$KAs[169]."7",$KAs[173],$KAs[174],$KAs[185]." HX711(1)".$KAs[208]."-A",$KAs[185]." HX711(1)".$KAs[208]."-B",$KAs[185]." HX711(2)".$KAs[208]."-A",$KAs[185]." HX711(2)".$KAs[208]."-B",$KAs[185]." HX711(3)".$KAs[208]."-A",$KAs[185]." HX711(3)".$KAs[208]."-B",$KAs[185]." HX711(4)".$KAs[208]."-A",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==1) $Werte=array($KAs[167],$KAs[168],$KAs[169],$KAs[170],$KAs[171],$KAs[184],$KAs[173]."/".$KAs[180],$KAs[174]."/".$KAs[181],$KAs[179]."/".$KAs[183],$KAs[185]." HX711-".$KAs[208]."A",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 elseif($MultiType==-1) $Werte=array();

 else $Werte=array($KAs[167],$KAs[168],$KAs[169],$KAs[170],$KAs[171],$KAs[184],$KAs[173],$KAs[174],$KAs[179],$KAs[185]." HX711",$KAs[186]." Aux1",$KAs[186]." Aux2",$KAs[186]." Aux3");
 echo $Werte[$a];
 echo"</td>";
 }
 if($MultiType==-1){
 //Sensorlöschen
 if(($a>0)){
 echo"<td style='color:white ; background-color: ".$Sensoren[$a*5+1].";''><input type='checkbox' name='loeschen".($a+1)."' value='1'> </td>";
 }
 }
 echo"</tr>";
} // for($a=0;$a<$SensorCounter;$a++)


echo"\n<tr></tr>";

//eventueller neuer Sensor
if($SensorCounter < $Sensor_max_count){
echo"<tr><td colspan=3><b>".$KAs[187].":</td></tr><tr>";

echo"<td>Sensor ".str_pad(($a+1), 2, 0, STR_PAD_LEFT).": ";
echo"<input type='text' name='sensorneu' value='' size='15' maxlength='30'></td>";


if($MultiType==-1){
 echo"<td><select name='xanalystbeeloggerneu".($a+1)."'>";
 echo"<option value=''";
 echo">Bisher keine Auswahl</option>";
 for($h=0;$h < $AnzahlBeelogerCount;$h++){
 for($i=0;$i < $AngebotArray[$h][1];$i++){
 if(isset($AngebotArray[$h][2][$i])){
 if($AngebotArray[$h][2][$i] !=""){
 $Helper=$AngebotArray[$h][0].'/'.$AngebotArray[$h][2][$i].'/'.$i;
 echo"<option value='".$Helper."'>";
 if($AngebotArray[$h][3] !="") echo $AngebotArray[$h][0]."(".$AngebotArray[$h][3].")";
 else echo $AngebotArray[$h][0];
 echo '/'.$AngebotArray[$h][2][$i]."</option>";
 }
 }
 }
 }
 echo"</td>";
}

echo"<td style='color:#e7846f; background-color: #e7846f;'><input type='checkbox' value='true' name='mobilechecksensorneu' ></td>";
echo"<td style='color:#e7846f; background-color: #e7846f;'><input type='checkbox' value='true' name='useiconneu' ></td>";


echo"<td>";
echo"<select name='iconneu'>";

echo"<option value='temp_w.png'";
if($Icon[$a]=="temp_w.png") echo" selected";
echo">".$KAs[166]."</option>";

echo"<option value='temp_i.png'";
if($Icon[$a]=="temp_i.png") echo" selected";
echo">".$KAs[167]."</option>";

echo"<option value='temp_o.png'";
if($Icon[$a]=="temp_o.png") echo" selected";
echo">".$KAs[168]."</option>";

echo"<option value='hum_i.png'";
if($Icon[$a]=="hum_i.png") echo" selected";
echo">".$KAs[169]."</option>";

echo"<option value='hum_o.png'";
if($Icon[$a]=="hum_o.png") echo" selected";
echo">".$KAs[170]."</option>";

echo"<option value='sun.png'";
if($Icon[$a]=="sun.png") echo" selected";
echo">".$KAs[171]."</option>";

echo"<option value='weight.png'";
if($Icon[$a]=="weight.png") echo" selected";
echo">".$KAs[172]."</option>";

echo"<option value='bat.png'";
if($Icon[$a]=="bat.png") echo" selected";
echo">".$KAs[173]."</option>";

echo"<option value='solar.png'";
if($Icon[$a]=="solar.png") echo" selected";
echo">".$KAs[174]."</option>";

echo"<option value='wifi.png'";
if($Icon[$a]=="wifi.png") echo" selected";
echo">".$KAs[175]."</option>";

echo"<option value='press.png'";
if($Icon[$a]=="press.png") echo" selected";
echo">".$KAs[176]."</option>";

echo"<option value='rain.png'";
if($Icon[$a]=="rain.png") echo" selected";
echo">".$KAs[177]."</option>";

 echo"<option value='info.png'";
 if($Icon[$a]=="info.png") echo" selected";
 echo">".$KAs[178]."</option>";
 echo"<option value='service.png'";
 if($Icon[$a]=="service.png") echo" selected";
 echo">".$KAs[179]."</option>";

echo"<option value='bienen_in.png'";
if($Icon[$a]=="bienen_in.png") echo" selected";
echo">".$KAs[180]."</option>";

echo"<option value='bienen_out.png'";
if($Icon[$a]=="bienen_out.png") echo" selected";
echo">".$KAs[181]."</option>";

echo"<option value='honey.png'";
if($Icon[$a]=="honey.png") echo" selected";
echo">".$KAs[182]."</option>";

echo"<option value='no.png'";
if($Icon[$a]=="no.png" OR $Icon[$a]=="") echo" selected";
echo">".$KAs[33]."</option>";

echo"</select></td>";

echo"<td style='color:white ; background-color: black;'><select name='farbeneu'>";
for($f=0;$f < sizeof($Farben);$f++){
 echo"<option style='color:".$RealFarben[$f]."'; value='".$RealFarben[$f]."'";
 echo">".$Farben[$f]."</option>";
}
echo"</select></td>";

if($MultiType !=-1) echo"<td style='background-color: #e7846f;'><input type='checkbox' name='anzeigeneu' value='true'></td>";
else echo"<input type='hidden' name='anzeigeneu' value='true'>";
if($MultiType !=-1) echo"<td style='background-color: #e7846f;'><input type='checkbox' name='tageswertoptionneu' value='true'></td>";

echo"<td>";
echo"<select name='achseneu'>";
echo"<option value=";
echo"'y'";
echo">y</option>";
echo"<option value=";
echo"'y2'";
echo">y2</option>";
echo'</select></td>';

echo"<td><input type='text' name='fillgraphneu' value='-' size='3' maxlength='3'></td>";
echo"<td><input type='text' name='einheitneu' value='[]' size='5' maxlength='10'></td>";

if($MultiType !=-1){
 if($SensorCounter > $Sensor_max_count) echo"<td></td>"; //löschen
 array_push($Werte,"neu");
 echo"<td ><input type='text' name='' value='".$LetzteZeile[$a+1]."' size='4'>".$Werte[$a]."</td>";
}

echo"</tr><br><br>";
} // eventuell neuer Sensor
else echo"<tr><td colspan=11><b>"." "."</td></tr><tr>";

// direkte Tageswertanzeige an oder ausstellen
if($MultiType >=0 && $MultiType < 2){
 echo"<tr style='color:black ; background-color: #A4A4A4;'><td colspan='5';>".$KAs[188].":</td>";

 if($TageswertAnzeige=="deaktiviert") echo"<td colspan='2'; style='color:black; background-color:#a9dfbf;'>";
 else echo"<td colspan='2'; style='color:black ; background-color:#e7846f;'>";

 echo"<select name='tageswertanzeige'>";
 echo"<option value='false' selected>".$KAs[33]."</option>";//deakt
 $AnzahlOptionen=0;
 for($s=0;$s < $AnzahlSensoren;$s ++){
 if($TageswertOptionArray[$s]=="true"){
 $AnzahlOptionen ++;
 if($s==5){
 if($GewichtsEinheit=="g"){
 for($t=0;$t < 7;$t++){
 switch ($t){
 case 0:echo'<option value="200.0"';if($TageswertAnzeige=="200.0")echo" selected";echo'>200g</option>';break;
 case 1:echo'<option value="400.0"';if($TageswertAnzeige=="400.0")echo" selected";echo'>400g</option>';break;
 case 2:echo'<option value="600.0"';if($TageswertAnzeige=="600.0")echo" selected";echo'>600g</option>';break;
 case 3:echo'<option value="1000.0"';if($TageswertAnzeige=="1000.0")echo" selected";echo'>1000g</option>';break;
 case 4:echo'<option value="2000.0"';if($TageswertAnzeige=="2000.0")echo" selected";echo'>2000g</option>';break;
 case 5:echo'<option value="5000.0"';if($TageswertAnzeige=="5000.0")echo" selected";echo'>5000g</option>';break;
 case 6:echo'<option value="10000.0"';if($TageswertAnzeige=="10000.0")echo" selected";echo'>10000g</option>';break;
 }
 }
 }else{
 if($TageswertAnzeige=="true") $TageswertAnzeige="1.0"; //JJ-Abwärtskomp
 for($t=0;$t < 7;$t++){
 switch ($t){
 case 0:echo'<option value="0.2"';if(!strcmp($TageswertAnzeige,"0.2"))echo" selected";echo'>0.2 kg</option>';break;
 case 1:echo'<option value="0.4"';if(!strcmp($TageswertAnzeige,"0.4"))echo" selected";echo'>0.4 kg</option>';break;
 case 2:echo'<option value="0.6"';if(!strcmp($TageswertAnzeige,"0.6"))echo" selected";echo'>0.6 kg</option>';break;
 case 3:echo'<option value="1.0"';if(!strcmp($TageswertAnzeige,"1.0"))echo" selected";echo'>1 kg</option>';break;
 case 4:echo'<option value="2.0"';if(!strcmp($TageswertAnzeige,"2.0"))echo" selected";echo'>2 kg</option>';break;
 case 5:echo'<option value="5.0"';if(!strcmp($TageswertAnzeige,"5.0"))echo" selected";echo'>5 kg</option>';break;
 case 6:echo'<option value="10.0"';if(!strcmp($TageswertAnzeige,"10.0"))echo" selected";echo'>10 kg</option>';break;
 }
 }
 }
 }else {
 echo"<option value='".$s."'"; if($TageswertAnzeige==$s) echo" selected"; echo">".$Sensoren[$s*5]."</option>\n";
 }
 }
 }
 if($AnzahlOptionen==0) echo"<option value='1.0'> 1 kg</option>\n";//default

 echo"</select>";
 //echo"<td></td>";
 echo"<td>";
 echo"<select name='tageswertachse'>";
 echo"<option value='y'"; if($TageswertAchse=="y") echo" selected"; echo">y</option>";
 echo"<option value='y2'"; if($TageswertAchse=="y2") echo" selected"; echo">y2</option>";
 echo"</select></td>";
 echo"<td colspan='3'></td>";
 echo"</tr>";
}

if($MultiType > 1){
 echo"<tr style='color:black ; background-color: #A4A4A4;'><td colspan=5>".$KAs[188].":</td>";

 if($TageswertAnzeige=="deaktiviert") echo"<td style='color:black; background-color: #e7846f;'>";
 else echo"<td style='color:black ; background-color: #a9dfbf;'>";

 echo"<select name='tageswertanzeige'>";
 echo"<option value='false' selected>".$KAs[33]."</option>";//deakt
 $AnzahlOptionen=0;
 for($s=0;$s < $AnzahlSensoren;$s ++){
 if($TageswertOptionArray[$s]=="true"){
 $AnzahlOptionen ++;
 echo"<option value='".$s."'"; if($TageswertAnzeige==$s) echo" selected"; echo">".$Sensoren[$s*5]."</option>\n";
 }
 }

 echo"</select>";
 //echo"<td></td>";
 echo"<td>";
 echo"<select name='tageswertachse'>";
 echo"<option value="; echo"'y'"; if($TageswertAchse=="y") echo" selected"; echo">y</option>";
 echo"<option value="; echo"'y2'"; if($TageswertAchse=="y2") echo" selected"; echo">y2</option>";
 echo"</select></td>";
 echo"<td colspan='3'></td>";
 echo"</tr>";
}

 // Summe
echo"<tr></tr><tr style='color:black ; background-color: #A4A4A4;'><td colspan='5'>".$KAs[310].":";
echo"</td>";

if($Summe=="true") echo"<td colspan='2'; style='color:black ; background-color: #a9dfbf;'>";
else echo"<td colspan='2'; style='color:black ; background-color: #e7846f;'>";
echo"<input type='checkbox' value='true' name='neusumme'";if($Summe=="true") echo" checked";echo"></td>";

// bhome Tageswert/Summe buttons
echo"<td colspan='2';>".$SAs[64]."/".$KAs[310]."<br>&nbsp; - ".$KAs[313]."</td>";
echo"<td style='color:black ; background-color:#A4A4A4;'><input type='checkbox' value='false' name='neubhome'";
if($bhome>0) echo" checked";echo">";
echo"<td style='color:black ; background-color:#A4A4A4;'>";
if($bhome>0) echo $KAs[29]; else echo $KAs[89];

 // GTS color
echo"<tr style='color:black ; background-color: #A4A4A4;'><td colspan='5';5>".$SAs[149]."&nbsp;...&nbsp;".$KAs[158]." ?";
echo"</td>";
if (!isset($GTS_color))$GTS_color="true";

if($GTS_color=="true") echo"<td style='color:black ; background-color: #a9dfbf;'>";
else echo"<td style='color:black ; background-color: #e7846f;'>";
echo"<input type='checkbox' value='true' name='neugts_color'";if($GTS_color=="true") echo" checked";echo"></td>";
echo"<td colspan='5'></td>";
echo"</tr>";


 // zwei Linien
echo"<tr></tr><tr style='color:black ; background-color: #A4A4A4;'><td colspan=5>".$KAs[308].":";
echo"</td>";

if($TwoGrid=="true") echo"<td style='color:black ; background-color: #a9dfbf;'>";
else echo"<td style='color:black ; background-color: #e7846f;'>";
echo"<input type='checkbox' value='true' name='neutwogrid'";if($TwoGrid=="true") echo" checked";echo"></td>";
echo"<td colspan='5'></td></tr>";

 //Datenpunkte anzeigen
echo"<tr></tr><tr style='color:black ; background-color: #A4A4A4;'><td colspan=5>".$KAs[189].":";
echo '<details>
<summary>
<img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
</summary>
<table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[31].'?.
</td></tr>
</table>
</details>';
 echo"</td>";

if($PunktAnzeige=="true") echo"<td style='color:black ; background-color: #a9dfbf;'>";
else echo"<td style='color:black ; background-color: #e7846f;'>";

echo"<input type='checkbox' value='true' name='neupunktanzeige'";
if($PunktAnzeige=="true") echo" checked";
echo"></td>";
 echo"<td colspan='5'></td></tr>\n";

//Underlay
echo"<tr></tr><tr style='color:black ; background-color: #A4A4A4;'>";
echo"<td colspan=3>".$KAs[201]." - ".$KAs[72].":</td>";
echo"\n<td style='color:black ; background-color: #".$Underlay_Col.";'>".$KAs[158].": <input type='text' style='display:inline;' name='neuunderlaycol' value='".html_entity_decode($Underlay_Col)."' size='6' maxlength='6'><br>&nbsp; (hex)</td>";

echo"<td style='color:black ; background-color: #".$Underlay_Col.";'>&nbsp;";
echo"<select name='neuunderlay'>";
echo"<option value='0'";
if($Underlay=="0") echo" selected";
echo">-</option>";
echo"<option value='7'";
if($Underlay=="7") echo" selected";
echo">7</option>";
echo"<option value='14'";
if($Underlay=="14") echo" selected";
echo">14</option>";
echo"</select>&nbsp;d\n";
echo"</td>";

echo"<td style='color:black ; background-color: #".$Underlay_Col.";'>&nbsp;";
Echo $KAs[19]."&nbsp;";
echo"<select name='neuunderlaymo'>";
echo"<option value='4'";
if($Underlay_Mo=="4") echo" selected";
echo">4</option>";
echo"<option value='5'";
if($Underlay_Mo=="5") echo" selected";
echo">5</option>";
echo"<option value='6'";
if($Underlay_Mo=="6") echo" selected";
echo">6</option>";
echo"</select>&nbsp;h&nbsp;<br>&nbsp;&nbsp;";

Echo $KAs[20]."&nbsp;";
echo"<select name='neuunderlayab'>";
echo"<option value='18'";
if($Underlay_Ab=="18") echo" selected";
echo">18</option>";
echo"<option value='19'";
if($Underlay_Ab=="19") echo" selected";
echo">19</option>";
echo"<option value='20'";
if($Underlay_Ab=="20") echo" selected";
echo">20</option>";
echo"</select>&nbsp;h&nbsp;&nbsp;";


echo"<td colspan=1; style='color:black ; background-color: #A4A4A4;'>".$KAs[158].":</td>
      <td style='color:black; background-color: #FFFFA4;'> &nbsp;FFFFA4&nbsp;</td>
      <td style='color:black; background-color: #B9F5B4;'>&nbsp;B9F5B4&nbsp;</td>
      <td style='color:black; background-color: #a9dfbf;'>&nbsp;a9dfbf</td>";
echo"<td colspan='1'></td>\n";
echo"</tr>";

// Legende konfigurieren
echo"<tr style='color:black; background-color: silver;'><td colspan=5>".$KAs[190].":";
echo '<details>
<summary>
<img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
</summary>
<table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[32].'?
</td></tr>
</table>
</details>';
 echo"</td>";

if($Legende=="folgend") echo"<td style='background-color: grey;'>";
elseif($Legende=="keine") echo"<td style='background-color: lightgrey;''>";
else echo"<td style='color:black; background-color: #a9dfbf;'>";

echo"<select name='neulegende'>";
echo"<option value='immer'";
if($Legende=="immer") echo" selected";
echo">".$KAs[191]."</option>";
echo"<option value='folgend'";
if($Legende=="folgend") echo" selected";
echo">".$KAs[192]."</option>";
echo"<option value='keine'";
if($Legende=="keine") echo" selected";
echo">".$KAs[193]."</option>";
echo"</select>&nbsp;</td>";
 echo"<td colspan=6></td>";
 echo"</tr>";


// RollPeriod konfigurieren
 echo"<tr style='color:black ; background-color: lightgray;'><td colspan=5>".$KAs[195]." :";
 echo '<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[33].'.
</td></tr>
 </table>
</details>';
 echo"</td>";
 if(!isset ($RollPeriod)) $RollPeriod=1;
 echo "<td><input type='number' style='width:40px;' style='display:inline;' name='rollperiod' value='".$RollPeriod."' min='1' max='50' step='1'></b></td>";
 echo"<td colspan='6'></td>";
 echo"</tr>";


// Chart Typ für mobile devices

    echo"<tr style='color:black ; background-color: lightgray;'><td colspan=5> touch/mobile devices dygraph chart variant:";
/*    echo '<details>
  <summary>
    <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
  </summary>
  <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[33].'.
</td></tr>
  </table>
</details>';
*/
    echo"</td>";
  if(!isset ($chartMobile)) $chartMobile=1;

  echo"<td colspan='2'; style='background-color: lightgrey;''>";
  echo"<select name='neuchartmobile'>";
    echo"<option value=1";    if($chartMobile=="1") echo" selected";    echo">Dygraph Default</option>";
    echo"<option value=2";    if($chartMobile=="2") echo" selected";    echo">Dygraph nonInteractive</option>";
    echo"<option value=3";    if($chartMobile=="3") echo" selected";    echo"> Touch </option>";
    echo"<option value=4";    if($chartMobile=="4") echo" selected";    echo"> Touch Zoom/Pan only</option>";
    echo"<option value=5";    if($chartMobile=="5") echo" selected";    echo"> ".$KAs[33]." </option>";
  echo"</select>&nbsp;</td>";

   echo"<td colspan='5'></td>";
  echo"</tr>";
echo"</table>";


// Akkuspannung
if($MultiType==0){ //sub
 include($WechselIniName);
 echo'<br><span style="font-size:18px; color:black;">'.$KAs[148].'</span>';

 echo"<table border='5'  style='width: 90%; color:black; background-color:#e5e7e9;'>";
 echo"<td><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';</td><td colspan=4>&nbsp;".$KAs[241]."&nbsp;</td>";
 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";

 echo"<tr style='color:black; background-color: lightgrey;'><td style='background-color: white;'>";
 echo'<img src="../beelogger_icons/Akku25.png" height=18 width=34></td>';
 echo"\n<td align=right><b>&nbsp;".$KAs[149]."&nbsp;";
 echo $AkkuLeerSchwelle;
 echo"&nbsp;[V]&nbsp;&nbsp; </b></td>";
 echo"<td style='background-color: white;'>";
 echo'<img src="../beelogger_icons/Akku50.png" height=18 width=34></td>';
 echo"\n<td><b>&nbsp;".$KAs[150]."&nbsp;";
 echo $AkkuVollSchwelle."&nbsp;[V]&nbsp;&nbsp; </b></td><td style='background-color: white;'><img src='../beelogger_icons/Akku100.png' height=18 width=34></td>";
 if($AkkuLeerSchwelle !=0 && $AkkuVollSchwelle !=0 && $LetztesVBatt !=""){
 echo"<td style='background-color: white; color: black'>&nbsp;".$KAs[272]." :".$LetztesVBatt."V&nbsp;</td>";
 }
 else echo"<td></td>";
 echo"</tr></table>";
 include("beelogger_ini.php"); //ReInit
 }
if($MultiType > 0){ //single/Multi
 echo"<table border='4' style='width: 90%; color:black; background-color: silver;'>";
 echo'<tr><td colspan=6; style="font-size:18px;">'.$KAs[148];
 echo'<details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[20].'.</td></tr>
 </table>
 </details></td><td></td></tr>';

 echo"<td></td><td style='background-color: white;'>";
 echo'<img src="../beelogger_icons/Akku25.png" height=18 width=34></td>';

 echo"\n<td align=right><b>&nbsp;".$KAs[149].":&nbsp;".$AkkuLeerSchwelle."V&nbsp;&nbsp;</b></td>";

 echo"<td style='background-color: white;'>";
 echo'<img src="../beelogger_icons/Akku50.png" height=18 width=34></td>';

 echo"\n<td><b>&nbsp;".$KAs[150].": &nbsp;".$AkkuVollSchwelle."V&nbsp;&nbsp; </b></td>";
 echo"<td style='background-color: white;'>";
 echo'<img src="../beelogger_icons/Akku100.png" height=18 width=34></td>';

 if($AkkuLeerSchwelle !=0 && $AkkuVollSchwelle !=0 && $LetztesVBatt !=""){
 echo"<td style='background-color: white; color: black'>&nbsp;".$KAs[272].": ".$LetztesVBatt."V&nbsp;</td>";
 }

 echo '</tr>';
 echo"<tr><td>&nbsp;".$KAs[151].": &nbsp;</td>";
 echo"<td colspan=5><b>&nbsp;&nbsp;&nbsp;<select name='neuakkuschwellen'>";

 echo"<option value='0/0'";
 if($AkkuLeerSchwelle=="0" OR $AkkuVollSchwelle=="0") echo" selected";
 echo">nicht aktiv / not active / inactif</option>";

 echo"<option value='3.8/4.0'";
 if($AkkuLeerSchwelle=="3.8" OR $AkkuVollSchwelle=="4.0") echo" selected";
 echo">beeloggerBlue/Black, Universal 2.x, SMD (1 x LiIon-Akku) 3,8V - 4,0V</option>";

 echo"<option value='7.5/7.7'";
 if($AkkuLeerSchwelle=="7.5" OR $AkkuVollSchwelle=="7.7") echo" selected";
 echo">beeloggerUniversal (2 x LiIon-Akkus) 7,5V - 7,7V</option>";

 echo"<option value='5.9/6.1'";
 if($AkkuLeerSchwelle=="5.9" OR $AkkuVollSchwelle=="6.1") echo" selected";
 echo">beeloggerUniversal (1 x 6V-Blei-Akku) 5,9V - 6,1V</option>";

 echo"<option value='11.9/12.1'";
 if($AkkuLeerSchwelle=="11.9" OR $AkkuVollSchwelle=="12.1") echo" selected";
 echo">beeloggerUniversal (1 x 12V-Blei-Akku) 11,9V - 12,1V</option>";

 echo"</select>&nbsp;&nbsp;</td>";

 if($AkkuLeerSchwelle !=0 && $AkkuVollSchwelle !=0 && $LetztesVBatt !=""){
 echo"<td style='background-color: white;'>";
 if($LetztesVBatt >=$AkkuVollSchwelle) echo'&nbsp;<img src="../beelogger_icons/Akku100.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 elseif($LetztesVBatt < $AkkuLeerSchwelle) echo'&nbsp;<img src="../beelogger_icons/Akku25.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 else echo'&nbsp;<img src="../beelogger_icons/Akku50.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 if($AkkuVollSchwelle=="7.7"){
 if($LetztesVBatt > $AkkuVollSchwelle) echo'&nbsp;<img src="../beelogger_icons/Akku100.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 elseif($LetztesVBatt < $AkkuLeerSchwelle) echo'&nbsp;<img src="../beelogger_icons/Akku25.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 else echo'&nbsp;<img src="../beelogger_icons/Akku50.png" width="100" height="50" style="margin-bottom:-2px" title="'.$LetztesVBatt.'V">';
 }
 echo"&nbsp;</td>";
 }
 echo"</table>";
}
echo"<br><br>";

// mobileWatch-Konfiguration
if(file_exists("../general_ini.php")){
 include("../general_ini.php");
 include("beelogger_ini.php"); //REINIT

// vvv alle

 //beelogger1 in Show Liste
 echo'<table><td style="font-size:20px; color:blue;"> '.$SAs[35].'</td>
 <td><details>
 <summary><img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";></summary>
 <table style="font-size:15px; color:blue; background-color: #F2F2F2;"><tr><td>'.$HAs[41].'</td></tr></table>
 </details></td></table>';

 echo"<table border='4' style='color:lightgray; background-color: #0a0a0f;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/watch.png' height=30 width=20><td><b>&nbsp; ".$KAs[198].": &nbsp;</b></td> ";

 if($beelogger1_List=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;beelogger1 ... &nbsp;".$SAs[35]." &nbsp;&nbsp;&nbsp; ? &nbsp;";

 echo"<input type='checkbox' name='neubeelogger1_list' value='aktiviert'";
 if($beelogger1_List=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 echo '</table><br><br><br>';


//mobileWatch_konfig
 echo'<span style="font-size:20px; color:blue;">'.$KAs[196].'</span>';
 echo"<table border='4' style='color:lightgray; background-color: #0a0a0f;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/watch.png' height=30 width=20><td><b>&nbsp;".$KAs[198].": &nbsp;</b></td> ";

 if($mobileWatch_Show=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;".$KAs[197]."?&nbsp;";

 echo"<input type='checkbox' name='neumobilewatch_show' value='aktiviert'";
 if($mobileWatch_Show=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 // Sortierung
 echo"<td>&nbsp;".$KAs[199].":&nbsp;<select name='neumobilewatch_sort'>";
 echo"<option value='Ordner'";
 if($mW_sort=="Ordner") echo" selected";
 echo">".$KAs[200]."</option>";
 echo"<option value='Bienenvolkbezeichnung'";
 if($mW_sort=="Bienenvolkbezeichnung") echo" selected";
 echo">".$KAs[139]."</option>";
 echo"</select>&nbsp;</td>";

 $bl_name=substr(dirname(__FILE__), strrpos(dirname(__FILE__),"/")+1);
 if(strpos($bl_name,'Xanalyse')===0){
 echo'</table>';
 }else{ // XAnalyse in Watch

 echo"<td><b>&nbsp;".$KAs[201]."&nbsp;<input type='number' style='width:45px;' style='display:inline;' name='neumobilewatch_tage' value='".$mW_tage."' min='1' max='30' step='1'></b>&nbsp;</td>";
 echo"<td><b>&nbsp;".$KAs[202]."&nbsp;<input type='number' style='width:35px;' style='display:inline;' name='neumobilewatch_spalten' value='".$mW_spalten."' min='1' max='2' step='1'></b>&nbsp;</td>";

 echo'</table>';
// ^^^ alle

 if($MultiType !=-1){
 echo"<table border='4' style='color:lightgray; background-color: #0a0a0f;'>";
 $g_dir=getcwd();$g_pos=strrpos($g_dir,'/')+1;$g_d=substr($g_dir,$g_pos);
 echo"<td rowspan='2' style='color:#a9dfbf'>&nbsp;".$g_d."&nbsp;</td>";

 echo"<td>&nbsp;".$KAs[165]."1&nbsp;<select style='display:inline;' name='neumobilewatch_sensor1'>";

 for($i=1;$i < $AnzahlSensoren+1;$i++){
 if(!is_numeric($Sensoren[($i-1)*5])){
 echo"<option value='".$i."'";
 if($mW_sensor1==$i) echo" selected";
 echo">".$Sensoren[($i-1)*5]."</option>";
 }
 }
 echo"</select></td>";

 echo"<td><b>&nbsp;".$KAs[165]."2&nbsp;<select style='display:inline;' name='neumobilewatch_sensor2'>";
 for($i=0;$i < ($AnzahlSensoren+1);$i++){
 if($i==0) echo"<option value='0' selected>".$KAs[33]."</option>";
 else{
 if(!is_numeric($Sensoren[($i-1)*5])){
 echo"<option value='".$i."'";
 if($mW_sensor2==$i) echo" selected";
 echo">".$Sensoren[($i-1)*5]."</option>";
 }
 }
 }
 echo"</select></td>";
 // zwei Linien
 if($mW_twogrid=='1') echo"<td style='color:black;background-color: #a9dfbf;'";
 else echo"<td";
 echo">&nbsp;".$KAs[308]."&nbsp;<input type='checkbox' name='neumobilewatch_twogrid' value='1'";
 if($mW_twogrid=='1') echo" checked";
 echo">&nbsp;</td>";

 // zwei Achsen
 if($mW_one_y=='1') echo"<td style='color:black;background-color: #a9dfbf;'";
 else echo"<td";
 echo">&nbsp;".$KAs[193]." y1- ".$KAs[161]."&nbsp;<input type='checkbox' name='neumobilewatch_one_y' value='1'";
 if($mW_one_y=='1') echo" checked";
 echo">&nbsp;</td>";
 echo"<tr><td><b>&nbsp;".$KAs[229]."&nbsp;<input type='number' style='width:45px;' style='display:inline;' name='neumobilewatch_roll' value='".$mW_roll."' min='0' max='50' step='1'></b></td>";

 echo"<td>&nbsp;".$KAs[188]."?&nbsp;";
 echo"<input type='checkbox' name='neumobilewatch_tageswertanzeige' value='aktiviert'";
 if($mW_tageswertanzeige=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

//Legende
 echo"<td>&nbsp;".$KAs[190]."?&nbsp;<select name='neumobilewatch_legende'>";
 echo"<option value='always'";
 if($mW_legende=="always" OR $mW_legende=="") echo" selected";
 echo">".$KAs[191]."</option>";
 echo"<option value='follow'";
 if($mW_legende=="follow") echo" selected";
 echo">".$KAs[192]."</option>";
 echo"<option value='never'";
 if($mW_legende=="never") echo" selected";
 echo">".$KAs[193]."</option>";
 echo '</select>&nbsp;</td>';

 echo"<td>&nbsp;".$KAs[26]."?&nbsp;";
 echo"<input type='checkbox' name='neumobilewatch_notes' value='aktiviert'";
 if($mW_notes=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 echo '</tr></table>';
 }
} // not Xanalyse
echo '<br><br><br>';

if(($MultiType==0)||($MultiType==1)){ //single / sub only

 //mobileCheck_konfig
  echo'<table><td style="font-size:20px; color:blue;"> '.$KAs[312].'</td>
 <td><details>
 <summary><img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";></summary>
 <table style="font-size:15px; color:blue; background-color: #F2F2F2;"><tr><td>'.$HAs[44].'</td></tr></table>
 </details></td></table>';

 echo"<table border='4' style='color:lightgray; background-color: #0a0a0f;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/watch.png' height=30 width=20><td><b>&nbsp;".$KAs[198].": &nbsp;</b></td> ";
 if(isset($mobileCheck_Show)==FALSE)$mobileCheck_Show="aktiviert";

 if($mobileCheck_Show=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;".$KAs[311]."?&nbsp;";

 echo"<input type='checkbox' name='neumobilecheck_show' value='aktiviert'";
 if($mobileCheck_Show=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 echo '</table><br><br><br>';
}
}
else echo '</table><br><br><br>';
//mobileCheck_konfig

echo"<input type='hidden' name='alteanzahlsensoren' value='".$SensorCounter."'>";
echo"<input type='hidden' name='passwort' value='".$Passwort."'>";

if($MultiType>=0){
//Symbole: Daten speichern/verwerfen und zurück zur Show
echo"<table>";
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/arrow.png" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/konfig1.png" height="50" ><img src="../beelogger_icons/konfig.png" value="1" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/cancel.png" height="50" ></td></tr>';

echo"<tr><td><button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='configsichern' value='1'><b>".$KAs[203]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: red;' name='configsichern' value='0'><b>".$KAs[205]."</b></button>&nbsp;&nbsp;</td>";
echo"<input type='hidden' name='cfg_complete' value='1'>";
echo"</table>";

echo"<br><hr><hr><br>";
}

if($MultiType > 0){
//Neue beelogger anlegen
echo'<table><td style="font-size:20px; color:blue;">'.$KAs[8].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[8].'.</td><td>
'.$HAs[9].'.</td></tr>
 </table>
</details></td></table>';

echo"<table border='4' style='color:black; background-color:#e5e7e9 ;'><td rowspan=2>&nbsp;<img src='../beelogger_icons/bw_plus.png' height=35 width=35 style='margin-bottom:-4px';>&nbsp;</td><td>&nbsp;".$KAs[9].":&nbsp;";
echo"<select name='beeloggeranlage'>";
echo"<option value='' selected >".$KAs[10]."</option>";
echo"<option value='N'>beelogger (Single)</option>";
echo"<option value='2'>Duo (2 ".$KAs[11].")</option>";
echo"<option value='3'>Triple (3 ".$KAs[11].")</option>";
echo"<option value='4'>Quad (4 ".$KAs[11].")</option>";
if($_SERVER['SERVER_NAME'] !="community.beelogger.de"){ // user servers only
 echo"<option value='6'>Hexa (6 ".$KAs[11].")</option>"; // this allows creation hexa beeloggers
}
if(!file_exists("../Xanalyse1") OR !file_exists("../Xanalyse2") OR !file_exists("../Xanalyse3")) echo"<option value='X'>Xanalyse</option>";

echo"</select>&nbsp;";
echo"</td>";


//beelogger löschen
echo '<td rowspan=2>&nbsp;<img src="../beelogger_icons/bw_minus.png" height=35 width=35 style="margin-bottom:-4px";>&nbsp;</td><td>&nbsp;'.$KAs[12].':&nbsp;';
echo"<select name='beeloggerloeschen'>";
echo"<option value='' selected >".$KAs[10]."</option>";


$files=scandir("../");
natsort($files);
foreach ($files as $file){
 if( is_dir("../".$file) and $file !="." and $file !=".."){
 if(strlen($file) < 12 AND strpos($file, "beelogger")===0){
 $beeloggerNumber=intval(str_replace("beelogger", "", $file));
 if($beeloggerNumber==1) echo"<option value='B1' style='color:gray';>beelogger1 (".$KAs[13].")</option>";
 elseif(intval(str_replace("beelogger", "", $beelogger))==$beeloggerNumber) echo"<option value='' style='color:gray';>beelogger".$beeloggerNumber." (".$KAs[14].")</option>";
 else echo"<option value='N".$beeloggerNumber."'>beelogger".$beeloggerNumber."</option>";
 }
 if(strpos($file, "Duo")===0 OR strpos($file, "Triple")===0 OR strpos($file, "Quad")===0 OR strpos($file, "Penta")===0 OR strpos($file, "Hexa")===0 OR strpos($file, "Sept")===0){
 $beeloggerTypeNow=preg_replace('![^a-zA-Z]!', '', $file);
 $beeloggerNumberNow=intval(preg_replace('![^0-9]!', '', $file));
 $beeloggerSignNow=substr($beeloggerTypeNow,0,1);

 if($file==$beelogger OR (strpos($beelogger, ("beelogger".$beeloggerSignNow.$beeloggerNumberNow))===0)) echo"<option value='' style='color:gray';>".$file." (".$KAs[14].")</option>";
 else echo"<option value='M".$file."'>".$file." ".$KAs[90]." : beelogger".$beeloggerSignNow.$beeloggerNumberNow."_1 ".$KAs[37]." ";
 $i=2;
 while (file_exists("../beelogger".$beeloggerSignNow.$beeloggerNumberNow."_".$i)){
 $i++;
 }
 echo"beelogger".$beeloggerSignNow.$beeloggerNumberNow."_".($i-1)."</option>";
 }
 if(strpos($file, "Xanalyse")===0){
 if($file==$beelogger) echo"<option value='' style='color:gray';>".$file." (".$KAs[14].")</option>";
 else echo"<option value='".$file."'>".$file."</option>";
 }
 }
}

echo"</select>&nbsp;</td>";
echo"<tr><td>";
echo $KAs[259];
echo"<input type='text' value='".$BeeloggerShowPasswort."' name='beeloggeranlagepasswort'>";
echo"</td></tr>";
echo"</table>";
echo '<br>';

} // anlegen/loeschen


//Datensätze splitten bearbeiten
if($MultiType !=-1){

//Dateibereich Kopfzeile
echo'<table><td style="font-size:20px; color:blue;">'.$KAs[15].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[10].'.
</td></tr></table>
</details></td></table>';

if(file_exists('beelogger.csv')){//array füllen mit aktueller csv-datei
 $input='beelogger.csv';
 $array=file($input);
 if(sizeof($array) >0){ // leere beelogger.csv
 $what=trim($array[0]);
 $x=explode( ",", $what );
 $ErstesDatum=$x[0];
 $i=sizeof($array);

//text Dateibereich
echo"<table border='4' style='color:black; background-color:#d7dbdd ;'><td rowspan=3>&nbsp;".$KAs[16].":&nbsp;</td>";
//text Dateibereich

// Archivname  .csv
 echo"<td rowspan=3>&nbsp;&nbsp;&nbsp;&nbsp;".$KAs[17].":<br><br>&nbsp;";
 echo"<input type='text' style='display:inline;' name='archivdateiname' value='".$KAs[18]."' size='16' maxlength='20'>&nbsp;";
 echo".csv &nbsp;</td>";
// Archivname  .csv

//csv reset
 echo"<td colspan=3> ";
 echo"<br>&nbsp;&nbsp;".$KAs[295]."&nbsp;&nbsp;";
 echo '<select name="csvreset">';
 echo"<option value='0' selected >".$KAs[10]."</option>";
 echo"<option value='1' >".$KAs[24]."</option>";
 echo"</select>&nbsp;<br>&nbsp;</td>";
 echo"";
//csv reset

echo"<tr>";

// splitt von ende ... bis jetzt
echo"<td colspan=2>";
 echo"<br>&nbsp;&nbsp;".$KAs[21]." &nbsp;&nbsp;&nbsp; ".$KAs[19]."&nbsp;";
 echo"<select name='aktuellessplitdatum'>";
 for($d=0;$d < $i;$d=$d + 1+ abs($i/50)){
 $what=trim($array[(int)$d]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; echo $x[$s-1]; echo">".$x[0]."</option>";
 }
 $what=trim($array[$i]); //letzter Wert extra
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value=";  echo $x[$s-1]; echo">".$x[0]."</option>";//letzter Wert
 echo'</select>';
 echo"&nbsp;".$KAs[22]."&nbsp;<br>&nbsp;";
 echo"</td>";
// splitt von ende ... bis jetzt


// Datei abspitten ja/nein
 echo"<td rowspan=2>";
 echo"&nbsp;".$KAs[23]."&nbsp;&nbsp;";
 echo '<select name="splittensichern">';
 echo"<option value='0' selected >".$KAs[10]."</option>";
 echo"<option value='1' >".$KAs[24]."</option>";
 echo"</select>&nbsp;&nbsp;</td>";
// Datei abspitten ja/nein

 echo"<tr>";

// splittdatum von bis
echo"<td colspan=2><br>&nbsp; ".$KAs[19]." &nbsp;";
while ($i--){
 $what=trim($array[$i]);
 $x=explode( ",", $what );
 $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv ermittelt
 if($x[$s-1] !=''){//letzte Spalte=Zeitstempel abzufragen
 $Aktualisierung=$x[$s-1];
 break;
 }
 }
 $ZeitFuerNeueNote=$Aktualisierung; //Hilfe für neue Note bei neuer Zeitintervalleingabe
 $Aktualisierung=date("d.m.Y H:i:s",$Aktualisierung);

 echo"<select name='erstessplitdatum'>";
 for($d=0;$d < $i;$d=$d+1+abs($i/50)){
 $what=trim($array[(int)$d]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; echo $x[$s-1]; echo">".$x[0]."</option>";
 }
 $what=trim($array[$i]); //letzter Wert extra
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; echo $x[$s-1]; echo">".$x[0]."</option>";//letzter Wert
 echo'</select>';
 echo"&nbsp;&nbsp;".$KAs[20]."&nbsp;&nbsp;";
 echo"<select name='letztessplitdatum'>";
 $karray=array_reverse($array);
 for($d=0;$d < $i;$d=$d + 1+ abs($i/50)){
 $what=trim($karray[(int)$d]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; echo $x[$s-1]; echo">".$x[0]."</option>";
 }
 $what=trim($karray[$i]); //letzter Wert extra
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; echo $x[$s-1]; echo">".$x[0]."</option>";//letzter Wert
 echo'</select>';
 echo"&nbsp;&nbsp;<br>&nbsp;</td>";
// splittdatum von bis

 echo"</table>";
 } // leere beelogger.csv

if($MultiType < 2){//Daten für Forschungsprojekt vorbereiten
 $ForscherArray=file('beelogger.csv');
 $ForscherDateiName=basename(__DIR__).".csv";

if(1==0){ // keine Forscherdatei
  $ForscherFirstLine="Datum,";
  for($i=0;$i < ($AnzahlSensoren-1);$i++){
  $ForscherFirstLine .=html_entity_decode($Sensoren[$i*5]).",";
  }
  $ForscherFirstLine .=html_entity_decode($Sensoren[$i*5]).',Timestamp'."\r\n";
  $aktion=fOpen($ForscherDateiName,"w");
  fputs($aktion,$ForscherFirstLine);
  foreach($ForscherArray as $values){ fputs($aktion, $values);}
  fClose($aktion);

  echo"<td>".$KAs[273].": ";
  echo"<a href='".$ForscherDateiName."' target='_blank'>".$ForscherDateiName."</a>&nbsp;&nbsp;</td>";
}
else if (file_exists($ForscherDateiName))unlink ($ForscherDateiName);
}
echo"</table>";

if(file_exists("CSVedit.php")){
echo"<table border='4' style='color:black; background-color:#d7dbdd;'><td><img src='../beelogger_icons/m_back.png' width='30' height='30' style='margin-bottom:-4px';</td><td> &nbsp; &nbsp; </td>" ;
 echo'<td><form action="CSVedit.php" method="post" target="_blank">&nbsp;&nbsp;<input type="Submit" style="color:black; background-color:skyblue;"" value=" aktuelle beelogger.csv editieren " />';
 echo"&nbsp; ".$KAs[19]." ";

if(file_exists('beelogger.csv')){//array füllen mit aktueller csv-datei
 $input='beelogger.csv';
 $array=file($input);
 if(sizeof($array) > 0){
 $what=trim($array[0]);
 $x=explode( ",", $what );
 $ErstesDatum=$x[0];
 $i=sizeof($array);
 while ($i--){
 $what=trim($array[$i]);
 $x=explode( ",", $what );
 $s=sizeof($x); // Anzahl von Spalten pro Zeile im beelogger.csv ermittelt
 if($x[$s-1] !=''){ //letzte Spalte=Zeitstempel abzufragen
 $Aktualisierung=$x[$s-1];
 break;
 }
 }
 $ZeitFuerNeueNote=$Aktualisierung; //Hilfe für neue Note bei neuer Zeitintervalleingabe
 $Aktualisierung=date("d.m.Y H:i:s",$Aktualisierung);
 }
} // if exists

if(sizeof($array) > 0){
 echo"<select name='erstescsvsplitdatum'>";
 $what=trim($array[0]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value=";
 echo $x[$s-1];
 echo" selected>".$x[0]."</option>";


 for($d=1;$d < $i;$d=$d+1+abs($i/50)){
 $what=trim($array[(int)$d]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value=";
 echo $x[$s-1];
 echo">".$x[0]."</option>";
 }
 $what=trim($array[$i]); //letzter Wert extra
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; //letzter Wert
 echo $x[$s-1];
 echo">".$x[0]."</option>";
 echo'</select>';
 echo"&nbsp;".$KAs[20]."&nbsp;";
 echo"<select name='letztescsvsplitdatum'>";
 $karray=array_reverse($array);
 for($d=0;$d < $i;$d=$d + 1+ abs($i/50)){
 $what=trim($karray[(int)$d]);
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value=";
 echo $x[$s-1];
 echo">".$x[0]."</option>";
 }
 $what=trim($karray[$i]); //letzter Wert extra
 $x=explode( ",", $what );
 $s=sizeof($x);
 echo"<option value="; //letzter Wert
 echo $x[$s-1];
 echo">".$x[0]."</option>";
 echo'</select>';

 echo"&nbsp;&nbsp;</td>";
}
 echo"<input type='hidden' name='pw' value='".$BeeloggerShowPasswort."'>";
 echo" </form></table>";
} // csvedit
} // if exists

//download csv-Datei
echo"<table border='4' style='color:black; background-color:#d7dbdd;'>";
echo"<td><img src='../beelogger_icons/download.png' width='30' height='30' style='margin-bottom:-4px';</td><td> &nbsp; &nbsp; </td><td>&nbsp;&nbsp;<button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='csvdownload' value='1'><b>".$KAs[25]."</b></button>&nbsp;&nbsp;&nbsp;</td><td>";
echo"<select name='csvdownloaddatei'>";
echo"<option value='' selected >".$KAs[29]."</option>";
foreach (glob("*.csv") as $filename){echo"<option value='".$filename; echo"'>".$filename."</option>";}
echo"</select>&nbsp;</td>";
echo" </table>";


//loeschen csv-Datei
echo"<table border='4' style='color:black; background-color:#d7dbdd;'>";
echo"<td><img src='../beelogger_icons/loeschen.png' width='30' height='30' style='margin-bottom:-4px';</td><td><b>\n&nbsp;".$KAs[28].": ";
echo"<select name='csvloeschdatei'>";
echo"<option value='' selected >".$KAs[29]."</option>";
foreach (glob("*.csv") as $filename){
 if($filename !="notes.csv" AND $filename !="notes_old.csv" AND $filename !="beelogger.csv" AND $filename !="month.csv" AND $filename !="week.csv"){
 echo"<option value='".$filename; echo"'>".substr($filename,0,-4)."</option>";
 }
}
echo"</select>&nbsp;</td>";
echo" </table>";

echo "<br><br>";

//Symbole: Daten speichern/verwerfen und zurück zur Show
echo"<table>";
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/arrow.png" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/konfig1.png" height="50" ><img src="../beelogger_icons/konfig.png" value="1" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/cancel.png" height="50" ></td></tr>';

echo"<tr><td><button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='configsichern' value='1'><b>".$KAs[203]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: red;' name='configsichern' value='0'><b>".$KAs[205]."</b></button>&nbsp;&nbsp;</td>";
echo"</table>";
echo "<br><hr><hr><br>";
} // Multitype


//Server beelogger Steuerung
if($MultiType > 0){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[30].'</td>';
echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[11];

if($beeloggerSketchID=="" OR strpos($beeloggerSketchID,"GSM") !==FALSE OR strpos($beeloggerSketchID,"LTE") !==FALSE) echo '<br>'.$HAs[12];
else echo '<br>'.$HAs[13];

echo'</td></tr></table>
</details></td></table>';

 // Intervallsteuerung ------------------------
 echo"<table border='4' style='color:black; background-color: #bdc3c7;'><td rowspan=2><img src='../beelogger_icons/time.png' width='30' height='30' style='margin-bottom:-4px';</td><td rowspan=2>".$KAs[31].":&nbsp;</td>";

 echo"<td rowspan=2><b>".$KAs[32]."&nbsp;</b>";
 echo"<select name='neuintervallsendesteuerung'>";
 echo"<option value='deaktiviert'"; if($IntervallSendeSteuerung=="deaktiviert") echo" selected"; echo">".$KAs[33]."</option>";
 echo"<option value='LORA'"; if($IntervallSendeSteuerung=="LORA") echo" selected"; echo">LORA</option>";
 echo"<option value='zeitgesteuert'"; if($IntervallSendeSteuerung=="zeitgesteuert") echo" selected"; echo">".$KAs[34]."</option>";
 echo"<option value='lichtgesteuert'"; if($IntervallSendeSteuerung=="lichtgesteuert") echo" selected"; echo">".$KAs[36]."</option>";
 echo"<option value='solarspannungsgesteuert'"; if($IntervallSendeSteuerung=="solarspannungsgesteuert") echo" selected"; echo">".$KAs[35]."</option>";
 echo"</select></td>";

 $monate=array(1=>$KAs[38],2=>$KAs[39],3=>$KAs[40],4=>$KAs[41],5=>$KAs[42],6=>$KAs[43],7=>$KAs[44],8=>$KAs[45],9=>$KAs[46],10=>$KAs[47],11=>$KAs[48],12=>$KAs[49]);

 echo"<td colspan=2><b>".$KAs[50].":</b>";
 echo"<select name='neusommerbeginn'>";
 for($t=1;$t <=6;$t++ ){
 echo"<option value='".$t."'"; if($SommerBeginn==$t) echo" selected"; echo">1 ".$monate[$t]."</option>";
 $halb=$t+0.5;
 echo"<option value='".$halb."'"; if($SommerBeginn==$halb ) echo" selected"; echo">15 ".$monate[$t]."</option>";
 }
 echo"</select></td>";

 echo"<td><b>".$KAs[51].":\n";
 echo"<select name='neuwinterbeginn'>";
 echo"<option value='deaktiviert'";
 if($WinterBeginn=="deaktiviert") echo" selected";
 echo">".$KAs[52]."</option>";
 for($t=7;$t <=12;$t++ ){
 echo"<option value='".$t."'"; if($WinterBeginn==$t) echo" selected"; echo">1 ".$monate[$t]."</option>";
 $halb=$t+0.5;
 echo"<option value='".$halb."'"; if($WinterBeginn==$halb ) echo" selected"; echo">15 ".$monate[$t]."</option>";
 }
 echo"</select></td>";
 echo"<tr>";

 echo"\n<td><b>&nbsp;".$KAs[53];
 echo"<select style='display:inline;'name='neusommertagzeit'>";
 for($t=4;$t <=10;$t++ ){
 echo"<option value='".$t."'"; if($SommerTagZeit==$t) echo" selected"; echo">".$t."&nbsp;".$KAs[54]."</option>";
 }
 echo"</select>";

 echo "&nbsp;".$KAs[106]."&nbsp; <select style='width:50px; display:inline;' name='neusommersendeintervalltag'>&nbsp;".$KAs[55];
 echo"<option value='5'"; if($SommerSendeIntervallTag==5) echo" selected"; echo">5</option>";
 echo"<option value='10'"; if($SommerSendeIntervallTag==10) echo" selected"; echo">10</option>";
 echo"<option value='15'"; if($SommerSendeIntervallTag==15) echo" selected"; echo">15</option>";
 echo"<option value='20'"; if($SommerSendeIntervallTag==20) echo" selected"; echo">20</option>";
 echo"<option value='30'"; if($SommerSendeIntervallTag==30) echo" selected"; echo">30</option>";
 echo"<option value='45'"; if($SommerSendeIntervallTag==45) echo" selected"; echo">45</option>";
 echo"<option value='60'"; if($SommerSendeIntervallTag >=60) echo" selected"; echo">60</option>";
 echo"</select>";
 echo" ".$KAs[55]."</td>";

 echo"\n<td><b>".$KAs[56];
 echo"<select style='display:inline;'name='neusommernachtzeit'>";
 for($t=16;$t <=23;$t++ ){
 echo"<option value='".$t."'";
 if($SommerNachtZeit==$t) echo" selected";
 echo">".$t."&nbsp;".$KAs[54]."</option>";
 }
 echo"</select>";


 echo "&nbsp;".$KAs[106]."&nbsp; <select style='width:50px; display:inline;' name='neusommersendeintervallnacht'>&nbsp;".$KAs[55];
 echo"<option value='5'"; if($SommerSendeIntervallNacht==5) echo" selected"; echo">5</option>";
 echo"<option value='10'"; if($SommerSendeIntervallNacht==10) echo" selected"; echo">10</option>";
 echo"<option value='15'"; if($SommerSendeIntervallNacht==15) echo" selected"; echo">15</option>";
 echo"<option value='20'"; if($SommerSendeIntervallNacht==20) echo" selected"; echo">20</option>";
 echo"<option value='30'"; if($SommerSendeIntervallNacht==30) echo" selected"; echo">30</option>";
 echo"<option value='45'"; if($SommerSendeIntervallNacht==45) echo" selected"; echo">45</option>";
 echo"<option value='60'"; if($SommerSendeIntervallNacht >=60) echo" selected"; echo">60</option>";
 echo"</select>";
 echo" ".$KAs[55]."</td>";


 echo "\n<td><b>".$KAs[57]."&nbsp; <select style='width:50px; display:inline;' name='neuwintersendeintervall'>&nbsp;".$KAs[55];
 echo"<option value='5'"; if($WinterSendeIntervall==5) echo" selected"; echo">5</option>";
 echo"<option value='10'"; if($WinterSendeIntervall==10) echo" selected"; echo">10</option>";
 echo"<option value='15'"; if($WinterSendeIntervall==15) echo" selected"; echo">15</option>";
 echo"<option value='20'"; if($WinterSendeIntervall==20) echo" selected"; echo">20</option>";
 echo"<option value='30'"; if($WinterSendeIntervall==30) echo" selected"; echo">30</option>";
 echo"<option value='45'"; if($WinterSendeIntervall==45) echo" selected"; echo">45</option>";
 echo"<option value='60'"; if($WinterSendeIntervall >=60) echo" selected"; echo">60</option>";
 echo"</select>";
 echo" ".$KAs[55]."</td></tr>";

 echo"</tr></table>";

 //nächstes Sendeintervall

 if(file_exists('beelogger_map.php')) include('beelogger_map.php');

 echo"<table border='4' style='color:black; background-color: #bdc3c7;'><td rowspan=3><img src='../beelogger_icons/time.png' width='30' height='30' style='margin-bottom:-4px';</td><td rowspan=3>".$KAs[31].":&nbsp;</td>";

 echo"<td>&nbsp;".$KAs[289]."</td>";
 echo"<td>&nbsp;".$KAs[290]."</td>";

 echo"<tr><td>&nbsp;".$KAs[58]."</td>";
 if(strpos($beeloggerSketchID,"EE") !==FALSE) echo"<td colspan=3style='background-color: #a9dfbf;'>";
 else echo"<td colspan=3>";
 echo"<b>\n";

 echo"<select name='neueesendeintervall'>";
 echo"<option value='A'";
 if($EESendeIntervall=="A") echo" selected";
 echo">".$KAs[59]."</option>";
 echo"<option value='B'";
 if($EESendeIntervall=="B") echo" selected";
 echo">".$KAs[60]."</option>";
 echo"<option value='C'";
 if($EESendeIntervall=="C") echo" selected";
 echo">".$KAs[61]."</option>";
 echo"<option value='D'";
 if($EESendeIntervall=="D") echo" selected";
 echo">".$KAs[62]."</option>";
 echo"</select>";
 echo"</td></tr>";


 // nächster Service
 echo"<tr><td>";
 echo"<b>\n&nbsp;".$KAs[63].":&nbsp;</td>";
 if(file_exists("loc.php")) echo"<td colspan=1>"; // Locationangabe folgt
 else echo"<td colspan=3>";

 echo"<select name='neunextservice'>";
 if(time() > ($NextServiceP_Time + 86400)) $NextService=""; //"Pegelmessung" nur für 24 Stunden
 echo"<option value=''";
 if($NextService=="") echo" selected";
 echo">".$KAs[64]."</option>";
 echo"<option value='P'";
 if($NextService=="P") echo" selected";
 echo">".$KAs[65]."</option>";
 echo"</select>";
 echo"</td>";

 if($beeloggerSketchID=="" OR strpos($beeloggerSketchID,"GSM") !==FALSE OR strpos($beeloggerSketchID,"LTE") !==FALSE){

 if(file_exists("loc.php")){
 include("loc.php");
 echo"<td style='color:black; background-color: #a9dfbf;'>";
 echo $KAs[68].'='.round($lat,2).' '.$KAs[69].'='.round($lon,2).' '.$KAs[70].' <a href="https://nominatim.openstreetmap.org/reverse.php?format=html&lat='.$lat.'&lon='.$lon.'&z=16" target="_blank" rel="noopener">OpenStreetMap</a>';
 echo ' '.$KAs[71].' <a href="https://www.google.de/maps/@'.$lat.','.$lon.',15z" target="_blank" rel="noopener">GoogleMaps</a>'.$KAs[72];
 echo"</td>";
 echo"<td style='color:black; background-color: #a9dfbf;'>&nbsp;".$KAs[73]."&nbsp;&nbsp;";

 echo '<select name="standortloeschen">';
 echo"<option value='0' selected >".$KAs[10]."</option>";
 echo"<option value='1' >".$KAs[24]."</option>";
 echo"&nbsp;&nbsp;";
 echo"</select></td></tr>";
 }
 }
 echo"</table>";
 echo"<table border='4' style='color:black; background-color: #FFFFA4;'><td rowspan=3><img src='../beelogger_icons/time.png' width='30' height='30' style='margin-bottom:-4px';</td><td rowspan=3>".$KAs[288]."&nbsp;</td></table><br><br>";


//Log Passwort
if($MultiType > 0){
echo '<table><td style="font-size:20px; color:blue;">'.$KAs[4].'</td>';

echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
 '.$HAs[4].'.</td>';

if($MultiType > 0) echo '<td>'.$HAs[5].': ! * ‘ ( ) ; : @ &=+ $ , / ? % # [ ]'.$HAs[6].'.</td>';
else echo"<td></td>";

echo' </tr>
<tr><td colspan=2>'.$HAs[7].'.</td></tr>
 </table>
</details></td></table>';

echo"<table border='4' style='color:black; background-color: #f8f9f9;'><tr>";

 if($BeeloggerLogPasswort=="Log") echo"<td style='color:black; background-color:#e7846f'>";
 else echo"<td style='color:black; background-color:#a9dfbf;'>";
 echo '<b>&nbsp;beelogger_log - '.$KAs[7].': <input type="text" name="neubeeloggerlogpasswort" ';
 echo 'value="'.$BeeloggerLogPasswort.'" size="8" maxlength="8">&nbsp;</td>';

 echo"</tr></table><br>";
 echo"<br>&nbsp;";
}

 // Temperaturkompensation
 if($MultiType > 1){ //für Multi
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[74].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><td>'.$HAs[14].'.</td>
 </table>
</details></td></table>';

 echo"<table border='4' style='color:black; background-color: #a6acaf
 ;'><tr><td><b>".$KAs[75]."&nbsp;</b></td>";
 for($i=1;$i <=$MultiType;$i++){
 if($KorrekturwertArray[$i-1] !="" AND $KorrekturwertArray[$i-1] !="0") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>&nbsp;".$KAs[76].$i.":<br><input type='text' style='width:50px;' style='display:inline;' name='neukorrekturwert".$i."' value='".$KorrekturwertArray[$i-1]."'>&nbsp;".$GewichtsEinheit." / ".$KAs[77]."</b></td>";
 }

 if($KalibrierTemperatur !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>&nbsp;".$KAs[78]."&nbsp; <input type='text' style='width:40px;' style='display:inline;' name='neukalibriertemperatur' value='".$KalibrierTemperatur."'>&nbsp;".$KAs[77]."&nbsp;</b></td>";
 echo"<tr><td> ".$KAs[79]."</td>";
 echo"<td align=center>HX711-1 ".$KAs[80].":A</td><td align=center>HX711-1 ".$KAs[80].":B</td>";
 if($MultiType > 2) echo"<td align=center>HX711-2 ".$KAs[80].":A</td>";
 if($MultiType > 3) echo"<td align=center>HX711-2 ".$KAs[80].":B</td>";
 if($MultiType > 4) echo"<td align=center>HX711-3 ".$KAs[80].":A</td>";
 if($MultiType > 5) echo"<td align=center>HX711-3 ".$KAs[80].":B</td>";
 if($MultiType > 6) echo"<td align=center>HX711-4 ".$KAs[80].":A</td>";
 echo"<td></td></tr></table><br>";

 echo"<table border='4'style='color:black; background-color: #a6acaf
 ;'><td style='background-color: white;'><img src='../beelogger_icons/press.png' height=25 width=25>";
	if(!isset($KorrPressure)) $KorrPressure="0.0";
 if($KorrPressure !="0.0") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>&nbsp;Korrektur ".$KAs[176]."&nbsp; <input type='text' style='width:40px;' style='display:inline;' name='neukorrpressure' value='".$KorrPressure."'>&nbsp;hPa&nbsp;</b></td>";
	echo"</table><br>";

 }else{
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[81].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><td>'.$HAs[14].'.</td>
 </table>
</details></td></table>';
 echo"<table border='4'style='color:black; background-color: #a6acaf
 ;'><td style='background-color: white;'><img src='../beelogger_icons/weight.png' height=25 width=25>";

 if($Korrekturwert !="" AND $Korrekturwert !="0") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>".$KAs[82]."<input type='text' style='width:50px;' style='display:inline;' name='neukorrekturwert' value='".$Korrekturwert."'>&nbsp;".$GewichtsEinheit." / ".$KAs[77]."</b></td>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/bw_temp.png' height=25 width=25>";
 if($KalibrierTemperatur !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>&nbsp;".$KAs[78]."&nbsp; <input type='text' style='width:40px;' style='display:inline;' name='neukalibriertemperatur' value='".$KalibrierTemperatur."' >&nbsp;".$KAs[77]."&nbsp;</b></td></table><br>";

 echo"<table border='4'style='color:black; background-color: #a6acaf
 ;'><td style='background-color: white;'><img src='../beelogger_icons/press.png' height=25 width=25>";
	if(!isset($KorrPressure)) $KorrPressure="0.0";
 if($KorrPressure !="0.0") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>&nbsp;Korrektur ".$KAs[176]."&nbsp; <input type='text' style='width:40px;' style='display:inline;' name='neukorrpressure' value='".$KorrPressure."'>&nbsp;hPa&nbsp;</b></td>";
	echo"</table><br>";
 }
} //if($MultiType >=1)
elseif($MultiType==0){ // für Unterbeelogger Hinweis anzeigen
 //Hinweis Fernsteuerung
 echo'<span style="font-size:20px; color:blue;">'.$KAs[30].'</span>';
 echo"<table border='2' style='color:black; background-color:white'><td><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';></td><td>&nbsp;".$KAs[241]."&nbsp;</td>";

 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";

 include ($WechselIniName);
 echo"<tr>";
 echo"<td><img src='../beelogger_icons/time.png' width='18' height='18' style='margin-bottom:-4px';></td>";
 echo"<td colspan=3 align=middle style='color:black; ";
 if($IntervallSendeSteuerung !="deaktiviert") echo"background-color: #a9dfbf;'>";
 else echo"background-color:#626567;'>";
 echo $KAs[237].": ";
 if($IntervallSendeSteuerung=="deaktiviert") echo $KAs[33];
 if($IntervallSendeSteuerung=="zeitgesteuert") echo $KAs[34];
 if($IntervallSendeSteuerung=="solarspannungsgesteuert") echo $KAs[35];
 if($IntervallSendeSteuerung=="lichtgesteuert") echo $KAs[36];
 echo"</td></tr>";
 echo"</table><br>";
 include ("beelogger_ini.php");


 //Hinweis Tempkomp
 echo'<span style="font-size:20px; color:blue;">'.$KAs[74].'</span>';
 echo"<table border='2' style='color:black; background-color:white'><td><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';></td><td>&nbsp;".$KAs[241]."&nbsp;</td>";

 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";

 include ($WechselIniName);
 echo"<tr >";
 echo"<td><img src='../beelogger_icons/off_weight.png' width='18' height='18' style='margin-bottom:-4px';></td>";
 echo"<td colspan=3 align=middle style='color:black; ";
 if($KorrekturwertArray[$ServerMultiUnterordnerNummer-1] !="") echo"background-color: #a9dfbf;'>";
 else echo"background-color:#a6acaf;'>";
 echo $KAs[244];
 if($KorrekturwertArray[$ServerMultiUnterordnerNummer-1] !="") echo"&nbsp;".$KAs[245].":&nbsp;".$KorrekturwertArray[$ServerMultiUnterordnerNummer-1]." ".$GewichtsEinheit."/".$KAs[77]."&nbsp;".$KAs[246]."&nbsp;".$KalibrierTemperatur."&nbsp;".$KAs[77];
 else echo"&nbsp;".$KAs[247].".";
 echo"</td></tr>";
 echo"</table>";
 include ("beelogger_ini.php");
}

if($MultiType>=0){
echo"<br><br><br><br>";

//Symbole: Daten speichern/verwerfen und zurück zur Show
echo"<table>";
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/arrow.png" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/konfig1.png" height="50" ><img src="../beelogger_icons/konfig.png" value="1" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/cancel.png" height="50" ></td></tr>';

echo"<tr><td><button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='configsichern' value='1'><b>".$KAs[203]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: red;' name='configsichern' value='0'><b>".$KAs[205]."</b></button>&nbsp;&nbsp;</td>";
echo"</table>";

echo"<br><hr><hr><br>";
}

// externe
if($MultiType >= 1){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[101].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[17].'.</td></tr>
 </table>
</details></td></table>';
}

// Multi-Beep ---------------------------------------
if($MultiType > 1){
 //$b_icon = "bw_beep.png";for($ib=1;$ib <=$MultiType;$ib++){  if($BeepArray[$ib-1]=="aktiviert") $b_icon = "beep-icon.png"; }
 $b_icon = "beep-icon.png";
 echo"<table border='4' style='color:black; background-color:#a4a4a4;'><td style='background-color: white;'><img src='../beelogger_icons/".$b_icon."' height=30 width=30 style='margin-bottom:-4px';> <td><b>&nbsp;".$KAs[102]."&nbsp;</b></td>";

 for($ib=1;$ib <=$MultiType;$ib++){
echo"<tr>";
echo"<td>&nbsp;</td>";
 if($BeepArray[$ib-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;''>";
 else echo"<td>";
 echo"<b>\n&nbsp; ".$KAs[103]."&nbsp; beelogger".$MultiSign.$ServerMultiNumber."_".$ib.": ";
 echo"<input type='checkbox' name='neubeep".$ib."' value='aktiviert'";
 if($BeepArray[$ib-1]=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 if($BeepArray[$ib-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;''>";
 else echo"<td>";
 echo"\n<b>&nbsp; Beep-ID ".$ServerMultiNumber."_".$ib.": <br>&nbsp;<input type='text' style='display:inline;' name='neubeepid".$ib."' value='".$BeepIdArray[$ib-1]."' size='12' maxlength='64'>&nbsp;&nbsp;&nbsp;</td>";
echo"</tr>";
 }
 echo"</table><br>";
} // if($MultiType > 1)
elseif($MultiType==1){
 //$b_icon = "bw_beep.png"; if($Beep=="aktiviert") $b_icon = "beep-icon.png";
 $b_icon = "beep-icon.png";
 echo"<table border='4' style='color:black; background-color:#a4a4a4;'><td style='background-color: white;'><img src='../beelogger_icons/".$b_icon."' height=30 width=30 style='margin-bottom:-4px';> <td><b>&nbsp;".$KAs[102]."&nbsp;</b></td>";

 if($Beep=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"<b>\n&nbsp;";
 echo"<input type='checkbox' name='neubeep' value='aktiviert'";
 if($Beep=="aktiviert") echo" checked";
 echo">&nbsp;</td>";

 if($Beep=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"\n<b>&nbsp;Beep-ID: <input type='text' style='display:inline;' name='neubeepid' value='".$BeepId."' size='12' maxlength='64'>&nbsp;&nbsp;&nbsp;</td></table><br>";
}
elseif($MultiType==0){//Unterbeelogger Hinweis
 echo'<span style="font-size:20px; color:blue;">'.$KAs[101].'</span>';
 echo"<table border='2' style='color:black; background-color: white'><td align=middle><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';</td><td>&nbsp;".$KAs[241]."&nbsp;</td>";
 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";

 echo"<tr>";
 echo"<td align=middle><img src='../beelogger_icons/bw_beep.png' height=18 style='margin-bottom:-4px';></td>";
 echo"<td colspan=3 align=middle style='color:black; ";
 if($BeepArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo"background-color: #a9dfbf;'>";
 else echo"background-color:#a4a4a4;'>";
 echo $KAs[249];
 if($BeepArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo"&nbsp;".$KAs[250].": ".$BeepIdArray[$ServerMultiUnterordnerNummer-1]."&nbsp;".$KAs[252].".";
 else echo"&nbsp;".$KAs[251].".";
 echo"</td></tr>";
}

// Multi-Keeper ---------------------------------------
//echo"<br><b> ... maybe in the future ...</b>";
if($MultiType > 1){
 for($ib=1;$ib <=$MultiType;$ib++){if($KeeperArray[$ib-1]=="aktiviert") $b_icon = "ibeekeeper.png"; }
 $b_icon = "ibeekeeper.png";

 echo"<table border='4' style='color:black; background-color:#a4a4a4 ;'><tr><td style='background-color: white;'><img src='../beelogger_icons/".$b_icon."' height=30 width=120 style='margin-bottom:-4px';> <td><b>&nbsp;".$KAs[253]."&nbsp;</b></td>";
for($ib=1;$ib <=$MultiType;$ib++){
echo"<tr>";
echo"<td>&nbsp;</td>";
 if($KeeperArray[$ib-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;''>"; else echo"<td>";
 echo"<b>\n&nbsp; ".$KAs[103]."&nbsp; beelogger".$MultiSign.$ServerMultiNumber."_".$ib.": ";
 echo"<input type='checkbox' name='neuKeeper".$ib."' value='aktiviert'";
 if($KeeperArray[$ib-1]=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 if($KeeperArray[$ib-1]=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;''>"; else echo"<td>";
 echo"\n<b>&nbsp; ID: <br>&nbsp;<input type='text' style='display:inline;' name='neuKeeperid".$ib."' value='".$KeeperIdArray[$ib-1]."' size='48' maxlength='64'></b>&nbsp;&nbsp;&nbsp;</td>";
echo"</tr>";
}
echo"</tr></table><br>";
} // if($MultiType > 1)
elseif($MultiType==1){
 $b_icon = "ibeekeeper.png";
 echo"<table border='4' style='color:black; background-color:#a4a4a4 ;'><td style='background-color: white;'><img src='../beelogger_icons/".$b_icon."' height=30 width=120 style='margin-bottom:-4px';> <td><b>&nbsp;".$KAs[253]."&nbsp;</b></td>";

 if($Keeper=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>"; else echo"<td>";
 echo"<b>\n&nbsp;";
 echo"<input type='checkbox' name='neuKeeper' value='aktiviert'";
 if($Keeper=="aktiviert") echo" checked";
 echo">&nbsp;</b></td>";

 if($Keeper=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>"; else echo"<td>";
 echo"\n<b>&nbsp;ID: &nbsp;<input type='text' style='display:inline;' name='neuKeeperid' value='".$KeeperId."' size='48' maxlength='64'>&nbsp;&nbsp;&nbsp;</td>";

 echo"</table><br>";
}
elseif($MultiType==0){ //Unterbeelogger Hinweis
 echo"<tr><td align=middle><img src='../beelogger_icons/ibeekeeper.png' height=18 width=80 style='margin-bottom:-4px';></td>";
 echo"<td colspan=3 align=middle style='color:black; ";
 if($KeeperArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo"background-color: #a9dfbf;'>"; else echo"background-color:#a4a4a4;'>";
 echo $KAs[253];
 if($KeeperArray[$ServerMultiUnterordnerNummer-1]=="aktiviert") echo"&nbsp;".$KAs[254].": ".$KeeperIdArray[$ServerMultiUnterordnerNummer-1]." &nbsp; ".$KAs[252].".";
 else echo"&nbsp;".$KAs[251].".";
 echo"</td></tr><br>";
}

// ThingsPeak ---------------------------------------
if($MultiType>1){//Info
 echo"<table border='4' style='color:white; background-color:#626567;'>";
 echo"<tr>";
 echo"<td colspan=2 style='color:black; background-color:#5bbad6';><img src='../beelogger_icons/thingspeak_icon.png' height=30 style='margin-bottom:-4px';></td>";
 $actual_linkpos=strrpos(strstr((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? "https" : "https") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE),"/");
 $lclurl=substr(strstr((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? "https" : "https") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'/beelogger_show',TRUE), 0,$actual_linkpos);
 $ThingsPeak="";
 for($ib=1;$ib <=$MultiType;$ib++){

  $IniFile="../beelogger".$MultiSign.$ServerMultiNumber."_".$ib."/beelogger_ini.php";
  if(file_exists($IniFile))include($IniFile);
  if($ThingsPeak=="aktiviert")echo"<td style='color:black; background-color:#a9dfbf;'>";
  else echo"<td style='color:black; background-color:#d7dbdd;'>";
  $nurl = $lclurl."/beelogger".$MultiSign.$ServerMultiNumber."_".$ib."/beelogger_show.php";
  echo"<a href='".$nurl."'>&nbsp;beelogger".$MultiSign.$ServerMultiNumber."_".$ib."&nbsp;</a></td>";
 }
 echo"</tr></table>";
}
if($MultiType==1 OR $MultiType==0){
 $ThingsPeak=""; //INIT
 $ThingsPeakApi=""; //INIT
 $ThingsPeakChannel=""; //INIT
 $ThingsPeakSensoren=""; //INIT
 include("beelogger_ini.php"); echo "Wechselini " .$WechselIniName;

 echo"<table border='4' style='color:white; background-color:#626567;'>";
 if($ThingsPeak=="aktiviert" AND ($ThingsPeakApi !="" OR $ThingsPeakApi !="Meine Api")) echo"<tr style='color:black; background-color: #a9dfbf;'>";
 else echo"<tr>";
 echo"<td colspan=1 style='color:black; background-color:#5bbad6';><img src='../beelogger_icons/thingspeak_icon.png' height=30 style='margin-bottom:-4px';></td>";


 if($ThingsPeak=="aktiviert" AND ($ThingsPeakApi !="" OR $ThingsPeakApi !="Meine Api")) echo"<tr style='color:black; background-color: #a9dfbf;'>";
 else echo"<tr>";
 echo"<td style='color:lightgrey; background-color:#626567';><b>&nbsp;".$KAs[104]."?&nbsp;</b></td>";
 echo"<td><b>\n&nbsp;";
 echo"<input type='checkbox' name='neuthingspeak' value='aktiviert'";
 if($ThingsPeak=="aktiviert") echo" checked";
 echo">&nbsp;</td>";
 echo"\n<td><b>&nbsp;ThingSpeak Channel ID:<br>&nbsp; <input type='text' style='display:inline;' name='neuthingspeakchannel' value='".$ThingsPeakChannel."' size='15' maxlength='7'>&nbsp;&nbsp;&nbsp;</td>";
 echo"\n<td><b>&nbsp;ThingSpeak Write API Key:<br>&nbsp;<input type='text' style='display:inline;' name='neuthingspeakapi' value='".$ThingsPeakApi."' size='24' maxlength='24'>&nbsp;&nbsp;&nbsp;</td>";

echo"</tr><tr>";
 //Felder
 for($TP=1;$TP < 5;$TP++) {
 if($ThingsPeakSensoren[$TP]=="0") echo"<td style='color:black; background-color:#a4a4a4;'>";
 else echo"<td style='color:black; background-color:#a9dfbf;'>";
 echo"&nbsp;Field ".$TP.":&nbsp;<select style='display:inline;' name='neuthingspeaksensor".$TP."'>";
 for($i=1;$i < $AnzahlSensoren+1;$i++){
 if(!is_numeric($Sensoren[($i-1)*5])){
 echo"<option value='".$i."'";
 if($ThingsPeakSensoren[$TP]==$i) echo" selected";
 echo">".$Sensoren[($i-1)*5]."</option>";
 }
 }
 echo"<option value='0'";
 if($ThingsPeakSensoren[$TP]==0) echo" selected";
 echo">deaktiviert</option></select>&nbsp;</td>";
 }
 echo"</tr><tr>";
 //Zeile2
 for($TP=5;$TP < 9;$TP++){
 if($ThingsPeakSensoren[$TP]=="0") echo"<td style='color:black; background-color:#a4a4a4;'>";
 else echo"<td style='color:black; background-color:#a9dfbf;'>";
 echo"&nbsp;Field ".$TP.":&nbsp;<select style='display:inline;' name='neuthingspeaksensor".$TP."'>";
 for($i=1;$i < $AnzahlSensoren+1;$i++){
 if(!is_numeric($Sensoren[($i-1)*5])){
 echo"<option value='".$i."'";
 if($ThingsPeakSensoren[$TP]==$i) echo" selected";
 echo">".$Sensoren[($i-1)*5]."</option>";
 }
 }
 echo"<option value='0'";
 if($ThingsPeakSensoren[$TP]==0) echo" selected";
 echo">deaktiviert</option></select>&nbsp;</td>";
 }
 echo"</tr></table><br>";
}

echo '<br>';


// --------------------------- beelogger Map -------------------------

if($MultiType==0){ //nur Hinweis
 echo'<span style="font-size:20px; color:blue;">'.$KAs[105].'</span>';
 echo"<table border='2' style='width:90%; color:black; background-color: white'><td align=middle><img src='../beelogger_icons/off_no.png' width='18' height='18' style='margin-bottom:-4px';</td><td>&nbsp;".$KAs[241]."&nbsp;</td>";
 echo"<td><a href='".$NeubeeloggerURL."'>&nbsp;".$MultiTypeName.$ServerMultiNumber."&nbsp;</a></td>";
 echo"<tr>";
 echo"<td><img src='../beelogger_icons/bw_map.png' width='18' height='18' style='margin-bottom:-4px';</td>";
 echo"<td colspan=3 align=middle style='color:black; ";
 if(($beeloggerMap2=="aktiviert") AND (int)$beeloggerMapWaage==$ServerMultiUnterordnerNummer) echo"background-color: #a9dfbf;'>";
 else echo"background-color:#a4a4a4;'>";
 echo $KAs[111];
 if(($beeloggerMap2=="aktiviert") AND (int)$beeloggerMapWaage==$ServerMultiUnterordnerNummer) echo"&nbsp;".$KAs[89].".";
 else echo"&nbsp;".$KAs[33].".";
 echo"</td></tr>";
 echo"</table><br>";
 include ("beelogger_ini.php");
}

//MAP2 ----------------------------------------------------------------
if($MultiType > 0){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[105].'</td>';
 echo'<td><details>
 <summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>
'.$HAs[18].'
<br>beelogger aktiv <img src="../beelogger_icons/map_aktiv.png" width="70" height="30"> beelogger inaktiv <img src="../beelogger_icons/map_inaktiv.png" width="70" height="30">
 </td></tr>
 </table>
</details></td></table>';

 echo"<table border='4' style='width:90%; color:lightgray; background-color: #4d5656;'>";
 if($beeloggerMap2=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'><img src='../beelogger_icons/map_aktiv.png' width='100' height='40'></td>";
 else echo"<td><img src='../beelogger_icons/map_inaktiv.png' width='100' height='40'></td>";
 echo"<td align='middle'></b>&nbsp;<a href='https://beelogger.de/?page_id=196999' style='color:white;' target='_blank'>&nbsp;".$KAs[118]."&nbsp;</a></td>";

 if((strlen($beeloggerMap2ID)==8) AND (strlen($beeloggerMap2Key)==6 AND $beeloggerMap2BeeID !="")){//ID bereits erhalten

 if($beeloggerMap2=="aktiviert") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";

 if(file_exists("loc.php") AND ($beeloggerMap2Lat=="00.00" OR $beeloggerMap2Lat=="00.00" OR $beeloggerMap2Lat=="" OR $beeloggerMap2Lat=="")){
 //eventuellen GSM Standort übernehmen)
 include("loc.php");
 if($beeloggerMap2Lat=="00.00" OR $beeloggerMap2Lat=="") $beeloggerMap2Lat=$lat;
 if($beeloggerMap2Lon=="00.00" OR $beeloggerMap2Lon=="") $beeloggerMap2Lon=$lon;
 }

 $Fehlend="";
 $Vollstaendig=true;
 if($beeloggerMap2Location==""){$Fehlend .=" ".$KAs[107].", ";$Vollstaendig=false;}
 if($beeloggerMap2Lat=="" OR $beeloggerMap2Lat=="00.00"){$Fehlend .=" ".$KAs[108].", ";$Vollstaendig=false;}
 if($beeloggerMap2Lon=="" OR $beeloggerMap2Lon=="00.00"){$Fehlend .=" ".$KAs[109].", ";$Vollstaendig=false;}
 if($beeloggerMap2BeeloggerType=="" OR $beeloggerMap2Connect==""){$Fehlend .=$KAs[110];$Vollstaendig=false;}

 echo"<b>\n&nbsp;".$KAs[111]."&nbsp;";

 if($Vollstaendig==true){
 echo"<select name='neubeeloggermap2'>";
 echo"<option value='aktiviert'";
 if($beeloggerMap2=="aktiviert") echo" selected";
 echo">".$KAs[89]."</option>";
 echo"<option value='deaktiviert'";
 if($beeloggerMap2 !="aktiviert") echo" selected";
 echo">".$KAs[33]."</option>";
 echo"</select>&nbsp;";
 if($beeloggerMap2 !="aktiviert") echo" - ".$KAs[127]."!";
 }
 else echo $KAs[112]."! <br>&nbsp;".$KAs[113].": ".$Fehlend;
 echo"</td>";

 //beelogger ist deaktiviert ! Evtl alle Daten löschen?
 if($beeloggerMap2=="deaktiviert" OR $beeloggerMap2==""){
 echo"<tr><td colspan=2>&nbsp;".$KAs[230]."?</td>";
 echo"<td><select name='beeloggermap2loeschebeeid'>";
 echo"<option value='Nein' selected>&nbsp;".$KAs[10]."&nbsp;</option>";

 echo"<option value='loeschen' style='color:red';>&nbsp;".$KAs[231]."</option>";
 echo"</select>&nbsp;</td>";
 echo"<tr>";
 }

 if($MultiType > 1){
 echo"<tr><td>";
 echo"<b>\n&nbsp;".$KAs[117]."</td>";
 echo"<td><select name='neubeeloggermapwaage'>";

 for($i=1;$i <=$MultiType;$i++){
 echo"<option value='".$i."'";
 if((int)$beeloggerMapWaage==$i) echo" selected";
 echo">beelogger".$MultiSign.$ServerMultiNumber."_".$i."</option>";
 }

 echo"</select></td><td style='color:black; background-color: white'> ".$KAs[114]."</td></tr>";
 } // if($MultiType > 1)


 echo"<tr>";
 if($beeloggerMap2Location=="" AND $Bienenvolkbezeichnung !="auto") $beeloggerMap2Location=$Bienenvolkbezeichnung;
 if($beeloggerMap2Location==""){
 $pfad=getcwd(); // kompletter Verzeichnispfad
 $verz=strrchr($pfad, "/"); // Verzeichnisname einschließlich des / zu Beginn
 $beeloggerMap2Location=str_replace("/","",$verz);// / noch entfernt
 }
 echo"<td>".$KAs[107]."&nbsp;</td>";

 if($beeloggerMap2Location !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";
 echo"<input type='text' style='display:inline;' name='neubeeloggermap2location' value='".html_entity_decode($beeloggerMap2Location)."' size='20' maxlength='20'>&nbsp;&nbsp;&nbsp;</td>";
 echo"<td style='color:black; background-color: white'> ".$KAs[115]."</td>";
 echo"</tr>";

 if($beeloggerMap2Lat=="") $beeloggerMap2Lat="00.00";
 if($beeloggerMap2Lon=="") $beeloggerMap2Lon="00.00";
 echo"<td>".$KAs[108]."&nbsp;</td>";
 if($beeloggerMap2Lat !="" AND $beeloggerMap2Lat !="00.00") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";

 $LatLonInfo=""; //INIT
 if(file_exists("loc.php") AND $beeloggerMap2Lat==$lat) $LatLonInfo=$KAs[228];

 echo"<input style='display:inline;' title='".$KAs[120]."' name='neubeeloggermap2lat' value='".number_format($beeloggerMap2Lat,2,'.','')."' pattern='[0-9\-]{1,3}[.]{1}[0-9]{1,2}' size='6' maxlength='6' >&nbsp;".$LatLonInfo."</td>";
 echo '<td rowspan="2" style="color:black; background-color: white"> '.$KAs[116].'&nbsp;';
 echo"<a href='https://nominatim.openstreetmap.org' target='_blank'>OpenStreetMaps</a>";
 echo $KAs[119].'.</td></tr>';

 echo"<tr><td>".$KAs[109]."&nbsp;</td>";
 if($beeloggerMap2Lon !="" AND $beeloggerMap2Lon !="00.00") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";
 $LatLonInfo=""; //INIT
 if(file_exists("loc.php") AND $beeloggerMap2Lat==$lat) $LatLonInfo=$KAs[228];
 echo"<input style='display:inline;' type='text' title='".$KAs[120]."' name='neubeeloggermap2lon' value='".number_format($beeloggerMap2Lon,2,'.','')."' pattern='[0-9\-]{1,3}[.]{1}[0-9]{1,2}' size='6' maxlength='6' >&nbsp;".$LatLonInfo."</td>";
 echo"</tr>";
 echo"<tr>";
 echo"<td rowspan='2'>".$KAs[110]." </td>";
 if($beeloggerMap2BeeloggerType !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";
 echo"Typ&nbsp;";
 echo '<select name="beeloggermap2beeloggertype" size="1">';
 if($beeloggerMap2BeeloggerType=="") echo '<option value="">'.$KAs[122].'</option>';

 echo '<option value="S"';
 if($beeloggerMap2BeeloggerType=="S") echo" selected";
 echo '>SMD</option>
 <option value="U"';
 if($beeloggerMap2BeeloggerType=="U") echo" selected";
 echo '>Universal</option>
 <option value="B"';
 if($beeloggerMap2BeeloggerType=="B") echo" selected";
 echo '>Blue</option>
 <option value="K"';
 if($beeloggerMap2BeeloggerType=="K") echo" selected";
 echo '>Black</option>
 <option value="E"';
 if($beeloggerMap2BeeloggerType=="E") echo" selected";
 echo '>EasyPlug/Funk</option>
 </select>';
 echo"</td>";
 echo"<td rowspan='2'style='color:black; background-color: white'> ".$KAs[123].". </td>";
 echo"<tr>";
 if($beeloggerMap2Connect !="" AND $beeloggerMap2Connect !="undefined") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td style='color:black; background-color: #e7846f;'>";
 echo $KAs[232].': <select name="beeloggermap2connect" size="1">';

 if($beeloggerMap2Connect=="") echo '<option value="" selected>'.$KAs[122].'</option>';
 if($beeloggerMap2Connect=="undefined") echo '<option value="undefined" selected>'.$KAs[122].'</option>';

 echo '<option value="G"';
 if($beeloggerMap2Connect=="G") echo" selected";
 echo '>GSM</option>';
 echo '<option value="T"';
 if($beeloggerMap2Connect=="T") echo" selected";
 echo '>LTE</option>';
 echo' <option value="W"';
 if($beeloggerMap2Connect=="W") echo" selected";
 echo '>WLAN</option>
 <option value="L"';
 if($beeloggerMap2Connect=="L") echo" selected";
 echo '>LORA</option>
 <option value="S"';
 if($beeloggerMap2Connect=="S") echo" selected";
 echo '>SIGFOX</option>
 <option value="A"';
 if($beeloggerMap2Connect=="A") echo" selected";
 echo '>LAN</option>
 <option value="F"';
 if($beeloggerMap2Connect=="F") echo" selected";
 echo '>Funk</option>
 </select>';
 echo"</td></tr>";

//------------------
 echo"<tr><td>URL&nbsp;</td>";

 if(strlen($beeloggerMap2URL) > 20) echo"<td style='color:black; background-color: #a9dfbf;'";
 else echo"<td";

 echo">&nbsp;".$KAs[239]."?&nbsp;";

 echo"<input type='checkbox' name='neubeeloggermap2url' value='aktiviert'";
 if(strlen($beeloggerMap2URL) > 20) echo" checked";
 echo">&nbsp;</td>";


 echo"<td style='color:black; background-color: white'>".$KAs[240];
 echo"</td></tr>";
//------------------
 if($beeloggerMap2Email=="" AND $beeloggerMap2=="" AND $Empfaenger_Email !="empfaenger@meineDomain.de") $beeloggerMap2Email=$Empfaenger_Email;
 echo"<tr><td>E-Mail&nbsp;</td><td><input type='email' name='neubeeloggermap2email' value='".$beeloggerMap2Email."' size='35'>&nbsp;&nbsp;</td><td style='color:black; background-color: white'>".$KAs[125]."</td>";
 echo"</tr></table>";

//----------------
 echo"<table border='4' style='width:90%; color:lightgray; background-color: #4d5656;'>";
 echo"<tr><td>&nbsp;".$KAs[233]."&nbsp;&nbsp;</td>";

 if($beeloggerMap2Sensoren[0] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;<img src='../beelogger_icons/off_temp_o.png' width='25' height='25'>&nbsp;</td>";
 if($beeloggerMap2Sensoren[0] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";

 echo"&nbsp;".$KAs[168]."&nbsp;";
 echo '<select name="neubeeloggermap2sensor1" size="1">';

 if($beeloggerMap2Sensoren[0]=="") echo '<option value="" selected>'.$KAs[234].'?</option>';
 else echo '<option value="">'.$KAs[235].'!</option>';

 for($s=0;$s < $AnzahlSensoren ;$s++){
 if(!is_numeric($Sensoren[$s*5])){
 echo '<option value="'.$s.'"';
 if($beeloggerMap2Sensoren[0] !="" AND $beeloggerMap2Sensoren[0]==$s) echo" selected";
 echo '>'.$Sensoren[$s*5].'</option>';
 }
 }
 echo '</select></td>';

//----------------
 if($beeloggerMap2Sensoren[1] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;<img src='../beelogger_icons/off_hum_o.png' width='25' height='25'>&nbsp;</td>";
 if($beeloggerMap2Sensoren[1] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";

 echo"&nbsp;".$KAs[170]."&nbsp;";
 echo '<select name="neubeeloggermap2sensor2" size="1">';

 if($beeloggerMap2Sensoren[1]=="") echo '<option value="" selected>'.$KAs[234].'?</option>';
 else echo '<option value="">'.$KAs[235].'!</option>';

 for($s=0;$s < $AnzahlSensoren ;$s++){
 if(!is_numeric($Sensoren[$s*5])){
 echo '<option value="'.$s.'"';
 if($beeloggerMap2Sensoren[1] !="" AND $beeloggerMap2Sensoren[1]==$s) echo" selected";
 echo '>'.$Sensoren[$s*5].'</option>';
 }
 }
 echo '</select></td>';

 //----------------
 if($beeloggerMap2Sensoren[2] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;<img src='../beelogger_icons/off_press.png' width='25' height='25'>&nbsp;</td>";
 if($beeloggerMap2Sensoren[2] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";

 echo"&nbsp;".$KAs[176]."&nbsp;";
 echo '<select name="neubeeloggermap2sensor3" size="1">';

 if($beeloggerMap2Sensoren[2]=="") echo '<option value="" selected>'.$KAs[234].'?</option>';
 else echo '<option value="">'.$KAs[235].'!</option>';

 for($s=0;$s < $AnzahlSensoren ;$s++){
 if(!is_numeric($Sensoren[$s*5])){
 echo '<option value="'.$s.'"';
 if($beeloggerMap2Sensoren[2] !="" AND $beeloggerMap2Sensoren[2]==$s) echo" selected";
 echo '>'.$Sensoren[$s*5].'</option>';
 }
 }
 echo '</select></td>';

 //----------------
 if($beeloggerMap2Sensoren[3] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";
 echo"&nbsp;<img src='../beelogger_icons/off_rain.png' width='25' height='25'>&nbsp;</td>";
 if($beeloggerMap2Sensoren[3] !="") echo"<td style='color:black; background-color: #a9dfbf;'>";
 else echo"<td>";

 echo"&nbsp;".$KAs[177]."&nbsp;";
 echo '<select name="neubeeloggermap2sensor4" size="1">';

 if($beeloggerMap2Sensoren[3]=="") echo '<option value="" selected>'.$KAs[234].'?</option>';
 else echo '<option value="">'.$KAs[235].'!</option>';
 for($s=0;$s < $AnzahlSensoren ;$s++){
 if(!is_numeric($Sensoren[$s*5])){
 echo '<option value="'.$s.'"';
 if($beeloggerMap2Sensoren[3] !="" AND $beeloggerMap2Sensoren[3]==$s) echo" selected";
 echo '>'.$Sensoren[$s*5].'</option>';
 }
 }
 echo '</select></td>';
 echo"</tr>";

 echo"<tr><td style='color:black; background-color: #a9dfbf;' colspan=9>&nbsp;".$KAs[236]."</td></tr>";
 echo"<tr><td colspan=9 align=right><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button></td></tr>";
 }
 else {
 echo"<td>";
 echo"\n<b>&nbsp;".$KAs[126]."&nbsp;&nbsp;";
 echo'<input type="checkbox" value="yes" name="neubeeloggermap2teilnahme">';
 }

 // versteckte Variablen
 echo"<input type='hidden' name='neubeeloggermap2beeid' value='".$beeloggerMap2BeeID."'>";
 echo"<input type='hidden' name='neubeeloggermap2status' value='".$beeloggerMap2Status."'>";
 echo"</table><br>";
} // Ende beeloggerMap


//Openweathermap
if($MultiType >=0){
 if(!$CommunityUser){
 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[264].'</td>';
 echo'<td><details><summary>
 <img src="../beelogger_icons/m_help.png" height=40 width=40 style="margin-bottom:-11px";>
 </summary>
 <table style="font-size:15px; color:'.$HelpColor.'; background-color: '.$HelpBackColor.';"><tr><td>'.$HAs[39].'.</td></tr>
 </table></details></td></table>';

 if($MultiType >=1){
 echo"<table border='4' style='color:black; background-color:#626567
 ;'>";
 if($OpenweathermapKey !="") echo"<tr style='color:black; background-color: #a9dfbf;'>";
 else echo"<tr>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/n_Wetter (Schnee-Hagel-Wind).png' height=30 style='margin-bottom:-4px';><td style='color:black; background-color:#626567;'><b>&nbsp;".$KAs[265]."&nbsp;&nbsp;</b></td>";

 echo"<td>\n<b>&nbsp;Openweathermap-Key: <input type='text' style='display:inline;' name='neuopenweathermapkey' value='".$OpenweathermapKey."' size='12' maxlength='40'>&nbsp;&nbsp;&nbsp;</td>";

 echo"<td><b>\n&nbsp;".$KAs[267].": ";
 echo"<select name='neuwettericons'>";
 echo"<option value='1'";

 if($WetterIcons=="") $WetterIcons="1";//init

 if($WetterIcons=="1") echo" selected";
 echo">beeloggerIcons</option>";

 echo"<option value='2'";
 if($WetterIcons=="2") echo" selected";
 echo">Openweathermap-Icons</option>";

 echo"<option value='3'";
 if($WetterIcons=="3") echo" selected";
 echo">Keine Wetteranzeige</option>";

 echo"</select></td>";
 echo"</tr></table><br>";
 }
}
else{ //CommunityUser!

 echo'<table><td style="font-size:20px; color:blue;">'.$KAs[266].'</td>';
 echo '</table>';

 echo"<table border='4' style='color:black; background-color:#a4a4a4;'>";
 echo"<td style='background-color: white;'><img src='../beelogger_icons/n_Wetter (Schnee-Hagel-Wind).png' height=30 style='margin-bottom:-4px';></td>";

 echo"<td><b>\n&nbsp;".$KAs[267].": ";
 echo"<select name='neuwettericons'>";
 echo"<option value='1'";

 if($WetterIcons=="") $WetterIcons="1";//init

 if($WetterIcons=="1") echo" selected";
 echo">beeloggerIcons</option>";

 echo"<option value='2'";
 if($WetterIcons=="2") echo" selected";
 echo">Openweathermap-Icons</option>";

 echo"<option value='3'";
 if($WetterIcons=="3") echo" selected";
 echo">Keine Wetteranzeige</option>";

 echo"</select>&nbsp;&nbsp;</td>";

 echo"</tr></table><br>";
}
}

//Symbole: Daten speichern/verwerfen und zurück zur Show
echo"<table>";
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/arrow.png" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/konfig1.png" height="50" ><img src="../beelogger_icons/konfig.png" value="1" height="50" ></td>';
echo '<td align="center"><img src="../beelogger_icons/show.png" height="50" ><img src="../beelogger_icons/cancel.png" height="50" ></td></tr>';

echo"<tr><td><button type='submit' style='BACKGROUND-COLOR: #a9dfbf;' name='configsichern' value='1'><b>".$KAs[203]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: #81BEF7;' name='config' value='1'><b>".$KAs[204]."</b></button>&nbsp;&nbsp;</td>";
echo"<td><button type='submit' style='BACKGROUND-COLOR: red;' name='configsichern' value='0'><b>".$KAs[205]."</b></button>&nbsp;&nbsp;</td>";
echo"<input type='hidden' name='cfg_complete' value='1'>";
echo"</table>";

echo"<br><hr><hr><br>";

if(file_exists("../mobileWatch_ini.php")) unlink("../mobileWatch_ini.php");//Abwärtskomp
if(file_exists("../general_ini.php")) include("../general_ini.php");


echo "<h2>beelogger PHP script versions:</h2>";
echo"<td>".$KAs[206].":&nbsp;&nbsp;&nbsp;".$Softwareversion;
$SoftwareversionNummer=(str_replace("M.","",$Softwareversion));
$AktuelleServerversion=file_get_contents('https://www.community.beelogger.de/X_AktuelleSkriptVersion.php');
if($AktuelleServerversion !=false){
$AktuelleServerversionNummer=(str_replace("M.", "", $AktuelleServerversion));
if($AktuelleServerversionNummer > $SoftwareversionNummer) echo"<h3>
<a href='https://www.community.beelogger.de/aktuelleWebserverskripte.zip'>".$KAs[207]."&nbsp; (".$AktuelleServerversion.")</a></h3>";
} else echo"&nbsp; ???";
echo "</td><br>";

$Version=file_get_contents("./beelogger_show.php",FALSE, NULL,870 ,23);
echo "<br>beelogger_show:<br>";
echo $Version."<br>";

$Version=file_get_contents("./beelogger_config.php",FALSE, NULL,857 ,23);
echo "<br>beelogger_config:<br>".$Version."<br>";

if(file_exists("./beelogger_log.php")){
 $Version=file_get_contents("./beelogger_log.php",FALSE, NULL,870 ,23);
 echo "<br>beelogger_log:<br>".$Version."<br>";
}
else {
 echo "<br>beelogger_log:<br> --> &nbsp;&nbsp;".$MultiTypeName.$ServerMultiNumber."<br>";
}

$Version=file_get_contents("./beelogger_wetter.php",FALSE, NULL,867 ,23);
echo "<br>beelogger_wetter:<br>".$Version."<br>";

$Version=file_get_contents("./CSVedit.php",FALSE, NULL,870 ,23);
echo "<br>CSVedit:<br>".$Version."<br>";

$Version=file_get_contents("../beelogger_mobileWatch.php",FALSE, NULL,896 ,23);
echo "<br>beelogger_mobileWatch:<br>".$Version."<br>";

$Version=file_get_contents("../beelogger_mobileCheck.php",FALSE, NULL,868 ,23);
echo "<br>beelogger_mobileCheck:<br>".$Version."<br>";

echo "</form></tr></table></div>";
}

if (!function_exists('msg_box_box')){function msg_box_box($text){
 echo'<script language="javascript">';
 echo'alert("nicht erlaubte Zeichen /\ncharacter not allowed\nin: '.$text.'")';
 echo'</script>';
}}
