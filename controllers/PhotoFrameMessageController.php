<?php

require_once('Controller.php');

require_once('utils/photos.php');
require_once('utils/KeysUtil.php');
require_once('utils/PushMessage.php');

class PhotoFrameMessageController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'photowallnfc';
	}
	
	public function get( $request )
    {
        if( count($request->args) == 2 )
        {
            $photoFrameId = $request->args[0];
			$photoId = $request->args[1];

			$this->sendPush( $photoFrameId, $photoId );
        }
	}
	
	private function sendPush( $photoFrameId, $photoId )
	{
		$db = getDb();
		$row = $db->photo_frame_registration_token()->select('*')->where("id", $photoFrameId)->fetch();
		
		$data = array(
						'type' => 'photowall',
						'photo_id' => $photoId
					);
		
		#API access key from Google API's Console
		$registrationId = $row['token'];
		
		sendPushMessage( $registrationId, $data );
		/*
		#prep the bundle
		
		 $msg = array
			  (
			'body' 	=> 'Body  Of Notification',
			'title'	=> 'Title Of Notification',
					'icon'	=> 'myicon',// Default Icon
					'sound' => 'mySound'// Default sound
			  );
		
		$fields = array
				(
					'to'		=> $registrationIds,
					//'notification'	=> $msg,
					'data' => array(
										'message_type' => 'photowall',
										'photo_id' => $photoId
									)
				);


		$headers = array
				(
					'Authorization: key=' . $keys->firebase->key,
					'Content-Type: application/json'
				);
		error_log("sending push...");
		#Send Reponse To FireBase Server
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
		#Echo Result Of FireBase Server
		
		error_log("FCM Result: " . $result);
		*/
		
		echo '<!doctype html><html><head><script>function closeWindow() { window.close(); }; window.setTimeout(closeWindow, 1000);</script></head><body><h1>Showing Photo Details</h1></body></html>';
		//header( "Location: http://wethinkadventure.rocks/photo/$photoId" );
	}
	
	public function post( $request )
    {
		$postKey = 'registration_id';
		
		// Photo Frame wishes to register
		if( count($request->args) == 1
			&& is_numeric( $request->args[0] )
			&& array_key_exists($postKey, $request->post)
		  	&& !empty($request->post[$postKey]) )
		{
			$photoFrameId = $request->args[0];
			$fcmRegistrationId = $request->post[$postKey];
			
			$db = getDb();
			$db->photo_frame_registration_token()->insert_update(
				array("id" => $photoFrameId), // unique key
				array("token" => $fcmRegistrationId)
			);
			
			echo json_encode( new RegistrationResponse( true ) );
		}
		else
		{
			echo json_encode( new RegistrationResponse( false ) );
		}
	}
}

class RegistrationResponse
{
	public $success;

	public function __construct( $success )
	{
		$this->success = $success;
	}
}