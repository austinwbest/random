<?PHP
/*
----------------------------------
 ------  Created: 042321   ------
 ------  Austin Best	   ------
----------------------------------
*/
error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (file_exists('tmp/xseed'))
{
	exit();
}

touch('tmp/xseed');
include('xseed.constants.php');
include('xseed.qbittorrent.php');
include('xseed.jackett.php');
include('xseed.torrents.php');

$history 	= json_decode(@file_get_contents('xseed.history.json'), true);
$qbtCookie 	= qbt_login();
$qbtItems 	= qbt_queue($qbtCookie);

if (!$qbtItems)
{
	echo 'Nothing found in QBT queue.';
}
else
{	
	foreach ($qbtItems as $qbtItem)
	{
		if (@in_array($qbtItem['name'], $history))
		{
			continue;
		}

		$history[] = $qbtItem['name'];

		//-- FIND OTHER SITES THAT HAVE IT
		$search = jackett_search($qbtItem['name']);	
		$search = $search['Results'];

		if (!$search)
		{
			continue;
		}

		foreach ($search as $match)
		{
			if (jackett_download($match))
			{
				$tmpSeeds++;
			}
		}

		if (!$tmpSeeds)
		{
			echo 'No sites found with '. $qbtItem['name'] .'<br>';
		}
		else
		{
			//-- ADD TO QBT AND POINT TO EXISTING LOCATION
			$dir = opendir('tmp');
			while ($file = readdir($dir))
			{
				if (strpos($file, '.torrent') !== false)
				{
					$add = qbt_addTorrent($qbtCookie, 'tmp/'. $file, $qbtItem['content_path'], $qbtItem['hash']);
					if (!$add)
					{
						echo 'xseed added for '. $file .'<br>';
					}
					else
					{
						echo 'could not xseed '. $file .' - '. $add .'<br>';
					}
					@unlink('tmp/'. $file);
				}
			}
			closedir($dir);
		}
	}
}

file_put_contents('xseed.history.json', json_encode($history, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
unlink('tmp/xseed');
?>