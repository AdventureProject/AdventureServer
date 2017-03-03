<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class RootController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
  
    public function getTitle()
    {
      return 'Photo of the Day';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$xtpl->assign_file('BODY_FILE', 'templates/today.html');
		
		$xtpl->assign('PHOTO_ID',$todaysPhoto->id);
		
        $xtpl->parse('main.body');
    }
}

?>