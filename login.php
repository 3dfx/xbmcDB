<?php
	include_once "./template/config.php";
	include_once "./template/functions.php";
	include_once "globals.php";
	
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	
	$blacklisted = restoreBlacklist();
	$hostname    = $_SERVER['HTTP_HOST'];
	$path        = dirname($_SERVER['PHP_SELF']);
	
	startSession();
	if (isLoggedIn()) { loggedInSoRedirect(true); }
	
	logRefferer();
	
	$loggedInAs  = '';
	$failedText  = 'login failed!';
	$FAIL_       = 'FAiL';
	$redirect    = false;
	$asAdmin     = false;
	$noMoreLogin = false;
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$username_ = getEscPost('username');
		$passwort_ = getEscPost('passwort');
		$input_username = urldecode($username_);
		$input_passwort = urldecode($passwort_);
		
		if (!(empty($input_username) || empty($input_passwort))) {
			$login_username = isset($GLOBALS['LOGIN_USERNAME']) ? $GLOBALS['LOGIN_USERNAME'] : null;
			$login_passwort = isset($GLOBALS['LOGIN_PASSWORT']) ? $GLOBALS['LOGIN_PASSWORT'] : null;
			$gast_users     = isset($GLOBALS['GAST_USERS'])     ? $GLOBALS['GAST_USERS']     : array();
			$demo_users     = isset($GLOBALS['DEMO_USERS'])     ? $GLOBALS['DEMO_USERS']     : array();
			$demo_enabled   = isset($GLOBALS['DEMO_ENABLED'])   ? $GLOBALS['DEMO_ENABLED']   : false;
			$hashed         = isset($GLOBALS['PASSES_HASHED'])  ? $GLOBALS['PASSES_HASHED']  : false;
			
			$input_passwort = $hashed ? sha1($input_passwort) : $input_passwort;
			
			// check username und password
			if (!empty($login_username) && !empty($login_passwort) && $input_username == $login_username && $input_passwort == $login_passwort) {
				
				$asAdmin    = true;
				$redirect   = true;
				$loggedInAs = 'ADMiN';
				
				$_SESSION['user']       = $loggedInAs;
				$_SESSION['angemeldet'] = true;
				unset( $_SESSION['gast'] );
				
			} else if (isset($gast_users[$input_username]) && $input_passwort == $gast_users[$input_username]) {
				
				$redirect   = true;
				$loggedInAs = 'GUEST';
				
				$_SESSION['user'] = $input_username;
				$_SESSION['gast'] = true;
				unset( $_SESSION['angemeldet'] );
				
			} else if (
				  $demo_enabled && 
				  (isset($demo_users[$input_username]) && $input_passwort == $demo_users[$input_username])
				  ) {
				
				$redirect   = true;
				$loggedInAs = 'DEMO';
				
				$_SESSION['user'] = $input_username;
				$_SESSION['demo'] = true;
				unset( $_SESSION['angemeldet'] );
				
			} else {
				$loggedInAs = $FAIL_;
			}
		} else {
			$loggedInAs = $FAIL_;
		}
		
		logLogin();
		if ($redirect) { loggedInSoRedirect(); }
	}
	
	if (isBlacklisted()) { $noMoreLogin = true; $failedText = 'too many failed logins!'; }

function loggedInSoRedirect($loggedInAlready = false) {
	if (isset($_SESSION['show']) && $_SESSION['show'] == 'details') {
		unset( $_SESSION['show'], $_SESSION['idShow'] );
	}

	if (!$loggedInAlready) { restoreSession(); }
	redirectPage('', true);
}

function logLogin() {
	$LOCALHOST    = isset($GLOBALS['LOCALHOST'])   ? $GLOBALS['LOCALHOST']   : false;
	$HOMENETWORK  = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;
	$asAdmin      = $GLOBALS['asAdmin'];
	$loggedInAs   = $GLOBALS['loggedInAs'];
	$FAIL_        = 'FAiL';
	if ($loggedInAs == $FAIL_) { addBlacklist(); }
	else { removeBlacklist(); }
	
	if (!($LOCALHOST || $HOMENETWORK)) {
		$username       = $GLOBALS['input_username'];
		$passwort       = $GLOBALS['input_passwort'];
		$login_username = $GLOBALS['LOGIN_USERNAME'];
		$login_passwort = $GLOBALS['LOGIN_PASSWORT'];
		$blacklisted    = $GLOBALS['blacklisted'];
		$hostname       = $_SERVER['HTTP_HOST'];
		$ip             = $_SERVER['REMOTE_ADDR'];
		$host           = gethostbyaddr($ip);
		
		if (noLog($username, $host, $ip) && $loggedInAs != $FAIL_) { return; }
		
		$datum = strftime("%d.%m.%Y");
		$time  = strftime("%X");
		$logPass = $passwort;
		if ($asAdmin || $passwort == $login_passwort || $username == $login_username) {
			$logPass = '****';
		}
		
		$input = $datum."|".$time."|".$ip."|".$host."|".$hostname."|".$loggedInAs."|".$username."|".$logPass."\n";
		
		$datei = "./logs/loginLog.php";
		if (file_exists($datei)) {
			$fp = fopen($datei, "r");
			while(!feof($fp)) {
				$eintraege = fgets($fp, 1000);
				$input .= $eintraege;
			}
			fclose($fp);
		}
		
		$input = str_replace('<? /*', '', $input);
		$input = str_replace('*/ ?>', '', $input);
		$input = str_replace("\n\n", "\n", $input);
		$input = '<? /*'."\n".$input.'*/ ?>';
		
		$fp = fopen($datei, "w+");
		fputs($fp, $input);
		fclose($fp);
	}
}

function noLog($username, $host, $ip) {
	$NO_LOG_FROM = isset($GLOBALS['NO_LOG_FROM']) ? $GLOBALS['NO_LOG_FROM'] : array();
	if (count($NO_LOG_FROM) > 0) {
		$hosts = isset($NO_LOG_FROM[$username]) ? $NO_LOG_FROM[$username] : null;
		if (!empty($hosts)) {
			for ($i = 0; $i < count($hosts); $i++) {
				if ($ip == $hosts[$i] || substr_count($host, $hosts[$i]) >= 1) { return true; }
			}
		}
	}
	return false;
}
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>XBMC Database</title>
	<link rel="icon" type="image/png" href="img/favicon15.png">
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
<?php if (!$noMoreLogin) { ?>
	<script type="text/javascript">
		$(document).ready(function() { $('#username').focus(); });
		function hideFailed() { if ($('#failed').length) { $('#failed').hide(); } }
		function animateNav() { $('#navLogin').animate({ "top": "0%" }, 250, function() { return true; }); }
	</script>
<?php } ?>
</head>
<body style='overflow-x:hidden; overflow-y:auto;'>
<?php if ($loggedInAs == $FAIL_ || $noMoreLogin) { ?>
	<div id='failed' class='navBarBrand' style='min-width:0%; margin:0 auto; font-size:16pt; <?php echo $noMoreLogin ? 'width:250px; left:43%;' : 'width:120px; left:45%;'; ?> top:45.5%; position:absolute; z-index:1;'><?php echo $failedText; ?></div>
<?php } ?>
	<div class='navbar<?php echo ($INVERSE ? ' navbar-inverse' : ''); ?>' id='navLogin' style='margin:0px -15px; position:absolute; top:45%; width:102%;'>
		<div class='navbar-inner' style='height:30px;'>
<?php if (!$noMoreLogin) { ?>
			<div class='container' style='margin:0px auto; width:auto;'>
				<div class='nav-collapse' style='margin:0px;'><a class='brand navBarBrand' style='font-size:20px; top:4px; position: absolute;' href='#'>xbmcDB</a></div>
				<form action='login.php' method='post' class='navbar-search pull-right' style='height:25px;' onsubmit='hideFailed(); return animateNav();'>
				<ul class='nav' style='color:#FFF;'>
					<li style='margin:0px;'>
						<input class='search-query span1' style='margin:4px 10px; width:75px; height:25px;' type='text' id='username' name='username' placeholder='username' />
						<input class='search-query span1' style='margin:4px 10px; width:75px; height:25px;' type='password' id='passwort' name='passwort' placeholder='password' />
						<input type='submit' value='Ok' class='btn' style='height:20px; padding-top:0px; margin:5px 10px;' onfocus='this.blur();'/>
					</li>
				</ul>
				</form>
			</div>
<?php } ?>
		</div>
	</div>
</body>
</html>
