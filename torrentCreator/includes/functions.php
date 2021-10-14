<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

function saveTemplate()
{
	global $templatesData;
	
	foreach ($_POST as $key => $val)
	{
		if ($key != 'save_template')
		{
			logger('Saving template "'. $key .'" => "'. trim($val) .'"', __LINE__, __FILE__, 1);
			if (strpos($key, 'settings_') !== false)
			{
				$templatesData['settings'][str_replace('settings_', '', $key)] = $val;
			}
			else
			{
				$templatesData['templates'][$_POST['template_id']][trim($key)] = trim($val);
			}
		}
	}
	
	file_put_contents('_settings/templates.json', json_encode($templatesData));
	
	return $templatesData;
}

function saveSettings()
{
	global $settingsData;
	
	foreach ($_POST as $key => $val)
	{
		if ($key != 'save_settings')
		{
			logger('Saving setting "'. $key .'" => "'. trim($val) .'"', __LINE__, __FILE__, 1);
			$settingsData[$key] = trim($val);
		}
	}
	
	file_put_contents('_settings/settings.json', json_encode($settingsData));
	
	return $settingsData;
}

function buildNfoFromTemplate($tracker, $info)
{
	global $templatesData;
	
	$templateData = $templatesData['templates'][$tracker];
	
	if (!$templateData)
	{
		return 'No template setup for '. $tracker;
	}

	$metaData = json_decode($info['metaData'], true);
	$mediaInfo = json_decode($info['mediaInfo'], true);

	$_POST['process_imdb'] = ($metaData['imdb']) ? $metaData['imdb'] : $_POST['process_imdb'];

	$templateBuilder = str_replace('+', '', rawurldecode($templateData['template_body']));
	$templateBuilder = str_replace('{TITLE}', $metaData['title'], $templateBuilder);
	$templateBuilder = str_replace('{IMDB}', (($_POST['process_imdb']) ? '[url=https://www.imdb.com/title/'. $_POST['process_imdb'] .']IMDb[/url]' : ''), $templateBuilder);
	$templateBuilder = str_replace('{TMDB}', (($_POST['process_tmdb']) ? '[url=https://www.themoviedb.org/movie/'. $_POST['process_tmdb'] .']TMDb[/url]' : ''), $templateBuilder);
	$templateBuilder = str_replace('{TVDB}', (($_POST['process_tvdb']) ? '[url=http://www.thetvdb.com/?tab=series&id='. $_POST['process_tvdb'] .']TVDb[/url]' : ''), $templateBuilder);
	$templateBuilder = str_replace('{TORRENT}', $info['torrentName'], $templateBuilder);
	$templateBuilder = str_replace('{MEDIAINFO_SMALL}', $mediaInfo['short'], $templateBuilder);
	$templateBuilder = str_replace('{MEDIAINFO_FULL}', $mediaInfo['full'], $templateBuilder);
	$templateBuilder = str_replace('{DESCRIPTION}', $metaData['overview'], $templateBuilder);

	if ($templateData['template_bbcode_img_open'] && $templateData['template_bbcode_img_closed'])
	{
		if ($info['screenshots'])
		{
			foreach ($info['screenshots'] as $index => $screenshot)
			{
				$extension = end(explode('.', $screenshot));
				$screenshots .= '[URL='. str_replace('m.'. $extension, '.'. $extension, $screenshot) .']'. $templateData['template_bbcode_img_open'] . $screenshot . $templateData['template_bbcode_img_closed'] .'[/URL]';
			}
		}
		
		$templateBuilder = str_replace('{POSTER}', (($metaData['poster']) ? $templateData['template_bbcode_img_open'] . $metaData['poster'] . $templateData['template_bbcode_img_closed'] : ''), $templateBuilder);
		$templateBuilder = str_replace('{IMAGES}', (($screenshots) ? $screenshots : ''), $templateBuilder);
	}
	else
	{
		$templateBuilder = str_replace('{POSTER}', '', $templateBuilder);
		$templateBuilder = str_replace('{IMAGES}', '', $templateBuilder);
	}

	return $templateBuilder;
}

function utf8_sanitize($string)
{
	$string = preg_replace('/[^(\x20-\x7F)]*/','', $string);
	return $string;
}

function getFolderSize($path)
{
	$CMD = 'dir /a /s "'. $path .'"';
	exec($CMD, $result);
	
	foreach ($result as $index => $resultLine)
	{
		if (trim($resultLine) == 'Total Files Listed:')
		{
			unset($info);
		}
		$info[] = trim($resultLine);
	}
	array_shift($info);
	array_pop($info);
	
	$size = explode(' ', $info[0]);
	$size = $size[2];
	$size = str_replace(',', '', $size);
	
	return $size;
}

function byteConversion($bytes, $measurement, $dec = 2) 
{
	switch ($measurement)
	{
		case 'KB':
			return round($bytes / 1024, $dec); 
		break;
		case 'MB':
			return round($bytes / pow(1024, 2), $dec); 
		break;	
		case 'GB':
			return round($bytes  / pow(1024, 3), $dec); 
		break;
		case 'TB':
			return round($bytes  / pow(1024, 4), $dec); 
		break;	
	}
}

function getDirectoryTree($path) 
{
	if (!is_dir($path))
	{
		return array($path);
	}

	$tree = array();
	$tree[] = $path;
	
	foreach(scandir($path) as $file) 
	{
		if ($file == '.' || $file == '..') 
		{
			continue;
		}
			
		$dir = $path.DIRECTORY_SEPARATOR.$file;
		if (is_dir($dir)) 
		{
			$tree[] = $dir;
			$tree = array_merge($tree, getDirectoryTree($dir));
		}
	}
	
	return $tree;
}

function getDataDriveFiles($type)
{
	global $allowed, $settingsData;

	switch ($type)
	{
		case 'movies':
			$locations = explode("\n", $settingsData['movie_location']);

			if (!$locations)
			{
				logger('No $locations found', __LINE__, __FILE__, 1);
				return;
			}
			
			$showCount = $seasonCount = $episodeCount = 0;
			foreach ($locations as $index => $path)
			{
				$options .= '<optgroup label="'. $path .'">';
				
				$path = trim($path);
				
				if (!$path)
				{
					continue;
				}

				logger('$path="'. $path .'"', __LINE__, __FILE__, 1);
				
				$movieFolder = opendir($path);
				while ($movie = readdir($movieFolder))
				{
					if ($movie != '.' && $movie != '..' && is_dir($path .'/'. $movie))
					{
						logger('movie Found="'. $path .'/'. $movie .'/'. $file .'"', __LINE__, __FILE__, 2);
							
						$movies++;
						$options .= '<option value="'. $path .'/'. $movie .'/'. $file .'">'. $movie .'</option>';
					}
				}
				closedir($movieFolder);
				
				$options .= '</optgroup>';
			}
			
			return array('total' => $movies, 'options' => $options);
		break;
		
		case 'series':
			$locations = explode("\n", $settingsData['series_location']);

			if (!$locations)
			{
				logger('No $locations found', __LINE__, __FILE__, 1);
				return;
			}

			$showCount = $seasonCount = $episodeCount = 0;
			
			foreach ($locations as $index => $path)
			{
				$path = trim($path);

				if (!$path)
				{
					continue;
				}

				$shows = opendir($path);
				if ($path && $shows)
				{
					while ($show = readdir($shows))
					{
						if ($show != '.' && $show != '..' && is_dir($path .'/'. $show))
						{
							logger('$path="'. $path .'/'. $show .'"', __LINE__, __FILE__, 1);
							
							$showCount++;
							$options .= '<optgroup label="'. $path .'/'. $show .'/">';
							
							$seasons = opendir($path .'/'. $show);
							while ($season = readdir($seasons))
							{
								if ($season != '.' && $season != '..' && is_dir($path .'/'. $show .'/'. $season))
								{
									logger('season found="'. $path .'/'. $show .'/'. $season .'"', __LINE__, __FILE__, 2);
									
									$seasonCount++;
									$options .= '<option value="'. $path .'/'. $show .'/'. $season .'">'. $season .'</option>';
								}
							}
							closedir($seasons);
							
							$options .= '</optgroup>';
						}
					}
					closedir($shows);
				}
			}

			return array('options' => $options, 'shows' => $showCount, 'seasons' => $seasonCount);
		break;
	}
}

function getSeasonFromTitle($title)
{
	preg_match("/(Season) ([0-9]{1,2})/", $title, $matches);
	return $matches[2];
}

function getSourceFromTitle($title)
{
	if (strripos($title, 'webdl') !== false)
	{
		return 'WEBDL';
	}
	if (strripos($title, 'webrip') !== false)
	{
		return 'WEBRip';
	}
	if (strripos($title, 'dvd') !== false)
	{
		return 'DVD';
	}
	if (strripos($title, 'bluray') !== false)
	{
		return 'Bluray';
	}
	if (strripos($title, 'hdtv') !== false)
	{
		return 'HDTV';
	}
	if (strripos($title, 'sdtv') !== false)
	{
		return 'SDTV';
	}
}

function getDirectorySize($path)
{	
	$tree = getDirectoryTree($path);
	$bytesTotal = 0;

	if (!$tree)
	{
		return $bytesTotal;
	}
	
	foreach ($tree as $dir)
	{
		$openDir = opendir($dir);
		while ($file = readdir($openDir))
		{
			if ($file != '.' && $file != '..')
			{
				$bytesTotal += filesize64($dir .'/'. $file);
			}
		}
		closedir($openDir);
	}

    return ($bytesTotal > 0) ? $bytesTotal : ($bytesTotal * -1);
}

function filesize64($file)
{
    static $iswin;
    if (!isset($iswin)) {
        $iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
    }

    static $exec_works;
    if (!isset($exec_works)) {
        $exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC');
    }
    if ($exec_works) {
        $cmd = ($iswin) ? "for %F in (\"$file\") do @echo %~zF" : "stat -c%s \"$file\"";
        @exec($cmd, $output);
        if (is_array($output) && ctype_digit($size = trim(implode("\n", $output)))) {
            return $size;
        }
    }

    if ($iswin && class_exists("COM")) {
        try {
            $fsobj = new COM('Scripting.FileSystemObject');
            $f = $fsobj->GetFile( realpath($file) );
            $size = $f->Size;
        } catch (Exception $e) {
            $size = null;
        }
        if (ctype_digit($size)) {
            return $size;
        }
    }

    return filesize($file);
}

function logger($msg, $loggedLine, $loggedFile, $tabCount = 0)
{
	global $settingsData;
	
	if (!$settingsData['script_logging'])
	{
		return;
	}

	for ($x = 0; $x < intval($tabCount); $x++)
	{
		$tabs .= "\t";
	}

	file_put_contents(ABSOLUTE_PATH .'/_logs/'. LOGFILE, '['. date('m/d/Y h:i:s A') .'] '. end(explode('\\', $loggedFile)) .':'. $loggedLine .' - '. $tabs . $msg ."\n", FILE_APPEND | LOCK_EX);
}
?>