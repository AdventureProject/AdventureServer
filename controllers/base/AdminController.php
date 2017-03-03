<?php

require_once('controllers/KeysUtil.php');

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class AdminController extends BaseController
{
	private $authPassword;
	
    public function __construct( $config )
    {
        parent::__construct( false, $config );
		
		$keys = getKeys();
		$this->authPassword = $keys->admin->password;
    }
	
    public function getTitle()
    {
    	return 'Admin';
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
                    header('Location:/admin');
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
			
			$xtpl->assign( 'PHOTO_TITLE', substr( $photo['cache_title'], 0, 48 ) );
			$xtpl->assign( 'PHOTO_THUMBNAIL', $photo['cache_thumbnail'] );
			
			$xtpl->assign( 'PHOTO_LOCATION', $photo['cache_location'] );
			
			if( $id % 2 == 0 )
			{
				$xtpl->parse('main.body.photo_row.alt');
			}
			else
			{
				$xtpl->parse('main.body.photo_row.default');
			}
			
			if( !$photo['cache_location'] )
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
        	$photoFlickr = getPhoto( $photo['flickr_id'] );
			updatePhotoCache( $id, $photoFlickr, $db );
			
			usleep( 500000 ); // Wait for half a second so we don't anger Flickr
		}
	}
}

?>