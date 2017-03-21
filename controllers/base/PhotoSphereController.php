<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');

class PhotoSphereController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return '360photo';
	}
	
    public function getTitle()
    {
    	return '360 Photo';
    }
	
	public function provideBack()
	{
		return true;
	}
	
	public function getBackUrl()
	{
		return '/360photos';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/external/pannellum/pannellum.css', $xtpl );
		$this->addJsFile( '/external/pannellum/pannellum.js', $xtpl );
		
		$this->addCssFile( '/external/sweetalert/sweetalert.css', $xtpl );
		$this->addJsFile( '/external/sweetalert/sweetalert.min.js', $xtpl );
		
		$this->addCssFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.css', $xtpl );
		$this->addJsFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.js', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/photo_sphere.html');
		
		$this->addNavAction( 'info_button', 'info', 'More info about this photo', '#', $xtpl );
		
        if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
        {
			$db = getDb();
			
            $requestedPhotoId = $request->args[0];
			if( $requestedPhotoId > 0 && $requestedPhotoId <= $db->photo_spheres->count("*") )
			{
				$photoData = $db->photo_spheres[$requestedPhotoId];
				$xtpl->assign('FILE_ID', $photoData['file_id'] );
				$xtpl->assign('PHOTO_TITLE', $photoData['title'] );
				$xtpl->assign('PHOTO_DESCRIPTION', '<strong>' . $this->formatDateForDisplay( $photoData['date_taken'] ) . '</strong><br />' . $photoData['description'] );
				
				$keys = getKeys();
				$location = str_replace( ' ', '', $photoData['location'] ); // The popup doesn't like spaces
				$mapUrl = $this->getMapUrl( $location, $keys->google_maps_api->key );
				$xtpl->assign('PHOTO_LOCATION_URL', $mapUrl);
				
				$xtpl->assign('PITCH', $photoData['initial_pitch'] );
				$xtpl->assign('YAW', $photoData['initial_yaw'] );
			}
        }
		
		$xtpl->parse('main.body');
    }
	
	private function getMapUrl( $location, $googleMapsApiKey )
	{
		return "http://maps.googleapis.com/maps/api/staticmap?center=$location&zoom=2&scale=1&size=256x96&maptype=terrain&key=$googleMapsApiKey&format=jpg&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C$location";
	}
}

?>