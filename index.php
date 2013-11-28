<?php
include_once "auth.php";
include_once "./template/config.php";
include_once "./template/functions.php";

	startSession();
	if (!empty($_GET['img'])) { include_once "img.php"; exit; }
	
	header("Content-Type: text/html; charset=UTF-8");

	$start  = microtime(true);
	$show   = !empty($_GET['show'])   ? $_GET['show']   : null;
	$idShow = !empty($_GET['idShow']) ? $_GET['idShow'] : null;

	if ( isAdmin() && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) ) {
		$what       = isset($_POST['aktion'])     ? $_POST['aktion']     : null;
		$checkFilme = isset($_POST['checkFilme']) ? $_POST['checkFilme'] : null;
		
		if (!empty($what) && !empty($checkFilme)) {
			setSeenDelMovie($what, $checkFilme);
			unset($_POST);
		}
	} // post
	
	$show   = isset($_SESSION['show'])   && empty($show)   ? $_SESSION['show']   : $show;
	$idShow = isset($_SESSION['idShow']) && empty($idShow) ? $_SESSION['idShow'] : null;
	
	if ( !empty($_GET) || !empty($_POST) ) { redirectPage('', true); } //breaks the browsers back button, who gives a fuck??
?>	
<html><?php
	if (empty($show)) { $show = 'filme'; }
	
	     if ($show == 'logout')                     { include "./logout.php";   }
	else if ($show == 'export')                     { include "./dbExport.php"; }
	else if ($show == 'import')                     { include "./dbImport.php"; }
	else if ($show == 'details' && !empty($idShow)) { include "./details.php";  }
	else if ($show == 'serien')                     { include "./serien_.php";  }
	else if ($show == 'mvids')                     { include "./mvids_.php";  }
	else if ($show == 'filme')                      { include "./filme_.php";   }
	
	adminInfo($start, $show);
?>
<?php
	if (!isDemo()) {
		logc( 'Page generated in: '.round(microtime(true)-$start, 2).'s', true );
		adminInfoJS();
	}
?>
</html>