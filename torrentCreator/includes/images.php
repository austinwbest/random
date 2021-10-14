<?PHP
/*
----------------------------------
 ------  Created: 070320   ------
 ------  Austin Best	   ------
----------------------------------
*/

function takeScreenShot($path, $time)
{
	global $settingsData;

	if (!$path || !$settingsData['ffmpeg_location'])
	{
		logger('takeScreenShot() skipped', __LINE__, __FILE__, 3);
		return;
	}
	
	$ss = '_tmp/'. str_replace(':', '_', $time) .'.png';
	$CMD = '"'. $settingsData['ffmpeg_location'] .'" -ss 00:'. $time .'.000 -i "'. $path .'" -vframes 1 "'. ABSOLUTE_PATH .'/'. $ss .'"';
	logger('takeScreenShot() CMD='. $CMD, __LINE__, __FILE__, 3);
	exec($CMD, $result);
	
	return uploadImage($ss);
}

function uploadImage($image)
{
	global $settingsData;
	
	if (!file_exists($image) || !$settingsData['api_imgur'])
	{
		logger('uploadImage() skipped', __LINE__, __FILE__, 3);
		return;
	}
	
	$payload = array('image' => base64_encode(file_get_contents($image)));	
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID '. $settingsData['api_imgur']));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	$response 	= json_decode(curl_exec($ch), true);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	
	curl_close ($ch);
	
	foreach ($response as $key => $val)
	{
		if (is_array($val))
		{
			logger('['. $key .'] => ', __LINE__, __FILE__, 4);
			foreach ($val as $key2 => $val2)
			{
				logger('['. $key2 .'] => '. $val2, __LINE__, __FILE__, 5);
			}
		}
		else
		{
			logger('['. $key .'] => '. $val, __LINE__, __FILE__, 4);
		}
	}

	if (strpos($image, 'http') === false)
	{
		@unlink($image);
	}
	
	/*
		thumbnails: https://api.imgur.com/models/image
		s = 90x90
		b = 160x160
		t = 160x160 (Proportioned)
		m = 320x320 (Proportioned)
		l = 640x640 (Proportioned)
		h = 1024x1024 (Proportioned)
		
		This letter is also used in functions.php => buildNfoFromTemplate()
	*/

	$image = $response['data']['link'];
	$image = str_replace('.'. end(explode('.', $image)), 'm.'. end(explode('.', $image)), $image);

	return $image;
}
?>