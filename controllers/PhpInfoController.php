<?php

require_once('Controller.php');
require_once('Request.php');

class PhpInfoController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
    
    public function get( $request )
    {
        echo date('c');
        echo phpinfo();
    }
}

?>