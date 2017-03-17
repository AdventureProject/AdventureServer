<?php

require_once('Controller.php');

class ClientController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'client';
	}
    
    public function get( $request )
    {
        if( $request->args[0] == 'windows' )
        {
            $downloaded = false;
            
            $url = "http://api.github.com/repos/AdventureProject/AdventureWindows/releases";

            $options  = array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT']));
            $context  = stream_context_create($options);
            $json = file_get_contents($url, false, $context);            

            if( $json )
            {
                $jsonObj = json_decode($json, true);

                if( $jsonObj )
                {
                    foreach( $jsonObj[0]['assets'] as $asset )
                    {
                        if( $this->endsWith( $asset['name'], '.msi') )
                        {
                            $downloadUrl = $asset['browser_download_url'];
                            if( $downloadUrl )
                            {
                                header( "Location: $downloadUrl" );
                                $downloaded = true;
                            }
                            break;
                        }
                    }
                }
            }
            
            if( $downloaded === false )
            {
                //header( 'Location: https://github.com/Wavesonics/AdventureWindows/releases' );
            }
        }
        else if( $request->args[0] == 'android' )
        {
            header( 'Location: https://play.google.com/store/apps/details?id=com.darkrockstudios.apps.adventure' );
        }
    }
    
    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}

?>