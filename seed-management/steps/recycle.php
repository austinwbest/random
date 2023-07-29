<?php

/*
----------------------------------
 ------  Created: 092219   ------
 ------  Austin Best	   ------
----------------------------------
*/

$recycleError = false;
if (!RECYCLE_RULE_MATCHES) {
	$recycleError = true;
	output('recycle', 'Skipping recycle cleanup step, RECYCLE_RULE_MATCHES is false', ['print' => true, 'log' => true]);
}

if (!RECYCLE_HOLD) {
	$recycleError = true;
	output('recycle', 'Skipping recycle cleanup step, missing RECYCLE_HOLD', ['print' => true, 'log' => true]);
}

if (!RECYCLE_PATH) {
	$recycleError = true;
	output('recycle', 'Skipping recycle cleanup step, missing RECYCLE_PATH', ['print' => true, 'log' => true]);
}

$recycledItems = [];
$recycleItems = $recycleDiskSize = 0;

if (!$recycleError) {
	$dir = opendir(RECYCLE_PATH);
	while ($item = readdir($dir)) {
		if ($item[0] != '.') {
			$recycleItems++;
			$recycleTime 	= filemtime(RECYCLE_PATH . $item);
			$daysOld		= daysBetween($recycleTime);

			if ($daysOld > RECYCLE_HOLD) {
				$recycleDiskSize += size(RECYCLE_PATH . $item);

				$recycleMessage = (DRY_RUN_RECYCLE ? 'DRY_RUN_RECYCLE ' : '') . 'Removing "' . RECYCLE_PATH . $item . '", ' . $daysOld . ' days old > ' . RECYCLE_HOLD . ' day setting';
				output('recycle', $recycleMessage, ['print' => true, 'log' => true]);

				if (!DRY_RUN_RECYCLE) {
					$recycledItems[] = RECYCLE_PATH . $item;

					if (is_dir(RECYCLE_PATH . $item)) {
						removeDirectory(RECYCLE_PATH . $item);
					} else {
						removeFile(RECYCLE_PATH . $item);
					}
				}
			}
		}
	}
	closedir($dir);
}

output('recycle', 'Recycle cleanup results:', ['print' => true, 'log' => true]);
output('recycle', 'Items: ' . number_format($recycleItems), ['print' => true, 'log' => true]);
output('recycle', 'Recycled: ' . count($recycledItems), ['print' => true, 'log' => true]);
output('recycle', 'Size: ' . byteConversion($recycleDiskSize), ['print' => true, 'log' => true]);
