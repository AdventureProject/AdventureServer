<<<<<<< HEAD
<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/maps.php');

class PhotoController extends BaseController
{
	private $googleMapsApiKey;
	private $googleMapsApiSecret;
	private $currentPhoto = null;
	private $albumId = null;
	private $albumData = null;

	private $ALBUM_STUB = 'album';

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$keys = getKeys();

		$this->googleMapsApiKey = $keys->google_maps_api->key;
		$this->googleMapsApiSecret = $keys->google_maps_api->secret;
	}

	public function urlStub()
	{
		return 'photo';
	}

	public function getTitle()
	{
		return 'Photo';
	}

	public function provideBack()
	{
		return ($this->albumId != null);
	}

	public function get( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$photoId = $request->args[0];

			$photoFlickr = getPhoto( $photoId, true, 1024, 1024 );

			$this->currentPhoto = $photoFlickr;

			$this->albumId = $this->getAlbumId( $request );

			if( $this->albumId != null )
			{
				$db = getDb();
				$this->albumData = $db->albums[ $this->albumId ];
			}
		}

		parent::get( $request );
	}

	public function getBackUrl()
	{
		if( $this->albumId != null )
		{
			return '/album/' . $this->albumId;
		}
		else
		{
			return '/highlights';
		}
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		if( $this->albumId != null )
		{
			$photoId = $request->args[0];
			$albumSlug = $request->args[1];

			if( $albumSlug == $this->ALBUM_STUB )
			{
				$this->renderPhoto( $xtpl, $photoId, $this->albumId );
			}
		}
		else if( is_numeric( $request->args[0] ) )
		{
			$photoId = $request->args[0];

			$this->addNavAction( 'random', 'shuffle', 'Random', '/photo/random', $xtpl );

			if( $this->isAuthenticated() && array_key_exists( 'regenerate', $request->params ) )
			{
				error_log( 'regenerate: ' . $request->params['regenerate'] );

				if( $request->params['regenerate'] == 'thumbnail' )
				{
					error_log( 'regenerate thumbnail ' . $photoId );

					transferThumbnailFromFlickrToB2( $photoId, true );
				}
				else if( $request->params['regenerate'] == 'info' )
				{
					error_log( 'regenerate info ' . $photoId );

					$this->refreshInfoFromFlickr( $photoId );
				}
				else if( $request->params['regenerate'] == 'all' )
				{
					deleteResized( $photoId );

					$importTaskId = createReimportTask( $photoId );
					processImportTask( $importTaskId );

					transferThumbnailFromFlickrToB2( $photoId, true );
				}
				else if( $request->params['regenerate'] == 'resized' )
				{
					deleteResized( $photoId );
				}

				header( 'Location: /photo/' . $request->args[0] );
			}
			else if( $this->isAuthenticated() && array_key_exists( 'delete', $request->params ) )
			{
				if( $request->params['delete'] == 1 )
				{
					error_log( 'Deleting photo: ' . $photoId );

					deletePhoto( $photoId );

					header( 'Location: /admin' );
				}
			}
			// Default normal photo request
			else
			{
				$this->renderPhoto( $xtpl, $request->args[0] );
			}
		}
		else if( $request->args[0] == 'random' )
		{
			$photoIds = getRandomPhoto();

			header( 'Location: /photo/' . $photoIds['id'] );
			exit();
		}
		else
		{
			$this->addCssFile( '/css/not_found.css', $xtpl );
			$xtpl->assign_file( 'BODY_FILE', 'templates/photo_not_found.html' );
		}

		$xtpl->parse( 'main.body' );
	}

	private function getAlbumId( $request )
	{
		if( count( $request->args ) >= 3
			&& is_numeric( $request->args[0] )
			&& $request->args[1] == $this->ALBUM_STUB
			&& is_numeric( $request->args[2] ) )
		{
			return $request->args[2];
		}
		else
		{
			return null;
		}
	}

	public function getRichTitle()
	{
		return $this->currentPhoto->title;
	}

	public function getRichDescription()
	{
		return $this->currentPhoto->description;
	}

	public function getRichImage()
	{
		return $this->currentPhoto->thumbnail;
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		if( $this->albumId != null )
		{
			return b2GetPublicBlurUrl( $this->albumData['cover_photo_id'] );
		}
		else
		{
			return parent::getBlurredBackgroundPhotoUrl( $todaysPhoto );
		}
	}

	private function renderPhoto( $xtpl, $photoId, $albumId = null )
	{
		$db = getDb();

		$photoData = $db->photos[ $photoId ];
		if( $photoData != null )
		{
			$this->addCssFile( '/external/magnific-popup/magnific-popup.css', $xtpl );
			$this->addJsFile( '/external/magnific-popup/jquery.magnific-popup.min.js', $xtpl );

			$this->addCssFile( '/css/photo.css', $xtpl );
			$this->addJsFile( '/js/photo.js', $xtpl );

			$xtpl->assign_file( 'BODY_FILE', 'templates/photo.html' );

			$photoFlickr = getPhoto( $photoId, true, 1024, 1024 );

			$locationParts = explode( ',', $photoFlickr->location );

			$this->addSeoLocation( $locationParts[0], $locationParts[1], $xtpl );

			/*
			$tz = get_nearest_timezone($locationParts[0], $locationParts[1], "us");
			//date_default_timezone_set($tz);
			date_default_timezone_set("UTC");
			*/

			if( $this->albumData != null )
			{
				$dateTaken = $photoData['date_taken'];
				$sql = "SELECT 
							( SELECT photos.id FROM photos, album_photos
								WHERE photos.id = album_photos.photos_id AND album_photos.albums_id = '$albumId' AND photos.date_taken > '$dateTaken' LIMIT 1 ) AS nextId,
							( SELECT photos.id FROM photos, album_photos
								WHERE photos.id = album_photos.photos_id AND album_photos.albums_id = '$albumId' AND photos.date_taken < '$dateTaken' ORDER BY id DESC LIMIT 1 ) AS prevId
							LIMIT 1";

				$pdo = getDbPdo();
				$sequenceResult = $pdo->query( $sql )->fetch();
				$PDO = null;

				$nextId = $sequenceResult['nextId'];
				$prevId = $sequenceResult['prevId'];

				if( $prevId != null )
				{
					$url = '/photo/' . $prevId . '/album/' . $albumId;

					$xtpl->assign( 'HAS_PREV_PHOTO', 'true' );
					$xtpl->assign( 'PREV_PHOTO_URL', $url );

					$this->addNavAction( 'previous', 'keyboard_arrow_left', 'Previous', $url, $xtpl );
				}
				else
				{
					$xtpl->assign( 'HAS_PREV_PHOTO', 'false' );
				}

				if( $nextId != null )
				{
					$url = '/photo/' . $nextId . '/album/' . $albumId;

					$xtpl->assign( 'HAS_NEXT_PHOTO', 'true' );
					$xtpl->assign( 'NEXT_PHOTO_URL', $url );

					$this->addNavAction( 'next', 'keyboard_arrow_right', 'Next', $url, $xtpl );
				}
				else
				{
					$xtpl->assign( 'HAS_NEXT_PHOTO', 'false' );
				}

				$xtpl->parse( 'main.body.photo_nav_js_controls' );
			}

			$request = $this->getRequest();
			if( end( $request->args ) == 'lightbox' )
			{
				$xtpl->parse( 'main.body.start_zoomed' );
			}

			$xtpl->assign( 'PHOTO_ID', $photoId );
			$xtpl->assign( 'FLICKR_ID', $photoData['flickr_id'] );
			$xtpl->assign( 'PHOTO_TITLE', $photoFlickr->title );
			$dateStr = $this->formatDateForDisplay( $photoFlickr->date );
			$xtpl->assign( 'PHOTO_DATE', $dateStr );

			$xtpl->assign( 'THUMBNAIL_URL', b2GetPublicThumbnailUrl( $photoId ) );

			if( empty( $photoFlickr->location ) || $photoFlickr->location == ',' )
			{
				if( $this->isAuthenticated() )
				{
					$xtpl->assign( 'PHOTO_LOCATION', "<a target=\"_blank\" href=\"https://www.flickr.com/photos/organize/?batch_geotag=1&ids={$photoData['flickr_id']}&from_geo_ids={$photoData['flickr_id']}\">add geo data</a>" );
				}
				else
				{
					$xtpl->assign( 'PHOTO_LOCATION', '<em>No location data</em>' );
				}

				$xtpl->assign( 'MAP_ZOOMED_OUT', '/images/no_location_out.jpg' );
				$xtpl->assign( 'MAP_ZOOMED_IN', '/images/no_location_in.jpg' );
			}
			else
			{
				$xtpl->assign( 'PHOTO_LOCATION', $photoFlickr->location );

				$xtpl->assign( 'MAP_ZOOMED_OUT', $this->getZoomedOutMapUrl( $photoFlickr->location ) );
				$xtpl->assign( 'MAP_ZOOMED_IN', $this->getZoomedInMapUrl( $photoFlickr->location ) );
			}

			if( $photoFlickr->description != null && !empty( $photoFlickr->description ) )
			{
				$xtpl->assign( 'PHOTO_DESCRIPTION', $photoFlickr->description );
				$xtpl->parse( 'main.body.description' );
			}
			
			// Show what albums this photo is a part of
			$albums = $db->album_photos('photos_id = ?', $photoId);
			if( $albums )
			{
				foreach( $albums as $albumInfo )
				{
					$album = $db->albums[ $albumInfo['albums_id'] ];
					if( $album )
					{
						$xtpl->assign( 'ALBUM_ID', $album['id'] );
						$xtpl->assign( 'ALBUM_TITLE', $album['title'] );
						$xtpl->parse( 'main.body.album' );
					}
				}
			}

			$xtpl->assign( 'FLICKR_IMG', $photoFlickr->image );

			$xtpl->assign( 'IS_WALLPAPER', $photoData['wallpaper'] == 1 ? 'checked' : '' );
			$xtpl->assign( 'IS_HIGHLIGHT', $photoData['highlight'] == 1 ? 'checked' : '' );
			$xtpl->assign( 'IS_PHOTOFRAME', $photoData['photoframe'] == 1 ? 'checked' : '' );

			if( $this->isAuthenticated() )
			{
				if( !empty( $photoData['photowall_id'] ) )
				{
					$photoWallId = $photoData['photowall_id'];
					$xtpl->assign( 'PHOTOWALL_ID', $photoData['photowall_id'] );
				}
				else
				{
					$xtpl->assign( 'NEXT_PHOTOWALL_ID', $db->photos()->max( 'photowall_id' ) + 1 );
					$xtpl->assign( 'PHOTOWALL_ID', '<em>not on the wall</em>' );

					if( $this->isAuthenticated() )
					{
						$xtpl->parse( 'main.body.admin_links.add_photowall' );
					}
				}

				$xtpl->parse( 'main.body.admin_links.photo_actions' );

				$metaFiles = listMetaFiles( $photoId );
				if( $metaFiles && count( $metaFiles ) > 0 )
				{
					foreach( $metaFiles as $fileName )
					{
						$xtpl->assign( 'META_FILE_NAME', $fileName );
						$xtpl->assign( 'META_FILE_URL', b2GetPublicMetaUrl( $photoId, $fileName ) );
						$xtpl->parse( 'main.body.admin_links.meta_file' );
					}
				}

				$xtpl->parse( 'main.body.admin_links' );
			}
		}
		else
		{
			$this->addCssFile( '/css/not_found.css', $xtpl );
			$xtpl->assign_file( 'BODY_FILE', 'templates/photo_not_found.html' );
		}
	}

	public function post( $request )
	{
		if( $this->enforceAuth() )
		{
			if( count( $request->args ) >= 1 && is_numeric( $request->args[0] ) )
			{
				$photoId = $request->args[0];

				$db = getDb();
				$photoRow = $db->photos[ $photoId ];
				if( $photoRow )
				{
					$success = false;

					if( isset( $request->post['add_to_photowall'] ) )
					{
						if( empty( $photoRow['photowall_id'] ) )
						{
							$photoRow['photowall_id'] = $db->photos()->max( 'photowall_id' ) + 1;
							$success = $photoRow->update();
						}
						else
						{
							echo 'Photo is already on Photowall with ID: ' . $photoRow['photowall_id'];
							$success = false;
						}
					}
					else
					{
						$photoRow['wallpaper'] = isset( $request->post['is_wallpaper'] ) ? 1 : 0;
						$photoRow['photoframe'] = isset( $request->post['is_photoframe'] ) ? 1 : 0;
						$photoRow['highlight'] = isset( $request->post['is_highlight'] ) ? 1 : 0;

						$success = $photoRow->update();
					}

					if( $success == 0 || $success == 1 )
					{
						if( $photoRow['wallpaper'] == 1 )
						{
							addBlurMeta( $photoId );
						}

						header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
					}
					else
					{
						echo 'Error updating Database';
					}
				}
				else
				{
					echo 'Could not find photo by ID';
				}
			}
		}
		else
		{
			if( count( $request->args ) == 1 && is_numeric( $request->args[0] ) )
			{
				$photoId = $request->args[0];
			}
			else
			{
				echo 'No photo id provided';
			}
		}
	}

	private function getZoomedOutMapUrl( $location )
	{
		$url = "/maps/api/staticmap?center=$location&zoom=6&scale=1&size=700x400&maptype=terrain&key=$this->googleMapsApiKey&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%%7Clabel:%7C$location";
			
		return buildAndSignMapUrl( $url, $this->googleMapsApiSecret );
	}

	private function getZoomedInMapUrl( $location )
	{
		$url = "/maps/api/staticmap?center=$location&zoom=15&scale=1&size=800x800&maptype=terrain&key=$this->googleMapsApiKey&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C$location";
		
		return buildAndSignMapUrl( $url, $this->googleMapsApiSecret );
	}

	private function refreshInfoFromFlickr( $id )
	{
		$db = getDb();
		$flickrId = $db->photos[ $id ]['flickr_id'];

		updatePhotoInfoFromFlickr( $id, $flickrId, $db );
	}
=======
<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/maps.php');

class PhotoController extends BaseController
{
	private $googleMapsApiKey;
	private $googleMapsApiSecret;
	private $currentPhoto = null;
	private $albumId = null;
	private $albumData = null;

	private $ALBUM_STUB = 'album';

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$keys = getKeys();

		$this->googleMapsApiKey = $keys->google_maps_api->key;
		$this->googleMapsApiSecret = $keys->google_maps_api->secret;
	}

	public function urlStub()
	{
		return 'photo';
	}

	public function getTitle()
	{
		return 'Photo';
	}

	public function provideBack()
	{
		return ($this->albumId != null);
	}

	public function get( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$photoId = $request->args[0];

			$photoFlickr = getPhoto( $photoId, true, 1024, 1024 );

			$this->currentPhoto = $photoFlickr;

			$this->albumId = $this->getAlbumId( $request );

			if( $this->albumId != null )
			{
				$db = getDb();
				$this->albumData = $db->albums[ $this->albumId ];
			}
		}

		parent::get( $request );
	}

	public function getBackUrl()
	{
		if( $this->albumId != null )
		{
			return '/album/' . $this->albumId;
		}
		else
		{
			return '/highlights';
		}
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		if( $this->albumId != null )
		{
			$photoId = $request->args[0];
			$albumSlug = $request->args[1];

			if( $albumSlug == $this->ALBUM_STUB )
			{
				$this->renderPhoto( $xtpl, $photoId, $this->albumId );
			}
		}
		else if( is_numeric( $request->args[0] ) )
		{
			$photoId = $request->args[0];

			$this->addNavAction( 'random', 'shuffle', 'Random', '/photo/random', $xtpl );

			if( $this->isAuthenticated() && array_key_exists( 'regenerate', $request->params ) )
			{
				error_log( 'regenerate: ' . $request->params['regenerate'] );

				if( $request->params['regenerate'] == 'thumbnail' )
				{
					error_log( 'regenerate thumbnail ' . $photoId );

					transferThumbnailFromFlickrToB2( $photoId, true );
				}
				else if( $request->params['regenerate'] == 'info' )
				{
					error_log( 'regenerate info ' . $photoId );

					$this->refreshInfoFromFlickr( $photoId );
				}
				else if( $request->params['regenerate'] == 'all' )
				{
					deleteResized( $photoId );

					$importTaskId = createReimportTask( $photoId );
					processImportTask( $importTaskId );

					transferThumbnailFromFlickrToB2( $photoId, true );
				}
				else if( $request->params['regenerate'] == 'resized' )
				{
					deleteResized( $photoId );
				}

				header( 'Location: /photo/' . $request->args[0] );
			}
			else if( $this->isAuthenticated() && array_key_exists( 'delete', $request->params ) )
			{
				if( $request->params['delete'] == 1 )
				{
					error_log( 'Deleting photo: ' . $photoId );

					deletePhoto( $photoId );

					header( 'Location: /admin' );
				}
			}
			// Default normal photo request
			else
			{
				$this->renderPhoto( $xtpl, $request->args[0] );
			}
		}
		else if( $request->args[0] == 'random' )
		{
			$photoIds = getRandomPhoto();

			header( 'Location: /photo/' . $photoIds['id'] );
			exit();
		}
		else
		{
			$this->addCssFile( '/css/not_found.css', $xtpl );
			$xtpl->assign_file( 'BODY_FILE', 'templates/photo_not_found.html' );
		}

		$xtpl->parse( 'main.body' );
	}

	private function getAlbumId( $request )
	{
		if( count( $request->args ) >= 3
			&& is_numeric( $request->args[0] )
			&& $request->args[1] == $this->ALBUM_STUB
			&& is_numeric( $request->args[2] ) )
		{
			return $request->args[2];
		}
		else
		{
			return null;
		}
	}

	public function getRichTitle()
	{
		return $this->currentPhoto->title;
	}

	public function getRichDescription()
	{
		return $this->currentPhoto->description;
	}

	public function getRichImage()
	{
		return $this->currentPhoto->thumbnail;
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		if( $this->albumId != null )
		{
			return b2GetPublicBlurUrl( $this->albumData['cover_photo_id'] );
		}
		else
		{
			return parent::getBlurredBackgroundPhotoUrl( $todaysPhoto );
		}
	}

	private function renderPhoto( $xtpl, $photoId, $albumId = null )
	{
		$db = getDb();

		$photoData = $db->photos[ $photoId ];
		if( $photoData != null )
		{
			$this->addCssFile( '/external/magnific-popup/magnific-popup.css', $xtpl );
			$this->addJsFile( '/external/magnific-popup/jquery.magnific-popup.min.js', $xtpl );

			$this->addCssFile( '/css/photo.css', $xtpl );
			$this->addJsFile( '/js/photo.js', $xtpl );

			$xtpl->assign_file( 'BODY_FILE', 'templates/photo.html' );

			$photoFlickr = getPhoto( $photoId, true, 1024, 1024 );

			$locationParts = explode( ',', $photoFlickr->location );

			$this->addSeoLocation( $locationParts[0], $locationParts[1], $xtpl );

			/*
			$tz = get_nearest_timezone($locationParts[0], $locationParts[1], "us");
			//date_default_timezone_set($tz);
			date_default_timezone_set("UTC");
			*/

			if( $this->albumData != null )
			{
				$dateTaken = $photoData['date_taken'];
				$sql = "SELECT 
							( SELECT photos.id FROM photos, album_photos
								WHERE photos.id = album_photos.photos_id AND album_photos.albums_id = '$albumId' AND photos.date_taken > '$dateTaken' LIMIT 1 ) AS nextId,
							( SELECT photos.id FROM photos, album_photos
								WHERE photos.id = album_photos.photos_id AND album_photos.albums_id = '$albumId' AND photos.date_taken < '$dateTaken' ORDER BY id DESC LIMIT 1 ) AS prevId
							LIMIT 1";

				$pdo = getDbPdo();
				$sequenceResult = $pdo->query( $sql )->fetch();
				$PDO = null;

				$nextId = $sequenceResult['nextId'];
				$prevId = $sequenceResult['prevId'];

				if( $prevId != null )
				{
					$url = '/photo/' . $prevId . '/album/' . $albumId;

					$xtpl->assign( 'HAS_PREV_PHOTO', 'true' );
					$xtpl->assign( 'PREV_PHOTO_URL', $url );

					$this->addNavAction( 'previous', 'keyboard_arrow_left', 'Previous', $url, $xtpl );
				}
				else
				{
					$xtpl->assign( 'HAS_PREV_PHOTO', 'false' );
				}

				if( $nextId != null )
				{
					$url = '/photo/' . $nextId . '/album/' . $albumId;

					$xtpl->assign( 'HAS_NEXT_PHOTO', 'true' );
					$xtpl->assign( 'NEXT_PHOTO_URL', $url );

					$this->addNavAction( 'next', 'keyboard_arrow_right', 'Next', $url, $xtpl );
				}
				else
				{
					$xtpl->assign( 'HAS_NEXT_PHOTO', 'false' );
				}

				$xtpl->parse( 'main.body.photo_nav_js_controls' );
			}

			$request = $this->getRequest();
			if( end( $request->args ) == 'lightbox' )
			{
				$xtpl->parse( 'main.body.start_zoomed' );
			}

			$xtpl->assign( 'PHOTO_ID', $photoId );
			$xtpl->assign( 'FLICKR_ID', $photoData['flickr_id'] );
			$xtpl->assign( 'PHOTO_TITLE', $photoFlickr->title );
			$dateStr = $this->formatDateForDisplay( $photoFlickr->date );
			$xtpl->assign( 'PHOTO_DATE', $dateStr );

			$xtpl->assign( 'THUMBNAIL_URL', b2GetPublicThumbnailUrl( $photoId ) );

			if( empty( $photoFlickr->location ) || $photoFlickr->location == ',' )
			{
				if( $this->isAuthenticated() )
				{
					$xtpl->assign( 'PHOTO_LOCATION', "<a target=\"_blank\" href=\"https://www.flickr.com/photos/organize/?batch_geotag=1&ids={$photoData['flickr_id']}&from_geo_ids={$photoData['flickr_id']}\">add geo data</a>" );
				}
				else
				{
					$xtpl->assign( 'PHOTO_LOCATION', '<em>No location data</em>' );
				}

				$xtpl->assign( 'MAP_ZOOMED_OUT', '/images/no_location_out.jpg' );
				$xtpl->assign( 'MAP_ZOOMED_IN', '/images/no_location_in.jpg' );
			}
			else
			{
				$xtpl->assign( 'PHOTO_LOCATION', $photoFlickr->location );

				$xtpl->assign( 'MAP_ZOOMED_OUT', $this->getZoomedOutMapUrl( $photoFlickr->location ) );
				$xtpl->assign( 'MAP_ZOOMED_IN', $this->getZoomedInMapUrl( $photoFlickr->location ) );
			}

			if( $photoFlickr->description != null && !empty( $photoFlickr->description ) )
			{
				$xtpl->assign( 'PHOTO_DESCRIPTION', $photoFlickr->description );
				$xtpl->parse( 'main.body.description' );
			}
			
			// Show what albums this photo is a part of
			$albums = $db->album_photos('photos_id = ?', $photoId);
			if( $albums )
			{
				foreach( $albums as $albumInfo )
				{
					$album = $db->albums[ $albumInfo['albums_id'] ];
					if( $album )
					{
						$xtpl->assign( 'ALBUM_ID', $album['id'] );
						$xtpl->assign( 'ALBUM_TITLE', $album['title'] );
						$xtpl->parse( 'main.body.album' );
					}
				}
			}

			$xtpl->assign( 'FLICKR_IMG', $photoFlickr->image );

			$xtpl->assign( 'IS_WALLPAPER', $photoData['wallpaper'] == 1 ? 'checked' : '' );
			$xtpl->assign( 'IS_HIGHLIGHT', $photoData['highlight'] == 1 ? 'checked' : '' );
			$xtpl->assign( 'IS_PHOTOFRAME', $photoData['photoframe'] == 1 ? 'checked' : '' );

			if( $this->isAuthenticated() )
			{
				if( !empty( $photoData['photowall_id'] ) )
				{
					$photoWallId = $photoData['photowall_id'];
					$xtpl->assign( 'PHOTOWALL_ID', $photoData['photowall_id'] );
				}
				else
				{
					$xtpl->assign( 'NEXT_PHOTOWALL_ID', $db->photos()->max( 'photowall_id' ) + 1 );
					$xtpl->assign( 'PHOTOWALL_ID', '<em>not on the wall</em>' );

					if( $this->isAuthenticated() )
					{
						$xtpl->parse( 'main.body.admin_links.add_photowall' );
					}
				}

				$xtpl->parse( 'main.body.admin_links.photo_actions' );

				$metaFiles = listMetaFiles( $photoId );
				if( $metaFiles && count( $metaFiles ) > 0 )
				{
					foreach( $metaFiles as $fileName )
					{
						$xtpl->assign( 'META_FILE_NAME', $fileName );
						$xtpl->assign( 'META_FILE_URL', b2GetPublicMetaUrl( $photoId, $fileName ) );
						$xtpl->parse( 'main.body.admin_links.meta_file' );
					}
				}

				$xtpl->parse( 'main.body.admin_links' );
			}
		}
		else
		{
			$this->addCssFile( '/css/not_found.css', $xtpl );
			$xtpl->assign_file( 'BODY_FILE', 'templates/photo_not_found.html' );
		}
	}

	public function post( $request )
	{
		if( $this->enforceAuth() )
		{
			if( count( $request->args ) >= 1 && is_numeric( $request->args[0] ) )
			{
				$photoId = $request->args[0];

				$db = getDb();
				$photoRow = $db->photos[ $photoId ];
				if( $photoRow )
				{
					$success = false;

					if( isset( $request->post['add_to_photowall'] ) )
					{
						if( empty( $photoRow['photowall_id'] ) )
						{
							$photoRow['photowall_id'] = $db->photos()->max( 'photowall_id' ) + 1;
							$success = $photoRow->update();
						}
						else
						{
							echo 'Photo is already on Photowall with ID: ' . $photoRow['photowall_id'];
							$success = false;
						}
					}
					else
					{
						$photoRow['wallpaper'] = isset( $request->post['is_wallpaper'] ) ? 1 : 0;
						$photoRow['photoframe'] = isset( $request->post['is_photoframe'] ) ? 1 : 0;
						$photoRow['highlight'] = isset( $request->post['is_highlight'] ) ? 1 : 0;

						$success = $photoRow->update();
					}

					if( $success == 0 || $success == 1 )
					{
						if( $photoRow['wallpaper'] == 1 )
						{
							addBlurMeta( $photoId );
						}

						header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
					}
					else
					{
						echo 'Error updating Database';
					}
				}
				else
				{
					echo 'Could not find photo by ID';
				}
			}
		}
		else
		{
			if( count( $request->args ) == 1 && is_numeric( $request->args[0] ) )
			{
				$photoId = $request->args[0];
			}
			else
			{
				echo 'No photo id provided';
			}
		}
	}

	private function getZoomedOutMapUrl( $location )
	{
		$url = "/maps/api/staticmap?center=$location&zoom=6&scale=1&size=700x400&maptype=terrain&key=$this->googleMapsApiKey&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%%7Clabel:%7C$location";
			
		return buildAndSignMapUrl( $url, $this->googleMapsApiSecret );
	}

	private function getZoomedInMapUrl( $location )
	{
		$url = "/maps/api/staticmap?center=$location&zoom=15&scale=1&size=800x800&maptype=terrain&key=$this->googleMapsApiKey&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C$location";
		
		return buildAndSignMapUrl( $url, $this->googleMapsApiSecret );
	}

	private function refreshInfoFromFlickr( $id )
	{
		$db = getDb();
		$flickrId = $db->photos[ $id ]['flickr_id'];

		updatePhotoInfoFromFlickr( $id, $flickrId, $db );
	}
>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
}