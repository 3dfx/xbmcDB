<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";

	if (!isAdmin()) { die; }
	$count = getCount();
	echo '<span style="font:12px Verdana, Arial;">'.$count.' SQL-Statement'.($count != 1 ? 's were' : ' was').' executed!</span>'."\r\n";

function getCount() {
	$count = 0;
	$dbh   = getPDO();
	try {
		$actorsSQL = 'SELECT idActor,strActor FROM actors WHERE strActor NOT LIKE "%Gast Star%" AND strActor NOT LIKE "%Guest Star%" AND strActor NOT LIKE "%Autor%";';
		$res = querySQL_($dbh, $actorsSQL);
		$actors = array();
		foreach($res as $row) {
			$id  = $row['idActor'];
			$str = trim($row['strActor']);
			$key = strtolower($str);
			$key = str_replace('.', '', $key);
			$key = str_replace(' ', '', $key);
			$actors[$key]['str'] = $str;
			$actors[$key]['id']  = $id;
		}
		
		$guestSQL = 'SELECT idEpisode,idShow,c04 FROM episode WHERE c04 LIKE "%Gast Star%" OR c04 LIKE "%Guest Star%" ORDER BY idEpisode DESC;';
		$res = querySQL_($dbh, $guestSQL);
		$linkActs = array();
		foreach($res as $row) {
			$idEp   = $row['idEpisode'];
			$idShow = $row['idShow'];
			$tmp    = $row['c04'];
			$tmp    = str_replace(',',  ' / ', $tmp);
			$tmp    = explode(" / ", $tmp);
			$gs     = array();
			foreach($tmp as $g) {
				if (substr_count($g, 'Autor') > 0) { continue; }
				if (substr_count($g, 'Gast') == 0 && substr_count($g, 'Guest') == 0) { continue; }
				
				$g = str_replace('(Gast Star)',  '', $g);
				$g = str_replace('(Guest Star)', '', $g);
				$g = str_replace('(Gast)',  '', $g);
				$g = str_replace('(Guest)', '', $g);
				$g = trim($g);
				$key = strtolower($g);
				$key = str_replace('.', '', $key);
				$key = str_replace(' ', '', $key);
				
				$linkActs[$key]['str'] = $g;
				
				if (empty($linkActs[$key]['idEp'])) {
					$linkActs[$key]['idEp'] = array();
				}
				if (!in_array($idEp, $linkActs[$key]['idEp'])) {
					$linkActs[$key]['idEp'][] = $idEp;
				}
				
				if (empty($linkActs[$key]['idShow'])) {
					$linkActs[$key]['idShow'] = array();
				}
				if (!in_array($idShow, $linkActs[$key]['idShow'])) {
					$linkActs[$key]['idShow'][] = $idShow;
				}
			}
		}
		
		$countBefore = fetchActorlinkCount($dbh);
		
		if (!empty($dbh) && !$dbh->inTransaction()) { $dbh->beginTransaction(); }
		foreach ($actors as $key => $actor) {
			if (!isset($linkActs[$key])) { continue; }
			
			$idActor  = $actor['id'];
			$strActor = $actor['str'];
			$actLink  = $linkActs[$key];
			
			foreach($actLink['idEp'] as $idEp) {
				$SQL = 'INSERT OR IGNORE INTO actorlinkepisode VALUES('.$idActor.', '.$idEp.', "Gast Star", 1337);';
				execSQL_($dbh, $SQL);
			}
			foreach($actLink['idShow'] as $idShow) {
				$SQL = 'INSERT OR IGNORE INTO actorlinktvshow VALUES('.$idActor.', '.$idShow.', "Gast Star", 1337);';
				execSQL_($dbh, $SQL);
			}
		}
		
		$countAfter = fetchActorlinkCount($dbh);
		$count = $countAfter - $countBefore;
		
		if (!empty($dbh) && $dbh->inTransaction()) {
			if ($count > 0) { $dbh->commit(); }
			else          { $dbh->rollBack(); }
		}
		
	} catch(PDOException $e) {
		echo $e;
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
	}
	
	return $count;
}

function fetchActorlinkCount($dbh) {
	$count  = 0;
	$res    = fetchFromDB_($dbh, "SELECT COUNT(*) AS count FROM actorlinkepisode;");
	$count += $res['count'];
	
	$res    = fetchFromDB_($dbh, "SELECT COUNT(*) AS count FROM actorlinktvshow;");
	$count += $res['count'];
	return $count;
}
?>