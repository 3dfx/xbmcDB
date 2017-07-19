var actualFile = '';
function nowPlaying()   { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=nowPlaying",      success: function(data) { if (actualFile != data && actualFile != null) { $('#xbmcPlayerFile').text(data); if(data != 'null' && data != '') { $('#xbmControl').show(); } else { $('#xbmControl').hide(); } wrapItUp(); actualFile = data; } } }); }
function playerState()  { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=state",           success: function(data) { var res = (data == 'null' ? '' : (data == 0 ? 'paused' : 'playing')); $('#xbmcPlayerState').text(res); if (res != '') { $('#xbmControl').show(); nowPlaying(); } else { $('#xbmControl').hide(); } } }); }

$(document).on({ click: function(){ if ($('#plaYoutube').is(":visible")) { $('#xbmControlWrap,#plaYTdivide').show(5); $('#plaYoutube').hide(25); } else { $('#xbmControlWrap,#plaYTdivide').hide(5); $('#plaYoutube').show(100); $('#plaYoutube').val(''); $('#plaYoutube').focus(); } } }, '#ytIcon');
function playItemPrompt(obj, event)  { if (obj == null) { return false; } var search = $.trim(obj.value); var kC = getKeyCode(event); /* if (kC != 13) { return false; } */ if (search != null && search != '') { playItemExt(search); playerState(); $('#plaYoutube').hide("fast"); } }

function playItemExt(file) { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=playExt&file="+file, success: function(data) { playerState();           } }); }
function playItem(file)    { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=play&file="+file,    success: function(data) { playerState();           } }); }
function playPause()       { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=pausePlay",          success: function(data) { playerState();           } }); }
function playNext()        { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=next",               success: function(data) { playerState();           } }); }
function playPrev()        { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=prev",               success: function(data) { playerState();           } }); }
function stopPlaying()     { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=stop",               success: function(data) { $('#xbmControl').hide(); } }); }

function clearCache()      { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=clearCache" }); }
function scanLib()         { $.ajax({type:"POST", url:"./xbmcJson.php", data:"method=scanLib"    }); }

$(document).ready(function() { wrapItUp(); });
function wrapItUp() {
	if (xbmcRunning != null && !xbmcRunning) { return; }
	actualFile = $('#xbmcPlayerFile').text();
	setInterval("playerState()", 2500);
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
