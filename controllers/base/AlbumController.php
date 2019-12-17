<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');
require_once('utils/maps.php');

use Polyline;

use phpGPX\phpGPX;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;

class AlbumController extends BaseController
{
	private $googleMapsApiKey;
	private $googleMapsApiSecret;

	private $currentAlbumId = null;
	private $currentAlbumData = null;

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$keys = getKeys();

		$this->googleMapsApiKey = $keys->google_maps_api->key;
		$this->googleMapsApiSecret = $keys->google_maps_api->secret;
	}

	public function urlStub()
	{
		return 'album';
	}

	public function getTitle()
	{
		return 'Album' . ' - ' . $this->currentAlbumData['title'];
	}

	public function getRichTitle()
	{
		return $this->getTitle();
	}

	public function getRichDescription()
	{
		return $this->currentAlbumData['description'];
	}

	public function getRichImage()
	{
		return b2GetPublicThumbnailUrl( $this->currentAlbumData['cover_photo_id'] );
	}

	public function getBackUrl()
	{
		return '/albums';
	}

	public function provideBack()
	{
		return true;
	}

	public function get( $request )
	{
		$db = getDb();

		$this->currentAlbumId = $this->getAlbumId( $request );
		$this->currentAlbumData = $db->albums[ $this->currentAlbumId ];

		parent::get( $request );
	}

	private function getAlbumId( $request )
	{
		$albumId = null;
		if( count( $request->args ) == 1 && is_numeric( $request->args[0] ) )
		{
			$albumId = $request->args[0];
		}

		return $albumId;
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/album.css', $xtpl );
		$this->addCssFile( '/css/album_container.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );

		$this->addLazyLoadLibrary( $xtpl );

		$xtpl->assign_file( 'BODY_FILE', 'templates/album.html' );

		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
		{
			$db = getDb();
			$album = $db->albums[ $albumId ];

			$gpsPoints = null;
			$geoDataRow = $db->album_tracks->select( '*' )->where( 'album_id', $albumId )->fetch();
			if( $geoDataRow )
			{
				$rawGpx = $geoDataRow['gpx'];
				$rawGpx = trim( $rawGpx );

				if( !empty( $rawGpx ) )
				{
					$gpxParser = new phpGPX();
					$gpx = $gpxParser->parse( $rawGpx );
					$gpsPoints = array();

					$distanceMiles = 0;
					$elevationFeet = 0;
					foreach( $gpx->tracks as $track )
					{
						// Statistics for whole track
						$stats = $track->stats->toArray();
						//print_r($stats);

						// Convert meters to feet
						$elevationMeters = $stats['cumulativeElevationGain'];
						$elevationFeet += round( $elevationMeters * 3.28084 );

						// Convert meters to miles
						$distanceMeters = $stats['distance'];
						$distanceMiles += round( $distanceMeters * 0.000621371 );

						foreach( $track->segments as $segment )
						{
							// Statistics for segment of track
							#$segment->stats->toArray();
							foreach( $segment->points as $point )
							{
								$gpsPoints[] = array(
									array( $point->latitude, $point->longitude ),
									array( $point->elevation, $point->time )
								);
							}
						}
					}

					$overviewPoints = $this->downSample( $gpsPoints, 5000 );
					$overviewMapUrl = $this->getMapUrl( $overviewPoints, 640, 400 );

					$xtpl->assign( 'ALBUM_MAP_OVERVIEW', $overviewMapUrl );
					$xtpl->assign( 'ALBUM_MAP_DISTANCE', "$distanceMiles miles" );
					$xtpl->assign( 'ALBUM_MAP_ELEVATION_GAIN', "$elevationFeet feet" );

					$xtpl->parse( 'main.body.map' );
					//echo $gpx->metadata->time->format('Y-m-d H:i:s') . '<br />';
					//echo 'Description: '.$gpx->metadata->description . '<br />';
				}
			}

			$xtpl->assign( 'ALBUM_ID', $albumId );

			if( $this->isAuthenticated() && array_key_exists( 'regenerate', $request->params ) )
			{
				if( $request->params['regenerate'] == 'info' )
				{
					error_log( 'regenerate albumInfo ' . $albumId );

					updateAlbumInfo( $albumId );
				}
				elseif( $request->params['regenerate'] == 'photoinfo' )
				{
					updateAlbumPhotos( $albumId );
				}
				elseif( $request->params['regenerate'] == 'timelinemode' )
				{
					$this->updateTimelineMode( $albumId );
				}

				$stub = $this->urlStub();
				header( "Location: /$stub/$albumId" );
			}
			// If not authenticated, and album is not published, render 404
			elseif( !$this->isAuthenticated() && $album['is_published'] == 0 )
			{
				$this->addCssFile( '/css/not_found.css', $xtpl );
				$xtpl->assign_file( 'BODY_FILE', 'templates/photo_not_found.html' );
			}
			// Normal album render
			else
			{
				$pstDate = utcToPst( $album['date'], 'Y-m-d' );
				$pstTime = utcToPst( $album['date'], 'H:i:s' );
				$xtpl->assign( 'ANNOTATION_DATE', $pstDate );
				$xtpl->assign( 'ANNOTATION_TIME', $pstTime );

				$coverPhoto = getPhotoById( $album['cover_photo_id'], true, 1024, 768 );

				$timeLineMode = $album['timeline_mode'];
				if( array_key_exists( 'timeline', $request->params )
					&& is_numeric( $request->params['timeline'] )
					&& $request->params['timeline'] > 0 & $request->params['timeline'] < 3 )
				{
					$timeLineMode = $request->params['timeline'];
				}

				switch( $timeLineMode )
				{
					case 0:
						$this->addNavAction( 'timelinemode', 'av_timer', 'Timeline Mode Light', '?timeline=1', $xtpl );
						$xtpl->assign( 'TIMELINE_MODE', 'None' );
						break;
					case 1:
						$this->addNavAction( 'timelinemode', 'av_timer', 'Timeline Mode Full', '?timeline=2', $xtpl );
						$xtpl->assign( 'TIMELINE_MODE', 'Day' );
						break;
					case 2:
						$this->addNavAction( 'timelinemode', 'av_timer', 'No Timeline Mode', '?timeline=0', $xtpl );
						$xtpl->assign( 'TIMELINE_MODE', 'Hour' );
						break;
				}

				$xtpl->assign( 'ALBUM_TITLE', $album['title'] );
				$xtpl->assign( 'ALBUM_DESCRIPTION', $album['description'] );

				$albumDate = $this->formatDateForDisplayWithTimeZone( $album['date'], new DateTimeZone( "PST" ), "F j, Y" );

				$xtpl->assign( 'ALBUM_DATE', $albumDate );
				$xtpl->assign( 'ALBUM_PIC_URL', $coverPhoto->image );

				$xtpl->assign( 'IS_PUBLISHED', $album['is_published'] == 1 ? 'checked' : '' );

				$albumPhotoResults = $db->photos()->select( 'photos.id, photos.title, photos.description, photos.date_taken, photos.orientation, photos.location' )->where( 'album_photos:albums_id', $albumId )->order( 'date_taken ASC' );

				$xtpl->assign( 'ALBUM_NUM_PHOTOS', $albumPhotoResults->count() );

				if( $this->isAuthenticated() )
				{
					$xtpl->parse( 'main.body.admin_links' );
				}

				$data = array();

				$annotationResults = $db->album_annotations()->select( "*" )->where( 'albums_id', $albumId )->order( 'time ASC' );
				while( $annotation = $annotationResults->fetch() )
				{
					$item = new AlbumData();
					$item->type = "annotation";
					$item->dateTime = $annotation['time'];
					$item->data = $annotation;

					$data[] = $item;
				}

				while( $photo = $albumPhotoResults->fetch() )
				{
					$item = new AlbumData();
					$item->type = "photo";
					$item->dateTime = $photo['date_taken'];
					$item->data = $photo;

					$data[] = $item;
				}

				usort( $data, "cmp" );

				$currentDayOfYear = null;
				$currentHourOfDay = null;
				foreach( $data as $item )
				{
					if( $timeLineMode > 0 )
					{
						$newDayOfYear = $this->formatDateForDisplay( $item->dateTime, "z" );

						if( $currentDayOfYear != $newDayOfYear )
						{
							$currentDayOfYear = $newDayOfYear;

							$dayStr = $this->formatDateForDisplay( $item->dateTime, "l, F j" );

							$xtpl->assign( 'ALBUM_DAY_SEPARATOR', $dayStr );
							$xtpl->parse( 'main.body.item.day_separator' );
						}

						if( $timeLineMode > 1 )
						{
							//$photoLoc = explode(',', $photo['location']);
							//echo $photoLoc[0] . '   ' . $photoLoc[1] . "<br />";
							//$timeZone = get_nearest_timezone($photoLoc[0], $photoLoc[1], "US");

							$newHourOfDay = $this->formatDateForDisplay( $item->dateTime, "H" );

							if( $currentHourOfDay != $newHourOfDay )
							{
								$currentHourOfDay = $newHourOfDay;

								$timeStr = $this->formatDateForDisplay( $item->dateTime, "g A" );
								$xtpl->assign( 'ALBUM_TIME_SEPARATOR', $timeStr );
								$xtpl->parse( 'main.body.item.time_separator' );
							}
						}
					}

					if( $item->type == "photo" )
					{
						$photo = $item->data;

						$photoDateStr = $this->formatDateForDisplay( $photo['date_taken'] );

						$xtpl->assign( 'PHOTO_ID', $photo['id'] );
						$xtpl->assign( 'PHOTO_DATE', $photoDateStr );
						$xtpl->assign( 'PHOTO_URL', '/photo/' . $photo['id'] . '/album/' . $albumId );
						$xtpl->assign( 'PHOTO_IMAGE_URL', b2GetPublicThumbnailUrl( $photo['id'] ) );
						$xtpl->assign( 'PHOTO_TITLE', $photo['title'] );

						if( !empty( $photo['description'] ) )
						{
							$xtpl->assign( 'PHOTO_DESCRIPTION', $photo['description'] );
							$xtpl->parse( 'main.body.item.photo.photo_description' );
						}

						if( $photo['orientation'] == 'land' )
						{
							$xtpl->parse( 'main.body.item.photo.photo_element_land' );
						}
						else
						{
							$xtpl->parse( 'main.body.item.photo.photo_element_port' );
						}
						$xtpl->parse( 'main.body.item.photo' );
					}
					elseif( $item->type == "annotation" )
					{
						$xtpl->assign( 'ANNOTATION_TEXT', $item->data['text'] );

						/*
						 * For debugging annotation date/times
						 *
						$utc = new DateTimeZone("UTC");
						$pst = new DateTimeZone("America/Los_Angeles");
						$dateTime = new DateTime( $item->data['time'], $utc );
						$dateTime->setTimezone($pst);
						$cardDate = $dateTime->format('Y-m-d H:i:s');
						$xtpl->assign('ANNOTATION_DATE', $cardDate);
						*/

						$xtpl->assign( 'ANNOTATION_ID', $item->data['id'] );
						$xtpl->assign( 'ANNOTATION_DATE', utcToPst( $item->data['time'] ) );

						$type = $item->data['type'];
						if( $type == 'text' )
						{
							if( $this->isAuthenticated() )
							{
								$xtpl->parse( 'main.body.item.annotation_text.admin' );
							}

							$xtpl->parse( 'main.body.item.annotation_text' );
						}
						elseif( $type == 'path' )
						{
							$pathStartTimestamp = utcTimestamp( $item->data['path_start'] );
							$pathEndTimestamp = utcTimestamp( $item->data['path_end'] );

							$pathPoints = $this->getPathPoints( $gpsPoints, $pathStartTimestamp, $pathEndTimestamp );

							$urlPathPoints = $this->downSample( $pathPoints, 2000 );
							$pathMapUrl = $this->getMapUrl( $urlPathPoints, 450, 400 );
							$xtpl->assign( 'ALBUM_PATH_MAP', $pathMapUrl );

							$track = new Track();
							$segment = new Segment();
							foreach( $pathPoints as $pathPoint )
							{
								$point = new Point( Point::TRACKPOINT );
								$point->latitude = $pathPoint[0][0];
								$point->longitude = $pathPoint[0][1];
								$point->elevation = $pathPoint[1][0];
								$point->time = $pathPoint[1][1];

								$segment->points[] = $point;
							}
							$track->segments[] = $segment;
							$track->recalculateStats();
							$pathStats = $track->stats->toArray();

							$pathStartTimestamp = strtotime($pathStats['startedAt']);
							$pathFinishTimestamp = strtotime($pathStats['finishedAt']);
							$pathDurationHours = ($pathFinishTimestamp - $pathStartTimestamp) / 60 / 60;
							$pathDurationHours = round($pathDurationHours, 1);

							$xtpl->assign( 'ALBUM_PATH_DURATION', "$pathDurationHours hours" );

							// Convert meters to feet
							$pathElevationMeters = $pathStats['cumulativeElevationGain'];
							$pathElevationFeet = round( $pathElevationMeters * 3.28084 );

							// Convert meters to miles
							$pathDistanceMeters = $pathStats['distance'];
							$pathDistanceMiles = round( $pathDistanceMeters * 0.000621371 );

							$xtpl->assign( 'ALBUM_PATH_DISTANCE', "$pathDistanceMiles miles" );
							$xtpl->assign( 'ALBUM_PATH_ELEVATION', "$pathElevationFeet feet" );

							if( $this->isAuthenticated() )
							{
								$xtpl->parse( 'main.body.item.annotation_path.admin' );
							}

							$xtpl->parse( 'main.body.item.annotation_path' );
						}
					}
					$xtpl->parse( 'main.body.item' );
				}

				$db->close();
			}
		}

		$xtpl->parse( 'main.body' );
	}

	private function getPathPoints( $points, $startTime, $endTime )
	{
		$filtered = array();

		$totalPoints = sizeof( $points );
		for( $ii = 0; $ii < $totalPoints; $ii++ )
		{
			$point = $points[ $ii ];

			$ptTimestamp = $point[1][1]->getTimestamp();
			if( $ptTimestamp > $endTime )
			{
				break;
			}
			elseif( $ptTimestamp >= $startTime && $ptTimestamp < $endTime )
			{
				$filtered[] = $point;
			}
		}

		return $filtered;
	}

	public function post( $request )
	{
		if( $this->enforceAuth() )
		{
			if( count( $request->args ) >= 1 && is_numeric( $request->args[0] ) )
			{
				$albumId = $request->args[0];

				$action = $request->post['action'];

				if( $action == 'update' )
				{
					$db = getDb();
					$albumRow = $db->albums[ $albumId ];
					if( $albumRow )
					{
						$albumRow['is_published'] = isset( $request->post['is_published'] ) ? 1 : 0;
						$success = $albumRow->update();

						if( $success )
						{
							header( "Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
						}
						else
						{
							echo 'Failed to update album';
						}
					}
					else
					{
						echo 'No album found for id';
					}
				}
				elseif( $action == 'addcard' )
				{
					$type = $request->post['annotation-type'];
					$date = $request->post['annotation-date'];
					$content = $request->post['card_content'];

					$time = $request->post['annotation-time'];
					$datetimeStr = $date . 'T' . $time;
					$timestamp = pstToUtc( $datetimeStr );

					$pathStartTimestamp = null;
					$pathEndTimestamp = null;
					if( $type == 'path' )
					{
						$pathStartDate = $request->post['annotation-path-start-date'];
						$pathStartTime = $request->post['annotation-path-start-time'];
						$pathStartStr = $pathStartDate . 'T' . $pathStartTime;
						$pathStartTimestamp = pstToUtc( $pathStartStr );

						$pathEndDate = $request->post['annotation-path-end-date'];
						$pathEndTime = $request->post['annotation-path-end-time'];
						$pathEndStr = $pathEndDate . 'T' . $pathEndTime;
						$pathEndTimestamp = pstToUtc( $pathEndStr );
					}

					$this->addCard( $albumId, $type, $timestamp, $content, $pathStartTimestamp, $pathEndTimestamp );

					header( "Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
				}
			}
			else
			{
				echo 'No album id provided';
			}
		}
	}

	private function addCard( $albumId, $type, $timestamp, $content, $pathStartTimestamp, $pathEndTimestamp )
	{
		$db = getDb();

		error_log( 'Creating album annotation ' . $albumId );

		$newAnnotation = array( 'albums_id' => $albumId,
			'type' => $type,
			'text' => $content,
			'time' => $timestamp,
			'path_start' => $pathStartTimestamp,
			'path_end' => $pathEndTimestamp );

		$insertResult = $db->album_annotations()->insert( $newAnnotation );

		return ($insertResult != null);
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		$request = $this->getRequest();
		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
		{
			$db = getDb();
			$album = $db->albums[ $albumId ];

			return b2GetPublicBlurUrl( $album['cover_photo_id'] );
		}
		else
		{
			return "";
		}
	}

	private function updateTimelineMode( $albumId )
	{
		$db = getDb();

		$album = $db->albums[ $albumId ];
		$currentMode = $album['timeline_mode'];

		switch( $currentMode )
		{
			case 0:
				$album['timeline_mode'] = 1;
				break;
			case 1:
				$album['timeline_mode'] = 2;
				break;
			case 2:
			default:
				$album['timeline_mode'] = 0;
				break;
		}

		$album->update();

		$db->close();
	}

	private function getMapUrl( $pathPoints, $width, $height )
	{
		$polyline = Polyline::encode( $pathPoints );
		$encoded = urlencode($polyline);

		$url = "/maps/api/staticmap?size=${width}x${height}&maptype=terrain&key=$this->googleMapsApiKey&format=png&path=color:0x0000ff|weight:5|enc:$encoded";

		return buildAndSignMapUrl( $url, $this->googleMapsApiSecret );
	}

	private function downSample( $array, $max )
	{
		$totalPoints = sizeof( $array );
		$result = array();
		if( $totalPoints > $max )
		{
			$stride = ceil( $totalPoints / $max );
		}
		else
		{
			$stride = 1;
		}

		//array_pad($result, ($totalPoints/$stride), null);
		for( $ii = 0; $ii < $totalPoints; $ii += $stride )
		{
			$result[] = $array[ $ii ][0];
		}

		return $result;
	}
}

function cmp( $a, $b )
{
	return compareByTimeStamp( $a->dateTime, $b->dateTime );
}

function compareByTimeStamp( $time1, $time2 )
{
	if( strtotime( $time1 ) < strtotime( $time2 ) )
		return -1;
	else if( strtotime( $time1 ) > strtotime( $time2 ) )
		return 1;
	else
		return 0;
}

class AlbumData
{
	var $type;
	var $dateTime;
	var $data;
}

?>