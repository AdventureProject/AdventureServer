<?php

require_once('utils/BaseController.php');

class PeekController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'peek';
	}
	
    public function getTitle()
    {
    	return 'Peek';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $daysForward = 1;
        if( count($request->args) == 1 && is_numeric( $request->args[0] ) && $request->args[0] >= 0 )
        {
            $daysForward = $request->args[0];
        }
		
        $dayOfYear = date("z");
        $photo = getPhotoForDay($dayOfYear+$daysForward);
		$xtpl->assign('IMAGE', $photo->image);

		$this->addCssFile( '/css/today.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/today.html');
		
		$this->addNavAction( 'about', 'help', 'What is this site?', '/about', $xtpl );
		
		$xtpl->assign('PHOTO_ID',$photo->id);
		
        $xtpl->parse('main.body');
    }
}

?>