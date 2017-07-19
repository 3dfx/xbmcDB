var ids          = '';
var unsaved      = false;
var searchLength = 0;

$(document).ready(function() {
	$('.dropdown-toggle').dropdown();

	$('.styled-select').select2();
	$('.styled-select2').select2();
	$('.select2-search').hide();

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

function cursorBusy(state) {
	$('body').css('cursor', state);
	$('#xbmcDB').css('cursor', state);
	$('td').css('cursor', state);
	$('a').css('cursor', state);
}

function openNav(objId) {
	closeNavs();
	$(objId).addClass('open');
}

function closeNavs() {
	$('#dropOptions').removeClass('open');
	$('#dropViewmode').removeClass('open');
	$('#dropLanguage').removeClass('open');
	$('#dropAdmin').removeClass('open');
	$('#dropSearch').removeClass('open');
}

function checkForCheck() {
	if (unsaved)
		return (ids != null && ids != '' ? confirm("Attention:\nUnsaved selection will be lost!") : true);
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

	doRequest();

	listDiv.style.display = chk ? 'block' : 'none';
	if (!chk) {
		resBox.innerHTML = '';
		ids = '';
	}
}

function doRequest() { doRequest__(false, ids); }

function collectIds() {
	ids = '';
	var trs = $('TR.searchFlag');
	for (var r = 0; r < trs.length; r++) {
		var tr = trs[r];
		var obj = $( tr ).find('.checka')[0];
		if (obj.disabled || !obj.checked) { continue; }
		addId(obj.value);
	}
}

function newlyChange() {
	if (!checkForCheck()) { return false; }
	$('body').css('cursor', 'wait');
	moviefrm.submit();
}

function searchForString(obj, event) {
	var search = ( obj == null ? '' : $.trim(obj.value).toLowerCase() );

	if (obj != null && event != null) {
		var kC = getKeyCode(event);
		if (kC != 13) { return false; }
	}

	$('span').removeHighlight();

	if (search == '' || search.length < 3) {
		$('TR.searchFlag').show();
		$('TR').find('.checka').removeAttr('disabled');
		return false;
	}

	var srch = search.split(' ');

	var trs = $('TR.searchFlag');
	for (var r = 0; r < trs.length; r++) {
		var tr = trs[r];

		var foundSplits = 0;
		var foundString = false;
		var spans = $( tr ).find('.searchField');
		for (var s = 0; s < spans.length; s++) {
			var span = spans[s];
			if (span == null) { continue; }

			var string = $.trim(span.innerHTML).toLowerCase();
			if (string == null || string == '') { continue; }

			for (var i = 0; i < srch.length; i++) {
				if (string.indexOf(srch[i]) >= 0) {
					foundSplits++;
					break;
				}
			}

			//foundString = (string.indexOf(search) >= 0 ? true : false);
			//if (foundString) { break; }

			if (foundSplits >= srch.length) {
				foundString = true;
				break;
			}
		}

		if (foundString) {
			$( tr ).show(); 
			$( $( tr ).find('.checka')[0] ).removeAttr('disabled');

		} else {
			$( tr ).hide(); 
			$( $( tr ).find('.checka')[0] ).attr('disabled', 'disabled');
			$( $( tr ).find('.checka')[0] ).attr('checked', false);
		}

		if (foundString) { continue; }
	}

	for (var i = 0; i < srch.length; i++) {
		$('span').highlight(srch[i]);
	}
	searchLength = search.length;

	collectIds();
	doRequest();
}

function searchDbForString(obj, event) {
	if (obj == null) { return false; }
	var search = $.trim(obj.value);

	var kC = getKeyCode(event);
	if (kC != 13) { return false; }
	if (!checkForCheck()) { return false; }

	var href = './?show=filme&dbSearch=';
	if (search != null && search != '') { href = href + search; }

	window.location.href=href;
}

function resetDbSearch() {
	if (!checkForCheck() || $('#searchDBfor').val() == '') { return false; }
	window.location.href='./?show=filme&dbSearch=';
}

function resetFilter() {
	$('#searchfor').val('');
	searchForString(null, null);
}
