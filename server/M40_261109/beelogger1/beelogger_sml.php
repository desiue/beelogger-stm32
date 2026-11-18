<?php
/*
 *(C)2024 Jeremias Bruker, Thorsten Gurzan, Rudolf Schick - beelogger.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 *(at your option)any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
*/
//   beelogger.de - Arduino Datenlogger für Imker
//   Erläuterungen dieses Programmcodes unter https://beelogger.de



$Softwareversion = "M.40";//vom 18.10.2026 - beelogger_sml.php
$show_version=0;//0, 1, 2 Programmertool

SESSION_START();

header('Content-Type: text/html; charset=UTF-8');

$mobile_tools=0;//0, allow mobileWatch(=1)and mobileCheck(=2), both(=3)
$analyse=0;//allow beelogger_Analyse 0,1
$more_beelogger=0;//allow selection of other beeloggers 0,1

$isHttps=(!empty($_SERVER['HTTPS']));
if($isHttps==0){
  $local_network="192.168.178.";
  if((strstr($_SERVER['REMOTE_ADDR'],$local_network)==false)&&(strstr($_SERVER['SERVER_NAME'],$local_network)==false)){

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="shortcut icon" href="./../beelogger_icons/favicon-32x32.png" />
<style type="text/css">
body { font-family: Arial, Helvetica, sans-serif; color: black; background-color: ivory}
</style>
<title>beelogger</title>
</head>
<body>
<?php
	echo"<h3>Webseite&nbsp;&nbsp;".$_SERVER['SERVER_NAME']."&nbsp;&nbsp;bitte mit https://aufrufen<BR></h3>"."\n";
	$link="https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo '<h4><a href="'.$link.'" >'.$link.'</a></h4>'."\n";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$link.'"target="_blank"><img src="./../beelogger_icons/show.png" height="50px"></a>'."\n";
?>
</body>
</html>
<?php
    exit;
  }
}


error_reporting(0);
if($show_version){
  error_reporting(E_ERROR | E_WARNING | E_PARSE);
  if($show_version==2){error_reporting(E_ALL);ini_set('display_errors', "1");ini_set('display_startup_errors', true);}
}

$CommunityUser=false;
if($_SERVER['SERVER_NAME']=="community.beelogger.de") $CommunityUser=true;

$Sprache="1";//INIT falls noch keine Speicherung stattfand
if(file_exists("../general_ini.php"))include("../general_ini.php");//Sprache festlegen
if(file_exists("../beelogger_sprachfiles/Show_Sprache_".$Sprache.".php"))include("../beelogger_sprachfiles/Show_Sprache_".$Sprache.".php");//Sprache einbinden

date_default_timezone_set('Europe/Berlin');

$beelogger=substr(dirname(__FILE__), strrpos(dirname(__FILE__),"/")+1);
if(strchr($beelogger,"\\")){
  $beelogger=substr(dirname(__FILE__), strrpos(dirname(__FILE__),"\\")+1);
}

//Init some vars
$Message="";

$MultiType=1;//anzahl der Waagen pro beelogger
$MultiTypeAnzahl=1;
$MultiTypeName="";
$MultiSign="";
$WechselIniName="";//INIT
$WechselMapName="";//INIT
$beeloggerMap2="";
$beeloggerMap2BeeID="";
$LetztesVBatt="33";
$LetztesLicht=0;
$LetztesVSolar=0;
$AktualisierungsStamp=0;
$AktualisierungStampMessage=0;
$SendeIntervall=15;  
//Init vars not in older beelogger_ini
$KoeKaefig="";
$BeutenLeergewicht="";
$TwoGrid="false";
$chartMobile=3;
$Zoom="0";
$Summe="false";
$Underlay="7";
$Underlay_Mo="6";
$Underlay_Ab="18";
$Underlay_Col="FFFFA4";
$GTS_color="true";
$TageswertAchse="";//Abwärtskomp


if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");//Werte neu einlesen
else exit;

if(strlen($beelogger)<12 && strpos($beelogger,"beelogger")!==FALSE){
  $ServerMultiNumber=intval(str_replace("beelogger","",$beelogger));
}elseif(strpos($beelogger,'Duo')===0){//Duo an Position1
  $MultiTypeName="Duo";
  $MultiSign="D";
  $ServerMultiNumber=intval(str_replace("Duo","",$beelogger));
  $MultiType=2;//anzahl der Waagen pro beelogger
}elseif(strpos($beelogger,'Triple')===0){
  $MultiTypeName="Triple";
  $MultiSign="T";
  $ServerMultiNumber=intval(str_replace("Triple","",$beelogger));
  $MultiType=3;
}elseif(strpos($beelogger,'Quad')===0){
  $MultiTypeName="Quad";
  $MultiSign="Q";
  $ServerMultiNumber=intval(str_replace("Quad","",$beelogger));
  $MultiType=4;
}elseif(strpos($beelogger,'Penta')===0){
  $MultiTypeName="Penta";
  $MultiSign="P";
  $ServerMultiNumber=intval(str_replace("Penta","",$beelogger));
  $MultiType=5;
}elseif(strpos($beelogger,'Hexa')===0){
  $MultiTypeName="Hexa";
  $MultiSign="H";
  $ServerMultiNumber=intval(str_replace("Hexa","",$beelogger));
  $MultiType=6;
}else {
  $MultiType=0;//Unterordner der Multis
  if(strpos($beelogger,'Xanalyse')===0){
    $MultiType=-1;
    $XanalyseNummer=intval(substr($beelogger,-1));
  }
  $MultiSign=substr(str_replace("beelogger","",$beelogger),0,1);//D,T,Q,P oder S
  $ServerMultiNumber=substr(preg_replace('![^0-9_]!','',$beelogger),0,-2);
  $ServerMultiUnterordnerNummer=intval(substr($beelogger,-1,1));
  switch($MultiSign){
  case 'D':
    $MultiTypeName="Duo";
    $MultiTypeAnzahl=2;
    break;
  case 'T':
    $MultiTypeName="Triple";
    $MultiTypeAnzahl=3;
    break;
  case 'Q':
    $MultiTypeName="Quad";
    $MultiTypeAnzahl=4;
    break;
  case 'P':
    $MultiTypeName="Penta";
    $MultiTypeAnzahl=5;
    break;
  case 'H':
    $MultiTypeName="Hexa";
    $MultiTypeAnzahl=6;
    break;
  }
  if($MultiTypeAnzahl>1){
  $WechselIniName="../".$MultiTypeName.$ServerMultiNumber."/beelogger_ini.php";
  $WechselMapName="../".$MultiTypeName.$ServerMultiNumber."/beelogger_map.php";
  $WechselWetterName="../".$MultiTypeName.$ServerMultiNumber."/wetter_daten.php";
  $WechselLocName="../".$MultiTypeName.$ServerMultiNumber."/loc.php";
  }
$FillGraph=array();for($a=0;$a<34;$a++){$FillGraph[$a]="";}//needed for config	
}


                                 
            
$Csvdatei="";
if(isset($_GET["csvdatei"])){
 $Csvdatei=htmlentities(strip_tags(stripslashes($_GET["csvdatei"])));
}
if($Csvdatei=="")$Csvdatei=$StandardCSV;//Standard, wenn bisher nichts definiert wurde
if(!file_exists($Csvdatei))$Csvdatei="beelogger.csv";//falls der Standard nicht existiert

$AnzahlSensoren=(sizeof($Sensoren)/5);//Eventuell neue Anzahl an Sensoren
$HelpArraySensoren=$Sensoren;
$HelpAnzahlSensoren=$AnzahlSensoren;

if($MultiType==-1){//Xanalyse berechnen
  $AnzahlBeeloggerDateien=sizeof($XanalyseArray);
  $NewCount=0;//INIT
  $NewArray=array();
  $n_array=array();
  for($Xana=0;$Xana<=$AnzahlBeeloggerDateien;$Xana++){
   array_push($n_array,"");//create Array
  }
  array_push($NewArray,$n_array);

  for($Xana=0;$Xana<$AnzahlBeeloggerDateien;$Xana++){
  if($XanalyseArray[$Xana]!=""){//is configured?
    if(strchr($XanalyseArray[$Xana],"\\")){//windows
    $Xanalysebeelogger=substr($XanalyseArray[$Xana],0,-(strlen($XanalyseArray[$Xana])-strpos($XanalyseArray[$Xana],"\\")));
    $XanalyseSensor=substr($XanalyseArray[$Xana],(1+strrpos($XanalyseArray[$Xana],"\\")));
    }else{
    $Xanalysebeelogger=substr($XanalyseArray[$Xana],0,-(strlen($XanalyseArray[$Xana])-strpos($XanalyseArray[$Xana],"/")));
    $xa_s=1+strrpos($XanalyseArray[$Xana],"/");
    $xa_s=substr($XanalyseArray[$Xana],$xa_s);
    $XanalyseSensor=1+(int)$xa_s;
    }
    $datei="../".$Xanalysebeelogger."/".$Csvdatei;
    if(file_exists($datei)){
    $DateiArray=file($datei);
    $DateiArraySize=sizeof($DateiArray);//Grösse des Arrays

    $i=$DateiArraySize;
  if($i>0){
      while($i--){
        $what=trim($DateiArray[$i]);
        $x=explode(",",$what);
        $s=sizeof($x);//hier wird die Anzahl von Spalten pro Zeile im beelogger.csv ermittelt
        if($x[$s-1]!='')break;
      }
  }
    $NewArray[$NewCount][0]=date("Y/m/d H",$x[$s-1]);//Datum
    $NewArray[$NewCount][1+$Xana]=$x[$XanalyseSensor];//Sensordaten
    $NewCount++;
    array_push($NewArray,$n_array);
    for($ix=0;$ix<$i;$ix++){//alle Werte der Datei durchgehen
      $what=trim($DateiArray[$ix]);
      $x=explode(",",$what);
      $s=sizeof($x);
      if(date("Y/m/d H",$x[$s-1])!=$NewArray[$NewCount-1][0]){//Neue Stunde angebrochen
        array_push($NewArray,$n_array);
        $NewArray[$NewCount][0]=date("Y/m/d H",$x[$s-1]);//Datum
        $NewArray[$NewCount][1+$Xana]=$x[$XanalyseSensor];//Sensordaten
        $NewCount++;
      }
    }
    }
  }
  }

 if(sizeof($NewArray)>5){//sort and save
  $Lastvalue="";
  $LastArrayCount=0;
  asort($NewArray);
  $LastArray=array();
  foreach($NewArray as $value){
    if($value[0]!=$Lastvalue){
      array_push($LastArray,$n_array);
      $LastArrayCount++;
      $LastArray[$LastArrayCount][0]=$value[0];
      for($z=1;$z<$Xana;$z++){
        $LastArray[$LastArrayCount][$z]=$value[$z];
      }
      $Lastvalue=$value[0];
    }else{
      for($z=1;$z<$Xana;$z++){
        if(strlen($value[$z])>0)$LastArray[$LastArrayCount][$z]=$value[$z];
      }
    }
  }
  $LastArray[0]=$LastArray[1];
  $Message=$SAs[63];
 }
 else{//just some data
  $LastArray=array(["2022/03/04 05","18.10","","","",""],["2022/03/04 06","18.60","","","",""]);
  $Message=$SAs[148];
 }

  $aktion=fopen($Csvdatei,"w");
  foreach($LastArray as $val){//make csv-string
  $Output=$val[0].":00,";
  $HelpDate=str_replace("/","-",$Output);
  for($iV=1;$iV <($Xana);$iV++){
    $Output.=$val[$iV].",";
  }
  $Output.=",,,".strtotime($HelpDate)."\r\n";
  fputs($aktion,$Output);
  }
  fclose($aktion);
  if($Csvdatei=="beelogger.csv")CSVbuilder();
}//MultiType -1
else{
//CSVbuilder();
}

if(!file_exists($Csvdatei)){//no csv?
 foreach(glob("*.csv")as $filename){
  if(str_contains($filename,"notes"))continue;
  if(file_exists($filename)){
    $Csvdatei=$filename;
  break;
  }
 }
}
if(file_exists($Csvdatei)){//Letzte Werte aus .csv auslesen
  $array=file($Csvdatei);
  if(sizeof($array)>0){
  $ErsteZeile=trim($array[0]);
  $x=explode(",",$ErsteZeile);
  $CsvSpaltenAnzahl=sizeof($x);
  $ErstesDatum=$x[$CsvSpaltenAnzahl-1];
  if($CsvSpaltenAnzahl==0)$CsvSpaltenAnzahl=$AnzahlSensoren+4;//falls keine Daten
  $HelpAnzahlSensoren=$AnzahlSensoren;
  $HelpArraySensoren=$Sensoren;
  if($x[$CsvSpaltenAnzahl-2]=="" AND $x[$CsvSpaltenAnzahl-3]=="")$Abzug=4;//neue Datei
  else $Abzug=2;//alte Datei ohne 2 Leerstellen vorm Datum
  if($AnzahlSensoren<($CsvSpaltenAnzahl-$Abzug)){
    for($i=$AnzahlSensoren;$i<=($CsvSpaltenAnzahl-($Abzug+1));$i++){//es werden Quasi-Sensoren erzeugt
      $HelpArraySensoren[$i*5]=$i+1;
      $HelpArraySensoren[$i*5+1]="black";
      $HelpArraySensoren[$i*5+2]="false";
      $HelpArraySensoren[$i*5+3]="'y'";
      $HelpArraySensoren[$i*5+4]="[ ]";
      array_push($UseIcon);$UseIcon[$i]="false";
    }
    $HelpAnzahlSensoren=(sizeof($HelpArraySensoren)/5);
  }
  }//array size
}//ifcsv-file_exists..
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="../dygraph.js" charset="UTF-8"></script>
<link rel="stylesheet" href="../dygraph_beelogger.css">
<link rel="shortcut icon" href="../beelogger_icons/favicon-32x32.png" />
<style type="text/css">
body { font-family: Arial, Helvetica, sans-serif; color: black; background-color: ivory}

textarea { font-size: 14px; }
#Titelbox {
  background-color:transparent;
  position:relative;
  top:0px;
  left:10px;
  font-size: 32px;
  width:95%;
  -moz-text-size-adjust: none;
}
#Auswahlbuttons {
  background-color:transparent;
  position:relative;
  top:0px;
  left:10px;
  font-size: 14px;
  width:95%;
  -moz-text-size-adjust: none;
}
#Message_box {
  background-color:transparent;
  position:relative;
  top:7px;
  left:10px;
  width:95%;
  padding:5px;
  background:#ffff;
  border:1px solid black;
  border-radius:5px;
  font-family:Arial,'Times New Roman',serif;
  font-size:14px;
  -moz-text-size-adjust: none;
}

#Sensorbuttons {
background-color:transparent;
position:relative;
top:5px;
left:5px;
height:30px;
width:95%;
font-size:14px;
display:-webkit-flex; /* Safari */
-webkit-flex-wrap: wrap; /* Safari 6.1+ */
display:flex;
flex-wrap:wrap;
}

<?php
//SetButtons
for($s=0;$s<$HelpAnzahlSensoren;$s++){
  if($UseIcon[$s]=="true"){
  echo"\n".'#cbl'.$s.' {
  display: inline-block; margin: 10px; margin-bottom:-10px; margin-left:10px;
  background-image: url("../beelogger_icons/off_'.$Icon[$s].'");
  background-repeat:no-repeat;
  opacity:0.5;
  width:38px;
  border-bottom:1px solid '.$HelpArraySensoren[$s*5+1].';
  height:30px;';
  if(is_numeric($HelpArraySensoren[$s*5]))echo"display:none;";//Buttons die als Name nur Zahl haben
  echo'}'."\n";
  echo'#cb'.$s.':checked + #cbl'.$s.' {
     background-image: url("../beelogger_icons/off_'.$Icon[$s].'");
     opacity:1;
     height:30px;
     border-bottom:7px solid '.$HelpArraySensoren[$s*5+1].';';
     if(is_numeric($HelpArraySensoren[$s*5]))echo"display:none;";//Buttons die als Name nur Zahl haben
   echo'}'."\n";
   echo'#cb'.$s.' {display:none;}';
  }//if($UseIcon[$s]=="true")
  else{
  $SchriftWeite=round(9.3*strlen($HelpArraySensoren[$s*5]));
  if(strlen($HelpArraySensoren[$s*5])<7)$SchriftWeite+=15;//Kurze Namen brauchen etwas größere Buttons

//Sensor Buttons
  echo"\n#ck-button".$s." {
  position:relative;top:10px;left:5px;
  height:22px;margin:6px;background-color:#EFEFEF;
  overflow:hidden;float:left;
  -webkit-transition-duration:400ms; /* Safari */
  transition-duration:400ms;";

  if(is_numeric($HelpArraySensoren[$s*5]))echo"display:none;";//Buttons die als Name nur Zahl haben
  echo"\n  border-radius:15px;border:1px solid ".$HelpArraySensoren[$s*5+1].";";
  echo"\n}\n";

echo"#ck-button".$s." input:not(:checked)+ :hover {
    background-color:lightgrey;
    color:".$HelpArraySensoren[$s*5+1].";
}\n";
echo"#ck-button".$s." input:checked + :hover {
    background-color:".$HelpArraySensoren[$s*5+1].";
    color:lightgrey;
}\n";
echo"#ck-button".$s." label {
    color:".$HelpArraySensoren[$s*5+1].";
    float:left;
    width:".$SchriftWeite."px;
}
#ck-button".$s." label span {
    text-align:center;
    padding:3px 0px;
    display:block;
}
#ck-button".$s." label input {
    position:absolute;
    top:-20px;
}
  #ck-button".$s." input:checked + span {
    background-color:".$HelpArraySensoren[$s*5+1].";
    color:white;
}";
  }//else
}//for($s=0;$s < $HelpAnzahlSensoren;$s++)

//Button Tageswerte
echo"
#ck-button".$s." {
position:relative;top:10px;left:5px;
height:22px;margin:6px;background-color:#EFEFEF;
overflow:hidden;float:left;
-webkit-transition-duration:400ms; /* Safari */
transition-duration:400ms;border-radius:4px;
border:1px solid #000000;";
 if(($MultiType==-1)OR($bhome > 0)) echo"\ndisplay:none;";//TW
echo"}";
echo"
#ck-button".$s." input:not(:checked)+ :hover {
  background-color:grey;
  color:black;
}";
echo"
#ck-button".$s." input:checked + :hover {
  background-color:grey;
  color:white;
}";

$SchriftWeite=round(9.1*strlen($SAs[64]));
echo"
#ck-button".$s." label {
  color:black;
  float:left;
  width:".$SchriftWeite."px;
}
#ck-button".$s." label span {
  text-align:center;
  padding:3px 0px;
  display:block;
}
#ck-button".$s." label input {
  position:absolute;
  top:-20px;
}
#ck-button".$s." input:checked + span {
  background-color:black;
  color:#fff;";
echo"}";

echo'
 #Nummer_Box {
  background-color:transparent;
  position:relative;
  top:16px;
  left:6px;';
 if(($MultiType==-1)OR($bhome > 0)) echo"\ndisplay:none;";//TW
  echo'}';

echo'
 #sum_grenze {
  background-color:transparent;
  position:relative;
  top:18px;
  left:5px;';
  if(($MultiType==-1)OR($MultiType>1))echo"\ndisplay:none;";
  echo'}';

$s++;
//Button Summe
echo"
#ck-button".$s." {
position:relative;top:10px;left:5px;
height:22px;margin:6px;background-color:#EFEFEF;
overflow:hidden;float:left;
-webkit-transition-duration:400ms; /* Safari */
transition-duration:400ms;border-radius:4px;
border:1px solid orange;";
if(($MultiType==-1)OR($MultiType>1))echo"\ndisplay:none;";
echo"}";

echo"
#ck-button".$s." input:not(:checked)+ :hover {
  background-color:#FFC31E;/*lightorange;*/
  color:grey;
}";
echo"
#ck-button".$s." input:checked + :hover {
  background:#FFC31E;/*lightorange;*/
  color:grey;
}";
echo"
#ck-button".$s." label {
  color:orange;
  float:left;
  width:58px;
}
#ck-button".$s." label span {
  text-align:center;
  padding:3px 0px;
  display:block;
}
#ck-button".$s." label input {
  position:absolute;
  top:-20px;
}
#ck-button".$s." input:checked + span {
  background-color:orange;
  color:#fff;";
echo"}";


if($BeutenLeergewicht!="")if(((int)$BeutenLeergewicht)>0){
echo"
#ck-button99 {
position:relative;top:10px;left:5px;
height:21px;margin:6px;background-color:WHITE;
overflow:hidden;float:left;
-webkit-transition-duration:400ms; /* Safari */
transition-duration:400ms;border-radius:15px;
z-index:-1;border-radius:4px;border:1px solid grey;}

#ck-button99 label {
  color:grey;
  float:left;";
  $SchriftWeite=round(11*strlen($SAs[67]));
  if((int)$Honigraeume[0]<1 AND(int)$Honigraeume[2]<1 AND(int)$BeutenUtils[0][0]<1 AND(int)$BeutenUtils[1][0]<1 AND(int)$BeutenUtils[2][0]<1)echo"  width:".($SchriftWeite+50)."px;";
  else echo"  width:".($SchriftWeite+115+(((int)$Honigraeume[0]+(int)$Honigraeume[2]+(int)$BeutenUtils[0][0]+(int)$BeutenUtils[1][0]+(int)$BeutenUtils[2][0])*20))."px;";
echo" }";

echo"
#ck-button99 label span {
  text-align:center;
  padding:2px 0px;
  display:block;
}
#ck-button99 label input {
  position:absolute;
  top:-20px;
}
#ck-button99 input:checked + span {
  background-color:white;
  color:#fff;
}";
}

//Dummy für Abstand
echo"
#ck-button999 {
position:relative;top:10px;left:5px;
height:21px;margin:6px;background-color:WHITE;
overflow:hidden;float:left;
-webkit-transition-duration: 400ms; /* Safari */
transition-duration: 400ms;
z-index:-1;border-radius:2px;border:0px solid grey;}

#ck-button999 label {
  color:grey;
  float:left;
  width:1px;
}
#ck-button999 label span {
  text-align: center;
  padding:0px 0px;
  display:none;
}
#ck-button999 label input {
  position:absolute;
  top:-20px;
}
#ck-button999 input:checked + span {
  background-color: white;
  color:#fff;
}";
?>

#AlleAuswahlbutton {
  position:relative;
  top:10px;
  left:5px;
  height:22px;
  margin:6px;
  background-color:#EFEFEF;
  border-radius:4px;
  border:1px solid #D0D0D0;
  overflow:auto;
  float:left;
}
#AlleAuswahlbutton :hover {background:#fff;}
#AlleAuswahlbutton label{color:black;float:left;width:100%;}
#AlleAuswahlbutton label span {text-align:center;padding:2px 0px;display:block;}
#AlleAuswahlbutton label input {text-align:center; position:absolute;top:-20px;}

#NavigationAnzeige {
  background-color:transparent;
  position:relative;
  top:17px;
  left:4px;
  height:21px;
  display:flex;
  flex-wrap:wrap;
  display:-webkit-flex; /* Safari */
  -webkit-flex-wrap: wrap; /* Safari 6.1+ */
  -moz-text-size-adjust: none;
}
#BL_grafik {
  position:relative;
  top:20px;
  left:10px;
}
#BL_grafik .dygraph-ylabel {color:black;}
#BL_grafik .dygraph-axis-label-y1 {color:black;}
#BL_grafik .dygraph-y2label {color:black;}
#BL_grafik .dygraph-axis-label-y2 {color:black;}
</style>
<title>beelogger.de - Datenlogger für Imker</title>
</head>

<?php
//Ermittlung $Bienenvolkbezeichnung
if($Bienenvolkbezeichnung=="auto"){//nur wenn oben kein eigener Name angegeben wurde
  $pfad=getcwd();//kompletter Verzeichnispfad
  $verz=strrchr($pfad, "/");//Verzeichnisname einschließlich des / zu Beginn
  $BienenvolkbezeichnungToEcho=str_replace("/","",$verz);// / noch entfernt
}
else $BienenvolkbezeichnungToEcho=html_entity_decode($Bienenvolkbezeichnung);
$BienenvolkbezeichnungToEcho=html_entity_decode($BienenvolkbezeichnungToEcho);

$w_msg=1;//no data
$col_red=0;
if(file_exists('week.csv')){//array füllen mit aktueller csv-datei
  $input='week.csv';
  $array=file($input);
  $i=sizeof($array);
  if($i>1){
   $w_msg=0;
   while($i--){
    $what=trim($array[$i]);
    $x=explode(",",$what);
    $LetzteZeile=$x;//merke Letzte Zeile für beelogger_ini.php-Ansicht
    $s=sizeof($x);//hier wird die Anzahl von Spalten pro Zeile im beelogger.csv ermittelt
    if($x[$s-1]!=''){//um hier die letzte Spalte=Zeitstempel abzufragen
     $AktualisierungsStamp=$x[$s-1];
     $AktualisierungStampMessage=$AktualisierungsStamp;
   if($AktualisierungsStamp<(time()-86400*2))$col_red=1;
     if(($MultiType==0)OR($MultiType==1)){
      $LetztesLicht=$x[5];//wird gebraucht um das Sendeintervall zu ermitteln
      $LetztesVBatt=$x[7];//wird gebraucht um den Akkuzustand anzuzeigen
      $LetztesVSolar=$x[8];//wird gebraucht um das Sendeintervall zu ermitteln
     }
     elseif($MultiType>1){
      $LetztesLicht=$x[5];//wird gebraucht um das Sendeintervall zu ermitteln
      $LetztesVBatt=$x[(9+($MultiType-1)*3)];//wird gebraucht um das Sendeintervall zu ermitteln
      $LetztesVSolar=$x[(10+($MultiType-1)*3)];//wird gebraucht um das Sendeintervall zu ermitteln
     }
     $LetztesGewicht=$x[6];
     break;
    }
   }
   $NummerLetzteZeile=$i;
   $ZeitFuerNeueNote=$AktualisierungsStamp;//Hilfe für neue Note bei neuer Zeitintervalleingabe
   $Aktualisierung=date("d.m.Y H:i:s",$AktualisierungsStamp);
  }
}
if($w_msg){//no data
  if($MultiType==-1)$Message=$SAs[148];
  else $Message=$SAs[24];
  $Aktualisierung="-<br>";
}

//NotesDatei erzeugen, falls noch nicht geschehen
if(!file_exists("notes.csv")){$fp=fOpen("notes.csv","w");fclose($fp);}
function assignNotesfile($noteAlt){
  global $NotesDateiName,$Csvdatei;
  $NotesDateiName="notes".$Csvdatei;//notesArchiv
  if($noteAlt)if(!file_exists($NotesDateiName))$NotesDateiName="notes_old.csv";
  if(!file_exists($NotesDateiName))$NotesDateiName="notes.csv";
  if(($Csvdatei=="beelogger.csv")OR($Csvdatei=="month.csv")OR($Csvdatei=="week.csv")){
    $NotesDateiName="notes.csv";
  }
}


echo'<body><div id="Titelbox">';

echo"\n<table width=95%>";
  echo"\n<td>";
  echo"\n<table>";
  echo'<td><style="font-size:50px"><font color=#088A08>';
  echo'&nbsp;<b>'.$BienenvolkbezeichnungToEcho.'&nbsp;</b></style></td>';
  if($show_version)echo"\n".'<td><span style="font-size:12px">&nbsp;&nbsp;'.$Softwareversion.'</span></td>';

  if($KoeAnzeige!='' AND $KoeAnzeige!="deaktiviert"){
    if($KoeKaefig=="aktiviert")$KoeAnzeige=str_replace("zeichnet.png","kaefigt.png",$KoeAnzeige);
    echo"<td><img src='../beelogger_icons/".$KoeAnzeige."' width='30' height='30' style='margin-bottom:-5px'; title='".$KoeInfo."'></td>";
  }
  echo"\n</table>\n</td>";
  echo"\n<td align=right>\n<table><tr><td align=center>";

  $MapStatus="";
  
  if($WechselMapName=="")$MapNameNow="beelogger_map.php";
  else $MapNameNow=$WechselMapName;
  if(file_exists($MapNameNow)){
    include($MapNameNow);
    $MultiMapper=FALSE;
    if($MapNameNow!="beelogger_map.php"){//falls Unterbeelogger eines Multi
      if($beeloggerMapWaage > 0)$MultiMapper=TRUE;
    }
    if($MultiType>=1)$MultiMapper=TRUE;
//MAP2
    if(($beeloggerMap2Status=="[UPDATED]" OR $beeloggerMap2Status=="[CHANGED]")AND $MultiMapper==TRUE){//nochmal gegenchecken
        $ch=curl_init("https://map2.beelogger.de/log.php?mapid=".$beeloggerMap2ID."&beeid=".$beeloggerMap2BeeID."&do=372664");//cURL ínitialisieren
        curl_setopt($ch, CURLOPT_HEADER, 0);//Header soll nicht in Ausgabe enthalten sein
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $ServerAntwort=curl_exec($ch);//Ausführen
        curl_close($ch);//Objekt schließen und Ressourcen freigeben
        if($ServerAntwort=="[ONLINE]"){
          echo"\n<a href='https://beelogger.de/?page_id=196999' target='_blank'><img src='../beelogger_icons/map_aktiv.png' width='70' height='30' style='margin-bottom:-9px'; title='beeloggerMap: ".$ServerAntwort;
          $MapStatus=$ServerAntwort;
          echo"'></a>";
        }else{
          echo"\n<a href='https://beelogger.de/?page_id=196999' target='_blank'><img src='../beelogger_icons/map_inaktiv.png' width='70' height='30' style='margin-bottom:-9px'; title='beeloggerMap2 ".$SAs[33]."'></a>";
          $MapStatus=$SAs[33];
        }
      }
      elseif($MultiMapper==TRUE OR $beeloggerMap2=="deaktiviert"){
        echo"\n<a href='https://beelogger.de/?page_id=196999' target='_blank'><img src='../beelogger_icons/map_inaktiv.png' width='70' height='30' style='margin-bottom:-9px'; title='beeloggerMap2 ".$SAs[33]."'></a>";
        $MapStatus=$SAs[33];
      }
  }//if(file_exists($MapNameNow))

  echo"</td></tr>";

$TriggerAktiv=false;
if($MultiType>0){
  if($WechselIniName!="")include($WechselIniName);
  for($i=1;$i<5;$i++){
    if($TriggerAlarmArray[$i][1]=="aktiviert" OR $TriggerAlarmArray[$i][2]=="aktiviert" OR $TriggerAlarmArray[$i][7]=="aktiviert"){
      $TriggerAktiv=true;break;
      }
    }
}

//read notes file
$trigger_in_note=0;
$notes_i=0;
$narray=array();
assignNotesfile(1);
if(file_exists($NotesDateiName)){
  $narrayROH=file($NotesDateiName);
  for($i=0;$i < sizeof($narrayROH);$i++){
    $AnmerkungZeile=trim($narrayROH[$i]);
    $x=explode( ",",$AnmerkungZeile);
    $AnmerkungSpaltenAnzahl=sizeof($x);
    $AnmerkungDatum=$x[$AnmerkungSpaltenAnzahl-1];
    if($AnmerkungDatum > $ErstesDatum){
      array_push($narray,$narrayROH[$i]);
      $notes_i++;
	  if(strstr($narrayROH[$i],"Triggeralarm"))$trigger_in_note=1;//Iconname Triggeralarm
    }
  }
}//iffile exists

if($TriggerAktiv){
   echo'<tr align=center><td>';
   for($i=0;$i < 5;$i++){
    if($TriggerAlarmArray[$i][1]=="aktiviert" OR $TriggerAlarmArray[$i][2]=="aktiviert" OR $TriggerAlarmArray[$i][7]=="aktiviert"){ echo'<img src="../beelogger_icons/n_Triggeralarm.png" title="Triggeralarm'.$i.': '.$Sensoren[(5*$TriggerAlarmArray[$i][3])].' '.$TriggerAlarmArray[$i][4].' '.$TriggerAlarmArray[$i][5].'" height=25 style=';
	if($TriggerAktiv && $trigger_in_note)echo'"margin-top:0px; background-color:orange;">';//trigger in data
	else echo'"margin-top:0px;">';
	}
   }
   echo"</tr>";
}
else echo"\n<td align=center style='font-size:12px'>".$MapStatus."</td>";
echo"\n</table>";

  include("beelogger_ini.php");//REINIT

 if(file_exists("beelogger_wetter.php"))include("beelogger_wetter.php");
 echo"\n</td></table>";
 echo"\n</div>";//Titelbox

//<!------- 3 Auswahlbuttons -------->
  echo '<div id="Auswahlbuttons">&nbsp;';
//Listbox beelogger
 $dir="../";
 $files=scandir($dir);
 natcasesort($files);

//Array angezeigter beelogger neu anordnen - nach Duo Triple(E), Quad(M), Hexa(N), Penta(R)und Sept
  $beeloggerArraySize=0;
  $Passwort_Show_Counter=0;
  $beeloggerArray=array(array());
//index in Array  $ind_Beelogger=0;  $ind_Bienenvolkbezeichnung=1;  $ind_Farbe=2;
  foreach($files as $file){
    if($file!="mobile" && $file!="beelogger_icons" && $file!="beelogger_sprachfiles" && $file!="." && $file!=".." && is_dir("../".$file)){
      $ininame="../".$file."/beelogger_ini.php";
      $showname="../".$file."/beelogger_sml.php";
      if(file_exists($ininame))include($ininame);

      $file=str_replace("T","E",$file);
      $file=str_replace("Q","M",$file);
      $file=str_replace("H","N",$file);
      $file=str_replace("P","R",$file);
      array_push($beeloggerArray);
      $beeloggerArray[$beeloggerArraySize][0]=$file;
      $beeloggerArray[$beeloggerArraySize][1]=strtolower($Bienenvolkbezeichnung);
      if($beeloggerArray[$beeloggerArraySize][1]=="auto")$beeloggerArray[$beeloggerArraySize][1]="zzz";
      $beeloggerArray[$beeloggerArraySize][2]="black";
      $Bienenvolkbezeichnung="zzz";//RESET
      $beeloggerArraySize++;
    }
  }
  $Newfiles=$beeloggerArray;
  for($bb=0;$bb<$beeloggerArraySize;$bb++){
    $f_name=$Newfiles[$bb][0];
    $f_name=str_replace("E","T",$f_name);
    $f_name=str_replace("M","Q",$f_name);
    $f_name=str_replace("N","H",$f_name);
    $f_name=str_replace("R","P",$f_name);
    $beeloggerArray[$bb][0]=$f_name;
  }

//Farben zuordnen
   $GruppenFarben=array("green","blue","orange","olive","deepskyblue","purple","lime","black","yellow","red");
   $FarbCounter=0;
   $MultiArray=array('Duo','Triple','Quad','Hexa','Penta','Sept');
    foreach($MultiArray as $EachMulti){
      for($i=0;$i<$beeloggerArraySize;$i++){
        if(strpos($beeloggerArray[$i][0],$EachMulti)!==FALSE){
          $EachMultiNumber=intval(str_replace($EachMulti,"",$beeloggerArray[$i][0]));
          $beeloggerArray[$i][2]=$GruppenFarben[$FarbCounter];
          for($j=0;$j < $beeloggerArraySize;$j++){
            if(strpos($beeloggerArray[$j][0],(substr($EachMulti,0,1).$EachMultiNumber))!==FALSE){
              $beeloggerArray[$j][2]=$GruppenFarben[$FarbCounter];
              }
            }
          $FarbCounter++;
          }
        }
      }
//Ende Farben Zuordnen

if($beeloggerNamenSortierung=="aktiviert"){//Info in general_ini
  foreach($beeloggerArray as $nr=> $inhalt){
    $ABienenvolkbezeichnung[$nr]=strtolower($inhalt[1]);
  }
  array_multisort($ABienenvolkbezeichnung,SORT_NATURAL,$beeloggerArray);
}
if($more_beelogger==0){echo"<b>&nbsp;&nbsp;&nbsp;</b>";
}else{
 echo"<b>". $SAs[35] .": </b>";
 echo"<select name='beelogger' onChange='document.location.href=this.value'".' title="'.$SAs[34].'">';
 echo"\n";

for($i=0;$i<$beeloggerArraySize;$i++){
  $filename="../".$beeloggerArray[$i][0]."/beelogger_sml.php";
  if(file_exists($filename)){
    $ininame="../".$beeloggerArray[$i][0]."/beelogger_ini.php";
    $csvname="../".$beeloggerArray[$i][0]."/beelogger.csv";
    if($beeloggerArray[$i][0]==basename(realpath('.')))echo'<option style="color:'.$beeloggerArray[$i][2].';" value='.$filename." selected>";
    else{
      if(file_exists($ininame)AND file_exists($csvname))echo'<option style="color:'.$beeloggerArray[$i][2].';" value='.$filename.">";
      else echo'<option style="color:gray;" value='.$filename.">";
    }
    $ShowFileName=str_replace("beelogger","",$beeloggerArray[$i][0]);
    $Bienenvolkbezeichnung="auto";//INIT
    if(file_exists($ininame))include($ininame);
    if($beeloggerNamenSortierung=="aktiviert"){
      if($Bienenvolkbezeichnung!="auto")echo html_entity_decode($Bienenvolkbezeichnung)." (".$ShowFileName.")";
      else echo $beeloggerArray[$i][0];
    echo"</option>"."\n";
    }
    else{
      if($Bienenvolkbezeichnung !="auto")echo $ShowFileName." (".html_entity_decode($Bienenvolkbezeichnung).")";
      else echo $beeloggerArray[$i][0];
      echo"</option>"."\n";
    }
  }//if(file_exists($filename))
}

echo"</select>"."\n";
}//more beelogger

$RollPeriod="1";//Default
$TageswertAnzeige='false';//Default
$AkkuLeerSchwelle=0;//RESET
$AkkuVollSchwelle=0;//RESET
$IntervallSendeSteuerung="";//RESET
include("beelogger_ini.php");//passende Werte zur Show wieder initialisieren

if($MultiType > 1){
 if($more_beelogger==1){
  echo"&nbsp;&nbsp;";
  echo'<input type="button" value="'.$MultiTypeName.' '.$ServerMultiNumber.'" style="background-color:#a9dfbf;" onclick="window.location.href=';
  echo "'../".$MultiTypeName.$ServerMultiNumber."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
  for($i=1;$i <=$MultiType;$i++){
    echo'<input type="button" style="background-color:hsl(360,0%,'.(100-$i*10).'%);" value="'.$MultiSign.$ServerMultiNumber."- ".$i.'" onclick="window.location.href=';
    echo"'../beelogger".$MultiSign.$ServerMultiNumber."_".$i."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
    echo"\n";
  }
 }
 else{
  echo"&nbsp;&nbsp;";
  echo'<input type="button" value="'.$MultiTypeName.' '.$ServerMultiNumber.'" style="background-color:#a9dfbf;" onclick="window.location.href=';
  echo "'../".$beelogger."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
 }
}
if($MultiType==1){
  echo"&nbsp;&nbsp;";
  echo'<input type="button" value=" beel. '.$ServerMultiNumber.' " style="background-color:#a9dfbf;" onclick="window.location.href=';
  echo "'../".$beelogger."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
}
if($MultiType==0){
 if($more_beelogger==1){
  echo"&nbsp;&nbsp;";
  $MultiBeeloggerNummer=intval(substr($beelogger,-1));
  echo'<input type="button" value="'.$MultiTypeName.' '.$ServerMultiNumber.'" style="background-color:hsl(360,0%,95%);" onclick="window.location.href=';
  echo "'../".$MultiTypeName.$ServerMultiNumber."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';

  for($i=1;$i <=$MultiTypeAnzahl;$i++){
    echo'<input type="button" style="background-color:';
    if($MultiBeeloggerNummer==$i)echo"#a9dfbf";
    else echo"hsl(360,0%,".(100-$i*10).'%)';
    echo';" value="'.$MultiSign.$ServerMultiNumber."- ".$i.'" onclick="window.location.href=';
    echo"'../beelogger".$MultiSign.$ServerMultiNumber."_".$i."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
    echo"\n";
  }
 }
 else{
  echo"&nbsp;&nbsp;";
  $MultiBeeloggerNummer=intval(substr($beelogger,-1));
  echo'<input type="button" value="'.$MultiSign.$ServerMultiNumber.'-'.$MultiBeeloggerNummer.'" style="background-color:#a9dfbf;" onclick="window.location.href=';
  echo "'../".$beelogger."/beelogger_sml.php'".'"/>&nbsp;&nbsp;';
 }
}
if($analyse==1){
  for($X=1;$X<4;$X++){
    if(file_exists("../Xanalyse".$X)AND $beelogger !="Xanalyse".$X)echo'&nbsp;&nbsp;<a href="../Xanalyse'.$X.'/beelogger_sml.php"><img src="../beelogger_icons/Xanalyse.png" title="Xanalyse'.$X.'" style="margin-bottom:-9px" height=30 width=18 ></a>&nbsp;';
  }
}
echo"\n";

if(($mobile_tools==1)||($mobile_tools==3)){
echo'&nbsp;&nbsp;<a href="../beelogger_mobileWatch.php?sml"><img src="../beelogger_icons/watch.png" title="mobileWatch" style="margin-bottom:-9px" height=30 width=18 ></a>&nbsp;';
echo"\n";
}
if(($mobile_tools==2)||($mobile_tools==3)){
if($MultiType==-1)$GoToBeelogger="beelogger1";
elseif($MultiType > 1)$GoToBeelogger="beelogger".$MultiTypeName[0].$ServerMultiNumber."_1";
else $GoToBeelogger=$beelogger;
if($mobileCheck_Show=="aktiviert"){
echo'&nbsp;&nbsp;<a href="../beelogger_mobileCheck.php?beelogger='.$GoToBeelogger.'"><img src="../beelogger_icons/mCheck.png" title="mobileCheck" style="margin-bottom:-9px" height=30 width=18 ></a>';
echo"\n";
}}
//Anmerkungen Button
//Konfiguration Button'

echo"&nbsp;".$SAs[45].":&nbsp;<select name='neustandardcsv' style='margin:3px' onChange='document.location.href=this.value'".' title="'.$SAs[46].'">';
echo"\n";
//Listbox csv-dateien
foreach(glob("*.csv")as $filename){
  if(str_contains($filename,"notes")) continue;
  if(str_contains($filename,"Archiv")) continue;
  if(file_exists($filename)){
    echo"<option value=beelogger_sml.php?csvdatei=".$filename;
    if($filename==$Csvdatei)echo" selected";
    echo">";
    if($filename=="week.csv")echo $SAs[47];
    elseif($filename=="month.csv")echo $SAs[48];
    elseif($filename=="beelogger.csv")echo $SAs[49];
    else echo substr($filename,0,-4);
    echo"</option>\n";
  }
}
echo"</select>\n";
echo"</div>\n";
//------- ^^^^^  3 Auswahlbuttons -------->
include("beelogger_ini.php");//REINIT !!!

if($MultiType!=-1){
 $IntervallHelp="";
  if($IntervallSendeSteuerung=="lichtgesteuert"){//lichtgesteuerte Sendeintervalle
    if((int)$LetztesLicht > 1)$SendeIntervall=$SommerSendeIntervallTag;//Sonnenlicht vorhanden
    else $SendeIntervall=$SommerSendeIntervallNacht;//nachts --> Sensorwert=0
    if($LetztesLicht=="")$IntervallHelp="zeitgesteuert";//Lichtsteuerung ohne Werte geht nicht
  }

  if($IntervallSendeSteuerung=="solarspannungsgesteuert"){//solarspannungsgesteuerte Sendeintervalle
    if((float)$LetztesVSolar > 0.3)$SendeIntervall=$SommerSendeIntervallTag;//Sonnenlicht vorhanden
    else $SendeIntervall=$SommerSendeIntervallNacht;//nachts --> Sensorwert=0
    if($LetztesVSolar=="")$IntervallHelp="zeitgesteuert";//Spannungsteuerung ohne Werte geht nicht
  }
  
  if($IntervallSendeSteuerung=="zeitgesteuert" OR $IntervallHelp=="zeitgesteuert"){//zeitgesteuerte Sendeintervalle
    if((intval(date("G",$AktualisierungsStamp))>=(int)$SommerTagZeit)AND(intval(date("G",$AktualisierungsStamp))<(int)$SommerNachtZeit))$SendeIntervall=$SommerSendeIntervallTag;//Berechnung(sehr angenähert)für den Sonnen-Auf und Untergang
    else $SendeIntervall=$SommerSendeIntervallNacht;
  }
  
  if($WinterBeginn !="deaktiviert"){
    if(fmod($SommerBeginn, 1)==0.5)$SommerBeginnInTagen=date("z",strtotime(round($SommerBeginn, 0, PHP_ROUND_HALF_DOWN)."/15"));
    else $SommerBeginnInTagen=date("z",strtotime(round($SommerBeginn)."/1"));
    if(fmod($WinterBeginn, 1)==0.5)$WinterBeginnInTagen=date("z",strtotime(round($WinterBeginn, 0, PHP_ROUND_HALF_DOWN)."/15"));
    else $WinterBeginnInTagen=date("z",strtotime(round($WinterBeginn)."/1"));
    if((date("z",$AktualisierungsStamp)< $SommerBeginnInTagen)OR(date("z",$AktualisierungsStamp)> $WinterBeginnInTagen))$SendeIntervall=$WinterSendeIntervall;
  }

//<!-------  Message_box -------->

//Messages erzeugen

 if($Message==""){
  $dtdt=$SAs[127];
  if($col_red)$dtdt .="Y";
  if($AktualisierungStampMessage > 1000)$Message=$SAs[51]." ".date($dtdt,$AktualisierungStampMessage)." ".$SAs[52]." ".date("H:i",$AktualisierungStampMessage).$SAs[53].". ";//nur wenn überhaupt schon Daten vorhanden
  else $Message=$SAs[54]."! ";

  $Mapname="";
  if($MultiType==2)$Mapname="../Duo".intval(substr($beelogger,3,1))."/beelogger_map.php";
  elseif($MultiType==3)$Mapname="../Triple".intval(substr($beelogger,6,1))."/beelogger_map.php";
  elseif($MultiType==4)$Mapname="../Quad".intval(substr($beelogger,4,1))."/beelogger_map.php";
  elseif($MultiType==5)$Mapname="../Penta".intval(substr($beelogger,5,1))."/beelogger_map.php";
  elseif($MultiType==6)$Mapname="../Hexa".intval(substr($beelogger,4,1))."/beelogger_map.php";
  if($Mapname!="")include($Mapname);

  if(strpos($beeloggerSketchID,"LORA"))$IntervallSendeSteuerung="LORA";
  if($MultiType==0)$IntervallSendeSteuerung="LORA";

  if($IntervallSendeSteuerung !="LORA"){
  $Message.=$SAs[55].": ".$SendeIntervall." ".$SAs[61];

  if($IntervallSendeSteuerung=="zeitgesteuert"){
    $Message.=" (";if(strpos($beeloggerSketchID,"EE")!==FALSE)$Message .="EE-"; $Message.=$SAs[56].")";
  }elseif($IntervallSendeSteuerung=="solarspannungsgesteuert")$Message .=" (".$SAs[57].")";
  elseif($IntervallSendeSteuerung=="lichtgesteuert")$Message .=" (".$SAs[58].")";
  else {$Message.=" (".$SAs[59].")";}
  $Message.=" / ".$SAs[62]." ";

$MessageTime=23;
  if($EESendeIntervall=="A" AND $SendeIntervall<30)$MessageTime=SendeZeit(1);
  if($EESendeIntervall=="A" AND $SendeIntervall>=30)$MessageTime=SendeZeit(2);
  if($EESendeIntervall=="B" AND $SendeIntervall<30)$MessageTime=SendeZeit(2);
  if($EESendeIntervall=="B" AND $SendeIntervall>=30)$MessageTime=SendeZeit(4);
  if($EESendeIntervall=="C" AND $SendeIntervall<30)$MessageTime=SendeZeit(4);
  if($EESendeIntervall=="C" AND $SendeIntervall>=30)$MessageTime=SendeZeit(8);
  if($EESendeIntervall=="D" AND $SendeIntervall<30)$MessageTime=SendeZeit(8);
  if($EESendeIntervall=="D" AND $SendeIntervall>=30)$MessageTime=SendeZeit(16);

  if(date("H",$AktualisierungStampMessage)<6 AND date("G",$MessageTime)>=6){//Achtung: selbständige Sonderaussendung ab sechster Stunde
  $StartZeit=$AktualisierungStampMessage;
  $Endzeit=date("H",$StartZeit);
  while($Endzeit<6){
    $StartZeit+=$SendeIntervall*60;
    $Endzeit=date("H",$StartZeit);
  }
  $MessageTime=date("G:i",($StartZeit+$SendeIntervall*60));
  }
  if($MessageTime !=23)$Message .=$MessageTime."&nbsp;-&nbsp;".($MessageTime+1)." ".$SAs[53];
  else $Message .="23 - 0".$SAs[53];
    }//!=LORA
 }//$Message==""
}//MultiType

function SendeZeit($Zeitraum){//Zeitraum in Stunden
  global $AktualisierungStampMessage,$SendeIntervall;
  if($Zeitraum==24)return"5:".date("i",$AktualisierungStampMessage);//immer um 5 Uhr
  $JetztStunde=date("H",$AktualisierungStampMessage);
  $StartStunde=$JetztStunde;
  $DannStunde=$JetztStunde+$Zeitraum;
  $LaufStamp=$AktualisierungStampMessage;
  while($JetztStunde<$DannStunde){
    $LaufStamp+=$SendeIntervall*60;
    $JetztStunde=date("H",$LaufStamp);
    if($JetztStunde<$StartStunde)$JetztStunde +=24;//24 Stundenumsprung
  }
  return date("G",$LaufStamp);
}    
echo '<div id="Message_box" style="background-color:';
if($col_red){echo'#e7846f;"';}
else echo'ivory;"';
echo "><span>".$Message."&nbsp;</span></div>\n";                      
//------ die Sensoren -------->
echo'<div id="Sensorbuttons">'."\n";
//Checkboxen für SensorKurven ---------------------
$Klammern=array("[", "]");

if($MultiType==-1)$GewichtsEinheit="kg";
else $GewichtsEinheit=str_replace($Klammern,"",$HelpArraySensoren[5*5+4]);

  $ux_s=sizeof($UseIcon);//fix old data
  $ux=$HelpAnzahlSensoren-$ux_s;
  if($ux>0){for($uxx=0;$uxx<$ux;$uxx++){array_push($UseIcon);$UseIcon[$ux_s+$uxx]="false";}}
for($s=0;$s<$HelpAnzahlSensoren;$s++){
  if($HelpArraySensoren[5*$s]=="" AND $s<$HelpAnzahlSensoren){//falls Sensor keinen Namen hat
  echo'<div id="ck-button'.$s.'"><label><input type="checkbox" id="cb'.$s.'" onClick="change(this)"><span></span></label></div>';
  }//if$HelpArraySensoren
  else{//Sensor ist definiert
  if($UseIcon[$s]=="true"){
    echo'<div id="ck-button'.$s.'"><input id="cb'.$s.'" type="checkbox" onClick="change(this)"><label id="cbl'.$s.'" for="cb'.$s.'"></label></div>';
  }else{
    echo'<div id="ck-button'.$s.'"><label><input type="checkbox" id="cb'.$s.'" onClick="change(this)"><span><b>'.$HelpArraySensoren[$s*5].'</b></span></label></div>';
  }
 }
 echo"\n";
}//for

  echo'<div id="ck-button'.$s.'"><label><input type="checkbox" id="cb'.$s.'" onClick="change(this)"><span><b>'.$SAs[64]."</b></span></label></div>\n";
  $s++;

//Value in der Box ist Sensor Nummer oder Wert in der Konfiguration
 echo"\n".'<div id="Nummer_Box" title="'.$SAs[64].' '.$SAs[87].'">'."\n";
 echo'<select id="tagwertbox" onchange="tageswertanwahl()">';
 if(($MultiType==0)||($MultiType==1)){
  $tagwert=false;

  for($t=0;$t < $HelpAnzahlSensoren;$t++){
    if(($t<count($TageswertOptionArray))&&($TageswertOptionArray[$t]=="true" AND $t !=5)){//count -> fix to small array
		echo"\n".'<option value="'.($t+1).'" style="color:'.$HelpArraySensoren[$t*5+1].';"';
		if(($tagwert==false)&&($t==$TageswertAnzeige))echo" selected";
		echo'>'.$HelpArraySensoren[$t*5].'</option>';
		$tagwert=true;
    }
  }
  if($TageswertOptionArray[5]=="true"){
   $tagwert=true;
   if($GewichtsEinheit=="g"){
    for($t=0;$t < 7;$t++){
       switch($t){
		case 0:echo"\n".'<option value="200"';if(!strcmp($TageswertAnzeige,"200"))echo" selected";echo'>200g</option>';break;
		case 1:echo"\n".'<option value="400"';if(!strcmp($TageswertAnzeige,"400"))echo" selected";echo'>400g</option>';break;
		case 2:echo"\n".'<option value="600"';if(!strcmp($TageswertAnzeige,"600"))echo" selected";echo'>600g</option>';break;
		case 3:echo"\n".'<option value="1000"';if(!strcmp($TageswertAnzeige,"1000"))echo" selected";echo'>1000g</option>';break;
		case 4:echo"\n".'<option value="2000"';if(!strcmp($TageswertAnzeige,"2000"))echo" selected";echo'>2000g</option>';break;
		case 5:echo"\n".'<option value="5000"';if(!strcmp($TageswertAnzeige,"5000"))echo" selected";echo'>5000g</option>';break;
		case 6:echo"\n".'<option value="10000"';if(!strcmp($TageswertAnzeige,"10000"))echo" selected";echo'>10000g</option>';break;
      }
     }
   }else{
    if($TageswertAnzeige=="true")$TageswertAnzeige="1.0";//JJ-Abwärtskomp
    for($t=0;$t < 7;$t++){
       switch($t){
		case 0:echo"\n".'<option value="0.2"';if(!strcmp($TageswertAnzeige,"0.2"))echo" selected";echo'>0.2 kg</option>';break;
		case 1:echo"\n".'<option value="0.4"';if(!strcmp($TageswertAnzeige,"0.4"))echo" selected";echo'>0.4 kg</option>';break;
		case 2:echo"\n".'<option value="0.6"';if(!strcmp($TageswertAnzeige,"0.6"))echo" selected";echo'>0.6 kg</option>';break;
		case 3:echo"\n".'<option value="1.0"';if(!strcmp($TageswertAnzeige,"1.0")OR $TageswertAnzeige=="false")echo" selected";echo'>1 kg</option>';break;
		case 4:echo"\n".'<option value="2.0"';if(!strcmp($TageswertAnzeige,"2.0"))echo" selected";echo'>2 kg</option>';break;
		case 5:echo"\n".'<option value="5.0"';if(!strcmp($TageswertAnzeige,"5.0"))echo" selected";echo'>5 kg</option>';break;
		case 6:echo"\n".'<option value="10.0"';if(!strcmp($TageswertAnzeige,"10.0"))echo" selected";echo'>10 kg</option>';break;
       }
      }
    }
  }
  if($tagwert==false){echo'<option value="1.0">1 kg</option>';}//default
  }//$MultiType
else{//TW grünland
  for($t=0;$t < $HelpAnzahlSensoren;$t++){
	if($MultiType==2)if($t==5 OR $t==6 OR $t==13 OR $t==14)continue;
	if($MultiType==3)if(($t>4 AND $t<8)OR($t>15 AND $t<19))continue;
	if($MultiType==4)if(($t>4 AND $t<9)OR($t>18 AND $t<23))continue;
	if($MultiType==5)if(($t>4 AND $t<10)OR $t>21 )continue;
	if($MultiType==6)if(($t>4 AND $t<11)OR $t>22 )continue;
    if(($t<count($TageswertOptionArray))&&($TageswertOptionArray[$t]=="true")){//count -> fix to small array
		echo"\n".'<option value="'.($t+1).'" style="color:'.$HelpArraySensoren[$t*5+1].';"';
		if($t==(int)$TageswertAnzeige)echo" selected";
		echo'>'.$HelpArraySensoren[$t*5].'</option>';
    }
  }
}
echo"\n</select>\n";
echo"</div>\n";

echo"\n".'<div id="ck-button'.$s.'"><label><input type="checkbox" id="cb'.$s.'" onClick="change(this)"><span><b>'.$SAs[65]."</b></span></label>";
echo"</div>"."\n";
  $s++;

if(($MultiType==0)OR($MultiType==1)){//nur für "normale" beelogger
  if($BeutenLeergewicht>0){
    echo'<div id="ck-button99"><label>';
    echo'<input type="checkbox" id="99"><span>';
    if((((int)$Honigraeume[0]+(int)$Honigraeume[2])>0)AND(strlen($LetztesGewicht)>0)){
      echo"<b style='vertical-align:top'> &nbsp;".$SAs[66].":&nbsp;";
      echo round((float)$LetztesGewicht-(float)$BeutenLeergewicht-((int)$Honigraeume[0]*(float)$Honigraeume[1])-((int)$Honigraeume[2]*(float)$Honigraeume[3])-((int)$BeutenUtils[0][0]*(float)$BeutenUtils[0][1])-((int)$BeutenUtils[1][0]*(float)$BeutenUtils[1][1])-((int)$BeutenUtils[2][0]*(float)$BeutenUtils[2][1]),2).$GewichtsEinheit."</b>";
      echo"&nbsp;<img src='../beelogger_icons/Beute.png' width='15' height='15' title='".$SAs[145]."=".$BeutenLeergewicht.$GewichtsEinheit."'>";
      for($i=0;$i<(int)$Honigraeume[0];$i++){
        if((float)$Honigraeume[1]>100.0)$ShowHonigraum=(float)$Honigraeume[1]/1000;//noch in Gramm
        else$ShowHonigraum=$Honigraeume[1];
        echo"&nbsp;<img src='../beelogger_icons/honigraum.png' width='15' height='".($ShowHonigraum*2)."' title='".$SAs[141]."=".$Honigraeume[1].$GewichtsEinheit."'>";
      }
      for($j=0;$j<(int)$Honigraeume[2];$j++){
        if((float)$Honigraeume[3]>100.0)$ShowHonigraum=(float)$Honigraeume[3]/1000;//noch in Gramm
        else $ShowHonigraum=$Honigraeume[3];
        echo"&nbsp;<img src='../beelogger_icons/honigraum.png' width='15' height='".($ShowHonigraum*2)."' title='".$SAs[141]."=".$Honigraeume[3].$GewichtsEinheit."'>";
      }
      if((int)$BeutenUtils[0][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Varroaeinschub.png' width='15' height='15' title='".$SAs[142]."=".$BeutenUtils[0][1].$GewichtsEinheit."'>";
      if((int)$BeutenUtils[1][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Absperrgitter.png' width='15' height='15' title='".$SAs[143]."=".$BeutenUtils[1][1].$GewichtsEinheit."'>";
      if((int)$BeutenUtils[2][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Futterzarge.png' width='15' height='15' title='".$SAs[144]."=".$BeutenUtils[2][1].$GewichtsEinheit."'>";
    }else{
      echo"<b style='vertical-align:top'>&nbsp;".$SAs[67].":";
      $G_korr=(float)$LetztesGewicht-(float)$BeutenLeergewicht-((int)$Honigraeume[0]*(float)$Honigraeume[1])-((int)$Honigraeume[2]*(float)$Honigraeume[3]);
      if((int)$BeutenUtils[0][0]==1)$G_korr=$G_korr-(float)$BeutenUtils[0][1];
      if((int)$BeutenUtils[1][0]==1)$G_korr=$G_korr-(float)$BeutenUtils[1][1];
      if((int)$BeutenUtils[2][0]==1)$G_korr=$G_korr-(float)$BeutenUtils[2][1];
      echo round($G_korr,1).$GewichtsEinheit."</b>";
      if((int)$BeutenUtils[0][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Varroaeinschub.png' width='15' height='15' title='".$SA[142]."=".$BeutenUtils[0][1].$GewichtsEinheit."'>";
      if((int)$BeutenUtils[1][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Absperrgitter.png' width='15' height='15' title='".$SA[143]."=".$BeutenUtils[1][1].$GewichtsEinheit."'>";
      if((int)$BeutenUtils[2][0]==1)echo"&nbsp;<img src='../beelogger_icons/n_Futterzarge.png' width='15' height='15' title='".$SA[144]."=".$BeutenUtils[2][1].$GewichtsEinheit."'>";
    }
    echo"</span></label></div>"."\n";
  }
}else{//Dummybutton um die Symbole auszurichten falls nur Symbole verwendet werden
    echo'<div id="ck-button999"><label>';
    echo'<input type="checkbox" id="999" style="display:none"';
    echo'<span></span></label></div>'."\n";
}
//<!-- ^^^^ die Sensoren -------->

echo'<div id="AlleAuswahlbutton"> <label><input type="checkbox" id="cb'.$s.'" onClick="change(this)"><span>&nbsp;'.$SAs[68].'&nbsp;</span></label></div>'."\n\n";

//!-- ^^^^ Navigation -------->
echo'<div id="NavigationAnzeige"><span title="'.$SAs[73].'">&nbsp;&nbsp;</span>'."\n";
  if($Csvdatei[0]=='w'){//week: 1h, 2h, 4h, 8h, 24h, 1/2w,
  echo'<input id="nav1"; type="button" style="background-color:#FAFAFA;"value="1 h" onclick="zoom(86400/24);">';
  echo'<input id="nav2"; type="button" style="background-color:#F2F2F2;"value="2 h" onclick="zoom(86400/12);">
  <input id="nav4"; type="button" style="background-color:#E6E6E6;"value="4 h" onclick="zoom(86400/6);">';
  echo'<input id="nav8"; type="button" style="background-color:#D8D8D8;"value="8 h" onclick="zoom(86400/3);">
  <input id="nav24"; type="button" style="background-color:#BDBDBD;"value="'.$SAs[69].'" onclick="zoom(86400);">';
  echo'<input id="nav3d"; type="button" style="background-color:#A4A4A4;"value="1/2 '.$SAs[70].'" onclick="zoom(3.5 * 86400);">';
  }else if($Csvdatei[0]=='m'){//month: 2h, 4h, 8h,24h, 7d, 14d
  echo'<input id="nav2"; type="button" style="background-color:#F2F2F2;" value="2 h" onclick="zoom(86400/12);">
  <input id="nav4"; type="button" style="background-color:#E6E6E6;" value="4 h" onclick="zoom(86400/6);">';
  echo'<input id="nav8"; type="button" style="background-color:#D8D8D8;" value="8 h" onclick="zoom(86400/3);">
  <input id="nav24"; type="button" style="background-color:#BDBDBD;" value="'.$SAs[69].'" onclick="zoom(86400);">';
  echo'<input id="nav7d"; type="button" style="background-color:#BDBDBD;"value="'.$SAs[70].'" onclick="zoom(7 * 86400);">';
  echo'<input id="nav14d"; type="button" style="background-color:#BDBDBD;"value="14 '.$SAs[71].'" onclick="zoom(14 * 86400);">';
  }else if($Csvdatei[0]=='b'){//gesamt: 2,4,8,24, week, 14 day, month, 3 month
  echo'<input id="nav2"; type="button" style="background-color:#F2F2F2;" value="2 h" onclick="zoom(86400/12);">
  <input id="nav4"; type="button" style="background-color:#E6E6E6;" value="4 h" onclick="zoom(86400/6);">';
  echo'<input id="nav8"; type="button" style="background-color:#D8D8D8;" value="8 h" onclick="zoom(86400/3);">
  <input id="nav24"; type="button" style="background-color:#BDBDBD;" value="'.$SAs[69].'" onclick="zoom(86400);">';
  echo'<input id="nav7d"; type="button" style="background-color:#BDBDBD;"value="'.$SAs[70].'" onclick="zoom(7 * 86400);">';
  echo'<input id="nav14d"; type="button" style="background-color:#BDBDBD;"value="14 '.$SAs[71].'" onclick="zoom(14 * 86400);">';
  echo'<input id="nav30d"; type="button" style="background-color:#848484;" value="'.$SAs[72].'" onclick="zoom(30 * 86400);">';
  echo'<input id="nav90d"; type="button" style="background-color:#848484;" value="3 '.$SAs[72].'" onclick="zoom(90 * 86400);">';
  }else if($Csvdatei[0]=='A'){//archiv:  8h, 24h , 7d, month, 3 month
  echo'<input id="nav8"; type="button" style="background-color:#D8D8D8;" value="8 h" onclick="zoom(86400/3);">
  <input id="nav24"; type="button" style="background-color:#BDBDBD;" value="'.$SAs[69].'" onclick="zoom(86400);">';
  echo'<input id="nav7d"; type="button" style="background-color:#BDBDBD;"value="'.$SAs[70].'" onclick="zoom(7 * 86400);">';
  echo'<input id="nav30d"; type="button" style="background-color:#848484;" value="'.$SAs[72].'" onclick="zoom(30 * 86400);">';
  echo'<input id="nav90d"; type="button" style="background-color:#848484;" value="3 '.$SAs[72].'" onclick="zoom(90 * 86400);">';
  }

echo'
  <input type="button" style="background-color:#6E6E6E;" value="Reset Zoom" onclick="reset_Zoom();">

  <span title="'.$SAs[73].':">&nbsp;&nbsp;Scroll:&nbsp;</span>
  <input type="button" style="background-color:#E6E6E6;" value="|<" onclick="panend(-1);" title="'.$SAs[74].'">
  <input type="button" style="background-color:#F2F2F2;" value="<<" onclick="pan(-1);">
  <input type="button" style="background-color:#FAFAFA;" value="<"  onclick="pan(-0.5);"  title="'.$SAs[75].'">
  <input type="button" style="background-color:#FAFAFA;" value=">"  onclick="pan(0.5);"   title="'.$SAs[76].'">
  <input type="button" style="background-color:#F2F2F2;" value=">>" onclick="pan(1);">
  <input type="button" style="background-color:#E6E6E6;" value=">|" onclick="panend(1);"  title="'.$SAs[77].'">&nbsp;&nbsp;
  <input id="123" type="button" value="'.$SAs[81].'" onclick="show_points()" title="'.$SAs[78].'">&nbsp;
  <input id="124" type="button" value="'.$SAs[82].'" onclick="set_legend()" title="'.$SAs[79].'">
  </div>';

if(file_exists($WechselIniName)){
if($WechselIniName !="")include($WechselIniName);}

if($LetztesVBatt > 3.0){
if($AkkuLeerSchwelle !=0 && $AkkuVollSchwelle !=0 && $LetztesVBatt !=""){
  echo'&nbsp;&nbsp;&nbsp;&nbsp;<img ';
  if($LetztesVBatt >=$AkkuVollSchwelle)echo'src="../beelogger_icons/Akku100.png"';
  elseif($LetztesVBatt < $AkkuLeerSchwelle)echo'src="../beelogger_icons/Akku25.png"';
  else echo'src="../beelogger_icons/Akku50.png"';
  echo' width="26" height="12" style="margin-top:22px" title="Bat.: '.$LetztesVBatt.'V">';
}
}
include("beelogger_ini.php");//REINIT
if($TageswertAchse=="")$TageswertAchse="y2";//Abwärtskomp

echo'<div id="BL_grafik"> </div>
</div>'."\n\n";//<!-- div Sensorbuttons -->

?>

<!-- Dygraphsbereich Start -->

<script type="text/javascript" charset="UTF-8">

//globale Variablen festlegen
var Werteloeschen=false;
var BL;         //die dygraph Objecte
var desired_range=null;//Anzeige/Zoom Bereich für Dygraph
var move=1;//Anzahl der Schritte zum desired_range begrenzen
var Ax2Rng=0;//Variable die anzeigt, wieviele Kurven angezeigt werden
var data_d=[];//neue Datenstruktur
var tag_setup=0;//Tageswerte berechnet ?
var summe_setup=0;//Summenwerte berechnet ?
var GTS=0;
var roll_period=<?php echo $RollPeriod; ?>;
var ann_count=0;
var LoeschenButton=0;//Zustand des LoeschenButtons 0=nicht gedrückt
var winH=window.innerHeight;//das Fenster an sich

<?php
$mobilePhone=0;//Mobilgeräte
if(preg_match("/(alcatel|amoi|android|avantgo|blackberry|benq|cell|cricket|docomo|elaine
|htc|iemobile|iphone|ipad|ipaq|ipod|j2me|java|midp|mini|mmp|mobi|motorola|nec-|nokia|palm|
panasonic|philips|phone|playbook|sagem|samsung|sharp|sie-|silk|smartphone|sony|symbian|t-mobile|telus
|up\.browser|up\.link|vodafone|wap|webos|wireless|xda|xoom|zte)/i", $_SERVER['HTTP_USER_AGENT']))
{ $mobilePhone=1;}
if($PunktAnzeige=="true")echo'var points_show=1;document.getElementById("123").style.backgroundColor="green";';
else  echo'var points_show=0;'; ?>

var d_names=[<?php echo'"'.$SAs[107].'","'.$SAs[108].'","'.$SAs[109].'","'.$SAs[110].'","'.$SAs[111].'","'.$SAs[112].'","'.$SAs[113].'"'; ?>];
var m_names=[<?php echo'"'.$SAs[114].'","'.$SAs[115].'","'.$SAs[116].'","'.$SAs[117].'","'.$SAs[118].'","'.$SAs[119].'","'.$SAs[120].'","'.$SAs[121].'","'.$SAs[122].'","'.$SAs[123].'","'.$SAs[124].'","'.$SAs[125].'"'; ?>];
var EinzelSensoren=[<?php for($e=0;$e < $HelpAnzahlSensoren;$e++)echo'"'.$HelpArraySensoren[$e*5].'",'; echo'"'.$SAs[147].'+","'.$SAs[147].'-","'.$SAs[65].'"';?>];
var EinzelEinheiten=[<?php for($e=0;$e < $HelpAnzahlSensoren;$e++)echo"'".$HelpArraySensoren[$e*5+4]."',"; echo"'[".$GewichtsEinheit."]','[".$GewichtsEinheit."]','[".$GewichtsEinheit."]']";?>;
const tws=[180,280,350,500,550,700,850,1000,1100,1200,1400,1550,1552];

try {<?php echo "window.top.document.title=' ".$BienenvolkbezeichnungToEcho."';";?>} catch(error){}//whatever
//my_onresize: resize Event Grafik an Fenster anpassen
function my_onresize(){
var rect=document.getElementById("BL_grafik").getBoundingClientRect();
var w=window.innerWidth-30;//das Fenster an sich
var h=window.innerHeight;
var hh=(h - rect.top - 40);
<?php if($mobilePhone==1)echo "if(w > h){ if((winH - h)>100)winH=h; hh=winH+50; };";?>
if(typeof BL==='undefined'){return;}
BL.resize(w,hh);
}






<?php
 if($Legende=="immer")echo'document.getElementById("124").style.backgroundColor="green";';
   else if($Legende=="folgend")echo'document.getElementById("124").style.backgroundColor="grey";';
     else echo'document.getElementById("124").style.backgroundColor="lightgrey";';
//Mouseover fuer Icon erzeugen//aktiviere Buttons
  for($i=0;$i < $HelpAnzahlSensoren;$i++){
	$use_i=$i;if($use_i>=count($UseIcon))$use_i=0;//fix to small array
    echo"\n".'document.getElementById("cb'.$i.'").checked='.$HelpArraySensoren[$i*5+2].';';
    if($UseIcon[$use_i]=="true")echo"\n".'document.getElementById("cbl'.$use_i.'").setAttribute("title", "'.html_entity_decode($HelpArraySensoren[$use_i*5]).'");';
  }

echo"\n";
$y_ticks=1;//data only on y-axes
for($i=0;$i<$HelpAnzahlSensoren;$i++){if(strpos($HelpArraySensoren[$i*5+3],"y2")>0)$y_ticks=0;}
?>

var BL=new Dygraph(document.getElementById("BL_grafik"),
  <?php
  if(file_exists($Csvdatei)){
    $array=file($Csvdatei);$i=sizeof($array);
    for($x=0;$x<$i;$x++){$what=trim($array[$x]);echo '"'.$what.'\n"';if($x<$i-1)echo '+';}
  }
  else echo'"2022-10-01,,,,,,,,,,,,,,,,\n" + "2022-10-06 10:00,30,,,,,,30,,30,,,,,,,\n"+ "2022-10-06 22:00,,20,,,,,,,,,,,,,,\n" + "2022-10-07 10:0,11,,,,,,11,,,20,,,,,,\n" + "2022-10-09,10,23,,,,,10,,,,,,,,,\n"+ "2022-10-11,10,23,10,,,,,,,,,,,,,\n" + "2022-10-13,17,17,,,,,,,,,,,,,,\n" + "2022-10-14,,,12,19,19,,,,,,19,,,,,\n" + "2022-10-16,,,,,23,,,,,,,,,,,\n" + "2022-10-18,,,16,11,23,,,,,,11,,,,,\n" + "2022-10-20,,,19,10,19,,,,,,10,,,,,\n" + "2022-10-22,,,,,,19,,19,,,,,,,,\n"+ "2022-10-24,,,,,,23,,13,,,,,,,,\n" + "2022-10-26,,,,16,,23,,11,,,16,,,,,\n" +"2022-10-28,,,,19,,19,,10,,,19,,30,,,\n" + "2022-11-02,,,,,,,,,,,,,,,,\n"'; ?>,
//Optionen
  {
  labels: ["Datum",<?php for($s=0;$s < $HelpAnzahlSensoren;$s++){ echo'"'.html_entity_decode($HelpArraySensoren[$s*5]).' '.html_entity_decode($HelpArraySensoren[$s*5+4]).'",'; }
  echo'"'.$SAs[147].'+","'.$SAs[147].'-","'.$SAs[65].'"],'."\n";?>
  colors: [<?php for($s=0;$s < $HelpAnzahlSensoren;$s++){ echo"'".$HelpArraySensoren[$s*5+1]."',"; }
  echo"'#00ff01','#ff0001','orange'],\n";?>
  visibility: [<?php for($s=0;$s < $HelpAnzahlSensoren;$s++){ echo $HelpArraySensoren[$s*5+2].","; }?>false,false,false],
  xlabel:'',ylabel:'',y2label:'',
  series :{
  <?php for($s=0;$s < $HelpAnzahlSensoren;$s++){
  echo'"'.html_entity_decode($HelpArraySensoren[$s*5]).' '.html_entity_decode($HelpArraySensoren[$s*5+4]).'": { axis: '.$HelpArraySensoren[$s*5+3].'';
  if(strlen($FillGraph[$s])>2){echo',drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:'.$FillGraph[$s];}
  echo' },'."\n";
  }
  echo '"'.$SAs[147].'+":{axis:"'.$TageswertAchse.'",drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.5},
  "'.$SAs[147].'-":{axis:"'.$TageswertAchse.'",drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.5},
  "'.$SAs[65].'":{axis:"'.$TageswertAchse.'",drawPoints:false,pointSize:1,fillGraph:true,fillAlpha:0.2}';?>
  },
  axes: {
  x:{axisLabelFormatter:function(d,gran){
    var curr_month=m_names[d.getMonth()];
    var curr_day=d_names[d.getDay()];
	if(gran < 20){
		var Hrs=d.getHours();
		var Minuten=d.getMinutes();
		var Mins=((Minuten<10)?"0"+Minuten:Minuten);
		var hr=1;
		if(Minuten==0){hr=Hrs%8;if(gran<18)hr=Hrs%4;if(gran<17)hr=Hrs%2;if(gran<16)hr=0;}
		if(gran<15)hr=Minuten%30;
    if(hr==0)return(curr_day+" "+d.getDate()+"<?php echo $SAs[128].'"+curr_month+"'.$SAs[128]?>"+" "+Hrs+":"+ Mins);
		else return(Hrs+":"+Mins);
	}
    return curr_day+" "+d.getDate()+"<?php echo $SAs[128]?>"+curr_month;
    },
    axisLabelWidth:160,pixelsPerLabel:110
  },
  y:{drawGrid:true,independentTicks:<?php if(($TwoGrid=='true')OR($y_ticks==1)){ echo"true";echo",gridLinePattern: [5,5]";}else echo"false";?>,axisLabelWidth:60,pixelsPerLabel:30,digitsAfterDecimal:2,
  axisLabelFormatter:function(x){if(Math.abs(x)<0.01)return "0-";if(Math.abs(x)<10.0)return(Math.round(x*100,2)/100);if(x>500)return(Math.round(x));return(Math.round(x*10,1)/10);}},
  y2:{drawGrid:true,independentTicks:true,axisLabelWidth:60,pixelsPerLabel:30,<?php if($RollPeriod !=1)echo"digitsAfterDecimal:2,"; else echo"digitsAfterDecimal:3";?>}},
  hideOverlayOnMouseOut: false,
  connectSeparatedPoints:true,strokeWidth:2,
  showRoller:true,rollPeriod:<?php echo $RollPeriod; ?>,
  drawPoints:<?php if($PunktAnzeige=="true")echo"true,"; else echo"false,"; ?>
  displayAnnotations:true,
  highlightSeriesOpts:{strokeWidth:2,strokeBorderWidth:1,highlightCircleSize:4},
  underlayCallback: function(canvas, area, g){
    if(typeof BL==='undefined'){return;}
	var ax=0;<?php if($TageswertAchse=="y2")echo "ax=1;";
     if($GTS_color=="true")
      echo " if((GTS==1)&&(g.toDomYCoord(tws[0],ax)> 0)){
     var ya,yb;
  	 canvas.font='14px Arial';
     for(var x=0; x<12; x++){
	  canvas.fillStyle=`#000000`;
      ya=g.toDomYCoord(tws[x],ax);if(ya<0)break;yb=g.toDomYCoord(tws[x+1],ax);if(yb<0)yb=0;yb=yb-ya;
      if(x==0){canvas.fillText('Weide',area.x,ya);canvas.fillStyle=`rgba(246,241,106, 0.4)`;}
      if(x==1){canvas.fillText('Schlehe',area.x,ya);canvas.fillStyle=`rgba(106,246,176, 0.4)`;}
      if(x==2){canvas.fillText('  ',area.x,ya);canvas.fillStyle=`rgba(106,246,176, 0.0)`;}
      if(x==3){canvas.fillText('Kirsche',area.x,ya);canvas.fillStyle=`rgba(246,115,106, 0.5)`;}
      if(x==4){canvas.fillText('Raps',area.x,ya);canvas.fillStyle=`rgba(240,241, 18, 0.7)`;}
      if(x==5){canvas.fillText('Löwenzahn',area.x,ya);canvas.fillStyle=`rgba(246,241,106, 0.7)`;}
      if(x==6){canvas.fillText('Robinie',area.x,ya);canvas.fillStyle=`rgba(106,246,176, 0.5)`;}
      if(x==7){canvas.fillText('Kastanie',area.x,ya);canvas.fillStyle=`rgba(128,  0,  0, 0.5)`;}
      if(x==8){canvas.fillText('Brombeere',area.x,ya);canvas.fillStyle=`rgba(111, 44,145, 0.5)`;}
      if(x==9){canvas.fillText('S.-Linde',area.x,ya);canvas.fillStyle=`rgba( 40,180, 99, 0.5)`;}
      if(x==10){canvas.fillText('W.-Linde',area.x,ya);canvas.fillStyle=`rgba( 40,180, 99, 0.7)`;}
      if(x==11){canvas.fillStyle=`rgba(243,156, 18, 0.2)`;}//orange
     canvas.fillRect(area.x, ya, area.w, yb);
     }
    }"?>
	var w=BL.xAxisRange();
	if((w[1]-w[0])> <?php $ww=$Underlay*86405000; echo $ww;?>){return;}
    var  lf,rg,tr,bl;
	var wx=w[0];
    <?php if($Underlay=="0")echo"return;";
    $rd=substr($Underlay_Col,0,2);
    $gn=substr($Underlay_Col,2,2);
    $bl=substr($Underlay_Col,4,2);
    echo 'canvas.fillStyle="rgba('.hexdec($rd).','.hexdec($gn).','.hexdec($bl).',0.5)";';
    $wtg=1000* 60*60*($Underlay_Ab - $Underlay_Mo);
    echo "var wtg=".$wtg.";";?>
	var datum=new Date();
	for(var i=0;i<BL.numRows();i++){datum.setTime(BL.rawData_[i][0]);if(datum.getHours()==<?php echo"$Underlay_Mo";?>){wx=BL.rawData_[i][0];break;}}
    for(;wx<w[1];wx+=86400000){
      bl=g.toDomCoords(wx,-20);tr=g.toDomCoords(wx+wtg,+20);lf=bl[0];rg=tr[0]-lf;
      canvas.fillRect(lf, area.y, rg, area.h);
    }
  },
  zoomCallback:function(a,b,c){if(!move){berechne_sum();}},
//aktion on click removed
  showRangeSelector:true,rangeSelectorHeight:30,width:200,height:250,
  labelsSeparateLines:true,labelsShowZeroValues:true,legendFormatter: mylefo,pointSize:3,
  <?php
  if($Legende=="immer")echo"legend:'always',";
  else if($Legende=="folgend")echo"legend:'follow',";
  else echo"legend:'never',"; 
 if($mobilePhone==0)echo"interactionModel: Dygraph.defaultInteractionModel";  
 else  {
   if($chartMobile==1)echo"interactionModel: Dygraph.defaultInteractionModel";
   if($chartMobile==2)echo"interactionModel: Dygraph.nonInteractiveModel";
   if($chartMobile==3)echo"interactionModel: Dygraph.nonInteractiveTouch";
   if($chartMobile==4)echo"interactionModel: Dygraph.nonInteractiveTouchOnly";
   if($chartMobile==5)echo"interactionModel: {}";
  }
  ?>
});//new Ende

//*****************
BL.ready(function(){
window.addEventListener("resize",my_onresize);
window.addEventListener("mouseup",my_mouseup);
window.addEventListener("dblclick",my_mouseup);
my_onresize();
//Datei notes.csv nach Anmerkungen durchsuchen und im Array für die Darstellung aufbereiten-----
<?php
$anmerkungen=array();$b=0;

$NotesDateiName="";
assignNotesfile(1);    
if(file_exists($NotesDateiName)){
  $narray=file($NotesDateiName);$ni=sizeof($narray);
  for($a=0;$a<$ni;$a++){
    $what=trim($narray[$a]);$x=explode(",",$what);$s=sizeof($x);
    if($s>1){
      $Sensorx=$x[0];
      $H_Sens=html_entity_decode($HelpArraySensoren[$Sensorx*5]).' '.html_entity_decode($HelpArraySensoren[$Sensorx*5+4]);
      if(is_numeric($Sensorx)){
        $Sensorx=$H_Sens;//html_entity_decode($HelpArraySensoren[$Sensorx*5]);
        $Anmerkung=html_entity_decode($x[$s-2]);$AktualisierungStamp=$x[$s-1];
        if($AktualisierungStamp>$ErstesDatum){
          $DatumAnmerkung=date("Y/m/d H:i:s",$AktualisierungStamp);//Zeitformat für Dygraph umgestellt !$
          array_push($anmerkungen,$Sensorx);//Sensor im Array merken
          $b++;array_push($anmerkungen,$DatumAnmerkung);//Datumsstempel im Array merken
          $b++;$y=explode("&",$Anmerkung);array_push($anmerkungen,$y[0]);//Anmerkung kurz im Array merken
          $b++;array_push($anmerkungen,$y[1]);//Anmerkung lang im Array merken
          $b++;
    }}}
  }//ende for-schleife
}//Ende Anmerkungen Array erstellen

echo"BL.setAnnotations([  ";
  for($w=0;$w < $b;$w++){
   echo"\n{"; echo"series: '".$anmerkungen[$w]."',";
   $w++;
   echo'xval: Date.parse(\''.$anmerkungen[$w].'\'),';
   $w++;
   if(!strpos($anmerkungen[$w],".png")===false)echo'icon: "'.$anmerkungen[$w].'", width: 30, height: 30';
   else echo'shortText: "'.$anmerkungen[$w].'", width: '.(6+8*strlen($anmerkungen[$w]));
   $w++; echo',text: "'.$anmerkungen[$w].'",';echo"},";
  }//for $w
echo"]);";
if($TageswertAnzeige!="false" &&($MultiType>=0)){
	echo"tageswertanwahl();\n";
	if($Summe !="false"){
		echo"setTimeout(function(){berechne_sum();},300);"."\n";
		$x=$HelpAnzahlSensoren+1;echo"document.getElementById('cb$x').checked=true;"."\n";
		$x++;echo"BL.setVisibility($x,true);"."\n";
	}
}
?>
var anns=BL.annotations();
ann_count=anns.length+1;
<?php echo "set_btn_and_curv(".$HelpAnzahlSensoren+2 .",1);";
if(isset($Zoom))if((int)$Zoom > 0)echo"zoom($Zoom * 3600);"."\n";?>
//setTimeout(function(){my_onresize();},50);
});//.ready ende

function my_mouseup(e){
if(Ax2Rng){let y=e.clientY;var rect=document.getElementById("BL_grafik").getBoundingClientRect();if(y>rect.top){Rng_Axes();}}}

function mylefo(data){//legend formatter
  if(data.x==null){return '<br>';}
  var d_a=new Date(data.x);var html=d_names[d_a.getDay()]+' '+data.xHTML;
  data.series.forEach(function(series){
    if(!series.isVisible)return;if(!series.yHTML)return;
    var labeledData=series.labelHTML+': '+ series.yHTML;
    if(series.isHighlighted){labeledData='<b>'+labeledData+'</b>';}
    html+='<br>'+series.dashHTML+' '+labeledData;
  });
return html;
}

//show_points: Datenpunkte zeigen
function show_points(){
if(points_show){BL.updateOptions({drawPoints:false});points_show=0;document.getElementById("123").style.backgroundColor="lightgrey";}
else{BL.updateOptions({drawPoints:true});points_show=1;document.getElementById("123").style.backgroundColor="green";}
}

//set_legend: Legende Varianten
<?php if($Legende=="immer")echo"var legend_state=1;";
    else if($Legende=="folgend")echo"var legend_state=2;";
        else echo"var legend_state=0;"; ?>
function set_legend(){
if(legend_state==2){document.getElementById("124").style.backgroundColor="lightgrey";BL.updateOptions({legend:'never'});legend_state=0;}
else if(legend_state==1){document.getElementById("124").style.backgroundColor="grey";BL.updateOptions({legend:'follow'});legend_state=2;}
else{document.getElementById("124").style.backgroundColor="green";BL.updateOptions({legend:'always'});legend_state=1;}
//animate();
}

//** Label fuer Achsen festlegen, valueRange
function setup_Axes(){
var y_lbl=0;y2_lbl=0;y_einh_lbl=0;y2_einh_lbl=0;
var curve_y=0,curve_y2=0,n;
var y_label="";
var y2_label="";
var y2Kurven=[<?php for($y=0;$y < $HelpAnzahlSensoren;$y++){ if(strpos($HelpArraySensoren[$y*5+3],"y2")> 0)echo"true,";else echo"false,";} if($TageswertAchse=="y2")echo"true,true,true";else echo"false,false,false";?>];//zeigt an welche Kurven auf y oder y2 achse liegen

var is_visible=BL.visibility();
for(var i=0;i<=<?php echo $HelpAnzahlSensoren+2; ?>;i++){
 if(is_visible[i]){
  if(y2Kurven[i]){curve_y2=i;n=y2_label.includes(EinzelEinheiten[i]);
	if(n==0){if(y2_label.length>1){y2_label+=(", ");}y2_label=y2_label+EinzelEinheiten[i];y2_einh_lbl++;
	}else y2_lbl++;;
  }else{curve_y=i;n=y_label.includes(EinzelEinheiten[i]);
	if(n==0){if(y_label.length>1){y_label+=(", ");}y_label=y_label+EinzelEinheiten[i];y_einh_lbl++;
	}else y_lbl++;
}}}

var ycol="black";
var y2col="black";
Ax2Rng=0;
BL.updateOptions({gridLineColor:"black"});
BL.updateOptions({axes:{y2:{gridLineColor:"black"}}});
if(y_einh_lbl==1){if(y_lbl)BL.updateOptions({ylabel:y_label});else BL.updateOptions({ylabel:EinzelSensoren[curve_y]+'  '+y_label});
}else if(y_einh_lbl>1){BL.updateOptions({ylabel:y_label});}
else if(y_einh_lbl==0){
    <?php if($mobilePhone==1)echo"ycol='ivory';BL.updateOptions({gridLineColor:'ivory'});";?>
	if(y2_einh_lbl==1){if(y2_label!=" "){<?php if($mobilePhone==0)echo'Ax2Rng=2;';?> BL.updateOptions({ylabel:'<?php echo $SAs[140]; ?>'+y2_label});}
    else{ BL.updateOptions({ylabel:" "});}}
	else{BL.updateOptions({ylabel:" "});BL.updateOptions({gridLineColor:"ivory"});ycol="ivory";}
}
//##############
if(y2_einh_lbl==1){if(y2_lbl)BL.updateOptions({y2label:y2_label});else BL.updateOptions({y2label:EinzelSensoren[curve_y2]+'  '+y2_label});
}else if(y2_einh_lbl>1){BL.updateOptions({y2label:y2_label});}
else if(y2_einh_lbl==0){
    <?php if($mobilePhone==1)echo"y2col='ivory';BL.updateOptions({axes:{y2:{gridLineColor:'ivory'}}});"?>
	if(y_einh_lbl==1){if(y_label!=" "){BL.updateOptions({y2label:'<?php echo $SAs[140]; ?>'+y_label});<?php if($mobilePhone==0)echo'Ax2Rng=1;';?>}
    else{ BL.updateOptions({y2label:" "});}}
	else{BL.updateOptions({y2label:" "});BL.updateOptions({axes:{y2:{gridLineColor:"ivory"}}});y2col="ivory";}
}
BL.updateOptions({axes:{y:{valueRange:[null,null]}}});BL.updateOptions({axes:{y2:{valueRange:[null,null]}}});
axCol(ycol,y2col);
Rng_Axes();
}//setup_axes

//valueRange fuer relative Achsen konfigurieren
function Rng_Axes(){
var rng_y;
var y_offset;
if(Ax2Rng==1){
  rng_y=BL.yAxisRange(0);
  y_offset=(rng_y[1]-rng_y[0])*0.1;if(Math.abs(rng_y[0])< 0.01)y_offset=0;rng_y[1]=rng_y[1]-rng_y[0]-y_offset;rng_y[0]=-y_offset;
  BL.updateOptions({axes:{y2:{valueRange:rng_y}}});
}else if(Ax2Rng==2){
  rng_y=BL.yAxisRange(1);
  y_offset=(rng_y[1]-rng_y[0])*0.1;if(Math.abs(rng_y[0])< 0.01){y_offset=0;}rng_y[1]=rng_y[1]-rng_y[0]-y_offset;rng_y[0]=-y_offset;
  BL.updateOptions({axes:{y:{valueRange:rng_y}}});
}
}//Rng_Axes

function axCol(ycol,y2col){
var sd=document.styleSheets[1].cssRules;
 for(const rule of sd){
  if(rule.type===CSSRule.STYLE_RULE){
	if(rule.selectorText==="#BL_grafik .dygraph-y2label"){ rule.style.color=y2col;}
	if(rule.selectorText==="#BL_grafik .dygraph-axis-label-y2"){rule.style.color=y2col;}
	if(rule.selectorText==="#BL_grafik .dygraph-ylabel"){rule.style.color=ycol;}
	if(rule.selectorText==="#BL_grafik .dygraph-axis-label-y1"){rule.style.color=ycol;}
 }}
}

//Kurve ein - ausblenden
function change(el){//handle event
  var btn_nmr=el.id;
  var box=parseInt(btn_nmr.substr(2,3));//id="cbxy"
  var check=el.checked;
  set_btn_and_curv(box,check);
}

//set buttons and curve
function set_btn_and_curv(box,check){
var e=document.getElementById("tagwertbox");
var Value;
var Nummer=6;
if(e.selectedIndex>=0){ 
 Value=e.options[e.selectedIndex].value; if(Value.length<=2){Nummer=parseFloat(Value);}
 if((Nummer==2)<?php if($MultiType <2)echo"||(Nummer==13)";elseif($MultiType >1)echo"||(Nummer==1)";?>){if(GTS==2)GTS=1;}
}
  var v;
  <?php echo"if((!check)||(box==".($HelpAnzahlSensoren+2).")){";//aktiver Button macht Kurve an
    for($i=0;$i<$HelpAnzahlSensoren;$i++){echo"\n".'  v=document.getElementById("cb'.$i.'").checked; BL.setVisibility('.$i.', v);';}//for
    echo"\n".'  v=document.getElementById("cb'.$HelpAnzahlSensoren.'").checked;';
    echo' BL.setVisibility('.$HelpAnzahlSensoren.',v); BL.setVisibility('.($HelpAnzahlSensoren+1).',v);';
echo"\n".'  v=document.getElementById("cb'.($HelpAnzahlSensoren+1).'").checked;BL.setVisibility('.($HelpAnzahlSensoren+2).',v);'."}\n";
?>
else {
  if(box < <?php echo $HelpAnzahlSensoren; ?>){
<?php for($i=0;$i <=$HelpAnzahlSensoren+2;$i++){echo'  BL.setVisibility('.$i.', false);'."\n";} ?>
    BL.setVisibility(box,true);
    if(GTS==1)GTS=2;
  }
}

if(e.selectedIndex>=0){ 
<?php if($MultiType>=0){?>
 Nummer--;
 var nmr="cb" + Nummer.toString();
 if(box==<?php echo $HelpAnzahlSensoren; ?>){//Tageswerte
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren; ?>]=EinzelEinheiten[Nummer];
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren+1; ?>]=EinzelEinheiten[Nummer];
  berechne_tag(Nummer+1);summe_setup=0;
  if(check){//Wert zur Tageskurve an
    document.getElementById(nmr).checked=true;//Button
    BL.setVisibility(Nummer,true);//Kurve
    BL.updateOptions({});
    document.getElementById('cb<?php echo $HelpAnzahlSensoren; ?>').checked=true;
    if(GTS==1){
      BL.setVisibility(<?php echo $HelpAnzahlSensoren+2; ?>,true);
      document.getElementById('cb<?php echo $HelpAnzahlSensoren+1; ?>').checked=true;
    }
  }else {
    BL.updateOptions({rollPeriod:roll_period});
  }
  BL.setVisibility(<?php echo $HelpAnzahlSensoren; ?>,check);//Tw+
  BL.setVisibility(<?php echo $HelpAnzahlSensoren+1; ?>,check);//Tw-
  if(document.getElementById('cb<?php echo $HelpAnzahlSensoren+1; ?>').checked){
    berechne_sum();summe_setup=1;
  }
 }else if(box==<?php echo $HelpAnzahlSensoren+1; ?>){//Button Summe
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren+2; ?>]=EinzelEinheiten[Nummer];
  if(summe_setup==0){berechne_tag(Nummer+1);berechne_sum();summe_setup=1;}
  if(check){//Wert zur Tageskurve an
    document.getElementById(nmr).checked=true;//Button
    BL.setVisibility(Nummer,true);//Kurve
    BL.updateOptions({});
  }
  BL.setVisibility(<?php echo $HelpAnzahlSensoren+2; ?>,check);//Summe
 }
<?php } ?>
}
setup_Axes();
}//function change

//Box Tageswertanwahl
function tageswertanwahl(){
 var e=document.getElementById("tagwertbox");
 if(e.selectedIndex >=0){
  var Value=e.options[e.selectedIndex].value;
  var Nummer=6;
  if(Value.length <=2 )Nummer=parseFloat(Value);
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren; ?>]=EinzelEinheiten[Nummer-1];
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren+1; ?>]=EinzelEinheiten[Nummer-1];
  EinzelEinheiten[<?php echo $HelpAnzahlSensoren+2; ?>]=EinzelEinheiten[Nummer-1];
  berechne_tag(Nummer);//force
  BL.updateOptions({});
  if(GTS==0){  
    summe_setup=0;//Summe
    BL.setVisibility(<?php echo $HelpAnzahlSensoren+2; ?>,false);
    document.getElementById('cb<?php echo $HelpAnzahlSensoren+1; ?>').checked=false;
  } 
  else {
    BL.setVisibility(<?php echo $HelpAnzahlSensoren+2; ?>,true);
    document.getElementById('cb<?php echo $HelpAnzahlSensoren+1; ?>').checked=true;
  }
  set_btn_and_curv(<?php echo $HelpAnzahlSensoren; ?>,true);//btn tageswert
  BL.updateOptions({});
 }
}

//Berechne Tageszunahme
function berechne_tag(Nummer){
if(typeof BL==='undefined'){return;}
if(typeof BL.rawData_==='undefined'){return;}
if(roll_period>8)BL.updateOptions({rollPeriod:8});
if(Nummer==0){if(tag_setup==1){return;}else {Nummer=6;}}
var k,i,hr_akt,tx_time;
var g_alt,g_neu;
var suche_tag=1,tageswert=0.0;
var old_d,cur_day,day,cur_month;
var df_k,diff_korr,g_korr;
var datum=new Date();
var boundary=BL.numRows();//maximale Anzahl Punkte
GTS=0;
data_d.length=0;
g_neu=BL.rawData_[0][Nummer];//Startwert Tageswert
if(g_neu===null){g_neu=0;}
datum.setTime(BL.rawData_[0][0]);
day=datum.getDate();
var x_ax=boundary-1;
if(Nummer==6){//Auswertung Gewicht
 var e=document.getElementById("tagwertbox");//hole Grenzwert
 var sum_grz=parseFloat(e.options[e.selectedIndex].value);
 for(i=0;i<boundary;i++){datum.setTime(BL.rawData_[i][0]);cur_day=datum.getDate();hr_akt=datum.getHours();
  if(suche_tag){suche_tag=0;k=i;if(k>0){k--;}diff_korr=0.0;g_korr=0.0;day=cur_day;
   while((day==cur_day)&&(k<x_ax)){
    if(BL.rawData_[k][6]!==null){
     if(BL.rawData_[k+1][6]===null){var ix=1;while(ix<10){ix++;if(BL.rawData_[k+ix][6]!==null){df_k=BL.rawData_[k][6]-BL.rawData_[k+ix][6];break;}}}
     else{df_k=BL.rawData_[k][6]-BL.rawData_[k+1][6];}
	 if(Math.abs(df_k)>sum_grz){g_korr+=df_k;}
    }k++;datum.setTime(BL.rawData_[k][0]);day=datum.getDate();
   }
   old_d=cur_day;g_alt=g_neu;if(BL.rawData_[k-1][6]!==null){g_neu=BL.rawData_[k-1][6];}else{g_neu=g_alt;}tageswert=g_neu-g_alt+diff_korr+g_korr;if(Math.abs(tageswert)<0.015)tageswert=0.0;
  }
  if(cur_day!=old_d){suche_tag=1;tageswert=0;}
  if((hr_akt>2)&&(hr_akt<23)){
   if(tageswert>0){data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>tageswert,<?php if($RollPeriod > 2)echo"0"?>,0]);}
   else if(tageswert<0){data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";}?><?php if($RollPeriod > 2)echo"0"?>,tageswert,0]);}
   else{data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>0,<?php if($RollPeriod > 2)echo"0"?>,0]);}
  }else{data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?><?php if($RollPeriod > 2)echo"0,0"; else echo","; ?>,0]);}
 }
}else if((Nummer==2)<?php if($MultiType <2)echo"||(Nummer==13)";elseif($MultiType >1)echo"||(Nummer==1)";?>){//Grünlandtemp
var g_temp=0;
var datapoints;
datum.setTime(BL.rawData_[0][0]);
 for(i=0;i<boundary;i++){datum.setTime(BL.rawData_[i][0]);cur_month=datum.getMonth();cur_day=datum.getDate(); 
     if((cur_month==0)&&(cur_day<5)){document.getElementById("Message_box").style.backgroundColor="#a9dfbf";document.getElementById("Message_box").textContent="<?php echo $SAs[149]; ?>";
       GTS=1;break;
 }}
 day=cur_day;
 for(i=0;i<boundary;i++){
  datum.setTime(BL.rawData_[i][0]);cur_month=datum.getMonth();
  if(cur_month < 6){//nur die ersten 5 Monate
    cur_day=datum.getDate();msg=1;
    datapoints=0;   
    if(suche_tag){
      suche_tag=0;k=i;if(k>0){k--;}
      while((day==cur_day)&&(k<x_ax)){//whole day
        if(BL.rawData_[k][Nummer]!==null){
          tageswert+=BL.rawData_[k][Nummer];
          datapoints++;
        }
        k++;datum.setTime(BL.rawData_[k][0]);day=datum.getDate();
      }
      if(datapoints>0){
        tageswert=tageswert/datapoints;
        if(tageswert >0.0){if(cur_month==0)g_temp+=(tageswert*0.5);else if(cur_month==1)g_temp+=(tageswert*0.75);else g_temp+=tageswert;} else tageswert=0.0;
      }
    }
    if(cur_day!=day){suche_tag=1;cur_day=day;}
  }
  if(g_temp < 1500.0){data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>tageswert,,g_temp]);}
  else{data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>,,]);}
  tageswert=0.0;
 }
 if(GTS==0){document.getElementById("Message_box").style.backgroundColor="#ffe4c4";document.getElementById("Message_box").textContent="<?php echo $SAs[149].$SAs[150]; ?>";}
}else{//Auswertung Regen//fuer alle Punkte//nur addieren
 for(i=0;i<boundary;i++){datum.setTime(BL.rawData_[i][0]);cur_day=datum.getDate();hr_akt=datum.getHours();
  if(suche_tag){suche_tag=0;k=i;if(k>0){k--;}tageswert=0;day=cur_day;
   while((day==cur_day)&&(k<x_ax)){//whole day
    if(BL.rawData_[k][Nummer]!==null){tageswert+=BL.rawData_[k][Nummer];}
     k++;datum.setTime(BL.rawData_[k][0]);day=datum.getDate();
    }
   old_d=cur_day;if(Math.abs(tageswert)<0.05)tageswert=0.0;
  }
  if(cur_day!=old_d){suche_tag=1;tageswert=0;}
  if((hr_akt>2)&&(hr_akt<=22)){
   if(tageswert>0){data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>tageswert,<?php if($RollPeriod > 2)echo"0"?>,0]);}
   else{data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?>0,<?php if($RollPeriod > 2)echo"0"?>,0]);}
  }else{data_d.push([new Date(BL.rawData_[i][0]),<?php for($a=1;$a<=$HelpAnzahlSensoren;$a++){echo"BL.rawData_[i][".$a."],";} ?><?php if($RollPeriod > 2)echo"0,0"; else echo","; ?>,0]);}
 }
}
BL.updateOptions({'file':data_d});//neue Werte laden
<?php if(($MultiType==0)&&($MultiType==1))echo"setTimeout(function(){berechne_sum();},10);"?>
tag_setup=1;
}

//Berechne Summe aktueller Anzeigebereich
function berechne_sum(){
if(typeof BL==='undefined'){return;}
if(typeof BL.rawData_==='undefined'){return;}
if(data_d.length==0){return;}//keine Daten
if(GTS>0)return;
var i=0,tim;
var datum=new Date();
var sum_gew=0,once=1;
var j=BL.numRows();//maximale Anzahl Punkte
for(x=0;x< <?php echo $HelpAnzahlSensoren+1; ?>;x++){//grenzen
  try {
    i=BL.boundaryIds_[x][0];//akueller Bereich
    j=BL.boundaryIds_[x][1];
    break;
  }catch(ignore){}
}
try {
for(;i<j;i++){
  datum.setTime(data_d[i][0]);tim=datum.getHours();
  if(once&&(tim>=4)&&(tim<18)){if(!isNaN(data_d[i+1][<?php echo $HelpAnzahlSensoren+1; ?>])){sum_gew=sum_gew+data_d[i+1][<?php echo $HelpAnzahlSensoren+1; ?>];once=0;}
  if(!isNaN(data_d[i+1][<?php echo $HelpAnzahlSensoren+2; ?>])){sum_gew=sum_gew+data_d[i+1][<?php echo $HelpAnzahlSensoren+2; ?>];once=0;}
  }else if((tim>=22)||(tim<4)){once=1;}
  data_d[i][<?php echo $HelpAnzahlSensoren+3; ?>]=sum_gew;
}//end for
}catch(ignore){}
BL.updateOptions({'file':data_d});
}

//Button Reset Zoom
function reset_Zoom(){zoomBtnColor();desired_range=null;BL.resetZoom();Rng_Axes();}

function zoomBtnColor(){
<?php
  echo'document.getElementById("nav8").style.backgroundColor="#D8D8D8";'."\n";
  echo'document.getElementById("nav24").style.backgroundColor="#BDBDBD";'."\n";
  if($Csvdatei[0]=='w'){//week: 1h, 2h, 4h, 8h, 24h, 1/2w,
   echo'document.getElementById("nav1").style.backgroundColor="#FAFAFA";'."\n";
   echo'document.getElementById("nav2").style.backgroundColor="#F2F2F2";'."\n";
   echo'document.getElementById("nav4").style.backgroundColor="#E6E6E6";'."\n";
   echo'document.getElementById("nav3d").style.backgroundColor="#A4A4A4";'."\n";
  }else{
   echo'document.getElementById("nav7d").style.backgroundColor="#BDBDBD";'."\n";
   if($Csvdatei[0]=='m'){//month: 2h, 4h, 8h,24h, 7d, 14d
    echo'document.getElementById("nav2").style.backgroundColor="#FAFAFA";'."\n";
    echo'document.getElementById("nav4").style.backgroundColor="#F2F2F2";'."\n";
    echo'document.getElementById("nav14d").style.backgroundColor="#A4A4A4";'."\n";
  }else{
    echo'document.getElementById("nav30d").style.backgroundColor="#D8D8D8";'."\n";
    echo'document.getElementById("nav90d").style.backgroundColor="#BDBDBD";'."\n";
    if($Csvdatei[0]=='b'){//gesamt: 2,4,8,24, week, 14 day, month, 3 month
      echo'document.getElementById("nav2").style.backgroundColor="#FAFAFA";'."\n";
      echo'document.getElementById("nav4").style.backgroundColor="#F2F2F2";'."\n";
      echo'document.getElementById("nav14d").style.backgroundColor="#A4A4A4";'."\n";
    }else if($Csvdatei[0]=='A'){//archiv:  8h, 24h , 7d, month, 3 month
  }}}
?>
}

//Button zeige(letzten)Tag/Monat/Woche usw
function zoom(res){
  zoomBtnColor();
  var x='x';
  if(res==3600)x="nav1";
  if(res==7200)x="nav2";
  if(res==14400)x="nav4";
  if(res==28800)x="nav8";
  if(res==86400)x="nav24";
  if(res==302400)x="nav3d";
  if(res==604800)x="nav7d";
  if(res==1209600)x="nav14d";
  if(res==2592000)x="nav30d";
  if(document.getElementById(x))document.getElementById(x).style.backgroundColor="#a9dfbf";
  
  if(res>86400){BL.updateOptions({axes:{x:{pixelsPerLabel:90}}});}
  else{BL.updateOptions({axes:{x:{pixelsPerLabel:105}}});}
  var wold=BL.xAxisExtremes();
  var w=BL.xAxisRange();
  if((w[1]-res*1000)<wold[0]){w[0]=wold[0];//ganz links
    if((w[1]+res *1000)<wold[1]){w[1]=w[1]+res*1000;}//nach rechts geht noch
    else{w[1]=wold[1];}//ganz rechts
    desired_range=[w[0],w[1]];
  }else{desired_range=[w[1]-res*1000,w[1]];}
  animate();
}

//Button Ansicht nach links oder rechts verschieben
function pan(dir){
  var wold=BL.xAxisExtremes();
  var w=BL.xAxisRange();
  var scale=(w[1]-w[0]);//aktuelle X - Ansicht
  var amount=scale*dir;//Versatz
  if((dir<0)&&(w[0]>wold[0])){//Grenze links
    if((w[0]+amount)<=wold[0]){w[0]=wold[0]-amount;w[1]=w[0]+scale;}
    desired_range=[w[0]+amount,w[1]+amount];
    animate();
  }
  if((dir>0)&&(w[1]<wold[1])){//Grenze rechts
    if((w[1]+amount)>=wold[1]){w[1]=wold[1]-amount;w[0]=w[1]-scale;}
    desired_range=[w[0]+amount,w[1]+amount];
    animate();
  }
  animate();
}

//Button Zeige gleichen Bereich am rechten oder linkem Ende
function panend(dir){
  var wold=BL.xAxisExtremes();
  var w=BL.xAxisRange();
  var scale=(w[1]-w[0]);//Bereich aktuelle X - Ansicht
  var multiplier;
  if(dir<0){multiplier=(wold[0]-w[0])/scale;}
  else{multiplier=(wold[1]-w[1])/scale;}
  var amount=scale*multiplier;
  if((dir<0)&&(w[0]>wold[0])){//Grenze links erreicht?
    if((w[0]+amount)<=wold[0]){w[0]=wold[0]-amount;w[1]=w[0]+scale;}
    desired_range=[w[0]+amount,w[1]+amount];
    animate();
  }
  if((dir>0)&&(w[1]<wold[1])){//Grenze rechts erreicht ?
    if((w[1]+amount)>=wold[1]){w[1]=wold[1]-amount;w[0]=w[1]-scale;}
    desired_range=[w[0]+amount,w[1]+amount];
    animate();
  }
  animate();
}

function animate(){move++;setTimeout(function(){approach_range();},50);}

//Anzeigebereich festlegen
function approach_range(){
  if(!desired_range)return;
  if(move>=4){//(do not set another timeout.)
    BL.updateOptions({dateWindow:desired_range});
    berechne_sum();Rng_Axes();move=0;
  }else{//go halfway there
    var range=BL.xAxisRange();
    var new_range;
    new_range=[0.5*(desired_range[0]+range[0]),0.5*(desired_range[1]+range[1])];
    BL.updateOptions({dateWindow:new_range});
    animate();move++;
  }
}
<?php
echo"
</script>
<noscript><b>JavaScript must be enabled in order for you to use this page.</b>However, it seems JavaScript is either disabled or not supported by your browser. Enable JavaScript by changing your browser options, and then try again.
</noscript>
</body>
</html>";

function CSVbuilder($beeOrdner=""){//CSV Builder Version 4.0----
 $input=$beeOrdner."beelogger.csv";
  if(file_exists($input)==false)return;
  $array=file($input);
  $asize=sizeof($array);//Grösse des Arrays

  if($asize>0){//any elements
  while($asize--){
    $what=trim($array[$asize]);
    $x=explode(",",$what );
    $s=count($x);//beelogger.csv Spalten pro Zeile
    if($x[$s-1] !=''){//letzte Spalte=Zeitstempel abfragen
      $LastDate=(int)$x[$s-1];
      break;
    }
  }

//csv - Monat und Woche
   $L_m=$LastDate-2678400;//Monat etwa  60*60*24*31=2678400 Ticks
   $L_w=$LastDate-604800;//Woche etwa  60*60*24*7=604800 Ticks

   $fp_m=fOpen(($beeOrdner."month.csv"), "w");
   $fp_w=fOpen(($beeOrdner."week.csv"), "w");

   foreach($array as $val){
    $what=trim($val);
    $x=explode(",",$what);
    $s=count($x);//beelogger.csv Spalten pro Zeile
    if((int)$x[$s-1]>=$L_m)fputs($fp_m,$val);
    if((int)$x[$s-1]>=$L_w)fputs($fp_w,$val);
   }
  fclose($fp_w);
  fclose($fp_m);

  }//if$asize>0
}//Ende function csv-Builder 4.0
 ?>
