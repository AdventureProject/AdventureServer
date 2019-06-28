<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class AddAlbumCardController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'addalbumcard';
	}

	public function getTitle()
	{
		return 'Add Album Card';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file( 'BODY_FILE', 'templates/add_album_card.html' );
		//$this->addJsFile( '/js/add_photo.js', $xtpl );
		$xtpl->parse( 'main.body' );
	}

	public function post( $request )
	{
		echo 'test';
		if( !empty($request->post['album_id']) && is_numeric($request->post['album_id']) && !empty($request->post['card_content']) )
		{
			$db = getDb();

			$date = $request->post['annotation-date'];
			$time = $request->post['annotation-time'];

			$datetimeStr = $date . 'T' . $time . ' PST';
			$timestamp = date('Y-m-d H:i:s', strtotime($datetimeStr));

			echo $timestamp;

			error_log( 'Creating album annotation ' . $request->post['album_id'] );

			$db->debug = true;

			$newAnnotation = array( 'albums_id' => $request->post['album_id'],
				'text' => $request->post['card_content'],
				'time' => $timestamp );

			$insertResult = $db->album_annotations()->insert( $newAnnotation );

			if( $insertResult != null )
			{
				error_log( 'Added Album Annotation' );

				header('Location: /' . $this->urlStub());
			}
			else
			{
				error_log( 'Failed to add Album Annotation' );

				header('Location: /' . $this->urlStub());
			}
		}
	}
}

?>