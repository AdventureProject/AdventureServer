<?php

require_once('Config.php');

require_once('PhpInfoController.php');
require_once('PhotoWallController.php');
require_once('RedirectController.php');
require_once('ClientController.php');
require_once('PhotoFrameController.php');
require_once('RandomController.php');
require_once('PeekController.php');
require_once('TodaysWallpaperController.php');
require_once('RootController.php');
require_once('AdminController.php');
require_once('AddPhotoController.php');
require_once('PhotoController.php');
require_once('HealthController.php');
require_once('AppsController.php');
require_once('PhotoFrameErrorLogController.php');

$config = new Config( '/admin' );


$registered_controllers['phpinfo'] = new PhpInfoController( $config );

$registered_controllers['root'] = new RootController( $config );
$registered_controllers['photowall'] = new PhotoWallController( $config );
$registered_controllers['photoframe'] = new PhotoFrameController( $config );
$registered_controllers['client'] = new ClientController( $config );
$registered_controllers['random'] = new RandomController( $config );
$registered_controllers['peek'] = new PeekController( $config );
$registered_controllers['todayswallpaper'] = new TodaysWallpaperController( $config );
$registered_controllers['admin'] = new AdminController( $config );
$registered_controllers['addphoto'] = new AddPhotoController( $config );
$registered_controllers['photo'] = new PhotoController( $config );
$registered_controllers['health'] = new HealthController( $config );
$registered_controllers['apps'] = new AppsController( $config );
$registered_controllers['errorlog'] = new PhotoFrameErrorLogController( $config );

?>