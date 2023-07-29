<?php

/*
----------------------------------
 ------  Created: 111022   ------
 ------  Austin Best	   ------
----------------------------------
*/

$torrentErrorsSkipped = [];
$torrentIssueTagsAdded = $torrentIssueTagsRemoved = $torrentIssuePaused = 0;

foreach ($qbtItems as $torrent) {
	$trackers 	= $qbtApi->getTorrentTrackers($torrent['hash']);
	$fullError 	= true;

	foreach ($trackers as $tracker) {
		if ($tracker['status'] == 2) {
			$fullError = false;
			break;
		}
	}

	if (!$fullError) {
		if (TAG_ISSUES && ISSUES_TAG) {
			$torrentTags = explode(',', $torrent['tags']);
			$exists = $qbtApi->tagExists(ISSUES_TAG, $torrentTags);

			if ($exists) {
				if (checkIgnoreRules('errors', $torrent)) {
					$torrentErrorsSkipped[$torrent['hash']] = true;
					continue;			
				}

				$errorsMessage = (DRY_RUN_ERRORS ? 'DRY_RUN_ERRORS ' : '') . 'Announcer has at least one working, removing ' . ISSUES_TAG . ' from ' . $torrent['name'];
				output('errors', $errorsMessage, ['print' => true, 'log' => true]);

				if (!DRY_RUN_ERRORS) {
					$qbtApi->removeTagFromTorrent(ISSUES_TAG, $torrent['hash']);
					$torrentIssueTagsRemoved++;
				}
			}
		}
	}

	if ($fullError) {
		if (checkIgnoreRules('errors', $torrent)) {
			$torrentErrorsSkipped[$torrent['hash']] = true;
			continue;			
		}

		$errorsMessage = (DRY_RUN_ERRORS ? 'DRY_RUN_ERRORS ' : '') . 'All announcers have errors for ' . $torrent['name'];
		output('errors', $errorsMessage, ['print' => true, 'log' => true]);

		if (!DRY_RUN_ERRORS) {
			if (TAG_ISSUES && ISSUES_TAG) {
				$torrentTags 	= explode(',', $torrent['tags']);
				$exists 		= false;
	
				foreach ($indexer['ANNOUNCERS'] as $announcer) {
					if (strpos(strtolower($torrent['tracker']), strtolower($announcer)) !== false) {
						$exists = $qbtApi->tagExists(ISSUES_TAG, $torrentTags);
						break;
					}
				}

				if (!$exists) {
					$qbtApi->addTagToTorrent(ISSUES_TAG, $torrent['hash']);
					$torrentIssueTagsAdded++;
				}
			}

			if (PAUSE_ISSUES) {
				$qbtApi->pause($torrent['hash']);
				$torrentIssuePaused++;
			}
		}
	}
}

$torrentErrorsSkipped = count($torrentErrorsSkipped);

output('errors', 'Error announcer results:', ['print' => true, 'log' => true]);
output('errors', 'Tags added: ' . $torrentIssueTagsAdded, ['print' => true, 'log' => true]);
output('errors', 'Tags removed: ' . $torrentIssueTagsRemoved, ['print' => true, 'log' => true]);
output('errors', 'Paused torrents: ' . $torrentIssuePaused, ['print' => true, 'log' => true]);
output('errors', 'Torrents skipped: ' . $torrentErrorsSkipped, ['print' => true, 'log' => true]);

