<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	startSession();
	if (!isAdmin()) { return; }
?>
<html>
	<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	$closeFrame = getEscGPost('closeFrame', 0);
$idFile     = getEscGPost('idFile', -1);

	$row    = fetchFromDB('SELECT src as source FROM fileinfo WHERE idFile = '.$idFile.';');
	$source  = $row['source'];

/*	FUNCTIONS	*/
function postSources($actual) {
	foreach(SOURCE as $value => $name) {
        if ($value == null) { continue; }
		postSource($actual, $value < 0 ? '-1' : $value, $name);
	}
}

function postSource($actual, $value, $name) {
	echo "\t\t\t".'<option value="'.$value.'"'.($actual == $value ? ' SELECTED' : '').'>'.$name.'</option>';
	echo "\r\n";
}
?>
    <script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript">
		$(document).ready(function(){
			$('.styled-select').customStyle();
		});

		function cursorBusy() {
			$('body').css('cursor', 'wait');
			$('div').css('cursor', 'wait');
			$('select').css('cursor', 'wait');
			$('button').css('cursor', 'wait');
		}

		function setFileSource() {
			cursorBusy();
			<?php echo 'var idFile = '.(isset($GLOBALS['idFile']) ? $GLOBALS['idFile'] : '-1').';'."\r\n"; ?>
			var sel = document.getElementById('setSource');

			if (idFile === null || idFile === -1 || sel === null) {
				return;
			}

            var value = 'NULL';
			if (sel.value !== -1) {
				value = sel.value;
			}

			window.location.href='./dbEdit.php?act=setMovieSource' + '&idFile=' + idFile + '&source=' + value;
		}
	</script>
	</head>
    <body style="width:350px !important; max-width:unset !important; height:50px; margin:0; padding:15px 10px;">
        <div style="float:left; width:200px; padding:2px 0 0 0;">
            <select id="setSource" class="styled-select" style="position:absolute; font-size:10px !important; width:195px !important; height:18px !important;" size="1">
                <option value="-1">unknown</option>
<?php postSources($source); ?>
            </select>
        </div>
        <div style="float:right; padding:0 5px; position: relative; right: 50px;">
            <input type="button" value="Ok" class="okButton" onclick="setFileSource(); return false;">
        </div>
    </body>
</html>
