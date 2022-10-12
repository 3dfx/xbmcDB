jQuery(document).ready(function() {

	$(".innerCoverImg").fancybox({
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'hideOnContentClick'	: true,
		'showCloseButton'	: false,
		'padding'		: 0,
		'margin'		: 5,
		'overlayOpacity'	: 1,
		'overlayColor'		: '#000',
		'type'			: 'image',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'titlePosition' 	: 'over',
		'titleFormat'       	: function(title, currentArray, currentIndex, currentOpts) {
			return '<span id="fancybox-title-over" style="text-align:center; font-weight:bold; font-size:20px; padding:5px;">' + title + '</span>';
		},
		'onComplete'		: function() {
			$("#fancybox-title").show();

			$("#fancybox-wrap").hover(function() {
				$("#fancybox-title").hide();
			}, function() {
				$("#fancybox-title").show();
			});
		}
	});


	$(".openImdb").fancybox({
		'width'			: '63%',
		'height'		: '94%',
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'padding'		: 5,
		'margin'		: 10,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".openImdbDetail").fancybox({
		'width'			: '95%',
		'height'		: '94%',
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'padding'		: 5,
		'margin'		: 10,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancyimage1").fancybox({
		'centerOnScroll'	: true
	});

	$(".fancy_iframe").fancybox({
		'width'			: 1280,
		'height'		: 720,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_iframe2").fancybox({
		'width'			: '80%',
		'height'		: '90%',
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_iframe3").fancybox({
		'width'			: 350,
		'height'		: 150,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.98,
		'autoScale'		: true,
		'centerOnScroll'	: false,
		'padding'		: 5,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_explorer").fancybox({
		'width'			: 700,
		'height'		: 650,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'auto',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_movieset").fancybox({
		'width'			: 325,
		'height'		:  50,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.98,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: false,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_movieEdit").fancybox({
		'width'			: 380,
		'height'		: 235,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.55,
		'padding'		: 1,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'type'			: 'iframe',
		'titleShow'       	: false,
		'borderBgs'		: false
	});
}); //document.ready

function initShowFancies() {
	initFancyMsgbox();

	$(".fancy_iframe4").fancybox({
		'width'			: 1280,
		'height'		: 720,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'auto',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_addEpisode").fancybox({
		'width'			: 560,
		'height'		: 480,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: false,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'type'			: 'iframe',
		'titleShow'       	: false,
		'onStart'	        : function() {
			cursorBusy('progress');
		},
		'onCleanup'		: function() {
			cursorBusy('');
		}
	});

	$(".fancy_changesrc").fancybox({
		'width'			: 325,
		'height'		:  50,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.98,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: false,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'titleShow'       	: false,
		'type'			: 'iframe'
	});
} //initShowFancies

function initShowEpFancies() {
	$(".fancy_addEpisode").fancybox({
		'width'			: 560,
		'height'		: 480,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: false,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'type'			: 'iframe',
		'titleShow'       	: false,
		'onStart'	        : function() {
			cursorBusy('progress');
		},
		'onCleanup'		: function() {
			/*
			$('body').css('cursor', 'default');
			$('td').css('cursor', 'default');
			$('a').css('cursor', 'default');
			$('.addBoxx').css('cursor', 'default');
			$('#showSelectTable').css('cursor', 'default');
			$('#episodeAdd').css('cursor', 'default');
			*/
			cursorBusy('');
		}
	});
} //initShowEpFancies

function initFancyBrowsing() {
	$(".fancy_iframe4").fancybox({
		'width'			: 1280,
		'height'		: 720,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'auto',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});
}

function initShowDescFancies() {
	initFancyMsgbox();
	initFancyBrowsing();
/*
	$("#tvBanner").fancybox({
		'width'			: '63%',
		'height'		: '94%',
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'padding'		: 5,
		'margin'		: 10,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'type'			: 'html'
	});
*/
} //initShowDescFancies

function initFancyMsgbox() {
	$(".fancy_msgbox").fancybox({
		'width'			: 300,
		'height'		: 50,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.55,
		'padding'		: 1,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'type'			: 'iframe',
		'titleShow'       	: false,
		'borderBgs'		: false
	});
}

function initNavbarFancies() {
	initFancyMsgbox();

	$(".fancy_logs").fancybox({
		'width'			: 900,
		'height'		: 500,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_cpu").fancybox({
		'width'			: 900,
		'height'		: 460,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_blocks").fancybox({
		'width'			: 800,
		'height'		: 460,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'no',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_sets").fancybox({
		'width'			: 550,
		'height'		: 650,
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'elastic',
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.9,
		'speedIn'		: 500,
		'speedOut'		: 250,
		'padding'		: 5,
		'autoScale'		: true,
		'centerOnScroll'	: true,
		'enableEscapeButton'	: true,
		'scrolling'		: 'auto',
		'titleShow'       	: false,
		'type'			: 'iframe'
	});

	$(".fancy_iframe3").fancybox({
		'width'			: 350,
		'height'		: 150,
		'overlayColor'		: '#000',
		'overlayOpacity'	: 0.98,
		'autoScale'		: true,
		'centerOnScroll'	: false,
		'padding'		: 5,
		'scrolling'		: 'no',
		'transitionIn'		: 'elastic',
		'transitionOut'		: 'none',
		'enableEscapeButton'	: true,
		'titleShow'       	: false,
		'type'			: 'iframe'
	});
}
