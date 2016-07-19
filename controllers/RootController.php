<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class RootController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function get( $request )
    {
        $photo = getTodaysPhoto();
                    
        $xtpl = new XTemplate('templates/today.html');
        $xtpl->assign('IMAGE', $photo->image);
        $xtpl->parse('main');
	    $xtpl->out('main');
    }
}

?>