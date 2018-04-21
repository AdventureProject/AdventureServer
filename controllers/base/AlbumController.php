<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class AlbumController extends BaseController
{
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
		return 'Album';
	}
	
	public function getRichDescription()
	{
		return 'A photo album';
	}
	
	public function getBackUrl()
	{
		return '/albums';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/zoom.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/album.html');
		
		if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
        {
			$albumId = $request->args[0];
			
			$db = getDb();
				
			$album = $db->albums[$albumId];
			
			$xtpl->assign('ALBUM_TITLE', $album['title']);
			$xtpl->assign('ALBUM_DESCRIPTION', $album['description']);
			$xtpl->assign('ALBUM_DATE', $album['date']);
			
			$albumPhotoResults = $db->photos()->select('photos.id, photos.title, photos.orientation')->where('album_photos:albums_id', $albumId)->order('date_taken DESC');
			
			while( $photo = $albumPhotoResults->fetch() )
			{
				$xtpl->assign('PHOTO_ID', $photo['id']);
				$xtpl->assign('PHOTO_TITLE', $photo['title']);
				$xtpl->parse('main.body.photo');
				/*
				$xtpl->assign('PHOTO_ID',$data['id']);
				$xtpl->assign('PHOTO_THUMBNAIL', b2GetPublicThumbnailUrl($data['id']) );

				$style = NULL;
				if( $data['orientation'] == 'land' )
				{
					$style = 'mdl-cell--3-col pic-card-land';
				}
				else
				{
					$style = 'mdl-cell--2-col pic-card-port';
				}
				$xtpl->assign('PHOTO_ID',$data['id']);
				$xtpl->assign('PHOTO_TITLE',$data['title']);
				$xtpl->assign('PHOTO_STYLE',$style);

				$xtpl->parse('main.body.highlight_style');
				$xtpl->parse('main.body.highlight');
				*/
			}
		}
		
		$xtpl->parse('main.body');
	}
}

?>