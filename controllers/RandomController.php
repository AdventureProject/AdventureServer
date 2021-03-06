<?php

require_once('Controller.php');

require_once('utils/photos.php');

class RandomController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'random';
	}
    
    public function get( $request )
    {
        $photoIds = getRandomWallpaper();
        echo json_encode( getPhoto( $photoIds['flickr_id'], $photoIds['id'] ) );
    }
}

?>