<<<<<<< HEAD
<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class AddPhotoAlbumController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'addphotoalbum';
	}
    
    public function getTitle()
    {
    	return 'Add Photo Album';
    }
    
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo_album.html');
        $xtpl->parse('main.body');
    }
    
    public function post( $request )
    {
        if( !empty($request->post['flickr_album_id']) && is_numeric($request->post['flickr_album_id']) )
        {
			set_time_limit( 2400 );

			error_log('flickr album id provided' );
			$flickrAlbumId = $request->post['flickr_album_id'];

            $db = getDb();
			$keys = getKeys();
			
			$key = $keys->flickr_api->key;
			$secret = $keys->flickr_api->secret;
			$flickr = new Flickr($key, $secret);

			////////////////////////////////////////////////////////
			// Flickr Album Info

			$method = 'flickr.photosets.getInfo';
			$args = array(	'photoset_id' => $flickrAlbumId,
						 	'user_id' => $keys->flickr_api->user_id );

			$responseAlbumInfo = $flickr->call_method($method, $args);

			if( $flickr->ok( $responseAlbumInfo ) )
			{
				error_log('got album info' );
				$photoSetInfo = $responseAlbumInfo['photoset'];
				$albumTitle = $photoSetInfo['title']['_content'];
				$albumDescription = $photoSetInfo['description']['_content'];
				$albumCoverFlickrId = $photoSetInfo['primary'];
				
				$albumItem = array( 'flickr_album_id' => $flickrAlbumId,
									'title' => $albumTitle,
								  	'description' => $albumDescription );

				$existingAlbumResult = $db->albums('flickr_album_id', $flickrAlbumId)->fetch();
				if($existingAlbumResult)
				{
					$localAlbumId = $existingAlbumResult['id'];
				}
				else
				{
					$newAlbumRow = $db->albums()->insert( $albumItem );
					$localAlbumId = $newAlbumRow['id'];
				}

                if( $localAlbumId )
                {
					error_log('created album' );

					////////////////////////////////////////////////////////
					// Flickr Album Photos

					$method = 'flickr.photosets.getPhotos';
					$args = array(	'photoset_id' => $flickrAlbumId,
									'user_id' => $keys->flickr_api->user_id,
								 	'media' => 'photos',
									'perpage' => 500,
									'page' => 1);
					$responseAlbumPhotos = $flickr->call_method($method, $args);
					
					if( $flickr->ok( $responseAlbumPhotos ) )
					{
						error_log('got album photos' );
						$flickrPhotos = $responseAlbumPhotos['photoset']['photo'];
						
						foreach( $flickrPhotos as $flickrPhoto )
						{
							echo 'Creating import task: ' . $flickrPhoto['id'] . '<br />';

							$isCoverPhoto = ($albumCoverFlickrId == $flickrPhoto['id']);

							$importTaskId = createPhotoImport( $flickrPhoto['id'], $flickrAlbumId, $localAlbumId, $isCoverPhoto );
						}
						
						session_write_close();

						$importTasks = $db->photo_import('flickr_album_id', $flickrAlbumId);
						while( $task = $importTasks->fetch() )
						{
							echo 'Importing: ' . $task['flickr_id'] . '<br />';

							$importTaskId = $task['id'];
							processImportTask( $importTaskId );
						}

						echo 'ALL DONE!<br />';
					}
					else
					{
						error_log('FAILED to get album photos' );
					}
				}
			}
        }
    }
}

=======
<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class AddPhotoAlbumController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'addphotoalbum';
	}
    
    public function getTitle()
    {
    	return 'Add Photo Album';
    }
    
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo_album.html');
        $xtpl->parse('main.body');
    }
    
    public function post( $request )
    {
        if( !empty($request->post['flickr_album_id']) && is_numeric($request->post['flickr_album_id']) )
        {
			set_time_limit( 2400 );

			error_log('flickr album id provided' );
			$flickrAlbumId = $request->post['flickr_album_id'];

            $db = getDb();
			$keys = getKeys();
			
			$key = $keys->flickr_api->key;
			$secret = $keys->flickr_api->secret;
			$flickr = new Flickr($key, $secret);

			////////////////////////////////////////////////////////
			// Flickr Album Info

			$method = 'flickr.photosets.getInfo';
			$args = array(	'photoset_id' => $flickrAlbumId,
						 	'user_id' => $keys->flickr_api->user_id );

			$responseAlbumInfo = $flickr->call_method($method, $args);

			if( $flickr->ok( $responseAlbumInfo ) )
			{
				error_log('got album info' );
				$photoSetInfo = $responseAlbumInfo['photoset'];
				$albumTitle = $photoSetInfo['title']['_content'];
				$albumDescription = $photoSetInfo['description']['_content'];
				$albumCoverFlickrId = $photoSetInfo['primary'];
				
				$albumItem = array( 'flickr_album_id' => $flickrAlbumId,
									'title' => $albumTitle,
								  	'description' => $albumDescription );

				$existingAlbumResult = $db->albums('flickr_album_id', $flickrAlbumId)->fetch();
				if($existingAlbumResult)
				{
					$localAlbumId = $existingAlbumResult['id'];
				}
				else
				{
					$newAlbumRow = $db->albums()->insert( $albumItem );
					$localAlbumId = $newAlbumRow['id'];
				}

                if( $localAlbumId )
                {
					error_log('created album' );

					////////////////////////////////////////////////////////
					// Flickr Album Photos

					$method = 'flickr.photosets.getPhotos';
					$args = array(	'photoset_id' => $flickrAlbumId,
									'user_id' => $keys->flickr_api->user_id,
								 	'media' => 'photos',
									'perpage' => 500,
									'page' => 1);
					$responseAlbumPhotos = $flickr->call_method($method, $args);
					
					if( $flickr->ok( $responseAlbumPhotos ) )
					{
						error_log('got album photos' );
						$flickrPhotos = $responseAlbumPhotos['photoset']['photo'];
						
						foreach( $flickrPhotos as $flickrPhoto )
						{
							echo 'Creating import task: ' . $flickrPhoto['id'] . '<br />';

							$isCoverPhoto = ($albumCoverFlickrId == $flickrPhoto['id']);

							$importTaskId = createPhotoImport( $flickrPhoto['id'], $flickrAlbumId, $localAlbumId, $isCoverPhoto );
						}
						
						session_write_close();

						$importTasks = $db->photo_import('flickr_album_id', $flickrAlbumId);
						while( $task = $importTasks->fetch() )
						{
							echo 'Importing: ' . $task['flickr_id'] . '<br />';

							$importTaskId = $task['id'];
							processImportTask( $importTaskId );
						}

						echo 'ALL DONE!<br />';
					}
					else
					{
						error_log('FAILED to get album photos' );
					}
				}
			}
        }
    }
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>