<?php

require_once('utils/BaseController.php');
require_once('utils/b2_util.php');
require_once('utils/MarkDown.php');

class LogsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}

	public function urlStub()
	{
		return 'logs';
	}

	public function getTitle()
	{
		return 'Logs';
	}

	public function getRichTitle()
	{
		return 'Adventure.Rocks - Logs';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$db = getDb();

		foreach( $db->blogs()->where('is_published', 1) as $entry )
		{
			$content = $entry['content'];
			$contentSummary = substr( $content, 0, strpos( $content, "\n" ) );

			$bodyText = getMarkdown()->parse($contentSummary);

			$heroPhotoId = $entry['hero_photo_id'];
			$photo = getPhoto( $heroPhotoId, true, 1024, 1024 );

			$xtpl->assign_file( 'BODY_FILE', 'templates/logs.html' );

			$xtpl->assign( 'ENTRY_ID', $entry['id'] );
			$xtpl->assign( 'BLOG_DATE', $entry['date_created'] );
			$xtpl->assign( 'BLOG_TITLE', $entry['title'] );
			$xtpl->assign( 'BLOG_CONTENT', $bodyText );
			$xtpl->assign( 'BLOG_HERO_PHOTO_URL', $photo->image );
			$xtpl->parse( 'main.body.log_entry' );
		}

		$xtpl->parse( 'main.body' );
	}
}

?>