<?php
	include_once "auth.php";
	include_once "check.php";
	include_once "template/functions.php";
	
	if (!isAdmin()) { exit; }
	
	$which = 1;
	$file = 'logs/reffer.php';
	$title = 'Refferer-log';
	if (isset($_GET['which'])) {
		$which = $_GET['which'];
		if ($which == 2) {
			$file = 'logs/loginLog.php';
			$title = 'Login-log';
		}
	}
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html><head><title><?php echo $title; ?></title><head><body !oncontextmenu="return false" ondragstart="return false">
<link type="text/css" rel="stylesheet" href="./class.css">
<?php
	$datei = file($file);
	$linien = sizeof($datei);
	$x = 0;
	
	echo "<table class='film' style='margin-top:15px;'>\n";
	echo "<tr>";
	echo "<th colspan='".($which == 1 ? 6 : 8)."' style='padding:5px 5px;'>".$title."</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<th style='padding:2px 5px; text-align:right;'>#</th>";
	echo "<th style='padding:2px 10px;'>Date</th>";
	echo "<th style='padding:2px 10px;'>Time</th>";
	echo "<th style='padding:2px 10px;'>IP</th>";
	echo "<th style='padding:2px 10px;'>Host</th>";
	if ($which == 1) {
		echo "<th style='padding:2px 10px;'>Refferer</th>";
		
	} else if ($which == 2) {
		echo "<th style='padding:2px 10px;'>Logged in as</th>";
		echo "<th style='padding:2px 10px;'>Username</th>";
		echo "<th style='padding:2px 10px;'>Password</th>";
	}
	echo "</tr>";
	
	if ($linien > 0) {
		for ($i = 1; $i < $linien-1; $i++) {
			if ($datei[$i] != "") {
				$eintraege = explode ("|", $datei[$i]);
				$eintraege[4] = trim($eintraege[4]);
				
				if (strlen($eintraege[4]) > 35) {
					$show_p = substr($eintraege[4], 0, 32)."...";
				} else {
					$show_p = $eintraege[4];
				}
				
				$exit  = false;
				$admin = false;
				$color = '';
				if ($which == 1) {
					$exit = isset($eintraege[5]) && $eintraege[5] == 1;
				} else if ($which == 2) {
					if (isset($eintraege[5])) {
						if ($eintraege[5] == 'FAiL') {
							$exit  = true;
							$color = ' color:red;';
							
						} else if ($eintraege[5] == 'ADMiN') {
							$admin = true;
							$color = ' color:#6699CC;';
						}
					}
				}
				
				echo "<tr style='height:15px;'>";
				echo "<td style='padding:2px 5px; text-align:right;".$color."'>".($x+1)."</td>";
				echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[0])."</td>";
				echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[1])."</td>";
				echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[2])."</td>";
				echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[3])."</td>";
				
				if ($which == 1) {
					if (substr_count($eintraege[4], "http") || substr_count($eintraege[4], "www")) {
						echo "<td style='padding:2px 5px;".$color."'><a style='font: 11px Verdana, Arial' href='".(substr_count($eintraege[4], "http") ? '' : 'http://').$eintraege[4]."' target='_blank'".($show_p != $eintraege[4] ? " title='".$eintraege[4]."'" : "").">".$show_p."</a></td>";
					} else {
						echo "<td style='padding:2px 5px;".$color."'>".$show_p."</td>";
					}
				}
				
				if ($which == 2) {
					echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[5])."</td>";
					echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[6])."</td>";
					echo "<td style='padding:2px 10px;".$color."'>".trim($eintraege[7])."</td>";
				}
				echo "</TR>";
				
				if (++$x >= 20) { break; }
			}
		}
	}
	echo "</table>\n";
?>
</body></html>