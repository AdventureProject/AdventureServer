<?php

require_once('Request.php');

abstract class Controller
{
    public function __construct( $requiresAuth, $config )
    {
        $this->requiresAuth = $requiresAuth;
        $this->config = $config;
    }
    
    public function isAuthenticated()
    {
        return (array_key_exists('auth',$_SESSION) && $_SESSION['auth'] === true);
    }
    
    public function enforceAuth()
    {
        $isAuthed = $this->isAuthenticated();
        if( $isAuthed )
        {
            header('Location:'.$this->config->authUrl);
        }
        
        return $isAuthed;
    }
    
    public function handle_request( $request )
    {
        if( $this->requiresAuth && !$this->isAuthenticated() )
        {
            header('Location:'.$this->config->authUrl);
        }
        else
        {
            if( $request->verb === 'GET' )
            {
                $this->get( $request );
            }
            else if( $request->verb === 'POST' )
            {
                $this->post( $request );
            }
            else if( $request->verb === 'PUT' )
            {
                $this->put( $request );
            }
            else if( $request->verb === 'PATCH' )
            {
                $this->patch( $request );
            }
            else if( $request->verb === 'DELETE' )
            {
                $this->delete( $request );
            }
        }
    }
	
	abstract public function urlStub();
	
    public function isEnabled()
    {
        return true;
    }
    
    abstract public function get( $request );
    
    public function post( $request )
    {
        echo 'post unhandled';
    }
    
    public function put( $request )
    {
        echo 'put unhandled';
    }
    
    public function patch( $request )
    {
        echo 'patch unhandled';
    }
    
    public function delete( $request )
    {
        echo 'delete unhandled';
    }
} 

?>