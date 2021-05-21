<<<<<<< HEAD
<?php

// ben@jp

function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
	$timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
		: DateTimeZone::listIdentifiers();

	if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

		$time_zone = '';
		$tz_distance = 0;

		//only one identifier?
		if (count($timezone_ids) == 1) {
			$time_zone = $timezone_ids[0];
		} else {

			foreach($timezone_ids as $timezone_id) {
				$timezone = new DateTimeZone($timezone_id);
				$location = $timezone->getLocation();
				$tz_lat   = $location['latitude'];
				$tz_long  = $location['longitude'];

				$theta    = $cur_long - $tz_long;
				$distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
					+ (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
				$distance = acos($distance);
				$distance = abs(rad2deg($distance));
				// echo '<br />'.$timezone_id.' '.$distance;

				if (!$time_zone || $tz_distance > $distance) {
					$time_zone   = $timezone_id;
					$tz_distance = $distance;
				}

			}
		}
		return  $time_zone;
	}
	return 'unknown';
}

=======
<?php

// ben@jp

function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
	$timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
		: DateTimeZone::listIdentifiers();

	if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

		$time_zone = '';
		$tz_distance = 0;

		//only one identifier?
		if (count($timezone_ids) == 1) {
			$time_zone = $timezone_ids[0];
		} else {

			foreach($timezone_ids as $timezone_id) {
				$timezone = new DateTimeZone($timezone_id);
				$location = $timezone->getLocation();
				$tz_lat   = $location['latitude'];
				$tz_long  = $location['longitude'];

				$theta    = $cur_long - $tz_long;
				$distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
					+ (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
				$distance = acos($distance);
				$distance = abs(rad2deg($distance));
				// echo '<br />'.$timezone_id.' '.$distance;

				if (!$time_zone || $tz_distance > $distance) {
					$time_zone   = $timezone_id;
					$tz_distance = $distance;
				}

			}
		}
		return  $time_zone;
	}
	return 'unknown';
}

>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
?>