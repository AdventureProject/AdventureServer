<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');

class AdminController extends BaseController
{
	private $authPassword;
	
    public function __construct( $config )
    {
        parent::__construct( false, $config );
		
		$keys = getKeys();
		$this->authPassword = $keys->admin->password;
    }
	
	public function urlStub()
	{
		return 'admin';
	}
	
    public function getTitle()
    {
    	return 'Admin';
    }
	
	public function getSeoRobots()
	{
		return 'noindex, nofollow';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$subdomain = explode('.', $_SERVER['HTTP_HOST'])[0];
		if( $subdomain != 'admin' )
		{
			header('Location: http://admin.wethinkadventure.rocks/admin');
			exit();
		}
		
		if( is_numeric( $request->params['updatealbum'] ) )
		{
			$albumId = $request->params['updatealbum'];

			error_log( 'Updating album: ' . $albumId );

			updateAlbumInfo( $albumId );
			updateAlbumPhotos( $albumId );

			header("Location:/admin/home?browse=album?album_id=$albumId");
		}
        else if( count($request->args) > 0 )
        {
            if( $this->isAuthenticated() )
            {
                if( $request->args[0] == 'home' )
                {
                    $this->renderHome( $xtpl, $request );
                }
				else if( $request->args[0] == 'refreshcache' )
                {
                    $this->refreshCache();
                    header('Location:/admin');
                }
                else if( $request->args[0] == 'signout' )
                {
                    session_destroy();
					
					$url = (array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER']  : "");
					if( $this->checkRootDomain( $url ) == false )
					{
						$url = '/admin';
					}
                    header("Location:$url");
                }
            }
            else
            {
                header('Location:/admin');
            }
        }
        else
        {
            if( $this->isAuthenticated() )
            {
                header('Location:/admin/home');
            }
            else
            {
				$this->addCssFile( '/css/admin_auth.css', $xtpl );
				$this->addJsFile( '/js/admin_auth.js', $xtpl );
				$xtpl->assign_file('BODY_FILE', 'templates/admin_auth.html');
				$xtpl->parse('main.body');
            }
        }
    }
    
    public function post( $request )
    {
        if( count($request->args) > 0 )
        {
            if( $request->args[0] == 'authenticate' )
            {
                if( isset($request->post['auth']) && $request->post['auth'] == $this->authPassword )
                {
                    $_SESSION['auth'] = true;
                    header('Location:/admin/home');
                }
                else
                {
                    echo 'Great failure';
                }
            }
        }
    }
	
    private function renderHome( $xtpl, $request )
    {
		$this->addCssFile( '/css/admin_home.css', $xtpl );
		
        $xtpl->assign_file('BODY_FILE', 'templates/admin_home.html');
        
        $db = getDb();
		$pdo = getDbPdo();

		$totalPhotoFrame = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `photoframe` = 1")->fetch()['total'];
		$totalWallpaper = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `wallpaper` = 1")->fetch()['total'];
		$totalPhotoWall = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `photowall_id` IS NOT NULL")->fetch()['total'];
		$totalMissingLocation = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `location` = ',' OR `location` IS NULL")->fetch()['total'];
		$totalPhotos = $pdo->query("SELECT COUNT(id) AS total FROM `photos`")->fetch()['total'];
		$totalAlbums = $pdo->query("SELECT COUNT(id) AS total FROM `albums`")->fetch()['total'];

		$pdo = null;

        $xtpl->assign( 'TOTAL_PHOTO_FRAME_PHOTOS', $totalPhotoFrame );
		$xtpl->assign( 'TOTAL_PHOTO_WALL_PHOTOS', $totalPhotoWall );
		$xtpl->assign( 'TOTAL_WALLPAPER_PHOTOS', $totalWallpaper );
		$xtpl->assign( 'TOTAL_MISSING_LOCATION', $totalMissingLocation );
		$xtpl->assign( 'TOTAL_PHOTOS', $totalPhotos );
		$xtpl->assign( 'TOTAL_ALBUMS', $totalAlbums );

		$browseType = array_key_exists('browse', $request->params) ? $request->params['browse'] : null;
		$albumId = array_key_exists('album_id', $request->params) ? $request->params['album_id'] : null;
		
		$results = null;
		if( $browseType == 'wallpaper' )
		{
			$results = $db->photos("wallpaper = ?", 1);
		}
		else if( $browseType == 'photowall' )
		{
			$results = $db->photos("photowall_id IS NOT NULL");
		}
		else if( $browseType == 'photoframe' )
		{
			$results = $db->photos("photoframe = ?", 1);
		}
		else if( $browseType == 'missing_location' )
		{
			$results = $db->photos()->select('*')->where('location = ?', ',')->or('location IS NULL')->order('date_taken DESC');
		}
		else if( $browseType == 'album' )
		{
			//album_id
			$xtpl->assign( 'ALBUM_ID', $albumId );
			$xtpl->parse( 'main.body.update_album' );
			
			$results = $db->photos("location = ?", ',');
			$results = $db->photos()->select('photos.*')->where('album_photos:albums_id', $albumId)->order('date_taken ASC');
		}

		if( $results != null )
		{
			$resultCount = $results->count();
			$xtpl->assign( 'BROWSE_COUNT', $resultCount );
			foreach( $results as $id => $photo )
			{
				$xtpl->assign( 'PHOTO_ID', $id );
				$xtpl->assign( 'FLICKR_ID', $photo['flickr_id'] );
				$xtpl->assign( 'IS_WALLPAPER', $photo['wallpaper'] == 1 ? 'YES' : 'no' );
				$xtpl->assign( 'IS_PHOTOFRAME', $photo['photoframe'] == 1 ? 'YES' : 'no' );
				$xtpl->assign( 'PHOTOWALL_ID', $photo['photowall_id'] );

				$xtpl->assign( 'PHOTO_TITLE', substr( $photo['title'], 0, 48 ) );
				$xtpl->assign( 'PHOTO_THUMBNAIL', b2GetPublicThumbnailUrl( $id ) );

				$xtpl->assign( 'PHOTO_LOCATION', $photo['location'] );

				if( $id % 2 == 0 )
				{
					$xtpl->parse( 'main.body.photo_row.alt' );
				}
				else
				{
					$xtpl->parse( 'main.body.photo_row.default' );
				}

				if( !$photo['location'] )
				{
					$xtpl->parse( 'main.body.photo_row.location_false' );
				}
				else
				{
					$xtpl->parse( 'main.body.photo_row.location_true' );
				}

				$xtpl->parse( 'main.body.photo_row' );
			}
		}
        
        $xtpl->parse('main.body');
    }
	
	private function refreshCache()
	{
		$db = getDb();
        
        foreach ($db->photos() as $id => $photo)
        {
        	$photoFlickr = getPhoto( $photo['flickr_id'], $id );
			updatePhotoCache( $id, $photoFlickr, $db );
			
			usleep( 500000 ); // Wait for half a second so we don't anger Flickr
		}
	}
}

?>