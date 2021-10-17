<?PHP
/*
----------------------------------
 ------  Created: 072621   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';
require 'includes/shared.php';

$develop = curl('https://api.github.com/repos/Radarr/Radarr/tags?ref=develop', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$developVersion = $develop['response'][0]['name'];

$master = curl('https://api.github.com/repos/Radarr/Radarr/tags?ref=master', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$masterVersion = $master['response'][0]['name'];

//-- MIGRATIONS
$contents = curl('https://api.github.com/repos/Radarr/Radarr/contents/src/NzbDrone.Core/Datastore/Migration?ref=develop', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$nightlyMigrations = $contents['response'];

$contents = curl('https://api.github.com/repos/Radarr/Radarr/contents/src/NzbDrone.Core/Datastore/Migration?ref='. $developVersion, array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$developMigrations = $contents['response'];

$contents = curl('https://api.github.com/repos/Radarr/Radarr/contents/src/NzbDrone.Core/Datastore/Migration?ref=master', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$masterMigrations = $contents['response'];

//-- LANGUAGES
$contents = curl('https://raw.githubusercontent.com/Radarr/Radarr/develop/src/NzbDrone.Core/Languages/Language.cs', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$nightlyLanguage = $contents['response'];

$contents = curl('https://api.github.com/repos/Radarr/Radarr/contents/src/NzbDrone.Core/Languages/Language.cs?ref='. $developVersion, array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
$developLanguage = base64_decode($contents['response']['content']);

$contents = curl('https://raw.githubusercontent.com/Radarr/Radarr/master/src/NzbDrone.Core/Languages/Language.cs', array('Authorization: token '. GITHUB_API_KEY));
$masterLanguage = $contents['response'];

$langRegex = '/Language\(\\d+\,/';
preg_match_all($langRegex, $nightlyLanguage, $matches);
foreach ($matches[0] as $match)
{
	$nightlyLanguageId = preg_replace('/[^0-9]/', '', $match);
}
preg_match_all($langRegex, $developLanguage, $matches);
foreach ($matches[0] as $match)
{
	$developLanguageId = preg_replace('/[^0-9]/', '', $match);
}
preg_match_all($langRegex, $masterLanguage, $matches);
foreach ($matches[0] as $match)
{
	$masterLanguageId = preg_replace('/[^0-9]/', '', $match);
}

echo '<table style="max-width: 50%;" class="table table-striped table-bordered">';
echo '	<tr>';
echo '		<td colspan="5" align="center">Branch Hopping Allowed</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td align="center" style="font-weight: bold;">Currently on</td>';
echo '		<td align="center" style="font-weight: bold;">Jumping to</td>';
echo '		<td align="center" style="font-weight: bold;">Reverting back to</td>';
echo '		<td align="center" style="font-weight: bold;">Migration</td>';
echo '		<td align="center" style="font-weight: bold;">Language</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Master</td>';
echo '		<td>Develop</td>';
echo '		<td>Master</td>';
echo '		<td>'. (count($developMigrations) == count($masterMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($developLanguageId == $masterLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Master</td>';
echo '		<td>Nightly</td>';
echo '		<td>Master</td>';
echo '		<td>'. (count($nightlyMigrations) == count($masterMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($masterLanguageId == $nightlyLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Develop</td>';
echo '		<td>Master</td>';
echo '		<td>Develop</td>';
echo '		<td>'. (count($developMigrations) == count($masterMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($developLanguageId == $masterLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Develop</td>';
echo '		<td>Nightly</td>';
echo '		<td>Develop</td>';
echo '		<td>'. (count($nightlyMigrations) == count($developMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($nightlyLanguageId == $developLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Nightly</td>';
echo '		<td>Master</td>';
echo '		<td>Nightly</td>';
echo '		<td>'. (count($nightlyMigrations) == count($masterMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($nightlyLanguageId == $masterLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td>Nightly</td>';
echo '		<td>Develop</td>';
echo '		<td>Nightly</td>';
echo '		<td>'. (count($nightlyMigrations) == count($developMigrations) ? 'Yes' : 'No') .'</td>';
echo '		<td>'. ($nightlyLanguageId == $developLanguageId ? 'Yes' : 'No') .'</td>';
echo '	</tr>';
echo '</table>';

require 'includes/footer.php';
?>