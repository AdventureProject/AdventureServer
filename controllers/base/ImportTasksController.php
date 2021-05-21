<<<<<<< HEAD
<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class ImportTasksController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'importtasks';
	}

	public function getTitle()
	{
		return 'Import Tasks';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file('BODY_FILE', 'templates/import_tasks.html');

		$db = getDb();

		if( count( $request->args ) == 0 )
		{
			$importTasks = $db->photo_import()->select('*')->order('id');
			while ($task = $importTasks->fetch()) {
				$xtpl->assign('TASK_ID', $task['id']);
				$xtpl->assign('ALBUM_ID', $task['flickr_album_id']);
				$xtpl->assign('FLICKR_ID', $task['flickr_id']);
				$xtpl->assign('STATUS', $task['import_state']);

				$xtpl->parse('main.body.import_task');
			}

			$xtpl->parse('main.body');
		}
		else if( count( $request->args ) == 1 )
		{
			session_write_close();
			set_time_limit( 2400 );
			
			if( $request->args[0] == 'all' )
			{
				$importTasks = $db->photo_import()->select('id')->order('id');
				while ($task = $importTasks->fetch())
				{
					processImportTask($task['id']);
				}
			}
			else if( is_numeric($request->args[0]) )
			{
				$importTaskId = $request->args[0];
				processImportTask( $importTaskId );
			}

			header('Location:/' . $this->urlStub());
		}
		else
		{
			header('Location:/' . $this->urlStub());
		}
	}

	public function post( $request )
	{
		/*
		if( !empty($request->post['import_task_id']) && is_numeric($request->post['import_task_id']) )
		{
			processImportTask( $request->post['import_task_id'] );
		}
		*/
	}
}

=======
<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class ImportTasksController extends BaseController
{
	public function __construct( $config )
	{
		parent::__construct( true, $config );
	}

	public function urlStub()
	{
		return 'importtasks';
	}

	public function getTitle()
	{
		return 'Import Tasks';
	}

	public function getBody( $request, $todaysPhoto, $xtpl )
	{
		$xtpl->assign_file('BODY_FILE', 'templates/import_tasks.html');

		$db = getDb();

		if( count( $request->args ) == 0 )
		{
			$importTasks = $db->photo_import()->select('*')->order('id');
			while ($task = $importTasks->fetch()) {
				$xtpl->assign('TASK_ID', $task['id']);
				$xtpl->assign('ALBUM_ID', $task['flickr_album_id']);
				$xtpl->assign('FLICKR_ID', $task['flickr_id']);
				$xtpl->assign('STATUS', $task['import_state']);

				$xtpl->parse('main.body.import_task');
			}

			$xtpl->parse('main.body');
		}
		else if( count( $request->args ) == 1 )
		{
			session_write_close();
			set_time_limit( 2400 );
			
			if( $request->args[0] == 'all' )
			{
				$importTasks = $db->photo_import()->select('id')->order('id');
				while ($task = $importTasks->fetch())
				{
					processImportTask($task['id']);
				}
			}
			else if( is_numeric($request->args[0]) )
			{
				$importTaskId = $request->args[0];
				processImportTask( $importTaskId );
			}

			header('Location:/' . $this->urlStub());
		}
		else
		{
			header('Location:/' . $this->urlStub());
		}
	}

	public function post( $request )
	{
		/*
		if( !empty($request->post['import_task_id']) && is_numeric($request->post['import_task_id']) )
		{
			processImportTask( $request->post['import_task_id'] );
		}
		*/
	}
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>