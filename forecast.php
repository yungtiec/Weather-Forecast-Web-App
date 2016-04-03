<?php 
	// google geocode api
	$wholeAddress = $_GET['address'].','.$_GET['city'].','.$_GET['state'];
    $wholeAddress = str_replace(' ', '+', $wholeAddress);
	$GoogleAPIkey = 'AIzaSyCTuj5RiE1jO4oGz278ry41AAupcsHl2F0';
	$queryArgs = 'address='.$wholeAddress.'&key='.$GoogleAPIkey;
	$geocodeURL = 'https://maps.googleapis.com/maps/api/geocode/xml?'.$queryArgs;
	$xml = simplexml_load_file($geocodeURL);
	$lat = $xml->result->geometry->location->lat;
	$lng = $xml->result->geometry->location->lng;
	
	// forecast api
	$degree = $_GET['degree'];
	$APIkey = 'ff66ba4bd210eac795081f5e30b128ff';
	$forecastURL = 'https://api.forecast.io/forecast/'.$APIkey.'/'.$lat.','.$lng.'?units='.$degree.'&exclude=flags';
	$json = file_get_contents($forecastURL);
	$obj = json_decode($json,true);

    // process currently
	$current = $obj['currently'];    
	$daily = $obj['daily'];
	$timezone = $obj['timezone'];
    $latInt = $obj['latitude'];
    $lngInt = $obj['longitude'];
    $dailyEntry0 = $daily['data'][0]; 
    
	// get weather parameters
	$summary = $current['summary']; // each string corresponds to an icon
	$icon = $current['icon'];
	$temperature = $current['temperature']; // int, unit: depend on user input $degree
    $temperatureMin = $dailyEntry0['temperatureMin'];
    $temperatureMax = $dailyEntry0['temperatureMax'];
    // 0 -> none, 0.002 -> very light, 0.0017 -> light, 0.1 -> moderate, 0.4 -> heavy
	$precipitation = $current['precipIntensity']; 
	$chanceRain = $current['precipProbability']; // multiply by 100 unit: %
	$windSpeed = $current['windSpeed']; // int unit: mph
	$dewPoint = $current['dewPoint']; // int 
	$humidity = $current['humidity']; // multiply by 100 unit: %
	$visibility = $current['visibility']; // int unit: mi 
	$sunrise = $dailyEntry0['sunriseTime']; // an object, convert it to XX:XX AM/PM
	$sunset = $dailyEntry0['sunsetTime']; // an object, convert it to XX:XX AM/PM
		
	date_default_timezone_set($timezone);
	$sunriseTime = date("H:i A",$sunrise);
	$sunsetTime = date("H:i A",$sunset);

	// get summary image
	if ($icon == 'clear-day') {
		$iconImg = 'clear';
	} else if ($icon == 'clear-night') {
		$iconImg = 'clear_night';
	} else if ($icon == 'partly-cloudy-day') {
		$iconImg = 'cloud_day';
	} else if ($icon == 'partly-cloudy-night') {
		$iconImg = 'cloud_night';
	} else {
		$iconImg = $icon;
	}
	$iconImg = $iconImg.'.png';

	// percentage calculation
	$chanceRain = $chanceRain * 100;
	$humidity = $humidity * 100;

	// convert to integer
	$temperature = intval($temperature);
	$chanceRain = intval($chanceRain);
	$dewPoint = intval($dewPoint);
	$humidity = intval($humidity);
	$visibility = intval($visibility);
    
    // testing if everything is working properly
    // header("content-type:text/plain");
    // echo ($summary);

	// parsing currently
    $forecast = array();
    $forecast['currently'] = array (
        "lat" => $lat,
        "lng" => $lng,
        "address" => $_GET['address'],
        "city" => $_GET['city'],
        "state" => $_GET['state'],
        "degree" => $_GET['degree'],
        "summary" => $summary,
        "icon" => $icon,
        "iconImg" => $iconImg,
        "temperature" => $temperature,
        "temperatureMin" => $temperatureMin,
        "temperatureMax" => $temperatureMax,
        "precipitation" => $precipitation,
        "chanceRain" => $chanceRain,
        "windSpeed" => $windSpeed,
        "dewPoint" => $dewPoint,
        "humidity" => $humidity,
        "visibility" => $visibility,
        "sunrise" => $sunriseTime,
        "sunset" => $sunsetTime
    );
    // end processing currently

    // parsing hourly 
    $hourly = $obj['hourly'];
    $hourlyEntries = $hourly['data'];
    $hourlyArr = array();
    foreach ($hourlyEntries as $value) {
        $timeTemp = $value['time'];
        $hourlyTime = date("H:i A",$timeTemp);
        array_push($hourlyArr, $hourlyTime);
    }
    array_push($hourly, $timezone); //
    $forecast['hourly'] = $hourly;
    $forecast['hourlyArr'] = $hourlyArr;
    // end parsing hourly

    // parsing daily 
    $dailyEntries = $daily['data'];
    $weekdayArr = array();
    $monthDateArr = array();
    $dailySunriseArr = array();
    $dailySunsetArr = array();
    foreach ($dailyEntries as $value) {
        $timeTemp = $value['time'];
        $weekday = date("l",$timeTemp);
        $monthDate = date("M j",$timeTemp);
        $sunrise = $value['sunriseTime'];
        $sunrise = date("H:i A",$sunrise);
        $sunset = $value['sunsetTime'];
        $sunset = date("H:i A",$sunset);
        array_push($weekdayArr, $weekday);
        array_push($monthDateArr, $monthDate);
        array_push($dailySunriseArr, $sunrise);
        array_push($dailySunsetArr, $sunset);
    }
	$forecast['weekdayArr'] = $weekdayArr;
	$forecast['monthDateArr'] = $monthDateArr;
	$forecast['dailySunriseArr'] = $dailySunriseArr;
	$forecast['dailySunsetArr'] = $dailySunsetArr;
    array_push($daily, $timezone); //
    $forecast['daily'] = $daily;
    $forecast['latitude'] = $latInt;
    $forecast['longitude'] = $lngInt;
    // end parsing daily 

    header("content-type:application/json;charset=utf-8");
    echo json_encode($forecast);
?>