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


$Softwareversion = "M.40";//vom 24.09.2026 - csvedit.php

header('Content-Type: text/html; charset=UTF-8');

//Auswertung der geänderten Daten
if(isset($_POST["Doit"])){
	if ($_POST["Doit"] ==1){
	 
									  
	  	if (file_exists("beelogger.csv")){ //Letzte Werte aus beelogger.csv auslesen
		    $input = "beelogger.csv";
		    $Helparray = file($input);
			$datensatz="";
	  		for ($i=0; $i < sizeof($Helparray); $i++){
		
				if(isset($_POST['DeleteZeile_'.$i]) && $_POST["DeleteZeile_".$i]){ //ganze Zeile löschen
		 
		   			//nichts hinzufügen
		   				//echo "/".$i;
				}
		   		elseif (isset($_POST['BearbeiteZeile_'.$i]) && $_POST["BearbeiteZeile_".$i]){ //ganze Zeile neu einfügen
		 
			    	$datensatz .=  $_POST["CSV".$i."_0"];
			    	$k=1;
			    	while (isset($_POST["CSV".$i."_".$k])){
		  
			    		$datensatz .= ",".preg_replace('/[^0-9.]/','',$_POST["CSV".$i."_".$k]);
			    		$k++;
		    		}
					$datensatz .= "\n";
	    		}

				else $datensatz .=$Helparray[$i]; //Zeile übernehmen		
			}
	  		$aktion = fOpen("beelogger.csv", "w");
            fWrite($aktion , $datensatz);
            fClose($aktion);  
		}
	}
}
echo '
		<!DOCTYPE html>
		<html lang="de">
		  <head>
		    <meta charset="utf-8">
		    <meta name="viewport" content="width=device-width, initial-scale=1.0">
		    <title>beelogger.de - CSVedit</title>
			<link rel="shortcut icon" href="./../beelogger_icons/favicon-32x32.png" />

		<style>
		table, th, td {
		  border: 1px solid black;
		  padding: 5px;
		}
		table {
		  border-spacing: 5px;
		}
		</style>
		</head>
		  <body>
	    <!-- Sichtbarer Dokumentinhalt im body -->';

include("beelogger_ini.php");
$StartDate = $_POST['erstescsvsplitdatum'];
$EndDate = 	$_POST['letztescsvsplitdatum'];
$Auswahl = 0;
if(isset($_POST["Auswahl"])){$Auswahl=$_POST['Auswahl'];}

if ($_POST["pw"] == $BeeloggerShowPasswort){

	if ($Auswahl != "1"){
   
//RUNDE1
		echo'<form action="" method="post">';
	    echo "<input type='hidden' name = 'pw' value = '".$BeeloggerShowPasswort."'>";
	    echo "<input type='hidden' name = 'erstescsvsplitdatum' value = '".$StartDate."'>";
	    echo "<input type='hidden' name = 'letztescsvsplitdatum' value = '".$EndDate."'>";

		//beeloggerCSV

									
		if (file_exists("beelogger.csv")){ //Letzte Werte aus beelogger.csv auslesen
		    $input = "beelogger.csv";
		    $Helparray = file($input);
		    $j = sizeof($Helparray);
		    while ($j--){
		   
		        $what = trim($Helparray[$j]);    
		        $x = explode( ",", $what );
		        $Spalten = sizeof($x);
		        if ($x[$Spalten-1] !=''){      
				   
		          break;
		        }      
		    }
			
	      //echo"<button type='submit' style='BACKGROUND-COLOR: #81BEF7;width:100%;' name='Auswahl' value='1'><b>Mit Klick geht es nach Vorauswahl der Zeilen zur endgültigen Bearbeitung der Daten</b></button>";

			echo'<p><table style="width:100%">';
			$Tag=0;//just some value to init


			for ($i=0; $i < sizeof($Helparray); $i++){
	 

				$what = trim($Helparray[$i]);    
	          	$x = explode( ",", $what );
	          	$Spalten = sizeof($x);
	          	
				if (($x[$Spalten-1] >= $StartDate) AND ($x[$Spalten-1] <= $EndDate)){
	  

		          	if (date("d",$x[$Spalten-1]) != $Tag){
			   
	          			$Tag = date("d",$x[$Spalten-1]);

						echo'<tr><td>zum Löschen der Zeile auswählen</td><td>Datum</td>';
									   
		
						for ($o=0; $o < $Spalten-4; $o++){ echo '<td>'.$Sensoren[$o*5].'</td>'; }
	   
						echo "<td>zum Bearbeiten der Zeile auswählen</td></tr>";

			      		echo"<tr><td colspan=".$Spalten."><button type='submit' style='BACKGROUND-COLOR: #81BEF7;width:100%;' name='Auswahl' value='1'><b>Mit Klick geht es nach Vorauswahl der Zeilen zur endgültigen Bearbeitung der Daten</b></button></td></tr>";
					}
					if ($i % 2 != 0) echo'<tr bgcolor="lightgrey">';
					else echo'<tr bgcolor="white">';
					echo "<td style=background-color:#e7846f;><input type='checkbox' name='DeleteZeile_".$i."'> ganze Zeile löschen</td>";
				    echo'<td>'.$x[0].'</td>';

					for ($s=1; $s < $Spalten-3; $s++){echo'<td>'.$x[$s].'</td>';}
	   
								
	   
					echo "<td style=background-color:#2ECCFA;><input type='checkbox' name='BearbeiteZeile_".$i."'> Werte bearbeiten</td>";
					echo "</tr>";
				}
			}
			echo'<tr><td>zum Löschen der Zeile auswählen</td><td>Datum</td>';
									   
		
			for ($o=0; $o < $Spalten-4; $o++){echo '<td>'.$Sensoren[$o*5].'</td>';}
	   
			echo "<td>zum Bearbeiten der Zeile auswählen</td></tr>";
			echo "</table>";

	      	echo"<button type='submit' style='BACKGROUND-COLOR: #81BEF7;width:100%;' name='Auswahl' value='1'><b>Mit Klick geht es nach Vorauswahl der Zeilen zur endgültigen Bearbeitung der Daten</b></button>";
		}
	  }
	  else {	
		//RUNDE2
		echo'<form action="" method="post">';
		echo "<input type='hidden' name = 'pw' value = '".$BeeloggerShowPasswort."'>";
		echo "<input type='hidden' name = 'erstescsvsplitdatum' value = '".$StartDate."'>";
		echo "<input type='hidden' name = 'letztescsvsplitdatum' value = '".$EndDate."'>";

		//beeloggerCSV

									
		if (file_exists("beelogger.csv")){ //Letzte Werte aus beelogger.csv auslesen
			$input = "beelogger.csv";
			$Helparray = file($input);
			$j = sizeof($Helparray);
			while ($j--){
		   
				$what = trim($Helparray[$j]);    
				$x = explode( ",", $what );
				$Spalten = sizeof($x);
				if ($x[$Spalten-1] !=''){ break;}      
				   
				  
				   
			}


			echo"<button type='submit' style='background-color: #a9dfbf; width:100%;' name='Doit' value='1'><b>veränderte bzw. gelöschte Daten in beelogger.csv speichern... ACHTUNG:  Nicht Rückgängig zu machen!!!!</b></button>";
			echo'<p><table style="width:100%">';
			
			//Kopfzeile
			echo'<tr><td>Datum</td>';

									 
	  
			for ($o=0; $o < $Spalten-4; $o++) { echo '<td>'.$Sensoren[$o*5].'</td>';}
	 
			echo "<td>Wirklich löschen oder ändern?</td></tr>";
		

			//Datenzeilen	
			for ($i=0; $i < sizeof($Helparray); $i++) {
	 
				// Zeilen löschen	
				if(isset($_POST['DeleteZeile_'.$i])){
				if ($_POST['DeleteZeile_'.$i]){
					echo'<tr bgcolor="red">';
					$what = trim($Helparray[$i]);    
					$x = explode( ",", $what );
					$Spalten = sizeof($x);
					echo'<td>'.$x[0].'</td>';
					echo "<input type='hidden' name='CSV".$i."_0' value = '".$x[0]."'>";

					for ($s=1; $s < $Spalten-3; $s++) {	echo'<td>'.$x[$s].'</td>';}
		
								 
		
					echo "<td><input type='checkbox' name='DeleteZeile_".$i."' checked></td>";		
					echo "</tr>";
				}
				}

				// Zeilen bearbeiten
				if(isset($_POST['BearbeiteZeile_'.$i])){
				if ($_POST['BearbeiteZeile_'.$i]){
					echo'<tr bgcolor="white">';
					$what = trim($Helparray[$i]);    
					$x = explode( ",", $what );
					$Spalten = sizeof($x);
					echo'<td>'.$x[0].'</td>';
					echo "<input type='hidden' name='CSV".$i."_0' value = '".$x[0]."'>";

										
		
					for ($s=1; $s < $Spalten-3; $s++) {echo'<td><input size ="6" type=text value="'.$x[$s].'"name="CSV'.$i.'_'.$s.'"></td>';}
		
					echo "<input type='hidden' name='CSV".$i."_".($Spalten-3)."' value = ''>";
					echo "<input type='hidden' name='CSV".$i."_".($Spalten-2)."' value = ''>";
					echo "<input type='hidden' name='CSV".$i."_".($Spalten-1)."' value = '".$x[$Spalten-1]."'>";
					echo "<td><input type='checkbox' name='BearbeiteZeile_".$i."' checked></td>";
					echo "</tr>";
				}	
				}
			}
			echo "</table>";
			echo"<button type='submit' style='background-color: #a9dfbf; width:100%;' name='Doit' value='1'><b>veränderte bzw. gelöschte Daten in beelogger.csv speichern... ACHTUNG:  Nicht Rückgängig zu machen!!!!</b></button>";
		}
	}

}
else 	echo "";
?>

</form>
</table>
</body>
</html>
