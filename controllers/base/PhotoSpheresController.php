<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');

class PhotoSpheresController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return '360photos';
	}
	
    public function getTitle()
    {
    	return '360 Photos';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/css/photo_spheres.css', $xtpl );
		
		$this->addCssFile( '/external/pannellum/pannellum.css', $xtpl );
		$this->addJsFile( '/external/pannellum/pannellum.js', $xtpl );
		$xtpl->assign_file('BODY_FILE', 'templates/photo_spheres.html');
		
		$db = getDb();
		$keys = getKeys();
		
		$results = $db->photo_spheres()->select("*")->order('date_taken DESC');
		while( $data = $results->fetch() )
		{
			$xtpl->assign('PHOTO_ID', $data['id']);
			$xtpl->assign('FILE_ID', $data['file_id']);
			
			$xtpl->assign('PHOTO_TITLE', $data['title']);
			$xtpl->assign('PHOTO_DESCRIPTION', $data['description']);
			$xtpl->assign('PHOTO_DATE_TAKEN', $this->formatDateForDisplay( $data['date_taken'] ) );
			
			$mapUrl = $this->getMapUrl( $data['location'], $keys->google_maps_api->key );
			$xtpl->assign('PHOTO_LOCATION_IMG', $mapUrl);
			
			$xtpl->assign('PITCH', $data['initial_pitch'] );
			$xtpl->assign('YAW', $data['initial_yaw'] );
			
			$xtpl->parse('main.body.photo');
			$xtpl->parse('main.body.photo_script');
		}
		
		$xtpl->parse('main.body');
    }
	
	private function getMapUrl( $location, $googleMapsApiKey )
	{
		return "http://maps.googleapis.com/maps/api/staticmap?center=$location&zoom=2&scale=1&size=96x96&maptype=terrain&key=$googleMapsApiKey&format=jpg&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C$location";
	}
}

?>