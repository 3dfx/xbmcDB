function openNav(objId) {
	closeNavs();
	$(objId).addClass('open');
}

function closeNavs() {
	$('#dropAdmin').removeClass('open');
}
