<?php

require_once('BaseController.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class AppsController extends BaseController
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
		
		$this->setup( $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/apps.html');
		
        $xtpl->parse('main.body');
        $xtpl->parse('main');
	    $xtpl->out('main');
    }
}

?>