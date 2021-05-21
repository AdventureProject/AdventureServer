<?php

require_once('utils/BaseController.php');

class AppsController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'apps';
	}
	
    public function getTitle()
    {
    	return 'Apps';
    }
	
	public function getRichDescription()
	{
		return 'Apps to automatically set your wallpaper to the Photo of the Day';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/css/apps.css', $xtpl );
		$xtpl->assign_file('BODY_FILE', 'templates/apps.html');
        $xtpl->parse('main.body');
    }
}

?>