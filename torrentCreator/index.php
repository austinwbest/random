<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

//-- CHECK/MAKE NECESSARY FOLDERS
$folders = array('_settings', '_output', '_tmp', '_logs');
foreach ($folders as $index => $folder)
{
	if (!is_dir($folder))
	{
		mkdir($folder);
	}
}

//-- CHECK/MAKE NECESSARY FILES
$files = array('_settings/settings.json', '_settings/templates.json');
foreach ($files as $index => $file)
{
	if (!file_exists($file))
	{
		touch($file);
	}
}

include_once('includes/constants.php');

logger('Script starting', __LINE__, __FILE__);

logger('$_POST[]', __LINE__, __FILE__, 2);
foreach ($_POST as $logKey => $logVal)
{
	logger('['. $logKey .'] => '. $logVal, __LINE__, __FILE__, 3);
}
		
if ($_POST['save_settings'])
{
	logger('Saving "save_settings"', __LINE__, __FILE__);
	$settingsData = saveSettings();
}

if ($_POST['save_template'])
{
	logger('Saving "save_template"', __LINE__, __FILE__);
	$templatesData = saveTemplate();
}

logger('Fetching movies...', __LINE__, __FILE__);
$dataFileOptionsMovies = getDataDriveFiles('movies');
logger('Movies fetched.', __LINE__, __FILE__);

logger('Fetching series...', __LINE__, __FILE__);
$dataFileOptionsSeries = getDataDriveFiles('series');
logger('Series fetched.', __LINE__, __FILE__);

if ($_POST['process_start'])
{
	logger('Starting "process_start"', __LINE__, __FILE__);
	if (!$settingsData['mktorrent_location'])
	{
		$errors[] = 'mktorrent path is required to make the torrent file.';
	}
	if (!$settingsData['ffmpeg_location'] && $_POST['process_screenshots'])
	{
		$errors[] = 'ffmpeg path is required to make the screenshots.';
	}
	if (!$settingsData['mediainfo_location'])
	{
		$errors[] = 'mediainfo path is required to get the file details.';
	}
	if (!$settingsData['api_discordNotifier'])
	{
		$errors[] = 'Discord Notifier API with cache access is required to get the torrent details.';
	}
	if (!$settingsData['api_imgur'] && $_POST['process_screenshots'])
	{
		$errors[] = 'Imgur API is required to upload the screenshots.';
	}
	if (!$_POST['process_tmdb'] && $_POST['process_torrent_type'] == 'process_movie')
	{
		$errors[] = 'TMDb is required.';
	}
	if (!$_POST['process_tvdb'] && $_POST['process_torrent_type'] == 'process_series')
	{
		$errors[] = 'TVDb is required.';
	}
	if (!$_POST['process_torrent_type'])
	{
		$errors[] = 'A selection from the movie list or series list is required.';
	}
	if (!$_POST['process_announcers'])
	{
		$errors[] = 'A selection from the announcer list is required.';
	}
	
	if ($errors)
	{
		echo '<pre>';
		echo '	<b>Errors:</b><br>';
		echo '	<div style="padding-left:1em;">'. implode('<br>', $errors) .'</div>';
		echo '</pre>';
	}
	else
	{
		$torrents = createTorrentFile();
		
		echo '<pre>';
		if ($torrents)
		{
			echo '	There are up to 4 files in the locations listed below.<br><br><b>.torrent</b> is to upload to the tracker<br><b>.desc</b> is to use on the tracker (no template setup = no file contents)<br><b>.details</b> is information gathered during this process<br><b>.mediainfo</b> is a full dump of the media info if needed';
			echo '	<br><br><b>Results:</b><br>';
			echo '	<div style="padding-left:1em;">'. implode('<br>', $torrents) .'</div>';
		}
		else
		{
			echo 'Something failed...';
		}
		echo '</pre>';
	}
}
logger('Loading UI...', __LINE__, __FILE__);
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="author" content="Austin Best">

    <title>Torrent Creator</title>
	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>	
	
	<script>
		$( function() {
			$('#settings').accordion({
			  collapsible: true
			});
			$('#builder').accordion();
		});
		
		function loadTemplate()
		{
			var details = JSON.parse(decodeURIComponent($('#template_id option:selected').attr('data-template')));
			$('#template_name').val(details.template_name);
			$('#template_bbcode_img_open').val(details.template_bbcode_img_open);
			$('#template_bbcode_img_closed').val(details.template_bbcode_img_closed);
			$('#template_body').val(details.template_body.split('+').join(' '));
		}
	</script>
</head>

<div class="col-lg-6">
	<form method="post">
		<div id="builder">
			<h3>Builder</h3>
			<div>
				<table class="table table-striped table-bordered">
					<tr>
						<td width="100%">
							<input class="check-option" type="radio" name="process_torrent_type" id="process_movie" value="process_movie" /> Pick movies to create torrent for (1 torrent per selection)<br>
							<select onchange="$('#process_movie').prop('checked', true);" name="process_selected_movie[]" multiple size="10">
							<?= $dataFileOptionsMovies['options'] ?>
							</select><br>
							Movies: <?= number_format($dataFileOptionsMovies['total']) ?>
						</td>
					</tr>			
					<tr>
						<td width="100%">
							<input class="check-option" type="radio" name="process_torrent_type" id="process_series" value="process_series" /> Pick series to create torrent for (1 torrent per selection)<br>
							<select onchange="$('#process_series').prop('checked', true);" name="process_selected_series[]" multiple size="10">
							<?= $dataFileOptionsSeries['options'] ?>
							</select><br>
							Shows: <?= number_format($dataFileOptionsSeries['shows']) ?> | Seasons: <?= number_format($dataFileOptionsSeries['seasons']) ?>
						</td>
					</tr>
					<tr>
						<td>
							IMDb: <input type="text" name="process_imdb" value=""> *Most sites require this be in the description<br>
							TMDb: <input type="text" name="process_tmdb" value=""> *Required if movie is picked<br>
							TVDb: <input type="text" name="process_tvdb" value=""> *Required if series is picked
						</td>
					</tr>
					<tr>
						<td>
							<input class="check-option" type="checkbox" name="process_screenshots" /> Generate <input style="width:50px;" type="number" step="1" value="4" name="process_screenshots_amount"> screenshots,
							 1 x <input type="number" name="process_screenshots_mins" style="width:50px;" step="1" min="1" value="2"> mins<br>
							*Imgur has a 50 uploads/hr API rate limit
						</td>
					</tr>
					<tr>
						<td width="100%">
							Announcers:<br>
							<select name="process_announcers[]" multiple size="5">
							<?PHP
								$announcers = explode("\n", $settingsData['announcers']);
								if ($announcers)
								{
									foreach ($announcers as $announcer)
									{
										?><option value="<?= $announcer ?>"><?= $announcer ?></option><?PHP
									}
								}
							?>
							</select>
						</td>
					</tr>		
					<tr>
						<td align="center">
							<input type="submit" name="process_start" value="Run Script">
						</td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>
<div class="col-lg-6">
	<div id="settings">
		<h3>Settings</h3>
		<div>
			<form method="post">
				<table class="table table-striped table-bordered">
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Script</td>
					</tr>
					<tr>
						<td>Logging:<br><span style="font-size:10px;">- This will create a log everytime the page loads with details about what happened.</span></td>
						<td>
							<select name="script_logging">
								<option <?= (($settingsData['script_logging'] == 1) ? 'selected ' : '') ?>value="1">Enabled</option>
								<option <?= ((!$settingsData['script_logging'] || $settingsData['script_logging'] == 0) ? 'selected ' : '') ?>value="0">Disabled</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Media</td>
					</tr>
					<tr>
						<td>Movie paths:<br><span style="font-size:10px;">- The location of your movie folder, one location per line. Ex: D:\Media\Movies</span></td>
						<td><textarea name="movie_location" cols="35" rows="5"><?= $settingsData['movie_location'] ?></textarea></td>
					</tr>
					<tr>
						<td width="50%">Series paths:<br><span style="font-size:10px;">- The location of your series folder, one location per line. Ex: D:\Media\TV Shows</span></td>
						<td><textarea name="series_location" cols="35" rows="5"><?= $settingsData['series_location'] ?></textarea></td>
					</tr>
					<tr>
						<td>Extensions:<br><span style="font-size:10px;">- List of media extensions you use, Ex. mkv,mp4,avi (comma separated)</span></td>
						<td><input type="text" name="media_extensions" value="<?= $settingsData['media_extensions'] ?>"></td>
					</tr>
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Tools</td>
					</tr>
					<tr>
						<td>mktorrent location:<br><span style="font-size:10px;">- Path to mktorrent.exe</span></td>
						<td><input type="text" name="mktorrent_location" value="<?= $settingsData['mktorrent_location'] ?>"></td>
					</tr>
					<tr>
						<td>mediainfo location:<br><span style="font-size:10px;">- Path to mediainfo.exe</span></td>
						<td><input type="text" name="mediainfo_location" value="<?= $settingsData['mediainfo_location'] ?>"></td>
					</tr>
					<tr>
						<td>ffmpeg location:<br><span style="font-size:10px;">- Path to ffmpeg.exe</span></td>
						<td><input type="text" name="ffmpeg_location" value="<?= $settingsData['ffmpeg_location'] ?>"></td>
					</tr>
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">API Keys</td>
					</tr>
					<tr>
						<td>Discord Notifier API:<br><span style="font-size:10px;">- API Key</span></td>
						<td>
							<input type="text" style="width:175px;" name="api_discordNotifier" value="<?= $settingsData['api_discordNotifier'] ?>" />
						</td>
					</tr>
					<tr>
						<td>imgur API:<br><span style="font-size:10px;">- API Key</span></td>
						<td>
							<input type="text" style="width:175px;" name="api_imgur" value="<?= $settingsData['api_imgur'] ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Announcers</td>
					</tr>
					<tr>
						<td>Announce links:<br><span style="font-size:10px;">- Your unique URL provided by the site, one per line<br>- name|url Ex. rbg|http://rbg.com/12345/announce</td>
						<td><textarea name="announcers" cols="50" rows="5"><?= $settingsData['announcers'] ?></textarea></td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<input type="submit" name="save_settings" value="Save Settings">
						</td>
					</tr>
				</table>
			</form>
		</div>
		<h3>Templates</h3>
		<div>
			<form method="post">
				<table class="table table-striped table-bordered">
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Existing</td>
					</tr>
					<tr>
						<td width="50%">Update template:<br><span style="font-size:10px;">- Select the announcer site to modify its template</span></td>
						<td>
							<select id="template_id" name="template_id" onchange="loadTemplate()">
								<option value="0">-- Select one --</option>
								<?PHP
									if ($settingsData['announcers'])
									{
										$announcers = explode("\n", $settingsData['announcers']);
										foreach ($announcers as $announcer)
										{
											$name = reset(explode('|', $announcer));
											$template = $templatesData['templates'][$name];
											?><option value="<?= $name ?>" data-template="<?= urlencode(json_encode($template)) ?>"><?= $name ?></option><?PHP
										}
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center" style="font-weight:bold;">Template</td>
					</tr>
					<tr>
						<td>Image BBCode (open):<br><span style="font-size:10px;">- These vary from site to site, need to know which to use when adding images</span></td>
						<td>
							<input type="text" style="width:175px;" id="template_bbcode_img_open" name="template_bbcode_img_open" value="" />
						</td>
					</tr>
					<tr>
						<td>Image BBCode (close):<br><span style="font-size:10px;">- These vary from site to site, need to know which to use when adding images</span></td>
						<td>
							<input type="text" style="width:175px;" id="template_bbcode_img_closed" name="template_bbcode_img_closed" value="" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							Dynamic fields are the ones that the script will auto fill while running. A list of them is below.<br>
							{TITLE} - Will be replaced with the proper name of the torrent<br>
							{TORRENT} - Will be replaced with the torrents filename<br>
							{IMDB} - Will be replaced with a link to the IMDb for the torrent (if provided)<br>
							{TMDB} - Will be replaced with a link to the TMDb for the torrent (if provided)<br>
							{TVDB} - Will be replaced with a link to the TVDb for the torrent (if provided)<br>
							{IMAGES} - Will be replaced with the screenshot links (if any where taken)<br>
							{POSTER} - Will be replaced with the poster for the torrent (if one was found)<br>
							{DESCRIPTION} - Will be replaced the the overview<br>
							{MEDIAINFO_SMALL} - Will be replaced with the media info overview<br>
							{MEDIAINFO_FULL} - Will be replaced with the full media info dump
						</td>
					</tr>
					<tr>
						<td>Template:<br><span style="font-size:10px;">- Body of the template</td>
						<td><textarea cols="50" rows="30" id="template_body" name="template_body"></textarea></td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<input type="submit" name="save_template" value="Save Template">
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
<br clear="all" />

<?PHP
logger('UI Loaded...', __LINE__, __FILE__);
?>