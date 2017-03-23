<?php

require_once('utils/BaseController.php');

class RootController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'root';
	}
  
    public function getTitle()
    {
      return 'Photo of the Day';
    }
	
	public function getRichTitle()
	{
		return 'Adventure.Rocks';
	}
	
	public function blurBackground()
	{
		return false;
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/css/today.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/today.html');
		
		$this->addNavAction( 'about', 'help', 'What is this site?', '/about', $xtpl );
		
		$xtpl->assign('PHOTO_ID',$todaysPhoto->id);
		
        $xtpl->parse('main.body');
    }
}

?>