<?php

require_once('Controller.php');
require_once('Request.php');

class ClientController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
    
    public function get( $request )
    {
        if( $request->args[0] == 'windows' )
        {
            header( 'Location: https://github.com/Wavesonics/AdventureWindows/releases' );
        }
        else if( $request->args[0] == 'android' )
        {
            header( 'Location: https://play.google.com/apps/testing/com.darkrockstudios.apps.adventure' );
        }
    }
}

?>