<?php

require_once('utils/BaseController.php');

class HeatmapController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'heatmap';
	}
	
    public function getTitle()
    {
    	return 'Heatmap';
    }
	
	public function getRichTitle()
	{
		return 'Adventure.Rocks - ' . $this->getTitle();
	}

    public function getRichDescription()
    {
        return "A heatmap showing where in the world we've taken photos";
    }

    public function getRichImage()
    {
        return "http://wethinkadventure.rocks/images/heatmap_preview.jpg";
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		if( $request->params['action'] == 'regenerate' )
		{
			if( $this->enforceAuth() )
			{
				// This is a dirty dirty hack!! We need an actual way to post to this controller
				$this->regenerateGeoJson();
				exit();
			}
		}
		else
		{
			$this->addCssFile( 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.44.2/mapbox-gl.css', $xtpl );
			$this->addJsFile( 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.44.2/mapbox-gl.js', $xtpl );
			$this->addCssFile( '/css/heatmap.css', $xtpl );

			$xtpl->assign_file('BODY_FILE', 'templates/heatmap.html');
			$xtpl->parse('main.body');
		}
    }
	
	public function post( $request )
	{
		if( $this->enforceAuth() )
		{
			$this->regenerateGeoJson();
		}
	}
	
	private function regenerateGeoJson()
	{
		$geojson = array(
		   'type'      => 'FeatureCollection',
		   'features'  => array()
		);

		$dbPdo = getDbPdo();
        $results = $dbPdo->query(
            "SELECT photos.id, photos.date_taken, photos.location, photos.title, photos.description, album_photos.albums_id
                        FROM 
                            photos
                            LEFT JOIN 
                                album_photos
                                ON album_photos.photos_id = photos.id
                                AND album_photos.id IN
                                    (SELECT MAX(id)
                                     FROM album_photos
                                     GROUP BY album_photos.id)
                        ORDER BY photos.date_taken ASC" );

		while( $photo = $results->fetch() )
		{
			if( !empty($photo['location']) && $photo['location'] != ',' )
			{
				$locationParts = explode( ',', $photo['location'] );
				$lat = trim( $locationParts[0] );
				$lon = trim( $locationParts[1] );

				$hasAlbum = !empty($photo['albums_id']);

				$feature = array(
					'id' => $photo['id'],
					'type' => 'Feature', 
					'geometry' => array(
						'type' => 'Point',
						# Pass Longitude and Latitude Columns here
						'coordinates' => array($lon, $lat)
					),
					# Pass other attribute columns here
					'properties' => array(
						'name' => $photo['title'],
						'description' => $photo['description'],
						'date' => $photo['date_taken'],
						'id' => $photo['id'],
                        'album_id' => $hasAlbum ? $photo['albums_id'] : null,
						'thumbnail_url' => b2GetPublicThumbnailUrl($photo['id']),
						'photo_url' => 'http://wethinkadventure.rocks/photo/' . $photo['id'] . ( $hasAlbum ? '/album/'.$photo['albums_id'] : '' )
						)
					);
				# Add feature arrays to feature collection array
				array_push($geojson['features'], $feature);
			}
		}

        $dbPdo = null;

		$geoJsonStr = json_encode($geojson, JSON_NUMERIC_CHECK);

		var_dump( file_put_contents('data/photos_geojson.json', $geoJsonStr) );

		//header('Content-type: application/json');
		//echo $geoJsonStr;
	}
}

?>