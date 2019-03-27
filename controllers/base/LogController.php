<?php

require_once('utils/BaseController.php');
require_once('libs/Parsedown.php');

class LogController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'log';
	}
	
    public function getTitle()
    {
    	return 'Log';
    }
	
	public function getRichTitle()
	{
		return 'Adventure.Rocks - Log';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		//$this->addCssFile( '/css/about.css', $xtpl );
		
		$parsedown = new Parsedown();
		
		
		$markdownText = "**test** this _shit_\n# Header";
		$bodyText = $parsedown->text( $markdownText );
		
		$xtpl->assign_file('BODY_FILE', 'templates/log.html');
		$xtpl->assign('MARK_DOWN', $bodyText);
        $xtpl->parse('main.body');
    }
}

?>