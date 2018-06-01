<?php

require_once('utils/BaseController.php');

class HealthController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'health';
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
		if( count($request->args) == 1
		   && is_numeric($request->args[0])
		   && is_numeric($request->params['version']) )
		{
			$photoFrameId = $request->args[0];
			
			$deviceId = null;
			if( array_key_exists('deviceId', $request->params) )
			{
				$deviceId = $request->params['deviceId'];
			}

			$db = getDb();
			$row = $db->photo_frame[$photoFrameId];
			// Register unregistered photo frames
			if( !$row )
			{
				// Try to get it by device_id if we couldnt get it by 
				$row = $db->photo_frame()->select('*')->where('device_id = ?', $deviceId)->fetch();
				if( !$row )
				{
					$item['id'] = $photoFrameId;
					$item['owner'] = 'unregistered';
					$row = $db->photo_frame()->insert( $item );
				}
				else
				{
					$row['id'] = $photoFrameId;
					$row->update();
				}
			}
			
			if( $deviceId != null )
			{
				$row['device_id'] = $deviceId;
				$row->update();
			}
			
			if( array_key_exists('update_channel', $request->post) )
			{
				$updateChannel = $request->post['update_channel'];
				$row['update_channel'] = $updateChannel;
				$row->update();
			}

			$photoFrameVersion = $request->params['version'];

			$item['photo_frame'] = $photoFrameId;
			$item['version'] = $photoFrameVersion;

			$row = $db->health_monitor()->insert( $item );

			echo $row;
		}
    }
}

?>