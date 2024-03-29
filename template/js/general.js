$(document).ready(function() {
	if (newMovies) { newMoviesAvailable(); }
	$(document).keydown(function(event) {
		if(event.ctrlKey && event.keyCode == '65') {
			event.preventDefault();
			if (!$('#clearSelectAll').length) { return; }
			var clearSelectAll = $('#clearSelectAll');
			var chk = $(clearSelectAll).prop('checked');
			$(clearSelectAll).attr('checked', !chk);
			clearSelectAll.checked = !chk;
			clearSelectBoxes(clearSelectAll);
		}
	});
});

function darkSideOfTheForce() {
	$('.tabDiv').addClass('darkSide');
	//$('#showDesc').addClass('darkSide');
	//$('#showInfo').addClass('darkSide');
	//$('#showEpDesc').addClass('darkSide');
}

function getKeyCode(event) {
	event = event || window.event;
	return event.keyCode;
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

function newMoviesAvailable() {
	if (!gotoConfirmation()) { return; }

	window.location.href='./?show=filme&newmode=1&newsort=2&unseen=3&dbSearch&which&just&sort&mode&country';
}

function gotoConfirmation() {
	return confirm("New Movies in DB since last visit!\nDo yout want to check them now?");
}

function doRequest__(isShow, ids) {
	var resBox = document.getElementById('result');
	if (resBox == null) { return; }

	var copyAsScript = document.getElementById('copyAsScript');
	var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;

	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+(isShow ? "&isShow=1" : "")+"&ids=" + ids + "&copyAsScript=" + asscript,
		success: function(data) {
			// noinspection EqualityComparisonWithCoercionJS
			if (data == '-3') { alert('Session expired!'); setTimeout("location.reload(true);", 150); }
			$("#result").html(data);
		}
	});
}

function saveSelection() {
	unsaved = false;
	saveSelection__(false, ids);
}

function saveSelection__(isShow, ids) {
	var resBox = document.getElementById('result');
	if (resBox == null) { return; }

	var copyAsScript = document.getElementById('copyAsScript');
	var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;

	$.ajax({
		type: "POST",
		url: "request.php",
		data: "contentpage="+(isShow ? "&isShow=1" : "")+"&ids=" + ids + "&copyAsScript=" + asscript + "&forOrder=1",
		success: function(data) {
			if (data == '1') { alert('Selection saved!'); }
			else if (data == '2') { alert('Selection appended!'); }
			else if (data == '-3') { alert('Session expired!'); setTimeout("location.reload(true);", 150); }
			else { alert('Error saving!'); }

			//if (data != '-1') { clearSelectBoxes(null); }
		}
	});
}

function selected(obj, changeMaster, postRequest, admin) {
	collectIds__();

	if (obj != null) {
		var tr = $( obj ).parent().parent();
		var td = $( tr ).children('TD');
		if (obj.checked) {
			unsaved = true;
			//addId(obj.value);
			td.addClass('highLighTR');

		} else {
			unsaved = true;
			//removeId(obj.value);
			td.removeClass('highLighTR');
		}
	}

	var resBox  = document.getElementById('result');
	var listDiv = document.getElementById('movieList');
	if (listDiv == null) { return; }
	var clearSelectAll = document.getElementById('clearSelectAll');
	if (ids == null || ids === '') {
		listDiv.style.display = 'none';
		resBox.innerHTML = '';

	} else {
		listDiv.style.display = 'block';
	}

	if (changeMaster) {
		clearSelectAll.checked = ids !== '';
	}

	if (postRequest) {
		doRequest();
	}

	return true;
}

function collectIds__() {
	ids = '';
	var tds = $('TD');
	for (var r = 0; r < tds.length; r++) {
		var td = tds[r];
		var obj = $( td ).find('.checka')[0];
		if (obj == null || obj.type !== "checkbox") { continue; }
		if (obj.disabled || !obj.checked) { continue; }
		addId(obj.value);
	}
}

function addId(id) {
	if (ids.search(id + ',') !== -1 || ids.search(', ' + id) !== -1) {
		return;
	}
	ids = ids + (ids.length === 0 ? '' : ', ') + id;
}
