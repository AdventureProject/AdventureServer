<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . '/utils/KeysUtil.php' );

function getCredentials()
{
	$keys = getKeys();
	
	$sharedConfig = [
    	'region'  => 'us-west-2',
		'version' => '2006-03-01',
		'signature_version' => 'v4',
		'credentials' => [
				'key'    => $keys->aws->key,
				'secret' => $keys->aws->secret
			]
	];
	
	return $sharedConfig;
}

?>
