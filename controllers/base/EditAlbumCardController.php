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

				if($annotation['type'] == 'text')
				{
					$xtpl->assign( 'ANNOTATION_TYPE_TEXT_CHECKED', 'checked' );
					$xtpl->assign( 'ANNOTATION_TYPE_PATH_CHECKED', '' );
				}
				else if($annotation['type'] == 'path')
				{
					$xtpl->assign( 'ANNOTATION_TYPE_TEXT_CHECKED', '' );
					$xtpl->assign( 'ANNOTATION_TYPE_PATH_CHECKED', 'checked' );

					$pstPathStartDate = utcToPst( $annotation['path_start'], 'Y-m-d' );
					$pstPathStartTime = utcToPst( $annotation['path_start'], 'H:i:s' );
					$xtpl->assign( 'ANNOTATION_PATH_START_DATE', $pstPathStartDate );
					$xtpl->assign( 'ANNOTATION_PATH_START_TIME', $pstPathStartTime );

					$pstPathEndDate = utcToPst( $annotation['path_end'], 'Y-m-d' );
					$pstPathEndTime = utcToPst( $annotation['path_end'], 'H:i:s' );
					$xtpl->assign( 'ANNOTATION_PATH_END_DATE', $pstPathEndDate );
					$xtpl->assign( 'ANNOTATION_PATH_END_TIME', $pstPathEndTime );
				}

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

			$type = $request->post['type'];

			$pathStartTimestamp = null;
			$pathEndTimestamp = null;
			if($type == 'path')
			{
				$pathStartDate = $request->post['annotation-path-start-date'];
				$pathStartTime = $request->post['annotation-path-start-time'];
				$pathStartStr = $pathStartDate . 'T' . $pathStartTime;
				$pathStartTimestamp = pstToUtc( $pathStartStr );

				$pathEndDate = $request->post['annotation-path-end-date'];
				$pathEndTime = $request->post['annotation-path-end-time'];
				$pathEndStr = $pathEndDate . 'T' . $pathEndTime;
				$pathEndTimestamp = pstToUtc( $pathEndStr );
			}

			error_log( 'Updating album annotation ' . $annotationId );

			$annotation = $db->album_annotations[ $annotationId ];
			if( $annotation )
			{

				$annotation['type'] = $type;
				$annotation['time'] = $timestamp;
				$annotation['text'] = $request->post['card_content'];
				$annotation['time'] = $timestamp;
				$annotation['path_start'] = $pathStartTimestamp;
				$annotation['path_end'] = $pathEndTimestamp;
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