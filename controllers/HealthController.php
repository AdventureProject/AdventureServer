<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

class HealthController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function get( $request )
    {
        echo 'error';
    }
    
    public function post( $request )
    {
        if( count($request->args) == 1 && is_numeric($request->args[0]) )
        {
            $photoFrameId = $request->args[0];

            $errors = "no";
            if( !empty($request->post) )
            {
                $errors = $request->post;
            }
            
            $item['photo_frame'] = $photoFrameId;
            $item['errors'] = $errors;
            
            $db = getDb();
            $row = $db->health_monitor()->insert( $item );

            echo $row;
        }
    }
}

?>