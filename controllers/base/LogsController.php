<?php

require_once('utils/BaseController.php');
require_once('libs/Parsedown.php');
require_once('utils/b2_util.php');

class LogsController extends BaseController
{
	private $parser;

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$this->parser = new \cebe\markdown\MarkdownExtra();
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

		//$parsedown = new Parsedown();

		foreach( $db->blogs() as $entry )
		{
			$content = $entry['content'];
			$contentSummary = substr( $content, 0, strpos( $content, "\n" ) );

			//$bodyText = $parsedown->text( $contentSummary );
			$bodyText = $this->parser->parse($contentSummary);

			$coverPhotoId = $entry['cover_photo_id'];

			$xtpl->assign_file( 'BODY_FILE', 'templates/logs.html' );

			$xtpl->assign( 'ENTRY_ID', $entry['id'] );
			$xtpl->assign( 'BLOG_DATE', $entry['date_created'] );
			$xtpl->assign( 'BLOG_TITLE', $entry['title'] );
			$xtpl->assign( 'BLOG_CONTENT', $bodyText );

			$xtpl->assign( 'PHOTO_THUMBNAIL', b2GetPublicBlurUrl( $coverPhotoId ) );
			$xtpl->parse( 'main.body.log_entry' );
		}

		$xtpl->parse( 'main.body' );
	}
}

?>