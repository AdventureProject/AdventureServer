<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class AdminController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function get( $request )
    {
        $photo = getTodaysPhoto();
                    
        $xtpl = new XTemplate('templates/base.html');
        $xtpl->assign('IMAGE', $photo->image);
        
        if( count($request->args) > 0 )
        {
            if( $this->isAuthenticated() )
            {
                if( $request->args[0] == 'home' )
                {
                    $this->renderHome( $xtpl );
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
        
        $xtpl->parse('main');
	    $xtpl->out('main');
    }
    
    public function post( $request )
    {
        if( count($request->args) > 0 )
        {
            if( $request->args[0] == 'authenticate' )
            {
                if( isset($request->post['auth']) && $request->post['auth'] == '3.14159!' )
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
            $xtpl->parse('main.body.photo_row');
        }
        
        $xtpl->parse('main.body');
    }
}

?>