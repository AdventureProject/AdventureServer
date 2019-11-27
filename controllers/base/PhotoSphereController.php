<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');


class PhotoSphereController extends BaseController
{
	private $currentPhoto;
	
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function getTitle()
    {
    	return '360 Photo';
    }
	
	public function urlStub()
	{
		return '360photo';
	}
	
	public function provideBack()
	{
		return true;
	}
	
	public function getBackUrl()
	{
		return '/360photos';
	}
	
	public function getRichTitle()
	{
		return $this->currentPhoto['title'];
	}
	
	public function getRichDescription()
	{
		return $this->getRichTitle() . ' - ' . $this->currentPhoto['description'];
	}
	
	public function getRichImage()
	{
		$b2BasePath = $GLOBALS['b2BasePath']['360photos'];
		return $b2BasePath . '/' . $this->currentPhoto['file_id'] . '/preview.jpg';
	}
	
	public function getSeoKeywords()
	{
		return parent::getSeoKeywords() . ' 360 panorama photosphere';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$b2BasePath = $GLOBALS['b2BasePath']['360photos'];
		
		$this->addCssFile( '/external/pannellum/pannellum.css', $xtpl );
		$this->addJsFile( '/external/pannellum/pannellum.js', $xtpl );
		
		$this->addCssFile( '/external/sweetalert/sweetalert.css', $xtpl );
		$this->addJsFile( '/external/sweetalert/sweetalert.min.js', $xtpl );
		
		$this->addCssFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.css', $xtpl );
		$this->addJsFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.js', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/photo_sphere.html');
		
		$this->addNavAction( 'info_button', 'info', 'More info about this photo', '#', $xtpl );
		
		$xtpl->assign('B2_BASE_PATH', $b2BasePath );
		
        if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
        {
			$db = getDb();
			
            $requestedPhotoId = $request->args[0];
			if( $requestedPhotoId > 0 && $requestedPhotoId <= $db->photo_spheres->count("*") )
			{
				$photoData = $db->photo_spheres[$requestedPhotoId];
				$this->currentPhoto = $photoData;
				$locationParts = explode( ',', $photoData['location'] );
				$this->addSeoLocation( $locationParts[0], $locationParts[1], $xtpl );

				$this->addNavAction( 'download_button', 'file_download', 'Download this photo', b2GetPublic360Photo($photoData['file_id']), $xtpl, 'download' );
				
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