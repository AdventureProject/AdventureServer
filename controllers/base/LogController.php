<?php

require_once('utils/BaseController.php');
require_once('libs/Parsedown.php');
require_once('utils/b2_util.php');

class LogController extends BaseController
{
	private $parser;

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$this->parser = new \cebe\markdown\MarkdownExtra();
	}

	public function urlStub()
	{
		return 'log';
	}

	public function getTitle()
	{
		return 'Log';
	}

	public function getRichTitle()
	{
		return 'Adventure.Rocks - Log';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		//$this->addCssFile( '/css/about.css', $xtpl );

		$db = getDb();
		$blogPost = $db->blogs[1];

		$markdownText = $blogPost['content'];
		$bodyText = $bodyText = $this->parser->parse($markdownText);

		$coverPhotoId = $blogPost['cover_photo_id'];

		$xtpl->assign_file( 'BODY_FILE', 'templates/log.html' );

		$xtpl->assign( 'BLOG_DATE', $blogPost['date_created'] );
		$xtpl->assign( 'BLOG_TITLE', $blogPost['title'] );
		$xtpl->assign( 'BLOG_CONTENT', $bodyText );

		$xtpl->assign( 'PHOTO_THUMBNAIL', b2GetPublicBlurUrl( $coverPhotoId ) );

		$xtpl->parse( 'main.body' );
	}
}

?>