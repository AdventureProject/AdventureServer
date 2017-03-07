<?php

require_once('Controller.php');

abstract class BaseController extends Controller
{
    public function get( $request )
    {
        $todaysPhoto = getTodaysPhoto();
        
        $xtpl = new XTemplate('templates/base.html');
        $xtpl->assign('IMAGE', $todaysPhoto->image);
        $xtpl->assign('TITLE', $this->getTitle());
		
        if( $this->isAuthenticated() )
        {
            $xtpl->parse('main.authenticated');
        }
        
        $this->getBody( $request, $todaysPhoto, $xtpl );
        
        $xtpl->parse('main');
		$xtpl->out('main');
    }
  
    abstract public function getTitle();
    
    abstract public function getBody( $request, $todaysPhoto, $xtpl );
}

?>