<?php

/*
----------------------------------
 ------  Created: 020423   ------
 ------  Austin Best	   ------
----------------------------------
*/

function qbt_login()
{
	$postData = array('username' => QBT_USER, 'password' => QBT_PASS);
	
	$url = QBT_URL .'/api/v2/auth/login';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(QBT_URL));	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code'] != '200') {
		exit('QBT auth failure, check settings.');
	}

	$headers = explode("\n", $response);

	foreach ($headers as $header) {
		if (strpos($header, 'set-cookie:') !== false) {
			$cookie = reset(explode(';', $header));
			$cookie = str_replace('set-cookie: ', '', $cookie);
			break;
		}
	}

	return $cookie;
}

function qbt_queue($qbtCookie, $filter = 'completed')
{
	$url = QBT_URL .'/api/v2/torrents/info?filter='. $filter .'&sort=added_on&reverse=true';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);
	
	$torrents = json_decode($response, true);

	if ($info['http_code'] != '200') {
		exit('QBT queue failure, check settings.');
	}

	if (!$torrents) {
		exit('Nothing in the QBT queue.');
	}

	return $torrents;
}

function qbt_addTorrent($qbtCookie, $qbtItem)
{
	$postData = array('root_folder' 	=> 'false', 
					  'skip_checking' 	=> 'true', 
					  'urls'			=> QBT_TMP_FOLDER . $qbtItem['hash'] . '.torrent',
					  'savepath' 		=> $qbtItem['save_path'], 
					  'category' 		=> $qbtItem['category'], 
					  'tags' 			=> $qbtItem['tags'], 
					  'contentLayout' 	=> 'Original'
					  );

	$url = QBT_URL .'/api/v2/torrents/add';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$addResponse 	= curl_exec($ch);
	$addError 		= curl_error($ch);
	$addInfo 		= curl_getinfo($ch);
	curl_close($ch);

	if ($addResponse == 'Fails.') {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Failed to add torrent to qbt.<br>';
		return true;
	}

	return false;
}

function qbt_delete($qbtCookie, $hash)
{
	$url = QBT_URL .'/api/v2/torrents/delete?hashes='. $hash .'&deleteFiles=false';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);	
}
