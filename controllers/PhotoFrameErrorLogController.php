<?php

require_once('Controller.php');

require_once('utils/photos.php');

class PhotoFrameErrorLogController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'errorlog';
	}
	
	public function get( $request )
    {
		if( count($request->args) == 1 )
        {
			if( is_numeric( $request->args[0] ) )
			{
				$db = getDb();
				$row = $db->health_monitor[ $request->args[0] ];
				
				header("Content-Type: text/plain");
				echo $row['errors'];
			}
		}
	}
}

?>