<?php
	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	
	$hostname = $_SERVER['HTTP_HOST'];
	$path     = dirname($_SERVER['PHP_SELF']);
	
	startSession();
	if (isLogedIn()) { logedInSoRedirect(); }
	
	$reffer = (isset($_SESSION['reffer']) ? $_SESSION['reffer'] : null);
	if (empty($reffer)) { $reffer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null; }
	logRefferer($reffer);
	
	$logedInAs = '';
	$redirect  = false;
	$asAdmin   = false;
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$input_username = isset($_POST['username']) ? $_POST['username'] : null;
		$input_passwort = isset($_POST['passwort']) ? $_POST['passwort'] : null;
		
		if (!(empty($input_username) || empty($input_passwort))) {
			$login_username = $GLOBALS['login_username'];
			$login_passwort = $GLOBALS['login_passwort'];
			$gast_users     = $GLOBALS['gast_users'];
			
			// Benutzername und Passwort werden überprüft
			if ($input_username == $login_username && $input_passwort == $login_passwort) {
				
				$asAdmin   = true;
				$redirect  = true;
				$logedInAs = 'ADMiN';
				
				$_SESSION['user'] = $logedInAs;
				$_SESSION['angemeldet'] = true;
				unset($_SESSION['gast']);
				
			} else if (isset($gast_users[$input_username]) && $input_passwort == $gast_users[$input_username]) {
				
				$redirect  = true;
				$logedInAs = 'GUEST';
				
				$_SESSION['user'] = $input_username;
				$_SESSION['gast'] = true;
				unset($_SESSION['angemeldet']);
				
			} else {
				$logedInAs = 'FAiL';
			}
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
	$LOCALHOST   = isset($GLOBALS['LOCALHOST'])   ? $GLOBALS['LOCALHOST']   : false;
	$HOMENETWORK = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;
	$asAdmin     = $GLOBALS['asAdmin'];
	
	if (!($LOCALHOST || $HOMENETWORK)) {
		$logedInAs      = $GLOBALS['logedInAs'];
		$username       = $GLOBALS['input_username'];
		$passwort       = $GLOBALS['input_passwort'];
		$login_passwort = $GLOBALS['login_passwort'];
		$hostname       = $_SERVER['HTTP_HOST'];
		$ip             = $_SERVER['REMOTE_ADDR'];
		$host           = gethostbyaddr($ip);
		
		if (noLog($username, $host) && $logedInAs != 'FAiL') { return; }
		
		$datum = strftime("%d.%m.%Y");
		$time  = strftime("%X");
		$logPass = $passwort;
		if ($asAdmin || $passwort == $login_passwort) {
			$logPass = '****';
		}
		
		$input = $datum."|".$time."|".$ip."|".$host."|".$hostname."|".$logedInAs."|".$username."|".$logPass."\n";
		
		$datei = "./logs/loginLog.php";
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

function noLog($username, $host) {
	$NO_LOG_FROM = isset($GLOBALS['NO_LOG_FROM']) ? $GLOBALS['NO_LOG_FROM'] : array();
	
	if (count($NO_LOG_FROM) > 0) {
		$hosts = isset($NO_LOG_FROM[$username]) ? $NO_LOG_FROM[$username] : null;
		if (!empty($hosts)) {
			for ($i = 0; $i < count($hosts); $i++) {
				if (substr_count($host, $hosts[$i]) >= 1) { return true; }
			}
		}
	}
	return false;
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
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<script type="text/javascript">
		$(document).ready(function() { $('#username').focus(); });
		function hideFailed() { if ($('#failed').length) { $('#failed').hide(); } }
		function animateNav() { $('#navLogin').animate({ "top": "0%" }, 250, function() { return true; }); }
	</script>
</head>
<body style='overflow-x:hidden; overflow-y:auto;'>
<?php if ($logedInAs == 'FAiL') { ?>
	<div id='failed' class='navBarBrand' style='min-width:0%; margin:0 auto; font-size:16pt; width:120px; top:45.5%; left:45%; position:absolute; z-index:1;'>login failed!</div>
<?php } ?>
	<div class='navbar<?php echo ($INVERSE ? ' navbar-inverse' : ''); ?>' id='navLogin' style='margin:0px -15px; position:absolute; top:45%; width:102%;'>
		<div class='navbar-inner' style='height:30px;'>
			<div class='container' style='margin:0px auto; width:auto;'>
				<div class='nav-collapse' style='margin:0px;'><a class='brand navBarBrand' style='font-size:20px; top:4px; position: absolute;' href='#'>xbmcDB</a></div>
				<form action='login.php' method='post' class='navbar-search pull-right' style='height:25px;' onsubmit='hideFailed(); return animateNav();'>
				<ul class='nav' style='color:#FFF;'>
					<li style='margin:0px;'>
						<input class='search-query span1' style='margin:4px 10px; width:75px; height:10px;' type='text' id='username' name='username' placeholder='username' />
						<input class='search-query span1' style='margin:4px 10px; width:75px; height:10px;' type='password' id='passwort' name='passwort' placeholder='password' />
						<input type='submit' value='Ok' class='btn' style='height:20px; padding-top:0px; margin:5px 10px;' onfocus='this.blur();'/>
					</li>
				</ul>
				</form>
			</div>
		</div>
	</div>
</body>
</html>
