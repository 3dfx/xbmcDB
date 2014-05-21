var ids          = '';
var searchLength = 0;

$(document).ready(function() {
	$('.dropdown-toggle').dropdown();

	$('.styled-select').select2();
	$('.styled-select2').select2();
	$('.select2-search').hide();
	
	if (bindF) { $(document).keydown(function(event) {
		if(event.ctrlKey && event.keyCode == '70') {
			event.preventDefault();
			openNav('#dropSearch');
			$('#searchDBfor').focus();
		}
	}); }
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
}

function doRequest(admin) {
	var resBox = document.getElementById('result');
	if (resBox == null) { return; }

	var copyAsScript = document.getElementById('copyAsScript');
	var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;

//			document.getElement
	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+"&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin,
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

//			document.getElement
	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+"&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin + "&forOrder=1",
		success: function(data) {
			if (data == '1') { alert('Selection saved!'); }
			else if (data == '2') { alert('Selection appended!'); }
			else { alert('Error saving!'); }

			if (data != '-1') { clearSelectBoxes(null); }
		}
	});
}

function collectIds() {
	ids = '';
	var trs = $('TR.searchFlag');
	for (var r = 0; r < trs.length; r++) {
		var tr = trs[r];
		var obj = $( tr ).find('.checka')[0];
		if (obj.disabled || !obj.checked) { continue; }
		ids = ids + (ids.length == 0 ? '' : ', ') + obj.value;
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

	if (search.length < 3) {
		$('TR.searchFlag').show();
		$('TR').find('.checka').removeAttr('disabled');
		return false;
	}

	var trs = $('TR.searchFlag');
	for (var r = 0; r < trs.length; r++) {
		var tr = trs[r];

		var foundString = false;
		var spans = $( tr ).find('.searchField');
		for (var s = 0; s < spans.length; s++) {
			var span = spans[s];
			if (span == null) { continue; }

			var string = $.trim(span.innerHTML).toLowerCase();
			if (string == null || string == '') { continue; }

			foundString = (string.indexOf(search) >= 0 ? true : false);
			if (foundString) { break; }
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

	if (search != null && search != '') { $('span').highlight(search); }
	searchLength = search.length;

	collectIds();
	doRequest(isAdmin);
}

function searchDbForString(obj, event) {
	if (obj == null) { return false; }
	var search = $.trim(obj.value).toLowerCase();

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

function getKeyCode(event) {
	event = event || window.event;
	return event.keyCode;
}
