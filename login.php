<?php
	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	
	startSession();
	if (isLogedIn()) { logedInSoRedirect(); }

	$reffer = (isset($_SESSION['reffer']) ? $_SESSION['reffer'] : null);
	if ($reffer == null) { $reffer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null; }
	logRefferer($reffer);
	
	$logedInAs = 'FAiL';
	$redirect = false;
	$asAdmin = false;
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$input_username = (isset($_POST['username']) ? $_POST['username'] : '');
		$input_passwort = (isset($_POST['passwort']) ? $_POST['passwort'] : '');

		$gast_username = $GLOBALS['gast_username'];
 		$gast_passwort = $GLOBALS['gast_passwort'];
 		$login_username = $GLOBALS['login_username'];
 		$login_passwort = $GLOBALS['login_passwort'];
 		
		// Benutzername und Passwort werden überprüft
		if (!empty($login_username) && !empty($login_passwort) &&
		    $input_username == $login_username && $input_passwort == $login_passwort) {
		    
			$_SESSION['angemeldet'] = true;
			unset($_SESSION['gast']);

			$logedInAs = 'ADMiN';
			$asAdmin = true;
			$redirect = true;
		}
		
		if (!empty($gast_username) && !empty($gast_passwort) ||
		    $input_username == $gast_username && $input_passwort == $gast_passwort) {
		    
			$_SESSION['gast'] = true;
			unset($_SESSION['angemeldet']);

			$logedInAs = 'GUEST';
			$redirect = true;
		}

		logLogin();
		if ($redirect) { logedInSoRedirect(); }
	}

function logedInSoRedirect() {
	$path = dirname($_SERVER['PHP_SELF']);
	header('Location: '.getHostnamee().($path == '/' ? '' : $path));
	exit;
}

function logLogin() {
	$LOCALHOST = isset($GLOBALS['LOCALHOST']) ? $GLOBALS['LOCALHOST'] : false;
	$HOMENETWORK = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;
	$asAdmin = $GLOBALS['asAdmin'];

	if (!($LOCALHOST || $HOMENETWORK)) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$host = gethostbyaddr($ip);
		$hostname = $_SERVER['HTTP_HOST'];

		$logedInAs = $GLOBALS['logedInAs'];
		$username = $GLOBALS['input_username'];
 		$passwort = $GLOBALS['input_passwort'];
 		$login_passwort = $GLOBALS['login_passwort'];

		$datei = "./logs/loginLog.php";

		$datum = strftime("%d.%m.%Y");
		$time = strftime("%X");
		$logPass = $passwort;
		if ($asAdmin || $passwort == $login_passwort) {
			$logPass = '****';
		}
		
		$input = $datum."|".$time."|".$ip."|".$host."|".$hostname."|".$logedInAs."|".$username."|".$logPass."\n";

		$fp = fopen($datei, "r");
		while(!feof($fp)) {
			$eintraege = fgets($fp, 1000);
			$input .= $eintraege;
		}
		fclose($fp);

		$input = str_replace('<? /*', '', $input);
		$input = str_replace('*/ ?>', '', $input);
		$input = str_replace("\n\n", "\n", $input);
		$input = '<? /*'."\n".$input.'*/ ?>';

		$fp = fopen($datei, "w+");
		fputs($fp, $input);
		fclose($fp);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<title>XBMC Database</title>
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="class.css" />
		<script type="text/javascript" src="./template/js/jquery.min.js"></script>
		<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.js"></script>
		<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
		<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
		<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
		<script type="text/javascript">
			$(document).ready(function() { $('#username').focus(); });
			function animateNav() {
				var animTime = 500;
				if ($.browser.mozilla) { animTime = 250; }
				$('#navLogin').animate({ "top": "0%" }, animTime, function() { return true; });
			}
		</script>
	</head>
	<body style="overflow-x:hidden; overflow-y:auto;">
	<div class="navbar<?php echo ($INVERSE ? ' navbar-inverse' : ''); ?>" id="navLogin" style="margin:0px -15px; position:absolute; top:45%; width:102%;">
		<div class="navbar-inner" style="height:30px;">
			<div class="container" style="margin:0px auto; width:auto;">
				<div class="nav-collapse" style="margin:0px;">
				<a class="brand navBarBrand" href="#">xbmcDB</a>
				</div>
				<form action="login.php" method="post" class="navbar-search pull-right" style="height:25px;" onsubmit="return animateNav();">
				<ul class="nav" style="color:#FFF;">
					<li style="margin:0px;">
						<input class="search-query span1" style="margin:4px 10px; width:75px; height:10px;" type="text" id="username" name="username" placeholder="username" />
						<input class="search-query span1" style="margin:4px 10px; width:75px; height:10px;" type="password" id="passwort" name="passwort" placeholder="password" />
						<input type="submit" value="Ok" class="btn" style="height:20px; padding-top:0px; margin:5px 10px;" onfocus="this.blur();"/>
					</li>
				</ul>
				</form>
			</div>
		</div>
	</div>
	</body>
</html>
