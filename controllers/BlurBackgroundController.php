<?php

require_once('utils/photos.php');
require_once('Controller.php');

class BlurBackgroundController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'blurbackground';
	}
    
    public function get( $request )
    {
        if( !empty( $request->args[0] ) && is_numeric( $request->args[0] ) && $request->args[0] > -1 && $request->args[0] < 1000000000 )
        {
			$photoId = $request->args[0];
			
			$targetFileName = 'blurred.jpg';
			$targetPath = getB2PhotoMetaPath( $photoId ) . '/' . $targetFileName;
			
			if( !b2PhotoMetaExists( $photoId, $targetPath ) )
			{
				
				$localPath = 'data/blur/';
				$fileName = "blurred_background_{$request->args[0]}.jpg";
				$localFile = $localPath . $fileName;
					
				// Now make the new one
				$todaysPhoto = getTodaysPhoto( 1024, 768 );
				
				if( $request->args[0] !== $todaysPhoto->id )
				{
					echo 'Must be todays photo';
					exit();
				}
				
				// Download the image from Flickr
				file_put_contents($localFile, file_get_contents( $todaysPhoto->image ));
				
				// Read the file and blur it
				$image = new Imagick( $localFile );
				if( $image )
				{
					$image->gaussianBlurImage(15,5);
					
					$image->writeImage( $localFile );
					
					// Upload blurred image to B2
					uploadB2File( $localFile, $targetPath );
				}
			}
			
			//$this->caching_headers( $localFile, filemtime($localFile) );
			$remotePath = b2GetPublicBlurUrl( $photoId );
			//$remoteFile = fopen($remotePath, 'r');
			
			header('Content-Type: image/jpeg');
			//header('Content-Length: ' . filesize($remotePath));
			readfile( $remotePath );
        }
    }
	
	function caching_headers($file, $timestamp) {
		$gmt_mtime = gmdate('r', $timestamp);
		header('ETag: "'.md5($timestamp.$file).'"');
		header('Last-Modified: '.$gmt_mtime);
		header('Cache-Control: public');

		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp.$file)) {
				header('HTTP/1.1 304 Not Modified');
				exit();
			}
		}
	}
}

?>