<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

class PhotoWallController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
    
    public function get( $request )
    {
        if( count($request->args) == 0 )
        {
            header( 'Location: https://www.flickr.com/photos/adamwbrown/albums/72157661260548425' );
        }
        else
        {
            $photoId = $request->args[0];
            
            $photoAlbum = 72157661260548425;
    
            $flickrId = -1;

            $db = getDb();
            $row = $db->photos()->select('flickr_id')->where("photowall_id", $photoId);
            if( $row !== false )
            {
                $result = $row->fetch();
                if( $result !== false )
                {
                    $flickrId = $result['flickr_id'];
                }
            }

            if( $flickrId > 0 )
            {
                header( "Location: https://www.flickr.com/photos/adamwbrown/$flickrId/in/album-$photoAlbum/" );
            }
            else
            {
                echo 'Not Found';
            }
        }
    }
}

?>