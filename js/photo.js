function confirmPhotoWall( elem )
{
	return confirm('Do you really want to all this to the PhotoWall?');
}

$(document).ready(function() {
      $("#body_container").scroll(function() {
		  var isElementInView = Utils.isElementInView($('#info_card'), true, true);
          if( isElementInView )
		  {
			$("#info_button").fadeOut();
		  }
		  else
		  {
			  $("#info_button").fadeIn();
		  }
      });
	
	$("#info_button").on('click', function(e) {
		 e.preventDefault();
		 var target = $(this).attr('href');
		 $('html, body, #body_container').animate({
		   scrollTop: ($(target).offset().top)
		 }, 500);
	  });
  });

function Utils() {

}

Utils.prototype = {
    constructor: Utils,
    isElementInView: function (element, fullyInView, orBelow) {
        var pageTop = $(window).scrollTop();
        var pageBottom = pageTop + $(window).height();
        var elementTop = $(element).offset().top;
        var elementBottom = elementTop + $(element).height();

        if (fullyInView === true && orBelow === false) {
            return ((pageTop < elementTop) && (pageBottom > elementBottom));
		} else if (fullyInView === true && orBelow === true) {
			return ((pageTop < elementTop) && (pageBottom > elementBottom)) || (pageBottom > elementBottom);
        } else {
            return ((elementTop <= pageBottom) && (elementBottom >= pageTop));
        }
    }
};

var Utils = new Utils();