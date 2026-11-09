<?php
//##########################################################
// Split Anmerkungen
//##########################################################

// Routine splittes notesold.csv in notesArchiv - Dateien auf.
//  zu jeder Archiv-Datei wird eine  notesArchiv... .csv erstellt 

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);  // script may run up to 5 minutes


$Execute = 0;  // auf 1 für aktiv

$dir = "../";  // Pfad in dem gesucht wird

echo '<!DOCTYPE html><html lang="de"><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"></head>';

echo '<h1><font color="green">Notes Archive  erstellen:</h1><br>';

if ($Execute == 0) echo '<h2><font color="blue">Das ist nur ein Testlauf!</h2><br></font>';
else echo '<h2><font color="orange">Notes Archive aus "notes_old" werden erstellt:</h2><br></font>';

echo "<br>akt. Verzeichnis: ".getcwd()."<br><br>";
echo"<br>";

$mydir = $dir.basename(getcwd()); //echo $mydir."<br>";
   
$files = scandir($dir);
natcasesort($files);
foreach ($files as $file) { // for all accounts
	//  nur Account Directories
  if ($file != "." && $file != ".." && $file != "X_beelogger_icons" && $file != "X_beelogger_sprachfiles" && $file != "archiv")	{
	$accountdir = $dir.$file;
	//echo "<font SIZE=6 COLOR=LightGREEN><br><b>".$accountdir.": </b></font><br>";
            if ($accountdir == $mydir){
                echo "<font SIZE=6><b><br>".$accountdir."&nbsp;&nbsp; ignored <br></b></font>";
				continue;
			}
    if (is_dir($accountdir)) { // account directory ?
	  echo "<font SIZE=6 COLOR=GREEN><br><b>".$accountdir.": found</b></font><br>";

	  $xfiles = scandir($accountdir);
      natcasesort($xfiles);
      foreach ($xfiles as $xfile) {  // beelogger directories in account
		if ($xfile != "." && $xfile != ".." && $xfile != "beelogger_icons" && $xfile != "beelogger_sprachfiles" ) {
			if (is_dir($accountdir."/".$xfile)) { // subdirs (=beeloggers) only

				$beeloggerDir = $accountdir."/".$xfile;
				echo "<font SIZE=6 COLOR=LightGREEN><br><b>".$beeloggerDir.": </b></font><br>";
//#########################################
				if(!file_exists($beeloggerDir."/beelogger_show.php")) { echo "&nbsp; &nbsp; skipped!<br>";continue;}

$bfiles = scandir($beeloggerDir);
natcasesort($bfiles);
foreach ($bfiles as $archiv) { // Dateien in einem beelogger

	if(stripos($archiv,"Archiv") ===0){ // ist archivdatei

		$firstDate =0;
		$lastDate =0;

		$the_archive = $beeloggerDir."/".$archiv;  // Datenarchiv
		$SplitArray = file($the_archive);
		$is = sizeof($SplitArray);
	  if($is > 0){ // Daten im Archiv ?
			$what = trim($SplitArray[0]);
			$x = explode( ",", $what );
			$s = sizeof($x);
			if($x[$s-1] !='')$firstDate=(int)$x[$s-1]; //erste Spalte = Zeitstempel abfragen

			while ($is--){
				$what = trim($SplitArray[$is]);
				$x = explode( ",", $what );
				$s = sizeof($x);
				if($x[$s-1] !=''){ //letzte Spalte = Zeitstempel abfragen
					$lastDate=(int)$x[$s-1];
					break;
				}
			}
			$fdat = date('d-m-Y H:i:s', $firstDate);
			$ldat = date('d-m-Y H:i:s', $lastDate);

			echo $beeloggerDir."/".$archiv."&nbsp&nbsp&nbsp Daten erste: ".$fdat." &nbsp&nbsp letzte: ".$ldat."<br>";
			if($lastDate == 0 || $firstDate == 0) continue; // keine Zeitstempel

			if(($firstDate < 1262300400) && ($lastDate < 1262300400)){  // data in file before 2010
				unlink ($the_archive); 
				echo "<font SIZE=6 COLOR=Purple><br><b>".$the_archive." deleted </b></font><br>";
			}
		
		
			$oldNotes = $beeloggerDir."/notes_old.csv";
			if(!file_exists($oldNotes))continue;

			$NotesArray = file($oldNotes);
			$in = sizeof($NotesArray);
if($in > 0){ // Notes in notes_old.csv ?
		
	$NotesArchivName="";
	if($Execute == 0 ) { //test ausgabe
		$NotesArchivName = $beeloggerDir."/notes".$archiv;
		if(!file_exists($NotesArchivName)) echo " TEST --- Notes Archiv erstellen: ".$NotesArchivName."<br>";
		else echo " TEST Archiv vorhanden: ".$NotesArchivName."<br>";
		echo "<br>";
		foreach($NotesArray as $val){
			$what = trim($val);
			$x = explode( ",", $what );
			$s = sizeof($x);
			if( ((int)$x[$s-1] >= (int)$firstDate) && ((int)$x[$s-1] <= (int)$lastDate) ) echo "&nbsp;&nbsp;&nbsp;&nbsp;Test Note: ".$val."<br>";
		}
		echo "<br>";
	}
	  
	if($Execute ==1){
		$NotesArchivName = $beeloggerDir."/notes".$archiv;
		if(!file_exists($NotesArchivName)){
			$fp = fOpen($NotesArchivName, "w"); // Archiv schreiben
			foreach($NotesArray as $val){
				$what = trim($val);
				$x = explode( ",", $what );
				$s = sizeof($x);
				if( ((int)$x[$s-1] >= (int)$firstDate) && ((int)$x[$s-1] <= (int)$lastDate) ) fputs($fp, $val);
			}
			fclose($fp);
			echo " Notes Archiv erstellt: ".$NotesArchivName."<br>";
		}
		else  echo " Archiv vorhanden: ".$NotesArchivName."<br>";
		echo "<br>";
	} // Execute = 1

}  // any notes in notes_old.csv

	  } // Daten im Archiv
	} // ist archivdatei
} // foreach in beelogger dir



if($Execute ==1){
	$file_a = $beeloggerDir."/notes_old.csv";
	$file_b = $beeloggerDir."/notes_oldsave.csv";
	if (file_exists($file_a)){
		if (filesize($file_a)<20){
			unlink($file_a);
			echo " leere - notes_old.csv gelöscht<br>";
		}
		else {
			rename ($file_a,$file_b);
			echo " - notes_old.csv umbenennen<br>";
		}
	}
	sleep(1);
	if (file_exists($file_b)){
		if (filesize($file_b)<20){
			unlink($file_b);
			echo " leere - notes_oldsav.csv gelöscht<br>";
		}
	}
}
echo "beelogger Verzeichnis fertig<br>";

//#########################################
			}  // is beelogger 
		  } // valid file
		} // foreach
	  } //is_dir
	}
} // for all aaccounts

echo "<br<br>alles fertig<br>";

?>