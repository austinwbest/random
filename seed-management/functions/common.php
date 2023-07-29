<?php

/*
----------------------------------
 ------  Created: 092219   ------
 ------  Austin Best	   ------
----------------------------------
*/

function checkIgnoreRules($step, $torrent)
{
	global $ignoreTags, $ignoreCats;

	if ($torrent['state'] == STATE_DOWNLOADING) {
		output($step, 'Skipping ' . $torrent['name'] .', STATE_DOWNLOADING', ['log' => true]);
		return true;
	}

	if ($torrent['state'] == STATE_PAUSED && IGNORE_PAUSED) {
		output($step, 'Skipping ' . $torrent['name'] .', IGNORE_PAUSED', ['log' => true]);
		return true;
	}

	if (!empty($ignoreCats) && in_array($torrent['category'], $ignoreCats)) {
		output($step, 'Skipping ' . $torrent['name'] .', IGNORE_CATEGORIES (' . $torrent['category'] . ')', ['log' => true]);
		return true;
	}

	$torrentTags = explode(',', $torrent['tags']);
	if (!empty($ignoreTags) && !empty($torrentTags)) {
		foreach ($torrentTags as $torrentTag) {
			if (in_array(trim($torrentTag), $ignoreTags)) {
				output($step, 'Skipping ' . $torrent['name'] .', IGNORE_TAGS (' . $torrent['tags'] . ')', ['log' => true]);
				return true;
			}
		}
	}

	return false;
}

function output($step, $message, $options)
{
	$backtrace = debug_backtrace();
	$backtrace = array_shift($backtrace);

	$preMessage = '[' . $step  . '] ' .  date('g:i:s') . ' ' . str_replace(HOME_DIR, '', $backtrace['file']) . '::' . $backtrace['line'] . ' ';
	if ($options['print'] && PRINT_RUN) {
		if (is_array($message)) {
			echo $preMessage . '<br>';
			print_r($message);
			echo '<br>';
		} else {
			echo $preMessage . $message . '<br>';
		}
	}

	if ($options['log'] && LOG_FILE) {
		$logLine = $preMessage . $message . "\n";
		file_put_contents(LOG_FILE, $logLine, FILE_APPEND);
	}
}

function getDriveFreespace($drive)
{
	$CMD = 'wmic logicaldisk where "DeviceID=\'' . str_replace('\\', '', $drive) . '\'" get FreeSpace';
	exec($CMD, $result);
	array_shift($result);
	array_pop($result);
	
	return $result[0];
}

function size($path)
{
	$CMD = 'dir /a /s "'. $path .'"';
	exec($CMD, $result);
	
	foreach ($result as $resultLine) {
		if (trim($resultLine) == 'Total Files Listed:') {
			unset($info);
		}
		$info[] = trim($resultLine);
	}

	array_shift($info);
	array_pop($info);

	$size = explode(' ', $info[0]);
	$size = $size[count($size) - 2];
	$size = str_replace(',', '', $size);

	return (int) $size;
}

function byteConversion($bytes, $measurement = '', $dec = 2)
{
    if (!$bytes || $bytes <= 0) {
        return 0;
    }

    //-- SEND LARGEST ONE
    if (!$measurement) {
        $units  = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        $bytes  = max($bytes, 0);
        $pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow    = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $dec) . ' ' . $units[$pow];
    }

    switch ($measurement) {
        case 'KiB':
            return round($bytes / 1024, $dec);
        case 'MiB':
            return round($bytes / pow(1024, 2), $dec);
        case 'GiB':
            return round($bytes / pow(1024, 3), $dec);
        case 'TiB':
            return round($bytes / pow(1024, 4), $dec);
    }
}

function daysBetween($time)
{
	$original 	= new DateTime(date('c', $time));
	$now 		= new DateTime(date('c', time()));
	$days		= $original->diff($now);

	return $days->days;
}

function removeFile($path)
{	
	$CMD = 'unlink('. $path .')';

	output('removeFile()', 'Removing "'. $path .'"', ['log' => true]);
	output('removeFile()', $CMD, ['log' => true]);
	
	if (!unlink($path)) {
		$error = error_get_last();
		output('removeFile()', 'ERROR: '. $error['message'], ['log' => true]);
	} else {
		output('removeFile()', 'File removed', ['log' => true]);
	}
}

function removeDirectory($directory)
{	
	if (!is_dir($directory)) {
		removeFile($directory);
		return;
	}
	
	$CMD = 'rmdir "'. $directory .'" /s /q ';
	output('removeDirectory()', 'Removing "'. $directory .'"', ['log' => true]);
	output('removeDirectory()', $CMD, ['log' => true]);
	
	exec($CMD, $result);
	output('removeDirectory()', 'CMD Response: ' . implode("\r\n\t\t\t\t\t\t", $result), ['log' => true]);
}

function removeFolderPlusContents($dir) 
{
    foreach (glob($dir) as $file) {
        if (is_dir($file)) { 
            removeFolderPlusContents($file . '/*');
            rmdir($file);
        } else {
            unlink($file);
        }
    }
}

function seconds2relative($seconds) {
	$t = round($seconds);

	if ($seconds >= 3600) {
		return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
	} else {
		return sprintf('%02d:%02d', ($t / 60 % 60), $t % 60);
	}
}

function move($source, $destination)
{
	if (is_dir($source)) {
		move_recursive($source, $destination);
		$sourceSize = size($source);

		if ($sourceSize == 0) {
			removeFolderPlusContents($source);
			rmdir($source);
		}
	} else {
		rename($source, $destination);
	}

	$destinationSize = size($destination);
	if ($destinationSize > 0) {
		touch($destination);
		return true;
	}

	return false;
}

function move_recursive($source, $destination)
{
	if (!is_dir($destination)) {
		mkdir($destination);
	}

    $dir = opendir($source);
    while ($file = readdir($dir) ) { 
        if ($file[0] != '.') { 
            if (is_dir($source . '/' . $file)) { 
                move_recursive($source . '/' . $file, $destination . '/' . $file);
            }  else { 
                rename($source . '/' . $file, $destination . '/' . $file); 
            } 
        } 
    } 

    closedir($dir);
} 