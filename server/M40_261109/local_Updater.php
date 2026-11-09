<?php // Listbox beelogger

$Softwareversion = "M.40";//vom 24.09.2026 - local_Updater.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);  // script may run up to 5 minutes

// Update Verzeichnisse
$main_dir = "../";



echo '<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>beelogger Updater Sketche / Zip-File </title>
    <link rel="shortcut icon" href="favicon-32x32.png" />
  <meta name="description" content="local_Updater">
</head>
<body>';


$timestamp = time();
$datum     = date("d.m.Y - H:i", $timestamp);
echo $datum."<BR>";

if ((!isset($_GET["X"])) AND (!isset($_GET["U"]))) {
    echo "Hiermit können alle lokalen beelogger-Verzeichnisse automatisch mit der neuen Version  versorgt werden. <br>";
    echo "<br> Aktion bitte mit <br> ?U = Update PHP-Files &nbsp;&nbsp;&nbsp; all accounts";
    echo "<br> ?X = Update Icons / Sprache &nbsp;&nbsp;&nbsp; all accounts<br>";
}
echo "<br> actual PHP-Version: ".phpversion()."<br>";


echo "<br>akt. Verzeichnis: ".getcwd()."<br><br>";
$Version = file_get_contents("./beelogger1/beelogger_show.php", FALSE, NULL, 870, 23);
echo "beelogger_show-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger1/beelogger_log.php", FALSE, NULL, 870, 23);
echo "beelogger_log-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger1/beelogger_config.php", FALSE, NULL, 857, 23);
echo "beelogger_config-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger1/beelogger_wetter.php", FALSE, NULL, 867, 23);
echo "beelogger_wetter-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger1/CSVedit.php", FALSE, NULL, 870, 23);
echo "CSVedit-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger_mobileWatch.php", FALSE, NULL, 896, 23);
echo "beelogger_mobileWatch-Version: ".$Version."<br>";

$Version = file_get_contents("./beelogger_mobileCheck.php", FALSE, NULL, 868, 23);
echo "mobileCheck-Version: ".$Version."<br>";

$Version = file_get_contents("./scraper.php", FALSE, NULL, 840, 23);
echo "scraper-Version: ".$Version."<br>";

echo "<h2>Ziel:&nbsp;".$main_dir."<br> directories to update:<br></h2>";

if ((!isset($_GET["U"])) AND (!isset($_GET["X"]))) { // show if no option given
    $mydir = "..//".basename(getcwd());

    $dir_s = scandir($main_dir);
    natcasesort($dir_s);
    if (file_exists($main_dir."/beelogger1/beelogger_show.php")) {
        echo '<font SIZE=4 COLOR=RED><br><b> Error: beelogger php-scripts need "account" directory !</b></font>';
        echo '<font SIZE=4 COLOR=Green><br><b> Please move Script-Update directory one directory level up.<br></b></font>';
    }
    else {
        foreach ($dir_s as $a_dir) {
        if (is_dir($main_dir."/".$a_dir)) {
                echo $a_dir."<br>";
            $x_a_dir = $main_dir."/".$a_dir;
                $sub_dirs = scandir($x_a_dir);
                natcasesort($sub_dirs);
                foreach ($sub_dirs as $subdir) {
                    $f = $x_a_dir."/".$subdir."/beelogger_show.php";
                    if (file_exists($f)) {
                        echo "<font SIZE=4 COLOR=GREEN><b>".$x_a_dir."/".$subdir."&nbsp;&nbsp; found <br> </b></font>";
                    }
                }
            }
        }
    }
    echo "---------------------------------------------------------------------------------";
}


echo "<h2>&nbsp;&nbsp; beelogger PHP files";
if(!isset($_GET["U"])){
    echo "<font COLOR=ORANGE>&nbsp;&nbsp; not&nbsp;&nbsp;</font>";
}
echo " updating!<br></h2>";
if(!isset($_GET["X"])){
    echo "<h2><font COLOR=ORANGE>&nbsp;&nbsp; not&nbsp;&nbsp;</font> updating icons / Sprache!</h2>";
} else
    echo "<h2> updating icons / Sprache!<BR></h2>";



function rrmdir($thedir){
    if (is_dir($thedir)) {
        $objects = scandir($thedir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($thedir."/".$object) == "dir")
                    rrmdir($thedir."/".$object);
                else
                    unlink($thedir."/".$object);
            }
        }
        reset($objects);
        rmdir($thedir);
    }
}


$thisdir = basename(getcwd()); // don't update the source 
$dir_s   = scandir($main_dir);
natcasesort($dir_s);
foreach ($dir_s as $a_dir) {
    $do_the_update = 0;
    if (isset($_GET["A"])) { // Update all ?U&A
        if (($a_dir != ".") && ($a_dir != "..") && ($a_dir != "update") && ($a_dir != $thisdir)) { // this will update all accounts
            $do_the_update = 1;
        }
    } else { // Update just these  ?U
        if (($a_dir != ".") && ($a_dir != "..") && ($a_dir != "update") && ($a_dir != $thisdir)
        //AND ($a_dir == "bee") ){  // comment out this line to update just the one account
            ) { // this line is active to update all accounts, therefore: ?U and ?U&A will be the same action
            $do_the_update = 1;
        }
    }
    
    if (($do_the_update) && ((isset($_GET["U"])) OR (isset($_GET["X"])))) { // show info 

        $x_a_dir  = $main_dir."/".$a_dir;
        //	echo "is_dir: ".$x_a_dir."  ";if (is_dir($x_a_dir))echo "ja";else echo "nein"; echo"<BR><BR>";
        //  echo $main_dir."/beelogger1/beelogger_show.php<BR>";
        if (file_exists($main_dir."/beelogger1/beelogger_show.php")) {
            echo '<font SIZE=4 COLOR=RED><br><b> Error: beelogger php-scripts need "account" directory!</b></font>';
        }
        //echo "<BR>".$x_a_dir." flat:".$flat_dir."<br>";
        else{
            if (is_dir($x_a_dir)) {
                echo "<font SIZE=4 COLOR=GREEN><br><b>".$x_a_dir.": &nbsp;&nbsp;&nbsp;</b></font>";           
                if (file_exists($x_a_dir."/local_Updater.php")) { // AlleAktuellenDateien
                    echo "<font SIZE=4 COLOR=ORANGE><b> ... will be skipped. &nbsp;&nbsp;&nbsp;</b></font>";
                }
                else{
                    $copy_once =0;
                    $sub_dirs = scandir($x_a_dir);
                    natcasesort($sub_dirs);
                    foreach ($sub_dirs as $subdir) {
                        $f = $x_a_dir."/".$subdir."/beelogger_show.php";
                        if (file_exists($f)) {
                            echo "<font SIZE=4 COLOR=GREEN><b>".$x_a_dir."/".$subdir."&nbsp;&nbsp; found </b></font>";
                            beelogger_update();
                        }
                    }
                }
            }
        }//else echo $x_a_dir.": no<br>"; // no account dir exists
    } // do_update
} //foreach account
echo "<br>---------------------------------------------------------------------------------";

echo "<br>---------------------------------------------------------------------------------";

function beelogger_update(){
    
    global $x_a_dir, $subdir, $copy_once;

    if (isset($_GET["U"])) { // Update files aktiv?
        
        if (is_dir($x_a_dir."/".$subdir)) {
   
            if (file_exists($x_a_dir."/local_Updater.php")) { // source dir
                echo "<font SIZE=4 COLOR=RED><b> ... skipped. &nbsp;&nbsp;&nbsp;</b></font>";
            } else { // dir exists
					  
                $showname = $x_a_dir."/".$subdir."/beelogger_show.php";
                $logname  = $x_a_dir."/".$subdir."/beelogger_log.php";
                if (file_exists($showname)) {
                    
                    echo "<b><br>".$showname." found ...&nbsp;&nbsp; </b>";
                    copy("./beelogger1/beelogger_config.php", $x_a_dir."/".$subdir."/beelogger_config.php");
                    echo "-config ";
                    copy("./beelogger1/beelogger_wetter.php", $x_a_dir."/".$subdir."/beelogger_wetter.php");
                    echo "-wetter ";
                    copy("./beelogger1/CSVedit.php", $x_a_dir."/".$subdir."/CSVedit.php");
                    echo "-CSVedit ";
                    // copy beelogger_show -----------------------------------
                    copy("./beelogger1/beelogger_show.php", $x_a_dir."/".$subdir."/beelogger_show.php");
                    echo "-show ";
                    copy("./beelogger1/beelogger_sml.php", $x_a_dir."/".$subdir."/beelogger_sml.php");
                    echo "-sml ";
                    // eventuell copy beelogger_log-----------------------------------
                    if (file_exists($logname)) {
                        copy("./beelogger1/beelogger_log.php", $x_a_dir."/".$subdir."/beelogger_log.php");
												copy("./beelogger1/lora_downlink.php", $main_dir."/".$a_dir."/".$subdir."/lora_downlink.php");
												echo "-log ";
                    }
                    echo "<br>";
                    // these files only once per account
                    if ($copy_once == 0) {
                        $copy_once = 1;
                        copy("./beelogger_cron21.php", $x_a_dir."/beelogger_cron21.php");
                        echo "<font SIZE=4 COLOR=GREEN><br><b> &nbsp;&nbsp;&nbsp; copy ... Errorchecker files.. </b></font>";
                        copy("./Errorchecker.php", $x_a_dir."/Errorchecker.php");
                        if (!file_exists($x_a_dir."/ErrorcheckerData.php")) copy("./ErrorcheckerData.php", $x_a_dir."/ErrorcheckerData.php");
                        if (!file_exists($x_a_dir."/ErrorcheckerData_Error.php")) copy("./ErrorcheckerData_Error.php", $x_a_dir."/ErrorcheckerData_Error.php");
                        echo "<font SIZE=4 COLOR=GREEN><br><b> &nbsp;&nbsp;&nbsp; copy ... mobileWatch /-Check etc. </b></font>";
                        // copy beelogger_mobileWatch -----------------------------------
                        copy("./beelogger_mobileWatch.php", $x_a_dir."/beelogger_mobileWatch.php");
                        echo "-mobileWatch ";
                        // copy beelogger_mobileCheck-----------------------------------
                        copy("./beelogger_mobileCheck.php", $x_a_dir."/beelogger_mobileCheck.php");
                        echo "-mobileCheck ";
                        // copy dygraph-----------------------------------
                        copy("./dygraph.js", $x_a_dir."/dygraph.js");
                        echo "-dyjs ";
                        // copy dygraph-----------------------------------
                        copy("./dygraph_beelogger.css", $x_a_dir."/dygraph_beelogger.css");
                        echo "-dycss ";
                        // copy scrapper-----------------------------------
                        copy("./scraper.php", $x_a_dir."/scraper.php");
                        echo "-scrapper <br><br>";
                    } // copy_once
                } // if there is a beelogger_show
            } // source dir
        } // is_dir
    } //if U
    
    
    if (isset($_GET["X"])) { // Update Sprache / Icons aktiv?
        
        if (!file_exists($x_a_dir."/local_Updater.php")) { // source dir
            if (($subdir == "beelogger_icons") AND strpos($x_a_dir, "beelogger_icons")) {
                $dest_icons = $x_a_dir."/beelogger_icons";
                echo "<br>".$dest_icons;
                if (file_exists($dest_icons)) {
                    rrmdir($dest_icons);
                    echo " -  geloescht";
                }
                if (!file_exists($dest_icons)) {
                    echo " ... ";
                    mkdir($dest_icons, 0777, true);
                    $verzeichnis = opendir("beelogger_icons");
                    echo " und neu";
                    while ($Xfile = readdir($verzeichnis)) { // Verzeichnis öffnen und auslesen
                        if ($Xfile != "." AND $Xfile != ".." AND $Xfile != "AnalyseIcons")
                            copy("beelogger_icons/".$Xfile,$dest_icons."/".$Xfile); // Datei kopieren
                    }
                    closedir($verzeichnis);
                    echo " angelegt.";
                }
            } // if icons
            if (($subdir == "beelogger_sprachfiles") AND strpos($x_a_dir, "beelogger_sprachfiles")) {
                $dest_sprache = $x_a_dir."/beelogger_sprachfiles";
                echo "<br>".$dest_sprache;
                if (file_exists($dest_sprache)) {
                    rrmdir($dest_sprache);
                    echo " -  geloescht";
                }
                if (!file_exists($dest_sprache)) {
                    echo " ... ";
                    mkdir($dest_sprache, 0777, true);
                    $verzeichnis = opendir("beelogger_sprachfiles");
                    echo " und neu ";
                    while ($Xfile = readdir($verzeichnis)) { // Verzeichnis öffnen und auslesen
                        if ($Xfile != "." AND $Xfile != "..")
                            copy("beelogger_sprachfiles/".$Xfile,$dest_sprache."/".$Xfile); // Datei kopieren
                    }
                    closedir($verzeichnis);
                    echo " angelegt.";
                }
            } // if sprachfiles
        } // source dir
    } // if X
} // end function


//##########################################################
// Split Anmerkungen
//##########################################################

// Routine splittes notesold.csv in notesArchiv - Dateien auf.
//  zu jeder Archiv-Datei wird eine  notesArchiv... .csv erstellt 


$Execute = 0;
if (isset($_GET["U"])) {$Execute = 1;}  // 1 für aktiv


echo '<!DOCTYPE html><html lang="de"><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"></head>';

echo '<h1><font color="green">Notes Archive  erstellen:</h1><br>';

if ($Execute == 0) echo '<h2><font color="blue">Das ist nur ein Testlauf!</h2><br></font>';
else echo '<h2><font color="orange">Notes Archive aus "notes_old" werden erstellt:</h2><br></font>';

echo "<br>akt. Verzeichnis: ".getcwd()."<br><br>";
echo"<br>";

$mydir = $main_dir.basename(getcwd()); //echo $mydir."<br>";
   
$files = scandir($main_dir);
natcasesort($files);
foreach ($files as $file) { // for all accounts
	//  nur Account Directories
  if ($file != "." && $file != ".." && $file != "X_beelogger_icons" && $file != "X_beelogger_sprachfiles" && $file != "archiv")	{
	$accountdir = $main_dir.$file;
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

if($Execute ==1){
	$file_a = $beeloggerDir."/notes_old.csv";
	$file_b = $beeloggerDir."/notes_oldsave.csv";
	if (file_exists($file_a)){
		rename ($file_a,$file_b);
		echo " - notes_old.csv umbenennen<br>";
	}
}

//#########################################
			}  // is beelogger 
		  } // valid file
		} // foreach
	  } //is_dir
	}
} // for all aaccounts

echo "<br<br>alles fertig<br>";


?>