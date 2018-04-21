<?php

require_once('utils/photos.php');

$geojson = array(
   'type'      => 'FeatureCollection',
   'features'  => array()
);

$db = getDb();
$row = $db->photos()->select('id, flickr_id, date_taken, location, title, description')->order("id ASC");

while( $photo = $row->fetch() )
{
	$locationParts = explode( ',', $photo['location'] );
	$lat = trim( $locationParts[0] );
	$lon = trim( $locationParts[1] );
	
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
			'photo_url' => 'http://wethinkadventure.rocks/photo/' . $photo['id']
            )
        );
    # Add feature arrays to feature collection array
    array_push($geojson['features'], $feature);
}

header('Content-type: application/json');
echo json_encode($geojson, JSON_NUMERIC_CHECK);

?>