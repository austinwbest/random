<?PHP
/*
----------------------------------
 ------  Created: 021121   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$settingsMap	= array('Cookie' 			=> array('helpText' => 'Login cookie from website', 'textMatches' => array('configData.Cookie.Value'), 'privacy' => '', 'type' => ''),
						'Username' 			=> array('helpText' => 'Site Username', 'textMatches' => array('configData.Username.Value', 'configData.User.Value'), 'privacy' => 'UserName', 'type' => ''),
						'2FA' 				=> array('helpText' => 'Two-Factor Auth', 'textMatches' => array('configData.TwoFactorAuth.Value'), 'privacy' => '', 'type' => ''),
						'Email' 			=> array('helpText' => 'Site Email', 'textMatches' => array('configData.Email.Value'), 'privacy' => '', 'type' => ''),
						'Pid' 				=> array('helpText' => 'Site PID', 'textMatches' => array('configData.Pid.Value'), 'privacy' => '', 'type' => ''),
						'Pin' 				=> array('helpText' => 'Site Pin', 'textMatches' => array('configData.Pin.Value'), 'privacy' => '', 'type' => ''),
						'Password'			=> array('helpText' => 'Site Password', 'textMatches' => array('configData.Password.Value'), 'privacy' => 'Password', 'type' => 'Password'),
						'Passkey'			=> array('helpText' => 'Site Passkey', 'textMatches' => array('configData.Passkey.Value'), 'privacy' => '', 'type' => ''),
						'API Key'			=> array('helpText' => 'Site API Key', 'textMatches' => array('configData.Key.Value'), 'privacy' => 'ApiKey', 'type' => ''),
						'RSS Key' 			=> array('helpText' => 'Site RSS Key', 'textMatches' => array('configData.RSSKey.Value'), 'privacy' => '', 'type' => ''),
						'Captcha Text' 		=> array('helpText' => 'Captcha Text', 'textMatches' => array('configData.CaptchaText.Value'), 'privacy' => '', 'type' => ''),
						'Captcha Cookie' 	=> array('helpText' => 'Captcha Cookie', 'textMatches' => array('configData.CaptchaCookie.Value'), 'privacy' => '', 'type' => ''),
						'Filters' 			=> array('helpText' => 'Filters (optional)', 'textMatches' => array('configData.FilterString.Value'), 'privacy' => '', 'type' => ''),
						);

$pending 	= 'pending/';
$converted 	= 'converted/';

$pendingDir = opendir($pending);
while ($indexer = readdir($pendingDir))
{
	if (!is_dir($indexer) && stripos($indexer, '.cs') !== false)
	{
		$fileString = file_get_contents($pending . $indexer);

		if (stripos($fileString, 'BaseCachingWebIndexer') === false && stripos($fileString, 'BaseWebIndexer') === false)
		{
			echo date('Y-m-d H:i:s') .' Skipping '. $indexer .'<br><br>';
			continue;
		}
		echo date('Y-m-d H:i:s') .' Converting '. $indexer .'...<br>';

		$fileLines 	= file($pending . $indexer);
		$site 		= trim(str_replace('.cs', '', $indexer));
		
		unset($indexerUrls, $encoding, $language, $type, $baseUrl, $tvQ, $movieQ, $musicQ, $bookQ, $caps, $category, $categoryParts, $settings, $rules, $fieldDefinitions, $description, $applyConfiguration, $performQuery, $parseSingleResult, $parseMultiResult);
		foreach ($fileLines as $index => $line)
		{
			$line = trim($line);

			//-- AlternativeLinks
			if (strpos($line, 'AlternativeSiteLinks') !== false)
			{
				for ($x = ($index + 1); $x <= count($fileLines); $x++)
				{
					if ($fileLines[$x])
					{
						if (strpos(trim($fileLines[$x]), '};') !== false)
						{
							break;
						}

						$indexerUrls .= "            ". trim($fileLines[$x]) ."\n";
					}
				}
			}

			//-- ParseSingleResult
			if (strpos($line, 'private') !== false && strpos($line, 'ParseSingleResult') !== false && !$parseSingleResult)
			{
				for ($x = $index; $x <= count($fileLines); $x++)
				{
					$parseSingleResult .= $fileLines[$x];

					if (strpos(trim($fileLines[$x]), 'return') !== false && strpos(trim($fileLines[$x]), 'releases') !== false)
					{
						$parseSingleResult .= $fileLines[$x + 1];
						break;
					}
				}
			}

			//-- ParseMultiResult
			if (strpos($line, 'private') !== false && strpos($line, 'ParseMultiResult') !== false && !$parseMultiResult)
			{
				for ($x = $index; $x <= count($fileLines); $x++)
				{
					$parseMultiResult .= $fileLines[$x];

					if (strpos(trim($fileLines[$x]), 'return') !== false && strpos(trim($fileLines[$x]), 'releases') !== false)
					{
						$parseMultiResult .= $fileLines[$x + 1];
						break;
					}
				}
			}

			//-- PerformQuery
			if (strpos($line, 'private') !== false && strpos($line, 'PerformQuery') !== false && !$performQuery)
			{
				for ($x = $index; $x <= count($fileLines); $x++)
				{
					$performQuery .= '    '. $fileLines[$x];

					if (strpos(trim($fileLines[$x]), 'return') !== false && strpos(trim($fileLines[$x]), '(response)') !== false)
					{
						$performQuery .= '    '. $fileLines[$x + 1];
						break;
					}
				}
			}

			//-- ApplyConfiguration
			if (strpos($line, 'private') !== false && strpos($line, 'ApplyConfiguration') !== false && !$applyConfiguration)
			{
				for ($x = $index; $x <= count($fileLines); $x++)
				{
					$applyConfiguration .= '    '. $fileLines[$x];

					if (strpos(trim($fileLines[$x]), 'return') !== false && strpos(trim($fileLines[$x]), 'IndexerConfigurationStatus') !== false)
					{
						$applyConfiguration .= '    '. $fileLines[$x + 1];
						break;
					}
				}
			}

			//-- ENCODING
			if (stripos($line, 'Encoding = ') !== false && !$encoding)
			{
				$encoding = trim(str_replace('Encoding = ', '', str_replace(';', '', $line)));
			}

			//-- DESCRIPTION
			if (stripos($line, 'description: ') !== false && !$description)
			{
				$description = trim(str_replace('description: ', '', str_replace(',', '', $line)));
			}

			//-- LANGUAGE
			if (stripos($line, 'Language = ') !== false && !$language)
			{
				$language = ucfirst(trim(str_replace('Language = ', '', str_replace(';', '', $line))));
			}

			//-- TYPE
			if (stripos($line, 'Type = ') !== false && !$type)
			{
				$type = ucfirst(trim(str_replace('Type = "', '', str_replace('";', '', $line))));
			}

			//-- LINK
			if (stripos($line, 'link:') !== false && !$baseUrl)
			{
				$baseUrl = trim(str_replace('link:', '', str_replace(',', '', $line)));
			}

			//-- GET CAPS
			if (stripos($line, 'TvSearchParam.Q') !== false && !$tvQ)
			{
				$tvQ = $line;
			}
			if (stripos($line, 'MovieSearchParam.Q') !== false && !$movieQ)
			{
				$movieQ = $line;
			}
			if (stripos($line, 'MusicSearchParam.Q') !== false && !$musicQ)
			{
				$musicQ = $line;
			}
			if (stripos($line, 'BookSearchParam.Q') !== false && !$bookQ)
			{
				$bookQ = $line;
			}

			//-- GET CAPS CATEGORIES
			if (stripos($line, 'AddCategoryMapping(') !== false)
			{
				$category 		= trim(str_replace('AddCategoryMapping(', '', str_replace(');', '', $line)));
				$categoryParts 	= explode(',', $category);

				unset($extraCommaSplits);
				if ($categoryParts[3])
				{
					for ($x = 3; $x < count($categoryParts); $x++)
					{
						$extraCommaSplits .= ($extraCommaSplits ? ', ' : '') . trim($categoryParts[$x]);
					}
				}

				$caps[] 		= '            caps.Categories.AddCategoryMapping('. trim($categoryParts[0]) .', NewznabStandardCategory.'. trim(end(explode('.', $categoryParts[1]))) . (trim($categoryParts[2]) ? ', '. trim($categoryParts[2] . ($extraCommaSplits ? $extraCommaSplits : '')) : '') .');';
				$capsCat		= true;
			}

			if (trim($line) == '}')
			{
				if (stripos($fileLines[$index-1], 'AddCategoryMapping(') !== false)
				{
					$capsCat = false;
				}
			}

			if ($capsCat && !trim($line) && $caps[count($caps) - 1] != "\n")
			{
				$caps[] = " ";
			}

			//-- GET SETTINGS
			foreach ($settingsMap as $name => $data)
			{
				foreach ($data['textMatches'] as $toMatch)
				{
					if (stripos($line, $toMatch) !== false)
					{
						$settings[$name] = '            '. $name .' = "";';
					}
				}
			}
		}

		$indexerUrls = $indexerUrls ? "{\n". $indexerUrls ."        };" : '{ '. $baseUrl .' };';

		$base = file_get_contents('template-'. ($type == 'Public' ? 'public' : 'private') .'.cs');
		$convert = str_replace('NewIndexer', $site, $base);
		$convert = str_replace('{IndexerUrls}', $indexerUrls, $convert);
		$convert = str_replace('{Encoding}', ($encoding ? $encoding : 'Encoding.UTF8'), $convert);
		$convert = str_replace('{Language}', ($language ? $language : 'en-us'), $convert);
		$convert = str_replace('{Privacy}', ($type == 'Public' ? 'Public' : 'Private'), $convert);
		$convert = str_replace('{BaseUrl}', $baseUrl, $convert);
		$convert = str_replace('{Description}', $description, $convert);
		$TvSearchParam = '
                TvSearchParams = new List<TvSearchParam>
                                   {
                                       '. $tvQ .'
                                   }'. (($movieQ || $musicQ || $bookQ) ? ',' : '');
		$MovieSearchParam = '
                MovieSearchParams = new List<MovieSearchParam>
                                   {
                                       '. $movieQ .'
                                   }'. (($musicQ || $bookQ) ? ',' : '');
		$MusicSearchParam = '
                MusicSearchParams = new List<MusicSearchParam>
                                   {
                                       '. $musicQ .'
                                   }'. (($bookQ) ? ',' : '');
		$BookSearchParam = '
                BookSearchParams = new List<BookSearchParam>
                                   {
                                       '. $bookQ .'
                                   }';
		$convert = str_replace('{TvSearchParam}', ($tvQ ? $TvSearchParam : ''), $convert);
		$convert = str_replace('{MovieSearchParam}', ($movieQ ? $MovieSearchParam : ''), $convert);
		$convert = str_replace('{MusicSearchParam}', ($musicQ ? $MusicSearchParam : ''), $convert);
		$convert = str_replace('{BookSearchParam}', ($bookQ ? $BookSearchParam : ''), $convert);
		$convert = str_replace('{caps}', implode("\n", $caps), $convert);
		$convert = str_replace('{ApplyConfiguration}', ($applyConfiguration ? "\n\n            /*\n". $applyConfiguration ."            */\n" : ''), $convert);
		$convert = str_replace('{PerformQuery}', ($performQuery ? "\n\n            /*\n". $performQuery ."            */" : ''), $convert);
		$convert = str_replace('{ParseSingleResult}', ($parseSingleResult ? "\n\n        /*\n". $parseSingleResult ."        */" : ''), $convert);
		$convert = str_replace('{ParseMultiResult}', ($parseMultiResult ? "\n\n        /*\n". $parseMultiResult ."        */" : ''), $convert);

		if ($settings)
		{
			unset($ruleSettings);
			foreach ($settings as $setting)
			{
				$ruleSettings[] = '            '. str_replace('="', ' = "', str_replace(' ', '', $setting));
			}

			$convert = str_replace('{Settings}', implode("\n", $ruleSettings), $convert);

			$fieldDefinitions[] = "\n".'        [FieldDefinition(1, Label = "Base Url", HelpText = "Select which baseurl Prowlarr will use for requests to the site", Type = FieldType.Select, SelectOptionsProviderAction = "getUrls")]';
			$fieldDefinitions[] = '        public string BaseUrl { get; set; }';
			$counter = 2;
			foreach ($settings as $name => $setting)
			{
				unset($definitionParts);

				//-- GET RULES
				$rules[] = '            RuleFor(c => c.'. str_replace(' ', '', $name) .').NotEmpty();';

				$definitionParts = $counter .',';
				$definitionParts .= ' Label = "'. $name .'",';
				$definitionParts .= ' HelpText = "'. $settingsMap[$name]['helpText'] .'"';
				$definitionParts .= ($settingsMap[$name]['privacy'] ? ', Privacy = PrivacyLevel.'. $settingsMap[$name]['privacy'] : '');
				$definitionParts .= ($settingsMap[$name]['type'] ? ', Type = FieldType.'. $settingsMap[$name]['type'] : '');
				$definitionParts .= ($settingsMap[$name]['SelectOptionsProviderAction'] ? ', SelectOptionsProviderAction = '. $settingsMap[$name]['SelectOptionsProviderAction'] : '');

				//-- GET FIELD DEFINITIONS
				$fieldDefinitions[] = "\n".'        [FieldDefinition('. $definitionParts .')]';
				$fieldDefinitions[] = '        public string '. $name .' { get; set; }';
				$counter++;
			}

			$fieldDefinitions[] = "\n".'        [FieldDefinition('. $counter++ .')]';
			$fieldDefinitions[] = '        public IndexerBaseSettings BaseSettings { get; set; } = new IndexerBaseSettings();';

			$convert = str_replace('{RuleFor}', implode("\n", $rules), $convert);
			$convert = str_replace('{FieldDefinition}', implode("\n", $fieldDefinitions), $convert);
		}
		file_put_contents($converted . $site .'.cs', $convert);

		echo date('Y-m-d H:i:s') .' Converted '. $indexer .'<br><br>';
	}
}
closedir($pendingDir);

?>