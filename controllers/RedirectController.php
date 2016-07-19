<?php

require_once('Controller.php');
require_once('Request.php');

class RedirectController extends Controller
{    
   function __construct( $url, $config )
   {
       parent::__construct( false, $config );
       $this->url = $url;
   }
    
    public function get( $request )
    {
        header( 'Location:'.$this->url );
    }
}

?>