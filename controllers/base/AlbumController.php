<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class AlbumController extends BaseController
{
	private $currentAlbumId = null;
	private $currentAlbumData = null;
	
	public function __construct( $config )
	{
		parent::__construct( false, $config );
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
		$this->currentAlbumData = $db->albums[$this->currentAlbumId];

		parent::get( $request );
	}

	private function getAlbumId( $request )
	{
		$albumId = null;
		if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
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
		
		$xtpl->assign_file('BODY_FILE', 'templates/album.html');

		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
        {
			$db = getDb();

			$album = $db->albums[$albumId];
			$coverPhoto = getPhotoById( $album['cover_photo_id'], true, 1024, 768 );

            $timeLineMode = $album['timeline_mode'];
            if( array_key_exists('timeline', $request->params)
                && is_numeric($request->params['timeline'])
                && $request->params['timeline']> 0 & $request->params['timeline'] < 3)
            {
                $timeLineMode = $request->params['timeline'];
            }

            switch( $timeLineMode )
            {
                case 0:
                    $this->addNavAction( 'timelinemode', 'av_timer', 'Timeline Mode Light', '?timeline=1', $xtpl );
                    break;
                case 1:
                    $this->addNavAction( 'timelinemode', 'av_timer', 'Timeline Mode Full', '?timeline=2', $xtpl );
                    break;
                case 2:
                    $this->addNavAction( 'timelinemode', 'av_timer', 'No Timeline Mode', '?timeline=0', $xtpl );
                    break;
            }

			$xtpl->assign('ALBUM_TITLE', $album['title']);
			$xtpl->assign('ALBUM_DESCRIPTION', $album['description']);

			$albumDate = $this->formatDateForDisplayWithTimeZone($album['date'], new DateTimeZone("PST"), "F j, Y");

			$xtpl->assign('ALBUM_DATE', $albumDate );
			$xtpl->assign('ALBUM_PIC_URL', $coverPhoto->image );

			$albumPhotoResults = $db->photos()->select('photos.id, photos.title, photos.date_taken, photos.orientation, photos.location')->where('album_photos:albums_id', $albumId)->order('date_taken ASC');

			$xtpl->assign('ALBUM_NUM_PHOTOS', $albumPhotoResults->count());

			$data = array();

			$annotationResults = $db->album_annotations()->select("*")->where('albums_id', $albumId)->order('time ASC');
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

			usort($data, "cmp");

			$currentDayOfYear = null;
			$currentHourOfDay = null;
			foreach( $data as $item )
			{
				if($item->type == "photo")
				{
					$photo = $item->data;

					if( $timeLineMode > 0 )
					{
						$newDayOfYear = date("z", strtotime($photo['date_taken']));

						if ($currentDayOfYear != $newDayOfYear)
						{
							$currentDayOfYear = $newDayOfYear;

							$photoLoc = explode(',', $photo['location']);
							$dayStr = $this->formatDateForDisplayWithLocation($photo['date_taken'], $photoLoc[0], $photoLoc[1], "l, F j");

							$xtpl->assign('ALBUM_DAY_SEPARATOR', $dayStr);
							$xtpl->parse('main.body.item.day_separator');
						}

						if( $timeLineMode > 1 )
						{
							$photoLoc = explode(',', $photo['location']);
							//echo $photoLoc[0] . '   ' . $photoLoc[1] . "<br />";
							$timeZone = get_nearest_timezone($photoLoc[0], $photoLoc[1], "US");

							$newHourOfDay = $this->formatDateForDisplayWithLocation($photo['date_taken'], $photoLoc[0], $photoLoc[1], "H");

							if( $currentHourOfDay != $newHourOfDay )
							{
								$currentHourOfDay = $newHourOfDay;

								$timeStr = date("g A", strtotime($photo['date_taken']));
								$xtpl->assign('ALBUM_TIME_SEPARATOR', $timeStr);
								$xtpl->parse('main.body.item.time_separator');
							}
						}
					}

					$xtpl->assign('PHOTO_ID', $photo['id']);
					$xtpl->assign('PHOTO_URL', '/photo/' . $photo['id'] . '/album/' . $albumId );
					$xtpl->assign('PHOTO_IMAGE_URL', b2GetPublicThumbnailUrl($photo['id']));
					$xtpl->assign('PHOTO_TITLE', $photo['title']);

					if( $photo['orientation'] == 'land' )
					{
						$xtpl->parse('main.body.item.photo.photo_element_land');
					}
					else
					{
						$xtpl->parse('main.body.item.photo.photo_element_port');
					}
					$xtpl->parse('main.body.item.photo');
				}
				elseif($item->type == "annotation")
				{
					$xtpl->assign('ANNOTATION_TEXT', $item->data['text']);
					$xtpl->parse('main.body.item.annotation');
				}
				$xtpl->parse('main.body.item');
			}
		}
		
		$xtpl->parse('main.body');
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		$request = $this->getRequest();
		$albumId = $this->getAlbumId( $request );
		if( $albumId != null )
		{
			$db = getDb();
			$album = $db->albums[$albumId];

			return b2GetPublicBlurUrl( $album['cover_photo_id'] );
		}
		else
		{
			return "";
		}
	}
}

function cmp($a, $b)
{
	return compareByTimeStamp($a->dateTime, $b->dateTime);
}

function compareByTimeStamp($time1, $time2)
{
	if (strtotime($time1) < strtotime($time2))
		return -1;
	else if (strtotime($time1) > strtotime($time2))
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