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
		$xtpl->parse( 'main.body' );

		/*
		 * Hack for modifying all album annotation times
		 *
		$db = getDb();
		$db->debug = true;

		foreach( $db->album_annotations() as $item )
		{
			//$adjustedTime = date('Y-m-d H:i:s', strtotime($item["time"]) - 60 * 60 * 8);
			$adjustedTime = date('Y-m-d H:i:s', strtotime($item["time"]) + 60 * 60 * 5);
			//echo strtotime($item["time"]) . ' - ' . $adjustedTime . '<br />';
			$item["time"] = $adjustedTime;
			$item->update();
		}
		*/
	}

	public function post( $request )
	{
		if( !empty($request->post['album_id']) && is_numeric($request->post['album_id']) && !empty($request->post['card_content']) )
		{
			$db = getDb();

			$date = $request->post['annotation-date'];
			$time = $request->post['annotation-time'];

			date_default_timezone_set('UTC');
			$datetimeStr = $date . 'T' . $time . ' PST';
			$timestamp = date('Y-m-d H:i:s', strtotime($datetimeStr));

			error_log( 'Creating album annotation ' . $request->post['album_id'] );

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