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
        if( count($request->args) > 0 )
        {
            if( $this->isAuthenticated() )
            {
                if( $request->args[0] == 'home' )
                {
                    $this->renderHome( $xtpl );
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
    
    private function renderHome( $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/admin_home.html');
        
        $db = getDb();
        
        foreach ($db->photos() as $id => $photo)
        {
            $xtpl->assign( 'PHOTO_ID', $id );
            $xtpl->assign( 'FLICKR_ID', $photo['flickr_id'] );
            $xtpl->assign( 'IS_WALLPAPER', $photo['wallpaper'] == 1 ? 'YES' : 'no' );
            $xtpl->assign( 'IS_PHOTOFRAME', $photo['photoframe'] == 1 ? 'YES' : 'no' );
            $xtpl->assign( 'PHOTOWALL_ID', $photo['photowall_id'] );
			
			$xtpl->assign( 'PHOTO_TITLE', substr( $photo['title'], 0, 48 ) );
			$xtpl->assign( 'PHOTO_THUMBNAIL', $photo['thumbnail'] );
			
			$xtpl->assign( 'PHOTO_LOCATION', $photo['location'] );
			
			if( $id % 2 == 0 )
			{
				$xtpl->parse('main.body.photo_row.alt');
			}
			else
			{
				$xtpl->parse('main.body.photo_row.default');
			}
			
			if( !$photo['location'] )
			{
				$xtpl->parse('main.body.photo_row.location_false');
			}
			else
			{
				$xtpl->parse('main.body.photo_row.location_true');
			}
			
            $xtpl->parse('main.body.photo_row');
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