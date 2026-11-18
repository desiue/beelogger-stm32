<?php
/*
 * (C) 2022 Jeremias Bruker, Thorsten Gurzan, Rudolf Schick - beelogger.de
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

$Softwareversion = "M.40";//vom 24.09.2026 - beelogger_mobilecheck.php
$show_version = 0;  //0,1,2 Programmertool

header('Content-Type: text/html; charset=UTF-8');

$Sprache = 1; // INIT falls noch keine Speicherung stattfand

$isHttps = (!empty($_SERVER['HTTPS']));
if($isHttps == 0){
	$local_network="192.168.178.";
	if((strstr($_SERVER['REMOTE_ADDR'],$local_network)==false)&&(strstr($_SERVER['SERVER_NAME'],$local_network)==false)){
		echo "Webseite   ".$_SERVER['SERVER_NAME']." bitte mit https:// aufrufen'";
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


date_default_timezone_set('Europe/Berlin');
if (file_exists("general_ini.php")) include("general_ini.php"); //Sprache abfragen
if ($Sprache == "") $Sprache = 1; // INIT

$file_f= "beelogger_sprachfiles/Check_Sprache_".$Sprache.".php";
if (file_exists($file_f)) include($file_f);  // Sprache
$file_f= "beelogger_sprachfiles/Mobile_Sprache_".$Sprache.".php";
if (file_exists($file_f)) include($file_f);  // Sprache

$ext_help = 0;
$ext_time = 1;
$ext_graph = 999;
$ext_beelogger="";

if(isset($_POST['help'])) $ext_help = htmlentities(strip_tags(stripslashes($_POST['help'])));
if(isset($_POST['time'])) $ext_time = htmlentities(strip_tags(stripslashes($_POST['time'])));
if(isset($_POST['graph'])) $ext_graph = htmlentities(strip_tags(stripslashes($_POST['graph'])));
if(isset($_POST['beelogger'])) $ext_beelogger = htmlentities(strip_tags(stripslashes($_POST['beelogger'])));
if($ext_beelogger=="") {
	if(isset($_GET['beelogger'])) $ext_beelogger = htmlentities(strip_tags(stripslashes($_GET['beelogger'])));
}

if($ext_beelogger=="") {
  $dir = "./";
  $files = scandir($dir);
  natsort($files);
  foreach ($files as $file) {
	if ($file != "." && $file != ".."){
		if (is_dir("./".$file)) {
			$filename = "./".$file."/beelogger_interface.php";
			if (file_exists($filename)) {
				$ininame = "./".$file."/beelogger_ini.php";
				$interfacename = "./".$file."/beelogger_interface.php";
				if (file_exists($ininame) AND file_exists($interfacename)) {
					$ext_beelogger = $file;
					break;
				}
			}
		}
	}
  }
}
$sensor_einheit=array('','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','');
$pfad = "./".$ext_beelogger."/";
?>


<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=0.9">

<link rel="icon" type="image/png" sizes="16x16" href="./beelogger_icons/favicon-16x16.png" />
<link rel="icon" type="image/png" sizes="32x32" href="./beelogger_icons/favicon-32x32.png" />
<link rel="icon" type="image/png" sizes="96x96" href="./beelogger_icons/favicon-96x96.png" />

<link rel="stylesheet" href="./dygraph_beelogger.css">
<title>beelogger mobileCheck</title>

<script type="text/javascript" src="./dygraph.js" charset="utf-8"></script>

<style>
select	{font-size:18px; width:100%; height:30px; border-radius: 10px; text-align: center; background: #E5E4E2;}
select>option { background-color: #b8e1ba;}
input[type="submit"]   	{font-size:18px; width:100%; height:25px; border-radius: 10px; text-align: center; background: lightgrey;}
#headline	{font-size:20px; font-weight: bold; font-style: italic;}
#value		{font-size:18px; font-weight: bold;}
#date		{font-size:16px; font-weight: bold;}
#delta		{font-size:16px; font-weight: bold; color: red;}
#help		{font-size:18px;}
#graph		{font-size:18px; font-weight: bold;}
</style>
<style type="text/css">
   .dygraph-legend {
     padding: 4px;
     margin-left: 100px !important;
  }
	#beelogger_grafik .dygraph-ylabel {color:#ffff87;}
	#beelogger_grafik .dygraph-axis-label-y1 {color:#ffff87;}
	#beelogger_grafik .dygraph-y2label {color:black;}
	#beelogger_grafik .dygraph-axis-label-y2 {color:black;}
</style>
</head>
<body bgcolor="#ffff87">
<div id='top'></div>
<?php    if ($show_version) echo "\n<td>&nbsp;&nbsp;&nbsp;Version:&nbsp;".$Softwareversion."</td>";?>
<table border="0" width="100%">
<tr>
<td>
	<table border="0" width="100%">
	<tr>
	<td valign="top" align="left" width="1%">
	<image src="./beelogger_icons/m_beelogger_logo_small.png" height="22">
	</td>
	<td valign="top" align="left" width="1%">
	<div id="headline">mobileCheck 	<?php if(file_exists("X_info_to_user.php")){include ("X_info_to_user.php"); if($msg_2_user != "") echo "\n<h2>$msg_2_user</h2>";}?>
	</div>
	</td>

	<td valign="top" align="center" width="30%">
		<a href="<?php echo $pfad ?>beelogger_show.php" title="show";  target="_blank"><img src="./beelogger_icons/m_dia.png" height="30px"></a>
		<a href="./beelogger_mobileWatch.php" title="mobileWatch";  target="_blank"><img src="./beelogger_icons/watch.png" height="30px"></a>
	</td>
	<td valign="top" align="center" width="10%">
	<form action="mobileCheck.php" method="post">
		<?php if($ext_help==1) { ?>
			<input type='hidden' name='help' value = '0'>
			<input type="hidden" name="time" value="<?php echo "$ext_time";?>" />
			<input type="hidden" name="graph" value="<?php echo "$ext_graph";?>" />
			<input type="hidden" name="beelogger" value="<?php echo "$ext_beelogger";?>" />
			<input type="image" title="help"; name="submit" src="./beelogger_icons/m_back.png" border="0" style="height: 30px;" />
		<?php } else { ?>
			<input type='hidden' name='help' value = '1'>
			<input type="hidden" name="time" value="<?php echo "$ext_time";?>" />
			<input type="hidden" name="graph" value="<?php echo "$ext_graph";?>" />
			<input type="hidden" name="beelogger" value="<?php echo "$ext_beelogger";?>" />
			<input type="image" title="help"; name="submit" src="./beelogger_icons/m_help.png" border="0" style="height: 30px;" />
		<?php } ?>
	</form>
	</td>
	</tr>
	</table>
</td></tr>

<tr><td height="2"><hr></td></tr>
<tr>
<td>
<?php 
if (file_exists($pfad."beelogger_interface.php")) {include($pfad."beelogger_interface.php");} else $ext_help=1;
if (file_exists($pfad."beelogger_ini.php")) {include($pfad."beelogger_ini.php");} else $ext_help=1;
if ($mobileCheck_Show != "aktiviert") $ext_help=1;

if ($ext_help==0) { ?>

	<table border="0" width="100%">
	<tr>
	<td>
	<?php
		$currentTime = new DateTime("now");
		$updateTime = date_create();
		date_timestamp_set($updateTime, $sensor_aktualisierung);
		$currentTime->format("H:i:s d.m.Y");
		$updateTime->format("H:i:s d.m.Y");		
		$df = $currentTime->diff($updateTime); 
		$days = $df->format("%d");
		
		$nexttime = ((int)$sensor_aktualisierung + (((int)$sensor_intervall) * 60));
		$nextupdateTime = date_create();
		date_timestamp_set($nextupdateTime, $nexttime);
		$nextupdateTime->format("H:i d.m.Y");
		$nextdiff = $nextupdateTime->diff($currentTime);
		$nexth = (int)$nextdiff->format("%H") +1;

		$aktualisierung = "> 24h";
		$next_aktualisierung = " ";
		$deltacolor="red";
		if($days == 0){
			$aktualisierung = $df->format("%H").":".$df->format("%I");
			$next_aktualisierung = "~ ".$nexth." ... ".$nexth+1 ."h";
			if (time() < ($nexttime + 3600)){$deltacolor="green";}
		}
	?>

	<table border="0" width="100%">
	<tr>
	<td width='30%'>
	<form action="beelogger_mobileCheck.php" method="post">
		<input type="hidden" name="time" value="<?php echo "$ext_time"; ?>" />
		<input type="hidden" name="graph" value="<?php echo "$ext_graph";?>" />
		<input type="hidden" name="beelogger" value="<?php echo "$ext_beelogger";?>" />
		<input type="image" name="submit" src="./beelogger_icons/m_refresh.png" border="0" style="height:30px;" />
	</form>
	</td>
	<td width='20%'><div id="date"><?php echo date("d.m.Y - H:i", $sensor_aktualisierung) ?></div></td>
	<td align="right"><image src="./beelogger_icons/m_delta.png" height="30"></td>
	<td><div id="delta" style="color:<?php echo "$deltacolor"; ?>;"><?php echo " $aktualisierung  <br>  $next_aktualisierung"; ?></div></td>
	</tr>
	</table>
	</td></tr>

	<tr><td>
	<table border="0" width="100%">
	<tr>
	<td width="*">
		<input type="image" title="show"; src="./beelogger_icons/m_dia.png"  width="40" onclick="window.location.href='<?php echo $pfad ?>beelogger_show.php'"></input></td>
	<td width="100%">
	<form action="beelogger_mobileCheck.php" method="post">
		<select name='beelogger' onChange='document.location.href=this.value' title='beelogger'>

	<?php
		$set_title="";
		$dir = "./";
		$files = scandir($dir);
		natsort($files);
		foreach ($files as $file) {
		  if ($file != "." && $file != ".."){
			if (is_dir("./".$file)){
				$filename = "./".$file."/beelogger_interface.php";
			  if (file_exists($filename)){
				$link = "beelogger_mobileCheck.php?beelogger=".$file;
				$interfacename = "./".$file."/beelogger_interface.php";
				$Bienenvolkbezeichnung = "auto";
				$ininame = "./".$file."/beelogger_ini.php";
				$Bienenvolkbezeichnung = "auto";
				$mobileCheck_Show = "aktiviert";//init
				if (file_exists($ininame)) include ($ininame);
				if ($mobileCheck_Show == "aktiviert"){
				 if ($file == $ext_beelogger) {echo '<option style="background-color: lightgreen" value='.$link." selected>";
				 if ($Bienenvolkbezeichnung != "auto") $set_title=html_entity_decode($Bienenvolkbezeichnung);}

				 else{
					if (file_exists($ininame) AND file_exists($interfacename)) echo '<option value='.$link.">";
					else echo '<option value='.$link.">";
				 }
				 $ShowFile = str_replace("beelogger","",$file);
				 if ($Bienenvolkbezeichnung != "auto") echo $ShowFile." (".html_entity_decode($Bienenvolkbezeichnung).")";
				 else echo $ShowFile;
				 echo "</option>\n";
				}
			  }
			}
		  }
		}

		echo"</select>";

		echo '<script type="text/javascript">'."window.top.document.title='mobileCheck ";
		if (strlen($set_title)>1) echo ": ".html_entity_decode($set_title);
		echo"';</script>";
		unset($MobileCheckSensor); // init
		include($pfad."beelogger_ini.php"); //REINIT
	?>
	</form>
	</td>
	</tr>
	</table>
	</td>
	</tr>

	<tr><td height="5"><hr></td></tr>
	<tr><td>
	<table border="0" width="100%">
		<tr>
		<td width="10%"></td>
		<td width="30%" align="center"><img src="./beelogger_icons/m_time.png" height="30"></td>

		<form action="beelogger_mobileCheck.php" method="post">
			<td width="30%" align="center">
		<?php
			if ($ext_time==1) {
				echo"
				<input type='hidden' name='time' value='24' />
				<input type='hidden' name='graph' value='".$ext_graph."' />
				<input type='hidden' name='beelogger' value='".$ext_beelogger."' />
				<input type='image' name='submit' src='./beelogger_icons/m_time1.png' border='0' style='height: 30px;' />
				";
			}
			if ($ext_time==24) {
				echo"
				<input type='hidden' name='time' value='1' />
				<input type='hidden' name='graph' value='".$ext_graph."' />
				<input type='hidden' name='beelogger' value='".$ext_beelogger."' />
				<input type='image' name='submit' src='./beelogger_icons/m_time24.png' border='0' style='height: 30px;' />
				";
			}
		?>
			</td>
		</form>

		<td width="30%" align="center"><img src="./beelogger_icons/m_balance.png" height="30"></td>
		</tr>
		<tr><td colspan="4"><hr></td></tr>
	<?php

	$diffcolor = "black";
	$s_wert_x = array();
	if ($sensor_anzahl > 0) {
		for($i=0; $i<$sensor_anzahl; $i++) {
			$diffcolor = "black";
			$showSensor = "false"; //handle new array
			if(isset($MobileCheckSensor))$showSensor=$MobileCheckSensor[$i];
			else {
				if(($sensor_icon[$i]!="no.png") and ($sensor_icon[$i]!=""))$showSensor = "true";
			}
			if ( is_numeric($sensor_bezeichnung[$i]) || ($sensor_wert[$i] =="")) $showSensor = "false"; 
			if ($showSensor=="true"){
				if($ext_time==1) {
					$s_wert_x = $sensor_wert_1;
				}
				if($ext_time==24) {
					$s_wert_x = $sensor_wert_24;
				}
				$diff = "";
				if (($s_wert_x[$i]!="") and (is_numeric($s_wert_x[$i]))) {
					$val = (float)$sensor_wert[$i] - (float)$s_wert_x[$i];
					$diff= round($val,2);

					if ($diff < 0) $diffcolor = "red";
					if ($diff > 0) $diffcolor = "green";
				}
				if (($sensor_bezeichnung[$i]!="Status") or ($sensor_icon[$i]!="m_info_1.png")) {
				if ($i==$ext_graph) $switchgraph = 999;
				else  $switchgraph=$i;
				if ($ext_graph==$i) $anker ="top";
				else $anker ="graph";

			echo "<tr>";
			echo "<form action='beelogger_mobileCheck.php#".$anker."' method='post'>"."\n";
			echo "<td width='20%'>"."\n";
			echo "<input type='hidden' name='graph' value='".$switchgraph."' />"."\n";
			echo "<input type='hidden' name='time' value='".$ext_time."' />"."\n";
			echo "<input type='hidden' name='beelogger' value='".$ext_beelogger."' />"."\n";
			echo "<input type='image' name='submit' src='./beelogger_icons/".$sensor_icon[$i]."' title='".$sensor_bezeichnung[$i]."' border='0' style='height: 30px;' />"."\n";
			 echo " $sensor_bezeichnung[$i] $sensor_einheit[$i]</td>"."\n"."</form>"."\n";
			echo "<td width='30%' align='center'><div id='value'> $sensor_wert[$i] </div></td>";
			$sens_val = $s_wert_x[$i];
			echo "<td width='30%' align='center'><div id='value'> $sens_val </div></td>";
			echo "<td width='30%' align='center'><div id='value'><font color='$diffcolor'> $diff </font></div></td>\n";
			echo "</tr><tr><td colspan='4'><hr></td></tr>";
		
		if (($sensor_icon[$i]=="rain.png") and ($rain_for_24!="no")) { ?>
			<tr>
			<form action='beelogger_mobileCheck.php#<?php echo"$anker";?>' method='post'>
				<td width='10%'>
				<input type='hidden' name='graph' value='<?php echo"$switchgraph";?>' />
				<input type='hidden' name='time' value='<?php echo"$ext_time";?>' />
				<input type='hidden' name='beelogger' value='<?php echo"$ext_beelogger";?>' />
				<input type='image' name='submit' src='./beelogger_icons/rain_24.png' title='<?php echo"$sensor_bezeichnung[$i]";?> 24' border='0' style='height: 30px;' />  <?php echo $sensor_bezeichnung[$i]." ".$sensor_einheit[$i];?>
				</td>
			</form>
	<?php	$rain_val = number_format(((float)$rain_for_24),2,".","");
			echo"<td width='20%' align='center'><div id='value'>".$MAs[4]." 24h: ".$rain_val."</div></td>";
			if(isset($rain_for_7d)){ 
				$rain_7d = number_format(((float)$rain_for_7d),1,".",""); 
				echo "<td width='20%' align='center'><div id='value'>".$MAs[4]." 7d: &nbsp;".$rain_7d."</div></td>";
			}
			else echo"<td width='20%' align='center'> - <div id='value'>&nbsp;</div></td>";
			echo"<td width='30%' align='center'><div id='value'>&nbsp;</div></td>
			</tr>
			<tr><td colspan='4'><hr></td></tr>";
		}
	   } else {
?>
		<tr>
		<td width='10%'><img src='./beelogger_icons/<?php echo"$sensor_icon[$i]";?>' title='<?php echo"$sensor_bezeichnung[$i]";?>' height='30'></td>
		<td width='30%' align='center' colspan='3'><div id='value'><?php echo"ceil($sensor_wert[$i])";?></div></td>
		</tr>
		<tr><td colspan='4'><hr></td></tr>
		</table>
<?php
	   }
	  }
	} // for sensoranzahl

	} else echo "<tr><td colspan='4'>$CAs[1]</td></tr>";
?>

	</table>
	</td>
	</tr>

	<tr><td colspan="4">
	<div id='graph'></div>


<?php
$ininame = "./".$ext_beelogger."/beelogger_ini.php";
if (file_exists($ininame)) { // load the ini
	include($ininame);
}


$ext_file = "./".$ext_beelogger."/week.csv";
$a_val = (int) $ext_graph;
if ( ($a_val < 50) and ($i>0)) {
	$ext_sensor=$a_val + 1;
	$mobile =0;
  echo'<button type="reset" id="Zoom" onclick="BL_Graph_zoom();" style="background-color: #ffff88;" name="Zoom" value="0"><b>Zoom: 24h</b></button>';
// Mobilgeräte
if (preg_match("/(alcatel|amoi|android|avantgo|blackberry|benq|cell|cricket|docomo|elaine
|htc|iemobile|iphone|ipad|ipaq|ipod|j2me|java|midp|mini|mmp|mobi|motorola|nec-|nokia|palm|
panasonic|philips|phone|playbook|sagem|sharp|sie-|silk|smartphone|sony|symbian|t-mobile|telus
|up\.browser|up\.link|vodafone|wap|webos|wireless|xda|xoom|zte)/i", $_SERVER['HTTP_USER_AGENT']))
{ $mobile=1;}
?>

<div id="beelogger_grafik"></div>

<script type="text/javascript">
var d_names = [<?php echo '"'.$CAs[13].'","'.$CAs[14].'","'.$CAs[15].'","'.$CAs[16].'","'.$CAs[17].'","'.$CAs[18].'","'.$CAs[19].'"'; ?>];
var zoom = 0;
var w = parseInt(window.innerWidth *0.95);
var BL_Graph = new Dygraph(
	document.getElementById("beelogger_grafik"),<?php echo "'".$ext_file."'";?>,{
	labels: [ 'Datum',<?php for ($s = 0; $s < $sensor_anzahl; $s++){ echo "'".html_entity_decode($Sensoren[$s*5]).' '.html_entity_decode($Sensoren[$s*5+4])."',"; }
	echo "'','',''";?>],
	colors: [<?php for ($s = 0; $s < $sensor_anzahl; $s++){ echo "'".$Sensoren[$s*5+1]."',"; }
		echo "'grey','grey','grey'";?>],
	visibility: [<?php for ($s = 0; $s < $sensor_anzahl; $s++){
		if (($s+1) ==  $ext_sensor OR !file_exists($ext_file)) echo "1,";else echo "0,"; };
		echo "0,0,0";?>],
	xlabel: '',ylabel:'',y2label:'',
	series: {<?php for ($s = 0; $s < $sensor_anzahl; $s++){echo "'".html_entity_decode($Sensoren[$s*5]).' '.html_entity_decode($Sensoren[$s*5+4])."':{axis:'y2'},"; } ?>
	},
	axes:{
		y:{
			axisLabelWidth:20,
			gridLineColor:'#ffff87'
		},
		y2:{
			drawGrid: true,
			independentTicks: true,
			labelsKMB: false,
			axisLabelWidth:60,
		  axisLabelFormatter:function(x){if(Math.abs(x)<0.01)return "0-";if(Math.abs(x)<10.0)return(Math.round(x*100,2)/100);if(x>500)return(Math.round(x));return(Math.round(x*10,1)/10);},
		},
	},
	legend:'always',legendFormatter: BL_Graph_mylefo,
	hideOverlayOnMouseOut: false,labelsSeparateLines: false,strokeWidth:2.0,
	<?php 	if($mobile){ echo "interactionModel:{}";}else {echo "interactionModel: Dygraph.defaultInteractionModel";}
	echo",y2label:'".$Sensoren[($ext_sensor-1)*5]." in ".$Sensoren[($ext_sensor-1)*5+4]."'";?>
});

BL_Graph.ready(function(){BL_Graph_onresize();BL_Graph_mouseup();window.addEventListener("resize", BL_Graph_onresize);
	<?php if($mobile==0)echo"window.addEventListener('mouseup', BL_Graph_mouseup);window.addEventListener('dblclick', BL_Graph_mouseup);";?>
	BL_Graph_zoom();
});

function BL_Graph_onresize(){var y =parseInt(window.innerWidth *0.95); 
<?php if($mobile)echo"if(Math.abs(y -w) >250) w=y;BL_Graph.resize(w,390);";else echo"BL_Graph.resize(y,450);";?>
}

function BL_Graph_mouseup(){range_y=BL_Graph.yAxisRange(1);BL_Graph.updateOptions({axes:{y:{valueRange:range_y}}});}

function BL_Graph_zoom(){
 if(zoom==0){
  document.getElementById("Zoom").textContent = "Zoom: 24h";
  var res = 86400;
  var desired_range = null;   // Anzeige/Zoom Bereich für Dygraph
  var wold=BL_Graph.xAxisExtremes();
  var w=BL_Graph.xAxisRange();
  if((w[1]-res*1000)<wold[0]){w[0]=wold[0]; // ganz links
    if((w[1]+res *1000)<wold[1]){w[1]=w[1]+res*1000;} // nach rechts geht noch
    else{w[1]=wold[1];} // ganz rechts
    desired_range=[w[0],w[1]];
  }else{desired_range=[w[1]-res*1000,w[1]];}
  BL_Graph.updateOptions({dateWindow:desired_range});
	zoom=1;
 }else{document.getElementById("Zoom").textContent = "Zoom: 7d";BL_Graph.resetZoom();zoom=0;}
} 
function BL_Graph_mylefo(data) { //legend formatter
  if(data.x == null){return '<br>';}
  var d_a=new Date(data.x);var html=d_names[d_a.getDay()]+' '+data.xHTML;
  data.series.forEach(function(series){
    if(!series.isVisible)return;if(!series.yHTML)return;
    html+='<br>'+series.dashHTML+' '+ series.labelHTML+': '+ series.yHTML;
  });
return html;
}
</script>
<?php
} // if ext_graph
?>
</td></tr>
</table>

<?php
} else {  // if ext_help
?>
<table border="0" width="100%">
<tr><td colspan="3">
	<div id="help"><?php echo $CAs[0]."<br><br>".$CAs[1]."<br><br>";?></div>
	</td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
<td valign="top"><input type="image" src="./beelogger_icons/m_refresh.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[2]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
<td valign="top"><input type="image" src="./beelogger_icons/m_delta.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[3]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
<td valign="top"><input type="image" src="./beelogger_icons/m_dia.png"  width="40"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[4]; ?> </div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
<td valign="top"><input type="image" src="./beelogger_icons/m_time.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[5]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
<td valign="top"><input type="image" src="./beelogger_icons/m_time1.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[6]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/m_time24.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[7]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/m_balance.png"  height="25"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[8]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/m_aio.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[9]; ?></div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/rain_24.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[10]; ?> </div></td>
</tr>
<tr><td colspan="3"><hr></td></tr>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/watch.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[11]; ?></div></td>
</tr>
	<td colspan="3"><hr></td>
<tr>
	<td valign="top"><input type="image" src="./beelogger_icons/like.png"  height="30"></td>
	<td>&nbsp;</td>
	<td><div id="help"><?php echo $CAs[12]; ?></div></td>
</tr>

<?php
	}   // else ext_help
?>
</tr>

<tr><td colspan="3"><hr></td></tr>
</table>
</td>
</tr>
</table>
</body>