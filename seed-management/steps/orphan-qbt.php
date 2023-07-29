<?php

/*
----------------------------------
 ------  Created: 020423   ------
 ------  Austin Best	   ------
----------------------------------
*/

$orphanedQbtTorrents = $orphanTagsSkipped = [];
$orphanTagsAdded = $orphanTagsRemoved = 0;

output('orphan-qbt', 'Checking ' . count($qbtItems) . ' content_path values for orphans...', ['print' => true, 'log' => true]);

foreach ($qbtItems as $torrent) {
	if (checkIgnoreRules('orphan-qbt', $torrent)) {
		$orphanTagsSkipped[$torrent['hash']] = true;
		continue;			
	}

	$torrentTags = explode(',', $torrent['tags']);
	$exists = $qbtApi->tagExists(ORPHAN_TAG, $torrentTags);

	if (!is_dir($torrent['content_path']) && !file_exists($torrent['content_path'])) {
		output('orphan-qbt', 'Orphaned qbt item found, ' . $torrent['name'], ['print' => true, 'log' => true]);
		$orphanedQbtTorrents[] = $torrent['name'];
		$orphanMessage = (DRY_RUN_ORPHAN_QBT ? 'DRY_RUN_ORPHAN_QBT ' : '') . 'Orphan tag added';
		output('orphan-qbt', $orphanMessage, ['print' => true, 'log' => true]);

		if (!DRY_RUN_ORPHAN_QBT && TAG_ORPHANS && ORPHAN_TAG && !$exists) {
			$qbtApi->addTagToTorrent(ORPHAN_TAG, $torrent['hash']);
			$orphanTagsAdded++;
		}
	} else {
		if (TAG_ORPHANS && ORPHAN_TAG) {
			if (!DRY_RUN_ORPHAN_QBT && $exists) {
				$orphanMessage = (DRY_RUN_ORPHAN_QBT ? 'DRY_RUN_ORPHAN_QBT ' : '') . 'Orphan tag removed';
				output('orphan-qbt', $orphanMessage, ['print' => true, 'log' => true]);

				$qbtApi->removeTagToTorrent(ORPHAN_TAG, $torrent['hash']);
				$orphanTagsRemoved++;
			}
		}
	}
}

output('orphan-qbt', 'Orphan qbt results:', ['print' => true, 'log' => true]);
output('orphan-qbt', 'Skipped: ' . count($orphanTagsSkipped), ['print' => true, 'log' => true]);
output('orphan-qbt', 'Orphans: ' . count($orphanedQbtTorrents), ['print' => true, 'log' => true]);
output('orphan-qbt', 'Tags added: ' . number_format($orphanTagsAdded), ['print' => true, 'log' => true]);
output('orphan-qbt', 'Tags removed: ' . number_format($orphanTagsRemoved), ['print' => true, 'log' => true]);

?>
