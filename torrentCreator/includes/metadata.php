<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getMetaFromCache($id, $source)
{
	global $settingsData;

	if (!$id || !$source || !$settingsData['api_discordNotifier'])
	{
		logger('getMetaFromCache() skipped', __LINE__, __FILE__, 3);
		return;
	}

	$url = 'https://discordnotifier.com/api.php?source='. $source .'&id='. $id .'&request='. $source .'&api='. $settingsData['api_discordNotifier'];

	logger('notifier url='. $url, __LINE__, __FILE__, 3);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);

	$result = json_decode($result, true);

	logger('$result[]', __LINE__, __FILE__, 2);
	foreach ($result as $logKey => $logVal)
	{
		if (is_array($logVal))
		{
			logger('['. $logKey .'] => ', __LINE__, __FILE__, 3);
			foreach ($logVal as $logKey2 => $logVal2)
			{
				if (is_array($logVal2))
				{
					logger('['. $logKey2 .'] => ', __LINE__, __FILE__, 4);
					foreach ($logVal2 as $logKey3 => $logVal3)
					{
						if (is_array($logVal3))
						{
							logger('['. $logKey3 .'] => ', __LINE__, __FILE__, 5);
							foreach ($logVal3 as $logKey4 => $logVal4)
							{
								logger('['. $logKey4 .'] => '. $logVal4, __LINE__, __FILE__, 6);
							}
						}
						else
						{
							logger('['. $logKey3 .'] => '. $logVal3, __LINE__, __FILE__, 5);
						}
					}
				}
				else
				{
					logger('['. $logKey2 .'] => '. $logVal2, __LINE__, __FILE__, 4);
				}
			}
		}
		else
		{
			logger('['. $logKey .'] => '. $logVal, __LINE__, __FILE__, 3);
		}
	}

	return $result;
}

function getTVDbDetails($tvdb)
{
	global $settingsData;

	if (!$tvdb)
	{
		logger('getTVDbDetails() skipped', __LINE__, __FILE__, 3);
		return;
	}

	$cacheMeta = getMetaFromCache($tvdb, 'tvdb');
	$mediaArray = $cacheMeta['TVDB'];
	
	if (!$mediaArray)
	{
		logger('getMetaFromCache() failed with tvdb="'. $tvdb .'"', __LINE__, __FILE__, 3);
		return;
	}

	$seriesData = (array) $mediaArray['tv_season_results'];
	
	$series['title'] 	= $seriesData['SeriesName'];
	$series['poster'] 	= $mediaArray['poster_path'];
	$series['overview'] = $seriesData['Overview'];
	$series['imdb'] 	= $seriesData['IMDB_ID'];

	return $series;
}

function getTMDbDetails($tmdb)
{
	global $settingsData;

	if (!$tmdb)
	{
		logger('getTMDbDetails() skipped', __LINE__, __FILE__, 3);
		return;
	}
	
	$cacheMeta = getMetaFromCache($tmdb, 'tmdb');
	$mediaArray = $cacheMeta['TMDB'];
	
	if (!$mediaArray)
	{
		logger('getMetaFromCache() failed with tmdb="'. $tmdb .'"', __LINE__, __FILE__, 3);
		return;
	}

	$movie['title'] 	= $mediaArray['title'];
	$movie['poster'] 	= 'http://image.tmdb.org/t/p/w185'. $mediaArray['poster_path'];
	$movie['overview'] 	= $mediaArray['overview'];
	$movie['imdb'] 		= $mediaArray['imdb_id'];

	return $movie;
}
?>
