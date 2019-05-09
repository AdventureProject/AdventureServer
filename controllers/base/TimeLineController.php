<?php

require_once('utils/BaseController.php');
require_once('utils/b2_util.php');

class TimeLineController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}

	public function urlStub()
	{
		return 'timeline';
	}

	public function getTitle()
	{
		return 'Timeline';
	}

	public function getRichTitle()
	{
		return 'Adventure.Rocks - Timeline';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/external/vertical-timeline/style.css', $xtpl );
		$this->addJsFile( '/external/vertical-timeline/main.js', $xtpl );

		$this->addCssFile( '/css/timeline.css', $xtpl );

		$xtpl->assign_file( 'BODY_FILE', 'templates/timeline.html' );

		$jsonData = file_get_contents('timeline.json');
		$data = json_decode($jsonData);

		foreach( $data->timeline as $entry )
		{
			echo 'print line<br />';
			$date = date( 'j M', $entry->date );
			$xtpl->assign( 'ENTRY_DATE', $date );
			$xtpl->assign( 'ENTRY_TITLE', $entry->title );
			$xtpl->assign( 'ENTRY_DESCRIPTION', $entry->description );

			$numPhotos = count($entry->photos);
			if($numPhotos == 1)
			{
				$xtpl->parse( 'main.body.timeline_entry.photo_container_full' );
			}
			else
			{
				$xtpl->parse( 'main.body.timeline_entry.photo_container_gallery' );
			}

			foreach($entry->photos as $photo)
			{
				$photoUrl = b2GetPublicTimelinePhoto($photo);
				$xtpl->assign( 'ENTRY_PHOTO', $photoUrl );
				$xtpl->parse( 'main.body.timeline_entry.photo' );
			}

			$xtpl->parse( 'main.body.timeline_entry' );
		}

		$xtpl->parse( 'main.body' );
	}
}

?>