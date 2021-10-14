<?PHP
/*
----------------------------------
 ------  Created: 042321   ------
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

	if ($info['http_code'] != '200')
	{
		exit('QBT auth failure, check settings.');
	}

	$headers = explode("\n", $response);

	foreach ($headers as $header)
	{
		if (strpos($header, 'set-cookie:') !== false)
		{
			$cookie = reset(explode(';', $header));
			$cookie = str_replace('set-cookie: ', '', $cookie);
			break;
		}
	}

	return $cookie;
}

function qbt_getFiles($qbtCookie, $hash)
{
	$url = QBT_URL .'/api/v2/torrents/files?hash='. $hash;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);

	$files = json_decode($response, true);

	if ($files)
	{
		foreach ($files as $file)
		{
			$fileList[] = $file['name'];
		}
	}

	return $fileList;
}

function qbt_queue($qbtCookie, $filter = 'completed')
{
	$url = QBT_URL .'/api/v2/torrents/info?filter='. $filter .'&sort=added_on&reverse=true&limit='. HISTORY;
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

	if ($info['http_code'] != '200')
	{
		exit('QBT queue failure, check settings.');
	}
	if (!$torrents)
	{
		exit('Nothing in the QBT queue, xseeding is not possible.');
	}

	return $torrents;
}

function qbt_addTorrent($qbtCookie, $torrent, $source, $hash)
{
	$absolutePath 	= str_replace('xseed.php', '', $_SERVER['SCRIPT_FILENAME']);
	$postData 		= array('root_folder' => 'false', 'skip_checking' => 'true', 'urls' => $absolutePath . $torrent, 'savepath' => $source, 'category' => QBT_TAG);
	$torrentFile	= new Torrent($postData['urls']);
	$content		= $torrentFile->content();
	$qbtFile		= qbt_getFiles($qbtCookie, $hash);

	if (count($content) == count($qbtFile))
	{
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

		if ($addResponse == 'Fails.')
		{
			$existingTrackers 	= qbt_getTrackers($qbtCookie, $hash);
			$announcers 		= $torrentFile->announce();
			$diff				= array_diff($announcers[0], $existingTrackers);

			if (!$existingTrackers || !$announcers[0])
			{
				return 'Failed to add trackers';
			}

			if (!$diff)
			{
				$diff = array_diff($existingTrackers, $announcers[0]);
			}

			if ($diff)
			{
				foreach ($diff as $tracker)
				{
					$added = qbt_addTracker($qbtCookie, $hash, $tracker);
				}

				if ($added)
				{
					return;
				}
			}

			return 'Failed to match hash or add new torrent';
		}
		else
		{
			sleep(3);
			$recent = qbt_queue($qbtCookie, 'all');
			foreach ($recent as $torrent)
			{
				if ($torrent['state'] != 'stalledUP')
				{
					qbt_delete($qbtCookie, $torrent['hash']);
					return 'Check files failed';
				}
			}
		}

		return;
	}

	return 'File count mis-match';
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

function qbt_getTrackers($qbtCookie, $hash)
{
	$url = QBT_URL .'/api/v2/torrents/trackers?hash='. $hash;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);

	$trackers = json_decode($response, true);

	if ($trackers)
	{
		foreach ($trackers as $tracker)
		{
			if ($tracker['status'] > 0)
			{
				$trackerList[] = $tracker['url'];
			}
		}
	}

	return $trackerList;
}

function qbt_addTracker($qbtCookie, $hash, $trackers)
{
	$postData = array('hash' => $hash, 'urls' => $trackers);
	
	$url = QBT_URL .'/api/v2/torrents/addTrackers';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $qbtCookie));	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response 	= curl_exec($ch);
	$error 		= curl_error($ch);
	$info 		= curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code'] == '200')
	{
		return true;
	}

	return false;
}

?>