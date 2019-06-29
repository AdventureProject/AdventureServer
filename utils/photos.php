<?php

require_once('utils/KeysUtil.php');
require_once('utils/b2_util.php');
require_once('libs/flickr.simple.php');
require_once('libs/NotORM.php');

date_default_timezone_set( 'America/Los_Angeles' );

global $keys;
global $flickr;

$keys = getKeys();

$key = $keys->flickr_api->key;
$secret = $keys->flickr_api->secret;
$flickr = new Flickr( $key, $secret );

function getDb()
{
	global $keys;

	$connection = new PDO( "mysql:dbname={$keys->mysql->database}", $keys->mysql->user, $keys->mysql->password );
	$adventure = new NotORM( $connection );
	return $adventure;
}

function getDbPdo()
{
	global $keys;
	$servername = "localhost";

	try {
		$conn = new PDO("mysql:host=$servername;dbname={$keys->mysql->database}", $keys->mysql->user, $keys->mysql->password);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $conn;
	}
	catch(PDOException $e)
	{
		error_log( "Connection failed: " . $e->getMessage() );
	}
}

function getRandomPhoto()
{
	$db = getDb();
	$row = $db->photos()->select( 'id, flickr_id' )->order( 'RAND()' )->limit( '1' );
	$data = $row->fetch();

	$photoIds = array();
	$photoIds['id'] = $data['id'];
	$photoIds['flickr_id'] = $data['flickr_id'];
	
	$db->close();
	$db = null;

	return $photoIds;
}

function getAllPhotos()
{
	$db = getDb();
	$row = $db->photos()->select( 'id, flickr_id' );

	$photos = array();
	while( $data = $row->fetch() )
	{
		$photoIds = array();
		$photoIds['id'] = $data['id'];
		$photoIds['flickr_id'] = $data['flickr_id'];

		$photos[] = $photoIds;
	}
	
	$db->close();
	$db = null;

	return $photos;
}

function getRandomWallpaper()
{
	$db = getDb();
	$row = $db->photos()->select( 'id, flickr_id' )->where( "wallpaper", 1 )->order( 'RAND()' )->limit( '1' );
	$data = $row->fetch();

	$photoIds = array();
	$photoIds['id'] = $data['id'];
	$photoIds['flickr_id'] = $data['flickr_id'];
	
	$db->close();
	$db = null;

	return $photoIds;
}

function getWallpapers()
{
	$db = getDb();
	$row = $db->photos()->select( 'id, flickr_id' )->where( "wallpaper", 1 );

	$wallpapers = array();
	while( $data = $row->fetch() )
	{
		$photoIds = array();
		$photoIds['id'] = $data['id'];
		$photoIds['flickr_id'] = $data['flickr_id'];

		$wallpapers[] = $photoIds;
	}
	
	$db->close();
	$db = null;

	return $wallpapers;
}

function getPhotoframePhoto()
{
	$db = getDb();
	$photo = $db->photos()->select( 'id' )->where( 'photoframe', 1 )->order( "RAND()" )->limit( 1 )->fetch();
	$db->close();
	$db = null;

	return $photo;
}

function getTodaysPhotoLocal()
{
	$dayOfYear = date( "z" );
	return getPhotoForDayLocal( $dayOfYear );
}

function getPhotoForDayLocal( $dayOfYear )
{
	$db = getDb();

	$todaysPhotoData = $db->photos()->where( "wallpaper", 1 )->order( "RAND({$dayOfYear})" )->limit( 1 )->fetch();

	$db->close();
	$db = null;
	
	return $todaysPhotoData;
}

function getTodaysPhoto( $minWidth = 1900, $minHeight = 1080 ) // These sizes are good for full fidelity
{
	$dayOfYear = date( "z" );
	return getPhotoForDay( $dayOfYear, $minWidth, $minHeight );
}

function getPhotoForDay( $dayOfYear, $minWidth = -1, $minHeight = -1 )
{
	$photoData = getPhotoForDayLocal( $dayOfYear );

	$findSmallest = false;
	if( $minWidth > -1 || $minHeight > -1 )
	{
		$findSmallest = true;
	}

	return getPhoto( $photoData['id'], $findSmallest, $minWidth, $minHeight );
}

function getPhotoById( $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
	return getPhoto( $photoId, $findSmallest, $minWidth, $minHeight );
}

function getPossiblePhotoSizes( $photoId )
{
	$minWidth = 640;
	$minHeight = 480;

	$db = getDb();
	$row = $db->photos()->select( 'id, width, height', 'imagetype' )->where( "id", $photoId )->fetch();

	$width = $row['width'];
	$height = $row['height'];

	$curWidth = $width;
	$curHeight = $height;

	$sizes = array();
	$sizes['original']['width'] = $width;
	$sizes['original']['height'] = $height;

	$sizes['sizes'] = array();

	while( true )
	{
		if( $curWidth > $minWidth && $curHeight > $minHeight
			&& is_numeric( $curWidth ) && is_numeric( $curHeight ) )
		{
			$size = array();
			$size['width'] = $curWidth;
			$size['height'] = $curHeight;

			$sizes['sizes'][] = $size;

			$curWidth = floor( $curWidth / 2 );
			$curHeight = floor( $curHeight / 2 );
		}
		else
		{
			break;
		}
	}
	
	$db->close();
	$db = null;

	return $sizes;
}

function getPhoto( $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
	$db = getDb();
	$photoRow = $db->photos()->select( '*' )->where( "id", $photoId )->fetch();

	$todaysPhoto = new Photo();
	$todaysPhoto->id = $photoId;
	$todaysPhoto->title = $photoRow['title'];
	$todaysPhoto->description = $photoRow['description'];
	$todaysPhoto->date = $photoRow['date_taken'];
	$todaysPhoto->url = "http://wethinkadventure.rocks/photo/$photoId";
	$todaysPhoto->orientation = $photoRow['orientation'];
	$todaysPhoto->imageType = $photoRow['imagetype'];

	if( !empty( $photoRow['location'] ) )
	{
		$todaysPhoto->location = $photoRow['location'];
	}

	$possibleSizes = getPossiblePhotoSizes( $photoId );

	if( $possibleSizes )
	{
		if( $findSmallest === true )
		{
			$selectedSize = findSmallest( $possibleSizes, $minWidth, $minHeight, $photoRow['imagetype'], $photoId );
		}
		else
		{
			$selectedSize = b2GetPublicPhotoOriginalUrl( $photoId );
		}
		$todaysPhoto->image = $selectedSize;
	}

	$todaysPhoto->thumbnail = b2GetPublicThumbnailUrl( $photoId );
	
	$db->close();
	$db = null;

	return $todaysPhoto;
}

function determineOrientation( $width, $height )
{
	if( $width > $height )
	{
		return 'land';
	}
	else
	{
		return 'port';
	}
}

function findSmallest( $sizes, $minWidth, $minHeight, $imageType, $photoId )
{
	$smallestSize = NULL;

	$curSmallestSize = PHP_INT_MAX;

	foreach( $sizes['sizes'] as $size )
	{
		$totalSize = $size['width'] * $size['height'];
		if( $totalSize < $curSmallestSize && ($size['width'] >= $minWidth && $size['height'] >= $minHeight) )
		{
			$curSmallestSize = $totalSize;
			$smallestSize = $size;
		}
	}

	$resizedUrl = NULL;
	if( is_numeric( $smallestSize['width'] ) && is_numeric( $smallestSize['height'] )
		&& $smallestSize['width'] != $sizes['original']['width']
		&& $smallestSize['height'] != $sizes['original']['height'] )
	{
		$resizedUrl = b2GetPublicResizedUrl( $photoId, $smallestSize['width'], $smallestSize['height'], $imageType );

		$rowValues = array( "photo_id" => $photoId, "width" => $smallestSize['width'], "height" => $smallestSize['height'] );

		$db = getDb();
		$photoSizeRow = $db->photo_sizes()->select( '*' )->where( $rowValues )->fetch();

		// If it doesn't already exist, we must make it!
		if( !$photoSizeRow )
		{
			error_log( 'Resized file does NOT exist!' );

			$originalSizeUrl = b2GetPublicPhotoOriginalUrl( $photoId );
			error_log( 'source ' . $originalSizeUrl );
			$localPath = 'data/resize';
			$fileName = "resize_" . $photoId . '_' . $smallestSize['width'] . '_' . $smallestSize['height'] . '.' . $imageType;
			$localFile = $localPath . '/' . $fileName;

			// Download the original image
			file_put_contents( $localFile, file_get_contents( $originalSizeUrl ) );

			error_log( 'local: ' . $localFile );
			error_log( 'downloaded original' );
			$image = new Imagick( $localFile );
			if( $image )
			{
				$image->resizeImage( $smallestSize['width'], $smallestSize['height'], Imagick::FILTER_LANCZOS, 1 );
				error_log( 'resized!' );
				$image->writeImage( $localFile );

				error_log( 'Proccessed image' );

				$targetPath = getB2PhotoMetaResizedPath( $photoId, $smallestSize['width'], $smallestSize['height'], $imageType );
				// Upload resized image to B2
				uploadB2File( $localFile, $targetPath );

				$db->photo_sizes()->insert( $rowValues );

				error_log( 'uploaded' );
			}

			unlink( $localFile );
		}
		
		$db->close();
		$db = null;
	}
	else
	{
		$resizedUrl = b2GetPublicPhotoOriginalUrl( $photoId );
	}

	return $resizedUrl;
}

function remoteFileExists( $theURL )
{
	$code = getHttpResponseCode( $theURL );
	return $code == 200;
}

function getHttpResponseCode( $theURL )
{
	$headers = @get_headers( $theURL );
	return substr( $headers[0], 9, 3 );
}

function updatePhotoInfoFromFlickr( $id, $flickrId, $db )
{
	$keys = getKeys();

	$key = $keys->flickr_api->key;
	$secret = $keys->flickr_api->secret;
	$flickr = new Flickr( $key, $secret );

	$method = 'flickr.photos.getInfo';
	$args = array( 'photo_id' => $flickrId );
	$responseInfo = $flickr->call_method( $method, $args );

	$imageType = $responseInfo['photo']['originalformat'];
	$title = $responseInfo['photo']['title']['_content'];
	$description = $responseInfo['photo']['description']['_content'];
	$location = $responseInfo['photo']['location']['latitude'] . ',' . $responseInfo['photo']['location']['longitude'];
	$dateTaken = $responseInfo['photo']['dates']['taken'];

	$utc = new DateTimeZone("UTC");
	$pst = new DateTimeZone("America/Los_Angeles");
	$dateTime = new DateTime( $dateTaken, $pst );
	$dateTime->setTimezone($utc);
	$photoDate = $dateTime->format('Y-m-d H:i:s');

	$rowUpdate = array(
		'title' => $title,
		'description' => $description,
		'imagetype' => $imageType,
		'location' => $location,
		'date_taken' => $photoDate,
		'date_updated' => new NotORM_Literal( "NOW()" )
	);

	$photoRow = $db->photos[ $id ];
	$result = $photoRow->update( $rowUpdate );
	
	error_log('updating photo info: ' . $id . ' flickr: ' . $flickrId);
}

function deletePhoto( $photoId )
{
	$b2Files = listAllFilesInternal( $photoId );
	
	if( $b2Files )
	{
		foreach( $b2Files as $file )
		{
			$deleteResult = deleteB2File( $file );
			echo 'Delete file ' . $file . ' Success: ' . $deleteResult . '<br />';
		}
	}

	$db = getDb();
	$photoResult = $db->photos[ $photoId ]->delete();
	echo 'Photo deleted: ' . ($photoResult == true) . '<br />';
	
	$sizesResults = $db->photo_sizes('photo_id = ?', $photoId);
	foreach( $sizesResults as $row )
	{
		$deleteResult = $row->delete();
		echo 'Photo Size deleted: ' . ($deleteResult == true) . '<br />';
	}
	
	$albumResults = $db->album_photos('photos_id = ?', $photoId);
	foreach( $albumResults as $row )
	{
		$deleteResult = $row->delete();
		echo 'Album Photo deleted: ' . ($deleteResult == true) . '<br />';
	}
	
	$db->close();
	$db = null;
}

function addBlurMeta( $photoId, $force = false )
{
	$success = false;

	$targetPath = b2GetPublicBlurUrl( $photoId );

	if( !remoteFileExists( $targetPath ) || $force )
	{
		error_log( 'Photo: ' . $photoId );
		$photo = getPhotoById( $photoId, true, 1024, 768 );

		$localPath = 'data/temp';
		$fileName = $photoId . '_blurred_background.jpg';
		$localFile = $localPath . '/' . $fileName;

		// Download the image from Flickr
		if( file_put_contents( $localFile, file_get_contents( $photo->image ) ) )
		{
			error_log( 'Downloaded Photo' );
			// Read the file and blur it
			$image = new Imagick( $localFile );
			if( $image )
			{
				$image->gaussianBlurImage( 15, 10 );

				$image->writeImage( $localFile );
				error_log( 'Proccessed image: ' . $localFile );

				$uploadUrl = getB2PhotoMetaPath( $photoId ) . '/' . $GLOBALS['b2InternalPath']['photo']['blurred_image'];
				// Upload blurred image to B2
				$success = uploadB2File( $localFile, $uploadUrl );
				if( $success )
				{
					error_log( 'Uploaded to B2' );
				}
				else
				{
					error_log( 'Failed to upload to B2' );
				}
			}

			unlink( $localFile );
			error_log( 'Temp file deleted' );
		}
		else
		{
			error_log( 'Failed to download photo' );
		}
	}
	else
	{
		error_log( 'Blur already exists, skipping' );
		$success = true;
	}

	return $success;
}

function createPhotoImport( $flickrId, $flickrAlbumId, $targetAlbum, $isAlbumCoverPhoto = false, $isWallpaper = false, $isHighlight = false, $isPhotoframe = false, $isPhotowall = false )
{
	$importTaskId = null;

	$db = getDb();

	$existingImport = $db->photo_import( "flickr_id", $flickrId )->fetch();

	if( !$existingImport )
	{
		error_log( 'Creating import task for ' . $flickrId );

		$newImportTask = array( 'flickr_id' => $flickrId,
			'flickr_album_id' => $flickrAlbumId,
			'target_album_id' => $targetAlbum,
			'is_wallpaper' => $isWallpaper ? 1 : 0,
			'is_photoframe' => $isPhotoframe ? 1 : 0,
			'is_highlight' => $isHighlight ? 1 : 0,
			'is_photowall' => $isPhotowall ? 1 : 0,
			'is_album_cover' => $isAlbumCoverPhoto ? 1 : 0 );

		$insertResult = $db->photo_import()->insert( $newImportTask );
		if( $insertResult )
		{
			$importTaskId = $insertResult['id'];
		}
		else
		{
			error_log('Failed to insert Import Task into DB!');
		}
	}
	else
	{
		error_log( 'Flickr import already exists! State: ' . $existingImport['import_state'] );
	}
	
	$db->close();
	$db = null;

	return $importTaskId;
}

function processImportTask( $importTaskId )
{
	$localId = null;

	$db = getDb();

	$keys = getKeys();

	$key = $keys->flickr_api->key;
	$secret = $keys->flickr_api->secret;
	$flickr = new Flickr( $key, $secret );

	$importTask = $db->photo_import[ $importTaskId ];
	if( $importTask )
	{
		$localId = $importTask['created_photo_id'];
		$flickrId = $importTask['flickr_id'];
		$flickrAlbumId = $importTask['flickr_album_id'];
		$isWallpaper = $importTask['is_wallpaper'];
		$isHighlight = $importTask['is_highlight'];
		$isPhotoframe = $importTask['is_photoframe'];
		$isPhotowall = $importTask['is_photowall'];
		$isAlbumCover = $importTask['is_album_cover'];
		$targetAlbumId = $importTask['target_album_id'];

		//'not_started','photo_data_imported','source_photo_transferred','thumbnail_transferred','meta_blur_uploaded','import_complete'
		$importRunning = true;
		while( $importRunning )
		{
			switch( $importTask['import_state'] )
			{
				case 'not_started':
					error_log( $importTaskId . ' - not_started' );

					if( $importRunning = createPhotoData( $flickrId, $targetAlbumId, $isWallpaper, $isHighlight, $isPhotoframe, $isPhotowall, $db, $flickr, $keys ) )
					{
						$newPhoto = $db->photos()->select( '*' )->where( 'flickr_id', $flickrId )->fetch();
						if( $newPhoto )
						{
							$importTask['created_photo_id'] = $localId = $newPhoto['id'];

							if( $isAlbumCover )
							{
								$album = $db->albums[$targetAlbumId];
								if( $album )
								{
									$album['cover_photo_id'] = $localId;
									$album->update();
								}
							}

							$importTask['import_state'] = 'photo_data_imported';
							$importTask->update();
						}
					}
					break;
				case 'photo_data_imported':
					error_log( $importTaskId . ' - photo_data_imported' );

					if( $importRunning = transferPhotoFromFlickrToB2( $localId, $flickrId, $flickr ) )
					{
						$importTask['import_state'] = 'source_photo_transferred';
						$importTask->update();
					}
					break;
				case 'source_photo_transferred':
					error_log( $importTaskId . ' - source_photo_transferred' );

					if( $importRunning = transferThumbnailFromFlickrToB2( $localId ) )
					{
						$importTask['import_state'] = 'thumbnail_transferred';
						$importTask->update();
					}
					break;
				case 'thumbnail_transferred':
					error_log( $importTaskId . ' - thumbnail_transferred' );

					$album = $db->albums( 'cover_photo_id', $localId )->fetch();
					$isAlbumCover = ($album != false);

					if( $isWallpaper || $isAlbumCover )
					{
						if( addBlurMeta( $localId ) )
						{
							$importTask['import_state'] = 'meta_blur_uploaded';
						}
						else
						{
							$importRunning = false;
						}
					}
					else
					{
						$importTask['import_state'] = 'meta_blur_uploaded';
					}

					$importTask->update();
					break;
				case 'meta_blur_uploaded':
					error_log( 'Import complete! Flickr ID: ' . $flickrId . ' Local ID: ' . $localId );
					$importTask->delete();
					$importRunning = false;
					break;
				case 'import_complete':
					$importRunning = false;
					break;
			}
		}
	}
	
	$db->close();
	$db = null;

	return $localId;
}

function createPhotoData( $flickrId, $targetAlbumId, $isWallpaper, $isHighlight, $isPhotoframe, $isPhotowall, $db, $flickr, $keys )
{
	$success = false;

	$row = $db->photos( 'flickr_id = ?', $flickrId )->fetch();

	if( !$row )
	{
		$item['flickr_id'] = $flickrId;
		$item['wallpaper'] = $isWallpaper ? 1 : 0;
		$item['highlight'] = $isHighlight ? 1 : 0;
		$item['photoframe'] = $isPhotoframe ? 1 : 0;

		if( $isPhotowall )
		{
			$item['photowall_id'] = $db->photos()->max( 'photowall_id' ) + 1;
		}
		else
		{
			$item['photowall_id'] = null;
		}

		////////////////////////////////////////////////////////
		// Flickr Info

		$method = 'flickr.photos.getInfo';
		$args = array( 'photo_id' => $flickrId );
		$responseInfo = $flickr->call_method( $method, $args );

		$item['imagetype'] = $responseInfo['photo']['originalformat'];
		$item['title'] = $responseInfo['photo']['title']['_content'];
		$item['description'] = $responseInfo['photo']['description']['_content'];
		$item['location'] = $responseInfo['photo']['location']['latitude'] . ',' . $responseInfo['photo']['location']['longitude'];

		$hadRotation = null;
		$rotation = $responseInfo['photo']['rotation'];
		if( $rotation == 90 || $rotation == 180 )
		{
			$hadRotation = true;
		}
		else
		{
			$hadRotation = false;
		}

		$item['date_taken'] = $responseInfo['photo']['dates']['taken'];

		////////////////////////////////////////////////////////
		// Flickr Sizes

		$method = 'flickr.photos.getSizes';
		$args = array( 'photo_id' => $flickrId );
		$responseSizes = $flickr->call_method( $method, $args );

		$width = -1;
		$height = -1;

		foreach( $responseSizes['sizes']['size'] as $size )
		{
			if( $size['label'] == 'Original' )
			{
				$width = $size['width'];
				$height = $size['height'];
				break;
			}
		}

		if( $hadRotation )
		{
			$item['width'] = $height;
			$item['height'] = $width;
		}
		else
		{
			$item['width'] = $width;
			$item['height'] = $height;
		}

		$item['orientation'] = determineOrientation( $item['width'], $item['height'] );

		////////////////////////////////////////////////////////
		// Insert to DB

		$newRow = $db->photos()->insert( $item );
		if( $newRow )
		{
			$success = true;
		}
	}
	else
	{
		$success = true;
	}

	if( $success )
	{
		// If this is an album photo, insert it to that album
		if( $targetAlbumId != null )
		{
			$photoRow = $db->photos( 'flickr_id', $flickrId )->fetch();

			$item = array( 'albums_id' => $targetAlbumId,
				'photos_id' => $photoRow['id'] );

			$db->album_photos()->insert( $item );

			// By default we want the album date to be the date that the earliest photo in that album was taken,
			// so if this photo is older than the current album date, update the album date
			$album = $db->albums[ $targetAlbumId ];
			if( strtotime( $photoRow['date_taken'] ) < strtotime( $album['date'] ) )
			{
				$album['date'] = $photoRow['date_taken'];
				$album->update();
			}
		}
	}

	return $success;
}

function getFlickrSizes( $flickrId, $flickr )
{
	////////////////////////////////////////////////////////
	// Flickr Sizes

	$method = 'flickr.photos.getSizes';
	$args = array( 'photo_id' => $flickrId );
	$responseSizes = $flickr->call_method( $method, $args );

	$sizes = false;
	if( $responseSizes )
	{
		$flickrUrl = null;
		$thumbnailUrl = null;

		foreach( $responseSizes['sizes']['size'] as $size )
		{
			if( $size['label'] == 'Original' )
			{
				$flickrUrl = $size['source'];
			}
			else if( $size['label'] == 'Medium' )
			{
				$thumbnailUrl = $size['source'];
			}
		}

		$sizes = array( 'original' => $flickrUrl, 'thumbnail' => $thumbnailUrl );
	}

	return $sizes;
}

function transferPhotoFromFlickrToB2( $id, $flickrId, $flickr, $force = false )
{
	$success = false;

	$sourceUrl = b2GetPublicPhotoOriginalUrl( $id );

	if( !remoteFileExists( $sourceUrl ) || $force )
	{
		$flickrSizes = getFlickrSizes( $flickrId, $flickr );
		$flickrUrl = $flickrSizes['original'];

		error_log( 'about to download ' . $flickrUrl );

		$tmpFileName = "data/temp/" . $flickrId;
		$downloadResult = file_put_contents( $tmpFileName, fopen( $flickrUrl, 'r' ) );

		if( $downloadResult )
		{
			error_log( 'file download SUCCESS' );

			// If the photo has an orientation set, rotate it
			$exif = exif_read_data( $tmpFileName );
			if( array_key_exists( 'Orientation', $exif ) && $exif['Orientation'] != 1 )
			{
				error_log( 'rotation required' );

				$image = new Imagick( $tmpFileName );
				autorotateImage( $image );
				$image->writeImage();
			}

			$photoExt = pathinfo( $flickrUrl, PATHINFO_EXTENSION );

			$targetPath = getB2PhotoPath( $id );
			$targetFileName = $targetPath . '/' . 'source.' . $photoExt;
			$b2BucketId = getKeys()->b2->bucket_id;

			$success = uploadB2File( $tmpFileName, $targetFileName, $b2BucketId );

			error_log( 'b2 uploaded' );

			// Delete the temp file
			unlink( $tmpFileName );

			error_log( 'temp file deleted' );
		}
		else
		{
			$success = true;
			error_log( 'file download failed' );
		}
	}
	else
	{
		$success = true;
		error_log( 'Source image already exists, skipping transfer' );
	}

	return $success;
}

function transferThumbnailFromFlickrToB2( $photoId, $force = false )
{
	$success = false;

	$ourThumbnailUrl = b2GetPublicThumbnailUrl( $photoId );
	if( (!remoteFileExists( $ourThumbnailUrl ) || $force) )
	{
		error_log( 'transferThumbnailFromFlickrToB2 photoId: ' . $photoId );
		
		$db = getDb();
		$photoRow = $db->photos()->select( 'flickr_id' )->where( "id", $photoId )->fetch();
		$db->close();
		$db = null;
		
		$flickrId = $photoRow['flickr_id'];

		$keys = getKeys();

		$key = $keys->flickr_api->key;
		$secret = $keys->flickr_api->secret;
		$flickr = new Flickr( $key, $secret );

		////////////////////////////////////////////////////////
		// Flickr Sizes

		$method = 'flickr.photos.getSizes';
		$args = array( 'photo_id' => $flickrId );
		$responseSizes = $flickr->call_method( $method, $args );

		$thumbnailUrl = null;

		foreach( $responseSizes['sizes']['size'] as $size )
		{
			if( $size['label'] == 'Medium' )
			{
				$thumbnailUrl = $size['source'];
				break;
			}
		}

		error_log( 'thumbnailUrl: ' . $thumbnailUrl );

		$tmpFileName = "data/temp/" . $photoId . '_thumbnail';
		$downloadResult = file_put_contents( $tmpFileName, fopen( $thumbnailUrl, 'r' ) );
		if( $downloadResult )
		{
			error_log( 'thumbnail downloaded' );

			$targetPath = getB2PhotoMetaPath( $photoId ) . '/' . 'thumbnail.jpg';
			$success = uploadB2File( $tmpFileName, $targetPath );

			error_log( 'thumbnail uploaded' );
		}
		unlink( $tmpFileName );
		error_log( 'thumbnail temp deleted' );
	}
	else
	{
		error_log( 'thumbnail already exists, skipping' );
		$success = true;
	}

	return $success;
}

function autorotateImage( Imagick $image )
{
	switch( $image->getImageOrientation() )
	{
		case Imagick::ORIENTATION_TOPLEFT:
			break;
		case Imagick::ORIENTATION_TOPRIGHT:
			$image->flopImage();
			break;
		case Imagick::ORIENTATION_BOTTOMRIGHT:
			$image->rotateImage( "#000", 180 );
			break;
		case Imagick::ORIENTATION_BOTTOMLEFT:
			$image->flopImage();
			$image->rotateImage( "#000", 180 );
			break;
		case Imagick::ORIENTATION_LEFTTOP:
			$image->flopImage();
			$image->rotateImage( "#000", -90 );
			break;
		case Imagick::ORIENTATION_RIGHTTOP:
			$image->rotateImage( "#000", 90 );
			break;
		case Imagick::ORIENTATION_RIGHTBOTTOM:
			$image->flopImage();
			$image->rotateImage( "#000", 90 );
			break;
		case Imagick::ORIENTATION_LEFTBOTTOM:
			$image->rotateImage( "#000", -90 );
			break;
		default: // Invalid orientation
			break;
	}
	$image->setImageOrientation( Imagick::ORIENTATION_TOPLEFT );
	return $image;
}

class Photo
{
	public $title = "";
	public $description = "";
	public $date = "";
	public $image = "";
	public $imageType = "";
	public $url = "";
	public $thumbnail = "";
	public $orientation = "";
	public $location = "";
	public $id = "";
}

?>