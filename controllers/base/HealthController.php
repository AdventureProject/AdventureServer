<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class HealthController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function getTitle()
    {
    	return 'Health Monitor';
    }
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		if( $this->isAuthenticated() )
		{
			$xtpl->assign_file('BODY_FILE', 'templates/health.html');

			$db = getDb();
			$photoFrameIds = $db->photo_frame()->select('*');

			foreach( $photoFrameIds as $id => $photoFrame )
			{
				$xtpl->assign( 'PHOTO_FRAME_ID', $id );
				$xtpl->assign( 'PHOTO_FRAME_OWNER', $photoFrame['owner'] );
				$xtpl->assign( 'PHOTO_FRAME_CREATED', $photoFrame['created'] );

				$healthInfo = $db->health_monitor()->select('*')->where('photo_frame', $id)->order('date DESC')->limit(1);;
				foreach( $healthInfo as $checkIn )
				{
					$curCheckIn = $checkIn;
					break;
				}

				$xtpl->assign( 'CHECK_IN_DATE', $curCheckIn['date'] );
				$xtpl->assign( 'CURRENT_VERSION', $curCheckIn['version'] );

				$errors = $db->health_monitor()->select('id,date')->where('photo_frame = ? AND errors <> ?', $id, '')->order('date DESC');

				foreach( $errors as $logId => $error )
				{
					$xtpl->assign( 'ERROR_LOG_ID', $logId );
					$xtpl->assign( 'ERROR_LOG_DATE', $error['date'] );
					$xtpl->parse('main.body.photo_frame.error_log');
				}

				$xtpl->parse('main.body.photo_frame');
			}

			$xtpl->parse('main.body');
		}
		else
		{
			header('Location:'.$this->config->authUrl);
		}
    }
    
    public function post( $request )
    {
			if( count($request->args) == 1 && is_numeric($request->args[0]) && is_numeric($request->params['version']) )
			{
				$photoFrameId = $request->args[0];

				$db = getDb();
				$row = $db->photo_frame[$photoFrameId];
				// Register unregistered photo frames
				if( !$row )
				{
					$item['id'] = $photoFrameId;
					$item['owner'] = 'unregistered';
					$db->photo_frame()->insert( $item );
				}

				$photoFrameVersion = $request->params['version'];

				$errors = "none";
				if( isset( $request->post['errors'] ) )
				{
					$errors = $request->post['errors'];
				}

				$item['photo_frame'] = $photoFrameId;
				$item['version'] = $photoFrameVersion;
				$item['errors'] = $errors;

				$row = $db->health_monitor()->insert( $item );

				echo $row;
			}
    }
}

?>