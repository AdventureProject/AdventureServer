<?php

session_name("all_subdomains");
session_start();

require_once('Request.php');
require_once('Controller.php');

$registered_controllers = array();
require_once('register.php');

$request = new Request();

$request->verb = $_SERVER['REQUEST_METHOD'];

$url_elements = array();
if( isset($_GET['rest']) )
{
    $url_elements = explode('/', $_GET['rest']);
}

$request->params = $_GET;
$request->post = $_POST;

if( count( $url_elements ) > 0 )
{
    $request->controller = $url_elements[0];
}
else
{
    $request->controller = 'root';
}

if( count( $url_elements ) > 1 )
{
    for( $ii=1; $ii<count($url_elements); ++$ii )
    {
        if( !empty($url_elements[$ii]) )
        {
            $request->args[] = $url_elements[$ii];
        }
    }
}

if( isset($registered_controllers[$request->controller]) )
{
    $registered_controllers[$request->controller]->handle_request( $request );
}
else
{
    echo 'No controller for: ' . $request->controller;
}

?>