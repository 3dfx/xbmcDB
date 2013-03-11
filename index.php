<?php
	header('Content-Type: text/html');
	
	include_once "auth.php";
	include_once "template/config.php";
	include_once "template/functions.php";
	startSession();
	
	$start = microtime(true);
	
	if ( isAdmin() && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) ) {
		$what = isset($_POST['aktion']) ? $_POST['aktion'] : null;
		$checkFilme = isset($_POST['checkFilme']) ? $_POST['checkFilme'] : null;

		if (!empty($what) && !empty($checkFilme)) {
			setSeenDelMovie($what, $checkFilme);
			unset($_POST);
		}
	} // post
	
	$show = isset($_SESSION['show']) ? $_SESSION['show'] : null;
	$idShow = isset($_SESSION['idShow']) ? $_SESSION['idShow'] : null;
	$reffer = isset($_SESSION['reffer']) ? $_SESSION['reffer'] : null;
	
	if ( !empty($_GET) || !empty($_POST) ) {
		redirectPage('', true);
		return;
	}
?>	

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>

<?php
	if ($show == null) { $show = 'filme'; }
	if ($show != null && $show == 'logout') { include "./logout.php"; }
	else if ($show != null && $show == 'export') { include "./dbExport.php"; }
	else if ($show != null && $show == 'import') { include "./dbImport.php"; }
	else if ($show != null && $show == 'details' && $idShow != null) { include "./details.php"; }
	else if ($show != null && $show == 'serien') { include "./serien_.php"; }
	else if ($show == 'filme') { include "./filme_.php"; }
	
	adminInfo($start, $show);
?>

</body>
<?php
	logc( 'Page generated in: '.round(microtime(true)-$start, 2).'s', true );
	adminInfoJS();
?>
</html>