<?php
/*
require_once('vendor/autoload.php');

use B2Backblaze\B2Service;

require_once('utils/KeysUtil.php');
require_once('utils/photos.php');

$keys = getKeys();
$client = new B2Service($keys->b2->account_id, $keys->b2->application_id);

//Authenticate with server. Anyway, all methods will ensure the authorization.
$client->authorize();

// Returns true if bucket exists
if( $client->isBucketExist($keys->b2->bucket_id) )
{
	print("Bucket ready<br />");
	
	$basePath = 'data/';
	$photosPath = $basePath . 'photos/';
	
	$photoId = '0';
	$photoFileName = 'blur.jpg';
	$fileName = $photosPath . $photoId . '/' . $photoFileName;
	
	$fileNameToUpdload = 'data/blur/blurred_background_57.jpg';
	$fileToUpload = file_get_contents($fileNameToUpdload);
echo $fileName . '<br />';
	$result = $client->insert($keys->b2->bucket_id, $fileToUpload, $fileName);
echo 'complete: ' . $result;
	print_r($result);
}
else
{
	print("Bucket does not exist!");
}
*/
?>