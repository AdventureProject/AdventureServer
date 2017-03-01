<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class PeekController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
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

		$xtpl->assign_file('BODY_FILE', 'templates/today.html');
    }
}

?>