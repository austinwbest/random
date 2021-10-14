<?PHP
/*
----------------------------------
 ------  Created: 100620   ------
 ------  Austin Best	   ------
----------------------------------
*/

ini_set('max_execution_time', '-1');
ini_set('memory_limit', '-1');
set_time_limit(0);

//-- SETTINGS
$settingsData = json_decode(file_get_contents('_settings/settings.json'), true);
$templatesData = json_decode(file_get_contents('_settings/templates.json'), true);

//-- INCLUDE EXTRA FILES
$includePath = ((file_exists('includes/functions.php')) ? 'includes/' : ''); //-- Web browser path is different from bash php execute path
$includeFiles = array('functions.php', 'images.php', 'metadata.php', 'mediainfo.php', 'torrents.php');
foreach ($includeFiles as $index => $includeFile)
{
	include($includePath . $includeFile);
}

define('ABSOLUTE_PATH', str_replace('/'. end(explode('/', $_SERVER['SCRIPT_FILENAME'])), '', $_SERVER['SCRIPT_FILENAME']));
define('LOGFILE', 'log_'. time() .'.txt');
?>