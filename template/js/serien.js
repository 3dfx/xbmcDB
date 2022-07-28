var airdateVisible   = false;
var epListLoadedOnce = false;
var epDescLoadedOnce = true;
var descLoadedOnce   = true;
var eplistLoading    = false;
var epDescLoading    = false;
var descLoading      = false;
var epsPositioned    = false;
var fromNavbar       = false;
var lastIdShow       = -1;
var lastIdEp         = -1;
var ids              = '';
var unsaved          = false;

$(document).ready(function() {
	$('.dropdown-toggle').dropdown();

	$(document).keydown(function(event) {
		if (bindF) {
			if (event.ctrlKey && event.keyCode == '70') {
				event.preventDefault();
				openNav('#dropSearch');
				$('#searchDBfor').focus();
			}
		}
		if (!isAdmin) {
			if (event.ctrlKey && event.keyCode == '83') {
				event.preventDefault();
				saveSelection();
			}
		}
	});
});

function closeShow() {
	$('#showDesc').hide();
	$('#showEpDesc').hide();
	$('#showInfo').hide();

	$('.selectedShow').addClass('unselectedShow')
	lastIdShow = 0;
}

function selSpanText(obj) {
	var range, selection;

	if (window.getSelection && document.createRange) {
		selection = window.getSelection();
		range = document.createRange();
		range.selectNodeContents($(obj)[0]);
		selection.removeAllRanges();
		selection.addRange(range);
	} else if (document.selection && document.body.createTextRange) {
		range = document.body.createTextRange();
		range.moveToElementText($(obj)[0]);
		range.select();
	}
}

function openNav(objId) { openNav_(objId, true); }
function openNav_(objId, all) {
	closeNavs_(all);
	$(objId).addClass('open');
}

function closeNavs() { closeNavs_(true); }
function closeNavs_(all) {
	$('.dropdown-submenu').removeClass('open');

	$('#dropAdmin').removeClass('open');
	$('#dropSearch').removeClass('open');
	if (isAdmin) {
		$('#dropViewmode').removeClass('open');
	}
	if (all) {
		$('#dropLatestEps').removeClass('open');
	}
}

function toggleActive(obj) {
	$(obj).addClass('active');
}

function toggleDActive(obj) {
	$(obj).removeClass('active');
}

function searchDbForString(obj, event) {
	if (obj == null) { return false; }
	var search = $.trim(obj.value).toLowerCase();

	var kC = getKeyCode(event);
	if (kC !== 13) { return false; }

	var href = './?show=serien&dbSearch=';
	if (search != null && search !== '') { href = href + search; }

	window.location.href=href;
}

function resetDbSearch() {
	if ($('#searchDBfor').val() === '') { return false; }
	window.location.href='./?show=serien&dbSearch=';
}

function getKeyCode(event) {
	event = event || window.event;
	return event.keyCode;
}

$(window).resize(function() {
	resizeAll(0);
});

$("#showsTable").ready(function() {
	resizeAll(0);
});

function resizeAll(xOffest1) {
	epsPositioned = false;
	moveDivR(xOffest1);
	moveDivL(-351);
	moveDivEpDesc(-351);
}

function toggleEps(id, eps, objId) {
	epsPositioned = false;
	toggleEps_(id, eps, objId);
}

function toggleEps_(id, eps, objId) {
	var obj = null;
	if (objId == null && id != null) {
		objId = id.replace('iD', 'tgl');
	}
	if (objId != null) {
		obj = document.getElementById(objId);
	}
	if (obj == null) { return; }
	$('.plmin').removeClass('showlink').addClass('hidelink');

	var show = false;
	var trs  = document.getElementsByTagName('TR');
	if (trs != null) {
		for (var t = 0; t < trs.length; t++) {
			var tr = trs[t];
			if (tr == null || tr.className.indexOf('epTR') === -1) { continue; }

			var selectedSeason = (tr.className.indexOf(id) > -1);
			if (!selectedSeason) {
				if (tr.style.display !== 'none') { $( tr ).hide(); }
				continue;
			}

			if (tr.style.display === 'none') {
				$( tr ).fadeIn(300, 'linear');
				show = true;
			} else {
				$( tr ).fadeOut(300, 'linear');
			}
		}
	}

	if (show) {
		$(obj).removeClass('hidelink');
		$(obj).addClass('showlink');
	} else {
		$(obj).removeClass('showlink');
		$(obj).addClass('hidelink');
	}

	//moveDivR(show ? 1 : 0);
	moveDivR(0);

	moveDivL(-351);
	moveDivEpDesc(-351);
}

function spoilIt() {
	$( '#epSpoiler' ).hide();
	$( '#epDescr' ).show();
}

function showGuests() {
	$( '#epGuest' ).hide();
	$( '#epGuests' ).show();
}

function setFlagsBack(objId) {
	if (objId === '#showInfo') {
		eplistLoading = false;

	} else if (objId === '#showDesc') {
		descLoading = false;

	} else if (objId === '#showEpDesc') {
		epDescLoading = false;
	}
}

function checkShowInfo(objId, xOffset1) {
	var c = 0;
	do {
		if ($(objId).width() > 0) {
			cursorBusy('');
			resizeAll(xOffset1);
			setFlagsBack(objId);
			return;
		}
		if (c++ > 100) { return; }
	} while ($(objId).width() === 0);

	setFlagsBack(objId);
}

function cursorBusy(state) {
	$('body').css('cursor', state);
	$('#xbmcDB').css('cursor', state);
	$('#showInfo').css('cursor', state);
	$('td').css('cursor', state);
	$('a').css('cursor', state === '' ? 'pointer' : state);
	$('a.fancy_msgbox, a.fancy_addEpisode, li.dropdown-submenu>a').css('cursor', state === '' ? 'default' : state);
}

function loadLatestShowInfo(obj, sId, epId, trClass, eps) {
	fromNavbar = true;
	$('#showInfo').hide();

	loadShowInfoPlus(obj, sId, trClass, eps);
	loadEpDetails(obj, epId);
	toggleEps_(trClass, eps, null);

	epsPositioned = false;
	moveDivR(3);
	$('#showInfo').show();
	closeNavs();
}

function loadShowInfo(obj, id) {
	loadShowInfoPlus(obj, id, null, null);
	moveDivR(-1);
}

function loadShowInfoPlus(obj, id, trClass, eps) {
	if (epDescLoading || lastIdShow === id) { return; }

	cursorBusy('progress');

	$('#showEpDesc').text('');
	moveDivEpDesc(0);

	$('.sTR').children('TD').removeClass('selectedShow unselectedShow');
	$('#iD' + id).children('TD').addClass('selectedShow');

	loadEplistPlus(obj, trClass, eps);
	loadDesc(obj, id);

	lastIdShow = id;
	lastIdEp = -1;

	return true;
}

function loadEpDetails(obj, epId) {
	epsPositioned = false;
	if (epDescLoading || lastIdEp === epId) { return; }

	$('.epTR').children('TD').removeClass('selectedShow');
	$('#' + epId).children('TD').addClass('selectedShow');

	if (epDescLoadedOnce) {
		$('#showEpDesc').fadeOut(100, 'linear', function() {
			loadEpDetails_(obj, epId);
		});

	} else {
		loadEpDetails_(obj, epId);
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
		$(obj).attr('_href'),
		function() {
			$('#showEpDesc').animate(
				{ "width": "+=351px", "left": "-=352px", "height" : "+=400px"},
				"slow",
				function() {
					var height = $('#epDescription').height() + "px";
					checkShowInfo('#showEpDesc', 1);
					$("#showEpDesc").css({ "width" : "351px", "height" : height });
					$('#' + epId).children('TD').addClass('selectedShow');
				}
			);
		}
	);

	lastIdEp = epId;
	epsPositioned = true;
	return false;
}

function moveDivEpDesc(resizeOffset) {
	var posElem  = lastIdShow < 0 ? '#showsDiv' : '.selectedShow';
	if (!$(posElem).length) { return; }

	var yOffset  = 1;
	var xOffset  = $.browser.webkit ? 3 : 0;
	var left     = $(posElem).position().left;
	var top      = $('#showDesc').position().top + $(".showDesc").height() + yOffset;
	var width    = $('#showDesc').width();
	//var height   = "5px";
	var height   = "auto";
	var divTop   = top + "px";
	var divLeft  = Math.floor(left + resizeOffset + xOffset) + "px";

	$('#showEpDesc').css({
		"position"	: "absolute",
		"top"		: divTop,
		"left"		: divLeft,
		"width"		: "0px",
		"height"	: height,
		"z-index"	: "2"
	});

	if (!epDescLoading) {
		$("#showEpDesc").css({ "width" : "351px" });
		$("#epDescription").css({ "width" : $.browser.webkit ? "350px" : "352px" });
	}
}

function loadEplist(obj) {
	loadEplistPlus(obj, null, null);
}

function loadEplistPlus(obj, trClass, eps) {
	epsPositioned = false;
	if (eplistLoading) {
		return false;
	}

	if (epListLoadedOnce) {
		$('#showInfo').fadeOut(200, 'linear', function() {
			loadEplistPlus_(obj, trClass, eps);
		});

	} else {
		loadEplistPlus_(obj, trClass, eps);
	}

	epListLoadedOnce = true;
}

function loadEplist_(obj) {
	loadEplistPlus_(obj, null, null);
}

function loadEplistPlus_(obj, trClass, eps) {
	eplistLoading = true;

	$('#showInfo').text('');
	$('#showInfo').show();
	moveDivR(0);

	$('#showInfo').load(
		$(obj).attr('eplist'),
		function() {
			$('#showInfo').animate(
				{ "width": "+=385px" },
				"slow",
				function() {
					//var height = $('#serieTable').height() + "px";
					var height = "auto";
					checkShowInfo('#showInfo', 0);
					$('#showInfo').css({ "width" : "385px", "height" : height });
					if ($.browser.mozilla) { $("#showInfo").css({ "left" : "+=1px" }); }
					toggleEps(trClass, eps, null);
					initShowEpFancies();

					if (fromNavbar) {
						var offset = $('.selectedShow').offset().top - 50;
						$('html, body').animate({ scrollTop: offset }, 250);
						fromNavbar = false;
					}
				}
			);
		}
	);
}

function loadDesc(obj, id) {
	if (descLoading) {
		return false;
	}

	if (descLoadedOnce) {
		$('#showDesc').fadeOut(200, 'linear', function() {
			loadDesc_(obj, id);
		});

	} else {
		loadDesc_(obj, id);
	}

	descLoadedOnce = true;
}

function loadDesc_(obj, id) {
	var posElem = lastIdShow < 0 ? '#showsDiv' : '.selectedShow';
	if (!$(posElem).length) { return; }

	var yOffset = $.browser.webkit ? 0 : -1;
	var xOffset = $.browser.webkit ? 3 : 1;
	var resizeOffset = 0;
	descLoading = true;

	$('#showDesc').text('');
	$('#showDesc').show();
	moveDivL(resizeOffset);

	var top     = $(posElem).position().top + yOffset + "px";
	var left    = $(posElem).position().left;
	var divLeft = Math.floor(left + resizeOffset + xOffset - 352) + "px";

	$('#showDesc').load(
		$(obj).attr('desc'),
		function() {
			$('#showDesc').animate(
				{ "width": "+=351px", "left": divLeft, "top": top },
				"slow",
				function() {
					checkShowInfo('#showDesc', 0);
					$("#showDesc").css({ "width" : "351px", "left" : divLeft });
					$(".fClose").show();
					initShowDescFancies();
					drawDonut(id);
				}
			);
		}
	);
}

function moveDivL(resizeOffset) {
	var posElem  = lastIdShow < 0 ? '#showsDiv' : '.selectedShow';
	if (!$(posElem).length) { return; }

	var yOffset  = $.browser.webkit ? 0 : -1;
	var xOffset  = $.browser.webkit ? 2 : 0.5;
	var left     = $(posElem).position().left;
	var top      = $(posElem).position().top + yOffset;
	var width    = $("tbody:first").width();
	//var height   = "350px";
	var height   = "auto";
	var divTop   = top + "px";
	var divLeft  = Math.floor(left + resizeOffset + xOffset) + "px";

	$('#showDesc').css({
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

function moveDivR(resizeOffset) {
	if (epsPositioned) { return; }

	var posElem  = lastIdShow < 0 ? '#emptyTR' : '.selectedShow';
	if (!$(posElem).length) { return; }

	var yOffset  = $.browser.webkit ? 0 : -1;
	var xOffset  = $.browser.webkit ? 1 : -0.5;
	var left     = $(posElem).position().left;
	var top      = $(posElem).position().top + yOffset;
	var width    = $("tbody:first").width();
	//var height   = $('#serieTable').height() + "px";
	var height   = "auto";
	var divTop   = top + "px";
	var divLeft   = Math.floor(left + width + xOffset + resizeOffset) + "px";

	$('#showInfo').css({
		"position"	: "absolute",
		"top"		: divTop,
		"left"		: divLeft,
		"width"		: "0px",
		"height"	: height,
		"z-index"	: "2"
	});

	if (!eplistLoading) {
		$("#showInfo").css({ "width" : "385px" });
	}
}

function toggleAirdates() {
	if (!isAdmin) { return; }
	var w = $('#showsTable').width();

	if (airdateVisible) {
		//$( '.showName' ).removeClass($.browser.webkit ? 'airdShown' : 'airdShownM').addClass('airdHidden');
		$( '.showName' ).removeClass('airdShown').addClass('airdHidden');
		$( '.airdate' ).hide();
		w -= 75;
	} else {
		//$( '.showName' ).removeClass('airdHidden').addClass($.browser.webkit ? 'airdShown' : 'airdShownM');
		$( '.showName' ).removeClass('airdHidden').addClass('airdShown');
		$( '.airdate' ).show();
		w += 75;
	}
	airdateVisible = !airdateVisible;

	$('#showsTable').css({ "width" : w+"px" }).css({ "min-width" : w+"px" });
	//$('#showsTable').css({ "min-width" : w+"px" });
	resizeAll(0);
}

function checkForCheck() {
	if (unsaved)
		return (ids != null && ids != '' ? confirm("Attention:\nSelection will be lost!") : true);
	return true;
}

function clearSelectBoxes(obj) {
	var node_list = document.getElementsByTagName('input');
	var chk = obj != null ? obj.checked : false;
	if (obj == null) { $('#clearSelectAll').attr('checked', false); }

	for (i = 0; i < node_list.length; i++) {
		var a = node_list[i];
		if (a == null) {
			continue;
		}

		if (a.id === 'copyAsScript' || a.id === 'clearSelectAll') {
			continue;
		}

		if (a.type === 'checkbox' && a.checked !== chk && !a.disabled) {
			a.checked = chk;
			selected(a, false, false, isAdmin);
		}
	}

	var resBox  = document.getElementById('result');
	var listDiv = document.getElementById('movieList');
	if (listDiv == null) { return; }

	doRequest();

	listDiv.style.display = chk ? 'block' : 'none';
	if (!chk) {
		resBox.innerHTML = '';
		ids = '';
	}
}

function doRequest() { doRequest__(true, ids); }
function saveSelection() { saveSelection__(true, ids); }

function drawDonut(id) {
	if (id == null || id < 0) { return; }

	var url     = './detailSerieDesc.php?data&id=' + id;
	var ctx = null;
	if ( $('#donutChartPir').length ) {
		ctx = $("#donutChartPie").get(0).getContext('2d');
	} else {
		console.log('Error: Canvas not found with selector #donutChartPir');
		return;
	}

	var myDoughnutChart = new Chart(ctx, {
		type: 'doughnut',
		data: {
			labels: [],
			datasets: [{
				data: [],
				backgroundColor: []
			}]
		},
		options: { cutout: '40%', borderWidth: 0, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
	});

	ajax_chart(myDoughnutChart, url);
}

function ajax_chart(chart, url, data) {
	var data = data || {};

	$.getJSON(url, data).done(function(response) {
		chart.data.labels = response.labels;
		chart.data.datasets[0].backgroundColor = response.datasets[0].backgroundColor;
		chart.data.datasets[0].data = response.datasets[0].data;
		chart.update();
	});
}

function setAspectRatio(idFile, idEpisode, ar = "") {
	if (idFile === null || idEpisode === null) { return; }

	let answer = prompt("Enter aspect ratio", ar);
	if (answer === null || answer.trim() === "") { ar = ""; }
	else { answer = answer.replace(",", "."); }

	if (answer !== null && answer !== "" && !isNaN(answer)) {
		ar = Number.parseFloat(answer);
	}

	var url = './dbEdit.php?act=setAspectRatio&noForward=1&idFile=' + idFile + '&idMovie=' + idEpisode + '&' + 'aRatio=' + ar;
	$.ajax(url);
}
