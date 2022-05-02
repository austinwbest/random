<?PHP
/*
----------------------------------
 ------  Created: 072621   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

define('GITHUB_API_KEY', '');

$app    = strtolower($_GET['app']);
$repo   = ucfirst($app) .'/'. ucfirst($app);
$valid  = false;

if (!$app) {
    exit('No app was provided.');
}

switch ($app) {
    case 'lidarr':
    case 'prowlarr':
    case 'radarr':
    case 'readarr':
    case 'whisparr':
        $valid = true;
        break;
}

if (!$valid) {
    exit($app .' is not supported.');
}

$memcache = new Memcached();
$memcache->addServer('localhost', 11211) or die('Cache connection failure');
$memcached = $memcache->get($app .'-branches');

if (@$_GET['memcached'] == 'reset') {
    unset($memcached);
}

if (!$memcached) {
    $develop = curl('https://api.github.com/repos/'. $repo .'/tags?ref=develop', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $developVersion = $develop['response'][0]['name'];

    $master = curl('https://api.github.com/repos/'. $repo .'/tags?ref=master', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $masterVersion = $master['response'][0]['name'];

    //-- MIGRATIONS
    $contents = curl('https://api.github.com/repos/'. $repo .'/contents/src/NzbDrone.Core/Datastore/Migration?ref=develop', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $nightlyMigrations = $contents['response'];

    $contents = curl('https://api.github.com/repos/'. $repo .'/contents/src/NzbDrone.Core/Datastore/Migration?ref='. $developVersion, array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $developMigrations = $contents['response'];

    $contents = curl('https://api.github.com/repos/'. $repo .'/contents/src/NzbDrone.Core/Datastore/Migration?ref=master', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $masterMigrations = $contents['response'];

    //-- LANGUAGES
    $contents = curl('https://raw.githubusercontent.com/'. $repo .'/develop/src/NzbDrone.Core/Languages/Language.cs', array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $nightlyLanguage = $contents['response'];

    $contents = curl('https://api.github.com/repos/'. $repo .'/contents/src/NzbDrone.Core/Languages/Language.cs?ref='. $developVersion, array('Authorization: token '. GITHUB_API_KEY, 'accept: application/vnd.github.v3+json'));
    $developLanguage = base64_decode($contents['response']['content']);

    $contents = curl('https://raw.githubusercontent.com/'. $repo .'/master/src/NzbDrone.Core/Languages/Language.cs', array('Authorization: token '. GITHUB_API_KEY));
    $masterLanguage = $contents['response'];

    $cache['developVersion']    = $developVersion;
    $cache['masterVersion']     = $masterVersion;
    $cache['nightlyMigrations'] = $nightlyMigrations;
    $cache['developMigrations'] = $developMigrations;
    $cache['masterMigrations']  = $masterMigrations;
    $cache['nightlyLanguage']   = $nightlyLanguage;
    $cache['developLanguage']   = $developLanguage;
    $cache['masterLanguage']    = $masterLanguage;

    $memcache->set($app .'-branches', $cache, 43200); //-- 12 HOUR CACHE
    
    $branches['cache'] = 'false';
} else {
    $developVersion     = $memcached['developVersion'];
    $masterVersion      = $memcached['masterVersion'];
    $nightlyMigrations  = $memcached['nightlyMigrations'];
    $developMigrations  = $memcached['developMigrations'];
    $masterMigrations   = $memcached['masterMigrations'];
    $nightlyLanguage    = $memcached['nightlyLanguage'];
    $developLanguage    = $memcached['developLanguage'];
    $masterLanguage     = $memcached['masterLanguage'];
    
    $branches['cache'] = 'true';
}

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

$branches['branchJumping']['master-develop'] = array('from'       => 'master', 
                                                     'to'         => 'develop', 
                                                     'back'       => (count($developMigrations) == count($masterMigrations) && $developLanguageId == $masterLanguageId) ? 'yes' : 'no',
                                                     'conflicts'  => array('migration'    => count($developMigrations) == count($masterMigrations) ? 'no' : 'yes',
                                                                           'language'     => $developLanguageId == $masterLanguageId ? 'no' : 'yes'
                                                                          )
                                                    );

$branches['branchJumping']['master-nightly'] = array('from'       => 'master', 
                                                     'to'         => 'nightly', 
                                                     'back'       => (count($nightlyMigrations) == count($masterMigrations) && $nightlyLanguageId == $masterLanguageId) ? 'yes' : 'no',
                                                     'conflicts'  => array('migration'    => count($nightlyMigrations) == count($masterMigrations) ? 'no' : 'yes',
                                                                           'language'     => $nightlyLanguageId == $masterLanguageId ? 'no' : 'yes'
                                                                          )
                                                    );

$branches['branchJumping']['develop-nightly'] = array('from'       => 'develop', 
                                                      'to'         => 'nightly', 
                                                      'back'       => (count($developMigrations) == count($nightlyMigrations) && $developLanguageId == $nightlyLanguageId) ? 'yes' : 'no',
                                                      'conflicts'  => array('migration'    => count($developMigrations) == count($nightlyMigrations) ? 'no' : 'yes',
                                                                            'language'     => $developLanguageId == $nightlyLanguageId ? 'no' : 'yes'
                                                                           )
                                                    );

$return = json_encode($branches, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
header('Content-Type: application/json');
echo $return;

function curl($url, $headers = false, $method = false, $payload = false)
{
    $curlHeaders = array('user-agent: Notifiarr');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    switch ($method) {
        case 'DELETE':
        case 'PATCH':
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            break;
    }

    if ($headers) {
        foreach ($headers as $header) {
            $curlHeaders[] = $header;
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response       = curl_exec($ch);
    $response       = (!empty(json_decode($response, true))) ? json_decode($response, true) : $response;
    $error          = json_decode(curl_error($ch), true);
    $code           = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    foreach ($curlHeaders as $header) {
        $cleanedHeaders[] = $header;
    }

    return array('url' => $url, 'method' => $method, 'headers' => $cleanedHeaders, 'payload' => $payload, 'response' => $response, 'error' => $error, 'code' => $code);
}
