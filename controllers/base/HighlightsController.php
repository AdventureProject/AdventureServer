<<<<<<< HEAD
<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class HighlightsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}
	
	public function urlStub()
	{
		return 'highlights';
	}
	
	public function getTitle()
	{
		return 'Highlights';
	}
	
	public function getRichDescription()
	{
		return 'A selection of some of our best photos';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/album_container.css', $xtpl );
		$this->addCssFile( '/css/highlights.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );

		$this->addLazyLoadLibrary( $xtpl );

		$xtpl->assign_file('BODY_FILE', 'templates/highlights.html');
		
		$db = getDb();
		$results = $db->photos()->select('id, title, orientation')->where('highlight', 1)->order('date_taken DESC');
		
		while( $data = $results->fetch() )
		{
			$xtpl->assign('PHOTO_ID',$data['id']);
			$xtpl->assign('PHOTO_THUMBNAIL', b2GetPublicThumbnailUrl($data['id']) );
			$xtpl->assign('PHOTO_TITLE',$data['title']);
			
			$albumResult = $db->album_photos()->select('*')->where('photos_id', $data['id'])->limit(1)->fetch();
			if( $albumResult )
			{
				$albumId = $albumResult['albums_id'];
				$xtpl->assign('PHOTO_URL', '/photo/' . $data['id'] . '/album/'. $albumId);
			}
			else
			{
				$xtpl->assign('PHOTO_URL', '/photo/' . $data['id']);
			}
			
			if( $data['orientation'] == 'land' )
			{
				$xtpl->parse('main.body.highlight.photo_element_land');
			}
			else
			{
				$xtpl->parse('main.body.highlight.photo_element_port');
			}
			
			$xtpl->parse('main.body.highlight');
		}
		
		$db->close();
		
		$xtpl->parse('main.body');
	}
}

=======
<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class HighlightsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}
	
	public function urlStub()
	{
		return 'highlights';
	}
	
	public function getTitle()
	{
		return 'Highlights';
	}
	
	public function getRichDescription()
	{
		return 'A selection of some of our best photos';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/album_container.css', $xtpl );
		$this->addCssFile( '/css/highlights.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );

		$this->addLazyLoadLibrary( $xtpl );

		$xtpl->assign_file('BODY_FILE', 'templates/highlights.html');
		
		$db = getDb();
		$results = $db->photos()->select('id, title, orientation')->where('highlight', 1)->order('date_taken DESC');
		
		while( $data = $results->fetch() )
		{
			$xtpl->assign('PHOTO_ID',$data['id']);
			$xtpl->assign('PHOTO_THUMBNAIL', b2GetPublicThumbnailUrl($data['id']) );
			$xtpl->assign('PHOTO_TITLE',$data['title']);
			
			$albumResult = $db->album_photos()->select('*')->where('photos_id', $data['id'])->limit(1)->fetch();
			if( $albumResult )
			{
				$albumId = $albumResult['albums_id'];
				$xtpl->assign('PHOTO_URL', '/photo/' . $data['id'] . '/album/'. $albumId);
			}
			else
			{
				$xtpl->assign('PHOTO_URL', '/photo/' . $data['id']);
			}
			
			if( $data['orientation'] == 'land' )
			{
				$xtpl->parse('main.body.highlight.photo_element_land');
			}
			else
			{
				$xtpl->parse('main.body.highlight.photo_element_port');
			}
			
			$xtpl->parse('main.body.highlight');
		}
		
		$db->close();
		
		$xtpl->parse('main.body');
	}
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>