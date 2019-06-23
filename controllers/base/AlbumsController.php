<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class AlbumsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}
	
	public function urlStub()
	{
		return 'albums';
	}
	
	public function getTitle()
	{
		return 'Albums';
	}
	
	public function getRichDescription()
	{
		return 'Photo Albums';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/albums.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );

		$this->addLazyLoadLibrary( $xtpl );

		$xtpl->assign_file('BODY_FILE', 'templates/albums.html');
		
		$db = getDb();
		$results = $db->albums()->order('date DESC');
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