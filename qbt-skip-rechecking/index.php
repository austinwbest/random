<?php

/*
----------------------------------
 ------  Created: 020423   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require 'constants.php';
require 'qbittorrent.php';

if (!is_dir('tmp')) {
	mkdir('tmp');
}

if (!is_dir('tmp/added')) {
	mkdir('tmp/added');
}

$qbtCookie 	= qbt_login();
$qbtItems 	= qbt_queue($qbtCookie);

$checking = 0;
foreach ($qbtItems as $torrent) {
	if ($torrent['state'] != 'checkingUP') {
		continue;
	}

	$checking++;

	//-- MAKE A COPY OF IT
	if (copy(QBT_BACKUP_FOLDER . $torrent['hash'] . '.torrent', 'tmp\\' . $torrent['hash'] . '.torrent')) {
		echo $checking . '. Copied ' . $torrent['name'] . ' hash file to tmp/' . $torrent['hash'] . '<br>';

		//-- DELETE IT
		qbt_delete($qbtCookie, $torrent['hash']);

		//-- RE-ADD IT
		usleep(250000);
		if (!qbt_addTorrent($qbtCookie, $torrent)) {
			echo '&nbsp;&nbsp;&nbsp;&nbsp;Added to qbt.<br>';
			if (copy('tmp\\' . $torrent['hash'] . '.torrent', 'tmp\\added\\' . $torrent['hash'] . '.torrent')) {
				unlink('tmp\\' . $torrent['hash'] . '.torrent');
			}
		}
	} else {
		echo $checking . '. Failed to copy ' . $torrent['name'] . ' hash file to tmp/' . $torrent['hash'] . '<br>';
	}
}

echo '<br><br>Torrents in checking state: ' . $checking;
