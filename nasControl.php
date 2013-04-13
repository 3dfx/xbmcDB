<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<head>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="class.css" />
	<style> body {background-color: #D9E8FA;} </style>
</head>
<html>
<body style="padding:0px;">
<?php
	include_once "template/config.php";
	include_once "globals.php";
	include_once "check.php";
	
	if (isAdmin()) {
		$res = null;
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['shutdownBtn'])) {
				shutdownNAS();
			} else
			if (isset($_POST['restartBtn'])) {
				startNAS();
			} else
			if (isset($_POST['delay_shutdown_btn'])) {
				if (isset($_POST['delay_shutdown'])) {
					delayShutdown($_POST['delay_shutdown']);
				}
			} else
			if (isset($_POST['delay_restart_btn'])) {
				if (isset($_POST['delay_restart'])) {
					delayRestart($_POST['delay_restart']);
				}
			}
		}
		
		$nasIP = isset($GLOBALS['NAS_IP']) ? $GLOBALS['NAS_IP'] : 0;
		$serverRunning = !empty($nasIP) ? pingNAS($nasIP) : 0;
?>
	<form action="nasControl.php" name="nascontrol" method="post">
		<table style="width:99%; margin:5px; font:12px verdana,sans-serif;">
			<tr><td colspan="2" style="text-align:center;">
<?php
			echo '<span style="padding:2px 5px; background:';
			if ($serverRunning == 0) {
				echo 'red;">Status: down or not reachable!';
			} else {
				echo 'yellowgreen;">Status: up and running!';
			}
			echo '</span>';
?>
			</td></tr>
			<tr style="height:15px; font:2px"><td colspan="2"></td></tr>
			<tr style="height:25px;"><td colspan="2" style="text-align:center;">
<?php
				if (!$serverRunning) {
?>
					<input type="submit" id="restartBtn" name="restartBtn" value="start NAS" class="okButton" />
<?php
				} else {
?>
					<input type="submit" id="shutdownBtn" name="shutdownBtn" value="shutdown NAS" class="okButton" />
<?php
				}
?>
			</td></tr>
			<tr style="height:15px; font:2px"><td colspan="2"></td></tr>
			<tr>
				<td style="width:70%;">Delay restart<?php $res = getDelay('myScripts/.NAS_RESTART_OFF'); echo ($res > 0 ? ' ('.$res.')' : ($res < 0 ? ' (disabled)' : '')); ?>:</td>
				<td style="width:30%; text-align:right;">
					<input type="text" id="delay_restart" name="delay_restart" style="width:35px;" class="inputbox" />
					<input type="submit" id="delay_restart_btn" name="delay_restart_btn" value="Ok" class="flatBtn" />
				</td>
			</tr>
			<tr>
				<td style="width:70%;">Delay shutdown<?php $res = getDelay('myScripts/.NAS_SHUTDOWN_OFF'); echo ($res > 0 ? ' ('.$res.')' : ($res < 0 ? ' (disabled)' : '')); ?>: </td>
				<td style="width:30%; text-align:right;">
					<input type="text" id="delay_shutdown" name="delay_shutdown" style="width:35px;" class="inputbox" />
					<input type="submit" id="delay_shutdown_btn" name="delay_shutdown_btn" value="Ok" class="flatBtn" />
				</td>
			</tr>
		</table>
<?php
?>
	</table>
	</form>
<?php
	} //if ($admin)
	
	function pingNAS($IP) {
		if (!isAdmin()) { return; }
		exec('ping '.$IP.' -c 1 -W 1 | grep -o \'time=\' | wc -l', $output);
		return $output[0];
	}
	
	function getDelay($filename) {
		if (!isAdmin()) { return; }
		if (!file_exists($filename)) {
			return;
		}

		$datei = file($filename);
		$timestamp = $datei[0];
		if ($timestamp < 0) { return -1; }
		return date("d.m.Y", $timestamp);
	}
	
	function shutdownNAS() {
		if (!isAdmin()) { return; }
		exec('/sbin/nas_shutdown 1', $output);
		return $output;
	}
	
	function startNAS() {
		if (!isAdmin()) { return; }
		exec('/sbin/nas_start 1', $output);
		return $output;
	}

	function delayShutdown($days) {
		if (!isAdmin()) { return; }
		exec('/sbin/delay_shutdown_nas '.$days, $output);
		return $output;
	}

	function delayRestart($days) {
		if (!isAdmin()) { return; }
		exec('/sbin/delay_restart_nas '.$days, $output);
		return $output;
	}
?>
</body>
</html>
