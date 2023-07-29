<?php

/*
----------------------------------
 ------  Created: 072423   ------
 ------  Austin Best	   ------
----------------------------------
*/

$notifyError = false;

if (!NOTIFIARR_APIKEY) {
	$notifyError = true;
	output('notify', 'Skipping notification step, NOTIFIARR_APIKEY is empty', ['print' => true, 'log' => true]);
}

if (!DISCORD_CHANNEL) {
	$notifyError = true;
	output('notify', 'Skipping notification step, DISCORD_CHANNEL is empty', ['print' => true, 'log' => true]);
}

if (!NOTIFY_RUN) {
	$notifyError = true;
	output('notify', 'Skipping notification step, NOTIFY_RUN is false', ['print' => true, 'log' => true]);
}

if (!$notifyError) {
	$liveRun 	= [];
	$dryRun 	= ['Tags', 'Removals', 'Recycle cleanup', 'Errors', 'QBT Orphans', 'Disk Orphans'];
	$tagData = $removalData = $recycleData = $errorData = $qbtOrphanData = $driveOrphanData = '';
	if (!DRY_RUN_TAGS) {
		unset($dryRun[0]);
		$liveRun[] = 'Tags';
		$tagData = 'Indexers skipped: ' . number_format($taggingIndexersSkipped) . "\n";
		$tagData .= 'Torrents skipped: ' . number_format($torrentTagsSkipped) . "\n";
		$tagData .= 'Tags added: ' . number_format($torrentTagsAdded) . "\n";
	}

	if (!DRY_RUN_REMOVAL) {
		unset($dryRun[1]);
		$liveRun[] = 'Removals';
		$removalData = 'Skipped: ' . number_format(count($removeSkipped)) . "\n";
		$removalData .= 'Tagged: ' . number_format($removalTagsAdded) . "\n";
		$removalData .= 'Paused: ' . number_format($removalTorrentsPaused) . "\n";
		$removalData .= 'Recycled: ' . number_format($removeTorrentsRecycled) . "\n";
		$removalData .= 'Deleted: ' . number_format($removeTorrentsDeleted) . "\n";
		$removalData .= 'Size: ' . byteConversion($removalSize) . "\n";
	}

	if (!DRY_RUN_RECYCLE) {
		unset($dryRun[2]);
		$liveRun[] = 'Recycle cleanup';
		$recycleData = 'Items: ' . number_format($recycleItems) . "\n";
		$recycleData .= 'Recycled: ' . number_format(count($recycledItems)) . "\n";
		$recycleData .= 'Size: ' . byteConversion($recycleDiskSize) . "\n";
	}

	if (!DRY_RUN_ERRORS) {
		unset($dryRun[3]);
		$liveRun[] = 'Errors';
		$errorData = 'Skipped: ' . number_format($torrentErrorsSkipped) . "\n";
		$errorData .= 'Tags added: ' . number_format($torrentIssueTagsAdded) . "\n";
		$errorData .= 'Tags removed: ' . number_format($torrentIssueTagsRemoved) . "\n";
		$errorData .= 'Paused: ' . number_format($torrentIssuePaused) . "\n";
	}

	if (!DRY_RUN_ORPHAN_QBT) {
		unset($dryRun[4]);
		$liveRun[] = 'QBT Orphans';
		$qbtOrphanData = 'Skipped: ' . number_format(count($orphanTagsSkipped)) . "\n";
		$qbtOrphanData .= 'Orphans: ' . number_format(count($orphanedQbtTorrents)) . "\n";
		$qbtOrphanData .= 'Tags added: ' . number_format($orphanTagsAdded) . "\n";
		$qbtOrphanData .= 'Tags removed: ' . number_format($orphanTagsRemoved) . "\n";
	}

	if (!DRY_RUN_ORPHAN_DISK) {
		unset($dryRun[5]);
		$liveRun[] = 'Disk Orphans';
		$driveOrphanData = 'Orphans: ' . number_format(count($orphanDiskTorrents)) . "\n";
		$driveOrphanData .= 'Moved: ' . number_format($orphanDiskTorrentsMoved) . "\n";
		$driveOrphanData .= 'Deleted: ' . number_format($orphanDiskTorrentsDeleted) . "\n";
		$driveOrphanData .= 'Size: ' . byteConversion($orphanDiskSize) . "\n";
	}

	$fields[] = ['title' => 'Indexers', 'text' => number_format(count($indexers)), 'inline' => true];
	$fields[] = ['title' => 'Torrents', 'text' => number_format(count($qbtItems)), 'inline' => true];
	$fields[] = ['title' => 'Run time', 'text' => seconds2relative($runEnd - $runStart) .'s', 'inline' => true];
	$fields[] = ['title' => 'Dry run', 'text' => (!empty($dryRun) ? implode(', ', $dryRun) : 'None'), 'inline' => false];
	$fields[] = ['title' => 'Live run', 'text' => (!empty($liveRun) ? implode(', ', $liveRun) : 'None'), 'inline' => false];

	if ($tagData) {
		$fields[] = ['title' => 'Tag results', 'text' => '```' . $tagData . '```', 'inline' => false];
	}

	if ($removalData) {
		$fields[] = ['title' => 'Removal results', 'text' => '```' . $removalData . '```', 'inline' => false];
	}

	if ($recycleData) {
		$fields[] = ['title' => 'Recycle cleanup results', 'text' => '```' . $recycleData . '```', 'inline' => false];
	}

	if ($errorData) {
		$fields[] = ['title' => 'Error results', 'text' => '```' . $errorData . '```', 'inline' => false];
	}

	if ($qbtOrphanData) {
		$fields[] = ['title' => 'QBT Orphan Results', 'text' => '```' . $qbtOrphanData . '```', 'inline' => false];
	}

	if ($driveOrphanData) {
		$fields[] = ['title' => 'Disk Orphan Results', 'text' => '```' . $driveOrphanData . '```', 'inline' => false];
	}

	if (LOG_FILE) {
		$fields[] = ['title' => 'Log file', 'text' => '`' . LOG_FILE . '`', 'inline' => false];
	}

	$notifiarr = [];
	$notifiarr['notification'] 	= [
									'update' 	=> false, 
									'name' 		=> 'Seed Management', 
									'event' 	=> 0
									];
	$notifiarr['discord'] 		= [
									'color' 	=> 'FFA500',
									'ping' 		=> [
													'pingUser' 		=> '', 
													'pingRole' 		=> ''
													],
									'images' 	=> [
													'thumbnail' 	=> '', 
													'image' 		=> ''
													],
									'text' 		=> [
													'title' 		=> 'Run finished',
													'icon' 			=> 'https://notifiarr.com/images/logo/notifiarr.png',
													'content' 		=> '',
													'description' 	=> '',
													'fields' 		=> $fields,
													'footer' 		=> 'Passthrough Integration'
													],
									'ids' 		=> [
													'channel' 		=> DISCORD_CHANNEL
													]
									];

	$payload = json_encode($notifiarr);
	$url = 'https://notifiarr.com/api/v1/notification/passthrough/' . NOTIFIARR_APIKEY;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
	curl_setopt($ch, CURLOPT_POST, true);
	$result = curl_exec($ch);
	curl_close($ch);
}
