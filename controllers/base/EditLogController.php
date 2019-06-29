<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class EditLogController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'editlog';
	}

	public function getTitle()
	{
		return 'Edit Log';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file( 'BODY_FILE', 'templates/edit_log.html' );

		$this->addCssFile( 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', $xtpl );
		$this->addJsFile( 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', $xtpl );

		$this->addJsFile( '/js/edit_log.js', $xtpl );

		if( is_numeric( $request->args[0] ) )
		{
			$logId = $request->args[0];

			$db = getDb();
			$log = $db->blogs[ $logId ];

			if( $log )
			{
				$title = $log['title'];
				$content = $log['content'];
				$published = $log['is_published'];
				$heroPhotoId = $log['hero_photo_id'];
				if($heroPhotoId)
				{
					$heroPhotoUrl = b2GetPublicThumbnailUrl($heroPhotoId);
				}
				else
				{
					$heroPhotoUrl = "";
				}
				$displayDate = $log['date_display'];

				$dateTime = new DateTime($displayDate);
				$tz = new DateTimeZone("America/Los_Angeles");
				$dateTime->setTimezone($tz);

				$date = $dateTime->format('Y-m-j');
				$time = $dateTime->format('H:i');

				$xtpl->assign( 'BLOG_ID', $logId );
				$xtpl->assign( 'BLOG_TITLE', $title );
				$xtpl->assign( 'BLOG_CONTENT', $content );
				$xtpl->assign( 'BLOG_HERO_PHOTO_ID', $heroPhotoId );
				$xtpl->assign( 'HERO_PHOTO_URL', $heroPhotoUrl );
				$xtpl->assign( 'BLOG_DATE', $date );
				$xtpl->assign( 'BLOG_TIME', $time );

				$xtpl->assign( 'IS_PUBLISHED', $published == 1 ? 'checked' : '' );
			}
		}

		$xtpl->parse( 'main.body' );
	}

	public function post( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$logId = $request->args[0];

			$blogTitle = $request->post['blog_title'];
			$blogContent = $request->post['blog_content'];
			$blogPublished = ($request->post['is_published'] == 'on');

			$heroPhotoId = $request->post['blog_hero_photo_id'];

			$blogDate = $request->post['blog_date'];
			$blogTime = $request->post['blog_time'];

			$datetimeStr = $blogDate . 'T' . $blogTime . ' PST';
			$timestamp = date('Y-m-d H:i:s', strtotime($datetimeStr));

			$db = getDb();
			$row = $db->blogs[ $logId ];
			$row['title'] = $blogTitle;
			$row['content'] = $blogContent;
			$row['is_published'] = $blogPublished;
			$row['hero_photo_id'] = $heroPhotoId;
			$row['date_display'] = $timestamp;
			$row['date_updated'] = date("Y-m-d H:i:s",time());
			$updateResult = $row->update();

			if( $updateResult )
			{
				error_log( 'Blog updated' );

				header( 'Location: /' . $this->urlStub() . '/' . $logId );
			}
			else
			{
				error_log( 'Blog update FAILED' );
				echo 'Blog update FAILED';
			}
		}
	}
}

?>