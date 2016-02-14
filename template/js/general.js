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

function newMoviesAvailable() {
	if (!gotoConfirmation()) { return; }
	
	var href = './?show=filme&newmode=1&newsort=2&unseen=3&dbSearch&which&just&sort&mode&country';
	window.location.href=href;
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
			if (data == '-3') { alert('Session expired!'); setTimeout("location.reload(true);", 150); }
			$("#result").html(data);
		}
	});
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
			
			if (data != '-1') { clearSelectBoxes(null); }
		}
	});
}
