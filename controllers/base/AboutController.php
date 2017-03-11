<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class AboutController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function getTitle()
    {
    	return 'About';
    }
	
	public function getRichTitle()
	{
		return 'Adventure.Rocks';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/css/about.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/about.html');
        $xtpl->parse('main.body');
    }
}

?>