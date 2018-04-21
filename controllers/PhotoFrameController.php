<?php

require_once('Controller.php');

require_once('utils/photos.php');

class PhotoFrameController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'photoframe';
	}
    
    public function get( $request )
    {
        if( count($request->args) == 1 )
		{
			// Use this to differentiate photo frames
        	$photoFrameId = $request->args[0];

			$photo = getPhotoframePhoto();

			echo json_encode( getPhoto( $photo['id'], true, 800, 480 ) );
		}
		else if( count($request->args) == 2 )
		{
			$photoFrameId = $request->args[0];
			$photoId = $request->args[1];

			echo json_encode( getPhotoById( $photoId, true, 800, 480 ) );
		}
    }
}

?>