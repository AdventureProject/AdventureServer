<?php

require_once('utils/photos.php');
require_once('utils/file_system_util.php');
require_once('vendor/autoload.php');
require_once('libs/aws_config.php');


use Aws\Sdk;
use Aws\S3\S3Client;

$sdk = new Aws\Sdk( getCredentials() );
$s3Client = $sdk->createS3();

$script = 'generate.py';

chdir('../external/pannellum/tools');

$preview_file = $_FILES['preview_upload']['tmp_name'];
$previewName = $_FILES['preview_upload']['name'];
$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );

$target_file = $_FILES["image_upload"]["tmp_name"];
$image = $_FILES["image_upload"]["name"];
$imageName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $image);

$db = getDb();

$row = $db->photo_spheres( 'file_id = ?', $imageName )->fetch();

if( !$row )
{
	ob_implicit_flush(true);
	
	if( file_exists('output') )
	{
		echo 'Cleaning old output...';
		cleanUpOutput( 'output' );
	}

	echo 'Processing image...<br />';
	passthru( "python $script $target_file", $return_code );

	if( $return_code === 0 )
	{
		echo '<br />Image generation complete.<br />';

		echo 'Inserting into Database...<br />';
		
		$exif = exif_read_data( $target_file );
		$location = get_location( $exif );
		
		$timestamp = date("Y-m-d H:i:s", strtotime( $exif['DateTime'] ));
		
		$item['file_id'] = $imageName;
		$item['title'] = $_POST['title'];
		$item['description'] = $_POST['description'];
		$item['location'] = $location['lat'] . ', ' . $location['long'];
		$item['date_taken'] = $timestamp;

		$newRow = $db->photo_spheres()->insert( $item );

		echo 'Updating config...<br />';

		$configFile = 'output/config.json';
		$json = json_decode( file_get_contents( $configFile ), true);

		$json['basePath'] = "https://s3-us-west-2.amazonaws.com/wethinkadventurerocks/data/360photos/{$imageName}";
		$json['title'] = $_POST['title'];
		$json['preview'] = "/preview.$previewExt";

		file_put_contents( $configFile, json_encode( $json ) );

		echo 'Uploading to S3...<br />';
		$baseUploadDir = 'data/360photos/'.$imageName;

		// Upload the source image
		$mimeType = mime_content_type( $target_file );
		uploadFile( $target_file, $baseUploadDir, $image, $mimeType, $s3Client );
		
		// Upload the preview image
		$mimeType = mime_content_type( $preview_file );
		$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );
		uploadFile( $preview_file, $baseUploadDir, "preview.$previewExt", $mimeType, $s3Client );
		
		// Upload our processed ouput
		uploadDirectory( 'output', $baseUploadDir, $s3Client );

		echo 'Upload complete<br />';
		
		echo "<a href='/360photo/{$newRow["id"]}'>Go to PhotoSphere</a><br />";
	}
	else
	{
		echo 'Image conversion failed<br />';
	}

	echo 'Cleaning up...<br />';
	cleanUpOutput( 'output' );
}
else
{
	echo 'Image already added!';
}

ob_implicit_flush(false);

function get_location( $exif )
{
	$location = array();
	
	$degLong = $exif['GPSLongitude'][0];
	$minLong = $exif['GPSLongitude'][1];
	$secLong = $exif['GPSLongitude'][2];
	$refLong = $exif['GPSLongitudeRef'];

	$degLat = $exif['GPSLatitude'][0];
	$minLat = $exif['GPSLatitude'][1];
	$secLat = $exif['GPSLatitude'][2];
	$refLat = $exif['GPSLatitudeRef'];

	$location['lat']  = to_decimal($degLat, $minLat, $secLat, $refLat);
	$location['long'] = to_decimal($degLong, $minLong, $secLong, $refLong);
	
	return $location;
}

function to_decimal($deg, $min, $sec, $hem)
{
	$d = $deg + ((($min/60) + ($sec/3600)/100));
	return ($hem =='S' || $hem=='W') ?  $d*=-1 : $d;
}

function cleanUpOutput( $dir )
{
	delTree( $dir );
}

function delTree( $dir )
{
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file)
	{
		(is_dir("$dir/$file")) ? delTree("$dir/$file", true) : unlink("$dir/$file");
	}
	
	return rmdir($dir);
}

function uploadDirectory( $curDir, $baseUploadDir, $s3Client )
{
	echo 'Directory: ' . $baseUploadDir . '<br />';
	
	$files = dirToArray( $curDir );
	foreach( $files as $parentDir => $file )
	{
		if( is_array( $file ) )
		{
			uploadDirectory( $curDir . '/' . $parentDir, $baseUploadDir . '/' . $parentDir, $s3Client );
		}
		else if( $file !== '.' && $file !== '..' )
		{
			$localFilePath = $curDir . '/' . $file;
			$mimeType = mime_content_type( $localFilePath );
			uploadFile( $localFilePath, $baseUploadDir, $file, $mimeType, $s3Client );
		}
	}
	echo '<br />';
}

function uploadFile( $filepath, $baseDir, $destination, $mimeType, $s3 )
{
	$realPath = realpath( $filepath );
	$bucket = 'wethinkadventurerocks';
	
	echo 'Uploading File: ' . $baseDir . '/' . $destination . '<br />';
	echo 'Mime: ' . $mimeType . '<br />'; 
	
	// Upload a file.
	$result = $s3->putObject(array(
		'Bucket'       => $bucket,
		'Key'          => $baseDir . '/' . $destination,
		'SourceFile'   => $realPath,
		'ContentType'  => $mimeType,
		'ACL'          => 'public-read',
		'StorageClass' => 'STANDARD'
	));
}
?>