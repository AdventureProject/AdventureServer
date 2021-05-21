<?php

require_once('Controller.php');

class TodaysWallpaperController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'todayswallpaper';
	}
    
    public function get( $request )
    {
        echo json_encode( getTodaysPhoto() );
    }
}

?>