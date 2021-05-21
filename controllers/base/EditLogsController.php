<<<<<<< HEAD
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
		$db = getDb();
		if($request->params['create'] == true)
		{
			$db->debug = true;

			$newBlog = array('title' => '', 'content' => '', 'hero_photo_id' => 1);
			$row = $db->blogs()->insert($newBlog);

			$newId = $row['id'];

			if( $newId )
			{
				header( 'Location: /editlog/' . $newId );
			}
			else
			{
				echo('Failed to create log');
			}
		}
		else
		{
			$xtpl->assign_file( 'BODY_FILE', 'templates/edit_logs.html' );

			foreach( $db->blogs() as $blog )
			{
				$xtpl->assign( 'BLOG_ID', $blog['id'] );
				$xtpl->assign( 'BLOG_TITLE', $blog['title'] );
				$xtpl->parse( 'main.body.log_entry' );
			}

			$xtpl->parse( 'main.body' );
		}

		$db->close();
		$db = null;
	}
}

=======
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
		$db = getDb();
		if($request->params['create'] == true)
		{
			$db->debug = true;

			$newBlog = array('title' => '', 'content' => '', 'hero_photo_id' => 1);
			$row = $db->blogs()->insert($newBlog);

			$newId = $row['id'];

			if( $newId )
			{
				header( 'Location: /editlog/' . $newId );
			}
			else
			{
				echo('Failed to create log');
			}
		}
		else
		{
			$xtpl->assign_file( 'BODY_FILE', 'templates/edit_logs.html' );

			foreach( $db->blogs() as $blog )
			{
				$xtpl->assign( 'BLOG_ID', $blog['id'] );
				$xtpl->assign( 'BLOG_TITLE', $blog['title'] );
				$xtpl->parse( 'main.body.log_entry' );
			}

			$xtpl->parse( 'main.body' );
		}

		$db->close();
		$db = null;
	}
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>