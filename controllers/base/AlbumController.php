<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class AlbumController extends BaseController
{
	private $currentAlbumId = null;
	private $currentAlbumData = null;
	
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}
	
	public function urlStub()
	{
		return 'album';
	}
	
	public function getTitle()
	{
		return 'Album' . ' - ' . $this->currentAlbumData['title'];
	}
	
	public function getRichTitle()
	{
		return $this->getTitle();
	}

	public function getRichDescription()
	{
		return $this->currentAlbumData['description'];
	}

	public function getRichImage()
	{
		return b2GetPublicThumbnailUrl( $this->currentAlbumData['cover_photo_id'] );
	}
	
	public function getBackUrl()
	{
		return '/albums';
	}

	public function provideBack()
	{
		return true;
	}
	
	public function get( $request )
	{
		$db = getDb();
		
		$this->currentAlbumId = $this->getAlbumId( $request );
		$this->currentAlbumData = $db->albums[$this->currentAlbumId];

		parent::get( $request );
	}

	private function getAlbumId( $request )
	{
		$albumId = null;
		if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
		{
			$albumId = $request->args[0];
		}

		return $albumId;
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/album.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/album.html');

		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
        {
			$db = getDb();

			$album = $db->albums[$albumId];
			$coverPhoto = getPhotoById( $album['cover_photo_id'], true, 1024, 768 );

			$xtpl->assign('ALBUM_TITLE', $album['title']);
			$xtpl->assign('ALBUM_DESCRIPTION', $album['description']);
			$albumDate = date("F j, Y", strtotime( $album['date'] ));
			$xtpl->assign('ALBUM_DATE', $albumDate );
			$xtpl->assign('ALBUM_PIC_URL', $coverPhoto->image );
			$xtpl->parse('main.body.album_header');
			
			$albumPhotoResults = $db->photos()->select('photos.id, photos.title, photos.orientation')->where('album_photos:albums_id', $albumId)->order('date_taken ASC');
			
			while( $photo = $albumPhotoResults->fetch() )
			{
				$xtpl->assign('PHOTO_ID', $photo['id']);
				$xtpl->assign('PHOTO_URL', '/photo/' . $photo['id'] . '/album/' . $albumId );
				$xtpl->assign('PHOTO_IMAGE_URL', b2GetPublicThumbnailUrl($photo['id']));
				$xtpl->assign('PHOTO_TITLE', $photo['title']);

				$xtpl->parse('main.body.photo_style');

				if( $photo['orientation'] == 'land' )
				{
					$xtpl->parse('main.body.photo.photo_element_land');
				}
				else
				{
					$xtpl->parse('main.body.photo.photo_element_port');
				}

				$xtpl->parse('main.body.photo');
			}
		}
		
		$xtpl->parse('main.body');
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		$request = $this->getRequest();
		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
		{
			$db = getDb();
			$album = $db->albums[$albumId];

			return b2GetPublicBlurUrl( $album['cover_photo_id'] );
		}
		else
		{
			return "";
		}
	}
}

?>