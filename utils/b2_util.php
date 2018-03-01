<?php

require_once('vendor/autoload.php');

use B2Backblaze\B2Service;

require_once('utils/KeysUtil.php');
require_once('utils/photos.php');

$GLOBALS['b2BasePath'] = "https://data.wethinkadventure.rocks/file/adventure/data/360photos";

function getB2Client()
{
	$keys = getKeys();
	return new B2Service($keys->b2->account_id, $keys->b2->application_id);
}

function uploadB2File( $inputFilePath, $targetPath, $targetBucketId )
{
	$b2Client = getB2Client();
	
	$fileToUpload = file_get_contents($inputFilePath);
	$result = $b2Client->insert($targetBucketId, $fileToUpload, $targetPath);
}

?>