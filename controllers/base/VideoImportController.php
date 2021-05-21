<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class VideoImportController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'videoimport';
	}

	public function getTitle()
	{
		return 'Video Import';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file( 'BODY_FILE', 'templates/video_import.html' );

		$xtpl->parse( 'main.body' );
	}

	public function post( $request )
	{
		if( $request->post['flickr_id'] )
		{
			$flickrId = $request->post['flickr_id'];
echo $flickrId;
			$keys = getKeys();
			$key = $keys->flickr_api->key;
			$secret = $keys->flickr_api->secret;
			$flickr = new Flickr( $key, $secret );

			$method = 'flickr.photos.getSizes';
			$args = array( 'photo_id' => $flickrId );
			$responseSizes = $flickr->call_method( $method, $args );

			foreach( $responseSizes['sizes']['size'] as $size )
			{
				if( $size['label'] == 'Site MP4' )
				{
					$videoUrl = $size['source'];
					break;
				}
			}

			echo $videoUrl;
			exit();

			$updateResult = false;
			$db = getDb();
			//$updateResult = $row->update();

			if( $updateResult )
			{
				error_log( 'Video updated' );

				//header( 'Location: /' . $this->urlStub() . '/' . $flickrId );
			}
			else
			{
				error_log( 'Video update FAILED' );
				echo 'Video update FAILED';
			}
		}
	}
}

?>