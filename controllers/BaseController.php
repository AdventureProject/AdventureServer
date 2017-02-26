<?php

require_once('Controller.php');

abstract class BaseController extends Controller
{
    public function setup( $xtpl )
    {
        if( $this->isAuthenticated() )
        {
            $xtpl->parse('main.authenticated');
        }
    }
}

?>