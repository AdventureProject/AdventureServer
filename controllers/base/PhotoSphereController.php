<?php

require_once('utils/BaseController.php');

class PhotoSphereController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return '360photo';
	}
	
    public function getTitle()
    {
    	return '360 Photo';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/external/pannellum/pannellum.css', $xtpl );
		$this->addJsFile( '/external/pannellum/pannellum.js', $xtpl );
		$xtpl->assign_file('BODY_FILE', 'templates/photo_sphere.html');
		
		$xtpl->parse('main.body');
    }
}

?>