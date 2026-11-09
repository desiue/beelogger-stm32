<?php

$Softwareversion = "M.40";//vom 24.09.2026 - Errorchecker.php

error_reporting(E_ALL);

$isHttps = (!empty($_SERVER['HTTPS']));
if($isHttps == 0){
	echo"Webseite   ".$_SERVER['SERVER_NAME']." bitte mit https:// aufrufen"."\n";
  exit;
}


echo'<!DOCTYPE html>
<html lang="de">
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <meta name="description" content="Auflistung">
  <meta http-equiv="refresh" content="120">
  <title>beelogger Errorchecker :-)</title>
  <link rel="shortcut icon" href="favicon-32x32.png" />
</head>

<body>
';
$f_name = "ErrorcheckerData.php";
if(!file_exists($f_name)){$aktion = fOpen($f_name,"w");fWrite($aktion,"<?php\r\n"); fClose($aktion);}


if (filesize("ErrorcheckerData.php") <50){
	echo "<h4><font COLOR=GREEN>Es sind keine neuen Daten vorhanden...läuft gut bei den beeloggern ;-) &nbsp ".'<img src="X_beelogger_icons/like.png" height="20" width="20" style="margin-bottom:-3px">'."</font></h4>";
}
  
if(file_exists("ErrorcheckerData_old.php")) echo '<form action="Errorchecker.php" method="post"><button name="Show_Old" value="1" style="height:40px; width:150px">Zeige alte Daten</button></form>';


$show_old ="0";
if(isset($_POST['Show_Old']))$show_old=$_POST['Show_Old'];
$daterr="0";
if(isset($_POST['DataErr']))$daterr=$_POST['DataErr'];
$delet="0";
if(isset($_POST["DelAll"]))$delet=$_POST["DelAll"];

//echo "<br>- ".$show_old." - ".$daterr." - ".$daterrdel." - ".$delet." -<br>";

if ($show_old=="1"){
  $f_name = "ErrorcheckerData_old.php";
  echo "<h2><font COLOR=ORANGE>Anzeige alte Daten &nbsp;-> ".$f_name." :<BR></font></h2><br>";
  if(file_exists("ErrorcheckerData___old.php")) include("ErrorcheckerData___old.php");
  if(file_exists("ErrorcheckerData__old.php"))  include("ErrorcheckerData__old.php");
  if(file_exists("ErrorcheckerData_old.php"))   include("ErrorcheckerData_old.php");
}
else if ($daterr=="1"){
  echo '<form action="Errorchecker.php" method="post"><button name="DaterrDel" value="1" style="height:40px; width:250px">Daten mit >datErr< löschen </button></form>';
}
else if($delet=="1"){
  $f_name = "ErrorcheckerData.php";
  $aktion = fOpen($f_name,"a");fWrite($aktion,"?>\r\n");fClose($aktion);
  //  save old data
  //if(file_exists("ErrorcheckerData___old.php")) rename("ErrorcheckerData___old.php","ErrorcheckerData_xold.php");
  if(file_exists("ErrorcheckerData__old.php"))rename("ErrorcheckerData__old.php", "ErrorcheckerData___old.php");
  if(file_exists("ErrorcheckerData_old.php")) rename("ErrorcheckerData_old.php",  "ErrorcheckerData__old.php");
  rename($f_name,"ErrorcheckerData_old.php");
  echo "<h2><font COLOR=RED> ".$f_name." gelöscht!</font></h2><br>";
  // create empty file
  $aktion = fOpen($f_name,"w");fWrite($aktion,"<?php\r\n");fClose($aktion);
}
else {
  $f_name = "ErrorcheckerData.php";
  if (filesize($f_name) >50){
    echo '<form action="Errorchecker.php" method="post"><button name="DelAll" value="1" style="height:40px; width:250px">Daten löschen </button></form>';
    echo "<br><h3><font COLOR=ORANGE> -> ".$f_name." :</font></h3>";
    echo "<h4><font COLOR=ORANGE> Größe:&nbsp;".filesize("ErrorcheckerData.php")." byte</font></h4>";
    if (file_exists($f_name)) include($f_name);
  }
}
echo '</body></html>';
?>
