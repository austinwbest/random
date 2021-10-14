<?PHP
/*
----------------------------------
 ------  Created: 042321   ------
 ------  Austin Best	   ------
----------------------------------
*/

function jackett_search($release)
{
	global $xseedIndexers;

	$trackerList = implode('' , array_map(function($tracker) {
		return '&Tracker%5B%5D='. strtolower($tracker);
	}, $xseedIndexers));

	$release = str_replace('.mkv', '', $release);
	$release = str_replace('.mp4', '', $release);

	$url = JACKET_URL .'/api/v2.0/indexers/all/results?apikey='. JACKETT_API .'&Query='. $release .'&_='. time() . $trackerList;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);
	
	$results = json_decode($response, true);

	if ($info['http_code'] == '200')
	{
		return $results;
	}
}

function jackett_download($result)
{
	$destination = 'tmp/['. $result['Tracker'] .'] '. $result['Title'] .'.torrent';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $result['Link']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);
	
	if ($info['http_code'] == 200)
	{
		file_put_contents($destination, $response);
		return true;
	}
	
	return false;
}
?>