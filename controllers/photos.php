<?php

require_once( 'libs/flickr.simple.php' );
require_once( 'libs/NotORM.php' );

date_default_timezone_set('America/Los_Angeles');

$keys = json_decode( utf8_encode( file_get_contents('../keys.json') ) );

if( !$keys )
{
    die('Bad Keys file');
}

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

function getWallpapers()
{
    $db = getDb();
    $row = $db->photos()->select('flickr_id')->where("wallpaper", 1);
    
    $wallpapers = array();
    while( $data = $row->fetch() )
    {
        $wallpapers[] = $data['flickr_id'];
    }
    
    return $wallpapers;
}

function getPhotoframes()
{
    $db = getDb();
    $row = $db->photos()->select('flickr_id')->where("photoframe", 1);
    
    $photos = array();
    while( $data = $row->fetch() )
    {
        $photos[] = $data['flickr_id'];
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
    $wallpapers = getWallpapers();

    mt_srand($dayOfYear);
    $photoIndex = mt_rand( 0, count($wallpapers) );
    
    $photoId = $wallpapers[$photoIndex];

    return getPhoto( $photoId );
}

function getPhoto( $photoId, $findSmallest = false, $minWidth = -1, $minHeight = -1 )
{
    global $flickr;
    
    $todaysPhoto = new Photo();
    
    $method = 'flickr.photos.getInfo';
    $args = array('photo_id' => $photoId);
    $response = $flickr->call_method($method, $args);
    
    if( $response['stat'] == "ok" )
    {
        $photoInfo = $response['photo'];
        
        $todaysPhoto->title = $photoInfo['title']['_content'];
        $todaysPhoto->description = $photoInfo['description']['_content'];
        $todaysPhoto->date = $photoInfo['dates']['taken'];
        if( array_key_exists('location', $photoInfo) )
        {
            $todaysPhoto->location = $photoInfo['location']['latitude'] . ',' . $photoInfo['location']['longitude'];
        }
    }
    
    $method = 'flickr.photos.getSizes';
    $args = array('photo_id' => $photoId);
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
        $todaysPhoto->url = $selectedSize['url'];
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

class Photo
{
    public $title = "";
    public $description = "";
    public $date = "";
    public $image = "";
    public $url = "";
    public $location = "";
}

?>