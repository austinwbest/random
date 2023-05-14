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

$qbtCookie 	= qbt_login();
$qbtItems 	= qbt_queue($qbtCookie);

if ($qbtItems) {
	foreach ($qbtItems as $torrent) {
		$validSeeds[] = $torrent['content_path'];
	}
}

if (empty($seeds)) {
	exit('No seed directories specified in constants file');
}

$size = 0;
foreach ($seeds as $seedDir) {
	if (is_dir($seedDir)) {
		echo 'Checking ' . $seedDir . ' for orphaned data...<br>';

		$dir = opendir($seedDir);
		while ($item = readdir($dir)) {
			if (strpos($item, ORPHAN_PREFIX) !== false) {
				continue;
			}
				
			if ($item[0] == '.') {
				continue;
			}

			if (is_array($ignoreDir)) {
				if (in_array($item, $ignoreDir)) {
					continue;
				}
			}

			$found = false;
			foreach ($validSeeds as $seed) {
				if ($seed == $seedDir . $item) {
					$found = true;
					$seedContentPath = $seed;
					break;
				}
			}

			if (!$found) {
				if (is_dir($seedDir . $item)) {
					$itemDir = opendir($seedDir . $item);
					while ($nestedItem = readdir($itemDir)) {
						if ($nestedItem[0] != '.') {
							$size += find_filesize($seedDir . $item . '/' . $nestedItem);
						}
					}
					closedir($itemDir);
				} else {
					$size += find_filesize($seedDir . $item);
				}

				echo '&nbsp;&nbsp;&nbsp;' . $seedDir . $item . ' is not linked to anything in qbt,';

				if (DRY_RUN) {
					echo ' DRY_RUN is on so only reporting.';
				} else {
					echo ' renaming this item.';
					rename($seedDir . $item, $seedDir . ORPHAN_PREFIX . $item);
				}

				echo '<br>';
			}
		}
		closedir($dir);
	}
}

echo 'Orphaned data size: ' . byteConversion($size) . '<br><hr><br>';

foreach ($validSeeds as $seed) {
	if (!is_dir($seed) && !file_exists($seed)) {
		echo $seed . ' does not exist as a file or directory,';

		foreach ($qbtItems as $torrent) {
			if ($seed == $torrent['content_path']) {
				break;
			}
		}

		if (in_array($torrent['category'], $ignoreCat)) {
			echo ' category in ignore list.';
		} else {
			if (DRY_RUN) {
				echo ' DRY_RUN is on so only reporting.';
			} else {
				echo ' adding orphaned tag.';
				qbitAddTag($qbtCookie, $torrent['hash']);
			}
		}

		echo '<br>';
	}
}

function find_filesize($file)
{
    if (substr(PHP_OS, 0, 3) == 'WIN') {
        exec('for %I in ("' . $file . '") do @echo %~zI', $output);
        return $output[0];
    } else {
        return filesize($file);
    }
}

function byteConversion($bytes, $dec = 2)
{
    if (!$bytes || $bytes <= 0) {
        return 0;
    }

	$units  = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
	$bytes  = max($bytes, 0);
	$pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow    = min($pow, count($units) - 1);
	$bytes /= (1 << (10 * $pow));

	return round($bytes, $dec) . ' ' . $units[$pow];
}

