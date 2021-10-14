<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

function moveExtraFilesOut($path)
{
	global $settingsData;
	$tmpFolder = '_tmp';

	if (!$path)
	{
		logger('moveExtraFilesOut() skipped - no path provided', __LINE__, __FILE__, 3);
		return;
	}

	if (!$settingsData['media_extensions'])
	{
		logger('moveExtraFilesOut() skipped - no extensions configured', __LINE__, __FILE__, 3);
		return;
	}

	$extensions = explode(',', $settingsData['media_extensions']);
	$media 		= end(explode('/', $path));
	$parent 	= str_replace('/'. $media, '', $path);

	if (!is_dir($parent .'/'. $tmpFolder))
	{
		logger('mkdir() => '. $parent .'/'. $tmpFolder, __LINE__, __FILE__, 3);
		if (!@mkdir($parent .'/'. $tmpFolder))
		{
			logger('mkdir() failed', __LINE__, __FILE__, 4);
		}
	}
	if (!is_dir($parent .'/'. $tmpFolder .'/'. $media))
	{
		logger('mkdir() => '. $parent .'/'. $tmpFolder .'/'. $media, __LINE__, __FILE__, 3);
		if (!@mkdir($parent .'/'. $tmpFolder .'/'. $media))
		{
			logger('mkdir() failed', __LINE__, __FILE__, 4);
		}
	}

	$opendir = opendir($path);
	while ($file = readdir($opendir))
	{
		if ($file != '.' && $file != '..')
		{
			$extension = end(explode('.', $file));
			if (!in_array($extension, $extensions))
			{
				rename($path .'/'. $file, $parent .'/'. $tmpFolder .'/'. $media .'/'. $file);
				logger('moving extra file out: '. $path .'/'. $file .' => '. $parent .'/'. $tmpFolder .'/'. $media .'/'. $file, __LINE__, __FILE__, 3);
			}
		}
	}
	closedir($opendir);
}

function moveExtraFilesIn($path)
{
	$tmpFolder = '_tmp';

	if (!$path)
	{
		logger('moveExtraFilesIn() skipped - no path provided', __LINE__, __FILE__, 3);
		return;
	}

	$media = end(explode('/', $path));
	$parent = str_replace('/'. $media, '', $path);
	
	if (is_dir($parent .'/'. $tmpFolder .'/'. $media))
	{
		$opendir = opendir($parent .'/'. $tmpFolder .'/'. $media);
		while ($file = readdir($opendir))
		{
			if ($file != '.' && $file != '..')
			{
				rename($parent .'/'. $tmpFolder .'/'. $media .'/'. $file, $path .'/'. $file);
				logger('moving extra file in: '. $parent .'/'. $tmpFolder .'/'. $media .'/'. $file .' => '. $path .'/'. $file, __LINE__, __FILE__, 3);
			}
		}
		closedir($opendir);
		
		logger('removing extras folder: '. $parent .'/'. $tmpFolder .'/'. $media, __LINE__, __FILE__, 3);
		rmdir($parent .'/'. $tmpFolder .'/'. $media);
		logger('removing extras tmp folder: '. $parent .'/'. $tmpFolder, __LINE__, __FILE__, 3);
		rmdir($parent .'/'. $tmpFolder);
	}
}

function createTorrentFile()
{
	global $settingsData;
	
	$type = ($_POST['process_selected_movie'] && $_POST['process_torrent_type'] == 'process_movie') ? 'movie' : 'series';
	$dirs = ($type == 'movie') ? $_POST['process_selected_movie'] : $_POST['process_selected_series'];
	
	logger('createTorrentFile() started', __LINE__, __FILE__, 1);
	
	foreach ($dirs as $path)
	{
		$dir = opendir($path);
		while ($file = readdir($dir))
		{
			if ($file != '.' && $file != '..' && !is_dir($path .'/'. $file))
			{
				logger('$file="'. $path .'/'. $file .'"', __LINE__, __FILE__, 2);
				
				$extensions = explode(',', $settingsData['media_extensions']);
				$pathExtension = end(explode('.', $path .'/'. $file));

				if (in_array($pathExtension, $extensions))
				{
					logger('allowed extension, adding to $fileList[]', __LINE__, __FILE__, 3);

					if ($type == 'series')
					{
						if ($previousPack == $path)
						{
							continue;
						}
					}
					
					$fileList[] = array('path' => $path, 'file' =>  $file);
					$previousPack = $path;
				}
			}
		}
		closedir($dir);
	}
	
	if (!is_array($fileList))
	{
		logger('$fileList empty', __LINE__, __FILE__, 2);
		return;
	}

	foreach ($fileList as $index => $fileParts)
	{
		logger('fileList loop #'. ($index + 1), __LINE__, __FILE__, 2);

		unset($mediaInfo, $metaData, $season, $source, $probeInfo, $torrentFile, $torrentInfo);

		$dummyAnnouncer = 'http://dummy.announcer.url';
		$dummySite		= 'dummy_site_name';
		
		$originalFile	= rtrim($fileParts['path'], '/') .'/'. $fileParts['file'];
		$mediaInfo		= getMediaInfo($originalFile, $type);	
		$metaData		= ($_POST['process_tvdb']) ? getTVDbDetails($_POST['process_tvdb']) : getTMDbDetails($_POST['process_tmdb']);
		$resolution 	= normalizeResolution($mediaInfo['resolution']) .'p';
		$source			= getSourceFromTitle($fileParts['file']);

		if ($type == 'series')
		{
			$torrentName 	= explode('/', $fileParts['path']);
			$torrentName	= $torrentName[count($torrentName) - 2] .'/'. $torrentName[count($torrentName) - 1];
			$torrentName 	= str_replace('/Specials', ' S00', $torrentName);
			$season 		= getSeasonFromTitle($torrentName);
			$torrentName 	= str_replace('/Season '. $season, ' S'. str_pad($season, 2, 0, STR_PAD_LEFT), $torrentName);
		}
		else
		{
			$torrentName	= $metaData['title'];
		}

		$finalName			= $torrentName .' '. (($resolution) ? $resolution .' ' : '') . (($source) ? $source .' ' : '') . (($mediaInfo['audio']) ? $mediaInfo['audio'] : 'AC3') .' '. (($mediaInfo['video']) ? $mediaInfo['video'] : '');
		$torrentFile 		= '_tmp/'. $finalName .'.torrent';
		$torrentDesc		= '_tmp/'. $finalName .'.desc';
		$torrentInfo 		= '_tmp/'. $finalName .'.details';
		$torrentMediaInfo	= '_tmp/'. $finalName .'.mediainfo';

		$info['type']			= $type;
		$info['originalFile']	= utf8_sanitize($originalFile);
		$info['torrentName'] 	= $finalName .'.torrent';
		$info['torrentFile'] 	= $torrentFile;
		$info['torrentInfo'] 	= $torrentInfo;
		$info['resolution']		= $resolution;
		$info['source']			= $source;
		$info['mediaInfo'] 		= json_encode($mediaInfo);
		$info['metaData'] 		= json_encode($metaData);
		
		logger('$info[]', __LINE__, __FILE__, 2);
		foreach ($info as $logKey => $logVal)
		{
			logger('['. $logKey .'] => '. $logVal, __LINE__, __FILE__, 3);
		}

		//-- CREATE SCREENSHOTS
		if ($settingsData['ffmpeg_location'] && $_POST['process_screenshots'] && $_POST['process_screenshots_amount'])
		{
			for ($x = 0; $x < intval($_POST['process_screenshots_amount']); $x++)
			{
				logger('screenshot loop #'. ($x + 1), __LINE__, __FILE__, 2);

				$info['screenshots'][] = takeScreenShot($originalFile, str_pad(($x + 1) * $_POST['process_screenshots_mins'], 2, 0, STR_PAD_LEFT) .':00');
			}
		}
		else
		{
			logger('screenshots skipped', __LINE__, __FILE__, 2);
		}

		if (!file_exists($torrentFile))
		{
			moveExtraFilesOut($fileParts['path']);

			$dirSize = getDirectorySize($fileParts['path']);
			$info['dirSizeBytes'] = $dirSize;

			//-- https://bytesized-hosting.com/pages/how-to-create-a-torrent-using-mktorrent
			$size = ceil($dirSize / 1024 / 1024 / 1024);
			$info['dirSize'] = $size .'GB';

			switch (true)
			{
				case $size <= 8:
					$pieceSize = 20; //-- 1MB
					$info['humanPieceSize'] = '1MB';
				break;
				case $size > 8 && $size <= 16:
					$pieceSize = 21; //-- 2MB
					$info['humanPieceSize'] = '2MB';
				break;
				case $size > 16 && $size <= 72:
					$pieceSize = 22; //-- 4MB
					$info['humanPieceSize'] = '4MB';
				break;
				case $size > 72:
					$pieceSize = 23; //-- 8MB
					$info['humanPieceSize'] = '8MB';
				break;
			}

			$info['cliPieceSize'] = $pieceSize;
			
			unset($cygPathSource, $cygPathDestination, $createTorrent);
			if ($type == 'series')
			{
				$cygPathSource = str_replace(':', '', $fileParts['path']);
			}
			else
			{
				$cygPathSource = str_replace(':', '', $fileParts['path'] .'/'. $fileParts['file']);
			}
			$cygPathSource = '/cygdrive/'. $cygPathSource;
			$cygPathDestination = '/cygdrive/'. str_replace(':', '', ABSOLUTE_PATH) .'/'. $torrentFile;

			//-- CHANGE THE name FOR SEASON PACKS
			if ($type == 'series')
			{
				$path = explode('/', $info['originalFile']);
				$currentName = trim($path[count($path) - 1]);
				$path = str_replace('/'. $currentName, '', $info['originalFile']);
				$path = explode('/', $path);
				$series = utf8_sanitize(trim($path[count($path) - 2]));
				$newTorrentName = ' -n "'. $series .' S'. str_pad($season, 2, 0, STR_PAD_LEFT) .'"';
			}
			
			$CMD = '"'. $settingsData['mktorrent_location'] .'" -l '. $pieceSize .' -v -p -s "'. $dummySite .'" -a '. $dummyAnnouncer .' -o "'. $cygPathDestination .'"'. $newTorrentName .' "'. $cygPathSource .'"';
			exec($CMD, $createTorrent);
			$result = $createTorrent[count($createTorrent) - 1];

			logger('torrent creation CMD='. $CMD, __LINE__, __FILE__, 2);
			foreach ($createTorrent as $key => $val)
			{
				logger('['. $key .'] => '. $val, __LINE__, __FILE__, 3);
			}
			
			moveExtraFilesIn($fileParts['path']);
			
			$infoFile = fopen($torrentInfo, 'w+');
			fwrite($infoFile, json_encode($info));
			fclose($infoFile);
			
			logger('converting tmp torrent for each announcer', __LINE__, __FILE__, 2);
			//-- COPY THE TMP .torrent AND .info TO PROPER SITE FOLDERS, THIS METHOD CREATES 1 SET OF IMAGES & FILES AND THEN USES THEM FOR ALL SITES
			foreach ($_POST['process_announcers'] as $announcer)
			{
				$parts = explode('|', $announcer);
				$announcerName = trim((count($parts) == 2) ? $parts[0] : 'No Site Name');
				$announcerUrl = trim((count($parts) == 2) ? $parts[1] : $parts[0]);

				logger('$announcerName='. $announcerName, __LINE__, __FILE__, 3);
				logger('$announcerUrl='. $announcerUrl, __LINE__, __FILE__, 3);
				
				if (!is_dir('_output/'. $announcerName))
				{
					logger('creating directory: _output/'. $announcerName, __LINE__, __FILE__, 3);
					mkdir('_output/'. $announcerName);
				}

				//-- GET SOURCE FROM TMP
				$torrentFileSource = file_get_contents($torrentFile);
				$torrentInfoSource = file_get_contents($torrentInfo);
				
				//-- UPDATE announce AND THE LENGTH
				$torrentFileSource = str_replace($dummyAnnouncer, $announcerUrl, $torrentFileSource);
				$torrentFileSource = str_replace('announce'. strlen($dummyAnnouncer) .':', 'announce'. strlen($announcerUrl) .':', $torrentFileSource);
				
				//-- UPDATE source AND THE LENGTH
				$torrentFileSource = str_replace($dummySite, $announcerName, $torrentFileSource);
				$torrentFileSource = str_replace('source'. strlen($dummySite) .':', 'source'. strlen($announcerName) .':', $torrentFileSource);
				
				$torrentInfoSource = str_replace($dummyAnnouncer, $announcerUrl, $torrentInfoSource);
				$torrentInfoSource = str_replace($dummySite, $announcerName, $torrentInfoSource);
				
				if (!is_dir('_output/'. $announcerName .'/'. $finalName))
				{
					logger('creating directory: _output/'. $announcerName .'/'. $finalName, __LINE__, __FILE__, 3);
					mkdir('_output/'. $announcerName .'/'. $finalName);
				}

				$fopen = fopen(str_replace('_tmp/', '_output/'. $announcerName .'/'. $finalName .'/', $torrentFile), 'wb');
				fwrite($fopen, $torrentFileSource);
				fclose($fopen);
					
				$fopen = fopen(str_replace('_tmp/', '_output/'. $announcerName .'/'. $finalName .'/', $torrentInfo), 'w+');
				fwrite($fopen, $torrentInfoSource);
				fclose($fopen);

				$mediaInfo = json_decode($info['mediaInfo'], true);
				$fopen = fopen(str_replace('_tmp/', '_output/'. $announcerName .'/'. $finalName .'/', $torrentMediaInfo), 'w+');
				fwrite($fopen, $mediaInfo['full']);
				fclose($fopen);

				$fopen = fopen(str_replace('_tmp/', '_output/'. $announcerName .'/'. $finalName .'/', $torrentDesc), 'w+');
				fwrite($fopen, buildNfoFromTemplate($announcerName, $info));
				fclose($fopen);
				
				$torrents[] = '_output/'. $announcerName .'/'. $finalName;
			}
		}
		else
		{
			logger('torrent skipped', __LINE__, __FILE__, 2);
		}

		//-- REMOVE TMP FILES
		@unlink($torrentFile);
		@unlink($torrentInfo);
	}

	logger('createTorrentFile() completed', __LINE__, __FILE__, 1);
	
	return $torrents;
}
?>
