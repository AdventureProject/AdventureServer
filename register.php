<?php

require_once('utils/file_system_util.php');

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

?>