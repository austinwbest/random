<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getMediaInfo($path, $type)
{
	global $settingsData;
	
	if (!$path)
	{
		logger('getMediaInfo() skipped', __LINE__, __FILE__, 3);
		return;
	}
	
	$CMD = '"'. $settingsData['mediainfo_location'] .'" "'. $path .'"';
	exec($CMD, $results);

	logger('mediainfo CMD='. $CMD, __LINE__, __FILE__, 3);
	foreach ($results as $key => $val)
	{
		logger('['. $key .'] => '. $val, __LINE__, __FILE__, 4);
	}

	$mediaInfoShort = cleanupMediainfo($results, 'string');
	$mediaInfoArray = cleanupMediainfo($results, 'array');
	$mediaInfoFull 	= cleanupMediainfo($results, 'full');

	if ($mediaInfoArray['Audio'])
	{
		$useAudio = $mediaInfoArray['Audio'];
	}
	else if ($mediaInfoArray['Audio #1'])
	{
		$useAudio = $mediaInfoArray['Audio #1'];
	}

	if ($useAudio)
	{
		foreach ($useAudio as $index => $value)
		{
			if (strpos($value, 'Format') !== false)
			{
				$audio = trim(end(explode(':', $value)));
				break;
			}
		}
		
		if (!$audio)
		{
			foreach ($useAudio as $index => $value)
			{
				if (strpos($value, 'Codec') !== false)
				{
					$audio = trim(end(explode(':', $value)));
					break;
				}
			}
		}
		
		$audio = trim(reset(explode(' ', $audio)));
	}

	if ($mediaInfoArray['Video'])
	{
		foreach ($mediaInfoArray['Video'] as $index => $value)
		{
			if (strpos($value, 'Height') !== false)
			{
				$resolution = trim(end(explode(':', $value)));
				$resolution = str_replace('pixels', '', $resolution);
				$resolution = str_replace(' ', '', $resolution);
				break;
			}
		}

		foreach ($mediaInfoArray['Video'] as $index => $value)
		{
			if (strpos($value, 'Codec') !== false && strpos($value, '/') === false)
			{
				$video = trim(end(explode(':', $value))) .' H264';
				break;
			}	
			elseif (strpos($value, 'Format') !== false)
			{
				if (strpos($value, 'HEVC') !== false)
				{
					$video = 'HEVC x265';
					break;
				}	
			}						
			elseif (strpos($value, 'Writing library') !== false)
			{
				if (strpos($value, 'x264') !== false)
				{
					$video = 'x264';
					break;
				}
				if (strpos($value, 'x265') !== false)
				{
					$video = 'x265';
					break;
				}
			}							
		}
	}
	
	if (!$video)
	{
		$video = 'H264';
	}
	
	return array('audio' => $audio, 'video' => $video, 'resolution' => $resolution, 'short' => $mediaInfoShort, 'full' => $mediaInfoFull);	
}

function cleanupMediainfo($mediainfo, $return = 'string')
{
	if ($return == 'full')
	{
		foreach ($mediainfo as $index => $info)
		{
			$allMediainfo .= $info ."\n";
		}
		
		return $allMediainfo;
	}
	
	$wanted = array('General' 	=> array('format', 'file size', 'duration', 'overall bit rate', 'writing application'),
					'Video'		=> array('format', 'codec id', 'bit rate', 'maximum bit rate', 'width', 'height', 'display aspect ratio', 'frame rate', 'writing library'),
					'Audio'		=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode'),
					'Audio #1'	=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode'),
					'Audio #2'	=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode'),
					'Audio #3'	=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode'),
					'Audio #4'	=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode'),
					'Audio #5'	=> array('format', 'codec id', 'bit rate', 'frame rate', 'compression mode')
					);
	
	foreach ($wanted as $wantedSection => $wantedOptions)
	{
		unset($section);
		if ($mediainfo)
		{
			foreach ($mediainfo as $index => $info)
			{
				if (@trim($info) == @trim($wantedSection) && !$section)
				{
					$section = $info;
				}

				if ($section)
				{
					$thisLine = strtolower(trim(reset(explode(':', $info))));

					if (in_array($thisLine, $wantedOptions))
					{
						$data[$section][] = $info;
					}
				}
				
				if (!$info) //-- SECTION BREAK
				{
					unset($section);
				}
			}
		}
	}
	
	if ($data)
	{
		foreach ($data as $section => $details)
		{
			$string .= "\n". $section ."\n----------\n";
			
			foreach ($details as $index => $sectionData)
			{
				$string .= $sectionData ."\n";
				$array[$section][] = $sectionData;
			}
		}
	}
	
	if ($return == 'string')
	{
		return $string;
	}
	if ($return == 'full')
	{
		return $data;
	}

	return $array;
}

function normalizeResolution($res)
{
	if ($res <= 400)
	{
		return 360;
	}
	if ($res <= 600)
	{
		return 480;
	}
	if ($res <= 800)
	{
		return 720;
	}
	if ($res <= 1200)
	{
		return 1080;
	}
	if ($res >= 2100)
	{
		return 2160;
	}	
}

?>