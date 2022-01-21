<?PHP
/*
----------------------------------
 ------  Created: 030121   ------
 ------  Austin Best	     ------
----------------------------------
*/
// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$frontendFiles  = array('js');
$backendFiles   = array('cs');
$startTime      = microtime(true);

define('APP', $_GET['application']);
define('TYPE', $_GET['type']);

//-- PATHS
define('LIDARR_BACKEND_ROOT', 'C:\\Development\\Code\\Lidarr\\src');
define('LIDARR_FRONTEND_ROOT', 'C:\\Development\\Code\\Lidarr\\frontend\\src');
define('LIDARR_LANGUAGE_ROOT', 'C:\\Development\\Code\\Lidarr\\src\\NzbDrone.Core\\Localization\\Core\\');
define('PROWLARR_BACKEND_ROOT', 'C:\\Development\\Code\\Prowlarr\\src');
define('PROWLARR_FRONTEND_ROOT', 'C:\\Development\\Code\\Prowlarr\\frontend\\src');
define('PROWLARR_LANGUAGE_ROOT', 'C:\\Development\\Code\\Prowlarr\\src\\NzbDrone.Core\\Localization\\Core\\');
define('RADARR_BACKEND_ROOT', 'C:\\Development\\Code\\Radarr\\src');
define('RADARR_FRONTEND_ROOT', 'C:\\Development\\Code\\Radarr\\frontend\\src');
define('RADARR_LANGUAGE_ROOT', 'C:\\Development\\Code\\Radarr\\src\\NzbDrone.Core\\Localization\\Core\\');
define('READARR_BACKEND_ROOT', 'C:\\Development\\Code\\Readarr\\src');
define('READARR_FRONTEND_ROOT', 'C:\\Development\\Code\\Readarr\\frontend\\src');
define('READARR_LANGUAGE_ROOT', 'C:\\Development\\Code\\Readarr\\src\\NzbDrone.Core\\Localization\\Core\\');
define('SONARR_BACKEND_ROOT', 'C:\\Development\\Code\\Sonarr\\src');
define('SONARR_FRONTEND_ROOT', 'C:\\Development\\Code\\Sonarr\\frontend\\src');
define('SONARR_LANGUAGE_ROOT', 'C:\\Development\\Code\\Sonarr\\src\\NzbDrone.Core\\Localization\\Core\\');

switch (APP) {
  case 'lidarr';
    define('BACKEND_ROOT', LIDARR_BACKEND_ROOT);
    define('FRONTEND_ROOT', LIDARR_FRONTEND_ROOT);
    define('LANGUAGE_ROOT', LIDARR_LANGUAGE_ROOT);
    break;
  case 'prowlarr';
    define('BACKEND_ROOT', PROWLARR_BACKEND_ROOT);
    define('FRONTEND_ROOT', PROWLARR_FRONTEND_ROOT);
    define('LANGUAGE_ROOT', PROWLARR_LANGUAGE_ROOT);
    break;
  case 'radarr';
    define('BACKEND_ROOT', RADARR_BACKEND_ROOT);
    define('FRONTEND_ROOT', RADARR_FRONTEND_ROOT);
    define('LANGUAGE_ROOT', RADARR_LANGUAGE_ROOT);
    break;
  case 'readarr';
    define('BACKEND_ROOT', READARR_BACKEND_ROOT);
    define('FRONTEND_ROOT', READARR_FRONTEND_ROOT);
    define('LANGUAGE_ROOT', READARR_LANGUAGE_ROOT);
    break;
  case 'sonarr';
    define('BACKEND_ROOT', SONARR_BACKEND_ROOT);
    define('FRONTEND_ROOT', SONARR_FRONTEND_ROOT);
    define('LANGUAGE_ROOT', SONARR_LANGUAGE_ROOT);
    break;
}

//-- FULL STOP IF NO APP WAS PICKED
if (!file_exists(LANGUAGE_ROOT . DIRECTORY_SEPARATOR . 'en.json') || !APP || !TYPE) {
  exit();
}

$lang           = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents(LANGUAGE_ROOT . DIRECTORY_SEPARATOR . 'en.json')), true);
$langKeys       = array_keys($lang);
$originalCount  = count($langKeys);

$codebase = array(
  'front' => array(
    'files'      => recursive_read(FRONTEND_ROOT),
    'regex_u'    => "/translate\(\'%s\'/i",
    'regex_m'    => "/translate\('(.*?)'/mi",
    'ext'        => $frontendFiles,
    'fileCount'  => 0,
  ),
  'back'  => array(
    'files'      => recursive_read(BACKEND_ROOT, false, true),
    'regex_u'    => '/GetLocalizedString\(\"%s\"\)/mi',
    'regex_m'    => '/GetLocalizedString\("(.*?)"/mi',
    'ext'        => $backendFiles,
    'fileCount'  => 0,
  ),
);

switch (TYPE) {
  case 'unused':
    foreach ($codebase as $where => $details) {
      foreach ($details['files'] as $filePath) {
        //-- ALL KEYS FOUND
        if (!$langKeys) {
          break;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        //-- SKIP EXTENSIONS
        if (in_array($extension, $details['ext'])) {
          $codebase[$where]['fileCount']++;
          $file = file_get_contents($filePath);
          foreach ($langKeys as $index => $key) {
            //-- KEY ALREADY FOUND
            if (!$langKeys[$index]) {
              continue;
            }
            preg_match(sprintf($details['regex_u'], strtolower($key)), $file, $matches);
            if ($matches) {
              //-- KEY FOUND, REMOVE IT
              unset($langKeys[$index]);
            }
          }
        }
      }
    }

    //-- APPLY REMOVALS TO ENGLISH
    $removeKeys = array_values($langKeys);
    $english     = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents(LANGUAGE_ROOT . DIRECTORY_SEPARATOR . 'en.json')), true);
    foreach ($removeKeys as $key) {
      if ($english[$key]) {
        $removed++;
        unset($english[$key]);
      }
    }

    //-- WRITE THE CHANGES TO THE FILE
    $json   = json_encode($english, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    //-- DROP IT FROM 4 SPACES TO 2 FOR *arr
    $json   = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json);
    $handle = fopen(LANGUAGE_ROOT . DIRECTORY_SEPARATOR . 'en.json', 'w');
    fwrite($handle, $json, strlen($json));
    fclose($handle);

    $results = array(
      'frontendScanned'  => $codebase['front']['fileCount'],
      'backendScanned'   => $codebase['back']['fileCount'],
      'keyTotal'         => $originalCount,
      'unusedTotal'      => count($langKeys),
      'runtime'          => number_format(microtime(true) - $startTime, 2),
      'unused'           => $langKeys,
      'removed'          => $removed
    );
    break;
  case 'missing':
    foreach ($codebase as $where => $details) {
      foreach ($details['files'] as $filePath) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        //-- SKIP EXTENSIONS
        if (in_array($extension, $details['ext'])) {
          $codebase[$where]['fileCount']++;
          $file = file_get_contents($filePath);
          preg_match_all($details['regex_m'], $file, $matches);

          if (!empty($matches[1])) {
            foreach ($matches[1] as $checkExistingKey) {
              if (!$lang[$checkExistingKey]) {
                $codebaseKeys[$checkExistingKey][] = $filePath;
              }
            }
          }
        }
      }
    }

    $results = array(
      'frontendScanned'  => $codebase['front']['fileCount'],
      'backendScanned'   => $codebase['back']['fileCount'],
      'missingTotal'     => count($codebaseKeys),
      'runtime'          => number_format(microtime(true) - $startTime, 2),
      'missing'          => $codebaseKeys
    );
    break;
  default:
    exit();
    break;
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);

function recursive_read($directory, $entries_array = array(), $backend = false)
{
  if (is_dir($directory)) {
    $handle = opendir($directory);
    while (false !== ($entry = readdir($handle))) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      $entry = $directory . DIRECTORY_SEPARATOR . $entry;

      if (is_dir($entry) && (!$backend || ($backend && strpos($entry, 'NzbDrone.') !== false))) {
        $entries_array = recursive_read($entry, $entries_array, $backend);
      } else {
        $entries_array[] = $entry;
      }
    }
    closedir($handle);
  }
  return $entries_array;
}
