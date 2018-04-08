<?php

require_once( 'utils/KeysUtil.php' );
require_once( 'utils/b2_util.php' );
require_once( 'libs/flickr.simple.php' );
require_once( 'libs/NotORM.php' );

date_default_timezone_set('America/Los_Angeles');

global $keys;
global $flickr;

$keys = getKeys();

$key = $keys->flickr_api->key;
$secret = $keys->flickr_api->secret;
$flickr = new Flickr($key, $secret);

function getDb()
{
    global $keys;
	
    $connection = new PDO("mysql:dbname={$keys->mysql->database}",$keys->mysql->user,$keys->mysql->password);
    $adventure = new NotORM($connection);
    return $adventure;
}

function getRandomPhoto()
{
    $db = getDb();
    $row = $db->photos()->select('id, flickr_id')->order('RAND()')->limit('1');
	$data = $row->fetch();

	$photoIds = array();
	$photoIds['id'] = $data['id'];
	$photoIds['flickr_id'] = $data['flickr_id'];

    return $photoIds;
}

function getAllPhotos()
{
    $db = getDb();
    $row = $db->photos()->select('id, flickr_id');

    $photos = array();
    while( $data = $row->fetch() )
    {
        $photoIds = array();
        $photoIds['id'] = $data['id'];
        $photoIds['flickr_id'] = $data['flickr_id'];

        $photos[] = $photoIds;
    }

    return $photos;
}

function getRandomWallpaper()
{
    $db = getDb();
    $row = $db->photos()->select('id, flickr_id')->where("wallpaper", 1)->order('RAND()')->limit('1');
	$data = $row->fetch();

	$photoIds = array();
	$photoIds['id'] = $data['id'];
	$photoIds['flickr_id'] = $data['flickr_id'];

    return $photoIds;
}

function getWallpapers()
{
    $db = getDb();
    $row = $db->photos()->select('id, flickr_id')->where("wallpaper", 1);

    $wallpapers = array();
    while( $data = $row->fetch() )
    {
        $photoIds = array();
        $photoIds['id'] = $data['id'];
        $photoIds['flickr_id'] = $data['flickr_id'];

        $wallpapers[] = $photoIds;
    }

    return $wallpapers;
}

function getPhotoframes()
{
    $db = getDb();
    $row = $db->photos()->select('id, flickr_id')->where("photoframe", 1);
    
    $photos = array();
    while( $data = $row->fetch() )
    {
        $photos[] = array( 'id' => $data['id'], 'flickr_id' => $data['flickr_id'] );
    }
    
    return $photos;
}

function getTodaysPhotoLocal()
{
	$dayOfYear = date("z");
	return getPhotoForDayLocal( $dayOfYear );
}

function getPhotoForDayLocal( $dayOfYear )
{
	$db = getDb();
	
	$todaysPhotoData = $db->photos()->where("wallpaper", 1)->order("RAND({$dayOfYear})")->limit(1)->fetch();
	
	return $todaysPhotoData;
}

function getTodaysPhoto( $minWidth = 1900, $minHeight = 1080 ) // These sizes are good for full fidelity
{
    $dayOfYear = date("z");
    return getPhotoForDay( $dayOfYear, $minWidth, $minHeight );
}

function getPhotoForDay($dayOfYear, $minWidth = -1, $minHeight = -1)
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
	$row = $db->photos()->select('id, width, height', 'imagetype')->where("id", $photoId)->fetch();

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

	return $sizes;
}

function getPhoto( $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
	$db = getDb();
	$photoRow = $db->photos()->select('*')->where("id", $photoId)->fetch();

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

	error_log($photoId . " final smallest size: " . $smallestSize['width'] . ' ' . $smallestSize['height']);

	$resizedUrl = NULL;
    if( is_numeric( $smallestSize['width']  ) && is_numeric( $smallestSize['height'] )
		&& $smallestSize['width'] != $sizes['original']['width']
	    && $smallestSize['height'] != $sizes['original']['height'] )
    {
		$resizedUrl = b2GetPublicResizedUrl($photoId, $smallestSize['width'], $smallestSize['height'], $imageType);

		$rowValues = array("photo_id" => $photoId, "width" => $smallestSize['width'], "height" => $smallestSize['height']);

		$db = getDb();
		$photoSizeRow = $db->photo_sizes()->select('*')->where($rowValues)->fetch();

		// If it doesn't already exist, we must make it!
		if (!$photoSizeRow)
		{
			error_log('Resized file does NOT exist!');

			$originalSizeUrl = b2GetPublicPhotoOriginalUrl($photoId);
			error_log('source ' . $originalSizeUrl);
			$localPath = 'data/resize';
			$fileName = "resize_" . $photoId . '_' . $smallestSize['width'] . '_' . $smallestSize['height'] . '.' . $imageType;
			$localFile = $localPath . '/' . $fileName;

			// Download the original image
			file_put_contents($localFile, file_get_contents($originalSizeUrl));

			error_log('local: ' . $localFile);
			error_log('downloaded original');
			$image = new Imagick($localFile);
			if ($image)
			{
				$image->resizeImage($smallestSize['width'], $smallestSize['height'], Imagick::FILTER_LANCZOS, 1);
				error_log('resized!');
				$image->writeImage($localFile);

				error_log('Proccessed image');

				$targetPath = getB2PhotoMetaResizedPath($photoId, $smallestSize['width'], $smallestSize['height'], $imageType);
				// Upload resized image to B2
				uploadB2File($localFile, $targetPath);

				$db->photo_sizes()->insert($rowValues);

				error_log('uploaded');
			}

			unlink($localFile);
		}
		else
		{
			error_log('Resized file does exist :D');
		}
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
	$headers = @get_headers($theURL);
	return substr($headers[0], 9, 3);
}

function updatePhotoCache( $id, $flickrPhoto, $db )
{
	$rowUpdate = array(
		'title' => $flickrPhoto->title,
		'description' => $flickrPhoto->description,
		'thumbnail' => $flickrPhoto->thumbnail,
		'imagetype' => $flickrPhoto->imageType,
		'orientation' => $flickrPhoto->orientation,
		'location' => $flickrPhoto->location,
		'date_taken' => $flickrPhoto->date,
		'date_updated' => new NotORM_Literal("NOW()")
	);

	$photoRow = $db->photos[$id];
	$result = $photoRow->update( $rowUpdate );
}

function addBlurMeta( $photoId )
{
	$targetPath = getB2PhotoMetaPath( $photoId ) . '/' . $GLOBALS['b2InternalPath']['photo']['blurred_image'];
	
	error_log('Photo: ' . $photoId);
	$photo = getPhotoById( $photoId, true, 1024, 768 );

	$localPath = 'data/blur';
	$fileName = "blurred_background.jpg";
	$localFile = $localPath . '/' . $fileName;

	// Download the image from Flickr
	file_put_contents($localFile, file_get_contents( $photo->image ));
	error_log('Downloaded Photo');
	// Read the file and blur it
	$image = new Imagick( $localFile );
	if( $image )
	{
		$image->gaussianBlurImage(15,5);

		$image->writeImage( $localFile );
		error_log('Proccessed image');
		// Upload blurred image to B2
		uploadB2File( $localFile, $targetPath );
		error_log('Uploaded to B2');
	}

	unlink( $localFile );
	error_log('Temp file deleted');
}

function transferPhotoFromFlickrToB2( $id, $flickrId, $flickrUrl )
{
	error_log( 'about to download ' . $flickrUrl );
	
	$tmpFileName = "data/temp/" . $flickrId;
	$downloadResult = file_put_contents($tmpFileName, fopen($flickrUrl, 'r'));
	
	if( $downloadResult )
	{
		error_log( 'file download SUCCESS' );

		// If the photo has an orientation set, rotate it
		$exif = exif_read_data( $tmpFileName );
		if( array_key_exists( 'Orientation', $exif ) && $exif['Orientation'] != 1 )
		{
			error_log( 'rotation required' );

			$image = new Imagick($tmpFileName);
			autorotateImage($image);
			$image->writeImage();
		}
		
		$photoExt = pathinfo( $flickrUrl, PATHINFO_EXTENSION );
		
		$targetPath = getB2PhotoPath( $id );
		$targetFileName = $targetPath . '/' . 'source.' . $photoExt;
		$b2BucketId = getKeys()->b2->bucket_id;

		uploadB2File( $tmpFileName, $targetFileName, $b2BucketId );

		error_log( 'b2 uploaded' );

		// Delete the temp file
		unlink( $tmpFileName );

		error_log( 'temp file deleted' );
	}
	else
	{
		error_log( 'file download failed' );
	}
}

function autorotateImage(Imagick $image)
{
	switch ($image->getImageOrientation()) {
		case Imagick::ORIENTATION_TOPLEFT:
			break;
		case Imagick::ORIENTATION_TOPRIGHT:
			$image->flopImage();
			break;
		case Imagick::ORIENTATION_BOTTOMRIGHT:
			$image->rotateImage("#000", 180);
			break;
		case Imagick::ORIENTATION_BOTTOMLEFT:
			$image->flopImage();
			$image->rotateImage("#000", 180);
			break;
		case Imagick::ORIENTATION_LEFTTOP:
			$image->flopImage();
			$image->rotateImage("#000", -90);
			break;
		case Imagick::ORIENTATION_RIGHTTOP:
			$image->rotateImage("#000", 90);
			break;
		case Imagick::ORIENTATION_RIGHTBOTTOM:
			$image->flopImage();
			$image->rotateImage("#000", 90);
			break;
		case Imagick::ORIENTATION_LEFTBOTTOM:
			$image->rotateImage("#000", -90);
			break;
		default: // Invalid orientation
			break;
	}
	$image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
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