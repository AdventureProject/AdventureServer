$( document ).ready(function() {
	$('.zooming').hover(function(){
		  $(this).addClass('transition');
	},function(){
		$(this).removeClass('transition');   
	});
});