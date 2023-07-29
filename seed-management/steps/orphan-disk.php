<?php

/*
----------------------------------
 ------  Created: 020423   ------
 ------  Austin Best	   ------
----------------------------------
*/

$orphanDiskError 	= false;
$orphanDiskTorrents = [];
$orphanDiskSize 	= $orphanDiskTorrentsMoved = $orphanDiskTorrentsDeleted = 0;

if (!SEED_PATH) {
	$orphanDiskError = true;
	output('orphan-disk', 'Skipping orphan-disk step, missing SEED_PATH', ['print' => true, 'log' => true]);
}

if (!$orphanDiskError) {
	$dir = opendir(SEED_PATH);
	while ($item = readdir($dir)) {
		if ($item[0] != '.') {
			$orphanDiskTorrents[] = SEED_PATH . $item;
		}
	}
	closedir($dir);

	output('orphan-disk', 'Checking ' . count($orphanDiskTorrents) . ' disk torrents for matching qbt torrents...', ['print' => true, 'log' => true]);

	foreach ($qbtItems as $torrent) {
		foreach ($orphanDiskTorrents as $orphanDiskIndex => $orphanDiskTorrent) {
			if ($orphanDiskTorrent == $torrent['content_path'] || strpos($torrent['content_path'], $orphanDiskTorrent) !== false) {
				unset($orphanDiskTorrents[$orphanDiskIndex]);
				break;
			}
		}
	}

	if (!empty($orphanDiskTorrents)) {
		foreach ($orphanDiskTorrents as $orphanDiskTorrent) {
			output('orphan-disk', 'Orphaned disk item found, ' . $orphanDiskTorrent, ['print' => true, 'log' => true]);
			$orphanDiskSize += size($orphanDiskTorrent);

			if (RECYCLE_DISK_ORPHANS && RECYCLE_PATH) {
				//-- CHECK FOR DISK SPACE FIRST
				if ($orphanDiskSize < $recycleFreeSpaceBytes) {
					$orphanMessage = (DRY_RUN_ORPHAN_DISK ? 'DRY_RUN_ORPHAN_DISK ' : '') . 'Recycled orphan';
					output('orphan-disk', $orphanMessage, ['print' => true, 'log' => true]);
					$recycleFreeSpaceBytes -= $orphanDiskSize;
					$torrentNameParts = explode('\\', $orphanDiskTorrent);
					$torrentName = $torrentNameParts[count($torrentNameParts) - 1];

					$orphanMessage = (DRY_RUN_ORPHAN_DISK ? 'DRY_RUN_ORPHAN_DISK ' : '') . 'Moving "' . $orphanDiskTorrent . '" to "' . RECYCLE_PATH . $torrentName . '"';
					output('orphan-disk', $orphanMessage, ['log' => true]);

					if (!DRY_RUN_ORPHAN_DISK) {
						$moved = move($orphanDiskTorrent, RECYCLE_PATH . $torrentName);

						if ($moved) {
							$orphanDiskTorrentsMoved++;
						}
					}
				} else {
					output('orphan-disk', 'Skipping orphan recycle, not enough disk space on "' . RECYCLE_DRIVE . '"', ['print' => true, 'log' => true]);
				}

			} elseif (DELETE_DISK_ORPHANS) {
				$orphanMessage = (DRY_RUN_ORPHAN_DISK ? 'DRY_RUN_ORPHAN_DISK ' : '') . 'Deleted orphan';
				output('orphan-disk', $orphanMessage, ['print' => true, 'log' => true]);

				if (!DRY_RUN_ORPHAN_DISK) {
					$orphanDiskTorrentsDeleted++;
					removeFolderPlusContents($orphanDiskTorrent);
				}
			}
		}
	}
}

output('orphan-disk', 'Orphan disk results:', ['print' => true, 'log' => true]);
output('orphan-disk', 'Orphans: ' . count($orphanDiskTorrents), ['print' => true, 'log' => true]);
output('orphan-disk', 'Moved: ' . number_format($orphanDiskTorrentsMoved), ['print' => true, 'log' => true]);
output('orphan-disk', 'Deleted: ' . number_format($orphanDiskTorrentsDeleted), ['print' => true, 'log' => true]);
output('orphan-disk', 'Size: ' . byteConversion($orphanDiskSize), ['print' => true, 'log' => true]);
