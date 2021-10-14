<?PHP
/*
----------------------------------
 ------  Created: 071021   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';

if ($_POST['tmdbId'] > 0)
{
	$tmdbId = intval($_POST['tmdbId']);
	$tmdb		= getMeta('tmdb', $tmdbId);
	$servarr	= getMeta('servarr', $tmdbId);
	$notifiarr	= getMeta('notifiarr-movie', $tmdbId);

	if (!$tmdb['id'])
	{
		$tmdbData['error'] = 'Nothing found for tmdb id "'. $tmdbId .'" (Empty API response.)';
	}			
	if (!$servarr['TmdbId'])
	{
		$servarrData['error'] = 'Nothing found for tmdb id "'. $tmdbId .'" (Empty API response.)';
	}
	if (!$notifiarr['id'])
	{
		$notifiarrData['error'] = 'Nothing found for tmdb id "'. $tmdbId .'" (Empty API response.)';
	}

	if (!$tmdbData['error'])
	{
		$tmdbData['IMDB'] 				= $tmdb['imdb_id'];
		$tmdbData['title'] 				= $tmdb['title'];
		$tmdbData['original_title'] 	= $tmdb['original_title'];
		$tmdbData['original_language'] 	= $tmdb['original_language'];
		$tmdbData['overview'] 			= $tmdb['overview'];
		$tmdbData['poster_path'] 		= end(explode('/', $tmdb['poster_path']));
		$tmdbData['backdrop_path'] 		= end(explode('/', $tmdb['backdrop_path']));
		$tmdbData['release_date'] 		= $tmdb['release_date'];
		$tmdbData['runtime'] 			= $tmdb['runtime'];
		$tmdbData['status'] 			= $tmdb['status'];

		foreach ($tmdb['genres'] as $genre)
		{
			$tmdbData['genres'] .= (($tmdbData['genres']) ? ', ' : '') . $genre['name'];
		}
	}

	if (!$notifiarrData['error'])
	{
		$notifiarrData['IMDB'] 				= $notifiarr['imdb_id'];
		$notifiarrData['title'] 			= $notifiarr['title'];
		$notifiarrData['original_title'] 	= $notifiarr['original_title'];
		$notifiarrData['original_language']	= $notifiarr['original_language'];
		$notifiarrData['overview'] 			= $notifiarr['overview'];
		$notifiarrData['poster_path'] 		= end(explode('/', $notifiarr['poster_path']));
		$notifiarrData['backdrop_path'] 	= end(explode('/', $notifiarr['backdrop_path']));
		$notifiarrData['release_date'] 		= $notifiarr['release_date'];
		$notifiarrData['runtime'] 			= $notifiarr['runtime'];
		$notifiarrData['status'] 			= $notifiarr['status'];

		foreach ($notifiarr['genres'] as $genre)
		{
			$notifiarrData['genres'] .= (($notifiarrData['genres']) ? ', ' : '') . $genre['name'];
		}
	}

	if (!$servarrData['error'])
	{
		$servarrData['IMDB'] 				= $servarr['ImdbId'];
		$servarrData['title'] 				= $servarr['Title'];
		$servarrData['original_title'] 		= $servarr['OriginalTitle'];
		$servarrData['original_language']	= $servarr['OriginalLanguage'];
		$servarrData['overview'] 			= $servarr['Overview'];

		foreach ($servarr['Images'] as $image)
		{
			if ($image['CoverType'] == 'Poster')
			{
				$servarrData['poster_path'] = end(explode('/', $image['Url']));
			}
			if ($image['CoverType'] == 'Fanart')
			{
				$servarrData['backdrop_path'] = end(explode('/', $image['Url']));
			}
		}

		$servarrData['release_date'] 		= reset(explode('T', $servarr['Premier']));
		$servarrData['runtime'] 			= $servarr['Runtime'];
		$servarrData['status'] 				= $servarr['Status'] ? $servarr['Status'] : 'null';
		$servarrData['genres'] 				= implode(', ', $servarr['Genres']);
	}

	if (!$tmdbData['error'])
	{
		foreach ($tmdbData as $field => $value)
		{
			$fieldDisplay = ucwords(str_replace('_', ' ', $field));

			if (!is_array($value))
			{
				$notifiarrMatch	= (@trim($tmdbData[$field]) == @trim($notifiarrData[$field])) ? true : false;
				$servarrMatch	= (@trim($tmdbData[$field]) == @trim($servarrData[$field])) ? true : false;

				$results .= '<tr>';
				$results .= '	<td>'. $fieldDisplay .': '. $value .'</td>';
				$results .= '	<td>'. (!$notifiarrMatch ? $notifiarrData[$field] : '') .'</td>';
				$results .= '	<td align="center">'. ($notifiarrMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '	<td>'. (!$servarrMatch ? $servarrData[$field] : '') .'</td>';
				$results .= '	<td align="center">'. ($servarrMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '</tr>';
			}
		}
	}
}

if ($_POST['tvdbId'] > 0)
{
	$tvdbId 				= intval($_POST['tvdbId']);	
	$tvdb 					= getMeta('tvdb', $tvdbId);
	$tvdbSeriesData 		= (array) $tvdb['tv_season_results'];
	$tvdbEpisodeData 		= (array) $tvdb['tv_episode_results'];
	$skyhook 				= getMeta('skyhook', $tvdbId);
	$notifiarr				= getMeta('notifiarr-series', $tvdbId);
	$notifiarrSeriesData 	= (array) $notifiarr['tv_season_results'];
	$notifiarrEpisodeData 	= (array) $notifiarr['tv_episode_results'];

	if (!$tvdbSeriesData['id'])
	{
		$tvdbData['error'] = 'Nothing found for tvdb id "'. $tvdbId .'" (Empty API response.)';
	}			
	if ($skyhook['message'] == 'NotFound')
	{
		$skyhookData['error'] = 'Nothing found for tvdb id "'. $tvdbId .'" (Empty API response.)';
	}
	if (!$notifiarr['tv_season_results']['id'])
	{
		$notifiarrData['error'] = 'Nothing found for tvdb id "'. $tvdbId .'" (Empty API response.)';
	}
	
	if (!$tvdbData['error'])
	{	
		$tvdbData['IMDB'] 	= $tvdbSeriesData['IMDB_ID'];
		$tvdbData['Series'] = $tvdbSeriesData['SeriesName'];

		foreach ($tvdbEpisodeData as $index => $episode)
		{
			$episode = (array) $episode;
			$season[$episode['SeasonNumber']][] = $episode;
		}
		
		$tvdbData['Seasons'] 				= count($season);
		$tvdbData['Episodes'] 				= count($tvdb['tv_episode_results']);
		$tvdbData['Network'] 				= $tvdbSeriesData['Network'];
		$tvdbData['Runtime'] 				= $tvdbSeriesData['Runtime'];
		$tvdbData['Status'] 				= $tvdbSeriesData['Status'];
		$tvdbData['Added'] 					= $tvdbSeriesData['added'];
		$tvdbData['Series_Level_Update'] 	= gmdate('Y-m-d H:i:s', $tvdbSeriesData['lastupdated']);
		$tvdbData['First_Aired'] 			= $tvdbSeriesData['FirstAired'];
		$tvdbData['Content_Rating'] 		= $tvdbSeriesData['ContentRating'];
		$tvdbData['Overview'] 				= $tvdbSeriesData['Overview'];
		$tvdbData['Banner'] 				= ($tvdbSeriesData['banner']) ? 'https://artworks.thetvdb.com/banners/'. $tvdbSeriesData['banner'] : '';
		$tvdbData['Fanart'] 				= ($tvdbSeriesData['fanart']) ? 'https://artworks.thetvdb.com/banners/'. $tvdbSeriesData['fanart'] : '';
		$tvdbData['Poster'] 				= ($tvdbSeriesData['poster']) ? 'https://artworks.thetvdb.com/banners/'. $tvdbSeriesData['poster'] : '';

		foreach ($season as $seasonNumber => $episodes)
		{
			foreach ($episodes as $index => $episode)
			{
				$format = 'S'. str_pad($episode['SeasonNumber'], 2, 0, STR_PAD_LEFT) .'E'.str_pad($episode['EpisodeNumber'], 2, 0, STR_PAD_LEFT);

				$tvdbSeasonData[$format]['name'] 	= $episode['EpisodeName'];
				$tvdbSeasonData[$format]['aired'] 	= $episode['FirstAired'];
				$tvdbSeasonData[$format]['updated']	= gmdate('Y-m-d H:i:s', $episode['lastupdated']);
				$tvdbSeasonData[$format]['hash'] 	= md5(@trim($episode['EpisodeName']).@trim($episode['FirstAired']));
			}
		}

		ksort($tvdbSeasonData, SORT_NATURAL);
		$tvdbData = array_merge($tvdbData, $tvdbSeasonData);
	}

	if (!$notifiarrData['error'])
	{
		unset($season);

		$notifiarrData['IMDB'] 		= $notifiarrSeriesData['IMDB_ID'];
		$notifiarrData['Series'] 	= $notifiarrSeriesData['SeriesName'];

		foreach ($notifiarrEpisodeData as $index => $episode)
		{
			$episode = (array) $episode;
			$season[$episode['SeasonNumber']][] = $episode;
		}
		
		$notifiarrData['Seasons'] 				= count($season);
		$notifiarrData['Episodes'] 				= count($tvdb['tv_episode_results']);
		$notifiarrData['Network'] 				= $notifiarrSeriesData['Network'];
		$notifiarrData['Runtime'] 				= $notifiarrSeriesData['Runtime'];
		$notifiarrData['Status'] 				= $notifiarrSeriesData['Status'];
		$notifiarrData['Added'] 				= $notifiarrSeriesData['added'];
		$notifiarrData['Series_Level_Update'] 	= gmdate('Y-m-d H:i:s', $notifiarrSeriesData['lastupdated']);
		$notifiarrData['First_Aired'] 			= $notifiarrSeriesData['FirstAired'];
		$notifiarrData['Content_Rating'] 		= $notifiarrSeriesData['ContentRating'];
		$notifiarrData['Overview'] 				= $notifiarrSeriesData['Overview'];
		$notifiarrData['Banner'] 				= ($notifiarrSeriesData['banner']) ? 'https://artworks.thetvdb.com/banners/'. $notifiarrSeriesData['banner'] : '';
		$notifiarrData['Fanart'] 				= ($notifiarrSeriesData['fanart']) ? 'https://artworks.thetvdb.com/banners/'. $notifiarrSeriesData['fanart'] : '';
		$notifiarrData['Poster'] 				= ($notifiarrSeriesData['poster']) ? 'https://artworks.thetvdb.com/banners/'. $notifiarrSeriesData['poster'] : '';

		foreach ($season as $seasonNumber => $episodes)
		{
			foreach ($episodes as $index => $episode)
			{
				$format = 'S'. str_pad($episode['SeasonNumber'], 2, 0, STR_PAD_LEFT) .'E'.str_pad($episode['EpisodeNumber'], 2, 0, STR_PAD_LEFT);

				$notifiarrSeasonData[$format]['name']	= $episode['EpisodeName'];
				$notifiarrSeasonData[$format]['aired']	= $episode['FirstAired'];
				$notifiarrSeasonData[$format]['hash']	= md5(@trim($episode['EpisodeName']).@trim($episode['FirstAired']));
			}
		}

		ksort($notifiarrSeasonData, SORT_NATURAL);
		$notifiarrData = array_merge($notifiarrData, $notifiarrSeasonData);
	}

	if (!$skyhookData['error'])
	{
		unset($season);
		
		$skyhookData['IMDB'] 				= $skyhook['imdbId'];
		$skyhookData['Series'] 				= $skyhook['title'];
		$skyhookData['Seasons'] 			= count($skyhook['seasons']);
		$skyhookData['Episodes'] 			= count($skyhook['episodes']);
		$skyhookData['Network'] 			= $skyhook['network'];
		$skyhookData['Runtime'] 			= $skyhook['runtime'];
		$skyhookData['Status'] 				= $skyhook['status'];
		$skyhookData['Added'] 				= gmdate('Y-m-d H:i:s', strtotime($skyhook['added']));
		$skyhookData['Series_Level_Update'] = gmdate('Y-m-d H:i:s', strtotime($skyhook['lastUpdated']));
		$skyhookData['First_Aired'] 		= $skyhook['firstAired'];
		$skyhookData['Content_Rating'] 		= $skyhook['contentRating'];
		$skyhookData['Overview'] 			= $skyhook['overview'];
		
		if ($skyhook['images'])
		{
			foreach ($skyhook['images'] as $index => $image)
			{
				if ($image['coverType'] == 'Banner')
				{
					$skyhookData['Banner'] = $image['url'];
				}
				if ($image['coverType'] == 'Poster')
				{
					$skyhookData['Poster'] = $image['url'];
				}
				if ($image['coverType'] == 'Fanart')
				{
					$skyhookData['Fanart'] = $image['url'];
				}
			}
		}
		$skyhookData['Episode_Details'] = '';
		
		foreach ($skyhook['episodes'] as $index => $episode)
		{
			$season[$episode['seasonNumber']][] = $episode;
		}

		foreach ($season as $seasonNumber => $episodes)
		{
			foreach ($episodes as $index => $episode)
			{
				$format = 'S'. str_pad($episode['seasonNumber'], 2, 0, STR_PAD_LEFT) .'E'.str_pad($episode['episodeNumber'], 2, 0, STR_PAD_LEFT);

				$skyhookSeasonData[$format]['name'] 	= $episode['title'];
				$skyhookSeasonData[$format]['aired']	= $episode['airDate'];
				$skyhookSeasonData[$format]['hash'] 	= md5(@trim($episode['title']).@trim($episode['airDate']));
			}
		}

		ksort($skyhookSeasonData, SORT_NATURAL);
		$skyhookData = array_merge($skyhookData, $skyhookSeasonData);
	}

	if (!$tvdbData['error'])
	{
		foreach ($tvdbData as $field => $value)
		{
			$fieldDisplay = str_replace('_', ' ', $field);

			if (!is_array($value))
			{
				$notifiarrMatch	= (@trim($tvdbData[$field]) == @trim($notifiarrData[$field])) ? true : false;
				$skyhookMatch	= (@trim($tvdbData[$field]) == @trim($skyhookData[$field])) ? true : false;

				$results .= '<tr>';
				$results .= '	<td>'. $fieldDisplay .': '. $value .'</td>';
				$results .= '	<td>'. (!$notifiarrMatch ? $notifiarrData[$field] : '') .'</td>';
				$results .= '	<td align="center">'. ($notifiarrMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '	<td>'. (!$skyhookMatch ? $skyhookData[$field] : '') .'</td>';
				$results .= '	<td align="center">'. ($skyhookMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '</tr>';
			}
			else //-- EPISODE LIST
			{
				$fieldLine = 'Episode: '. $field .'<br>';
				$fieldLine .= 'Name: '. $value['name'] .'<br>';
				$fieldLine .= 'Aired: '. $value['aired'] .'<br>';
				$fieldLine .= 'Updated: '. $value['updated'];

				$notifiarrMatch = $notifiarrData[$field]['hash'] == $value['hash'] ? true : false;
				$skyhookMatch 	= $skyhookData[$field]['hash'] == $value['hash'] ? true : false;

				if (!$notifiarrMatch)
				{
					$notifiarrFieldLine = $field .'<br>';
					$notifiarrFieldLine .= $notifiarrData[$field]['name'] .'<br>';
					$notifiarrFieldLine .= $notifiarrData[$field]['aired'];
				}

				if (!$skyhookMatch)
				{
					$skyhookFieldLine = $field .'<br>';
					$skyhookFieldLine .= $skyhookData[$field]['name'] .'<br>';
					$skyhookFieldLine .= $skyhookData[$field]['aired'];
				}

				$results .= '<tr>';
				$results .= '	<td>'. $fieldLine .'</td>';
				$results .= '	<td>'. (!$notifiarrMatch ? $notifiarrFieldLine : '') .'</td>';
				$results .= '	<td align="center">'. ($notifiarrMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '	<td>'. (!$skyhookMatch ? $skyhookFieldLine : '') .'</td>';
				$results .= '	<td align="center">'. ($skyhookMatch ? '<i style="color:green;" class="fa fa-check"></i>' : '<i style="color:red;" class="fa fa-exclamation-circle"></i>') .'</td>';
				$results .= '</tr>';
			}
		}
	}
}
?>

<!doctype html>
<html lang="en" class="no-js">
	<head>	
		<title>Cache Checker</title>
		<?PHP require 'includes/shared.php' ?>
	</head>
	<body>
		<div class="content-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-md-10 col-md-offset-1">
						<div class="row">
							<div class="col-md-12">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h4>Cache Checker</h4>
										<div align="center" class="form-group">
											<form method="post" class="form-horizontal" enctype="multipart/form-data">
												<input type="text" placeholder="TVDb ID" name="tvdbId"> <button><i style="color:green;" class="fa fa-check"></i></button>
												<input type="text" placeholder="TMDb ID" name="tmdbId"> <button><i style="color:green;" class="fa fa-check"></i></button>
											</form>
										</div>
									</div>
									<?PHP if ($tvdbId || $tmdbId) { ?>
									<div class="panel-body">
										<div class="table-responsive">
										<table style="overflow-x: scroll;" class="table table-striped table-bordered">
											<?PHP if ($tvdbId) { ?>
											<tr>
												<td align="center" style="font-weight:bold;" width="32%">TVDb (Source)</td>
												<td align="center" style="font-weight:bold;" width="32%">Notifiarr</td>
												<td align="center" style="font-weight:bold;" width="2%">&nbsp;</td>
												<td align="center" style="font-weight:bold;" width="32%">Skyhook (Sonarr)</td>
												<td align="center" style="font-weight:bold;" width="2%">&nbsp;</td>
											</tr>
											<tr>
												<td valign="top">Endpoint: /api/{apikey}/series/<?= $tvdbId ?>/all/en.json</td>
												<td valign="top" colspan="2">/api/v1/series/meta/{apikey}/?sources=tvdb&id=<?= $tvdbId ?></td>
												<td valign="top" colspan="2">/v1/tvdb/shows/en/<?= $tvdbId ?></td>
											</tr>
											<?= $results ?>
											<?PHP } ?>
											<?PHP if ($tmdbId) { ?>
											<tr>
												<td align="center" style="font-weight:bold;" width="32%">TMDb (Source)</td>
												<td align="center" style="font-weight:bold;" width="32%">Notifiarr</td>
												<td align="center" style="font-weight:bold;" width="2%">&nbsp;</td>
												<td align="center" style="font-weight:bold;" width="32%">Servarr (Radarr)</td>
												<td align="center" style="font-weight:bold;" width="2%">&nbsp;</td>
											</tr>
											<tr>
												<td valign="top">Endpoint: /3/movie/<?= $tmdbId ?>?api_key={apikey}</td>
												<td valign="top" colspan="2">/api/v1/movie/meta/{apikey}/?sources=tmdb&id=<?= $tmdbId ?></td>
												<td valign="top" colspan="2">/v1/movie/<?= $tmdbId ?></td>
											</tr>
											<?= $results ?>
											<?PHP } ?>
										</table>
										</div>
									</div>
									<?PHP } else { ?>
									<div class="panel-body">Waiting for ID...</div>
									<?PHP } ?>
								</div>
							</div>
						</div>
						<div class="row">
							<?PHP if ($tvdbId) { ?>
							<div class="col-md-12">
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#tvdbResponse').toggle()">TVDb Response (Toggle)</div>
									<pre style="display:none;" id="tvdbResponse">
<?PHP print_r($tvdb) ?>
									</pre>
								</div>
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#notifiarrResponse').toggle()">Notifiarr Response (Toggle)</div>
									<pre style="display:none;" id="notifiarrResponse">
<?PHP print_r($notifiarr) ?>
									</pre>
								</div>
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#skyhookResponse').toggle()">Skyhook Response (Toggle)</div>
									<pre style="display:none;" id="skyhookResponse">
<?PHP print_r($skyhook) ?>
									</pre>
								</div>								
							</div>
							<?PHP } ?>
							<?PHP if ($tmdbId) { ?>
							<div class="col-md-12">
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#tmdbResponse').toggle()">TMDb Response (Toggle)</div>
									<pre style="display:none;" id="tmdbResponse">
<?PHP print_r($tmdb) ?>
									</pre>
								</div>
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#notifiarrResponse').toggle()">Notifiarr Response (Toggle)</div>
									<pre style="display:none;" id="notifiarrResponse">
<?PHP print_r($notifiarr) ?>
									</pre>
								</div>
								<div class="panel panel-default">
									<div style="cursor:pointer;" class="panel-heading" onclick="$('#servarrResponse').toggle()">Servarr Response (Toggle)</div>
									<pre style="display:none;" id="servarrResponse">
<?PHP print_r($servarr) ?>
									</pre>
								</div>								
							</div>
							<?PHP } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

<?PHP
function getMeta($source, $id)
{
	switch ($source)
	{
		case 'tvdb':	
			$url = 'https://thetvdb.com/api/'. TVDB_API_KEY .'/series/'. $id .'/all/en.json';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$result = curl_exec($ch);
			curl_close($ch);
			
			$result = @simplexml_load_string($result);
			$result = (array) $result;
			
			foreach ($result['Banners'] as $index => $bannerObj)
			{
				$thisBanner = (array) $bannerObj;
				
				if ($thisBanner['Banner'])
				{
					$thisBanner = $thisBanner['Banner'];
					$thisBanner = (array) $bannerObj;
				}
				
				if ($thisBanner['BannerType'] == 'poster')
				{
					$posters[$thisBanner['RatingCount']] = $thisBanner;
				}
			}

			$thisPoster = '';
			if ($posters)
			{
				foreach ($posters as $rating => $poster)
				{
					if ( ($rating > $previousRating) || !$previousRating)
					{
						$thisPoster = $poster;
					}
					
					$previousRating = $rating;
				}
				
				if ($thisPoster['BannerPath'])
				{
					$mediaArray['poster_path'] = 'https://thetvdb.com/banners/'. $thisPoster['BannerPath'];
				}
			}
			
			$mediaArray['tv_episode_results'] 	= $result['Episode'];
			$mediaArray['tv_season_results'] 	= $result['Series'];
		break;
		case 'skyhook':
			$url = 'https://skyhook.sonarr.tv/v1/tvdb/shows/en/'. $id;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$result = curl_exec($ch);
			curl_close($ch);
			
			$mediaArray = json2Array($result);
		break;
		case 'notifiarr-series':
			$mediaArray = api('series/meta', array('sources' => 'tvdb', 'id' => $id));
			$mediaArray = $mediaArray['TVDB'];
		break;
		case 'tmdb':
			$mediaArray = getTMDBDetails($id);
		break;
		case 'servarr':
			$url = 'https://radarrapi.servarr.com/v1/movie/'. $id;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$result = curl_exec($ch);
			curl_close($ch);
			
			$mediaArray = json2Array($result);
		break;
		case 'notifiarr-movie':
			$mediaArray = api('movie/meta', array('sources' => 'tmdb', 'id' => $id));
			$mediaArray = $mediaArray['TMDB'];
		break;
	}

	return $mediaArray;
}
?>