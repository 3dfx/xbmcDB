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

	$id = $_GET['id'];
	if (empty($id) || $id < 0) { die('No id given!'); }

	$serien = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie = $serien->getSerie($id);
	$idTvdb = $serie->getIdTvdb();
	$desc = encodeString($serie->getDesc());
	$banner = null;
	$imgURL = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
	$tvdbURL = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;
	
	echo '<table class="film" style="width:350px; padding:0px; margin:0px; z-index:1;">';
	echo '<tr class="showDesc">';
	echo '<td colspan="3" style="padding:25px 25px; text-align:justify; white-space:pre-line;">';
	if (!empty($idTvdb) && $idTvdb != -1) {
		if ($USECACHE) {
			$imgFile = './img/banners/'.$idTvdb.'.jpg';
			if (loadImage($imgURL, $imgFile) == -1) { $imgFile = null; }
		}
		
		$banner = !empty($imgFile) ? $imgFile : $imgURL;
		#echo '<a class="openImdb" style="width:300px !important; height:55px !important;" href="'.$tvdbURL.'">';
		echo '<img id="tvBanner" class="innerCoverImg" style="width:300px;" src="'.$banner.'" href="'.$tvdbURL.'" />';
		#echo '<div style="background:url('.$imgFile.') transparent no-repeat; width:300px; height:55px;"></div>';
		#echo '</a>';
	}

	echo '<div style="margin-top:15px;">'.$desc.'</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
?>