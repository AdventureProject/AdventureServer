<?php

require_once('utils/BaseController.php');

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

		$timelineData = array(
			array(
				"date" => "1"
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			),
			array(
				"date" => 2
			)
		);

		foreach( $timelineData as $entry )
		{
			$xtpl->parse( 'main.body.timeline_entry' );
		}

		$xtpl->parse( 'main.body' );
	}
}

?>