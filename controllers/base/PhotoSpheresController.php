<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');
require_once('utils/photos.php');


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
	
	public function getRichDescription()
	{
		return 'A selection of our best photos 360 Photos';
	}
	
	public function getSeoKeywords()
	{
		return parent::getSeoKeywords() . ' 360 panorama photosphere';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$b2BasePath = $GLOBALS['b2BasePath']['360photos'];
		
		$this->addCssFile( '/css/photo_spheres.css', $xtpl );
		
		$this->addCssFile( '/external/pannellum/pannellum.css', $xtpl );
		$this->addJsFile( '/external/pannellum/pannellum.js', $xtpl );
		$xtpl->assign_file('BODY_FILE', 'templates/photo_spheres.html');
		
		$db = getDb();
		$keys = getKeys();
		
		$xtpl->assign('B2_BASE_PATH', $b2BasePath);
		
		$results = $db->photo_spheres()->select("*")->where('highlight', 1)->order('date_taken DESC');
		while( $data = $results->fetch() )
		{
			$xtpl->assign('PHOTO_ID', $data['id']);
			$xtpl->assign('FILE_ID', $data['file_id']);
			
			$xtpl->assign('PHOTO_TITLE', $data['title']);
			$xtpl->assign('PHOTO_DESCRIPTION', $data['description']);
			$xtpl->assign('PHOTO_DATE_TAKEN', $this->formatDateForDisplay( $data['date_taken'] ) );
			
			$mapUrl = getPhotoSphereMapUrl( $data['location'], $keys->google_maps_api->key );
			$xtpl->assign('PHOTO_LOCATION_IMG', $mapUrl);
			
			$xtpl->assign('PITCH', $data['initial_pitch'] );
			$xtpl->assign('YAW', $data['initial_yaw'] );
			
			$xtpl->parse('main.body.photo');
			$xtpl->parse('main.body.photo_script');
		}
		
		$xtpl->parse('main.body');
    }
}

?>