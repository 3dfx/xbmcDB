var actualFile = '';
function nowPlaying()   { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=nowPlaying",      success: function(data) { if (actualFile != data && actualFile != null) { $('#xbmcPlayerFile').text(data); if(data != 'null' && data != '') { $('#xbmControl').show(); } else { $('#xbmControl').hide(); } wrapItUp(); actualFile = data; } } }); }
function playerState()  { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=state",           success: function(data) { var res = (data == 'null' ? '' : (data == 0 ? 'paused' : 'playing')); $('#xbmcPlayerState').text(res); if (res != '') { $('#xbmControl').show(); nowPlaying(); } else { $('#xbmControl').hide(); } } }); }

function playItemPrompt() { var item = prompt("",""); playItem(item); }
function playItem(file) { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=play&file="+file, success: function(data) { playerState();           } }); }
function playPause()    { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=pausePlay",       success: function(data) { playerState();           } }); }
function playNext()     { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=next",            success: function(data) { playerState();           } }); }
function playPrev()     { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=prev",            success: function(data) { playerState();           } }); }
function stopPlaying()  { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=stop",            success: function(data) { $('#xbmControl').hide(); } }); }

function clearCache()   { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=clearCache" }); }
function scanLib()      { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=scanLib"    }); }

$(document).ready(function() { wrapItUp(); });
function wrapItUp() {
	if (xbmcRunning != null && !xbmcRunning) { return; }
	actualFile = $('#xbmcPlayerFile').text();
	setInterval("nowPlaying()", 2500);
	if ($('#xbmcPlayerFile').text().length <= 30) { return; }
	if ( $('#xbmControl').is(":visible") ) { $('#xbmcPlayerFile').marquee({
		speed: 50000,
		gap: 1,
		delayBeforeStart: 50,
		direction: 'left',
		duplicated: true,
		pauseOnHover: true
	}); }
}