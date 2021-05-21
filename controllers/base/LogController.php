<<<<<<< HEAD
<?php

require_once('utils/BaseController.php');
require_once('utils/b2_util.php');
require_once('utils/MarkDown.php');

class LogController extends BaseController
{
	private $blogPost = null;
	private $heroPhoto = null;

	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}

	public function urlStub()
	{
		return 'log';
	}

	public function getTitle()
	{
		return 'Log - ' . $this->blogPost['title'];
	}

	public function getRichTitle()
	{
		return $this->blogPost['title'];
	}

	public function getRichDescription()
	{
		return 'Adventure.Rocks - Blog';
	}

	public function getRichImage()
	{
		return $this->heroPhoto->thumbnail;
	}

	public function provideBack()
	{
		return true;
	}

	public function getBackUrl()
	{
		return '/logs';
	}

	public function get( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$db = getDb();

			$blogId = $request->args[0];
			$tempBlog = $db->blogs[ $blogId ];

			if( $this->isAuthenticated() || $tempBlog['is_published'] == 1 || $request->params['hack'] == 1 )
			{
				$this->blogPost = $tempBlog;
				$heroPhotoId = $this->blogPost['hero_photo_id'];

				$this->heroPhoto = getPhoto( $heroPhotoId, true, 1024, 1024 );
			}
			else
			{
				exit();
			}
		}

		parent::get( $request );
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile('/css/log.css', $xtpl);

		$markdownText = $this->blogPost['content'];

		$bodyText = getMarkdown()->parse($markdownText);
		$postDate = $this->formatDateForDisplay($this->blogPost['date_display'], 'M d, Y');

		$xtpl->assign_file( 'BODY_FILE', 'templates/log.html' );

		$xtpl->assign( 'BLOG_DATE', $postDate );
		$xtpl->assign( 'BLOG_TITLE', $this->blogPost['title'] );
		$xtpl->assign( 'BLOG_HERO_PHOTO_URL', $this->heroPhoto->image );
		$xtpl->assign( 'BLOG_CONTENT', $bodyText );

		$xtpl->parse( 'main.body' );
	}
}

=======
<?php

require_once('utils/BaseController.php');
require_once('utils/b2_util.php');
require_once('utils/MarkDown.php');

class LogController extends BaseController
{
	private $blogPost = null;
	private $heroPhoto = null;

	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}

	public function urlStub()
	{
		return 'log';
	}

	public function getTitle()
	{
		return 'Log - ' . $this->blogPost['title'];
	}

	public function getRichTitle()
	{
		return $this->blogPost['title'];
	}

	public function getRichDescription()
	{
		return 'Adventure.Rocks - Blog';
	}

	public function getRichImage()
	{
		return $this->heroPhoto->thumbnail;
	}

	public function provideBack()
	{
		return true;
	}

	public function getBackUrl()
	{
		return '/logs';
	}

	public function get( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$db = getDb();

			$blogId = $request->args[0];
			$tempBlog = $db->blogs[ $blogId ];

			if( $this->isAuthenticated() || $tempBlog['is_published'] == 1 || $request->params['hack'] == 1 )
			{
				$this->blogPost = $tempBlog;
				$heroPhotoId = $this->blogPost['hero_photo_id'];

				$this->heroPhoto = getPhoto( $heroPhotoId, true, 1024, 1024 );
			}
			else
			{
				exit();
			}
		}

		parent::get( $request );
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile('/css/log.css', $xtpl);

		$markdownText = $this->blogPost['content'];

		$bodyText = getMarkdown()->parse($markdownText);
		$postDate = $this->formatDateForDisplay($this->blogPost['date_display'], 'M d, Y');

		$xtpl->assign_file( 'BODY_FILE', 'templates/log.html' );

		$xtpl->assign( 'BLOG_DATE', $postDate );
		$xtpl->assign( 'BLOG_TITLE', $this->blogPost['title'] );
		$xtpl->assign( 'BLOG_HERO_PHOTO_URL', $this->heroPhoto->image );
		$xtpl->assign( 'BLOG_CONTENT', $bodyText );

		$xtpl->parse( 'main.body' );
	}
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>