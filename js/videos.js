$(document).ready(function() {
	// Setup the video lightbox
	$('.popup-youtube').magnificPopup({
		disableOn: 700,
		type: 'iframe',
		mainClass: 'mfp-fade',
		removalDelay: 160,
		preloader: false,

		fixedContentPos: false
	});
	
	// Launch the video player if a video is specified
	$queryString = getQueryParams();
	if( $queryString.play  !== undefined )
	{
		var elemId = '#video_' + $queryString.play;
		if( $( elemId ).length > 0 )
		{
			$( elemId ).click();
		}
	}
	
	var clipboard = new Clipboard('.btn');
});

function getQueryParams()
{
	var vars = [], hash;
    var q = document.URL.split('?')[1];
    if(q !== undefined){
        q = q.split('&');
        for(var i = 0; i < q.length; i++){
            hash = q[i].split('=');
            vars.push(hash[1]);
            vars[hash[0]] = hash[1];
        }
	}
	
	return vars;
}

function showCopyConfirm()
{
	swal({
		title: "Link Copied!",
		text: "Video link copied to your click board",
		timer: 2500,
		showConfirmButton: false,
		type: "success"
	});
}