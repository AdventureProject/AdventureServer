<?php

require_once('Controller.php');

require_once('utils/photos.php');

class PhotoWallController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'photowall';
	}
    
    public function get( $request )
    {
        if( count($request->args) == 0 )
        {
            header( 'Location: https://www.flickr.com/photos/adamwbrown/albums/72157661260548425' );
        }
        else
        {
            $photoWallId = $request->args[0];
            
            $photoAlbum = 72157661260548425;
    
            $photoId = -1;

            $db = getDb();
            $row = $db->photos()->select('id')->where("photowall_id", $photoWallId);
            if( $row !== false )
            {
                $result = $row->fetch();
                if( $result !== false )
                {
                    $photoId = $result['id'];
                }
            }

            if( $photoId > 0 )
            {
				$photoFrameId = 7282; // Hard coded for now
				header( "Location: http://wethinkadventure.rocks/photowallnfc/$photoFrameId/$photoId" );
            }
            else
            {
                echo 'Not Found';
            }
        }
    }
}

?>