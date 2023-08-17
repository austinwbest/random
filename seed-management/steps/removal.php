<?php

/*
----------------------------------
 ------  Created: 092219   ------
 ------  Austin Best	   ------
----------------------------------
*/

output('removal', 'Checking ' . count($qbtItems) . ' torrents against removal rules', ['print' => true, 'log' => true]);

$remove = [];

foreach ($qbtItems as $torrent) {
    //-- PAUSED HAVE NO TRACKER
    if (!$torrent['tracker']) {
        $trackers = $qbtApi->getTorrentTrackers($torrent['hash']);
        $torrent['tracker'] = $trackers[0]['url'];
    }

    $indexerSettings = [];
    foreach ($indexers as $indexer) {
        foreach ($indexer['ANNOUNCERS'] as $announcer) {
            if (strpos(strtolower($torrent['tracker']), strtolower($announcer)) !== false) {
                $indexerSettings = $indexer;
                break;
            }
        }
    }

    if (empty($indexerSettings)) {
        continue;
    }

    $seedDays   = floor($torrent['seeding_time'] / 86400);
    $ratio      = $torrent['ratio'];

    //-- CHECK FOR "MIN_LENGTH"
    if ($indexerSettings['MIN_LENGTH'] && ($seedDays > $indexerSettings['MIN_LENGTH'])) {
        $torrent['indexerSettings'] = $indexerSettings;
        $torrent['reason']          = 'MIN_LENGTH';
        $remove[$torrent['hash']]   = $torrent;
    }
    //-- CHECK FOR "MAX_LENGTH"
    if ($indexerSettings['MAX_LENGTH'] && ($seedDays > $indexerSettings['MAX_LENGTH'])) {
        $torrent['indexerSettings'] = $indexerSettings;
        $torrent['reason']          = 'MAX_LENGTH';
        $remove[$torrent['hash']]   = $torrent;
    }
    //-- CHECK FOR "SATISFY_RATIO"
    if ($indexerSettings['SATISFY_RATIO'] && ($ratio > $indexerSettings['MIN_RATIO'])) {
        $torrent['indexerSettings'] = $indexerSettings;
        $torrent['reason']          = 'SATISFY_RATIO';
        $remove[$torrent['hash']]   = $torrent;
    }
}

output('removal', 'Checking ' . count($remove) . ' possible torrents for removal', ['print' => true, 'log' => true]);

$removeSkipped = [];
$removalTagsAdded = $removalTorrentsPaused = $removeTorrentsRecycled = $removeTorrentsDeleted = $removalSize = 0;
if ($remove) {
    foreach ($remove as $torrent) {
        $indexerSettings = $torrent['indexerSettings'];

		if (checkIgnoreRules('removal', $torrent)) {
			$removeSkipped[$torrent['hash']] = true;
			continue;			
		}

        $ratio      = $torrent['ratio'];
        $activeDays = daysBetween($torrent['last_activity']);
        $age        = $torrent['completed'];
        $seedDays   = floor($torrent['seeding_time'] / 86400);
        $tags 	    = explode(',', $torrent['tags']);

        if ((RECYCLE_RULE_MATCHES && RECYCLE_PATH) || DELETE_RULE_MATCHES) {
            if (RECYCLE_RULE_MATCHES && RECYCLE_PATH) {
                switch ($torrent['reason']) {
                    case 'SATISFY_RATIO':
                        //-- CHECK FOR DISK SPACE FIRST
                        if ($torrent['total_size'] < $recycleFreeSpaceBytes) {
                            $message = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Moving [' . $indexerSettings['TAG'] . '] torrent (SATISFY_RATIO) "' . $torrent['name'] . '" to "' . RECYCLE_PATH . $torrent['name'] . '"';
                            output('removal', $message, ['print' => true, 'log' => true]);

                            if (!DRY_RUN_REMOVAL) {
                                $moved = move($torrent['content_path'], RECYCLE_PATH . $torrent['name']);

                                if ($moved) {
                                    $removalSize += $torrent['total_size'];
                                    $removeTorrentsRecycled++;
                                    $qbtApi->remove($torrent['hash']);
                                }
                            }
                        } else {
                            output('removal', 'Skipping removal recycle "' . $torrent['name'] . '", not enough disk space on "' . RECYCLE_DRIVE . '"', ['log' => true]);
                            $removeSkipped[] = $torrent;
                        }
                        break;
                    case 'MIN_LENGTH':
                    case 'MAX_LENGTH':
                        if ($indexerSettings['KEEP_UNTIL_SPACE'] && MIN_DISK_SPACE && $seedFreeSpaceBytes > round(MIN_DISK_SPACE * pow(1024, 3))) {
                            output('removal', 'Skipping removal recycle "' . $torrent['name'] . '", indexer setting KEEP_UNTIL_SPACE set and there is more than MIN_DISK_SPACE', ['log' => true]);
                            $removeSkipped[] = $torrent;
                            continue;
                        }

                        if ($indexerSettings['KEEP_ACTIVE'] && ACTIVE_SEED && $activeDays < ACTIVE_SEED) {
                            output('removal', 'Skipping removal recycle of "' . $torrent['name'] . '", indexer setting KEEP_ACTIVE set and torrent active within the last ACTIVE_SEED', ['log' => true]);
                            $removeSkipped[] = $torrent;
                            continue;
                        }


                        //-- CHECK FOR DISK SPACE FIRST
                        if ($torrent['total_size'] < $recycleFreeSpaceBytes) {
                            $message = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Moving [' . $indexerSettings['TAG'] . '] torrent (MIN/MAX_LENGTH) "' . $torrent['name'] . '" to "' . RECYCLE_PATH . $torrent['name'] . '"';
                            output('removal', $message, ['print' => true, 'log' => true]);

                            if (!DRY_RUN_REMOVAL) {
                                $moved = move($torrent['content_path'], RECYCLE_PATH . $torrent['name']);

                                if ($moved) {
                                    $removalSize += $torrent['total_size'];
                                    $removeTorrentsRecycled++;
                                    $qbtApi->remove($torrent['hash']);
                                }
                            }
                        } else {
                            output('removal', 'Skipping removal recycle "' . $torrent['name'] . '", not enough disk space on "' . RECYCLE_DRIVE . '"', ['log' => true]);
                            $removeSkipped[] = $torrent;
                        }
                        break;
                }
            } elseif (DELETE_RULE_MATCHES) {
                switch ($torrent['reason']) {
                    case 'SATISFY_RATIO':
                        $message = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Deleting [' . $indexerSettings['TAG'] . '] torrent ' . $torrent['name'] . ' (SATISFY_RATIO)';
                        output('removal', $message, ['print' => true, 'log' => true]);

                        if (!DRY_RUN_REMOVAL) {
                            removeFolderPlusContents($torrent['content_path']);
                            $removeTorrentsDeleted++;
                            $removalSize += $torrent['total_size'];
                            $qbtApi->remove($torrent['hash']);
                        }
                        break;
                    case 'MIN_LENGTH':
                    case 'MAX_LENGTH':
                        if ($indexerSettings['KEEP_UNTIL_SPACE'] && MIN_DISK_SPACE && $seedFreeSpaceBytes > round(MIN_DISK_SPACE * pow(1024, 3))) {
                            output('removal', 'Skipping removal delete "' . $torrent['name'] . '", indexer setting KEEP_UNTIL_SPACE set and there is more than MIN_DISK_SPACE', ['log' => true]);
                            $removeSkipped[] = $torrent;
                            continue;
                        }

                        if ($indexerSettings['KEEP_ACTIVE'] && ACTIVE_SEED && $activeDays < ACTIVE_SEED) {
                            output('removal', 'Skipping removal delete "' . $torrent['name'] . '", indexer setting KEEP_ACTIVE set and torrent active within the last ACTIVE_SEED', ['log' => true]);
                            $removeSkipped[] = $torrent;
                            continue;
                        }

                        $message = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Deleting [' . $indexerSettings['TAG'] . '] torrent "' . $torrent['name'] . '" (MIN/MAX_LENGTH)';
                        output('removal', $message, ['print' => true, 'log' => true]);

                        if (!DRY_RUN_REMOVAL) {
                            removeFolderPlusContents($torrent['content_path']);
                            $removeTorrentsDeleted++;
                            $removalSize += $torrent['total_size'];
                            $qbtApi->remove($torrent['hash']);
                        }
                        break;
                }
            }
        } else {
            if (TAG_SATISFIED && SATISFIED_TAG) {
                $exists = $qbtApi->tagExists(SATISFIED_TAG, $tags);

                if (!$exists) {
                    $output = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Applying ' . $indexerSettings['TAG'] . ' tag "'. SATISFIED_TAG .'" to item "'. $torrent['name'] .'"';
                    output('removal', $output, ['print' => true, 'log' => true]);
                    output('removal', 'existing tags: ' . $torrent['tags'], ['log' => true]);
        
                    if (!DRY_RUN_REMOVAL) {
                        $qbtApi->addTagToTorrent(SATISFIED_TAG, $torrent['hash']);
                        $removalTagsAdded++;
                    }
                }
            }

            if (PAUSE_SATISFIED) {
                $output = (DRY_RUN_REMOVAL ? 'DRY_RUN_REMOVAL ' : '') . 'Pausing item "'. $torrent['name'] .'"';
                output('removal', $output, ['print' => true, 'log' => true]);

                if (!DRY_RUN_REMOVAL) {
                    $qbtApi->pause($torrent['hash']);
                    $removalTorrentsPaused++;
                }
            }
        }
    }
}

output('removal', 'Removal results:', ['print' => true, 'log' => true]);
output('removal', 'Skipped: ' . count($removeSkipped), ['print' => true, 'log' => true]);
output('removal', 'Tagged: ' . number_format($removalTagsAdded), ['print' => true, 'log' => true]);
output('removal', 'Paused: ' . number_format($removalTorrentsPaused), ['print' => true, 'log' => true]);
output('removal', 'Recycled: ' . number_format($removeTorrentsRecycled), ['print' => true, 'log' => true]);
output('removal', 'Deleted: ' . number_format($removeTorrentsDeleted), ['print' => true, 'log' => true]);
output('removal', 'Size: ' . byteConversion($removalSize), ['print' => true, 'log' => true]);
