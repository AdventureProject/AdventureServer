<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class AppsController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function getTitle()
    {
    	return 'Apps';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$xtpl->assign_file('BODY_FILE', 'templates/apps.html');
        $xtpl->parse('main.body');
    }
}

?>