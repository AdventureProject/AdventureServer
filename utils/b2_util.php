<?php

require_once('vendor/autoload.php');

use B2Backblaze\B2Service;
use B2Backblaze\B2API;

require_once('utils/KeysUtil.php');
require_once('utils/photos.php');

$GLOBALS['b2BasePath'] = array();
$GLOBALS['b2BasePath']['base'] = "https://data.wethinkadventure.rocks/file/adventure/data/";
$GLOBALS['b2BasePath']['photos'] = $GLOBALS['b2BasePath']['base'] . 'photos';
$GLOBALS['b2BasePath']['360photos'] = $GLOBALS['b2BasePath']['base'] . '360photos';
$GLOBALS['b2BasePath']['timeline'] = $GLOBALS['b2BasePath']['base'] . 'timeline';

$GLOBALS['b2InternalPath']['photos'] = 'data/photos';
$GLOBALS['b2InternalPath']['360photos'] = 'data/360photos';

$GLOBALS['b2InternalPath']['photo']['source'] = 'source';
$GLOBALS['b2InternalPath']['photo']['meta'] = 'meta';
$GLOBALS['b2InternalPath']['photo']['blurred_image'] = 'blurred.jpg';
$GLOBALS['b2InternalPath']['photo']['thumbnail_image'] = 'thumbnail.jpg';
$GLOBALS['b2InternalPath']['photo']['resized_base'] = 'resized_';

function getB2Client()
{
	$keys = getKeys();
	return new B2Service($keys->b2->account_id, $keys->b2->application_id);
}

function b2GetPublicTimelinePhoto( $file )
{
	return $GLOBALS['b2BasePath']['timeline'] . '/' . $file;
}

function b2GetPublicPhotoOriginalUrl( $id, $imageType )
{
	return $GLOBALS['b2BasePath']['photos'] . '/' . $id . '/' . $GLOBALS['b2InternalPath']['photo']['source'] . '.' . $imageType;
}

function b2GetPublicThumbnailUrl( $id )
{
	return $GLOBALS['b2BasePath']['photos'] . '/' . $id . '/' . $GLOBALS['b2InternalPath']['photo']['meta'] . '/' . $GLOBALS['b2InternalPath']['photo']['thumbnail_image'];
}

function b2GetPublicBlurUrl( $id )
{
	return $GLOBALS['b2BasePath']['photos'] . '/' . $id . '/' . $GLOBALS['b2InternalPath']['photo']['meta'] . '/' . $GLOBALS['b2InternalPath']['photo']['blurred_image'];
}

function b2GetPublicResizedUrl( $id, $width, $height, $imageType )
{
	return $GLOBALS['b2BasePath']['photos'] . '/' . $id . '/' . $GLOBALS['b2InternalPath']['photo']['meta'] . '/'
		. $GLOBALS['b2InternalPath']['photo']['resized_base'] . $width . '_' . $height . '.' .  $imageType;
}

function getB2PhotoMetaResizedPath( $id, $width, $height, $imageType )
{
	return getB2PhotoMetaPath( $id ) . '/' . $GLOBALS['b2InternalPath']['photo']['resized_base'] . $width . '_' . $height . '.' .  $imageType;
}

function b2GetPublicMetaUrl( $id, $fileName )
{
	return $GLOBALS['b2BasePath']['photos'] . '/' . $id . '/' . $GLOBALS['b2InternalPath']['photo']['meta'] . '/' . $fileName;
}

/*
function b2PhotoExists( $id,  $targetBucketId = null )
{
	if( $targetBucketId == null )
	{
		 $targetBucketId = getKeys()->b2->bucket_id;
	}
	$fileName = getB2PhotoFilename( $id );
	
	$b2Client = getB2Client();
	return $b2Client->exists( $targetBucketId, $fileName );
}

function b2PhotoMetaExists( $id, $fileName,  $targetBucketId = null )
{
	if( $targetBucketId == null )
	{
		$keys = getKeys();
		$targetBucketId = $keys->b2->bucket_id;
	}
	$fileName = getB2PhotoMetaPath( $id ) . '/' . $fileName;

	$b2Client = getB2Client();
	$b2Client->authorize();
	
	$file = $b2Client->get($targetBucketId, $fileName, false, true);
	//var_dump( $file );
	//return $file != null;
	return $b2Client->exists( $targetBucketId, $fileName );
}
*/

function getB2ThumbnailFilename( $id )
{
	return getB2PhotoMetaPath( $id ) . '/' . $GLOBALS['b2InternalPath']['photo']['thumbnail_image'];
}

function getB2PhotoFilename( $id )
{
	return getB2PhotoPath( $id ) . '/' . $GLOBALS['b2InternalPath']['photo']['source'];
}

function getB2PhotoPath( $id )
{
	return $GLOBALS['b2InternalPath']['photos'] . '/' . $id;
}

function getB2PhotoMetaPath( $id )
{
	return getB2PhotoPath( $id ) . '/' . $GLOBALS['b2InternalPath']['photo']['meta'];
}

function uploadB2File( $inputFilePath, $targetPath, $targetBucketId = null )
{
	$b2Client = getB2Client();
	
	if( $targetBucketId == null )
	{
		 $targetBucketId = getKeys()->b2->bucket_id;
	}

	$fileToUpload = file_get_contents($inputFilePath);
	$result = $b2Client->insert($targetBucketId, $fileToUpload, $targetPath);

	return $result == true;
}

function deleteB2File( $targetFile, $targetBucketId = null )
{
	$b2Client = getB2Client();
	$b2Client->authorize();
	
	if( $targetBucketId == null )
	{
		 $targetBucketId = getKeys()->b2->bucket_id;
	}

	$result = $b2Client->delete('adventure', $targetFile);
	
	return $result == true;
}

function listMetaFiles( $photoId )
{
	$keys = getKeys();
	$client = new B2API($keys->b2->account_id, $keys->b2->application_id, 2000);
	
	$targetBucketId = getKeys()->b2->bucket_id;
	
	$authResponse = $client->b2AuthorizeAccount();
	if ($authResponse->isOk())
	{
		$apiURL = $authResponse->get('apiUrl');
		$token = $authResponse->get('authorizationToken');
		$downloadURL = $authResponse->get('downloadUrl');
		$minimumPartSize = $authResponse->get('minimumPartSize');
		
		$fileNames = array();
		
		//public function b2ListFileNames($URL, $token, $bucketId, $startFileName = null, $maxFileCount = 100, $prefix = null, $delimiter = null)
		$fileNamesResponse = $client->b2ListFileNames( $apiURL, $token, $targetBucketId, $startFileName = null, $maxFileCount = 100, $prefix = getB2PhotoMetaPath( $photoId ) );
		if( $fileNamesResponse->isOK() )
		{
			$data = $fileNamesResponse->getData();
			
			foreach( $data['files'] as $file )
			{
				$parts = explode( '/', $file['fileName'] );
				$filename = end( $parts );
				$fileNames[] = $filename;
			}
		}
		
		return $fileNames;
	}
	else
	{
		return false;
	}
}

function listAllFilesInternal( $photoId )
{
	$keys = getKeys();
	$client = new B2API($keys->b2->account_id, $keys->b2->application_id, 2000);
	
	$targetBucketId = getKeys()->b2->bucket_id;
	
	$authResponse = $client->b2AuthorizeAccount();
	if ($authResponse->isOk())
	{
		$apiURL = $authResponse->get('apiUrl');
		$token = $authResponse->get('authorizationToken');
		$downloadURL = $authResponse->get('downloadUrl');
		$minimumPartSize = $authResponse->get('minimumPartSize');
		
		$fileNames = array();
		
		//public function b2ListFileNames($URL, $token, $bucketId, $startFileName = null, $maxFileCount = 100, $prefix = null, $delimiter = null)
		$fileNamesResponse = $client->b2ListFileNames( $apiURL, $token, $targetBucketId, $startFileName = null, $maxFileCount = 100, $prefix = getB2PhotoPath( $photoId ) );
		if( $fileNamesResponse->isOK() )
		{
			$data = $fileNamesResponse->getData();
			
			foreach( $data['files'] as $file )
			{
				$fileNames[] = $file['fileName'];
			}
		}
		
		return $fileNames;
	}
	else
	{
		return false;
	}
}

?>