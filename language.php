<?PHP
/*
----------------------------------
 ------  Created: 082920   ------
 ------  Austin Best	   ------
----------------------------------
*/
// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$startTime = microtime(true);
/*
  Supported:
    (label|title|helpText|helpTextWarning|legend|message|errorMessage)="text here"
    (label|title|helpText|helpTextWarning|legend|message|errorMessage)={`text here '${variableHere}'?`}
    (label|title|helpText|helpTextWarning|legend|message|errorMessage)={variableHere ? 'text here' : 'text there'}
    (label|title|helpText|helpTextWarning|legend|message|errorMessage)={'text here'}
    <FormLabel>text here</FormLabel>
    const key = "val"|'val'|{variableHere}
    let key = "val"|'val'|{variableHere}
  Needs supported:
  Not supporting:
    (label|title|helpText|helpTextWarning|legend|message|errorMessage)={variableHere} - Almost all of these are variables linked to things that do not need translated
    Conditional AND Interpolation in same line
*/

//-- PATHS
define('RADARR_BACKEND_ROOT', 'C:\\Repositories\\Radarr-Fork\\src');
define('RADARR_FRONTEND_ROOT', 'C:\\Repositories\\Radarr-Fork\\frontend\\src');
define('RADARR_LANGUAGE', 'C:\\Repositories\\Radarr-Fork\\src\\NzbDrone.Core\\Localization\\Core\\en.json');
define('LIDARR_BACKEND_ROOT', 'C:\\Repositories\\Lidarr-Fork\\src');
define('LIDARR_FRONTEND_ROOT', 'C:\\Repositories\\Lidarr-Fork\\frontend\\src');
define('LIDARR_LANGUAGE', 'C:\\Repositories\\Lidarr-Fork\\src\\NzbDrone.Core\\Localization\\Core\\en.json');
define('READARR_BACKEND_ROOT', 'C:\\Repositories\\Readarr\\src');
define('READARR_FRONTEND_ROOT', 'C:\\Repositories\\Readarr\\frontend\\src');
define('READARR_LANGUAGE', 'C:\\Repositories\\Readarr\\src\\NzbDrone.Core\\Localization\\Core\\en.json');
define('SONARR_BACKEND_ROOT', 'C:\\Repositories\\Sonarr-Fork\\src');
define('SONARR_FRONTEND_ROOT', 'C:\\Repositories\\Sonarr-Fork\\frontend\\src');
define('SONARR_LANGUAGE', 'C:\\Repositories\\Sonarr-Fork\\src\\NzbDrone.Core\\Localization\\Core\\en.json');

define('APP', $_GET['application']);

define('DS', '\\');
//-- NOT HELPING PEOPLE WHO IGNORE ME :)
define('DRY_RUN', (APP == 'sonarr' ? 1 : $_GET['dryRun'])); //-- WILL SHOW ALL THE OUTPUT BUT NOT MODIFY THE FILES
define('DEBUG', $_GET['debug']); //-- USE FOR ANYTHING TO SEE ON THE PASS
define('DEBUG_FILE', $_GET['debugFile']); //-- USE TO SEE FILE SPECIFIC INFORMATION
define('DEBUG_FULL_STOP', $_GET['debugFullstop']); //-- EXIT SCRIPT BEFORE CHANGES WRITTEN AFTER DISPLAYING FILE INFORMATION FROM "DEBUG_FILE", SAVES HAVING TO HARD RESET THE REPO EACH TEST WHEN "DRY_RUN" IS "false"

switch (APP)
{
  case 'radarr';
    define('TRANSLATE_ROOT', RADARR_FRONTEND_ROOT);
    define('EXISTING_LANGUAGE', RADARR_LANGUAGE);
    
    $ignoreFiles = array('App', //-- app title
                         'DeleteMovieModalContent', 
                         'AddListMovieActionsCell',  //-- commented code
                         'DiscoverMovieActionsCell', //-- commented code
                         'SignalRConnector',
                         'colors', //-- color codes
                         'MovieBanner', //-- image
                         'MovieHeadshot', //-- image
                         'MoviePoster', //-- image
                         'Peers', //-- title={`${getPeersTooltipPart(seeders, 'seeder')}, ${getPeersTooltipPart(leechers, 'leecher')}`} 
                        );
  break;
  case 'lidarr';
    define('TRANSLATE_ROOT', LIDARR_FRONTEND_ROOT);
    define('EXISTING_LANGUAGE', LIDARR_LANGUAGE);
    
    $ignoreFiles = array('App', //-- app title
                        );
  break;
  case 'readarr';
    define('TRANSLATE_ROOT', READARR_FRONTEND_ROOT);
    define('EXISTING_LANGUAGE', READARR_LANGUAGE);
    
    $ignoreFiles = array('App', //-- app title
                         'AuthorPoster', //-- image
                         'AuthorBanner', //-- image
                         'BookCover', //-- image
                         'DateFilterBuilderRowValue'
                        );
  break;
  case 'sonarr';
    define('TRANSLATE_ROOT', SONARR_FRONTEND_ROOT);
    define('EXISTING_LANGUAGE', SONARR_LANGUAGE);
  break;
  default:
    define('TRANSLATE_ROOT', '');
    define('EXISTING_LANGUAGE', '');
  break;
}

$regexs = explode('|', $_GET['regex']);

$searchFor = array(array('apply' => ((in_array('regex_attrQuotes', $regexs)) ? true : false),   
                         'name' => 'attrQuotes',   
                         'regex' => '/(label|title|helpText|helpTextWarning|legend|message|errorMessage|placeholder)="([^"]+)"/i'),
                   array('apply' => ((in_array('regex_attrInterp', $regexs)) ? true : false),   
                         'name' => 'attrInterp',   
                         'regex' => '/(label|title|helpText|helpTextWarning|legend|message|errorMessage|placeholder)={([^"]+)}/i'),
                   array('apply' => ((in_array('regex_tagFormLabel', $regexs)) ? true : false), 
                         'name' => 'tagFormLabel', 
                         'regex' => '/<FormLabel>(.*?)<\/FormLabel>/i'),
                   array('apply' => ((in_array('regex_tagDiv', $regexs)) ? true : false),       
                         'name' => 'tagDiv',       
                         'regex' => '/<div>(.*?)<\/div>/i'),
                   array('apply' => ((in_array('regex_defineQuote', $regexs)) ? true : false),  
                         'name' => 'defineQuote',  
                         'regex' => '/(const|let )(.*) = "([^"]+)"/i'),
                   array('apply' => ((in_array('regex_defineApos', $regexs)) ? true : false),   
                         'name' => 'defineApos',   
                         'regex' => '/(const|let )(.*) = \'([^"]+)\'/i'),
                   array('apply' => ((in_array('regex_mlHelpTexts', $regexs)) ? true : false),   
                         'name' => 'mlHelpTexts',   
                         'regex' => 'helpTexts='),
                  );

$searchFiles = array('js');

?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>

<script>
  function runConversion()
  {
    var regex = '';
    $.each($('[id^=regex_]'), function(){
      if ($(this).prop('checked'))
      {
        regex += ((regex) ? '|' : '') + $(this).attr('id');
      }
    });
    
    var base = 'language.php';
    if ($('#application').val())
    {
      var url = '?application='+ $('#application').val();
      url += '&debug='+ $('#debug').val();
      url += '&dryRun='+ $('#dryRun').val();
      url += '&debugFile='+ $('#debugFile').val();
      url += '&debugFullstop='+ $('#debugFullstop').val();
      url += '&regex='+ regex;
      
      window.location.href = base + url;
      return;
    }
    window.location.href = base;
  }
</script>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

<table class="table table-striped table-bordered">
  <tr>
    <td colspan="2" style="font-weight:bold; text-align:center;">Pick the application to translate</td>
  </tr>
  <tr>
    <td width="40%">Application</td>
    <td>
      <select id="application">
        <option value="">-- Select One --</option>
        <option value="">Reset</option>
        <option <?= ((APP == 'radarr') ? 'selected ' : '') ?>value="radarr">Radarr</option>
        <option <?= ((APP == 'lidarr') ? 'selected ' : '') ?>value="lidarr">Lidarr</option>
        <option <?= ((APP == 'readarr') ? 'selected ' : '') ?>value="readarr">Readarr</option>
        <option <?= ((APP == 'sonarr') ? 'selected ' : '') ?>value="sonarr">Sonarr</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Dry run<br>* No file changes will be made</td>
    <td>
      <select id="dryRun">
        <option <?= ((!DRY_RUN) ? 'selected ' : '') ?>value="0">No</option>
        <option <?= ((DRY_RUN) ? 'selected ' : '') ?>value="1">Yes</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Debug<br>* Dump logs on the screen for everything unless <b>Debug file</b> is specified</td>
    <td>
      <select id="debug">
        <option <?= ((!DEBUG) ? 'selected ' : '') ?>value="0">No</option>
        <option <?= ((DEBUG) ? 'selected ' : '') ?>value="1">Yes</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Debug stop<br>* Will stop the script exection after it matches the <b>Debug file</b></td>
    <td>
      <select id="debugFullstop">
        <option <?= ((!DEBUG_FULL_STOP) ? 'selected ' : '') ?>value="0">No</option>
        <option <?= ((DEBUG_FULL_STOP) ? 'selected ' : '') ?>value="1">Yes</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Debug file<br>* Example: SomeFile.js</td>
    <td>
      <input type="text" id="debugFile" value="<?= DEBUG_FILE ?>">
    </td>
  </tr>
  <tr>
    <td>Regex<br>* Choose the regex to utilize</td>
    <td>
      <?PHP
      foreach ($searchFor as $index => $regexData)
      {
        ?>
        <input type="checkbox" id="regex_<?= $regexData['name'] ?>" <?= (($regexData['apply']) ? 'checked' : '') ?>> <?= htmlspecialchars($regexData['regex']) ?><br>
        <?PHP
      }
      ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center"><button onclick="runConversion()">Run</button></td>
  </tr>
</table>

<?PHP
//-- FULL STOP IF NO APP WAS PICKED
if (!TRANSLATE_ROOT || !APP)
{
  exit();
}

$totalFilesToChange = $totalFiles = $totalLines = $fileLines = $fileKeys = 0;

$existingLanguageArray = array();
if (file_exists(EXISTING_LANGUAGE))
{
  $existingLanguageArray = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents(EXISTING_LANGUAGE)), true);
}

$list = recursive_read(TRANSLATE_ROOT);

foreach ($list as $index => $filePath)
{
  $extension = pathinfo($filePath, PATHINFO_EXTENSION);
  $filename = pathinfo($filePath, PATHINFO_FILENAME);
  
  //-- SKIP EXTENSIONS
  if (in_array($extension, $searchFiles))
  {    
    //-- SKIP FILES
    if ($ignoreFiles)
    {
      if (in_array($filename, $ignoreFiles))
      {
        continue;  
      }
    }
   
    unset($newContent);
    $fileKeys = $fileLines = $skipLines = 0;
    $translated = false;
    $totalFiles++;
    echo 'Checking: '. $filePath .'<br>';
    
    $contents = file($filePath);
    
    //-- LETS DO THE FILE LINES LOOPTY LOOP
    foreach ($contents as $lineIndex => $line)
    {      
      foreach ($searchFor as $regexIndex => $regexOptions)
      {
        if (!$regexOptions['apply'])
        {
          continue;
        }
        $regex = $regexOptions['regex'];
        
        //-- DOES NOT USE A REGEX
        if ($regexOptions['name'] == 'mlHelpTexts')
        {   
          if (strpos($line, $regex) !== false && strpos($line, ']}') === false)
          {
            unset($matches);
            
            foreach ($contents as $lineIndex2 => $line2)
            {
              if ($lineIndex2 < $lineIndex)
              {
                continue;
              }
              
              $matches[$lineIndex2] = trim($line2);
              
              if (trim($line2) == ']}')
              {
                  break;
              }
            }
            
            if (DEBUG)
            {
              echo '<div style="margin-left:1em;">';
              echo '  regex used: '. $regexOptions['name'] .' | '. htmlspecialchars($regexOptions['regex']) .' ['. __LINE__ .']';
              echo '  <pre>';
              print_r($matches);
              echo '  </pre>';
              echo '</div>';
              echo '<div style="margin-left:2em;">';
              echo '  Found a language change:<br>';
              echo '  <div style="margin-left:3em;">';
            }
            
            unset($newLines, $rebuildLine);
            $lineCnt = 0;
            foreach ($matches as $lineNumber => $match)
            {
              $lineCnt++;
              if ($lineCnt == 1 || $lineCnt == count($matches))
              {
                continue;
              }
              
              $thisLine = preg_match("/'(.*)'/i", $match, $text);
              $lineText = $text[1];
              $findKey = findKeyFromAttr($contents, $lineIndex);
              $key = $findKey['key'];
              $newKeyFound = $findKey['newKeyFound'];
              
              if ($newKeyFound)
              {
                $langKey = ucwords($key .'HelpTexts'. ($lineCnt - 1));
                $newLanguageKeys[$langKey] = $lineText;
                $line = str_replace($match, 'translate(\''. $langKey .'\')', $line);
                
                $newLines[$lineNumber] = str_replace($match, 'translate(\''. $langKey .'\')', $match);
                
                if (DEBUG)
                {
                  echo 'LANG: "'. $langKey .'" => "'. $lineText .'" ['. __LINE__ .']...<br>';
                }
              }
            }
            
            if ($newLines)
            {
              $translated = true;
              $lineCnt = 0;
              foreach ($matches as $lineNumber => $match)
              {
                $lineCnt++;
                if ($lineCnt == 1 || $lineCnt == count($matches))
                {
                  $newContent[] = $match ."\n";
                  $rebuildLine .= $match ."\n";
                  continue;
                }
                
                $fileKeys++;
                $thisLine = trim($newLines[$lineNumber]) . ((count($matches) > ($lineCnt + 1)) ? ",\n" : "\n");
                $rebuildLine .= $thisLine;
                
                $newContent[] = $thisLine;
              }
              
              $skipLines = $lineCnt;

              if (DEBUG)
              {
                echo 'NEW LINE: '. str_replace("\n", '<br>', $rebuildLine) .' ['. __LINE__ .']...<br>';
              }
            }
            
            if (DEBUG)
            {
              echo '  </div>';
              echo '</div>';
            }
          }
        }
        elseif ($skipLines == 0)
        {
          unset($matches);
          preg_match_all($regex, $line, $matches, PREG_SET_ORDER);

          if ($matches)
          {
            foreach ($matches as $matchIndex => $matchData)
            {
              unset($key);
              
              //-- INDEX 3 FROM $matchData
              if ($regexOptions['name'] == 'defineQuote' || $regexOptions['name'] == 'defineApos')
              {
                //-- CHECK AND SKIP IF ALREADY TRANSLATED || DATE STRING || SECTIONS || KEYS || PROTOCOL
                if (strpos($matchData[0], 'translate(') === false && $matchData[3] != 'MMM D YYYY' && $matchData[3] != 'yyyy-mm-dd' && trim($matchData[2]) != 'protocol' && trim($matchData[2]) != 'key' && 
                    trim($matchData[2]) != 'section' && strpos($matchData[0], 'app.') === false && strpos($matchData[0], 'settings.') === false && strpos($matchData[0], 'system.') === false)
                {
                  //-- IGNORE THE ALL CAPS, TYPICALLY INTERNAL USAGE
                  if (preg_match('/^[^a-z]+$/', trim($matchData[2])) !== 1)
                  {
                    if (DEBUG)
                    {
                      echo '<div style="margin-left:1em;">';
                      echo '  regex used: '. $regexOptions['name'] .' | '. htmlspecialchars($regexOptions['regex']) .' ['. __LINE__ .']';
                      echo '  <pre>';
                      print_r($matches);
                      echo '  </pre>';
                      echo '</div>';
                    }
                    
                    //-- CHECK IF VARIABLE
                    $text = trim(end(explode('= ', $line)));
                    if ($text[0] != '{')
                    {                    
                      //-- SEARCH THE FILE AND UPDATE THE ASSIGNMENT
                      $lineFound = false;
                      foreach ($contents as $lineIndex2 => $line2)
                      {
                        if (strpos($line2, '={'. $matchData[2] .'}') !== false)
                        {
                          $lineFound = true;
                          break;
                        }
                      }
                      
                      if ($lineFound)
                      {
                        $fileKeys++;
                        $translated = true;
                        
                        $newLanguageKeys[ucwords($matchData[2])] = $matchData[3];
                        $line = str_replace("'". $matchData[3] ."'", 'translate(\''. ucwords($matchData[2]) .'\')', $line);
                        
                        if (DEBUG)
                        {
                          echo '<div style="margin-left:2em;">';
                          echo '  Found a language change:<br>';
                          echo '  <div style="margin-left:3em;">';
                          echo '    LANG: "'. $key .'" => "'. $matchData[2] .'" ['. __LINE__ .']...<br>';
                          echo '    NEW LINE: '. $line .'" ['. __LINE__ .']...<br>';
                          echo '  </div>';
                          echo '</div>';
                        }
                      }
                    }
                  }
                }
              }
              //-- INDEX 2 FROM $matchData
              elseif ($regexOptions['name'] == 'attrQuotes' || $regexOptions['name'] == 'attrInterp')
              {
                //-- CHECK AND SKIP IF ALREADY TRANSLATED && NOT CALLING A FUNCTION (formatDateTime, titleCase, getTooltip, getDownloadTooltip)
                if (strpos($matchData[2], 'translate(') === false && strpos($matchData[2], 'formatDateTime(') === false && strpos($matchData[2], 'titleCase(') === false && 
                    strpos($matchData[2], 'getTooltip(') === false && strpos($matchData[2], 'getDownloadTooltip(') === false)
                {
                  if (DEBUG)
                  {
                    echo '<div style="margin-left:1em;">';
                    echo '  regex used: '. $regexOptions['name'] .' | '. htmlspecialchars($regexOptions['regex']) .' ['. __LINE__ .']';
                    echo '  <pre>';
                    print_r($matches);
                    echo '  </pre>';
                    echo '</div>';
                  }

                  //-- FIND THE name="" OR title="" AND USE FOR THE KEY WITH THESE.. CAN DO IT IN A LOOP BUT THIS WAS QUICK AND SIMPLE
                  if ($matchData[1] == 'helpText' || $matchData[1] == 'helpTextWarning' || $matchData[1] == 'message' || $matchData[1] == 'placeholder')
                  {
                    $findKey = findKeyFromAttr($contents, $lineIndex);
                    $key = $findKey['key'];
                    $newKeyFound = $findKey['newKeyFound'];
                    
                    if ($newKeyFound)
                    {
                      $key = rtrim($key, '"');
                      if ($matchData[1] == 'helpText')
                      {
                        $key .= 'HelpText';
                      }
                      if ($matchData[1] == 'helpTextWarning')
                      {
                        $key .= 'HelpTextWarning';
                      }
                      if ($matchData[1] == 'message')
                      {
                        $key .= 'MessageText';
                      }
                      if ($matchData[1] == 'placeholder')
                      {
                        $key .= 'PlaceHolder';
                      }
                    }
                  }

                  if ($key)
                  {
                    $key = ucfirst($key);
                  }
                  else
                  {
                    $key = reset(explode('${', $matchData[2]));
                    $key = preg_replace('/[^a-zA-Z0-9 ]/', '', $key);
                    //-- THIS WILL BE EMPTY IF THE KEY IS INTERPOLATED AND STARTS WITH THE INTERPOLATION, FIX IT
                    if (!$key)
                    {
                      $key = preg_replace('/[^a-zA-Z0-9 ]/', '', $matchData[2]);
                    }
                    $key = str_replace(' ', '', ucwords($key));
                    if (strpos($matchData[2], '${') !== false)
                    {                    
                      $key .= 'Interp';
                    }
                  }

                  $skipLangKey = false;
                  //-- CHECK IF INTERPOLATED BUT SKIP CONDITIONALS & USE translate(key, []) TO RESOLVE
                  if (strpos($matchData[2], '${') !== false && strpos($matchData[2], ' ? ') === false && strpos($matchData[2], ' : ') === false)
                  {                  
                    unset($sprintf, $translate);
                    $interpolationHolder = $matchData[2];
                    $matchData[2] = str_replace('`', '', $matchData[2]);
                    $matchData[2] = str_replace('${', '{', $matchData[2]);
                    preg_match_all('/\{([^\}]*?)}.*?/', $matchData[2], $interpolations, PREG_SET_ORDER);

                    if ($interpolations)
                    {
                      if (DEBUG)
                      {
                        echo '<div style="padding-top:0; margin-left:1em;">';
                        echo 'interpolation match ['. __LINE__ .']...<br>';
                        echo '</div>';
                      }
                      
                      $fileKeys++;
                      $addSprintfImport = $translated = true;
                      
                      foreach ($interpolations as $interpolationIndex => $interpolationMatch)
                      {
                        $sprintf .= (($sprintf) ? ', ' : '') . $interpolationMatch[1];
                        $interpolationHolder = str_replace('${'. $interpolationMatch[1] .'}', '{'. $interpolationIndex .'}', $interpolationHolder);
                      }
                      $matchData[2] = $interpolationHolder;
                      $translate = '{translate(\''. $key .'\', ['. $sprintf .'])}';
                      $line = str_replace($matchData[0], $matchData[1] .'='. $translate, $line);
                    }
                  }
                  //-- VARIABLE STRING ASSIGNMENT
                  elseif (strpos($matchData[0], "={'") !== false)
                  {
                    if (DEBUG)
                    {
                      echo '<div style="padding-top:0; margin-left:1em;">';
                      echo 'variable match ['. __LINE__ .']...<br>';
                      echo '</div>';
                    }
                    
                    $fileKeys++;
                    $translated = true;
                    
                    $translate = 'translate(\''. $key .'\')';
                    $line = str_replace($matchData[2], 'translate(\''. $key .'\')', $line);
                  }
                  //-- CONDITIONAL ASSIGNMENT BUT SKIP INTERPOLATION & DEFINED VARIABLES
                  elseif (strpos($matchData[2], '${') === false && strpos($matchData[2], ' ? ') !== false && strpos($matchData[2], ' : ') !== false && 
                         (strpos($matchData[2], '"') !== false || strpos($matchData[2], '`') !== false || strpos($matchData[2], "'") !== false))
                  {
                    if (DEBUG)
                    {
                      echo '<div style="padding-top:0; margin-left:1em;">';
                      echo 'conditional match ['. __LINE__ .']...<br>';
                      echo '</div>';
                    }
                    
                    $fileKeys++;
                    $translated = true;
                    $skipLangKey = true;
                    
                    $variable = trim(reset(explode('?', $matchData[2])));
                    $tmpMatch = str_replace($variable .' ? ', '', $matchData[2]);
                    $textOne = reset(explode('\' : ', $tmpMatch));
                    ltrim($textOne, $textOne[0]);
                    $textTwo = trim(end(explode(' : \'', $matchData[2])));
                    rtrim($textTwo, "'");
                    
                    $keyOne = ucwords($variable).ucwords($textOne);
                    $keyOne = preg_replace('/[^a-zA-Z0-9 ]/', '', $keyOne);
                    $keyOne = str_replace(' ', '', ucwords($keyOne));
                    $keyTwo = ucwords($variable).ucwords($textTwo);
                    $keyTwo = preg_replace('/[^a-zA-Z0-9 ]/', '', $keyTwo);
                    $keyTwo = str_replace(' ', '', ucwords($keyTwo));
                    $newLanguageKeys[$keyOne] = $textOne;
                    $newLanguageKeys[$keyTwo] = $textTwo;

                    if ($keyOne == 'undefined' || $keyOne == 'null')
                    {
                      $translate = '{'. $variable .' ? '. $keyOne .' : translate(\''. $keyTwo .'\')}';
                    }
                    elseif ($keyTwo == 'undefined' || $keyTwo == 'null')
                    {
                      $translate = '{'. $variable .' ? translate(\''. $keyOne .'\') : '. $keyTwo .'}';
                    }
                    else
                    {
                      $translate = '{'. $variable .' ? translate(\''. $keyOne .'\') : translate(\''. $keyTwo .'\')}';
                    }
                    $line = str_replace($matchData[0], $matchData[1] .'='. $translate, $line);

                    if (DEBUG)
                    {
                      echo '<div style="margin-left:2em;">';
                      echo '  Found a language change:<br>';
                      echo '  <div style="margin-left:3em;">';
                      echo '    LANG: "'. $keyOne .'" => "'. $textOne .'" ['. __LINE__ .']...<br>';
                      echo '    LANG: "'. $keyTwo .'" => "'. $textTwo .'" ['. __LINE__ .']...<br>';
                      echo '    NEW LINE: '. $line .'" ['. __LINE__ .']...<br>';
                      echo '  </div>';
                      echo '</div>';
                    }
                  }
                  else
                  {
                    if (DEBUG)
                    {
                      echo '<div style="padding-top:0; margin-left:1em;">';
                      echo 'attribute match ['. __LINE__ .']...<br>';
                      echo '</div>';
                    }
                    
                    //-- name={title} TYPE MATCHES, SKIP THESE FOR NOW SINCE THEY ARE TYPICALLY DYNAMICALLY ASSIGNED BY NON LANGUAGE RELATED SOURCES
                    $preRegex = str_replace($matchData[1] .'=', '', $matchData[0]);

                    if (substr($preRegex, 0, 1) != '{' && substr($preRegex, -1) != '}')
                    {
                      $fileKeys++;
                      $translated = true;
                      $skipLangKey = false;
                      $line = str_replace('"'. $matchData[2] .'"', '{translate(\''. $key .'\')}', $line);
                    }
                    else
                    {
                      $skipLangKey = true;
                      $translated = false;
                    }
                  }
                  
                  if (!$skipLangKey)
                  {
                    $translated = false;
                    unset($langValue);
                    $langValue = rtrim(ltrim($matchData[2], '`'), '`');
                    
                    //-- THERE IS ENOUGH TO USE THIS
                    if (preg_match("/[a-z]/i", $matchData[2]))
                    {
                      $translated = true;
                      $newLanguageKeys[$key] = $langValue;

                      if (DEBUG)
                      {
                        echo '<div style="margin-left:2em;">';
                        echo '  Found a language change:<br>';
                        echo '  <div style="margin-left:3em;">';
                        echo '    LANG: "'. $key .'" => "'. $langValue .'" ['. __LINE__ .']...<br>';
                        echo '    NEW LINE: '. $line .'" ['. __LINE__ .']...<br>';
                        echo '  </div>';
                        echo '</div>';
                      }
                    }
                    //-- RESET IT BACK
                    else
                    {
                      echo '<div style="margin-left:2em;">';
                      echo '  Skipping this change, pointless key to translate ['. __LINE__ .']<br>';
                      echo '</div>';
                      $line = $contents[$lineIndex];
                      $fileKeys--;
                    }
                  }
                }
              }
              //-- INDEX 1 FROM $matchData
              elseif ($regexOptions['name'] == 'tagFormLabel' || $regexOptions['name'] == 'tagDiv')
              {
                //-- CHECK IF ALREADY TRANSLATED || VARIABLE
                if (strpos($matchData[1], '{translate(') === false && $matchData[1][0] != '{')
                {
                  if (DEBUG)
                  {
                    echo '<div style="margin-left:1em;">';
                    echo '  regex used: '. $regexOptions['name'] .' | '. htmlspecialchars($regexOptions['regex']) .' ['. __LINE__ .']';
                    echo '  <pre>';
                    print_r($matches);
                    echo '  </pre>';
                    echo '</div>';
                  }
                  
                  $fileKeys++;
                  $translated = true;
                  
                  $key = preg_replace('/[^a-zA-Z0-9 ]/', '', $matchData[1]);
                  $key = str_replace(' ', '', ucwords($key));
                  $line = trim(str_replace($matchData[1], "\n".'  {translate(\''. $key .'\')}'."\n", $line)) ."\n";
                  
                  $newLanguageKeys[$key] = $matchData[1];
                  
                  if (DEBUG)
                  {
                    echo '<div style="margin-left:2em;">';
                    echo '  Found a language change:<br>';
                    echo '  <div style="margin-left:3em;">';
                    echo '    LANG: "'. $key .'" => "'. $matchData[1] .'" ['. __LINE__ .']...<br>';
                    echo '    NEW LINE: '. htmlspecialchars($line) .' ['. __LINE__ .']...<br>';
                    echo '  </div>';
                    echo '</div>';
                  }
                }
              }
            }
          }
        }
      }

      if ($skipLines > 0)
      {
        $skipLines--;
        continue;
      }

      $newContent[] = $line;
    }

    if ($fileKeys > 0)
    {
      $totalFilesToChange++;
      
      if (DEBUG)
      {
        echo '<div style="margin-left:2em;">';
        echo '  Found changes, rebuilding file to apply changes...<br>';
        echo '</div>';
      }
      
      //-- REBUILD THE FILE FROM ARRAY OF LINES TO A STRING
      $fileString = implode('', $newContent);
      
      //-- CHECK IF IMPORTS EXISTS AND ADD IT IF NOT, LINT WILL CLEAN UP THE ORDER ERROR
      $translateImport = false;
      foreach ($contents as $index => $line)
      {
        $importLine = false;
        $words = explode(' ', $line);
        
        if ($words[0] == 'import' || $words[1] == 'import' )
        {
          $importLine = true;
          
          if (strpos($line, 'import translate from') !== false)
          {
            $translateImport = true;
          }
        }

        if (!$importLine)
        {
          $previousLine = $contents[$index - 1];
          break;
        }
      }

      if (!$translateImport)
      {
        $newImportLine = $previousLine . 'import translate from \'Utilities/String/translate\';'."\n";
        $fileString = str_replace($previousLine, $newImportLine, $fileString);
      }

      //-- DEBUG A SPECIFIC FILE AND SEE IT BEFORE IT WRITES
      if (DEBUG && DEBUG_FILE)
      {
        if (strpos($filePath, DEBUG_FILE) !== false)
        {
          echo str_replace("\n", '<br>', htmlspecialchars($fileString));
          
          if (DEBUG_FULL_STOP)
          {
            exit();
          }
        }
      }

      if (!DRY_RUN)
      {
        file_put_contents($filePath, $fileString);
      }
    }
    
    $totalLines += count($contents);
    $fileLines += count($contents);
    echo '<div style="margin-left:1em;">';
    echo '  Lines searched: '. number_format($fileLines) .'<br>';
    echo '  Strings found to fix: '. number_format($fileKeys) .'<br>';
    echo '</div>';
  }
}

echo '<hr>';
echo '<b>Language</b><br>';
echo 'Files searched: '. number_format($totalFiles) .'<br>';
echo 'Lines searched: '. number_format($totalLines) .'<br>';
echo 'Files found to fix: '. number_format($totalFilesToChange) .'<br>';
echo 'Strings found to fix: '. number_format(count($newLanguageKeys)) .'<br>';

if (is_array($existingLanguageArray) && is_array($newLanguageKeys))
{
  $mergedLanguageKeys = array_merge($existingLanguageArray, $newLanguageKeys);
}

$newStrings = (($mergedLanguageKeys > 0) ? number_format(count($mergedLanguageKeys) - count($existingLanguageArray)) : 0);
echo 'Strings to be added: '. $newStrings .'<br><br>';

echo 'Existing strings: '. number_format(count($existingLanguageArray)) .'<br>';
echo 'Total strings: '. number_format(count($existingLanguageArray) + $newStrings) .'<br>';

$endTime = microtime(true);
echo '<br>Execution time: '. number_format(($endTime - $startTime), 4) .' seconds<br>';

//-- WRITE NEW JSON FILE, WRITING WITH json_encode CAUSES FORMATTING ISSUES SO JUST LOOP IT
if ($mergedLanguageKeys)
{
  ksort($mergedLanguageKeys);
  foreach ($mergedLanguageKeys as $key => $val)
  {
    $newJson .= (($newJson) ? ",\n" : '') .'  "'. $key .'": "'. $val .'"';
  }
  $newJson = '{'."\n". $newJson ."\n".'}'."\n";

  if (!DRY_RUN)
  {
    file_put_contents(EXISTING_LANGUAGE, $newJson);
  }
}

//-- FUNCTIONS
function recursive_read($directory, $entries_array = array()) 
{
    if (is_dir($directory)) 
    {
        $handle = opendir($directory);
        while (FALSE !== ($entry = readdir($handle))) 
        {
            if ($entry == '.' || $entry == '..') 
            {
                continue;
            }
            $Entry = $directory . DS . $entry;
            
            if(is_dir($Entry)) 
            {
                $entries_array = recursive_read($Entry, $entries_array);
            } 
            else 
            {
                $entries_array[] = $Entry;
            }
        }
        closedir($handle);
    }
    return $entries_array;
}

function findKeyFromAttr($contents, $lineIndex)
{
  $onePrevious    = $contents[$lineIndex - 1];
  $twoPrevious    = $contents[$lineIndex - 2];
  $threePrevious  = $contents[$lineIndex - 3];
  $fourPrevious   = $contents[$lineIndex - 4];
  $fivePrevious   = $contents[$lineIndex - 5];
  
  $oneAfter       = $contents[$lineIndex + 1];
  $twoAfter       = $contents[$lineIndex + 2];
  $threeAfter     = $contents[$lineIndex + 3];
  $fourAfter      = $contents[$lineIndex + 4];
  $fiveAfter      = $contents[$lineIndex + 5];
  
  $newKeyFound = false;
  switch (true)
  {
    case (strpos($onePrevious, 'name="') !== false):
      $key = trim(str_replace('name="', '', $onePrevious));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($twoPrevious, 'name="') !== false):
      $key = trim(str_replace('name="', '', $twoPrevious));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($threePrevious, 'name="') !== false):
      $key = trim(str_replace('name="', '', $threePrevious));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($fourAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $fourAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($fivePrevious, 'name="') !== false):
      $key = trim(str_replace('name="', '', $fivePrevious));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($oneAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $oneAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($twoAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $twoAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($threeAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $threeAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($fourAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $fourAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($fiveAfter, 'name="') !== false):
      $key = trim(str_replace('name="', '', $fiveAfter));
      $key = rtrim($key, '"');
      $newKeyFound = true;
    break;
    case (strpos($onePrevious, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $onePrevious));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($twoPrevious, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $twoPrevious));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($threePrevious, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $threePrevious));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($fourAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $fourAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($fivePrevious, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $fivePrevious));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($oneAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $oneAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($twoAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $twoAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($threeAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $threeAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($fourAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $fourAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
    case (strpos($fiveAfter, 'title=') !== false):
      $key = trim(str_replace('title={translate(\'', '', $fiveAfter));
      $key = trim(str_replace('\')}', '', $key));
      $key = rtrim($key, "'");
      $newKeyFound = true;
    break;
  }
  
  return array('key' => $key, 'newKeyFound' => $newKeyFound);
}

function search($needle, $haystack)
{
  return strpos($haystack, $needle);
}
?>