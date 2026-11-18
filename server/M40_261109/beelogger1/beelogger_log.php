<?php
/*
 * (C) 2024 Jeremias Bruker & Rudi Schick & Thorsten Gurzan - beelogger.de
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


$Softwareversion = "M.40";//vom 09.11.2026 - beelogger_log.php

error_reporting(0);

$Sprache=1;// falls noch keine Speicherung stattfand
if(file_exists("../general_ini.php"))include("../general_ini.php");
if(file_exists("../beelogger_sprachfiles/Log_Sprache_".$Sprache.".php"))include("../beelogger_sprachfiles/Log_Sprache_".$Sprache.".php");//Sprache einbinden

date_default_timezone_set('Europe/Berlin');
$beelg=substr(dirname(__FILE__),strrpos(dirname(__FILE__),"/")+1);

if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");
else{
	echo"NotIniT55Dok *";
	echo$LAs[0]."!";
	return;//end
}

$M_Type=1;//Waagen pro beelogger
$M_Sign="";
if(($s_t=strpos($beelg,'beelogger'))!==FALSE){
	$M_Name="Einzel";
	$M_Num=intval(substr($beelg,$s_t+9));
}elseif(($s_t=strpos($beelg,'Duo'))!==FALSE){
	$M_Type=2;$M_Name="Duo";$M_Sign="D";
	$M_Num=intval(substr($beelg,$s_t+3));
}elseif(($s_t=strpos($beelg,'Triple'))!==FALSE){
	$M_Type=3;$M_Name="Triple";$M_Sign="T";
	$M_Num=intval(substr($beelg,$s_t+6));
}elseif(($s_t=strpos($beelg,'Quad'))!==FALSE){
	$M_Type=4;$M_Name="Quad";$M_Sign="Q";
	$M_Num=intval(substr($beelg,$s_t+4));
}elseif(($s_t=strpos($beelg,'Penta'))!==FALSE){
	$M_Type=5;$M_Name="Penta";$M_Sign="P";
	$M_Num=intval(substr($beelg,$s_t+5));
}elseif(($s_t=strpos($beelg,'Hexa'))!==FALSE){
	$M_Type=6;$M_Name="Hexa";$M_Sign="H";
	$M_Num=intval(substr($beelg,$s_t+4));
}


$LORA=0;$NeubeeloggerSketchID="";
$int_Check=0.0;$EE_type=0;$ext_M_Type=1;$ext_M_Data="";$TimeSync="";
$ext_T_Out="";$ext_F_Out="";$ext_L="";$ext_VB="";$ext_VS="";$ext_B_In="";$ext_B_Out="";
$ext_T_In1="";$ext_F_In1="";$ext_G_1="";
$ext_T_In2="";$ext_F_In2="";$ext_G_2="";
$ext_T_In3="";$ext_F_In3="";$ext_G_3="";
$ext_T_In4="";$ext_F_In4="";$ext_G_4="";
$ext_T_In5="";$ext_F_In5="";$ext_G_5="";
$ext_T_In6="";$ext_F_In6="";$ext_G_6="";
$ext_Aux1="";$ext_Aux2="";$ext_Aux3="";
$ext_WaegTemp="";$ext_Service="";
$Count_Sets_in=0;//Anzahl Datensatz EE-Beelogger
$ExternDataCounter=0;

$ext_Passwort="";
if(isset($_GET["Passwort"])){if(preg_match("#^[a-zA-Z0-9\!\*\=\+\-\%\[\]]+$#",$_GET["Passwort"],$pww))$ext_Passwort=$pww[0];}
elseif(isset($_GET["PW"])){if(preg_match("#^[a-zA-Z0-9\!\*\=\+\-\%\[\]]+$#",$_GET["PW"],$pww))$ext_Passwort=$pww[0];}
if(isset($_GET["Z"])){
	$date=new DateTime('1970-01-01 00:00:00');
	$date2=new DateTime("now");
	$DS3231=$date2->getTimestamp() - $date->getTimestamp();
	$TimeSync=($DS3231 + date("I")*3600)."T";
}
if(isset($_GET["LORA"])){//TTN Lora POST
	$LORA=1;
	$postdataTTN=file_get_contents('php://input');
	$jsd_TTN=json_decode($postdataTTN,true);
	if(isset($jsd_TTN['uplink_message'])){
		$TTN_uplink=$jsd_TTN['uplink_message'];
		if(isset($TTN_uplink['decoded_payload']))$TTN_payload=$TTN_uplink['decoded_payload'];
	}
	else if(isset($jsd_TTN['decoded'])){//Helium
		$TTN_uplink=$jsd_TTN['decoded'];
		if(isset($TTN_uplink['payload']))$TTN_payload=$TTN_uplink['payload'];
	}

	if(isset($jsd_TTN['uplink_message']['rx_metadata'][0]['rssi'])){$ext_Aux2=$jsd_TTN['uplink_message']['rx_metadata'][0]['rssi'];$ext_rssi=$ext_Aux2;}

	if(isset($TTN_payload['TempOut'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['TempOut'])){$ext_T_Out=$TTN_payload['TempOut'];}
	$ext_WaegTemp=$ext_T_Out;
	if(isset($TTN_payload['TempIn'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['TempIn'])){$ext_T_In1=$TTN_payload['TempIn'];}
	if(isset($TTN_payload['FeuchteOut'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['FeuchteOut'])){$ext_F_Out=$TTN_payload['FeuchteOut'];}
	if(isset($TTN_payload['FeuchteIn'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['FeuchteIn'])){$ext_F_In1=$TTN_payload['FeuchteIn'];}
	if(isset($TTN_payload['Licht'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Licht'])){$ext_L=$TTN_payload['Licht'];}
	if(isset($TTN_payload['VBatt'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['VBatt'])){$ext_VB=$TTN_payload['VBatt'];}
	if(isset($TTN_payload['VSolar'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['VSolar'])){$ext_VS=$TTN_payload['VSolar'];}
	if(isset($TTN_payload['BienenIn'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['BienenIn'])){$ext_B_In=$TTN_payload['BienenIn'];}
	if(isset($TTN_payload['BienenOut'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['BienenOut'])){$ext_B_Out=$TTN_payload['BienenOut'];}
	if(isset($TTN_payload['Service'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Service'])){$ext_Service=$TTN_payload['Service'];}
	if(isset($TTN_payload['Aux1'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Aux1'])){$ext_Aux1=$TTN_payload['Aux1'];}
	if(isset($TTN_payload['Aux3'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Aux3'])){$ext_Aux3=$TTN_payload['Aux3']; $NeubeeloggerSketchID="LORA_".$ext_Aux3;}//SketchID ablegen

	if(isset($TTN_payload['Gewicht'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Gewicht'])){$ext_G_1=$TTN_payload['Gewicht'];}

	if($M_Type>1){$ext_M_Type=2;
		if(isset($TTN_payload['TempIn2'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['TempIn2'])){$ext_T_In2=$TTN_payload['TempIn2'];}
		if(isset($TTN_payload['FeuchteIn2'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['FeuchteIn2'])){$ext_F_In2=$TTN_payload['FeuchteIn2'];}
		if(isset($TTN_payload['Gewicht2'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Gewicht2'])){$ext_G_2=$TTN_payload['Gewicht2'];}

	if($M_Type>2){
		if(isset($TTN_payload['TempIn3'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['TempIn3'])){$ext_T_In3=$TTN_payload['TempIn3'];}
		if(isset($TTN_payload['FeuchteIn3'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['FeuchteIn3'])){$ext_F_In3=$TTN_payload['FeuchteIn3'];}
		if(isset($TTN_payload['Gewicht3'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Gewicht3'])){$ext_G_3=$TTN_payload['Gewicht3'];$ext_M_Type=3;}

	if($M_Type>3){$ext_M_Type=4;
		if(isset($TTN_payload['TempIn4'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['TempIn4'])){$ext_T_In4=$TTN_payload['TempIn4'];}
		if(isset($TTN_payload['FeuchteIn4'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['FeuchteIn4'])){$ext_F_In4=$TTN_payload['FeuchteIn4'];}
		if(isset($TTN_payload['Gewicht4'])&&preg_match("#^[0-9\-\.]+$#",$TTN_payload['Gewicht4'])){$ext_G_4=$TTN_payload['Gewicht4'];}

	if($M_Type>4){
		if(isset($TTN_payload['TempIn5'])){$ext_T_In5=$TTN_payload['TempIn5'];}
		if(isset($TTN_payload['FeuchteIn5'])){$ext_F_In5=$TTN_payload['FeuchteIn5'];}
		if(isset($TTN_payload['Gewicht5'])){$ext_G_5=$TTN_payload['Gewicht5'];$ext_M_Type=5;}

	if($M_Type==6){
		if(isset($TTN_payload['TempIn6'])){$ext_T_In6=$TTN_payload['TempIn6'];}
		if(isset($TTN_payload['FeuchteIn6'])){$ext_F_In6=$TTN_payload['FeuchteIn6'];}
		if(isset($TTN_payload['Gewicht6'])){$ext_G_6=$TTN_payload['Gewicht6'];$ext_M_Type=6;}
	}}}}}
	$ext_Show=0;$ext_Check=0;//dummy

}//if("LORA")

else{//Multibeelogger
	$val=array();
	foreach($_GET as $key => $value){
	if($key[0]=="M"){
		$EE_type=1;//EE
		$v=preg_replace('/[[:^print:]]/','',$value);//ignore non printable and unwanted chars
		$vx=preg_replace("/'/i","",$v);//remove '
		$val=preg_replace('/"/i','',$vx);//remove "

		if($key[1]>0 && $key[1]<7){
			$ext_M_Type=$key[1];
			$ext_M_Data=$val;//implode(',',$val);
		}
	}
	}
	if($EE_type==1){//EE
		if(strlen($val) < 20){//no valid data
			write_error_file(1);
			echo"dataError";exit();//kein ok, forciere timeout
		}
		if($TimeSync==""){write_error_file(2);}//Z missing
	}
	else {//kein EE-beelogger

	$ext_Check=5.0;//Datencheck
	if(isset($_GET["C"])&&preg_match("#^[0-9\-\.]+$#",$_GET["C"],$val))$ext_Check=$val[0];//Alt! - abwärtskompatibel
	elseif(isset($_GET["Check"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Check"],$val))$ext_Check=$val[0];

	if(isset($_GET["TempOut"])&&preg_match("#^[0-9\-\.]+$#",$_GET["TempOut"],$val))$ext_T_Out=$val[0];//Alt!
	elseif(isset($_GET["TO"])&&preg_match("#^[0-9\-\.]+$#",$_GET["TO"],$val))$ext_T_Out=$val[0];
	if($ext_T_Out !="")$int_Check +=$ext_T_Out;
	if($ext_T_Out=='99.9')$ext_T_Out='';

	if(isset($_GET["TempIn"])&&preg_match("#^[0-9\-\.]+$#",$_GET["TempIn"],$val))$ext_T_In1=$val[0];//Alt!
	elseif(isset($_GET["T1"])&&preg_match("#^[0-9\-\.]+$#",$_GET["T1"],$val))$ext_T_In1=$val[0];
	if($ext_T_In1 !="")$int_Check +=$ext_T_In1;
	if($ext_T_In1=='99.9')$ext_T_In1='';

	if(isset($_GET["FeuchteOut"])&&preg_match("#^[0-9\-\.]+$#",$_GET["FeuchteOut"],$val))$ext_F_Out=$val[0];//Alt!
	elseif(isset($_GET["FO"])&&preg_match("#^[0-9\-\.]+$#",$_GET["FO"],$val))$ext_F_Out=$val[0];
	if($ext_F_Out !="")$int_Check +=$ext_F_Out;
	if($ext_F_Out=='-1.0')$ext_F_Out='';

	if(isset($_GET["FeuchteIn"])&&preg_match("#^[0-9\-\.]+$#",$_GET["FeuchteIn"],$val))$ext_F_In1=$val[0];//Alt!
	elseif(isset($_GET["F1"])&&preg_match("#^[0-9\-\.]+$#",$_GET["F1"],$val))$ext_F_In1=$val[0];
	if($ext_F_In1 !="")$int_Check +=$ext_F_In1;
	if($ext_F_In1=='-1.0')$ext_F_In1='';

	if(isset($_GET["Licht"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Licht"],$val))$ext_L=$val[0];//Alt!
	elseif(isset($_GET["L"])&&preg_match("#^[0-9\-\.]+$#",$_GET["L"],$val))$ext_L=$val[0];
	if($ext_L !="")$int_Check +=$ext_L;
	if($ext_L=='-1.0')$ext_L='';

	if(isset($_GET["VBatt"])&&preg_match("#^[0-9\-\.]+$#",$_GET["VBatt"],$val))$ext_VB=$val[0];//Alt!
	elseif(isset($_GET["VB"])&&preg_match("#^[0-9\-\.]+$#",$_GET["VB"],$val))$ext_VB=$val[0];
	if($ext_VB !="")$int_Check +=$ext_VB;

	if(isset($_GET["VSolar"])&&preg_match("#^[0-9\-\.]+$#",$_GET["VSolar"],$val))$ext_VS=$val[0];//Alt!
	elseif(isset($_GET["VS"])&&preg_match("#^[0-9\-\.]+$#",$_GET["VS"],$val))$ext_VS=$val[0];
	if($ext_VS !="")$int_Check +=$ext_VS;

	if(isset($_GET["I"])&&preg_match("#^[0-9\-\.]+$#",$_GET["I"],$val))$ext_B_In=$val[0];
	elseif(isset($_GET["BienenIn"])&&preg_match("#^[0-9\-\.]+$#",$_GET["BienenIn"],$val))$ext_B_In=$val[0];
	if($ext_B_In !="")$int_Check +=$ext_B_In;
	if($ext_B_In=='-1')$ext_B_In='';

	if(isset($_GET["O"])&&preg_match("#^[0-9\-\.]+$#",$_GET["O"],$val))$ext_B_Out=$val[0];
	elseif(isset($_GET["BienenOut"])&&preg_match("#^[0-9\-\.]+$#",$_GET["BienenOut"],$val))$ext_B_Out=$val[0];
	if($ext_B_Out !="")$int_Check +=$ext_B_Out;
	if($ext_B_Out=='-1')$ext_B_Out='';

	if(isset($_GET["Gewicht"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Gewicht"],$val)){ $ext_G_1=$val[0];}//Alt!
	elseif(isset($_GET["G1"])&&preg_match("#^[0-9\-\.]+$#",$_GET["G1"],$val)){$ext_G_1=$val[0];}
	if($ext_G_1 !="")$int_Check +=$ext_G_1;

	if($M_Type==2){
		if(isset($_GET["W"])&&preg_match("#^[0-9\-\.]+$#",$_GET["W"],$val)){
			$ext_WaegTemp=$val[0];
			$int_Check +=$ext_WaegTemp;
			if($ext_WaegTemp=='99.9')$ext_WaegTemp='';
		}
		if(isset($_GET["T2"])&&preg_match("#^[0-9\-\.]+$#",$_GET["T2"],$val))$ext_T_In2=$val[0];
		if($ext_T_In2 !="")$int_Check +=$ext_T_In2;
		if(isset($_GET["F2"])&&preg_match("#^[0-9\-\.]+$#",$_GET["F2"],$val))$ext_F_In2=$val[0];
		if($ext_F_In2 !="")$int_Check +=$ext_F_In2;
		if(isset($_GET["G2"])&&preg_match("#^[0-9\-\.]+$#",$_GET["G2"],$val)){
			$ext_G_2=$val[0];
			$int_Check +=$ext_G_2;
			$ext_M_Type=2;
		}
	}//M_Type

	if(isset($_GET["A1"])&&preg_match("#^[0-9\-\.]+$#",$_GET["A1"],$val))$ext_Aux1=$val[0];
	elseif(isset($_GET["Aux1"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Aux1"],$val))$ext_Aux1=$val[0];
	if(isset($_GET["A2"])&&preg_match("#^[0-9\-\.]+$#",$_GET["A2"],$val))$ext_Aux2=$val[0];
	elseif(isset($_GET["Aux2"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Aux2"],$val))$ext_Aux2=$val[0];
	if(isset($_GET["A3"])&&preg_match("#^[0-9\-\.]+$#",$_GET["A3"],$val))$ext_Aux3=$val[0];
	elseif(isset($_GET["Aux3"])&&preg_match("#^[0-9\-\.]+$#",$_GET["Aux3"],$val))$ext_Aux3=$val[0];
	if($ext_Aux1 !="")$int_Check +=$ext_Aux1;
	if($ext_Aux2 !="")$int_Check +=$ext_Aux2;
	if($ext_Aux3 !="")$int_Check +=$ext_Aux3;
//old params only up to Duo
	if((isset($_GET["G3"]))OR(isset($_GET["G4"]))){write_error_file(8);}
}//ohne EE

	if(isset($_GET["S"])&&preg_match("#^[0-9\-\.]+$#",$_GET["S"],$val))$ext_Service=$val[0];
	if($ext_Service !="")$int_Check+=$ext_Service;

$ext_Show=0;
if(isset($_GET["Show"])&&$_GET["Show"]=="1"){
	$ext_Show=1;
	echo'<!DOCTYPE html><html lang="de">
		<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"><title>'.$LAs[1].' beelogger_log.php</title></head><body>';
}
//SketchID
if(isset($_GET["ID"])&& preg_match("#^[A-Z0-9\_]+$#",$_GET["ID"],$val))$NeubeeloggerSketchID=$val[0];

if($M_Type!=$ext_M_Type){
	if($ext_Show==0){echo"typErrT25Dok *";write_error_file(3);exit();}
}

}//Multibeelogger

function PruefeDatum($date,$format='Y/m/d H:i:s'){
	$d=DateTime::createFromFormat($format,$date);
	$NaechstesJahr=date("Y")+1;
	$NaechsterMonat=date("m")+1;
	$JahrAusDate=intval(substr($date,0,4));
	$MonatAusDate=intval(substr($date,5,2));
	return($d && $d->format($format)==$date && $JahrAusDate<$NaechstesJahr && $MonatAusDate<$NaechsterMonat);
}

//Process data
$BeepDataArray=array();
$KeeperDataArray=array();
$ThingsPeakDataArray=array();

$zeit=date("Y/m/d H:i:00");
if($ext_Passwort==$BeeloggerLogPasswort){
	if($ext_M_Data !=""){//M_Data Verarbeitung
		$M_D_Arr=explode(",",$ext_M_Data);
		$M_D_Sz=count($M_D_Arr);
		for($i=0; $i<$M_D_Sz; $i++){//remove invalid data
			if($M_D_Arr[$i]){
				if(!preg_match("#^[0-9\/\_\-\:\,\.]+$#",$M_D_Arr[$i])){$M_D_Arr[$i]='';}
			}
		}
		$M_Sz=13+($M_Type-1)*3;if($M_Type>1)$M_Sz++;//sollanzahl array
		if($M_D_Sz<$M_Sz){echo"DataSize";write_error_file(9);exit();}//kein ok forciere timeout
		$M_Sz=$M_D_Sz - $M_Sz;
		$ext_Servicesaved=$ext_Service;//zwischenspeichern
		$errlog_once=0;
		$G=0;
	try{
		while($G<=$M_Sz){
		if($G>1)$G--;
			do{
				$p=strpos($M_D_Arr[$G],'_');
				if($p==10){
					$zeit=$M_D_Arr[$G];$zeit[10]=' ';$zeit[17]='0';$zeit[18]='0';
					$G++; break;
				}
				$G++;
			} while($G<($M_D_Sz-11));
			if($M_Type>1){$ext_WaegTemp=$M_D_Arr[$G];$G++;}
			$ext_T_In1=$M_D_Arr[$G];$G++;
			if($ext_M_Type>1){$ext_T_In2=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>2){$ext_T_In3=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>3){$ext_T_In4=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>4){$ext_T_In5=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>5){$ext_T_In6=$M_D_Arr[$G];$G++;}

			$ext_T_Out=$M_D_Arr[$G];$G++;
			$ext_F_In1=$M_D_Arr[$G];$G++;
			if($ext_M_Type>1){$ext_F_In2=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>2){$ext_F_In3=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>3){$ext_F_In4=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>4){$ext_F_In5=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>5){$ext_F_In6=$M_D_Arr[$G];$G++;}

			$ext_F_Out=$M_D_Arr[$G];$G++;
			$ext_L=$M_D_Arr[$G];$G++;

			$ext_G_1=$M_D_Arr[$G];$G++;
			if($ext_M_Type>1){$ext_G_2=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>2){$ext_G_3=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>3){$ext_G_4=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>4){$ext_G_5=$M_D_Arr[$G];$G++;}
			if($ext_M_Type>5){$ext_G_6=$M_D_Arr[$G];$G++;}

			$ext_VB=$M_D_Arr[$G];$G++;
			$ext_VS=$M_D_Arr[$G];$G++;
			$ext_Service=$M_D_Arr[$G];$G++;
//take care of missing "," in string, timestamp in data
		if(strpos($M_D_Arr[$G],'/')){$ext_Aux1="";$ext_Aux2="";$ext_Aux3="";}
		else {
			$ext_Aux1=$M_D_Arr[$G];$G++;
			if(strpos($M_D_Arr[$G],'/')){$ext_Aux2="";$ext_Aux3="";}
			else {
				$ext_Aux2=$M_D_Arr[$G];$G++;
				if(strpos($M_D_Arr[$G],'/')){$ext_Aux3="";} else {$ext_Aux3=$M_D_Arr[$G];}
			}
		}
		$G++;

		if($G<$M_D_Sz){//Einzelner Datensatz in Gesamtdaten
			if(PruefeDatum($zeit)){
			$Count_Sets_in++;
			Auswertung(0);
			}
			else{
				if($errlog_once==0){$errlog_once=1;	write_error_file(4);}
			}
		}
		else{//der letzte Datensatz ist erreicht
			if($ext_Servicesaved!=""){$ext_Service=$ext_Servicesaved;}//verwende Wert aus GET(S=)

			if(PruefeDatum($zeit)){
				$Count_Sets_in++;
				Auswertung(1);//Auswertung mit Serverantwort
				ExternDataOut();//Beep/ThingSpeak
				Triggeralarme();
			}
			else{echo time()."T20Bok *";write_error_file(5);exit();}
		}
		}//while
	}catch(Exception $e){echo time()."T25Cok *"; write_error_file(7);exit();}
	}//if $ext_M_Data

	else{//kein EE
		$int_Check=round($int_Check + 0.5);//Checksummencheck
		if(((($int_Check - $ext_Check)< 2)AND(($int_Check - $ext_Check)> -2)AND $ext_Check !="")OR $ext_Show==1 OR $LORA==1){
			if($ext_Show==1)echo"<br>".$LAs[3].": ".$int_Check."----> ".$LAs[4].": ".$ext_Check."<BR>";//nur zur Info
			if($ext_M_Type==$M_Type){
				$Count_Sets_in=1;
				Auswertung(1);
				ExternDataOut();//Thingspeak /Beep
				Triggeralarme();//Verarbeitung der Triggeralarme
			}
			else{write_error_file(6);echo "typErrT25Dok *";}
		}
		elseif($ext_Show==1)echo"<br><blockquote>".$LAs[5]."</blockquote>Csum_error(".$int_Check.",".$ext_Check.")";
		else {echo"ChSumErrT55Dok *";}
	}
}//ENDE Process Data

//Spezialfunktion zur Anzeige für externe Displays oder INFO
elseif(isset($_GET["showlast"])&&$_GET["showlast"]=="zeigen"){
	if(file_exists("week.csv")){//Letzte Werte aus week.csv auslesen
		$input="week.csv";
		$array=file($input);
		$j=count($array);
		while($j--){
			$what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
			if($x[$s-1] !=''){break;}
		}
		echo $what;
		echo "ok *";
	}
}//$ext_ShowLast
else{
	write_error_file(10);
	echo	"PwErrorT55Dok *";//Code für falsches Passwort
}
//ENDE Skript

//#####//Berechnung der Serversendesteuerung
function ServerZeitSteuerung(){
	global $ext_L,$ext_VS;
	$MessIntervall="15";//falls keine Steuerung vorhanden
	if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");

	if($IntervallSendeSteuerung=="lichtgesteuert"){//lichtgesteuerte Sendeintervalle
		if($ext_L=="")$IntervallSendeSteuerung="zeitgesteuert";//Lichtsteuerung ohne Werte geht nicht
		else{
			$f_val=(float)$ext_L;
			if($f_val>1.0)$MessIntervall=$SommerSendeIntervallTag;//Sonnenlicht vorhanden
			else $MessIntervall=$SommerSendeIntervallNacht;//nachts --> Sensorwert=0
		}
	}
	if($IntervallSendeSteuerung=="solarspannungsgesteuert"){//solarspannungsgesteuerte Sendeintervalle
		if($ext_VS=="")$IntervallSendeSteuerung="zeitgesteuert";//Spannungsteuerung ohne Werte geht nicht
		else{
			$f_val=(float)$ext_VS;
			if($f_val>0.3)$MessIntervall=$SommerSendeIntervallTag;//Sonnenlicht vorhanden
			else $MessIntervall=$SommerSendeIntervallNacht;//nachts --> Sensorwert=0
		}
	}
	if($IntervallSendeSteuerung=="zeitgesteuert"){//zeitgesteuerte Sendeintervalle
		if((intval(date("G"))==($SommerTagZeit-1)AND intval(date("i"))>=56)OR(intval(date("G"))>=$SommerTagZeit AND(intval(date("G"))< $SommerNachtZeit)))$MessIntervall=$SommerSendeIntervallTag;
		else $MessIntervall=$SommerSendeIntervallNacht;
	}
	if($WinterBeginn !="deaktiviert"){
	if(fmod($SommerBeginn,1)==0.5)$SommerBeginnInTagen=date("z",strtotime(round($SommerBeginn,0,PHP_ROUND_HALF_DOWN)."/15"));
	else $SommerBeginnInTagen=date("z",strtotime(round($SommerBeginn)."/1"));
		if(fmod($WinterBeginn,1)==0.5)$WinterBeginnInTagen=date("z",strtotime(round($WinterBeginn,0,PHP_ROUND_HALF_DOWN)."/15"));
		else $WinterBeginnInTagen=date("z",strtotime(round($WinterBeginn)."/1"));
		if((date("z")< $SommerBeginnInTagen)OR(date("z")>=$WinterBeginnInTagen))$MessIntervall=$WinterSendeIntervall;//Winter, tagsüber keine Aktion in der Beute
	}
	if((int)$MessIntervall >60)$MessIntervall="60";
	return $MessIntervall;
}
//#####
function Auswertung($Answer){

//Globale Variablen definieren
	global $NeubeeloggerSketchID,$M_Type,$M_Name,$M_Sign,$M_Num,$beelg,$zeit,$Softwareversion,$ext_Show,$ExternDataCounter,$BeepDataArray,$BeepArray,$BeepIdArray,$KeeperDataArray,$KeeperArray,$KeeperIdArray,$KeeperApi,$ThingsPeakDataArray,$ThingsPeakArray,$ThingsPeakApiArray,$ThingsPeakChannelArray,$TimeSync,$PushToken,$PushUser,$T_Token,$T_Chat_Id,$Empfaenger_Email,$Absender_Email,$LORA;//Allgemeine globale Multi-Daten
	global $ext_WaegTemp,$ext_T_In1,$ext_T_In2,$ext_T_In3,$ext_T_In4,$ext_T_In5,$ext_T_In6,$ext_T_Out,$ext_F_In1,$ext_F_In2,$ext_F_In3,$ext_F_In4,$ext_F_In5,$ext_F_In6,$ext_F_Out,$ext_L,$ext_G_1,$ext_G_2,$ext_G_3,$ext_G_4,$ext_G_5,$ext_G_6,$ext_VB,$ext_VS,$ext_Service,$ext_B_In,$ext_B_Out,$ext_Aux1,$ext_Aux2,$ext_Aux3,$ext_rssi,$LAs;

	$EESendeIntervall="B";
	$MessIntervall="15";
	if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");
	if(!isset($KorrPressure))$KorrPressure="0.0";
	if(!isset($AutoAnmerkungenErzeugen))$AutoAnmerkungenErzeugen="";
	if(!isset($AutoServiceAnmerkung))$AutoServiceAnmerkung="";

	$MessIntervall=ServerZeitSteuerung();

	$hh=date("H");//nighttime force long send interval
	$M_Int=(int)$MessIntervall;
	if($hh>20 || $hh<4)if($M_Int >=30)$EESendeIntervall="B";

$Date_Min="1735686000";//1735686000=2025
$timestamp=strtotime($zeit);

$LetztesGewichtArray=array("","","","","","","","");
$AktualisierungStampArray=array($Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min);
$Klammern=array("[", "]");
$GewichtsEinheit=str_replace($Klammern,"",$Sensoren[5*5+4]);
$BienenDifferenzM=0;

if(!isset($Beep))$Beep="";
if(!isset($BeepId))$BeepId="";
if(!isset($BeepArray))$BeepArray=array("","","","","","");
if(!isset($BeepIdArray))$BeepIdArray=array("","","","","","");
if($M_Type==1){//Single/Multi-kompatibilität
	if($Beep !="")$BeepArray[0]=$Beep;
	if($BeepId !="")$BeepIdArray[0]=$BeepId;
}
if(!isset($Keeper))$Keeper="";
if(!isset($KeeperId))$KeeperId="";
if(!isset($KeeperArray))$KeeperArray=array("","","","","","");
if(!isset($KeeperIdArray))$KeeperIdArray=array("","","","","","");
if($M_Type==1){//Single/Multi-kompatibilität
	if($Keeper !="")$KeeperArray[0]=$Keeper;
	if($KeeperId !="")$KeeperIdArray[0]=$KeeperId;
}

$ThingsPeakArray=array("","","","","","");
$ThingsPeakApiArray=array("","","","","","");
$ThingsPeakChannelArray=array("","","","","","");
$ThingsPeakSensorenArray=array(array("","","","","","","","",""));
//single beelogger
if(!isset($ThingsPeak))$ThingsPeak=""; else $ThingsPeakArray[0]=$ThingsPeak;
if(!isset($ThingsPeakApi))$ThingsPeakApi=""; else $ThingsPeakApiArray[0]=$ThingsPeakApi;
if(!isset($ThingsPeakChannel))$ThingsPeakChannel=""; else $ThingsPeakChannelArray[0]=$ThingsPeakChannel;
if(!isset($ThingsPeakSensoren))$ThingsPeakSensoren=""; else $ThingsPeakSensorenArray[0]=$ThingsPeakSensoren;
$iBeeKeeperArrayrain=array("","","","","","");
$iBeeKeeperArrayrain[0]=$Icon[(sizeof($Icon)-2)];

$numMulti=$M_Type;
if($M_Type>1)$numMulti++;
//Daten vorbereiten
for($i=0;$i<$numMulti;$i++){
//ThingsPeakArrayDaten von den Unterbeeloggern abgreifen
	if($i>0){//0=beelogger_ini.php is already included
		$IniFile='../beelogger'.$M_Sign.$M_Num."_".$i.'/beelogger_ini.php';
		if(file_exists($IniFile))include($IniFile);
		$ThingsPeakArray[$i-1]=$ThingsPeak;
		$ThingsPeakApiArray[$i-1]=$ThingsPeakApi;
		$ThingsPeakChannelArray[$i-1]=$ThingsPeakChannel;
		$ThingsPeakSensorenArray[$i-1]=$ThingsPeakSensoren;
	  $iBeeKeeperArrayrain[$i-1]=$Icon[(sizeof($Icon)-2)];
	}
	
//Vorherige SensorenWerte zum Vergleich einholen
	if($i==0)$CSVFile="week.csv";
	else $CSVFile='../beelogger'.$M_Sign.$M_Num."_".$i.'/week.csv';

	if(file_exists($CSVFile)){
		$input=$CSVFile;
		$array=file($input);
		$j=count($array);
		if($j>0){
		 while($j--){
			$what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
			if($x[$s-1] !=''){
				$AktualisierungStampArray[$i]=$x[$s-1];
				if(($M_Type==1)OR($i>0))$LetztesGewichtArray[$i]=$x[6];
				if($M_Type==1)$LetzterService=$x[9];
				if($M_Type>1 AND $i==0)$LetzterService=$x[4];
				break;
			}
		 }
		}
/*
//Beelogger mit aktivem Bienenzähler: Bienendifferenz pro Tag berechnen
//Bienendifferenz wird nur Tagweise berechnet
	if($i==0 AND $ext_B_In !='' AND $ext_B_Out !=''){
		$b=count($array);
		if($b>12){
			$LastBienenIn=0;$LastBienenOut=0;
			while($b>0){
				$what=trim($array[$b-1]);$x=explode(",",$what);$s=count($x);
				if($x[$s-1] !=''){
					if((date("Y",$x[$s-1])<date("Y"))OR(date("Y",$x[$s-1])==date("Y")AND date("z",$x[$s-1])<date("z"))){//Daten des vorherigen Tages gefunden
						if($x[7] !="")$LastBienenIn=(int)$x[7];	else $LastBienenIn=0;
						if($x[8] !="")$LastBienenOut=(int)$x[8]; else $LastBienenOut=0;
						$BienenDifferenzM=$ext_B_In-$LastBienenIn - $ext_B_Out-$LastBienenOut;
						break;
					}
				}
				$b--;
			}//while
		}//if BZ
	 }//Bienendiff
*/	 
	}//week
}//vorbereiten

//Abfrage zur Fehlervermeidung Sommer/Winterzeitumstellung und Datensatzabstand kleiner 4 Minuten
//Service oder Pegelmessung immer erlaubt
 $store=0;
 $datensatz="-";
 $akttime=(int)$AktualisierungStampArray[0];
 $mintime=220;
 if($_SERVER['SERVER_NAME']=="community.beelogger.de"){$mon=(int)date("m"); if($mon<3 OR $mon>9) $mintime=520;}
 if($timestamp>($akttime+$mintime))$store=1;//normal
 else if(($timestamp>($akttime+110))AND((int)$ext_Service<0))$store=1;//Pegelmessung
 else if(($timestamp>$akttime)AND((int)$ext_Service==99))$store=1;//Arbeitsschalter
 else if($KalibrierTemperatur=="99.9"	AND($timestamp>($akttime+55)))$store=1;//Testfunktion
 
if($store){

//Temp
	if(is_numeric($ext_WaegTemp))$ext_WaegTemp=number_format((float)$ext_WaegTemp,2,'.','');
	if(is_numeric($ext_T_Out))$ext_T_Out=number_format((float)$ext_T_Out,1,'.','');
	$ext_T_InArray=array($ext_T_In1,$ext_T_In2,$ext_T_In3,$ext_T_In4,$ext_T_In5,$ext_T_In6);
	for($i=0;$i<$M_Type;$i++){if(is_numeric($ext_T_InArray[$i]))$ext_T_InArray[$i]=number_format((float)$ext_T_InArray[$i],1,'.','');}
//Feuchte
	if(is_numeric($ext_F_Out))$ext_F_Out=number_format((float)$ext_F_Out,1,'.','');
	$ext_F_InArray=array($ext_F_In1,$ext_F_In2,$ext_F_In3,$ext_F_In4,$ext_F_In5,$ext_F_In6);
	for($i=0;$i<$M_Type;$i++){if(is_numeric($ext_F_InArray[$i]))$ext_F_InArray[$i]=number_format((float)$ext_F_InArray[$i],1,'.','');}
//Weitere
	if(is_numeric($ext_L))$ext_L=number_format((float)$ext_L,1,'.','');
	if(is_numeric($ext_VB))$ext_VB=number_format((float)$ext_VB,2,'.','');
	if(is_numeric($ext_VS))$ext_VS=number_format((float)$ext_VS,2,'.','');
//Aux
	if(is_numeric($ext_Aux1)){$ext_Aux1=(float)$ext_Aux1 +(float)$KorrPressure;	$ext_Aux1=number_format($ext_Aux1,1,'.',''); }
	if(is_numeric($ext_Aux2))$ext_Aux2=number_format((float)$ext_Aux2,1,'.','');
	if(is_numeric($ext_Aux3))$ext_Aux3=number_format((float)$ext_Aux3,1,'.','');
//Gewichte
	$ext_G_Array=[$ext_G_1,$ext_G_2,$ext_G_3,$ext_G_4,$ext_G_5,$ext_G_6];
	for($i=0;$i<$M_Type;$i++){if(is_numeric($ext_G_Array[$i])){$prec=2; if((float)$ext_G_Array[$i]<14.5)$prec=3;$ext_G_Array[$i]=number_format((float)$ext_G_Array[$i],$prec,'.','');}}

//Service-Variablen
	if($ext_Service=='-1')$ext_Service='';
	if((int)$ext_Service>1000)$ext_Service='99';
	if($ext_Service !="")$ext_Service=round($ext_Service,1);

//externe Temperaturkompensation für die Gewichtssensoren
	$ext_G_KorrArray=array();$ext_G_KorrArray[0]=0;
	if($M_Type==1){//Einzelbeelogger
		$KorrekturwertArray=array("","","");
		if(!isset($Korrekturwert))$Korrekturwert="0";
		$KorrekturwertArray[0]=$Korrekturwert;
	}
	for($ik=0;$ik<$M_Type;$ik++){
		if(($ext_T_Out !='' OR $ext_WaegTemp !='')AND $KorrekturwertArray[$ik] !="0" AND $KorrekturwertArray[$ik] !='' AND $KalibrierTemperatur !='' AND $ext_G_Array[$ik] !=''){
			if(abs($KorrekturwertArray[$ik])<2){//Check Absolutwert --> Aktuelle Einheit im KG-Bereich
			$prec=2; if((float)$ext_G_Array[$ik]<10.0)$prec=3;
				if($ext_WaegTemp !='')$ext_G_KorrArray[$ik]=round($ext_G_Array[$ik] +(($ext_WaegTemp-(float)$KalibrierTemperatur) * (float)$KorrekturwertArray[$ik]),$prec);
				elseif($ext_T_Out !='')$ext_G_KorrArray[$ik]=round($ext_G_Array[$ik] +(($ext_T_Out-(float)$KalibrierTemperatur) * (float)$KorrekturwertArray[$ik]),$prec);
			}
			else{//Aktuelle Einheit im Gramm-Bereich
				if($ext_WaegTemp !='')$ext_G_KorrArray[$ik]=round($ext_G_Array[$ik] +(($ext_WaegTemp-(float)$KalibrierTemperatur) * (float)$KorrekturwertArray[$ik]));
				elseif($ext_T_Out !='')$ext_G_KorrArray[$ik]=round($ext_G_Array[$ik] +(($ext_T_Out-(float)$KalibrierTemperatur) * (float)$KorrekturwertArray[$ik]));
			}
		}
		else $ext_G_KorrArray[$ik]=$ext_G_Array[$ik];
	}

	if($M_Type==1){//Datensatz erzeugen normaler beelogger
		$datensatz=$zeit.",".$ext_T_InArray[0].",".$ext_T_Out.",".$ext_F_InArray[0].",".$ext_F_Out.",".$ext_L.",".$ext_G_KorrArray[0].",";

		if($ext_VB !="")$datensatz .=$ext_VB .",".$ext_VS.",".$ext_Service;//Datensatz normal
//		else $datensatz .=$ext_B_In.",".$ext_B_Out.",".$BienenDifferenzM;//Datensatz für Bienenz.
		else $datensatz .=$ext_B_In.",".$ext_B_Out.",".$ext_Service;//Datensatz für Bienenz.

		$datensatz .=",".$ext_G_Array[0].",".$ext_Aux1.",".$ext_Aux2.",".$ext_Aux3.",".",".",".$timestamp."\r\n";
		$fp=fOpen('beelogger.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		$fp=fOpen('month.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		$fp=fOpen('week.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		if($Answer AND(date("G")<8))CSVbuilder_log();
		FaceOut();
	}//if($M_Type==1)

	if($M_Type>1){//------- Gesamtdaten Multi-----------------
		$datensatz=$zeit.",".$ext_WaegTemp.",".$ext_T_Out.",".$ext_F_Out.",".$ext_Service.",".$ext_L.",".$ext_G_KorrArray[0].",".$ext_G_KorrArray[1];
		$i=2;
		while($i<$M_Type){$datensatz .=",".$ext_G_KorrArray[$i];$i++;}
		$datensatz .=",".$ext_T_InArray[0].",".$ext_T_InArray[1];
		$i=2;
		while($i<$M_Type){$datensatz .=",".$ext_T_InArray[$i];$i++;}
		$datensatz .=",".$ext_F_InArray[0].",".$ext_F_InArray[1];
		$i=2;
		while($i<$M_Type){$datensatz .=",".$ext_F_InArray[$i];$i++;}
		$datensatz .=",".$ext_VB.",".$ext_VS.",".$ext_G_Array[0].",".$ext_G_Array[1];
		$i=2;
		while($i<$M_Type){$datensatz .=",".$ext_G_Array[$i];$i++;}
		$datensatz .=",".$ext_Aux1.",".$ext_Aux2.",".$ext_Aux3.",".",".",".$timestamp."\r\n";
		$fp=fOpen('beelogger.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		$fp=fOpen('month.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		$fp=fOpen('week.csv',"a");fWrite($fp,$datensatz);fClose($fp);
		if($Answer AND(date("G")<8))CSVbuilder_log();

		for($i=0;$i<$M_Type;$i++){//Unterbeelogger
			$i_i=$i+1;
				$datensatz=$zeit.",".$ext_T_InArray[$i].",".$ext_T_Out.",".$ext_F_InArray[$i].",".$ext_F_Out.",".$ext_L.",".$ext_G_KorrArray[$i].",".$ext_VB.",".$ext_VS.",".$ext_Service.",".$ext_G_Array[$i];
				$datensatz .=",".$ext_Aux1.",".$ext_Aux2.",".$ext_Aux3.",".",".",".$timestamp."\r\n";

				$u_dir='../beelogger'.$M_Sign.$M_Num."_".$i_i.'/';
				$CSVFile=$u_dir.'beelogger.csv';$fp=fOpen($CSVFile,"a");fWrite($fp,$datensatz);fClose($fp);
				$CSVFile=$u_dir.'month.csv';$fp=fOpen($CSVFile,"a");fWrite($fp,$datensatz);fClose($fp);
				$CSVFile=$u_dir.'week.csv';$fp=fOpen($CSVFile,"a");fWrite($fp,$datensatz);fClose($fp);
				if($Answer AND(date("G")<8))CSVbuilder_log($u_dir);
				FaceOut($u_dir);
		}
	}
	if($ext_Show==1)echo"<br><br>".$LAs[32].": ".$datensatz."<br>";



//Anmerkungen erzeugen:	Für alle beelogger
//alte NotesWerte
$LetzterNoteNameArray=array("","","","","","");
$LetzterNoteSensorArray=array(0,0,0,0,0,0);
$LetzteNoteTimestampArray=array($Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min,$Date_Min);
	for($i=0;$i<$numMulti;$i++){
		$ServiceNoteAktualisierungsStamp=(int)$Date_Min;
		$GewichtsNoteAktualisierungsStamp=(int)$Date_Min;
		if($i==0)$NoteFile="notes.csv";
		else $NoteFile='../beelogger'.$M_Sign.$M_Num."_".$i.'/notes.csv';

		if(file_exists($NoteFile)){//Letzte Werte aus notes.csv auslesen
			$input=$NoteFile;
			$array=file($input);
			$j=count($array);
			$LastStamp=(int)$Date_Min;
			$try=0;
			if($j>0){
			 while($j--){
				$what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
				if($x[$s-1] !=''){
					if($x[$s-1]>$LastStamp){$y=$x;$LastStamp=$x[$s-1];}//hole letzte note
					if($j==0 OR $try>10){//in den letzten 10
						$LetzterNoteSensorArray[$i]=$y[0];
						$LetzterNoteNameArray[$i]=$y[2];
						$LetzteNoteTimestampArray[$i]=$y[$s-1];
						break;
					}
				}
				$try++;
			 }
			}
		}//file_exists...notes.csv

		if($AktualisierungStampArray[$i] !=$Date_Min){//altes Datum: keine Anmerkung
			$note='*';
			if($AutoAnmerkungenErzeugen=="aktiviert"){
				if($i==0 AND $M_Type !=1){//MultibeeloggerMain
					if((strpos($LetzterNoteNameArray[0],"n_Durchsicht")>0)&& $LetzterNoteSensorArray[0]==3)$ServiceNoteAktualisierungsStamp=$LetzteNoteTimestampArray[0];
				}
				else{//UnterMultibeelogger + Normal
					if((strpos($LetzterNoteNameArray[$i],"n_Durchsicht")>0)&& $LetzterNoteSensorArray[$i]==8)$ServiceNoteAktualisierungsStamp=$LetzteNoteTimestampArray[$i];
					if((strpos($LetzterNoteNameArray[$i],"n_Durchsicht")===0)&& $LetzterNoteSensorArray[$i]==5)$GewichtsNoteAktualisierungsStamp=$LetzteNoteTimestampArray[$i];
				}
				if(($M_Type==1)OR($i>0)){//Für MultibeeloggerMain gibt es keine Gewichtsautoanmerkungen
//Gewichtsinterrupt: Hier wird eine Auto-Anmerkung erzeugt
					$i_g=$i; if($i >0){ $i_g=$i-1;}//Single/Multi-kompatibilität
					$GewichtsDifferenz=(float)$ext_G_KorrArray[$i_g] - (float)$LetztesGewichtArray[$i];
					$absGewdiff=abs($GewichtsDifferenz);
					if($absGewdiff<100)$GewichtsDifferenz=round($GewichtsDifferenz,2);//es muss ein kg-Wert sein
					if($AnmerkungGewichtsDifferenzMax=="")$AnmerkungGewichtsDifferenzMax=100; //Abwärtkompatibel
					if(($absGewdiff>(float)$AnmerkungGewichtsDifferenz)AND($absGewdiff<(float)$AnmerkungGewichtsDifferenzMax))
					{//es liegt eine entscheidende Gewichtsänderung vor z.B. neue Zarge/Ernte/Durchsicht
						if($GewichtsDifferenz>0)$GewichtsÄnderungsArt=$LAs[58];//Gewichtszuwachs
						else $GewichtsÄnderungsArt=$LAs[59];//Gewichtsabnahme
						if(($timestamp>=($GewichtsNoteAktualisierungsStamp +(60*(int)$AnmerkungZeitDifferenz))OR $GewichtsNoteAktualisierungsStamp=="")AND $AutoAnmerkungenErzeugen=="aktiviert" AND(int)$ext_Service<99)$note="5,".$zeit.",?G&".$GewichtsÄnderungsArt.$LAs[60]." ".$absGewdiff.$GewichtsEinheit." ".$LAs[50].",";
//Anmerkung im Sensor Gewicht erzeugen
//Es soll nur eine Anmerkung pro Gewichtsänderung erzeugt werden - Deshalb wird x Minuten gewartet bevor eine zweite Anmerkung erscheint
						if((int)$ext_Service<99 AND(int)$LetzterService<99){//Arbeitsschalter inaktiv - nur dann Gewichtsanmerkung
						$notezeile=$note.$timestamp."\r\n";
						if($note !='*'){$fp=fOpen($NoteFile,"a");fWrite($fp,$notezeile);fClose($fp);}
						}
					}
				}
			}

			if(($AutoServiceAnmerkung=="true")AND($note=='*')){//ServiceIcon aktiviert
				$ext_SM_int=(int)$ext_Service;
				if($timestamp>=(2400+(int)$ServiceNoteAktualisierungsStamp)AND($ext_SM_int==99 OR $ext_SM_int>240)){//Durchsicht erkannt und 40 min warten vorm nächsten Icon
//MultiMainbeelogger im ServiceSensor
					if($i==0 AND $M_Type !=1)$note="3,".$zeit.",../beelogger_icons/n_Durchsicht.png&".$LAs[61].",";
			//Normalbeelogger und Unterbeelogger im Gewichtssensor / Unterbeelogger $i >0 , Normalbeelogger==0
					elseif(($M_Type==1)OR($i>0))$note="5,".$zeit.",../beelogger_icons/n_Durchsicht.png&".$LAs[61].",";//Arbeitsschalter aktiviert
				}
				$notezeile=$note.$timestamp."\r\n";
				if($note !='*'){$fp=fOpen($NoteFile,"a");fWrite($fp,$notezeile);fClose($fp);}
			}
		}//letztesDatum
	}//for anmerkungen
//Externe Daten ins Array packen
	for($it=0;$it<$numMulti;$it++){
		if(($it==0)AND($M_Type >1)){continue;}//no Data from MainMulti
		$ib=$it;if($it>0)$ib --;//Multi Duo...
		if($ext_G_KorrArray[$ib] !='')$x_G_Korr=number_format((float)$ext_G_KorrArray[$ib],2,'.','');
		else $x_G_Korr="";

	 if($KeeperArray[$ib]=="aktiviert" OR $BeepArray[$ib]=="aktiviert"){//Beep /ibeeKeeper
		$ExternData="time=".$timestamp."&t=".$ext_T_Out."&t_i=".$ext_T_InArray[$ib]."&h=".$ext_F_Out."&h_i=".$ext_F_InArray[$ib]."&l=".$ext_L."&bv=".$ext_VB."&weight_kg=".$x_G_Korr;
		if($ext_Aux1 !="" AND((int)$ext_Aux1>500))$ExternData .="&p=".$ext_Aux1;
		if($ext_B_In !='')$ExternData .="&bc_i=".$ext_B_In;
		if($ext_B_Out !='')$ExternData .="&bc_o=".$ext_B_Out;
		if($ext_rssi !='')$ExternData .="&rssi=".$ext_rssi;
		elseif($ext_Service<0)$ExternData .="&rssi=".$ext_Service;
	 }

	 if($BeepArray[$ib]=="aktiviert"){//Beep
		$BeepDataArray[$ib][$ExternDataCounter]=$ExternData;
	 }else $BeepDataArray[$ib][$ExternDataCounter]="";

	 if($KeeperArray[$ib]=="aktiviert"){//ibeeKeeper
	  if($iBeeKeeperArrayrain[$ib]=="rain.png"){if($ext_Aux2 !='')$ExternData .="&rain_mm=".$ext_Aux2;}
		$KeeperDataArray[$ib][$ExternDataCounter]=$ExternData;
	 } else $KeeperDataArray[$ib][$ExternDataCounter]="";

	if($ThingsPeakArray[$ib]=="aktiviert"){//ThingsPeak
		$ThingsPeakHelpArray=array("",$ext_T_InArray[$ib],$ext_T_Out,$ext_F_InArray[$ib],$ext_F_Out,$ext_L,$x_G_Korr,$ext_VB,$ext_VS,$ext_Service,$ext_G_Array[$ib],$ext_Aux1,$ext_Aux2,$ext_Aux3);
		$ThingsPeakData=array(strval($timestamp),
		$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][1])],$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][2])],
		$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][3])],$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][4])],
		$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][5])],$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][6])],
		$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][7])],$ThingsPeakHelpArray[($ThingsPeakSensorenArray[$ib][8])]);
		$ThingsPeakDataArray[$ib][$ExternDataCounter]=$ThingsPeakData;//zum bulken in ExternDataOut()
	 }
	}
	$ExternDataCounter++;
//Ende externe Daten vorbereiten

}//store
else{
	if($ext_Show==1)echo "<br><br><b>old timestamp: ".$timestamp." . ".$akttime."</b>";
	if($timestamp <	(int)$Date_Min){echo $TimeSync."15Aok *";exit();}
}

if($LORA==1 && file_exists("lora_downlink.php")){include_once("lora_downlink.php");lora_downlink($MessIntervall);}
if($NextService !=''){if(time()>((int)$NextServiceP_Time + 86400))$NextService="";}//Pegelmessung nur für 24 Stunden
if($Answer==1){echo $TimeSync.$MessIntervall.$EESendeIntervall.$NextService."ok *";}//next send

if($ext_Show==1){
	if(!file_exists("beelogger_ini.php"))echo "<br>".$LAs[0]."!";
	if($Softwareversion !="")echo "<br>".$LAs[7].": ".$Softwareversion.":	".date("Y/m/d H:i",filemtime("beelogger_log.php"))."<br>";
	if($NeubeeloggerSketchID !="")echo $LAs[8].": ".$NeubeeloggerSketchID."<br><br>";

	if($M_Type==1){
		if($ext_VB !="")echo"<br><blockquote>".$LAs[9]."!";
		else echo"<br><blockquote>".$LAs[10]."!";
		echo "<br>".$LAs[11].":<br><br>";
		echo "<b>".$Sensoren[0].": </b>".$ext_T_InArray[0]."<br><b>".$Sensoren[5].": </b>".$ext_T_Out."<br><b>".$Sensoren[10].": </b>".$ext_F_InArray[0]."<br><b>".$Sensoren[15].": </b>".$ext_F_Out."<br><b>".$Sensoren[20].": </b>".$ext_L."<br><b>".$Sensoren[25].": </b>".$ext_G_KorrArray[0]."<br>";
		echo"<b>".$LAs[12].": </b> ".$ext_VB.$ext_B_In."<br><b>".$LAs[13].": </b> ".$ext_VS.$ext_B_Out."<br><b>".$LAs[14].": </b>".$BienenDifferenzM.$ext_Service."</blockquote>";
		if($ext_Aux1 !="")echo"<br><b>Aux1 : </b>".$ext_Aux1;
		if($ext_Aux2 !="")echo"<br><b>Aux2 : </b>".$ext_Aux2;
		if($ext_Aux3 !="")echo"<br><b>Aux3 : </b>".$ext_Aux3;
		if($ext_G_KorrArray[0] !=$ext_G_Array[0])echo "<br><b>".$LAs[15].": </b>".$ext_G_Array[0]."<br><b>".$LAs[16].": </b>".$ext_G_KorrArray[0];
	}//$M_Type==1

	if($M_Type>1){
		echo "<br><blockquote>".$LAs[18].": ".$M_Name."</b> ".$LAs[19].":<br><br><table border=1><tr><td></td><td><b> ".$LAs[20]." 1 </b></td><td><b> ".$LAs[20]." 2 </b></td>";
		if($M_Type>2)echo "<td><b> ".$LAs[20]." 3 </b></td>";
		if($M_Type>3)echo "<td><b> ".$LAs[20]." 4 </b></td>";
		if($M_Type>4)echo "<td><b> ".$LAs[20]." 5 </b></td>";
		if($M_Type>5)echo "<td><b> ".$LAs[20]." 6 </b></td>";
		echo"</tr><tr><td><b>".$LAs[21]."</b></td><td>".$ext_T_InArray[0]."</td><td>".$ext_T_InArray[1]."</td>";
		if($M_Type>2)echo "<td>".$ext_T_InArray[2]."</td>";
		if($M_Type>3)echo "<td>".$ext_T_InArray[3]."</td>";
		if($M_Type>4)echo "<td>".$ext_T_InArray[4]."</td>";
		if($M_Type>5)echo "<td>".$ext_T_InArray[5]."</td>";
		echo"</tr><tr><td><b>".$LAs[22]."</b></td><td>".$ext_F_InArray[0]."</td><td>".$ext_F_InArray[1]."</td>";
		if($M_Type>2)echo "<td>".$ext_F_InArray[2]."</td>";
		if($M_Type>3)echo "<td>".$ext_F_InArray[3]."</td>";
		if($M_Type>4)echo "<td>".$ext_F_InArray[4]."</td>";
		if($M_Type>5)echo "<td>".$ext_F_InArray[5]."</td>";
		echo"</tr><tr><td><b>".$LAs[23]."</b></td><td>".$ext_G_Array[0]."</td><td>".$ext_G_Array[1]."</td>";
		if($M_Type>2)echo "<td>".$ext_G_Array[2]."</td>";
		if($M_Type>3)echo "<td>".$ext_G_Array[3]."</td>";
		if($M_Type>4)echo "<td>".$ext_G_Array[4]."</td>";
		if($M_Type>5)echo "<td>".$ext_G_Array[5]."</td>";
		echo"</tr><tr><td><b>".$LAs[24]."</b></td><td>".$ext_G_KorrArray[0]."</td><td>".$ext_G_KorrArray[1]."</td>";
		if($M_Type>2)echo "<td>".$ext_G_KorrArray[2]."</td>";
		if($M_Type>3)echo "<td>".$ext_G_KorrArray[3]."</td>";
		if($M_Type>4)echo "<td>".$ext_G_KorrArray[4]."</td>";
		if($M_Type>5)echo "<td>".$ext_G_KorrArray[5]."</td>";
		echo"</tr></table><br><b>".$LAs[25].": </b>".$ext_T_Out."<br><b>".$LAs[26].": </b>".$ext_WaegTemp."<br><b>".$LAs[27].": </b>".$ext_F_Out."<br><b>".$LAs[28].": </b>".$ext_L."<br><b>".$LAs[29].": </b>".$ext_Service."<br><b>".$LAs[30].": </b>".$ext_VB."<br><b>".$LAs[31].": </b>".$ext_VS."<br><br><b>Aux1: </b>".$ext_Aux1."<br><b>Aux2: </b>".$ext_Aux2."<br><b>Aux3: </b>".$ext_Aux3."<br></blockquote>";
	}

	if($IntervallSendeSteuerung !="deaktiviert")echo "<br>".$LAs[33]." '".$IntervallSendeSteuerung."' ".$LAs[34].": ".$MessIntervall. " ".$LAs[35].".";
	if(isset($note)){if($note !="*")echo "<br><br>".$LAs[36].": ".$note;}
	if($ExternData[0] !="")echo "<br><br>".$LAs[37].": ".$BeepId."<br> iBeeKeeper: ".$KeeperIdArray[0]."<br>";

	for($uss=0;$uss<$ExternDataCounter;$uss++){
		for($ib=0;$ib<$M_Type;$ib++){if($BeepArray[$ib]=="aktiviert"){echo "<br><br>".$LAs[20]." No.".$ib.": ".$LAs[37].": ".$BeepIdArray[$ib]." : ";echo $BeepDataArray[$ib][$uss];}}
		for($ib=0;$ib<$M_Type;$ib++){if($KeeperArray[$ib]=="aktiviert"){echo "<br><br>".$LAs[20]." No.".$ib.": -> iBeeKeeper &nbsp; ".$KeeperIdArray[$ib]." : ";echo $KeeperDataArray[$ib][$uss];}}
	}
	if($Answer==1)echo "<br>".$LAs[38].": ".$TimeSync.$MessIntervall.$EESendeIntervall.$NextService."ok *";

//Anzeige alte Zeitstempel bzw. Datenabstand unter 5 Minuten
	$akttime=(int)$AktualisierungStampArray[0];
	if($timestamp<($akttime+200))echo "<br><br><b>old timestamp_: ".$timestamp." . ".$akttime."</b>";
	if($timestamp <=(int)$Date_Min)return;
}//$ext_Show


//Schwarmalarm 2.0
if($store){

$EmailSchwarmAlarmArray=array("","","","","","");
$DifferenzGewichtArray=array("","","","","","");
$DifferenzGewichtMaxArray=array("","","","","","");
$PushSchwarmAlarmArray=array("","","","","","");
$ReferenzZeitArray=array("","","","","","");

if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");

	if($M_Type==1){//Single
		if($EmailSchwarmAlarm !="")$EmailSchwarmAlarmArray[0]=$EmailSchwarmAlarm;
		if($PushSchwarmAlarm !="")$PushSchwarmAlarmArray[0]=$PushSchwarmAlarm;
		if($DifferenzGewicht !="")$DifferenzGewichtArray[0]=(float)$DifferenzGewicht;
		if($DifferenzGewichtMax !="")$DifferenzGewichtMaxArray[0]=(float)$DifferenzGewichtMax;
		if($ReferenzZeit !="")$ReferenzZeitArray[0]=(int)$ReferenzZeit;
	}

	$Sw=0;
	while($Sw<$M_Type){
		if(($EmailSchwarmAlarmArray[$Sw]=="aktiviert")OR($PushSchwarmAlarmArray[$Sw]=="aktiviert")){
			if((int)$ext_Service<99 AND(int)$LetzterService<99){//Arbeitsschalter inaktiv
			$Swx=$Sw;
				if($M_Type==1)$WarnFile="Schwarminfo.php";
				else{//Multi
			$Swx++;
			$WarnFile='../beelogger'.$M_Sign.$M_Num."_".$Swx.'/Schwarminfo.php';
		}
		$SchwarmAlarmMessage="-";
				$SwInfo=array("<?php\n","//Datei zur Ermittlung eines Schwarmalarms\n","","","");
				if(!file_exists($WarnFile)){//Falls Datei nicht existiert - anlegen !
					$SwInfo[2]='$ZeitpunktErsterGewichtsverlust="";'."\n";
					$SwInfo[3]='$LetztesGewichtVormVerlust="";'."\n";
					$SwInfo[4]='$SchwarmAlarmMessage="'.$SchwarmAlarmMessage.'";'."\n";
					$fp=fOpen($WarnFile,"w+");
					foreach($SwInfo as $values)fwrite($fp,$values);
					$OK=fwrite($fp,"?>");fClose($fp);
				}
				include($WarnFile);//Damit wird eventuelle $SchwarmAlarmMessage aus dem Speicher geholt
				$Schwarm="false";

//Feststellung erster Gewichtsverlust
				$Gewichtsverlust=((float)$LetztesGewichtArray[$Swx] - (float)$ext_G_KorrArray[$Sw]);
				if(($Gewichtsverlust>$DifferenzGewichtArray[$Sw])AND($Gewichtsverlust<$DifferenzGewichtMaxArray[$Sw])){
//Gewichtsverlust registriert
					$SwInfo[2]='$ZeitpunktVormErstenGewichtsverlust="'.$AktualisierungStampArray[$Swx].'";'."\n";
					$SwInfo[3]='$LetztesGewichtVormVerlust="'.$LetztesGewichtArray[$Swx].'";'."\n";
					$SwInfo[4]='$SchwarmAlarmMessage="'.$SchwarmAlarmMessage.'";'."\n";
					$fp=fOpen($WarnFile,"w+");
					foreach($SwInfo as $values)fwrite($fp,$values);
					$OK=fwrite($fp,"?>");fClose($fp);

//Check, ob bereits Zeitdifferenz überschritten
					$MaxZeitSeitSchwarm=$timestamp - (int)$AktualisierungStampArray[$Swx];//in ticks=sekunden
					$MaxZeitSeitSchwarm=round(($MaxZeitSeitSchwarm/60),0).' '.$LAs[39].' '.($MaxZeitSeitSchwarm%60).' '.$LAs[40];
					if($timestamp>=($AktualisierungStampArray[$Swx] + $ReferenzZeitArray[$Sw]*60)){
						$Schwarm="true";
						if(intval($SchwarmAlarmMessage[3])>=1){//Es gab bereits mind. einen Schwarmalarm -Neuen Index rausfinden
							$SchwarmAlarmNewMessage="No.".(intval($SchwarmAlarmMessage[3])+1)." ".$LAs[41].": ".date("d.m H:i",$AktualisierungStampArray[$Swx]);
						}
						else{//Erster Schwarmalarm
							$SchwarmAlarmNewMessage="No.1 ".$LAs[41].":".date("d.m",$AktualisierungStampArray[$Swx])." ".$LAs[42]." ".date("H:i",$AktualisierungStampArray[$Swx]);
						}
					}//If Zeit direkt überschritten, Schwarmabgang wahrscheinlich - Nachrichten raus und Reset für Nachschwarm
				}//if Gewichtsverlust größer als Differenzgewicht

				if($ZeitpunktVormErstenGewichtsverlust !=""){//Ja, erster Gewichtsverlust war da...
						$MaxZeitSeitSchwarm=$timestamp - $ZeitpunktVormErstenGewichtsverlust;//in ticks=sekunden
						$MaxZeitSeitSchwarm=floor($MaxZeitSeitSchwarm/60).' '.$LAs[39].' '.($MaxZeitSeitSchwarm%60).' '.$LAs[40];
						if($timestamp>=($ZeitpunktVormErstenGewichtsverlust + $ReferenzZeitArray[$Sw]*60)){
							$Gewichtsverlust=((float)$LetztesGewichtVormVerlust - (float)$ext_G_KorrArray[$Sw]);
							if(($Gewichtsverlust>=$DifferenzGewichtArray[$Sw])AND($Gewichtsverlust<$DifferenzGewichtMaxArray[$Sw])){
								$Schwarm="true";//Der Gewichtsverlust hält an !!!
//Schwarmabgang wahrscheinlich - Nachrichten raus und Reset für Nachschwarm
							}//if Gewichtsverlust immer noch da
							else{//Kommando zurück weil :	Ursprungsgewicht ist ungefähr wieder erreicht
								$SwInfo[2]='$ZeitpunktVormErstenGewichtsverlust="";'."\n";
								$SwInfo[3]='$LetztesGewichtVormVerlust="";'."\n";
								$SwInfo[4]='$SchwarmAlarmMessage="'.$SchwarmAlarmMessage.'";'."\n";
								$fp=fOpen($WarnFile,"w+");
								foreach($SwInfo as $values)fwrite($fp,$values);
								$OK=fwrite($fp,"?>");fClose($fp);
							}//else Kommando zurück
						}//if Timestamp>=....

						if(intval($SchwarmAlarmMessage[3])>=1){//Es gab bereits mind. einen Schwarmalarm
							$SchwarmAlarmNewMessage="No.".(intval($SchwarmAlarmMessage[3])+1)." ".$LAs[41]." ".date("d.m",$ZeitpunktVormErstenGewichtsverlust)." ".$LAs[42]." ".date("H:i",$ZeitpunktVormErstenGewichtsverlust);
						}
						else{//Erster Schwarmalarm
							$SchwarmAlarmNewMessage="No.1 ".$LAs[41].": ".date("d.m",$ZeitpunktVormErstenGewichtsverlust)." ".$LAs[42]." ".date("H:i",$ZeitpunktVormErstenGewichtsverlust);
						}
					}//if($ZeitpunktVormErstenGewichtsverlust !="")

					if($Schwarm=="true"){
						$SwInfo[2]='$ZeitpunktVormErstenGewichtsverlust="";'."\n";
						$SwInfo[3]='$LetztesGewichtVormVerlust="";'."\n";
						$SwInfo[4]='$SchwarmAlarmMessage="'.$SchwarmAlarmNewMessage.'";'."\n";
						$fp=fOpen($WarnFile,"w+");
						foreach($SwInfo as $values)fwrite($fp,$values);
						$OK=fwrite($fp,"?>");fClose($fp);

						if($M_Type==1)$MailPushbeeloggerNummer=str_replace("beelogger","",$beelg);
						else $MailPushbeeloggerNummer=$M_Name.$M_Num."_".$Swx;

//EMAIL- Nachricht
						if(($EmailSchwarmAlarmArray[$Sw]=="aktiviert")AND($Absender_Email !="absender@meineDomain.de")AND($Empfaenger_Email !="empfaenger@meineDomain.de")){
						$Mailbetreff=$LAs[43].": beelogger".$MailPushbeeloggerNummer;
						$hdr="From:".$Absender_Email."\r\n";
						$hdr .="Return-Path:".$Absender_Email."\r\n";
						$hdr .="X-Mailer: PHP/".phpversion()."\r\nMime-Version: 1.0\r\n";
						$hdr .="Content-type: text/plain; charset=ISO-8859-1\r\n";
						$Nachricht=$LAs[45].":\n\n";
						$Nachricht .=$LAs[46]." ".$Gewichtsverlust.$GewichtsEinheit.$LAs[47].": ".$MaxZeitSeitSchwarm;
						$actual_link=strstr("{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'beelogger_log',TRUE)."beelogger_show.php";//no https
						$Nachricht .="\nLink	".$actual_link;
						$ret_path='"-f'.$Absender_Email.'"';//mail($email,$subject,$addletter,$headers, "-fno_replay@test.com");
							mail($Empfaenger_Email,$Mailbetreff,$Nachricht,$hdr,$ret_path);
						}//if EmailAlarm aktiviert

//PUSH-Nachricht
						if($PushSchwarmAlarmArray[$Sw]=="aktiviert"){
							$actual_link=strstr("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'beelogger_log',TRUE)."beelogger_show.php";
							if(!(isset($PushSchwarmPriorityArray)))$PushSchwarmPriorityArray=array("","","","","","");
							if($PushSchwarmPriorityArray[$Sw]=="")$PushSchwarmPriorityArray[$Sw]=0;
				if(($PushToken!="PushToken")AND($PushToken !="")){
				curl_setopt_array($ch=curl_init(),array(
				CURLOPT_URL => "https://api.pushover.net/1/messages.json",
				CURLOPT_POSTFIELDS => array(
								"token" => $PushToken,
								"user" => $PushUser,
								"message" => $LAs[48]." beelogger".$MailPushbeeloggerNummer." - ".$LAs[46]." ".$Gewichtsverlust.$GewichtsEinheit.". ".$LAs[47].": ".$MaxZeitSeitSchwarm." Link: ".$actual_link,
				"priority" => $PushSchwarmPriorityArray[$Sw],
				),
				CURLOPT_SAFE_UPLOAD => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_RETURNTRANSFER => true));
				curl_exec($ch);
				curl_close($ch);
				}
				if(($T_Chat_Id!="Id")AND($T_Chat_Id!="")){
								$message=$LAs[48]." beelogger".$MailPushbeeloggerNummer." - ".$LAs[46]." ".$Gewichtsverlust.$GewichtsEinheit.". ".$LAs[47].": ".$MaxZeitSeitSchwarm." Link: ".$actual_link;
				$data=['chat_id' => $T_Chat_Id,'text' => $message];
				curl_setopt_array($ch=curl_init(),array(
				CURLOPT_URL => "https://api.telegram.org/bot$T_Token/sendMessage?" .
				http_build_query($data),
				CURLOPT_SAFE_UPLOAD => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_RETURNTRANSFER => true));
				curl_exec($ch);
				curl_close($ch);
				}
						}//if PushSchwarmAlarm aktiviert

//Anmerkung erzeugen
						$note="5,".$zeit.",../beelogger_icons/n_Schwarmabgang.png&".$LAs[44]." ".$SchwarmAlarmNewMessage." ".$LAs[49]." ".$Gewichtsverlust.$GewichtsEinheit." ".$LAs[50].",";
						if($M_Type==1){
							$NoteFile="./notes.csv";
						}else{
								$NoteFile='../beelogger'.$M_Sign.$M_Num."_".$Swx.'/notes.csv';
						}
						$notezeile=$note.$timestamp."\r\n";
						$fp=fOpen($NoteFile,"a");fWrite($fp,$notezeile);fClose($fp);
						if(file_exists($WarnFile))unlink($WarnFile);//warnung.txt wieder löschen, da Schwarm durch
					}//If Schwarm=true

				if($ext_Show==1){
					echo "<br><br><br>".$LAs[44]."-INFO:";
					if($M_Type !=1)echo $Sw;
					if($EmailSchwarmAlarmArray[$Sw]=="aktiviert"){
						echo"<br>Email-".$LAs[44];
						if($M_Type !=1)echo $Sw;
						echo " ".$LAs[51]."!";
					}
					if($PushSchwarmAlarmArray[$Sw]=="aktiviert"){
						if(($PushToken!="PushToken")AND($PushToken !="")){
							echo"<br>Push-".$LAs[44];
							if($M_Type !=1)echo $Sw;
							echo " ".$LAs[51]."!";
						} else {echo"<br>Telegram-".$LAs[44]." ".$LAs[51]."!<br>";}
          }
					if($Gewichtsverlust>0)echo "<br>".$LAs[46].": ".$Gewichtsverlust.$GewichtsEinheit;
					echo "<br>".$LAs[52].": ".$DifferenzGewichtArray[$Sw].$GewichtsEinheit." ".$LAs[53].".";
					if($MaxZeitSeitSchwarm !="")echo "<br> ".$LAs[47]." ".$MaxZeitSeitSchwarm;
					echo "<br>".$LAs[54].": ".$ReferenzZeitArray[$Sw].' '.$LAs[35].' ';
					if($ZeitpunktVormErstenGewichtsverlust !="")echo "<br> ".$LAs[55].": ".date("d.m.Y H:i:s",$ZeitpunktVormErstenGewichtsverlust)." ".$LAs[56]."!";
				}//ext_Show
			}//Arbeitsschalter
		}//if	SchwarmEmailalarm oder push aktiviert
		$Sw++;
	}//while Schleife Schwarmalarm
}//ENDE Schwarmalarm 2.0

}//Ende Auswertung

//############################################
function FaceOut($beeOrdner=""){

	if(!file_exists($beeOrdner."beelogger_ini.php"))return;
	include($beeOrdner."beelogger_ini.php");

	$FA=array("","","","","","","","","","","","");
	$FA[0]="<?php\n".'$sensor_anzahl='.(sizeof($Sensoren)/5).";\n";
	$FA[1]='$sensor_aktualisierung="no";'."\n";//keine week.csv existent
	$FAHelp='$sensor_bezeichnung=array('."'";
	for($i=0;$i<((sizeof($Sensoren)/5)-1);$i++){ $FAHelp .=$Sensoren[$i*5+0]."','"; }
	$FA[2]=$FAHelp.$Sensoren[$i*5+0]."');\n";
	$FA[3]='$sensor_wert=array('."'no'".");\n";
	$FA[4]='$sensor_wert_1=array('."'no'".");\n";
	$FA[5]='$sensor_wert_24=array('."'no'".");\n";
	$FA[6]='$sensor_icon=array('."'no'".");\n";
	$FA[7]='$sensor_intervall="no";'."\n";
	$FA[8]='$rain_for_24="no";'."\n";
	$FA[9]='$rain_for_7d="no";'."\n";
	$FAHelp='$sensor_einheit=array('."'";
	for($i=0;$i<((sizeof($Sensoren)/5)-1);$i++){ $FAHelp .=$Sensoren[$i*5+4]."','"; }
	$FA[10]=$FAHelp.$Sensoren[$i*5+4]."');\n";
	$FA[11]="?>";

	$CSVFile=$beeOrdner."week.csv";
	if(file_exists($CSVFile)){//eine week.csv gefunden
	 $array=file($CSVFile);

	 $LetzterDatensatz=0;
	 $VorletzterDatensatz=0;

	 $j=count($array);
	 if($j>2){
		$FAHelp='$sensor_wert=array('."'";
		while($j--){
		 $what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
		 if($x[$s-1] !=''){
			$LetzterDatensatz=(int)$x[$s-1];
			$FA[1]='$sensor_aktualisierung="'.$x[$s-1].'";//'.date("Y/m/d H:i:s",($x[$s-1]))."\n";
			for($k=1;$k<($s-2);$k++){$FAHelp .=$x[$k]."','";}
			$FA[3]=$FAHelp.$x[$k]."');\n";
			break;
		 }
		}
		$j--;
		if($j>1){
		 $FAHelp='$sensor_wert_1=array('."'";
		 $what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
		 if($x[$s-1] !=''){
			$VorletzterDatensatz=(int)$x[$s-1];
			for($k=1;$k<($s-2);$k++){$FAHelp .=$x[$k]."','";}
			$FA[4]=$FAHelp.$x[$k]."');\n";
		 }
		}
//24 Stundenwert ermitteln
		$FAHelp='$sensor_wert_24=array('."'";
		while($j--){
			$what=trim($array[$j]);$x=explode(",",$what);$s=count($x);
			if($x[$s-1] !=''){
				$zeitstempel=(int)$x[$s-1];
				if($zeitstempel <=($LetzterDatensatz - 86400)AND $zeitstempel>($LetzterDatensatz - 90000)){
					for($k=1;$k<($s-2);$k++){$FAHelp .=$x[$k]."','";}
					$FA[5]=$FAHelp.$x[$k]."');\n";
					break;
				}
				if($zeitstempel<($LetzterDatensatz - 90000))break;
		 }
		}
	 }
//Ermittlung ob Regensensor vorhanden ---
	 $Size=count($Icon);
	 while($Size--){if($Icon[$Size]=="rain.png" OR $Size==0)break;}
	 if($Size>0){//Regensensor wahrscheinlich angelegt, Icon vorhanden
		$RegenSensorStelle=$Size+1;
		$RegenSumme24h=0;
		$RegenSumme7d=0;

		$i=count($array);
		if($i>12){
		while($i--){
			$what=trim($array[$i]);$x=explode(",",$what);$s=count($x);
			if($x[$RegenSensorStelle] !='')$RegenSumme7d +=$x[$RegenSensorStelle];
			if((int)$x[$s-1]>=($LetzterDatensatz-86400)){if($LetzterDatensatz>0){ if($x[$RegenSensorStelle] !='')$RegenSumme24h +=$x[$RegenSensorStelle];}}
		}
		$FA[8]='$rain_for_24="'.$RegenSumme24h.'"'.";\n";
		$FA[9]='$rain_for_7d="'.$RegenSumme7d.'"'.";\n";
		}
	 }//Regen
	}//if CSVFile
	$FAHelp='$sensor_icon=array(';
	$f=count($Icon);
	for($fi=0;$fi<$f-1;$fi++){$FAHelp .="'".$Icon[$fi]."',";}
	$FA[6]=$FAHelp."'".$Icon[$fi]."');\n";

	if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");//Sonderfall Multibeelogger

//Berechnung der Serversendesteuerung--------------------------
	$MessIntervall="0";
	$MessIntervall=ServerZeitSteuerung();

	$Interval=(int)$MessIntervall;//Hilfsvariable
	if($EESendeIntervall=="A" AND $Interval<30)$data_intervall="60";
	if($EESendeIntervall=="A" AND $Interval>=30)$data_intervall="120";
	if($EESendeIntervall=="B" AND $Interval<=30)$data_intervall="120";
	if($EESendeIntervall=="B" AND $Interval>30)$data_intervall="240";
	if($EESendeIntervall=="C" AND $Interval<=30)$data_intervall="240";
	if($EESendeIntervall=="C" AND $Interval>30)$data_intervall="480";
	if($EESendeIntervall=="D" AND $Interval<=30)$data_intervall="480";
	if($EESendeIntervall=="D" AND $Interval>30)$data_intervall="960";
	if($MessIntervall=="0"){
		if($LetzterDatensatz !=0 AND $VorletzterDatensatz !=0)$data_intervall=round(($LetzterDatensatz-$VorletzterDatensatz)/ 60);//in Minuten
	}
	if($MessIntervall !="0")$FA[7]='$sensor_intervall="'.$data_intervall.'"'.";\n";

	$fp=fOpen($beeOrdner."beelogger_interface.php","w");
	foreach($FA as $values){fwrite($fp,$values);}
	fclose($fp);
}//Ende function FaceOut

//##############################################
function CSVbuilder_log($beeOrdner=""){//CSV Builder
	$input=$beeOrdner."month.csv";
	if(file_exists($input)==false)return;
	$array=file($input);
	$asize=count($array);
	if($asize>20){
	 while($asize--){
		$what=trim($array[$asize]);$x=explode(",",$what);$s=count($x);
		if($x[$s-1] !=''){//letzte Spalte=Zeitstempel abfragen
			$LastDate=(int)$x[$s-1];
			break;
		}
	 }
	 $L_m=$LastDate-2678400;//Monat etwa	60*60*24*31=2678400 Ticks
	 $L_w=$LastDate-604800;//Woche etwa	60*60*24*7=604800 Ticks

	 $fp_m=fOpen(($beeOrdner."month.csv"),"w");
	 $fp_w=fOpen(($beeOrdner."week.csv"),"w");

	 foreach($array as $val){
		$what=trim($val);$x=explode(",",$what);$s=count($x);//beelogger.csv Spalten pro Zeile
		if((int)$x[$s-1]>=$L_m)fwrite($fp_m,$val);
		if((int)$x[$s-1]>=$L_w)fwrite($fp_w,$val);
	 }
	fclose($fp_w);
	fclose($fp_m);
	}
}//Ende csv-Builder Log

//###################################
function ExternDataOut(){
	global $LAs,$ext_Show,$M_Type,$ExternDataCounter,$BeepArray,$BeepIdArray,$BeepDataArray,$KeeperApi,$KeeperArray,$KeeperIdArray,$KeeperDataArray,$ThingsPeakDataArray,$ThingsPeakArray,$ThingsPeakChannelArray,$ThingsPeakApiArray,$NeubeeloggerSketchID,$EE_Type;

 for($ia=0;$ia<$ExternDataCounter;$ia++){
	$externOut=0;
	for($ib=0;$ib<$M_Type;$ib++){
		if(($M_Type==1 AND $ib==0)OR($M_Type>1)){//nur für Single- oder Unterbeelogger aussenden - nicht für Master vom Multi
//Beep
			if(($BeepArray[$ib]=="aktiviert")AND($BeepIdArray[$ib] !="meineID" OR $BeepIdArray[$ib] !="" OR $BeepIdArray[$ib] !="meine BeepID")AND $ib<$M_Type){//wenn aktiviert
			if($_SERVER['SERVER_NAME']=="community.beelogger.de"){$externOut++;}
			$burl="https://api.beep.nl/api/sensors_auth?key=".$BeepIdArray[$ib];
			if(file_exists("../../Xx_BeepApiHeader.php")){
			$ch=curl_init($burl);//cURL ínit
			include("../../Xx_BeepApiHeader.php");
			curl_setopt($ch,CURLOPT_HEADER,0);//Header soll nicht in Ausgabe enthalten sein
			curl_setopt($ch,CURLOPT_POST,1);//POST-Request wird abgesetzt
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$BeepDataArray[$ib][$ia]);//POST-Felder festlegen, die gesendet werden sollen
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,4);
			curl_setopt($ch,CURLOPT_TIMEOUT,5);
			curl_exec($ch);
			curl_close($ch);
			}}//Beep.nl

			if($externOut ==0){
			if(($KeeperArray[$ib]=="aktiviert")AND($KeeperIdArray[$ib] !="meineID" OR $KeeperIdArray[$ib] !="" OR $KeeperIdArray[$ib] !="meine KeeperID")AND $ib<$M_Type){//wenn aktiviert
			if($_SERVER['SERVER_NAME']=="community.beelogger.de"){$externOut++;}

			$iurl="https://webapp.ibeekeeper.de/api/v3/sensors/ingest/".$KeeperIdArray[$ib]."/";
			$ch=curl_init($iurl);//cURL ínit
			curl_setopt($ch,CURLOPT_HEADER,0);//Header soll nicht in Ausgabe enthalten sein
			curl_setopt($ch,CURLOPT_POST,1);//POST-Request wird abgesetzt
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$KeeperDataArray[$ib][$ia]);//POST-Felder festlegen, die gesendet werden sollen
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,4);
			curl_setopt($ch,CURLOPT_TIMEOUT,5);
			$response=curl_exec($ch);
			curl_close($ch);
			}}//iBeeKeeper
			
			if($M_Type==1)$externOut--;
//ThingsPeak
			if($externOut <=0){
			if($ia==($ExternDataCounter-1)){//Letzter Datensatz - bitte alles bulken
				if(($ThingsPeakArray[$ib]=="aktiviert")AND($ThingsPeakApiArray[$ib] !="meine Api" OR $ThingsPeakApiArray[$ib] !="")){//wenn aktiviert
				$ThingsBulkArray=array(array());
				for($js=0;$js<$ExternDataCounter;$js++){//JSON anlegen
				 if(is_numeric($ThingsPeakDataArray[$ib][$js][0])){
					$ThingsBulkArray[$js]['created_at']=$ThingsPeakDataArray[$ib][$js][0];
					if($ThingsPeakDataArray[$ib][$js][1] != ""){$ThingsBulkArray[$js]['field1']=$ThingsPeakDataArray[$ib][$js][1];}
					if($ThingsPeakDataArray[$ib][$js][2] != ""){$ThingsBulkArray[$js]['field2']=$ThingsPeakDataArray[$ib][$js][2];}
					if($ThingsPeakDataArray[$ib][$js][3] != ""){$ThingsBulkArray[$js]['field3']=$ThingsPeakDataArray[$ib][$js][3];}
					if($ThingsPeakDataArray[$ib][$js][4] != ""){$ThingsBulkArray[$js]['field4']=$ThingsPeakDataArray[$ib][$js][4];}
					if($ThingsPeakDataArray[$ib][$js][5] != ""){$ThingsBulkArray[$js]['field5']=$ThingsPeakDataArray[$ib][$js][5];}
					if($ThingsPeakDataArray[$ib][$js][6] != ""){$ThingsBulkArray[$js]['field6']=$ThingsPeakDataArray[$ib][$js][6];}
					if($ThingsPeakDataArray[$ib][$js][7] != ""){$ThingsBulkArray[$js]['field7']=$ThingsPeakDataArray[$ib][$js][7];}
					if($ThingsPeakDataArray[$ib][$js][8] != ""){$ThingsBulkArray[$js]['field8']=$ThingsPeakDataArray[$ib][$js][8];}
				 }
				}

				$ThingsBulk=['write_api_key' => $ThingsPeakApiArray[$ib],'updates' => $ThingsBulkArray];
				$ToSend=json_encode($ThingsBulk);
				$url="https://api.thingspeak.com/channels/".$ThingsPeakChannelArray[$ib]."/bulk_update.json";
				$curl=curl_init($url);
				curl_setopt($curl,CURLOPT_HEADER,false);
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl,CURLOPT_HTTPHEADER,
				array("Content-type: application/json"));
				curl_setopt($curl,CURLOPT_POST,true);
				curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,5);
				curl_setopt($curl,CURLOPT_TIMEOUT,5);
				curl_setopt($curl,CURLOPT_POSTFIELDS,$ToSend);
				$json_response=curl_exec($curl);
				curl_close($curl);
			}
		 }
		 }
		}//Single/Sub
	}
 }

//MAP2-----
 $ServerAntwort="";
 $beeloggerMap2="deaktiviert";
 if(file_exists("beelogger_map.php"))include("beelogger_map.php");
 if($beeloggerMap2=="aktiviert"){
	if(file_exists("../general_ini.php"))include("../general_ini.php");
//zunächst Trachtsituation der letzten 7 Tage berechnen
	if(!isset($beeloggerMapWaage))$beeloggerMapWaage="0";
	if($beeloggerMapWaage=="")$beeloggerMapWaage="0";
	if($beeloggerMapWaage=="0")$GewichtSensorStelle=6;//Singlebeelogger
	else $GewichtSensorStelle=5+(int)$beeloggerMapWaage;
	if($beeloggerMap2Sensoren[3] !=""){
		$RegenSensorStelle=(int)$beeloggerMap2Sensoren[3]+1;//Sendesensor4
	}
	else $RegenSensorStelle=0;

	$LastZeitpunkt=0;
	$TrachtGewicht=0;
	$Trachtdurchschnitt="";
	$RegenSumme24h=0;

	if(file_exists("week.csv")){//eine week.csv gefunden
		$w_array=file("week.csv");
		$i=count($w_array);
		if($i>1){
		$what=trim($w_array[$i-1]);
		$LetzteZeile=explode(",",$what);
		$prev_tg=(float)$LetzteZeile[$GewichtSensorStelle];
		 while($i--){
			$what=trim($w_array[$i]);$x=explode(",",$what);$s=count($x);
			if($x[$s-1] !='' AND $LastZeitpunkt==0){$LastZeitpunkt=(int)$x[$s-1];}
			if(((int)$x[$s-1]+86400)>=$LastZeitpunkt){ if($RegenSensorStelle>0)$RegenSumme24h +=$x[$RegenSensorStelle];}
			if($x[$GewichtSensorStelle]!=""){
			$this_tg=(float)$x[$GewichtSensorStelle];
			$tg_g=$prev_tg-$this_tg;$prev_tg=$this_tg;
			if(abs($tg_g)<0.5)$TrachtGewicht+=$tg_g;
			}//sum over all
		 }//while

		 $ZeitSpanne=($LastZeitpunkt - (int)$x[$s-1])/86400;//akutelles "x" -> Tage der Betrachtung
		 if(abs($TrachtGewicht)>0.3)$Trachtdurchschnitt=round(($TrachtGewicht/$ZeitSpanne),2);
	 if($TrachtGewicht>150.0)if(abs($TrachtGewicht)>150)$Trachtdurchschnitt=round(($TrachtGewicht/($ZeitSpanne*1000)),2);
		}
	}//week.csv


//Übertragung der Map2-Daten----------------------------
	$curlerString="https://map2.beelogger.de/log.php?mapid=".$beeloggerMap2ID."&beeid=".$beeloggerMap2BeeID."&do=164052&tracht=".$Trachtdurchschnitt;
	for($c=0;$c<6;$c++){
		if($beeloggerMap2Sensoren[$c] !=""){
			if($c==3)$curlerString .="&s4=".$RegenSumme24h;
			else $curlerString .="&s".($c+1)."=".$LetzteZeile[($beeloggerMap2Sensoren[$c]+1)];
		}
	}

	$ch=curl_init($curlerString);
	curl_setopt($ch,CURLOPT_HEADER,0);//Header soll nicht in Ausgabe enthalten sein
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
	curl_setopt($ch,CURLOPT_TIMEOUT,5);
	$ServerAntwort=curl_exec($ch);
	curl_close($ch);//Objekt schließen und Ressourcen freigeben
	$ServerAntwort=str_replace('"','',$ServerAntwort);//Anführungszeichen verboten
	$beeloggerMap2Status=$ServerAntwort;
 }//if($beeloggerMap2=="aktiviert")

//beeloggerMap Datenübertragung ende
 if($ServerAntwort !="" && $ext_Show==1)echo "<br><br>".$LAs[57].": ".$beeloggerMap2Status;

 if($beeloggerSketchID=="" AND $NeubeeloggerSketchID=="" AND $EE_Type==1)$OutputSketchID="EE";//kein Info also wenigstens EE anzeigen
 elseif($NeubeeloggerSketchID=="")$OutputSketchID=$beeloggerSketchID;//Kein Neuinfo alten Stand übernehmen
 else $OutputSketchID=$NeubeeloggerSketchID;

 if(($beeloggerMap2=="aktiviert")||strcmp($OutputSketchID,$beeloggerSketchID)){//beeloggerMAP2 Daten immer speichern!
	$MA = array("","","","","","","","","","","","","","","");
	$MA[0]="<?php\n";
	$MA[1]="//MAP2:\n".'$beeloggerMap2="'.$beeloggerMap2.'";'."\n";
	$MA[2]='$beeloggerMap2Location="'.$beeloggerMap2Location.'";'."\n";
	$MA[3]='$beeloggerMap2BeeID="'.$beeloggerMap2BeeID.'";'."\n";
	$MA[4]='$beeloggerMap2Status="'.$beeloggerMap2Status.'";'."\n";
	$MA[5]='$beeloggerMap2Lat="'.$beeloggerMap2Lat.'";'."\n";
	$MA[6]='$beeloggerMap2Lon="'.$beeloggerMap2Lon.'";'."\n";
	$MA[7]='$beeloggerMap2BeeloggerType="'.$beeloggerMap2BeeloggerType.'";'."\n";
	$MA[8]='$beeloggerMap2Connect="'.$beeloggerMap2Connect.'";'."\n";
	$MA[9]='$beeloggerMap2URL="'.$beeloggerMap2URL.'";'."\n";
	$MA[10]='$beeloggerMap2Email="'.$beeloggerMap2Email.'";'."\n";
	$MA[11]='$beeloggerMapWaage="'.$beeloggerMapWaage.'";'."\n";
	$MA[12]='$beeloggerMap2Sensoren=array("'.$beeloggerMap2Sensoren[0].'","'.$beeloggerMap2Sensoren[1].'","'.$beeloggerMap2Sensoren[2].'","'.$beeloggerMap2Sensoren[3].'","'.$beeloggerMap2Sensoren[4].'","'.$beeloggerMap2Sensoren[5].'");'."\n";

	$MA[13]='$beeloggerSketchID="'.$OutputSketchID.'";'."\n";
	if($NeubeeloggerSketchID !=""){//Nur wenn neue Aussendung
		$MA[14]='$InfoSketchID="Info vom:'.date("d.m.y H:i:s").'";'."\n";
	}
	else $MA[14]='$InfoSketchID="'.$InfoSketchID.'";'."\n";

	$fp=fOpen("beelogger_map.php","w+");
	foreach($MA as $values)fwrite($fp,$values);
	$OK=fwrite($fp,"?>");fclose($fp);
 }//if($beeloggerMap2=="aktiviert")
}//Ende function Externdaten übertragen

function Triggeralarme(){
	global $ext_Show,$beelg,$Count_Sets_in;

	if(file_exists("beelogger_ini.php"))include("beelogger_ini.php");
	$no_alarm=0;//any trigger set?
	for($i=1;$i<5;$i++){if($TriggerAlarmArray[$i][1]=="" AND $TriggerAlarmArray[$i][2]=="" AND($TriggerAlarmArray[$i][7]=="")){$no_alarm++; }}
	if($no_alarm==4)return;

	$LastTriggerArray=array("LastTrigger","false","false","false","false");
	$TriggerGesendetArray=array("WannGesendet?","","","","");
	if(file_exists("Triggeralarm.php"))include("Triggeralarm.php");
	$update_trgfile=0;

	if(file_exists("week.csv")){//Letzte Zeile aus week.csv auslesen
	 $input="week.csv";
	 $array=file($input);
	 $j=count($array);
	 if($j>0){//data in file
		while($j--){
			$what=trim($array[$j]);
			$LetzteZeile=explode(",",$what);
			$s=count($LetzteZeile);
			if($LetzteZeile[$s-1] !='')break;
		}
		while($Count_Sets_in>0){
		$what=trim($array[$j+1-$Count_Sets_in]);
		$Zeile=explode(",",$what);
		$s=count($Zeile);
		$TimeNow=(int)$Zeile[$s-1];

		for($i=1;$i<5;$i++){
			if($TriggerAlarmArray[$i][1]=="aktiviert" OR $TriggerAlarmArray[$i][2]=="aktiviert" OR($TriggerAlarmArray[$i][7]=="aktiviert")){

				$TriggerErkannt=0;
				if($TriggerAlarmArray[$i][4]=="<"){
					if((float)$Zeile[($TriggerAlarmArray[$i][3]+1)]<(float)$TriggerAlarmArray[$i][5]){
						$TriggerErkannt=1;
					}
				}else{
					if((float)$Zeile[($TriggerAlarmArray[$i][3]+1)]>(float)$TriggerAlarmArray[$i][5]){
						$TriggerErkannt=1;
					}
				}
				if($TriggerErkannt==0){
			if($LastTriggerArray[$i]=="true")$update_trgfile=1;//save change
			$LastTriggerArray[$i]="false";
		}

				$LastTriggerTime=0;
				if($TriggerGesendetArray[$i]!="")$LastTriggerTime=(int)$TriggerGesendetArray[$i];
				if($LastTriggerArray[$i]=="")$LastTriggerArray[$i]="false";

				if($TriggerErkannt &&(($TimeNow-$LastTriggerTime)>86400)&&($TriggerGesendetArray[$i] !="deaktiviert")&&($LastTriggerArray[$i]=="false")){
					$update_trgfile=1;
					$LastTriggerArray[$i]="true";
					if($ext_Show==1)echo "<br>"."Trigger o.k.";
					if($Bienenvolkbezeichnung !="auto")$Nachricht=$Bienenvolkbezeichnung;
					else $Nachricht=$beelg;

					$Nachricht .=" meldet einen Triggeralarm um ".date("H:i",$TimeNow)." Uhr\n".'Der Sensor "'.html_entity_decode($Sensoren[(($TriggerAlarmArray[$i][3])*5)]).'" hat mit dem Wert '.$Zeile[($TriggerAlarmArray[$i][3]+1)]." (".$TriggerAlarmArray[$i][4]." als ".$TriggerAlarmArray[$i][5].") eine Nachricht ausgeloest.";

					if((int)$TriggerAlarmArray[$i][6]<9000){
						$Nachricht .="\nDer naechst moegliche Alarm erfolgt fruehstens in: ";
						$Nachricht .="24 Stunden - um ".date("H:i",($TimeNow+86400))." Uhr.";
					}else{
						$Nachricht .="\nDer Triggeralarm wurde nun automatisch deaktiviert und kann in der Konfiguration neu aktiviert werden.";
					}

					if($TriggerAlarmArray[$i][1]=="aktiviert"){//Email
						$actual_mlink=strstr("{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'beelogger_log',TRUE)."beelogger_show.php";
						$Nachricht_m=$Nachricht."\nLink zum ".$beelg.": ".$actual_mlink;//ohne https
						if($Empfaenger_Email !="empfaenger@meineDomain.de"){
							$Mailbetreff=$beelg;
							if($Bienenvolkbezeichnung !="auto")$Mailbetreff .="(".$Bienenvolkbezeichnung.")";
							$Mailbetreff .="-Triggeralarm";
							$hdr="From:".$Absender_Email."\r\n";
							$hdr .="Return-Path:".$Absender_Email."\r\n";
							$hdr .="X-Mailer: PHP/".phpversion()."\r\nMime-Version: 1.0\r\n";
							$hdr .="Content-type: text/plain; charset=ISO-8859-1\r\n";
							$ret_path='"-f'.$Absender_Email.'"';//mail($email,$subject,$addletter,$headers, "-fno_replay@test.com");
							mail($Empfaenger_Email,$Mailbetreff,$Nachricht_m,$hdr,$ret_path);
							if($ext_Show==1)echo "<br>"."Triggeralarm".$i." per Mail versendet: ".$Nachricht;
							$TriggerGesendetArray[$i]=(string)$TimeNow;
							if((int)$TriggerAlarmArray[$i][6]==9999)$TriggerGesendetArray[$i]="deaktiviert";
						}//if EmailAlarm aktiviert
					}

					if($TriggerAlarmArray[$i][2]=="aktiviert"){//Pushnachricht
						$actual_link=strstr("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",'beelogger_log',TRUE)."beelogger_show.php";
						$Nachricht_p=$Nachricht."\nLink zum ".$beelg.": ".$actual_link;
						if($TriggerAlarmArray[$i][9]=="")$TriggerAlarmArray[$i][9]=0;//Prio
					 if(($PushToken!="PushToken")AND($PushToken !="")){
						curl_setopt_array(
						$ch=curl_init(),array(
						CURLOPT_URL => "https://api.pushover.net/1/messages.json",
						CURLOPT_POSTFIELDS => array(
							"token" => $PushToken,
							"user" => $PushUser,
							"message" => $Nachricht_p,
							"priority" => $TriggerAlarmArray[$i][9],
						),
						CURLOPT_SAFE_UPLOAD => true,
						CURLOPT_CONNECTTIMEOUT => 5,
						CURLOPT_TIMEOUT => 5,
						CURLOPT_RETURNTRANSFER => true));
						curl_exec($ch);
						curl_close($ch);
						if($ext_Show==1)echo "<br>"."Triggeralarm".$i." per Push send: ".$Nachricht_p;
					 }
					 if(($T_Chat_Id!="Id")AND($T_Chat_Id!="")){
						$data =['chat_id' => $T_Chat_Id,'text' => $Nachricht_p];
						curl_setopt_array($ch=curl_init(),array(
						CURLOPT_URL => "https://api.telegram.org/bot$T_Token/sendMessage?" .
						http_build_query($data),
						CURLOPT_SAFE_UPLOAD => true,
						CURLOPT_CONNECTTIMEOUT => 5,
						CURLOPT_TIMEOUT => 5,
						CURLOPT_RETURNTRANSFER => true));
						curl_exec($ch);
						curl_close($ch);
						if($ext_Show==1)echo"<br><br>Triggeralarm ".$i." per Telegram send: ".$Nachricht_p;
					 }//

						$TriggerGesendetArray[$i]=(string)$TimeNow;
						if((int)$TriggerAlarmArray[$i][6]==9999)$TriggerGesendetArray[$i]="deaktiviert";
					}//if PushTriggerAlarm aktiviert

					if($TriggerAlarmArray[$i][7]=="aktiviert"){//evtl. Note schreiben
						$triggernote="";
						$triggernote=$TriggerAlarmArray[$i][3].",".$Zeile[0].",../beelogger_icons/n_Triggeralarm.png&Mit dem Wert: ".$Zeile[($TriggerAlarmArray[$i][3]+1)]." (".$TriggerAlarmArray[$i][4]." als ".$TriggerAlarmArray[$i][5].") wurde ein ";
						if($TriggerAlarmArray[$i][1]=="aktiviert")$triggernote .="Mail";
						if($TriggerAlarmArray[$i][1]=="aktiviert" && $TriggerAlarmArray[$i][2]=="aktiviert")$triggernote .="/";
						if($TriggerAlarmArray[$i][2]=="aktiviert")$triggernote .="Push";
						$triggernote .="-Triggeralarm ausgeloest,".$TimeNow."\r\n";
						$fp=fOpen("notes.csv","a");fWrite($fp,$triggernote);fClose($fp);
						$TriggerGesendetArray[$i]=(string)$TimeNow;
					}
				}
			}//if"aktiviert"
		}//for
		$Count_Sets_in--;
	 }//while
	 if($update_trgfile){//update file
		$TD=array("","","");
		$TD[0]="<?php //file to control triggers ".$TimeNow." - ".date("Y/m/d H:i:s",$TimeNow)."\n";
		$TD[1]='$TriggerGesendetArray=array("WannGesendet?","'.$TriggerGesendetArray[1].'","'.$TriggerGesendetArray[2].'","'.$TriggerGesendetArray[3].'","'.$TriggerGesendetArray[4].'");'."\n";
		$TD[2]='$LastTriggerArray=array("LastTrigger","'.$LastTriggerArray[1].'","'.$LastTriggerArray[2].'","'.$LastTriggerArray[3].'","'.$LastTriggerArray[4].'");'."\n?>";
		$fp=fOpen("Triggeralarm.php","w");foreach($TD as $values)fwrite($fp,$values);fClose($fp);
		}
	 }//data in week.csv
	}//week.csv exists
}//Ende Triggeralarme

function write_error_file($typ){//write errorstring and data to ErrorcheckerData.php
	global $NeubeeloggerSketchID,$ext_Passwort,$BeeloggerLogPasswort,$M_Type,$ext_M_Type,$zeit;

	$S_ID="";
	$Email="-";
	if(file_exists("beelogger_map.php")){include("beelogger_map.php"); $S_ID=$beeloggerSketchID;}
	if($S_ID=="")$S_ID=$NeubeeloggerSketchID;

if($_SERVER['SERVER_NAME']=="community.beelogger.de"){//user servers only
	$link="https://$_SERVER[HTTP_HOST]/".substr(dirname(__FILE__),strrpos(dirname(__FILE__),"community/")+10)."/beelogger_show.php";
	$err_log_file="../../ErrorcheckerData.php";
	if(file_exists("../data.php"))include("../data.php");
}
else {
	$link="https://".str_replace($_SERVER['DOCUMENT_ROOT'],$_SERVER['SERVER_NAME'],dirname($_SERVER['SCRIPT_FILENAME']))."/beelogger_show.php";
	$err_log_file="../ErrorcheckerData.php";
}

	$Log_data="echo '<BR>".date("Y/m/d H:i:s")."<BR>".'<a href="'.$link.'" target="_blank">'.$link.'</a>';
	$Log_data.="	Mail: ".$Email."<BR>";

	if($typ==1){$Log_data .="Datenfehler ";}//Datenfehler
	elseif($typ==2){$Log_data .="Z=? ";}//Z=
	elseif($typ==3){$Log_data .="typErr int:".$M_Type." ext:".$ext_M_Type;}//typErr
	elseif($typ==4){$Log_data .="timestamp:(4) ".$zeit;}//zeitfehler
	elseif($typ==5){$Log_data .="timestamp:(5) ".$zeit;}//zeitfehler
	elseif($typ==6){$Log_data .="ErrorT55D Typ:".$M_Type." ext:".$ext_M_Type;}//ErrorT55D
	elseif($typ==7){$Log_data .="M_Data?? &nbsp;Zeit: ".$zeit;}//Datensatzfehler
	elseif($typ==8){$Log_data .="G3 G4 &nbsp;Zeit: ".$zeit;}//G3 G4 Daten
	elseif($typ==9){$Log_data .="DataSize &nbsp;Array: ".$zeit;}//daten
	elseif($typ==10){$Log_data .="PwError&nbsp;&nbsp;".$BeeloggerLogPasswort."&nbsp; ext:".$ext_Passwort;}//Passwort

	$Log_data .="&nbsp; SketchID: ".$S_ID."<BR>';\r\n";
	$fp=fOpen($err_log_file,"a");fWrite($fp,$Log_data);fWrite($fp,"echo'");
	/*foreach($_GET as $key => $value){
		$v=preg_replace('/[[:^print:]]/','',$value);//ignore non printable and unwanted chars
		$vx=str_replace("'","?",$v);//remove '
		$val=str_replace('"','?',$vx);//remove "
		$var="&".$key."=".$val;
		fWrite($fp,$var);
	}*/
	fWrite($fp ,"<BR>';\r\n");fClose($fp);
}
?>

