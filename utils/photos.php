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
	
	return getPhoto( $photoData['flickr_id'], $photoData['id'], $findSmallest, $minWidth, $minHeight );
}

function getPhotoById( $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
	$db = getDb();
    $row = $db->photos()->select('id, flickr_id')->where("id", $photoId)->fetch();
	
	return getPhoto( $row['flickr_id'], $photoId, $findSmallest, $minWidth, $minHeight );
}

function getPhoto( $flickrId, $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
    global $flickr;

    $todaysPhoto = new Photo();
    $todaysPhoto->id = $photoId;

    $method = 'flickr.photos.getInfo';
    $args = array('photo_id' => $flickrId);
    $response = $flickr->call_method($method, $args);
    
    if( $response['stat'] == "ok" )
    {
        $photoInfo = $response['photo'];

        $todaysPhoto->title = $photoInfo['title']['_content'];
        $todaysPhoto->description = $photoInfo['description']['_content'];
        $todaysPhoto->date = $photoInfo['dates']['taken'];
        $todaysPhoto->url = "http://wethinkadventure.rocks/photo/$photoId";
		
		$todaysPhoto->imageType = $photoInfo['originalformat'];
        
        if( array_key_exists('location', $photoInfo) )
        {
            $todaysPhoto->location = $photoInfo['location']['latitude'] . ',' . $photoInfo['location']['longitude'];
        }
    }
	else
	{
		//echo 'getInfo FAILED<br />';
		//print_r( $response );
	}
    
	$method = 'flickr.photos.getSizes';
	$args = array('photo_id' => $flickrId);
	$response = $flickr->call_method($method, $args);

	if( $response['stat'] == "ok" )
	{
		if( $findSmallest === true )
		{
			$selectedSize = findSmallest( $response['sizes']['size'], $minWidth, $minHeight );
		}
		else
		{
			$selectedSize = findLargest( $response['sizes']['size'] );
		}
		$todaysPhoto->image = $selectedSize['source'];

		$thumbnail = NULL;
		foreach( $response['sizes']['size'] as $size )
		{
			if( $size['label'] === 'Medium' )
			{
				$thumbnail = $size;
				break;
			}
		}

		$todaysPhoto->orientation = determineOrientation( $thumbnail['width'], $thumbnail['height'] );
		$todaysPhoto->thumbnail = $thumbnail['source'];
	}
	else
	{
		//echo 'getSizes FAILED<br />';
		//print_r( $response );
	}
    
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

function findLargest( $sizes )
{
    $largestSize = NULL;
    foreach( $sizes as $size )
    {
        $curLargestSize = ~PHP_INT_MAX;
        $totalSize = $size['width'] * $size['height'];
        if( $totalSize > $curLargestSize )
        {
            $curLargestSize = $totalSize;
            $largestSize = $size;
        }
    }

    return $largestSize;
}

function findSmallest( $sizes, $minWidth, $minHeight )
{
    $smallestSize = NULL;

    $curSmallestSize = PHP_INT_MAX;
    foreach( $sizes as $size )
    {
        $totalSize = $size['width'] * $size['height'];
        if( $totalSize < $curSmallestSize && ($size['width']>=$minWidth && $size['height']>=$minHeight) )
        {
            $curSmallestSize = $totalSize;
            $smallestSize = $size;
        }
    }

    return $smallestSize;
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
error_log('Proccessed image<br />');
		// Upload blurred image to B2
		uploadB2File( $localFile, $targetPath );
error_log('Uploaded to B2<br />');
	}

	unlink( $localFile );
error_log('Temp file deleted<br />');
}

function transferPhotoFromFlickrToB2( $id, $flickrId )
{
	$photo = getPhoto( $flickrId, $id );
	
	$flickrPath = $photo->image;

	echo 'about to download ' . $flickrPath . '<br />';
	
	$tmpFileName = "data/temp/" . $flickrId;
	$downloadResult = file_put_contents($tmpFileName, fopen($flickrPath, 'r'));
	
	if( $downloadResult )
	{
		echo 'file download SUCCESS' . '<br />';
		
		$photoExt = pathinfo( $flickrPath, PATHINFO_EXTENSION );
		
		$targetPath = getB2PhotoPath( $id );
		$targetFileName = $targetPath . '/' . 'source.' . $photoExt;
		$b2BucketId = getKeys()->b2->bucket_id;

		uploadB2File( $tmpFileName, $targetFileName, $b2BucketId );
		
		echo 'b2 uploaded' . '<br />';

		// Delete the temp file
		unlink( $tmpFileName );
		
		echo 'temp file deleted' . '<br />';
	}
	else
	{
		echo 'file download failed' . '<br />';
	}
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