<?php

/*
----------------------------------
 ------  Created: 072223   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

$notification = [];

$runStart = microtime(true);

date_default_timezone_set('America/New_York');
ini_set('max_execution_time', '-1');
ini_set('memory_limit', '-1');
set_time_limit(0);

define('HOME_DIR', __DIR__ . '\\');

require 'includes/constants.php';
require 'functions/common.php';
require 'classes/qbt.php';

define('LOG_FILE', LOG_PATH && LOG_RUN ? LOG_PATH . time() . '.txt': '');

if (empty($indexers)) {
	exit('$indexers is empty in contants.php');
}

$qbtApi 		= new qbtApi(QBT_URL, QBT_USER, QBT_PASS);
$qbtItems 		= $qbtApi->queue();
$totalTorrents 	= count($qbtItems);

$seedFreeSpace = $seedFreeSpaceBytes = 0;
if (SEED_DRIVE) {
	$seedFreeSpaceBytes = getDriveFreespace(SEED_DRIVE);
	$seedFreeSpace 		= byteConversion($seedFreeSpaceBytes, 'GiB');
}

$recycleFreeSpace = $recycleFreeSpaceBytes = 0;
if (RECYCLE_DRIVE) {
	$recycleFreeSpaceBytes 	= getDriveFreespace(RECYCLE_DRIVE);
	$recycleFreeSpace 		= byteConversion($recycleFreeSpaceBytes, 'GiB');
}

output('main', 'Drive check for SEED_DRIVE: ' . $seedFreeSpace . ' GiB free', ['print' => true, 'log' => true]);
output('main', 'Drive check for RECYCLE_DRIVE: ' . $recycleFreeSpace . ' GiB free', ['print' => true, 'log' => true]);
output('main', 'Log file: ' . LOG_FILE, ['print' => true, 'log' => true]);
output('main', 'Total torrents found: ' . number_format($totalTorrents), ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- INDEXER TAG MANAGEMENT
/*
	This step will loop the indexers in constants.php and check against the qbt torrent list to:
		Tag any that have matches and are missing tags
*/
output('main', 'Tag management started...', ['print' => true, 'log' => true]);
if (SKIP_TAGS) {
	output('main', 'Tag management skipped SKIP_TAGS=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/tags.php';
	$stepFinish = microtime(true);
	$stepTagsRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepTagsRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Tag management completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- CHECK FOR REMOVAL
/*
	This step will loop the indexers in constants.php and check against the qbt torrent list to:
		Remove any that meet the criteria for removal
*/
output('main', 'Removal started...', ['print' => true, 'log' => true]);
if (SKIP_REMOVAL) {
	output('main', 'Removal skipped SKIP_REMOVAL=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/removal.php';
	$stepFinish = microtime(true);
	$stepRemovalRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepRemovalRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Removal completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- RECYCLE BIN CLEANUP
/*
	This step will loop the recycle bin contents to:
		Remove anything that is older than allowed
*/
output('main', 'Recycle cleanup started...', ['print' => true, 'log' => true]);
if (SKIP_RECYCLE) {
	output('main', 'Recycle cleanup skipped SKIP_RECYCLE=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/recycle.php';
	$stepFinish = microtime(true);
	$stepRecycleRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepRecycleRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Recycle cleanup completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- ERROR TORRENTS
/*
	This step will loop the indexers in constants.php and check against the qbt torrent list to:
		Find any torrents that have errors on all announce urls
*/
output('main', 'Announce error checking started...', ['print' => true, 'log' => true]);
if (SKIP_ERRORS) {
	output('main', 'Announce error skipped SKIP_ERRORS=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/errors.php';
	$stepFinish = microtime(true);
	$stepErrorsRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepErrorsRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Announce error checking completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- ORPHANED IN QBT
/*
	This step will loop the indexers in constants.php and check against the qbt torrent list to:
		Find anything in qbt that does not have data on disk
*/
output('main', 'QBT orphan checking started...', ['print' => true, 'log' => true]);
if (SKIP_ORPHAN_QBT) {
	output('main', 'QBT orphan skipped SKIP_ORPHAN_QBT=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/orphan-qbt.php';
	$stepFinish = microtime(true);
	$stepOrphanQbtRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepOrphanQbtRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'QBT orphan checking completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

//-- ORPHANED ON DISK
/*
	This step will loop the seed directory to:
		Find anything on disk that does not have data in qbt
*/
output('main', 'Disk orphan checking started...', ['print' => true, 'log' => true]);
if (SKIP_ORPHAN_DISK) {
	output('main', 'Disk orphan skipped SKIP_ORPHAN_DISK=true', ['print' => true, 'log' => true]);
} else {
	$stepStart = microtime(true);
	require 'steps/orphan-disk.php';
	$stepFinish = microtime(true);
	$stepOrphanDiskRuntime = number_format(($stepFinish - $stepStart), 2);
}
output('main', 'Runtime: ' . $stepOrphanDiskRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Disk orphan checking completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

$runEnd = microtime(true);

//-- SEND NOTIFICATION
/*
	This step will take all the information from other steps and put it in a notification for Notifiarr
*/
output('main', 'Notification started...', ['print' => true, 'log' => true]);
$stepStart = microtime(true);
require 'steps/notification.php';
$stepFinish = microtime(true);
$stepNotificationRuntime = number_format(($stepFinish - $stepStart), 2);
output('main', 'Runtime: ' . $stepNotificationRuntime . 's', ['print' => true, 'log' => true]);
output('main', 'Notification completed.', ['print' => true, 'log' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);
output('main', '------------------------------------------------------------', ['print' => true]);

output('main', 'Total runtime: ' . number_format(($runEnd - $runStart), 2) . 's', ['print' => true, 'log' => true]);
