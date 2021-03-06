<?php

require_once('utils/KeysUtil.php');
require_once('utils/b2_util.php');
require_once('utils/photos.php');

class ApiController extends Controller
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}

	public function urlStub()
	{
		return 'api';
	}

	public function get( $request )
	{
		$outputData = [];

		if( $request->args[0] == '360photos' )
		{
			$b2BasePath = $GLOBALS['b2BasePath']['360photos'];

			$keys = getKeys();
			$db = getDb();
			$results = $db->photo_spheres()->select("*")->where('highlight', 1)->order('date_taken DESC');
			$outputData["photospheres"] = [];
			while( $data = $results->fetch() )
			{
				$id = $data['id'];
				$fileId = $data['file_id'];
				$location = $data['location'];
				$mapUrl = getPhotoSphereMapLargeUrl( $location, $keys->google_maps_api->key );

				$photoData['id'] = $id;
				$photoData['title'] = $data['title'];
				$photoData['description'] = $data['description'];
				$photoData['date_taken'] = $data['date_taken'];
				$photoData['location'] = $data['location'];
				$photoData['map'] = $mapUrl;
				$photoData['photo_url'] = b2GetPublic360Photo($fileId);
				$photoData['preview'] = b2GetPublic360PhotoPreview($fileId);
				$photoData['config_file'] = "$b2BasePath/$fileId/config.json";

				$outputData["photospheres"][] = $photoData;
			}
		}
		else
		{
			$outputData["error"] = "unknown endpoint";
		}

		header('Content-type: application/json');
		echo json_encode($outputData, JSON_NUMERIC_CHECK);
	}
}