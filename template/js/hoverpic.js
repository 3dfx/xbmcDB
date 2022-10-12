jQuery(document).ready(function() {
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

		if (hHeight == 0) { res = 138/2; }
		else if (CWOffset - val  < hHeight) { res = 0; }
		else if (CWOffset - val2 > hHeight) { res = hHeight / 2; }
		else { res = hHeight; }

		/*if (offsetTop - enterPos < $("#hoverpic").height()) { console.log('a'); res = 0; }
		else if (CWOffset - offsetTop - enterPos < 150) { console.log('b'); res = $("#hoverpic").height(); }
		else if (CWOffset > yHoverPicOffset) { console.log('c'); res = 90; }*/
		return res;
	}
});