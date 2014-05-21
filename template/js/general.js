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

function newMoviesAvailable() {
	if (!gotoConfirmation()) { return; }
	
	var href = './?show=filme&newmode=1&newsort=2&unseen=3&dbSearch&which&just&sort&mode&country';
	window.location.href=href;
}

function gotoConfirmation() {
	return confirm("New Movies in DB since last visit!\nDo yout want to check them now?");
}
