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
	
	include_once "./template/functions.php";
	include_once "./template/config.php";
	include_once "./template/_SERIEN.php";
	
	$id = isset($_GET['id']) ? $_GET['id'] : null;
	if (empty($id) || $id < 0) { return; }
	
	$serien  = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie   = $serien->getSerie($id);
	$idTvdb  = $serie->getIdTvdb();
	$desc    = $serie->getDesc();
	$running = $serie->isRunning();
	$banner  = null;
	$imgURL  = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
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
	
	echo '<div class="descDiv">';
	$checkAirDate = isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
	if ($checkAirDate && $running) {
		$airDate     = null;
		$nextEpisode = null;
		$nextAirDate = $serie->getNextAirDateStr();
		
		if (empty($nextAirDate) || dateMissed($nextAirDate)) {
			//tvdb
			$nextEpisode = getNextEpisode($serie);
			$airDate = getNextAirDate($nextEpisode);
		} else {
			$airDate = $nextAirDate;
		}
		
		if (!empty($airDate)) {
			$season  = -1;
			$episode = -1;
			
			if (!empty($nextEpisode)) {
				//tvdb
				$season  = $nextEpisode['SeasonNumber'];
				$episode = $nextEpisode['EpisodeNumber'];
			} else {
				$st      = $serie->getLastStaffel();
				$ep      = $st->getLastEpisode();
				$season  = $st->getStaffelNum();
				$episode = $ep->getEpNum();
			}
			
			$dbDate    = $airDate;
			$airDate   = addRlsDiffToDate($airDate);
			$dayOfWeek = dayOfWeek($airDate);
			$daysLeft  = daysLeft($airDate);
			$missed    = dateMissed($airDate);
			$info1     = $daysLeft > 0 ?
					'In '.$daysLeft.' day'.($daysLeft > 1 ? 's' : '').' on '.$dayOfWeek :
					(isAdmin() ? ($missed ? 'Missed episode' : 'Today') : '');
			
			$info2     = ' [<b style="color:'.($missed && isAdmin() ? 'red' : 'silver').';">'.toEuropeanDateFormat($airDate).'</b>]';
			
			echo 'Next airdate:<br />'.$info1.$info2.'<br /><br />';
			
			if (!empty($nextEpisode) && compareDates($nextAirDate, $dbDate)) {
				updateAirdateInDb($id, $season, $episode, $dbDate);
				clearMediaCache();
			}
		}
	}
	
	echo $desc;
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
?>