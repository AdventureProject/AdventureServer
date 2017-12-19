<?php

require_once('utils/KeysUtil.php');

function sendPushMessage( $registrationId, $dataPayload )
{		
	#prep the bundle
	/*
	 $msg = array
		  (
		'body' 	=> 'Body  Of Notification',
		'title'	=> 'Title Of Notification',
				'icon'	=> 'myicon',// Default Icon
				'sound' => 'mySound'// Default sound
		  );
	*/
	$keys = getKeys();
	
	$fields = array
			(
				'to'		=> $registrationId,
				//'notification'	=> $msg,
				'data' => $dataPayload
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
}

?>