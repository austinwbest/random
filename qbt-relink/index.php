<?php

/*
----------------------------------
 ------  Created: 071323   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

require 'constants.php';
require 'qbittorrent.php';

$regex = '/S([0-9]+)[ ]{0,1}E([0-9]+)/i';

$qbtCookie 	= qbt_login();
$qbtItems 	= qbt_queue();

foreach ($qbtItems as $torrent) {
	$diskFiles = $torrentFiles = [];

	if ($torrent['state'] == 'missingFiles') {
		echo 'Looking at data in ' . $torrent['save_path'] . ' to fix names in file ' . $torrent['name'] .'<br>';
		echo '&nbsp;&nbsp;<b>' . $torrent['save_path'] . '</b><br>';
		$dir = opendir($torrent['save_path']);
		while ($file = readdir($dir)) {
			if ($file[0] != '.' && (strpos($file, '.mp4') !== false || strpos($file, '.mkv') !== false)) {
				echo '&nbsp;&nbsp;&nbsp;&nbsp;- File: ' . $file .'<br>';
				$diskFiles[] = $file;
			}
		}
		closedir($dir);

		echo '&nbsp;&nbsp;<b>' . $torrent['name'] . '</b> ('. $torrent['hash'] .')<br>';
		$contents = qbt_torrentContents($torrent['hash']);
		foreach ($contents as $content) {
			echo '&nbsp;&nbsp;&nbsp;&nbsp;- File: ' . $content['name'] .'<br>';
			$torrentFiles[] = $content['name'];
		}

		echo '&nbsp;&nbsp;Counts: disk is ' . count($diskFiles) . ' and torrent is ' . count($torrentFiles) . '<br>';
		if (count($diskFiles) != count($torrentFiles)) {
			echo '&nbsp;&nbsp;Content counts do not match, can not fix this.';
			exit();
		}

		$renames = [];
		foreach ($torrentFiles as $torrentFileIndex => $torrentFile) {			
			if ($_GET['skipCountMismatch']) {
				$renames[] = ['old' => $torrentFile, 'new' => $diskFiles[$torrentFileIndex]];
			} else {
				if (strpos($torrent['save_path'], '\\Movies\\') !== false) {
					$renames[] = ['old' => $torrentFile, 'new' => $diskFiles[0]];
				} else {
					$torrentSeasonInfo = preg_match($regex, $torrentFile, $torrentSeasonEpisode);

					foreach ($diskFiles as $diskFile) {
						$diskSeasonInfo = preg_match($regex, $diskFile, $diskSeasonEpisode);		

						$torrentCheck 	= strtolower(str_replace(' ', '', $torrentSeasonEpisode[0]));
						$diskCheck 		= strtolower(str_replace(' ', '', $diskSeasonEpisode[0]));

						if ($torrentCheck == $diskCheck) {
							$renames[] = ['old' => $torrentFile, 'new' => $diskFile];
							break;
						}
					}
				}
			}
		}
		$renames = array_filter($renames);

		if (count($renames) != count($torrentFiles)) {
			echo '&nbsp;&nbsp;Rename count ('. count($renames) .') does not match torrentFiles count ('. count($torrentFiles) .'), can not fix this. Click <a href="index.php?skipCountMismatch=1">here</a> to try and ignore that.<br>';
			echo '<pre>';
			print_r($renames);
			print_r($torrentFiles);
			echo '</pre>';
			exit();
		}

		echo '&nbsp;&nbsp;<b>Renaming contents ('. count($renames) .')...</b><br>';
		foreach ($renames as $rename) {
			$renameItem = qbt_renameContents($torrent['hash'], $rename['old'], $rename['new']);
			if ($renameItem) {
				echo '&nbsp;&nbsp;&nbsp;&nbsp;Rename failed: old "'. $rename['old'] .'" new "'. $rename['new'] .'"<br>';
				exit();
			}

			echo '&nbsp;&nbsp;&nbsp;&nbsp;Renamed: old "'. $rename['old'] .'" new "'. $rename['new'] .'"<br>';
			usleep(150000);
		}
		
		echo '&nbsp;&nbsp;Rename complete<br>';

		if ($_GET['skipCountMismatch']) {
			echo '&nbsp;&nbsp;Skipping recheck because of an override used. Check the filenames manually.<br>';
		} else {
			echo '&nbsp;&nbsp;Starting a recheck<br>';
			qbt_recheckTorrent($torrent['hash']);
		}

		break;
	}
}
