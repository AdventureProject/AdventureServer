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