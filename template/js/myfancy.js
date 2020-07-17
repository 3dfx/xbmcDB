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

/*
	$("img[href$='.jpg'],img[href$='.png']").fancybox({
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
*/

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

	// these 2 variable determine popup's distance from the cursor
	// you might want to adjust to get the right result
	xHoverPicOffset = 30;
	yHoverPicOffset = 220;
	enterPos = null;
	enterMoved = false;
	$("a.hoverpic").hover(
		function(e) {
			this.t = this.title;
			this.title = "";

			var c = (this.t != "") ? "<br/>" + this.t : "";
			$("body").append("<p id='hoverpic'><img src='"+ this.rel +"'/>"+ c +"</p>");
			$("#hoverpic")
				.css("top",(e.pageY - correctYoffset(e, $(this).offset().top)) + "px")
				.css("left",(e.pageX + xHoverPicOffset) + "px")
				.fadeIn("fast");
		}, function(){
			this.title = this.t;
			enterPos = null;
			enterMoved = false;
			$("#hoverpic").remove();
		}
	);
	$("a.hoverpic").mousemove(function(e) {
		enterMoved = true;
		$("#hoverpic")
			.css("top",(e.pageY - correctYoffset(e, $(this).offset().top)) + "px")
			.css("left",(e.pageX + xHoverPicOffset) + "px");
	});

	function correctYoffset(e, offsetTop) {
		var CWOffset = $(window).height();
		var res = 0;

		if (enterPos == null) { enterPos = $("#hoverpic").offset().top; }
		var val  = enterPos - (offsetTop - CWOffset);
		var val2 = CWOffset - val;
		var hHeight = $("#hoverpic").height();

		//console.log( enterMoved + ', ' + enterPos + ', ' + val + ', ' + (CWOffset - val) + ', ' + hHeight + ', ' + offsetTop + ', ' + e.pageY + ', ' + CWOffset );

		if (hHeight == 0) { res = 138/2; }
		else if (CWOffset - val  < hHeight) { res = 0; }
		else if (CWOffset - val2 > hHeight) { res = hHeight / 2; }
		else { res = hHeight; }

		/*if (offsetTop - enterPos < $("#hoverpic").height()) { console.log('a'); res = 0; }
		else if (CWOffset - offsetTop - enterPos < 150) { console.log('b'); res = $("#hoverpic").height(); }
		else if (CWOffset > yHoverPicOffset) { console.log('c'); res = 90; }*/
		return res;
	}

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
		'type'			: 'iframe'
	});
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
		'width'			: 400,
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
		'width'			: 480,
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