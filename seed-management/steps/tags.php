<?php

/*
----------------------------------
 ------  Created: 092219   ------
 ------  Austin Best	   ------
----------------------------------
*/

$torrentTagsAdded = $taggingIndexersSkipped = 0;
$torrentTagsSkipped = [];

foreach ($indexers as $indexerIndex => $indexer) {
	if (!$indexer['TAG']) {
		$taggingIndexersSkipped++;
		output('tags', 'Skipping index ' . $indexerIndex . ', no TAG set', ['print' => true, 'log' => true]);
		continue;
	}

	output('tags', 'Checking index ' . $indexerIndex . ' with TAG "' . $indexer['TAG'] . '"', ['print' => true, 'log' => true]);

	foreach ($qbtItems as $torrentIndex => $torrent) {
		//-- PAUSED HAVE NO TRACKER
		if (!$torrent['tracker']) {
			$trackers = $qbtApi->getTorrentTrackers($torrent['hash']);
			$torrent['tracker'] = $trackers[0]['url'];
		}

		$indexerSettings = [];
		foreach ($indexer['ANNOUNCERS'] as $announcer) {
			if (strpos(strtolower($torrent['tracker']), strtolower($announcer)) !== false) {
				$indexerSettings = $indexer;
				break;
			}
		}

		if (empty($indexerSettings)) {
			continue;
		}

		if (checkIgnoreRules('tags', $torrent)) {
			$torrentTagsSkipped[$torrent['hash']] = true;
			continue;			
		}

		$torrentTags 	= explode(',', $torrent['tags']);
		$exists 		= false;

		foreach ($indexer['ANNOUNCERS'] as $announcer) {
			if (strpos(strtolower($torrent['tracker']), strtolower($announcer)) !== false) {
				$exists = $qbtApi->tagExists($indexer['TAG'], $torrentTags);
				break;
			}
		}
		
		if (!$exists) {
			$output = (DRY_RUN_TAGS ? 'DRY_RUN_TAGS ' : '') . 'Applying tag "'. $indexer['TAG'] .'" to item "'. $torrent['name'] .'"';
			output('tags', $output, ['print' => true, 'log' => true]);
			output('tags', 'existing tags: ' . $torrent['tags'], ['log' => true]);

			if (!DRY_RUN_TAGS) {
				$qbtApi->addTagToTorrent($indexer['TAG'], $torrent['hash']);
				$torrentTagsAdded++;
			}
		}
			
	}
}
$torrentTagsSkipped = count($torrentTagsSkipped);

output('tags', 'Missing tag results:', ['print' => true, 'log' => true]);
output('tags', 'Indexers skipped: ' . $taggingIndexersSkipped, ['print' => true, 'log' => true]);
output('tags', 'Tags added: ' . $torrentTagsAdded, ['print' => true, 'log' => true]);
output('tags', 'Torrents skipped: ' . $torrentTagsSkipped, ['print' => true, 'log' => true]);

