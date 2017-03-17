<?php

require_once('Config.php');

$config = new Config( '/admin' );

$dir = 'controllers';
$files = dirToArray( $dir );
$registered_controllers = registerDirectory( $files, $dir, $config, array() );

function registerDirectory( $dir, $basePath, $config, $registeredControllers )
{
	$fileExt = '.php';
	
	foreach( $dir as $parentDir => $file )
	{
		if( is_array( $file ) )
		{
			$registeredControllers = registerDirectory( $file, $basePath . '/' . $parentDir, $config, $registeredControllers );
		}
		elseif( endsWith( $file, '.php' ) )
		{
			require_once( $basePath . '/' . $file);
			$className = substr( $file, 0, (strlen($file)-strlen('.php')) );
			$controller = new $className( $config );
			
			if( $controller->isEnabled() )
			{
				$registeredControllers[ $controller->urlStub() ] = $controller;
			}
		}
	}
	
	return $registeredControllers;
}
	
function dirToArray($dir) { 
   
   $result = array(); 

   $cdir = scandir($dir); 
   foreach ($cdir as $key => $value) 
   { 
      if (!in_array($value,array(".",".."))) 
      { 
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) 
         { 
            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value); 
         } 
         else 
         { 
            $result[] = $value; 
         } 
      } 
   } 
   
   return $result; 
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

?>