<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');


class PhotoFrameErrorLogController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function get( $request )
    {
		if( count($request->args) == 1 )
        {
			if( is_numeric( $request->args[0] ) )
			{
				$db = getDb();
				$row = $db->health_monitor[ $request->args[0] ];
				echo "<pre>{$row['errors']}</pre>";
			}
		}
	}
}

?>