<?php
include_once "check.php";
include_once "./template/functions.php";

	if (!isAdmin()) { return; }
	if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET)) {
		$action = isset($_GET['act']) ? urldecode(getEscGet('act')) : null;
		if ($action == 'delete') {
			$ipDel = isset($_GET['ip']) ? urldecode(getEscGet('ip')) : null;
			removeBlacklist($ipDel);
		}
	}
?>
<html>
<head>
	<link type="text/css" rel="stylesheet" href="./class.css">
	<title>Blacklist control</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript">
		function deleteBlock(ip) {
			if (ip == null || ip === '') { return; }

			var frage = unescape("Delete block '" + ip + "'?");
			var answer = confirm(frage);
			if (!answer) { return; }

			window.location.href='./blacklistControl.php?act=delete&ip=' + ip;
		}
	</script>
</head>
<body !oncontextmenu="return false" ondragstart="return false" style="width:99%;">
<?php
	echo "<table class='film' style='margin:15px -10px; width:99%; max-width:99%;'>\r\n";
	echo "<tr>";
	#echo "<th colspan='".($which == 1 ? 6 : 8)."' style='padding:5px 5px;'>".$title."</th>";
	echo "<th colspan='5' style='padding:5px 5px; text-align:center;'>Blacklist control</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<th style='padding:2px 5px; text-align:right;'>#</th>";
	echo "<th style='padding:2px 10px;'>Date</th>";
	echo "<th style='padding:2px 10px;'>IP</th>";
	echo "<th style='padding:2px 0; text-align:right;'>Count</th>";
	echo "<th style='padding:2px 10px; text-align:center;'>Delete</th>";
	echo "</tr>\r\n";

	$x = 1;
	$blacklist = restoreBlacklist();
	if (!empty($blacklist)) {
		#arsort($blacklist, SORT_NUMERIC);
		foreach($blacklist as $ip => $entry) { $dates[$ip] = $entry['date']; }
		array_multisort($dates, SORT_DESC, SORT_NUMERIC, $blacklist);

		foreach($blacklist as $ip => $entry) {
			$date  = $entry['date'];
			$count = $entry['count'];
			$color = isBlacklisted($ip) ? ' color:red;' : ' color:#6699CC;';

			echo "<tr style='height:15px;'>";
			echo "<td style='padding:2px 5px; text-align:right;".$color."'>".($x++)."</td>";
			echo "<td style='padding:2px 10px;".$color."'>".strftime("%d.%m.%Y <b>%X</b>", $date)."</td>";
			#echo "<td style='padding:2px 10px;".$color."'>".$date."</td>";
			echo "<td style='padding:2px 10px;".$color."'>".$ip."</td>";
			echo "<td style='padding:2px 0;".$color." text-align:right;'>".$count."</td>";
			echo "<td style='padding:2px 10px;".$color." text-align:center;'><img src='./img/del.png' style='height:18px; width:18px; cursor:pointer;' onclick='deleteBlock(\"".$ip."\"); return false;'></td>";
			echo "</tr>\r\n";
		}
	}
	echo "</table>\r\n";
?>
</body>
</html>
