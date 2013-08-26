<script type="text/javascript">
	jQuery(document).ready(function() {
		$("#tvBanner").fancybox({
			'width'				: '63%',
			'height'			: '94%',
			'autoScale'			: true,
			'centerOnScroll'		: true,
			'enableEscapeButton'		: true,
			'padding'			: 5,
			'margin'			: 10,
			'transitionIn'			: 'elastic',
			'transitionOut'			: 'none',
			'type'				: 'iframe'
		});
	});
</script>
<?php
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "_SERIEN.php";

	$id = isset($_GET['id']) ? $_GET['id'] : null;
	if (empty($id) || $id < 0) { return; }

	$serien = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie = $serien->getSerie($id);
	$idTvdb = $serie->getIdTvdb();
	$desc = $serie->getDesc();
	$banner = null;
	$imgURL = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
	$tvdbURL = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;
	
	echo '<table class="film tableDesc">';
	echo '<tr class="showDesc">';
	echo '<td class="showDescTD">';
	if (!empty($idTvdb) && $idTvdb != -1) {
		$imgFile = './img/banners/'.$idTvdb.'.jpg';
		if (loadImage($imgURL, $imgFile) == -1) { $imgFile = null; }
		wrapItUp('banner', $idTvdb, $imgFile);
		$banner = empty($imgFile) ? $imgURL : getImageWrap($imgFile, $idTvdb, 'banner', 0);
		echo '<img id="tvBanner" class="innerCoverImg" src="'.$banner.'" href="'.$tvdbURL.'" />';
	}

	echo '<div class="descDiv">'.$desc.'</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
?>