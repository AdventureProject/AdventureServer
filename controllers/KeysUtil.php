<?php

function getKeys()
{
	$keys = json_decode( utf8_encode( file_get_contents('../keys.json') ) );

	if( !$keys )
	{
		die('Bad Keys file');
	}

	return $keys;
}

?>