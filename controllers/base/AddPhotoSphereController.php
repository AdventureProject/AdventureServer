<?php

require_once('utils/BaseController.php');

class AddPhotoSphereController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'add360photo';
	}
    
    public function getTitle()
    {
    	return 'Add 360 Photo';
    }
    
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo_sphere.html');
		$this->addJsFile( '/js/add_photo.js', $xtpl );
        $xtpl->parse('main.body');
    }
    
    public function post( $request )
    {
        if( !empty($request->post['flickr_id']) && is_numeric($request->post['flickr_id']) )
        {
            
        }
    }
}

?>