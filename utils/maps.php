<?php

function base64url_encode($data)
{ 
	return strtr(base64_encode($data), '+/', '-_');
} 

function base64url_decode($data)
{ 
	return base64_decode(strtr($data, '-_', '+/'));
}

function buildAndSignMapUrl( $url, $mapsApiSecret )
{
	$decodedSecret = base64url_decode( $mapsApiSecret );

	$urlBase = "http://maps.googleapis.com";

	$urlsignature = hash_hmac( "sha1", $url, $decodedSecret, true);

	$encodedSignature = base64url_encode($urlsignature);

	return $urlBase . $url . "&signature=$encodedSignature";
}

?>