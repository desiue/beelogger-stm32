<?php
/*
 * lora_downlink.php – Serverquittung für LoRa-beelogger per TTN-Downlink (Test P7)
 * 2026-11-08, desiue (github.com/desiue/beelogger-stm32)
 *
 * Bei WLAN/LTE antwortet beelogger_log.php mit "<Zeit>T<Intervall>...ok *".
 * Bei LoRa verwirft TTN diese Antwort. Diese Datei schickt Intervall und Uhrzeit
 * stattdessen als Downlink (FPort 11) über die Push-URL aus dem Webhook-Header.
 * Voraussetzung: im TTN-Webhook "Downlink API key" gesetzt (Recht: traffic down write).
 *
 * Einbindung in beelogger_log.php, direkt vor der Antwortzeile in Auswertung():
 *   if(isset($_GET["LORA"]) && file_exists("lora_downlink.php")){include_once("lora_downlink.php");lora_downlink($MessIntervall);}
 *
 * Payload FPort 11, 7 Byte, big endian:
 *   Byte 0     FCnt des Uplinks, zu dem der Downlink erzeugt wurde (unteres Byte)
 *   Byte 1..4  Empfangszeit dieses Uplinks, Format wie WLAN-Quittung
 *              (Ortszeit MEZ/MESZ als Unixzeit), 0 = keine Zeit
 *   Byte 5     Abstand der letzten beiden Uplinks in Minuten, 0 = unbekannt
 *   Byte 6     Messintervall in Minuten (5..60), 0 = keine Änderung
 * Der Downlink kommt meist noch im RX-Fenster desselben Uplinks an (Test 08.11.),
 * sonst nach dem nächsten Uplink. Der Logger stellt die Uhr
 *   bei Byte 0 == eigener FCnt     auf Byte 1..4,
 *   bei Byte 0 == eigener FCnt - 1 auf Byte 1..4 + Abstand (nur wenn Abstand > 0).
 *
 * Gesendet wird nur, wenn sich das Intervall ändert oder die Uhr heute noch nicht
 * gestellt wurde (TTN Fair Use: ca. 10 Downlinks pro Tag).
 */

function lora_downlink($MessIntervall){
  global $jsd_TTN;
  $stateFile="lora_downlink_state.json";
  $logFile="lora_downlink.log";

  $up=isset($jsd_TTN['uplink_message'])?$jsd_TTN['uplink_message']:null;
  if(!$up || !isset($up['f_cnt'])) return;                    // kein TTN-Uplink (Helium o. ä.)
  $fcnt=(int)$up['f_cnt'];
  $rx=isset($up['received_at'])?strtotime($up['received_at']):time();
  if(!$rx)$rx=time();

  $st=array();
  if(file_exists($stateFile)){$st=json_decode(file_get_contents($stateFile),true);if(!is_array($st))$st=array();}

  $tRx=$rx+date("Z",$rx);                                     // Ortszeit wie WLAN-Quittung (MEZ/MESZ)
  $gapMin=0;                                                   // Abstand der letzten beiden Uplinks
  if(isset($st['fcnt'],$st['rx']) && $fcnt==$st['fcnt']+1){
    $g=(int)round(($rx-$st['rx'])/60);
    if($g>=1 && $g<=180) $gapMin=$g;
  }

  $iv=(int)$MessIntervall;
  if($iv<5||$iv>60)$iv=0;
  $heute=date("Y-m-d");
  $ivNeu=($iv>0 && (!isset($st['iv']) || $st['iv']!=$iv));
  $zeitNeu=(!isset($st['tag']) || $st['tag']!=$heute);

  $st['fcnt']=$fcnt; $st['rx']=$rx;
  $msg="";
  if($ivNeu || $zeitNeu){
    $h=function_exists('getallheaders')?array_change_key_case(getallheaders(),CASE_LOWER):array();
    $url=isset($h['x-downlink-replace'])?$h['x-downlink-replace']:'';
    $key=isset($h['x-downlink-apikey'])?$h['x-downlink-apikey']:'';
    if($url && $key && preg_match('#^https://[a-z0-9.\-]+\.cloud\.thethings\.network/#',$url)){
      $pl=chr($fcnt&0xFF).pack("N",$tRx).chr($gapMin).chr($iv);
      $body=json_encode(array('downlinks'=>array(array('f_port'=>11,'frm_payload'=>base64_encode($pl),'priority'=>'NORMAL'))));
      $ctx=stream_context_create(array('http'=>array('method'=>'POST','timeout'=>5,'ignore_errors'=>true,
        'header'=>"Authorization: Bearer ".$key."\r\nContent-Type: application/json\r\n",'content'=>$body)));
      $res=@file_get_contents($url,false,$ctx);
      $code=isset($http_response_header[0])?$http_response_header[0]:'keine Antwort';
      $ok=(strpos($code,' 200')!==false);
      if($ok){ if($iv>0)$st['iv']=$iv; $st['tag']=$heute; }
      $msg="FCnt $fcnt Downlink iv=$iv t=".date("Y-m-d H:i:s",$rx)." gap=$gapMin -> $code";
    } else $msg="FCnt $fcnt Downlink nötig, aber kein Downlink-Header (Webhook ohne Downlink API key?)";
  }
  file_put_contents($stateFile,json_encode($st));
  if($msg!="")file_put_contents($logFile,date("Y-m-d H:i:s")." ".$msg."\n",FILE_APPEND);
}
?>
