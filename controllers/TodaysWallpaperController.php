<?php

require_once('Controller.php');
require_once('Request.php');

class TodaysWallpaperController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
    
    public function get( $request )
    {
        echo json_encode( getTodaysPhoto() );
    }
}

?>