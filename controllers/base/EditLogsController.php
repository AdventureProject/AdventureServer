<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class EditLogsController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'editlogs';
	}

	public function getTitle()
	{
		return 'Edit Logs';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		if($request->params['create'] == true)
		{
			$db = getDb();

			$db->debug = true;

			$newBlog = array('title' => '', 'content' => '');
			$row = $db->blogs()->insert($newBlog);

			$newId = $row['id'];

			header( 'Location: /editlog/' . $newId );
		}
		else
		{
			$xtpl->assign_file( 'BODY_FILE', 'templates/edit_logs.html' );

			$db = getDb();
			foreach( $db->blogs() as $blog )
			{
				$xtpl->assign( 'BLOG_ID', $blog['id'] );
				$xtpl->assign( 'BLOG_TITLE', $blog['title'] );
				$xtpl->parse( 'main.body.log_entry' );
			}

			$xtpl->parse( 'main.body' );
		}
	}
}

?>