<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');

class HighlightsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( false, $config );
	}
	
	public function urlStub()
	{
		return 'highlights';
	}
	
	public function getTitle()
	{
		return 'Highlights';
	}
	
	public function getRichDescription()
	{
		return 'A select of some of our best photos';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$this->addCssFile( '/css/highlights.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );
		$this->addJsFile( '/js/zoom.js', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/highlights.html');
		
		$db = getDb();
		$results = $db->photos()->select('id, cache_title, cache_thumbnail, cache_orientation')->where("highlight", 1)->order('id DESC');
		
		while( $data = $results->fetch() )
		{
			$xtpl->assign('PHOTO_ID',$data['id']);
			$xtpl->assign('PHOTO_THUMBNAIL',$data['cache_thumbnail']);

			$style;
			if( $data['cache_orientation'] == 'land' )
			{
				$style = 'mdl-cell--3-col pic-card-land';
			}
			else
			{
				$style = 'mdl-cell--2-col pic-card-port';
			}
			$xtpl->assign('PHOTO_ID',$data['id']);
			$xtpl->assign('PHOTO_TITLE',$data['cache_title']);
			$xtpl->assign('PHOTO_STYLE',$style);
			
			$xtpl->parse('main.body.highlight_style');
			$xtpl->parse('main.body.highlight');
		}
		
		$xtpl->parse('main.body');
	}
}

?>