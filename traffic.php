<?php
include_once "auth.php";
include_once "check.php";
include_once "template/functions.php";

if (!isAdmin()) { exit; }


$hgtabelle="#D9E8FA";			# Hintergundfarbe der Tabellen
$tblOUT=$hgtabelle;			# MousOut im Table
$tblOV="#AACFFE";			# MousOver im Table
$tblTiT="#6DA0E7";			# MousOver im Table

$stats = array();
$monate = array();
?>
<HTML>
<HEAD>
	<TITLE>Traffic shizzle</TITLE>
	<link type="text/css" rel="stylesheet" href="mastyle.css">
   <link href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700' rel='stylesheet' type='text/css'>
    
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
    <script id="jqueryui" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/jquery-ui.min.js"></script>
    <script src="//www.google.com/jsapi?key=AIzaSyCZfHRnq7tigC-COeQRmoa9Cxr0vbrK6xw"></script>

    <script type="text/javascript" src="//www.gstatic.com/feedback/api.js"></script>
 
    <script type="text/javascript">
<?php
echo "\t\tfunction mover(cell){ if (!cell.contains(event.fromElement)){ cell.bgColor ='".$tblOV."'; } }\n";
echo "\t\tfunction mout(cell){ if (!cell.contains(event.toElement)){ cell.bgColor ='".$tblOUT."'; } }\n";
/*
C:\Dokumente und Einstellungen\All Users\Anwendungsdaten\Hagel Technologies\DU Meter\
*/
?>

      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Datum', 'Download', 'Upload'],
<?php
	function addArrayValues($src, $dst) {
		if ($src[0] != $dst[0] || $dst[0] == 0) {
			return $src;
		}

		if ($src[0] == 0) {
			return $dst;
		}

		$res = array();
		$res[0] = $src[0];
		$res[1] = $src[1] + $dst[1];
		$res[2] = $src[2] + $dst[2];
		$res[3] = $src[3] + $dst[3];
		return $res;
	}

	function getMax($L) {
		$monate = $GLOBALS["monate"];
		$stat = $GLOBALS["stat"];
		$max = 0;
		for ($j = 1; $j <= count($monate); $j++) {
			$tmp = $stat[$monate[$j]][$L];
			if ($tmp > $max)
				$max = $tmp;
		}

		return $max;
	}

	function mon_repl($strrepl, $dire) {
		$ori = array("-","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
		$rep = array(".","01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
		$ger = array(".","Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez");
		if ($dire == 0) {
			for($x = 0; $x <= 12; $x++) { $strrepl = str_replace($ori[$x], $rep[$x], $strrepl); }
		} else {
			for($x = 0; $x <= 12; $x++) { $strrepl = str_replace($rep[$x].".", $ger[$x]." ", $strrepl); }
		}

		return $strrepl;
	}

	function drawLines() {
		$lValue = $GLOBALS["lValue"];
		$bWidth = $GLOBALS["bWidth"];
		$peak = $GLOBALS["peak"];
		//$peak = $GLOBALS["peak"] - $lValue;

		#//echo "\t\t<TD ALIGN=\"center\" VALIGN=\"bottom\">";
		while ($peak > 0) {
			#//echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\"><BR>";
			#//echo "<IMG SRC=\"imgs/trf_".($peak < 100 ? "0" : "").$peak.".png\"><BR>";
			#//echo "<IMG SRC=\"imgs/trf_spc_wh.png\" HEIGHT=\"".($peak < 1000 ? 30 : 20)."\" WIDTH=\"".$bWidth."\"><BR>";

			$peak -= $lValue;
		}

		#//echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\">";
		#//echo "</TD>\n";
	}

	//$path = "C:\\ProgramData\\Hagel Technologies\\DU Meter\\";
	//$datei = file($path."log.csv");
	$dateiPC = file("log_PC.csv");
	$dateiSRV = file("log_SRV.csv");
	$linesPC = sizeof($dateiPC) - 1;
	$linesSRV = sizeof($dateiSRV) - 1;

	$lineData = array();
	if ($linesSRV > 0) {
		for ($i = $linesSRV; $i > -1 ; $i--) {
			$data = explode(",", $dateiSRV[$i]);
			$data[0] = strtotime($data[0]);

			$ldata = array("","","","");
			if (isset($lineData[$data[0]])) { $ldata = $lineData[$data[0]]; }
			$lineData[$data[0]] = addArrayValues($data, $ldata);
		}
	}
	if ($linesPC > 0) {
		for ($i = $linesPC; $i > -1 ; $i--) {
			$data = explode(",", $dateiPC[$i]);
			$data[0] = strtotime($data[0]);
			
			$ldata = array("","","","");
			if (isset($lineData[$data[0]])) { $ldata = $lineData[$data[0]]; }
			$lineData[$data[0]] = addArrayValues($data, $ldata);
		}
	}

//	asort($lineData, SORT_NUMERIC);
	$lineData = array_values($lineData);

	$TB  = 1099511627776;
	$TiB = 1000000000000;
	$GB  = 1073741824;
	$GiB = 1000000000;
	$MB  = 1048576;
	$MiB = 1000000;

	$stats = $GLOBALS["stats"];
	$monate = $GLOBALS["monate"];

	#echo "<SPAN ID='DAILY' STYLE='position:absolute; left:15px; top:20px;'>\n";
	#$tblTiT = $GLOBALS["tblTiT"];
	#echo "<TABLE cellSpacing=0 cellPadding=2 border=\"0\" bordercolor=\"".$tblTiT."\" bgColor=\"".$tblTiT."\" HEIGHT=\"100%\">\n";
	#echo "\t<TR ALIGN=\"center\"><TD WIDTH=\"100\" align=\"center\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Datum</TD><TD align=\"right\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Download</TD><TD align=\"right\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Upload</TD><TD style=\"text-align:right;\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Ratio</TD></TR>\n";

	$lines = count($lineData);
	$eintraege = array();
	
	if ($lines > 0) {
		$totDL = 0;
		$totUL = 0;
		$stat = array();
		$data = "";
		
		for ($i = 0; $i < $lines-1 ; $i++) {
			//$eintraege = explode(",", $lineData[$i]);
			$eintraege = $lineData[$i];
			$eintraege[0] = date("d-M-Y", $eintraege[0]);

			$data_old = $data;

			$totDL = $totDL + $eintraege[1];
			$totUL = $totUL + $eintraege[2];

			if ($eintraege[1] > $GiB && $eintraege[1] < $GB)
				$eintraege[1] += ($GB - $GiB);

			if ($eintraege[2] > $GiB && $eintraege[2] < $GB)
				$eintraege[2] += ($GB - $GiB);

			if ($eintraege[1] >= $GB) { $send = number_format(round($eintraege[1] / $GB, 2), 2) . " GB"; }
			else { $send = number_format(round($eintraege[1] / $MB, 2), 2) . " MB"; }

			if ($eintraege[2] >= $GB) { $get = number_format(round($eintraege[2] / $GB, 2), 2) . " GB"; }
			else { $get = number_format(round($eintraege[2] / $MB, 2), 2) . " MB"; }

			#echo "\t<TR onMouseOver=mover(this); onMouseOut=mout(this); bgcolor='".$tblOUT."'>";
			$data = mon_repl($eintraege[0],0);
			#echo "<TD CLASS='text1c'>".trim($data)."</TD>";
			if ($send == "") $send = "0 MB";
			#echo "<TD CLASS='text2r'>".trim($send)."</TD>";
			if ($get == "") $get = "0 MB";
			#echo "<TD CLASS='text2r'>".trim($get)."</TD>";

			#if ($eintraege[2] == 0 || $eintraege[1] == 0) {
			#	echo "<TD CLASS='text2r'>0:0</TD>";
			#} else {
			#	if ($eintraege[2] > $eintraege[1]) {
			#		echo "<TD CLASS='text2r'>1:".round($eintraege[2] / $eintraege[1])."</TD>";
			#	} else {
			#		echo "<TD CLASS='text2r'>".round($eintraege[1] / $eintraege[2]).":1</TD>";
			#	}
			#}
			#echo "</TR>\n";
			#echo "\t<TR bgcolor='white'><TD HEIGHT='3' COLSPAN='4'></TR>\n";
			
			$monat_old = substr($data_old, 3, 7);
			$monat = substr($data, 3, 7);

			if ($monat != $monat_old && $monat != "")
				$monate[count($monate)+1] = $monat;

			$stat[$monat][0] = isset($stat[$monat][0]) ? $stat[$monat][0] + $eintraege[1] : $eintraege[1];
			$stat[$monat][1] = isset($stat[$monat][1]) ? $stat[$monat][1] + $eintraege[2] : $eintraege[2];
		}
	}
	#echo "</TABLE>\n";
	#echo "</SPAN>";

	$data = mon_repl($eintraege[0],0);
	$ratio = round($totDL / ($totUL == 0 ? 1 : $totUL), 2);
	$total = $totDL + $totUL;
	//$monTRF = round($total / count($monate) / $GB, 2);
	
	if ($total / count($monate) / $TB > 1)
		$monTRF = number_format(round($total / count($monate) / $TB, 2), 2); // . " TB";
	elseif ($total / count($monate) / $GB > 1)
		$monTRF = number_format(round($total / count($monate) / $GB, 2), 2); // . " GB";
	else
		$monTRF = number_format(round($total / count($monate) / $MB, 2), 2); // . " MB";

	if ($total >= $TB)
		$total = number_format(round($total / $TB, 2), 2); // . " TB";
	elseif ($total >= $GB)
		$total = number_format(round($total / $GB, 2), 2); // . " GB";
	else
		$total = number_format(round($total / $MB, 2), 2); // . " MB";

	$durch = ($totDL + $totUL) / $lines;
	if ($durch >= $TB)
		$durch = number_format(round($durch / $TB, 2), 2); // . " TB";
	if ($durch >= $GB)
		$durch = number_format(round($durch / $GB, 2), 2); // . " GB";
	else
		$durch = number_format(round($durch / $MB, 2), 2); // . " MB";

	if ($totDL >= $TB)
		$totDL = number_format(round($totDL / $TB, 2), 2); // . " TB";
	elseif ($totDL >= $GB)
		$totDL = number_format(round($totDL / $GB, 2), 2); // . " GB";
	else
		$totDL = number_format(round($totDL / $MB, 2), 2); // . " MB";

	if ($totUL >= $TB)
		$totUL = number_format(round($totUL / $TB, 2), 2); // . " TB";
	elseif ($totUL >= $GB)
		$totUL = number_format(round($totUL / $GB, 2), 2); // . " GB";
	else
		$totUL = number_format(round($totUL / $MB, 2), 2); // . " MB";

	//$maxDL = round(getMax(0) / $GB, 0);
	//$maxUL = round(getMax(1) / $GB, 0);
	$maxDL = ceil(getMax(0) / $GB);
	$maxUL = ceil(getMax(1) / $GB);

	$peak = ceil(($maxDL > $maxUL ? $maxDL : $maxUL) / 100) * 100;
	//echo "<SPAN ID='10000' STYLE='position:absolute; left:0px; top:0px;'><FONT SIZE=7>".$maxDL."__".$peak."</FONT></SPAN>";

	if (strlen($ratio) <= 3)
		$ratio .= "0";

	#//echo "\n<SPAN ID='1' STYLE='position:absolute; white-space:nowrap; left:800px; top:0px;'><FONT CLASS='text3 para0'>[3dfx]'s TRAFFiC</FONT></SPAN>\n";
	#//echo "<SPAN ID='2_1' STYLE='position:absolute; white-space:nowrap; left:825px; top:55px; width:450px;'><FONT CLASS='text21'>seit </FONT><FONT CLASS='text3'>".$data."</FONT></SPAN>\n";
	#//echo "<SPAN ID='2_2' STYLE='position:absolute; white-space:nowrap; left:825px; top:75px; width:450px;'><FONT CLASS='text3'>".$lines." </FONT><FONT CLASS='text21'>Tage</FONT><FONT CLASS='uber3'> (</FONT><FONT CLASS='text3'>".round($lines/365, 1)."</FONT><FONT CLASS='text21'> Jahre</FONT><FONT CLASS='uber3'>)</FONT></SPAN>\n";
	#//echo "<SPAN ID='3_1' STYLE='position:absolute; white-space:nowrap; left:815px; top:120px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>ø tägl. Traffic: </FONT></SPAN>\n";
	#//echo "<SPAN ID='3_2' STYLE='position:absolute; white-space:nowrap; left:815px; top:120px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$durch."</FONT></SPAN>\n";
	#//echo "<SPAN ID='3_3' STYLE='position:absolute; white-space:nowrap; left:815px; top:135px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>ø mon. Traffic: </FONT></SPAN>\n";
	#//echo "<SPAN ID='3_4' STYLE='position:absolute; white-space:nowrap; left:815px; top:135px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$monTRF."</FONT></SPAN>\n";
	#//echo "<SPAN ID='5_1' STYLE='position:absolute; white-space:nowrap; left:815px; top:145px; width:310px;'><HR noshade CLASS='uber3'></SPAN>\n";
	#//echo 	"<SPAN ID='5_2' STYLE='position:absolute; white-space:nowrap; left:815px; top:165px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>Total Downloaded: </FONT></SPAN>\n";
	#//echo	"<SPAN ID='5_3' STYLE='position:absolute; white-space:nowrap; left:815px; top:165px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$totDL."</FONT></SPAN>\n";
	#//echo 	"<SPAN ID='5_4' STYLE='position:absolute; white-space:nowrap; left:815px; top:180px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>Total Uploaded: </FONT></SPAN>\n";
	#//echo	"<SPAN ID='5_5' STYLE='position:absolute; white-space:nowrap; left:815px; top:180px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$totUL."</FONT></SPAN>\n";
	//echo "<SPAN ID='6' STYLE='position:absolute; white-space:nowrap; left:815px; top:185px; width:290px;'><HR noshade CLASS='uber3'></SPAN>\n";
	#//echo 	"<SPAN ID='7' STYLE='position:absolute; white-space:nowrap; left:815px; top:195px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>Total Traffic: </FONT></SPAN>\n";
	#//echo	"<SPAN ID='8' STYLE='position:absolute; white-space:nowrap; left:815px; top:195px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$total."</FONT></SPAN>\n";
	#//echo    "<SPAN ID='9' STYLE='position:absolute; white-space:nowrap; left:815px; top:210px; width:310px;'><HR noshade CLASS='uber3'></SPAN>\n";
	#//echo	"<SPAN ID='10' STYLE='position:absolute; white-space:nowrap; left:815px; top:225px; width:310px; height:20px; text-align:left;'><FONT CLASS='text21'>Ratio: </FONT></SPAN>\n";
	#//echo 	"<SPAN ID='11' STYLE='position:absolute; white-space:nowrap; left:815px; top:225px; width:310px; height:20px; text-align:right;'><FONT CLASS='text3'>".$ratio.":1</FONT></SPAN>\n";

	//echo "<P ALIGN='left'>";

	#//echo "<SPAN ID='16' STYLE='position:absolute; left:380px; top:20px;'>\n";
	#//echo "<TABLE CELLSPACING=0>\n";
	#//echo "\t<TR ALIGN=\"center\"><TD WIDTH=\"100\" bgColor=\"".$tblTiT."\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Datum</FONT></TD><TD WIDTH=\"100\" ALIGN=\"right\" bgColor=\"".$tblTiT."\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"green\">Download</FONT></TD><TD WIDTH=\"100\" ALIGN=\"right\" bgColor=\"".$tblTiT."\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"red\">Upload</FONT></TD><TD WIDTH=\"100\" STYLE=\"text-align:right;\" bgColor=\"".$tblTiT."\"><FONT SIZE=2 FACE=\"Verdana\" COLOR=\"white\">Ratio</FONT></TD></TR>\n";
	for ($j = 1; $j <= count($monate); $j++) {
		if ($stat[$monate[$j]][0] >= $TB)
			$stat[$monate[$j]][3] = number_format(round($stat[$monate[$j]][0] / $TB, 2), 2); //." TB";
		elseif ($stat[$monate[$j]][0] >= $GB)
			$stat[$monate[$j]][3] = number_format(round($stat[$monate[$j]][0] / $GB, 2), 2); //." GB";

		if ($stat[$monate[$j]][1] >= $TB)
			$stat[$monate[$j]][4] = number_format(round($stat[$monate[$j]][1] / $TB, 2), 2); //." TB";
		elseif ($stat[$monate[$j]][1] >= $GB)
			$stat[$monate[$j]][4] = number_format(round($stat[$monate[$j]][1] / $GB, 2), 2); //." GB";

//JS-OUT
		echo "          ['".mon_repl($monate[$j],1)."', ".$stat[$monate[$j]][3].", ".$stat[$monate[$j]][4]."]";
		
		
		#//echo #//echo"\t<TR onMouseOver=mover(this); onMouseOut=mout(this); bgcolor='".$tblOUT."'>";
		#//echo "<TD ALIGN=\"right\" CLASS=\"text1\">".mon_repl($monate[$j],1)."&nbsp;</TD>";
		#//echo "<TD ALIGN=\"right\" CLASS=\"text1\"><FONT COLOR=\"green\">".($stat[$monate[$j]][3] == 0 ? "0 GB" : $stat[$monate[$j]][3])."</FONT>&nbsp;</TD>";
		#//echo "<TD ALIGN=\"right\" CLASS=\"text1\"><FONT COLOR=\"red\">".($stat[$monate[$j]][4] == 0 ? "0 GB" : $stat[$monate[$j]][4])."</FONT></TD>";
		#//echo "<TD ALIGN=\"right\" CLASS=\"text2\">".round(($stat[$monate[$j]][1] != 0 ? $stat[$monate[$j]][0] / $stat[$monate[$j]][1] : 1)).($stat[$monate[$j]][1] == 0 ? ":0" : ":1")."</FONT></TD>";
		#//echo "</TR>\n";
		#//echo "\t<TR bgcolor='white'><TD HEIGHT='3' COLSPAN='4'></TR>\n";
		
		if ($j <= count($monate)-1 || $j <= 24) {
			echo ",\r\n";
		}
		
		if ($j > 24) {
			break;
		}
	}
	#//echo "</TABLE>\n";
	#//echo "</SPAN>\n";

	$lValue = 50;
	$bWidth = 10;

	#echo "<SPAN ID='17' STYLE='position:absolute; left:815px; top:280px;'>\n";
	#echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0><TR ALIGN=\"center\">\n\t<TR>\n";

	#drawLines();

/*
	for ($j = 1; $j <= count($monate); $j++) {
		echo "\t\t<TD ALIGN=\"center\" VALIGN=\"bottom\">";
		echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0><TR><TD VALIGN=\"bottom\">";

		$tDL = ceil($stat[$monate[$j]][0] / $GB);
		do {
			$DLr = ceil($tDL % $lValue);
			if ($DLr == 0) $tDL++;
		} while ($DLr == 0);
		$gDL = ($tDL - $DLr) / $lValue;
		//echo $gDL;

		$tMaxD = $peak - $tDL;
		//echo $tDL;
		$tMaxR = ceil($tMaxD % $lValue);
		$MaxR = ($tMaxD - $tMaxR) / $lValue;
		//$MaxR = ($tMaxD - $tMaxR) / $lValue - 1; // -1 für den peak.....
		//echo $MaxR;

		if ($DLr == 0) $MaxR--;
		$toFifty = $lValue - $DLr;
		//echo $toFifty;

		echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\"><BR>";
		$i = 0;
		do {
			echo "<IMG SRC=\"imgs/trf_spc_wh.png\" border=0 HEIGHT=\"".($i < $MaxR ? $lValue : $toFifty)."\" WIDTH=\"".$bWidth."\"><BR>";

			if ($i < $MaxR)
				echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\"><BR>";

			$i++;
		} while ($i <= $MaxR);

		$i = 0;
		do {
			echo "<IMG SRC=\"imgs/trf_spc_dl.png\" HEIGHT=\"".($i == 0 ? $DLr : $lValue)."\" WIDTH=\"".$bWidth."\" TITLE=\"".$stat[$monate[$j]][3]."\"><BR>";
			if ($i < $gDL)
				echo "<IMG SRC=\"imgs/trf_spc_dl_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\" TITLE\"".$stat[$monate[$j]][3]."\"><BR>";

			$i++;
		} while ($i <= $gDL);
		echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\">";

		echo "</TD>";
		echo "<TD VALIGN=\"bottom\">";
		//echo "<IMG SRC=\"imgs/trf_spc_dl.png\" HEIGHT=\"".($stat[$monate[$j]][0] / $GB)."\" WIDTH=\"".$bWidth."\" TITLE\"".$stat[$monate[$j]][3]."\">";
		//echo "<IMG SRC=\"imgs/trf_spc_ul.png\" HEIGHT=\"".($stat[$monate[$j]][1] / $GB)."\" WIDTH=\"".$bWidth."\" TITLE\"".$stat[$monate[$j]][4]."\">";
		$tUL = ceil($stat[$monate[$j]][1] / $GB);
		do {
			$ULr = ceil($tUL % $lValue);
			if ($ULr == 0) $tUL++;
		} while ($ULr == 0);
		$gUL = ($tUL - $ULr) / $lValue;

		$tMaxU = $peak - $tUL;
		$tMaxR = ceil($tMaxU % $lValue);
		$MaxR = ($tMaxU - $tMaxR) / $lValue;
		//$MaxR = ($tMaxU - $tMaxR) / $lValue - 1; // -1 für den peak.....

		if ($ULr == 0) $MaxR--;
		$toFifty = $lValue - $ULr;
		//if ($toFifty == $lValue) $toFifty--;

		echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\"><BR>";
		$i = 0;
		do {
			echo "<IMG SRC=\"imgs/trf_spc_wh.png\" border=0 HEIGHT=\"".($i < $MaxR ? $lValue : $toFifty)."\" WIDTH=\"".$bWidth."\"><BR>";

			if ($i < $MaxR)
				echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\"><BR>";

			$i++;
		} while ($i <= $MaxR);

		$i = 0;
		do {
			echo "<IMG SRC=\"imgs/trf_spc_ul.png\" HEIGHT=\"".($i == 0 ? $ULr : $lValue)."\" WIDTH=\"".$bWidth."\" TITLE\"".$stat[$monate[$j]][4]."\"><BR>";
			if ($i < $gUL)
				echo "<IMG SRC=\"imgs/trf_spc_ul_line.png\" HEIGHT=\"1\" WIDTH=\"".$bWidth."\" TITLE\"".$stat[$monate[$j]][4]."\"><BR>";

			$i++;
		} while ($i <= $gUL);

		echo "<IMG SRC=\"imgs/trf_spc_wh_line.png\">";
		echo "</TD></TR></TABLE>";
		echo "</TD>\n";
	}
*/

	#drawLines();

	#echo "\t</TR>\n\t<TR>\n";
	#echo "\t\t<TD ALIGN=\"center\" VALIGN=\"TOP\"><IMG SRC=\"imgs/trf_000.png\"></TD>\n";

	#for ($j = 1; $j <= count($monate); $j++) {
	#	$lala = explode(" ", mon_repl($monate[$j],1));
	#	echo "\t\t<TD ALIGN=\"center\"><IMG SRC=\"imgs/trf_".$lala[1].".png\"><BR><IMG SRC=\"imgs/trf_".$lala[0].".png\"></TD>\n";
	#}
	#echo "\t\t<TD ALIGN=\"center\" VALIGN=\"TOP\"><IMG SRC=\"imgs/trf_000.png\"></TD>\n";

	#echo "\t</TR>\n</TABLE>\n";
	#echo "</SPAN>\n";
?>
        ]);
        
        var options = {
          title: '3dfx traffic'
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
   </script>
</HEAD>
<BODY !oncontextmenu="return false" ondragstart="return false">
	<div id="chart_div"></div>
</BODY>
</HTML>