<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');

class HomeController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'root';
	}

	public function getTitle()
	{
		return 'Welcome';
	}
	
	public function getRichTitle()
	{
		return 'We Think Adventure.Rocks';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/album_container.css', $xtpl );
		$this->addCssFile( '/css/albums.css', $xtpl );
		$this->addCssFile( '/css/home.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/home.html');
		
		$NUM_HIGHLIGHTS = 4;
		
		$db = getDb();
		$results = $db->photos()->select('id, title, orientation')->where('highlight', 1)->order('rand()')->limit( $NUM_HIGHLIGHTS );
		
		$ii = 0;
		while( $data = $results->fetch() )
		{
			++$ii;
			
			$xtpl->assign('PHOTO_ID',$data['id']);
			$xtpl->assign('PHOTO_THUMBNAIL', b2GetPublicThumbnailUrl($data['id']) );
			$xtpl->assign('PHOTO_TITLE',$data['title']);
			$xtpl->assign('PHOTO_URL', '/photo/' . $data['id']);
			
			if( $ii == $NUM_HIGHLIGHTS )
			{
				$xtpl->assign('PHOTO_HIDE_LAST', 'hide-on-mobile');
			}
			
			$xtpl->parse('main.body.highlight');
		}

		$latestBlogs = $db->blogs()->where('is_published', 1)->order('date_display DESC')->limit(3);
		if(count($latestBlogs) > 0)
		{
			while( $blog = $latestBlogs->fetch() )
			{
				$contentSummary = substr( $blog['content'], 0, strpos( $blog['content'], "\n" ) );

				$bodyText = getMarkdown()->parse( $contentSummary );

				$heroPhotoId = $blog['hero_photo_id'];
				$photo = getPhoto( $heroPhotoId, true, 1024, 1024 );

				$blogDate = new DateTime( utcToPst($blog['date_display']) );
				$blogDateStr = $blogDate->format('M d');

				$xtpl->assign( 'ENTRY_ID', $blog['id'] );
				$xtpl->assign( 'BLOG_DATE', $blogDateStr );
				$xtpl->assign( 'BLOG_TITLE', $blog['title'] );
				$xtpl->assign( 'BLOG_CONTENT', $bodyText );
				$xtpl->assign( 'BLOG_HERO_PHOTO_URL', $photo->image );
				$xtpl->parse( 'main.body.logs.log_entry' );
			}
			$xtpl->parse( 'main.body.logs' );
		}

		$results = $db->albums()->order('date DESC')->limit(7);
		while( $album = $results->fetch() )
		{
			$xtpl->assign('ALBUM_ID', $album['id']);
			$xtpl->assign('ALBUM_TITLE', $album['title']);
			$xtpl->assign('ALBUM_IMAGE_URL', b2GetPublicThumbnailUrl( $album['cover_photo_id'] ) );
			$xtpl->assign('ALBUM_URL', 'album/' . $album['id']);

			$xtpl->parse('main.body.album');
		}
		
		$xtpl->parse('main.body');
	}
}

?>