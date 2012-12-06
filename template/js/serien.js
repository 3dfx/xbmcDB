var descLoadedOnce     = false;
var epListLoadedOnce   = false;
var epDescLoadedOnce   = false;
var eplistLoading      = false;
var epDescLoading      = false;
var descLoading        = false;
var lastIdShow         = -1;
var lastIdEp           = -1;

$(document).ready(function() {
	$('.dropdown-toggle').dropdown();
});

function openNav(objId) {
	closeNavs();
	$(objId).addClass('open');
}

function closeNavs() {
	$('#dropOptions').removeClass('open');
	$('#dropViewmode').removeClass('open');
	$('#dropLanguage').removeClass('open');
	$('#dropAdmin').removeClass('open');
}

$(window).resize(function() {
	resizeAll();
});

$("#showsTable").ready(function() {
	resizeAll();
});

function resizeAll() {
	moveDivR();
	moveDivL(-351);
	moveDivEpDesc(-351);
}

function toggleEps(id, eps, obj) {
	$('.plmin').removeClass('showlink').addClass('hidelink');

	var trs = document.getElementsByTagName('TR');
	if (trs != null) {
		for (var t = 0; t < trs.length; t++) {
			var tr = trs[t];
			if (tr.className != 'epTR') {
				continue;
			}

			var trId = tr.id;
			if (trId.indexOf(id) > -1) {
				continue;
			}

			if (tr.style.display != 'none') {
				//$( tr ).fadeOut(100, 'linear');
				tr.style.display = 'none';
			}
		}
	}

	var show = false;
	//for (var i = 1; i <= eps; i++) {
	for (var i = 1; i <= (eps+5); i++) {
		var trId = id + '.E' + (i < 10 ? '0'+i : i);
		var tr = document.getElementById(trId);

		if (tr == null) {
			continue;
		}

		if (tr.style.display == 'none') {
			$( tr ).fadeIn(300, 'linear');
			//tr.style.display = 'table-row';
			show = true;
		} else {
			$( tr ).fadeOut(300, 'linear');
			//tr.style.display = 'none';
		}
	}

	if (show) {
		$(obj).removeClass('hidelink');
		$(obj).addClass('showlink');
	} else {
		$(obj).removeClass('showlink');
		$(obj).addClass('hidelink');
	}

	resizeAll();
}

function spoilIt() {
	$( '#epDescr' ).show();
	$( '#spoiler' ).hide();
}

function setFlagsBack(objId) {
	if (objId == '#showInfo') {
		eplistLoading = false;

	} else if (objId == '#showDesc') {
		descLoading = false;

	} else if (objId == '#showEpDesc') {
		epDescLoading = false;
	}
}

function checkShowInfo(objId) {
	var c = 0;
	do {
		if ($(objId).width() > 0) {
			cursorBusy('default');
			setFlagsBack(objId);

			resizeAll();
			return;
		}
		if (c++ > 100) { return; }
	} while ($(objId).width() == 0);

	setFlagsBack(objId);
}

function cursorBusy(state) {
	$('body').css('cursor', state);
	$('#xbmcDB').css('cursor', state);
	$('#showInfo').css('cursor', state);
	$('td').css('cursor', state);
	$('a').css('cursor', state);
}

function loadShowInfo(obj, id) {
	if (lastIdShow == id) {
		return;
	}

	cursorBusy('progress');

	$('#showEpDesc').text('');
	moveDivEpDesc(0);

	loadEplist(obj);
	loadDesc(obj);

	lastIdShow = id;
	lastIdEp = -1;

	//$('#showsTable').forceRedraw(true);
	return false;
}

function loadEpDetails(obj, epId) {
	if (epDescLoading || lastIdEp == epId) {
		return;
	}

	if (epDescLoadedOnce) {
		$('#showEpDesc').fadeOut(100, 'linear', function() {
			loadEpDetails_(obj);
		});

	} else {
		loadEpDetails_(obj);
	}

	epDescLoadedOnce = true;
}

function loadEpDetails_(obj, epId) {
	epDescLoading = true;
	cursorBusy('progress');

	$('#showEpDesc').text('');
	$('#showEpDesc').show();
	moveDivEpDesc(0);

	$('#showEpDesc').load(
		$(obj).attr('href'),
		function() {
			$('#showEpDesc').animate(
				{ "width": "+=351px", "left": "-=352px" },
				"slow",
				function() {
					checkShowInfo('#showEpDesc');
					if ($.browser.mozilla) { $("#showEpDesc").css({ "width" : "352px" }); }
					if ($.browser.webkit) { $("#showEpDesc").css({ "width" : "351px" }); }
				}
			);
		}
	);

	lastIdEp = epId;
	return false;
}

function moveDivEpDesc(resizeOffset) {
	var yOffset = 1;
	var xOffset = 1;

	var position = $("tbody:first").position(); //$("#showDesc").position();
	var left = $("tbody:first").position().left;
	var top = $("#showDesc").position().top + $(".showDesc").height() + yOffset;

	var width = $("#showDesc").width();
	var height = $("table:first").height() - top;

	var divTop = top + "px";

	var divLeft = Math.floor(left + resizeOffset + xOffset);
	var divWidth = '351px';
	if ($.browser.mozilla) { divWidth = '352px'; }
	if ($.browser.mozilla) { divLeft = divLeft-1; }
	if ($.browser.webkit) { divLeft = divLeft+1; }
	divLeft = divLeft + "px";

	$("#showEpDesc").css({
		"position"	: "absolute",
		"top"		: divTop,
		"left"		: divLeft,
		"width"		: "0px",
		"height"	: height,
		"z-index"	: "2"
	});

	if (!epDescLoading) {
		$("#showEpDesc").css({ "width" : divWidth });
		if ($.browser.mozilla) { $("#epDescription").css({ "width" : divWidth }); }
	}
}

function loadEplist(obj) {
	if (eplistLoading) {
		return false;
	}

	if (epListLoadedOnce) {
		$('#showInfo').fadeOut(200, 'linear', function() {
			loadEplist_(obj);
		});

	} else {
		loadEplist_(obj);
	}

	epListLoadedOnce = true;
}

function loadEplist_(obj) {
	eplistLoading = true;

	$('#showInfo').text('');
	$('#showInfo').show();
	moveDivR();

	$('#showInfo').load(
		$(obj).attr('eplist'),
		function() {
			$('#showInfo').animate(
				{ "width": "+=351px" },
				"slow",
				function() {
					checkShowInfo('#showInfo');
					$("#showInfo").css({ "width" : "351px" });
					if ($.browser.mozilla) { $("#showInfo").css({ "left" : "+=1px" }); }
				}
			);
		}
	);
}

function loadDesc(obj) {
	if (descLoading) {
		return false;
	}

	if (descLoadedOnce) {
		$('#showDesc').fadeOut(200, 'linear', function() {
			loadDesc_(obj);
		});

	} else {
		loadDesc_(obj);
	}

	descLoadedOnce = true;
}

function loadDesc_(obj) {
	var xOffset = 1;
	var resizeOffset = 0;
	descLoading = true;

	$('#showDesc').text('');
	$('#showDesc').show();
	moveDivL(resizeOffset);

	var left = $("tbody:first").position().left;
	var divLeft = Math.floor(left + resizeOffset + xOffset - 351); // + "px";
	if ($.browser.mozilla) { divLeft = divLeft-1; }
	if ($.browser.webkit) { divLeft = divLeft+1; }
	divLeft = divLeft + "px";

	$('#showDesc').load(
		$(obj).attr('desc'),
		function() {
			$('#showDesc').animate(
				{ "width": "+=351px", "left": divLeft },
				/*{ "width": "+=351px" },*/
				"slow",
				function() {
					checkShowInfo('#showDesc');
					$("#showDesc").css({ "width" : "351px", "left" : divLeft });
				}
			);
		}
	);
}

function moveDivL(resizeOffset) {
	var yOffset = 0;
	var xOffset = $.browser.webkit ? 1 : -1;

	var position = $("tbody:first").position();
	var left = $("tbody:first").position().left;
	var top = $("#showsTable").position().top; // + $("#showsTable").height() + yOffset;
	//var top = $("#emptyTR").position().top + $("#emptyTR").height() + yOffset;

	var width = $("tbody:first").width();
	var height = $("table:first").height() - top;

	var divTop = top + "px";

	var divLeft = Math.floor(left + resizeOffset + xOffset); // + "px";
	if ($.browser.mozilla) { divLeft = divLeft-1; }
	if ($.browser.webkit) { divLeft = divLeft+1; }
	divLeft = divLeft + "px";

	$("#showDesc").css({
		"position"	: "absolute",
		"top"		: divTop,
		"left"		: divLeft,
		"width"		: "0px",
		"height"	: height,
		"z-index"	: "2"
	});

	if (!descLoading) {
		$("#showDesc").css({ "width" : "351px" });
	}
}

function moveDivR() {
	var yOffset = $.browser.webkit ? 2 : -1;
	var xOffset = $.browser.webkit ? 1 : 0;

	var position = $("tbody:first").position();
	var left = position.left;
	var top = $("#emptyTR").position().top + $("#emptyTR").height() + yOffset;
	//var top = $("#showsTable").position().top; // + $("#showsTable").height() + yOffset;

	var width = $("tbody:first").width();
	var height = $("table:first").height() - top;

	var divTop = top + "px";

	var divLeft = Math.floor(left + width + xOffset);
	if ($.browser.mozilla) { divLeft = divLeft-2; }
	if ($.browser.webkit) { divLeft = divLeft-1; }
	divLeft = divLeft + "px";

	$("#showInfo").css({
		"position"	: "absolute",
		"top"		: divTop,
		"left"		: divLeft,
		"width"		: "0px",
		"height"	: height,
		"z-index"	: "2"
	});

	if (!eplistLoading) {
		$("#showInfo").css({ "width" : "351px" });
		//$("#showInfo").css({ "width" : "0px" });
	}
}