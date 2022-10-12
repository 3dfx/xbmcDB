<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";

	if (!isAdmin()) { die; }
	$count = startImport();
	echo '<span style="font:12px Verdana, Arial;">'.$count.' SQL-Statement'.($count != 1 ? 's were' : ' was').' executed!</span>'."\r\n";

function startImport() {
	$count = 0;
	$dbVer = fetchDbVer();
	$dbh   = getPDO();
	try {
		//$actorsSQL = 'SELECT idActor, strActor FROM actors WHERE strActor NOT LIKE "%Gast Star%" AND strActor NOT LIKE "%Guest Star%" AND strActor NOT LIKE "%Autor%";';
		$actorsSQL = "SELECT ".mapDBC('idActor').", ".mapDBC('strActor')." FROM ".mapDBC('actors')." WHERE ".mapDBC('strActor')." NOT LIKE '%Gast Star%' AND ".mapDBC('strActor')." NOT LIKE '%Guest Star%' AND ".mapDBC('strActor')." NOT LIKE '%Autor%';";
		$res = querySQL($actorsSQL, false, $dbh);
		$actors = array();
		foreach($res as $row) {
			$id  = $row[mapDBC('idActor')];
			$str = trim($row[mapDBC('strActor')]);
			$key = strtolower($str);
			$key = str_replace('.', '', $key);
			$key = str_replace(' ', '', $key);

			$actors[$key]['str'] = $str;
			$actors[$key]['id']  = $id;
		}

		$guestSQL = 'SELECT idEpisode,idShow,c04 FROM episode WHERE c04 LIKE "%Gast Star%" OR c04 LIKE "%Guest Star%" ORDER BY idEpisode DESC;';
		$res = querySQL($guestSQL, false, $dbh);
		$linkActs = array();
		foreach($res as $row) {
			$idEp   = $row['idEpisode'];
			$idShow = $row['idShow'];
			$tmp    = $row['c04'];
			$tmp    = str_replace(',',  ' / ', $tmp);
			$tmp    = explode(" / ", $tmp);
			#$gs     = array();
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

		$countBefore = fetchActorlinkCount($dbVer, $dbh);

		if (!empty($dbh) && !$dbh->inTransaction()) { $dbh->beginTransaction(); }
		foreach ($actors as $key => $actor) {
			if (!isset($linkActs[$key])) { continue; }

			$idActor  = $actor['id'];
			$strActor = $actor['str'];
			$actLink  = $linkActs[$key];

			foreach($actLink['idEp'] as $idEp) {
				$SQL = 'INSERT OR IGNORE INTO '.mapDBC('actorlinkepisode').' VALUES('.$idActor.', '.$idEp.', '.($dbVer >= 93 ? '"episode", ' : '').'"Gast Star", GUEST_STAR_ID);';
				execSQL($SQL, false, $dbh);
			}
			foreach($actLink['idShow'] as $idShow) {
				$SQL = 'INSERT OR IGNORE INTO '.mapDBC('actorlinktvshow').' VALUES('.$idActor.', '.$idShow.', '.($dbVer >= 93 ? '"tvshow", ' : '').'"Gast Star", GUEST_STAR_ID);';
				execSQL($SQL, false, $dbh);
			}
		}

		$NOT_LINKED = 'SELECT media_id,actor_id FROM actor_link WHERE media_type="episode" AND cast_order=GUEST_STAR_ID AND actor_id NOT IN(SELECT actor_id FROM actor_link WHERE media_type="tvshow" AND cast_order=GUEST_STAR_ID);';
		$res = querySQL($NOT_LINKED, false, $dbh);
		$idEps = '';
		$idEpActor = array();
		foreach($res as $row) {
			$idEpisode = $row['media_id'];
			$idEpActor[$idEpisode] = $row['actor_id'];
			$idEps = $idEps.$idEpisode.',';
		}
		$idEps = substr($idEps, 0, -1);

		$SHOW_EP_SQL = 'SELECT idShow,idEpisode FROM episode WHERE idEpisode IN('.$idEps.');';
		$res = querySQL($SHOW_EP_SQL, false, $dbh);
		$fixed = 0;
		foreach($res as $row) {
			$fixed++;
			$idShow    = $row['idShow'];
			$idEpisode = $row['idEpisode'];
			$idActor   = $idEpActor[$idEpisode];
			$SQL = 'INSERT OR IGNORE INTO '.mapDBC('actorlinktvshow').' VALUES('.$idActor.', '.$idShow.', '.($dbVer >= 93 ? '"tvshow", ' : '').'"Gast Star", GUEST_STAR_ID);';
			execSQL($SQL, false, $dbh);
		}

		$countAfter = fetchActorlinkCount($dbVer, $dbh);
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

function fetchActorlinkCount($dbVer, $dbh = null) {
	$count  = 0;
	$res    = fetchFromDB("SELECT COUNT(*) AS count FROM ".mapDBC('actorlinkepisode').";", false, $dbh);
	if (!empty($res)) {
		$count += $res['count'];
	}

	if ($dbVer >= 93) {
		//dbVer 93 an above, the tables are the same
		return $count;
	}

	$res    = fetchFromDB("SELECT COUNT(*) AS count FROM ".mapDBC('actorlinktvshow').";", false, $dbh);
	if (!empty($res)) {
		$count += $res['count'];
	}

	return $count;
}
?>
