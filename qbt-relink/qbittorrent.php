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

function qbt_queue()
{
	global $qbtCookie;

	$url = QBT_URL .'/api/v2/torrents/info?sort=added_on&reverse=true';
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
		echo 'qbt_queue failure, check settings.';
		echo '<pre>';
		print_r($torrents);
		echo '</pre>';
		exit();
	}

	if (!$torrents) {
		exit('Nothing in the QBT queue.');
	}

	return $torrents;
}

function qbt_torrentContents($hash)
{
	global $qbtCookie;

	$url = QBT_URL .'/api/v2/torrents/files?hash=' . $hash;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);
	
	$contents = json_decode($response, true);

	if ($info['http_code'] != '200') {
		echo 'qbt_torrentContents failure, check settings.';
		echo '<pre>';
		print_r($contents);
		echo '</pre>';
		exit();
	}

	if (!$contents) {
		exit('No contents for hash '. $hash .'.');
	}

	return $contents;
}

function qbt_renameContents($hash, $old, $new)
{
	global $qbtCookie;

	$payload = ['hash' => $hash, 'oldPath' => $old, 'newPath' => $new];

	$url = QBT_URL .'/api/v2/torrents/renameFile';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);
	
	$response = json_decode($response, true);

	if ($info['http_code'] != '200') {
		echo 'qbt_renameContents failure, check settings.';
		echo '<pre>';
		print_r($response);
		echo '</pre>';
		exit();
	}

	return $response;
}

function qbt_recheckTorrent($hash)
{
	global $qbtCookie;

	$url = QBT_URL .'/api/v2/torrents/recheck?hashes=' . $hash;
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
