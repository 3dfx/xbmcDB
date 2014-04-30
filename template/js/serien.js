var airdateVisible   = false;
var descLoadedOnce   = true;
var epListLoadedOnce = true;
var epDescLoadedOnce = true;
var eplistLoading    = false;
var epDescLoading    = false;
var descLoading      = false;
var epsPositioned    = false;
var fromNavbar       = false;
var lastIdShow       = -1;
var lastIdEp         = -1;
var ids              = '';

$(document).ready(function() {
	$('.dropdown-toggle').dropdown();
	
	if (bindF) { $(document).keydown(function(event) {
		if(event.ctrlKey && event.keyCode == '70') {
			event.preventDefault();
			openNav('#dropSearch');
			$('#searchDBfor').focus();
		}
	}); }
});

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
	if (kC != 13) { return false; }

	var href = './?show=serien&dbSearch=';
	if (search != null && search != '') { href = href + search; }

	window.location.href=href;
}

function resetDbSearch() {
	if ($('#searchDBfor').val() == '') { return false; }
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

function toggleEps(id, eps, obj) {
	epsPositioned = false;
	toggleEps_(id, eps, obj);
}

function toggleEps_(id, eps, obj) {
	if (eps == null && obj == null) { return; }
	if (obj == null) {
		var tglId = id.replace('iD', 'tgl');
		obj = document.getElementById(tglId);
	}
	$('.plmin').removeClass('showlink').addClass('hidelink');
	
	var show = false;
	var trs  = document.getElementsByTagName('TR');
	if (trs != null) {
		for (var t = 0; t < trs.length; t++) {
			var tr = trs[t];
			if (tr == null || tr.className.indexOf('epTR') == -1) { continue; }
			
			var selectedSeason = (tr.className.indexOf(id) > -1);
			if (!selectedSeason) {
				if (tr.style.display != 'none') { $( tr ).hide(); }
				continue;
			}
			
			if (tr.style.display == 'none') {
				$( tr ).fadeIn(300, 'linear');
				show = true;
			} else {
				$( tr ).fadeOut(300, 'linear');
			}
		}
	}
	
	if (obj != null) {
		if (show) {
			$(obj).removeClass('hidelink');
			$(obj).addClass('showlink');
		} else {
			$(obj).removeClass('showlink');
			$(obj).addClass('hidelink');
		}
	}

	moveDivR(show ? 1 : 0);
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
	if (objId == '#showInfo') {
		eplistLoading = false;

	} else if (objId == '#showDesc') {
		descLoading = false;

	} else if (objId == '#showEpDesc') {
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
	} while ($(objId).width() == 0);

	setFlagsBack(objId);
}

function cursorBusy(state) {
	$('body').css('cursor', state);
	$('#xbmcDB').css('cursor', state);
	$('#showInfo').css('cursor', state);
	$('td').css('cursor', state);
	$('a').css('cursor', state == '' ? 'pointer' : state);
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
}

function loadShowInfo(obj, id) {
	loadShowInfoPlus(obj, id, null, null);
}

function loadShowInfoPlus(obj, id, trClass, eps) {
	if (epDescLoading || lastIdShow == id) { return; }
	
	cursorBusy('progress');
	
	$('#showEpDesc').text('');
	moveDivEpDesc(0);
	
	$('.sTR').children('TD').removeClass('selectedShow');
	$('#iD' + id).children('TD').addClass('selectedShow');
	
	loadEplistPlus(obj, trClass, eps);
	loadDesc(obj);
	
	lastIdShow = id;
	lastIdEp = -1;
	
	return true;
}

function loadEpDetails(obj, epId) {
	epsPositioned = false;
	if (epDescLoading || lastIdEp == epId) { return; }
	
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
	var posElem  = lastIdShow < 0 ? '#showsTable' : '.selectedShow';
	
	var yOffset  = 1;
	var xOffset  = $.browser.webkit ? 3 : 0;
	var left     = $(posElem).position().left;
	var top      = $("#showDesc").position().top + $(".showDesc").height() + yOffset;
	var width    = $("#showDesc").width();
	var height   = "5px";
	var divTop   = top + "px";
	var divLeft  = Math.floor(left + resizeOffset + xOffset) + "px";
	
	$("#showEpDesc").css({
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
					var height = $('#serieTable').height() + "px";
					checkShowInfo('#showInfo', 0);
					$("#showInfo").css({ "width" : "385px", "height" : height });
					if ($.browser.mozilla) { $("#showInfo").css({ "left" : "+=1px" }); }
					toggleEps(trClass, eps, null);
					
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
	var posElem = lastIdShow < 0 ? '#showsTable' : '.selectedShow';
	
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
				}
			);
		}
	);
}

function moveDivL(resizeOffset) {
	var posElem  = lastIdShow < 0 ? '#showsTable' : '.selectedShow';
	
	var yOffset  = $.browser.webkit ? 0 : -1;
	var xOffset  = $.browser.webkit ? 2 : 0.5;
	var left     = $(posElem).position().left;
	var top      = $(posElem).position().top + yOffset;
	var width    = $("tbody:first").width();
	var height   = "350px";
	var divTop   = top + "px";
	var divLeft  = Math.floor(left + resizeOffset + xOffset) + "px";
	
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

function moveDivR(resizeOffset) {
	if (epsPositioned) { return; }
	
	var posElem  = lastIdShow < 0 ? '#emptyTR' : '.selectedShow';
	
	var yOffset  = $.browser.webkit ? 0 : -1;
	var xOffset  = $.browser.webkit ? 1 : -0.5;
	var left     = $(posElem).position().left;
	var top      = $(posElem).position().top + yOffset;
	var width    = $("tbody:first").width();
	var height   = $('#serieTable').height() + "px";
	var divTop   = top + "px";
	var divLeft   = Math.floor(left + width + xOffset + resizeOffset) + "px";
	
	$("#showInfo").css({
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
	var w = $( '#showsTable' ).width();
	
	if (airdateVisible) { $( '.airdate' ).hide(); w -= 75; }
	else { $( '.airdate' ).show(); w += 75; }
	airdateVisible = !airdateVisible;
	
	$('#showsTable').css({ "width" : w+"px" });
	resizeAll(0);
}

function checkForCheck() {
	return (ids != null && ids != '' ? confirm("Attention:\nSelection will be lost!") : true);
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

		if (a.id == 'copyAsScript' || a.id == 'clearSelectAll') {
			continue;
		}

		if (a.type == 'checkbox' && a.checked != chk && !a.disabled) {
			a.checked = chk;
			selected(a, false, false, isAdmin);
		}
	}

	var resBox  = document.getElementById('result');
	var listDiv = document.getElementById('movieList');
	if (listDiv == null) { return; }

	doRequest(isAdmin);

	listDiv.style.display = chk ? 'block' : 'none';
	if (!chk) {
		resBox.innerHTML = '';
		ids = '';
	}
}

function selected(obj, changeMaster, postRequest, admin) {
	var tr = $( obj ).parent().parent();

	if (obj.checked) {
		ids = ids + (ids.length == 0 ? '' : ', ') + obj.value;
		$( tr ).children('TD').addClass('highLighTR');
		
	} else {
		$( tr ).children('TD').removeClass('highLighTR');

		if (ids.indexOf(obj.value + ', ') != -1) {
			ids = ids.replace(obj.value + ', ', '');

		} else if (ids.indexOf(', ' + obj.value) != -1) {
			ids = ids.replace(', ' + obj.value, '');

		} else if (ids.indexOf(',') == -1) {
			ids = ids.replace(obj.value, '');
		}
	}
	
	var resBox  = document.getElementById('result');
	var listDiv = document.getElementById('movieList');
	if (listDiv == null) { return; }
	var clearSelectAll = document.getElementById('clearSelectAll');
	if (ids == '') {
		listDiv.style.display = 'none';
		resBox.innerHTML = '';

	} else {
		listDiv.style.display = 'block';
	}

	if (changeMaster) {
		clearSelectAll.checked = ids == '' ? false : true;
	}

	if (postRequest) {
		doRequest(admin);
	}
	
	return true;
}

function doRequest(admin) {
	var resBox = document.getElementById('result');
	if (resBox == null) { return; }

	var copyAsScript = document.getElementById('copyAsScript');
	var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;

	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+"&isShow=1&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin,
		success: function(data){
			$("#result").html(data);
		}
	});
}

function saveSelection(admin) {
	var resBox = document.getElementById('result');
	if (resBox == null) { return; }

	var copyAsScript = document.getElementById('copyAsScript');
	var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;
	
	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+"&isShow=1&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin + "&forOrder=1",
		success: function(data) {
			if (data == '1') { alert('Selection saved!'); }
			else if (data == '2') { alert('Selection appended!'); }
			else { alert('Error saving!'); }

			if (data != '-1') { clearSelectBoxes(null); }
		}
	});
}