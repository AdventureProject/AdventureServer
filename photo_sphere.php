<<<<<<< HEAD
<?php

require_once('utils/photos.php');
require_once('utils/file_system_util.php');
require_once('vendor/autoload.php');
#require_once('libs/aws_config.php');
require_once('utils/b2_util.php');

#use Aws\Sdk;
#use Aws\S3\S3Client;

#$sdk = new Aws\Sdk( getCredentials() );
#$s3Client = $sdk->createS3();

$b2BucketId = getKeys()->b2->bucket_id;
$basePath = $GLOBALS['b2BasePath']['360photos'];

$script = 'generate.py';

chdir('external/pannellum/tools');

$preview_file = $_FILES['preview_upload']['tmp_name'];
$previewName = $_FILES['preview_upload']['name'];
$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );

$target_file = $_FILES["image_upload"]["tmp_name"];
$image = $_FILES["image_upload"]["name"];
$imageName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $image);

$db = getDb();

$row = $db->photo_spheres( 'file_id = ?', $imageName )->fetch();

if( !empty($preview_file) && !empty($previewName) && !empty($previewExt)
   && !empty($target_file) && !empty($image) && !empty($imageName)
   && !$row )
{
	ob_implicit_flush(true);
	
	if( file_exists('output') )
	{
		echo 'Cleaning old output...';
		cleanUpOutput( 'output' );
	}

	list($previewWidth, $previewHeight) = getimagesize( $preview_file );
	
	if( $previewWidth == 800 && $previewHeight == 600 )
	{
		echo 'Processing image...<br />';
		echo "python $script $target_file\n";
		passthru( "python $script $target_file", $return_code );

		if( $return_code === 0 )
		{
			echo '<br />Image generation complete.<br />';

			echo 'Inserting into Database...<br />';

			$exif = exif_read_data( $target_file );
			$latitude = gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
			$longitude = gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);

			if( array_key_exists( 'DateTime', $exif ) )
			{
				$timestamp = date("Y-m-d H:i:s", strtotime( $exif['DateTime'] ));
			}
			else if( array_key_exists( 'DateTimeOriginal', $exif ) )
			{
				$timestamp = date("Y-m-d H:i:s", strtotime( $exif['DateTimeOriginal'] ));
			}
			else
			{
				echo 'ERROR: Unable to find a DateTime field in photo exif data';
			}

			$item['file_id'] = $imageName;
			$item['title'] = htmlentities( $_POST['title'] );
			$item['description'] = htmlentities( $_POST['description'] );
			$item['location'] = $latitude . ', ' . $longitude;
			$item['date_taken'] = $timestamp;

			$newRow = $db->photo_spheres()->insert( $item );
			if( $newRow )
			{
				echo 'Updating config...<br />';

				$configFile = 'output/config.json';
				$json = json_decode( file_get_contents( $configFile ), true );

				$json['basePath'] = "$basePath/$imageName";
				$json['title'] = $_POST['title'];
				$json['preview'] = "/preview.$previewExt";

				file_put_contents( $configFile, json_encode( $json ) );

				echo 'Uploading to B2...<br />';
				$baseUploadDir = 'data/360photos/' . $imageName;

				// Upload the source image
				$mimeType = mime_content_type( $target_file );
				uploadFile( $target_file, $baseUploadDir, $image, $b2BucketId, $mimeType );

				// Upload the preview image
				$mimeType = mime_content_type( $preview_file );
				$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );
				uploadFile( $preview_file, $baseUploadDir, "preview.$previewExt", $b2BucketId, $mimeType );

				// Upload our processed ouput
				uploadDirectory( 'output', $baseUploadDir, $b2BucketId );

				echo 'Upload complete<br />';

				echo "<a href='/360photo/{$newRow["id"]}'>Go to PhotoSphere</a><br />";

				error_log("PhotoSphere added!");
			}
			else
			{
				error_log("Failed to insert row");
				echo 'Failed to insert row<br />';
			}
		}
		else
		{
			error_log("Image conversion failed: $return_code");
			echo 'Image conversion failed<br />';
		}
	}
	else
	{
		error_log("Preview image MUST be 800x600");
		echo 'Preview image MUST be 800x600<br />';
	}

	error_log("Cleaning up...");
	echo 'Cleaning up...<br />';
	cleanUpOutput( 'output' );
}
else
{
	error_log("Image already added!");
	echo 'Image already added!';
}

ob_implicit_flush(false);

function gps($coordinate, $hemisphere)
{
	for ($i = 0; $i < 3; $i++)
	{
		$part = explode('/', $coordinate[$i]);
		if (count($part) == 1)
		{
			$coordinate[$i] = $part[0];
		}
		else if (count($part) == 2)
		{
			$coordinate[$i] = floatval($part[0])/floatval($part[1]);
		}
		else
		{
			$coordinate[$i] = 0;
		}
	}
	list($degrees, $minutes, $seconds) = $coordinate;
	$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
	return $sign * ($degrees + $minutes/60 + $seconds/3600);
}

function cleanUpOutput( $dir )
{
	delTree( $dir );
}

function delTree( $dir )
{
	if(file_exists($dir))
	{
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach( $files as $file )
		{
			(is_dir( "$dir/$file" )) ? delTree( "$dir/$file", true ) : unlink( "$dir/$file" );
		}

		return rmdir( $dir );
	}
	else
	{
		return false;
	}
}

function uploadDirectory( $curDir, $baseUploadDir, $b2BucketId )
{
	echo 'Directory: ' . $baseUploadDir . '<br />';
	
	$files = dirToArray( $curDir );
	foreach( $files as $parentDir => $file )
	{
		if( is_array( $file ) )
		{
			uploadDirectory( $curDir . '/' . $parentDir, $baseUploadDir . '/' . $parentDir, $b2BucketId );
		}
		else if( $file !== '.' && $file !== '..' )
		{
			$localFilePath = $curDir . '/' . $file;
			$mimeType = mime_content_type( $localFilePath );
			uploadFile( $localFilePath, $baseUploadDir, $file, $b2BucketId, $mimeType );
		}
	}
	echo '<br />';
}

function uploadFile( $filepath, $baseDir, $destinationFileName, $b2BucketId, $mimeType )
{
	$realPath = realpath( $filepath );
	
	echo 'Uploading File: ' . $baseDir . '/' . $destinationFileName . '<br />';
	//echo 'Mime: ' . $mimeType . '<br />';
	
	uploadB2File( $realPath, $baseDir . '/' . $destinationFileName, $b2BucketId );
}
=======
<?php

require_once('utils/photos.php');
require_once('utils/file_system_util.php');
require_once('vendor/autoload.php');
#require_once('libs/aws_config.php');
require_once('utils/b2_util.php');

#use Aws\Sdk;
#use Aws\S3\S3Client;

#$sdk = new Aws\Sdk( getCredentials() );
#$s3Client = $sdk->createS3();

$b2BucketId = getKeys()->b2->bucket_id;
$basePath = $GLOBALS['b2BasePath']['360photos'];

$script = 'generate.py';

chdir('external/pannellum/tools');

$preview_file = $_FILES['preview_upload']['tmp_name'];
$previewName = $_FILES['preview_upload']['name'];
$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );

$target_file = $_FILES["image_upload"]["tmp_name"];
$image = $_FILES["image_upload"]["name"];
$imageName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $image);

$db = getDb();

$row = $db->photo_spheres( 'file_id = ?', $imageName )->fetch();

if( !empty($preview_file) && !empty($previewName) && !empty($previewExt)
   && !empty($target_file) && !empty($image) && !empty($imageName)
   && !$row )
{
	ob_implicit_flush(true);
	
	if( file_exists('output') )
	{
		echo 'Cleaning old output...';
		cleanUpOutput( 'output' );
	}

	list($previewWidth, $previewHeight) = getimagesize( $preview_file );
	
	if( $previewWidth == 800 && $previewHeight == 600 )
	{
		echo 'Processing image...<br />';
		echo "python $script $target_file\n";
		passthru( "python $script $target_file", $return_code );

		if( $return_code === 0 )
		{
			echo '<br />Image generation complete.<br />';

			echo 'Inserting into Database...<br />';

			$exif = exif_read_data( $target_file );
			$latitude = gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
			$longitude = gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);

			if( array_key_exists( 'DateTime', $exif ) )
			{
				$timestamp = date("Y-m-d H:i:s", strtotime( $exif['DateTime'] ));
			}
			else if( array_key_exists( 'DateTimeOriginal', $exif ) )
			{
				$timestamp = date("Y-m-d H:i:s", strtotime( $exif['DateTimeOriginal'] ));
			}
			else
			{
				echo 'ERROR: Unable to find a DateTime field in photo exif data';
			}

			$item['file_id'] = $imageName;
			$item['title'] = htmlentities( $_POST['title'] );
			$item['description'] = htmlentities( $_POST['description'] );
			$item['location'] = $latitude . ', ' . $longitude;
			$item['date_taken'] = $timestamp;

			$newRow = $db->photo_spheres()->insert( $item );
			if( $newRow )
			{
				echo 'Updating config...<br />';

				$configFile = 'output/config.json';
				$json = json_decode( file_get_contents( $configFile ), true );

				$json['basePath'] = "$basePath/$imageName";
				$json['title'] = $_POST['title'];
				$json['preview'] = "/preview.$previewExt";

				file_put_contents( $configFile, json_encode( $json ) );

				echo 'Uploading to B2...<br />';
				$baseUploadDir = 'data/360photos/' . $imageName;

				// Upload the source image
				$mimeType = mime_content_type( $target_file );
				uploadFile( $target_file, $baseUploadDir, $image, $b2BucketId, $mimeType );

				// Upload the preview image
				$mimeType = mime_content_type( $preview_file );
				$previewExt = pathinfo( $previewName, PATHINFO_EXTENSION );
				uploadFile( $preview_file, $baseUploadDir, "preview.$previewExt", $b2BucketId, $mimeType );

				// Upload our processed ouput
				uploadDirectory( 'output', $baseUploadDir, $b2BucketId );

				echo 'Upload complete<br />';

				echo "<a href='/360photo/{$newRow["id"]}'>Go to PhotoSphere</a><br />";

				error_log("PhotoSphere added!");
			}
			else
			{
				error_log("Failed to insert row");
				echo 'Failed to insert row<br />';
			}
		}
		else
		{
			error_log("Image conversion failed: $return_code");
			echo 'Image conversion failed<br />';
		}
	}
	else
	{
		error_log("Preview image MUST be 800x600");
		echo 'Preview image MUST be 800x600<br />';
	}

	error_log("Cleaning up...");
	echo 'Cleaning up...<br />';
	cleanUpOutput( 'output' );
}
else
{
	error_log("Image already added!");
	echo 'Image already added!';
}

ob_implicit_flush(false);

function gps($coordinate, $hemisphere)
{
	for ($i = 0; $i < 3; $i++)
	{
		$part = explode('/', $coordinate[$i]);
		if (count($part) == 1)
		{
			$coordinate[$i] = $part[0];
		}
		else if (count($part) == 2)
		{
			$coordinate[$i] = floatval($part[0])/floatval($part[1]);
		}
		else
		{
			$coordinate[$i] = 0;
		}
	}
	list($degrees, $minutes, $seconds) = $coordinate;
	$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
	return $sign * ($degrees + $minutes/60 + $seconds/3600);
}

function cleanUpOutput( $dir )
{
	delTree( $dir );
}

function delTree( $dir )
{
	if(file_exists($dir))
	{
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach( $files as $file )
		{
			(is_dir( "$dir/$file" )) ? delTree( "$dir/$file", true ) : unlink( "$dir/$file" );
		}

		return rmdir( $dir );
	}
	else
	{
		return false;
	}
}

function uploadDirectory( $curDir, $baseUploadDir, $b2BucketId )
{
	echo 'Directory: ' . $baseUploadDir . '<br />';
	
	$files = dirToArray( $curDir );
	foreach( $files as $parentDir => $file )
	{
		if( is_array( $file ) )
		{
			uploadDirectory( $curDir . '/' . $parentDir, $baseUploadDir . '/' . $parentDir, $b2BucketId );
		}
		else if( $file !== '.' && $file !== '..' )
		{
			$localFilePath = $curDir . '/' . $file;
			$mimeType = mime_content_type( $localFilePath );
			uploadFile( $localFilePath, $baseUploadDir, $file, $b2BucketId, $mimeType );
		}
	}
	echo '<br />';
}

function uploadFile( $filepath, $baseDir, $destinationFileName, $b2BucketId, $mimeType )
{
	$realPath = realpath( $filepath );
	
	echo 'Uploading File: ' . $baseDir . '/' . $destinationFileName . '<br />';
	//echo 'Mime: ' . $mimeType . '<br />';
	
	uploadB2File( $realPath, $baseDir . '/' . $destinationFileName, $b2BucketId );
}
>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>