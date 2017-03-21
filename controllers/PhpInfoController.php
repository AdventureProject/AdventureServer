<?php

require_once('Controller.php');

class PhpInfoController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'phpinfo';
	}
	
    public function isEnabled()
    {
        return true;
    }
    
    public function get( $request )
    {
        echo date('c');
        echo phpinfo();
    }
}

?>