<?php

/*
----------------------------------
 ------  Created: 042321   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (file_exists('tmp/xseed')) {
	exit('tmp/xseed exists, already running.');
}

include('xseed.constants.php');
include('xseed.qbittorrent.php');
include('xseed.jackett.php');
include('xseed.torrents.php');

$history = json_decode(@file_get_contents('xseed.history.json'), true);

if (file_exists('xseed.history.json') && !$history) {
    exit('The history file (xseed.history.json) has broken JSON, fix it or delete the file and try again.');
}

touch('tmp/xseed');

$qbtCookie 	= qbt_login();
$qbtItems 	= qbt_queue($qbtCookie);

if (!$qbtItems) {
	echo 'Nothing found in QBT queue.';
} else {
    echo '<h3>Jackett</h3>';

	foreach ($qbtItems as $qbtItem) {
		if (@in_array($qbtItem['name'], $history)) {
            $skipping[] = 'Skipping ' . $qbtItem['name'] . ' since it was already searched';
			continue;
		}

		$history[] = $qbtItem['name'];

		//-- FIND OTHER SITES THAT HAVE IT
		$search = jackett_search($qbtItem['name']);	
		$search = $search['Results'];

		if (!$search) {
			continue;
		}

		foreach ($search as $match) {
			if (jackett_download($match)) {
				$tmpSeeds++;
			}
		}

		if (!$tmpSeeds) {
			echo 'No sites found with '. $qbtItem['name'] .'<br>';
		} else {
			//-- ADD TO QBT AND POINT TO EXISTING LOCATION
			$dir = opendir('tmp');
			while ($file = readdir($dir)) {
				if (strpos($file, '.torrent') !== false) {
					$add = qbt_addTorrent($qbtCookie, 'tmp/'. $file, $qbtItem);
					if (!$add) {
						$added[] = 'xseed added for '. $file;
					} else {
						$failed[] = 'could not xseed '. $file .' - '. $add;
					}
					@unlink('tmp/'. $file);
				}
			}
			closedir($dir);
		}
	}

    //-- JACKETT ABOVE
    echo '<br><hr><br>';

    if ($added) {
        echo '<h3>Added</h3>' . implode('<br>', $added) . '<br><hr><br>';
    }

    if ($failed) {
        echo '<h3>Failed</h3>' . implode('<br>', $failed) . '<br><hr><br>';
    }

    if ($skipping) {
        echo '<h3>Skipping</h3>' . implode('<br>', $skipping) . '<br><hr><br>';
    }
}

file_put_contents('xseed.history.json', json_encode($history, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
unlink('tmp/xseed');
?>
