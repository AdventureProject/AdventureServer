<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class PeekController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function get( $request )
    {
        $daysForward = 1;
        if( count($request->args) == 1 && is_numeric( $request->args[0] ) && $request->args[0] >= 0 )
        {
            $daysForward = $request->args[0];
        }
		
        $dayOfYear = date("z");
        $photo = getPhotoForDay($dayOfYear+$daysForward);
                    
        $xtpl = new XTemplate('templates/today.html');
        $xtpl->assign('IMAGE', $photo->image);
        $xtpl->parse('main');
	    $xtpl->out('main');
    }
}

?>