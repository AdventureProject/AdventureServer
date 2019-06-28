<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class EditLogController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'editlog';
	}

	public function getTitle()
	{
		return 'Edit Log';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file( 'BODY_FILE', 'templates/edit_log.html' );

		$this->addCssFile( 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', $xtpl );
		$this->addJsFile( 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', $xtpl );

		$this->addJsFile( '/js/edit_log.js', $xtpl );

		if( is_numeric( $request->args[0] ) )
		{
			$logId = $request->args[0];

			$db = getDb();
			$log = $db->blogs[ $logId ];

			if( $log )
			{
				$title = $log['title'];
				$content = $log['content'];

				$xtpl->assign( 'BLOG_ID', $logId );
				$xtpl->assign( 'BLOG_TITLE', $title );
				$xtpl->assign( 'BLOG_CONTENT', $content );
			}
		}

		$xtpl->parse( 'main.body' );
	}

	public function post( $request )
	{
		if( is_numeric( $request->args[0] ) )
		{
			$logId = $request->args[0];

			$blogTitle = $request->post['blog_title'];
			$blogContent = $request->post['blog_content'];

			$db = getDb();
			$row = $db->blogs[ $logId ];
			$row['title'] = $blogTitle;
			$row['content'] = $blogContent;
			$updateResult = $row->update();

			if( $updateResult )
			{
				error_log( 'Blog updated' );

				header( 'Location: /' . $this->urlStub() . '/' . $logId );
			}
			else
			{
				error_log( 'Blog update FAILED' );
				echo 'Blog update FAILED';
			}
		}
	}
}

?>