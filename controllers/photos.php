<?php

require_once('controllers/KeysUtil.php');

require_once( 'libs/flickr.simple.php' );
require_once( 'libs/NotORM.php' );

date_default_timezone_set('America/Los_Angeles');

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

function getTodaysPhoto()
{
    $dayOfYear = date("z");
    return getPhotoForDay( $dayOfYear );
}

function getPhotoForDay($dayOfYear)
{
	$db = getDb();
	$numWallpapers = $db->photos()->where("wallpaper", 1)->count("*");

    mt_srand($dayOfYear);
    $photoIndex = mt_rand( 1, $numWallpapers );

	$photoData = $db->photos[$photoIndex];
		
	return getPhoto( $photoData['flickr_id'], $photoData['id'] );
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
        //$todaysPhoto->url = $photoInfo['urls']['url'][0]['_content'];
        $todaysPhoto->url = "http://wethinkadventure.rocks/photo/$photoId";
        
        if( array_key_exists('location', $photoInfo) )
        {
            $todaysPhoto->location = $photoInfo['location']['latitude'] . ',' . $photoInfo['location']['longitude'];
        }
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
		if( $thumbnail['width'] > $thumbnail['height'] )
		{
			$todaysPhoto->orientation = 'land';
		}
		else
		{
			$todaysPhoto->orientation = 'port';
		}
        $todaysPhoto->thumbnail = $thumbnail['source'];
    }
    
    return $todaysPhoto;
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
        'cache_title' => $flickrPhoto->title,
        'cache_thumbnail' => $flickrPhoto->thumbnail,
		'cache_orientation' => $flickrPhoto->orientation,
        'cache_location' => $flickrPhoto->location,
        'cache_updated' => new NotORM_Literal("NOW()")
    );
    
    $photoRow = $db->photos[$id];
    $photoRow->update( $rowUpdate );
}

class Photo
{
    public $title = "";
    public $description = "";
    public $date = "";
    public $image = "";
    public $url = "";
    public $thumbnail = "";
	public $orientation = "";
    public $location = "";
    public $id = "";
}

?>