<?PHP
/*
----------------------------------
 ------  Created: 091320   ------
 ------  Austin Best	   ------
----------------------------------
*/

$endpoints = array(0 => array('app' => 'prowlarr', 'endpoint' => 'statistics'),

                   1 => array('app' => 'radarr', 'endpoint' => 'moviesearch?movieids=[1053]'),
                   2 => array('app' => 'radarr', 'endpoint' => 'jackett'),
                   3 => array('app' => 'radarr', 'endpoint' => 'movie/lookup/tmdb?tmdbId=5176'),
                   4 => array('app' => 'radarr', 'endpoint' => 'movie?tmdbId=5176'),
                  );

define('ENDPOINT', 0);

define('RADARR_API_KEY', '');
define('RADARR_URL', 'localhost');
define('RADARR_PORT', 7878);

define('PROWLARR_API_KEY', '');
define('PROWLARR_URL', 'localhost');
define('PROWLARR_PORT', 9696);

if ($endpoints[ENDPOINT]['app'] == 'prowlarr')
{
    $url = 'http://'. PROWLARR_URL .':'. PROWLARR_PORT .'/api/v1/'. $endpoints[ENDPOINT]['endpoint'];
    $api = PROWLARR_API_KEY;
}
if ($endpoints[ENDPOINT]['app'] == 'radarr')
{
    $url = 'http://'. RADARR_URL .':'. PROWLARR_PORT .'/api/v3/'. $endpoints[ENDPOINT]['endpoint'];
    $api = RADARR_API_KEY;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'X-Api-Key:'. $api));
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo 'URL: '. $url .'<br>';
echo 'API: '. $api .'<br><br>';

echo '<pre>';
print_r($response);
echo '</pre>';
?>