<?php

function getKeys()
{
	$home = dirname($_SERVER['DOCUMENT_ROOT'], 1);
	$keys = json_decode( utf8_encode( file_get_contents( $home . '/keys.json' ) ) );

	if( !$keys )
	{
		die('Bad Keys file');
	}

	return $keys;
}

?>