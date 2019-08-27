<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class EditAlbumCardController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'editalbumcard';
	}

	public function getTitle()
	{
		return 'Edit Album Card';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file( 'BODY_FILE', 'templates/edit_album_card.html' );

		if( count( $request->args ) == 1 && is_numeric( $request->args[0] ) )
		{
			$annotationId = $request->args[0];

			$db = getDb();
			$annotation = $db->album_annotations[ $annotationId ];
			$albumId = $annotation['albums_id'];

			if( $request->params['delete'] == 1 )
			{
				$annotation->delete();

				header( "Location: /album/$albumId" );
			}
			else
			{
				$annotation = $db->album_annotations[ $annotationId ];

				$pstDate = utcToPst( $annotation['time'], 'Y-m-d' );
				$pstTime = utcToPst( $annotation['time'], 'H:i:s' );

				$xtpl->assign( 'ALBUM_ID', $albumId );
				$xtpl->assign( 'ANNOTATION_ID', $annotationId );
				$xtpl->assign( 'ANNOTATION_DATE', $pstDate );
				$xtpl->assign( 'ANNOTATION_TIME', $pstTime );
				$xtpl->assign( 'ANNOTATION_CONTENT', $annotation['text'] );

				$xtpl->parse( 'main.body' );
			}
			$db->close();
		}
	}

	public function post( $request )
	{
		if( count( $request->args ) == 1
			&& is_numeric( $request->args[0] )
			&& !empty( $request->post['card_content'] ) )
		{
			$annotationId = $request->args[0];

			$db = getDb();

			$date = $request->post['annotation-date'];
			$time = $request->post['annotation-time'];

			$datetimeStr = $date . 'T' . $time;
			$timestamp = pstToUtc( $datetimeStr );

			error_log( 'Updating album annotation ' . $annotationId );

			$annotation = $db->album_annotations[ $annotationId ];
			if( $annotation )
			{
				$annotation['text'] = $request->post['card_content'];
				$annotation['time'] = $timestamp;
				$annotation->update();

				$albumId = $annotation['albums_id'];

				error_log( 'Added Album Annotation' );

				header( "Location: /album/$albumId" );
			}
			else
			{
				error_log( 'Failed to add Album Annotation' );

				header( 'Location: /' . $this->urlStub() );
			}
		}
	}
}

?>