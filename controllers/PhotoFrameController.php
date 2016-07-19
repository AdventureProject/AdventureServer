<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

class PhotoFrameController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
    
    public function get( $request )
    {
        // Use this to differentiate photo frames
        $photoFrameId = $request->args[0];
        
        $photoFramePhotoIds = getPhotoframes();

        $photoId = $photoFramePhotoIds[array_rand( $photoFramePhotoIds )];

        echo json_encode( getPhoto( $photoId, true, 800, 480 ) );
    }
}

?>