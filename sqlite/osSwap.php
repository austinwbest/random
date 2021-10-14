<?PHP
/*
----------------------------------
 ------  Created: 010121   ------
 ------  Austin Best	   ------
----------------------------------
*/
// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$hash = ($_POST['hash']) ? $_POST['hash'] : md5($_SERVER['SERVER_ADDR'] . microtime());
$dbDir = 'conversions/';

?>
<head>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>
</head>
<?PHP
if (!$_POST && !$_FILES['databaseFile']['size'])
{
	?>
	<div class="row">
		<div class="col-sm-3">
			<form action="index.php" method="post" enctype="multipart/form-data">
				<table class="table table-striped table-bordered">
					<tr>
						<td>Select database file to convert</td>
					</tr>
					<tr>
						<td><input type="file" name="databaseFile" id="databaseFile"></td>
					</tr>
					<tr>
						<td align="center"><input type="submit" value="Upload" name="submit"></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
	<?PHP
}
else
{
	if ($_POST['dbFile'])
	{
		$dbFile = $_POST['dbFile'];
	}
	else
	{
		$database 	= $_FILES['databaseFile'];
		$size		= $database['size'];
		$dbFile 	= $dbDir . $hash . $database['name'];
		
		@unlink($dbFile);

		if (!move_uploaded_file($database['tmp_name'], $dbFile))
		{
			exit('Error: Uploading database failed.');
		}

		if (!file_exists($dbFile))
		{
			exit('Error: No database file uploaded.');
		}
	}

	try
	{
		$db = new PDO('sqlite:'. $dbFile, '', '', array(PDO::ATTR_PERSISTENT => false));
	}
	catch (PDOException $e)
	{
		echo 'Failed to open "'. $dbFile .'"<br>';
		echo 'Error: '. $e->getMessage();
		exit();
	}

	$sql = "SELECT Value
			FROM Config
			WHERE Key = 'recyclebin'";
	$query = $db->prepare($sql);
	$query->execute();
	$recyclebin = $query->fetch(PDO::FETCH_ASSOC);

	$sql = "SELECT *
			FROM RootFolders";
	$query = $db->prepare($sql);
	$query->execute();
	while ($row = $query->fetch(PDO::FETCH_ASSOC))
	{
		$folders[$row['Id']] = $row['Path'];
	}
	
	if ($_POST['changePaths'])
	{
		foreach ($_POST as $key => $val)
		{
			if (strpos($key, 'folder_') !== false)
			{
				$folderId = trim(end(explode('_', $key)));
				//-- ROOT FOLDER
				if (is_numeric($folderId))
				{
					$currentPath 	= $folders[$folderId];
					$newPath 		= $val;
					$valid 			= (strpos($newPath, ':') !== false || strpos($newPath, '\\\\') !== false) ? checkWindowsPath($newPath) : checkLinuxPath($newPath);

					if (!$valid)
					{
						$error .= 'New root folder path "'. $newPath .'" is not valid.<br>';
					}
					else
					{
						$newRoots[] = array('current' => $currentPath, 'new' => $newPath);
					}
				}
				if ($key == 'folder_recycleBin')
				{
					$currentPath 	= $recyclebin['Value'];
					$newPath 		= $val;
					$valid 			= (strpos($newPath, ':') !== false || strpos($newPath, '\\\\') !== false) ? checkWindowsPath($newPath) : checkLinuxPath($newPath);

					if (!$valid)
					{
						$error .= 'New recycle bin path "'. $newPath .'" is not valid.<br>';
					}
					else
					{
						$newRecycle = $newPath;
					}
				}
			}
		}

		if ($error)
		{
			echo $error;
			exit();
		}

		//-- RECYCLE BIN
		if ($newRecycle)
		{
			$sql = "UPDATE Config
					SET Value = :recycleBin
					WHERE Key = 'recyclebin'";
			$query = $db->prepare($sql);
			$query->bindValue(':recycleBin', $newRecycle);
			$query->execute();
		}

		//-- ROOT FOLDERS
		if ($newRoots)
		{
			$sql = "SELECT Id, Path
					FROM Movies";
			$select = $db->prepare($sql);
			$select->execute();
			while ($row = $select->fetch(PDO::FETCH_ASSOC))
			{
				unset($newPath);
				foreach ($newRoots as $root)
				{
					if (strpos($row['Path'], $root['current']) !== false)
					{
						$newPath = str_replace($root['current'], $root['new'], $row['Path']);

						$sql = "UPDATE Movies
								SET Path = :newPath
								WHERE Id = ". $row['Id'];
						$update = $db->prepare($sql);
						$update->bindValue(':newPath', $newPath);
						$update->execute();

						break;
					}
				}
			}
		}

		echo 'You can download the updated database <a href="'. $dbFile .'">here</a>. It will be purged shortly.';
	}
	else
	{
		?>
		<div class="row">
			<div class="col-sm-6">
				<table class="table table-striped table-bordered">
					<tr>
						<td>
							Connected to database file <b><?= end(explode('/', $dbFile)) ?></b> (<?= friendly_filesize($size) ?>)!<br>
							Found <?= count($folders) ?> root folders...<br>
						</td>
					</tr>
					<tr>
						<td>
							Use the table below to map the current path to a new path. The new path needs to be a <b>correct and valid path</b> of where your library is moving to.<br>
							<form action="index.php" method="post" enctype="multipart/form-data">
								<table class="table table-striped table-bordered">
									<tr>
										<td>Type</td>
										<td>Current</td>
										<td>Path</td>
										<td>&nbsp;</td>
										<td>New</td>
										<td>Path</td>
									</tr>
									<?PHP if ($recyclebin['Value']) { ?>
									<tr>
										<td>Recycle Bin</td>
										<td><?= ((strpos($recyclebin['Value'], ':') !== false || strpos($recyclebin['Value'], '\\\\') !== false) ? 'Windows' : 'Linux') ?></td>
										<td><?= $recyclebin['Value'] ?></td>
										<td><b>&raquo;</b></td>
										<td><?= ((strpos($recyclebin['Value'], ':') === false || strpos($recyclebin['Value'], '\\\\') === false) ? 'Linux' : 'Windows') ?></td>
										<td><input name="folder_recycleBin" type="text" placeholder="valid path" size="20"></td>
									</tr>
									<?PHP } ?>
									<?PHP
									foreach ($folders as $id => $path)
									{
										?>
										<tr>
											<td>Root Folder</td>
											<td><?= ((strpos($path, ':') !== false || strpos($path, '\\\\') !== false) ? 'Windows' : 'Linux') ?></td>
											<td><?= $path ?></td>
											<td><b>&raquo;</b></td>
											<td><?= ((strpos($path, ':') === false || strpos($path, '\\\\') === false) ? 'Linux' : 'Windows') ?></td>
											<td><input name="folder_<?= $id ?>" type="text" placeholder="valid path" size="20"></td>
										</tr>
										<?PHP
									}
									?>
									<tr>
										<td colspan="6">
											* New paths need to end with a slash. Examples: \\path\to\media\ or D:\path\to\media\ or /path/to/media/<br>
											** If you use remote path maps, you'll need to fix those when you start using this database<br>
											*** Please make sure you dont set the recycle bin path to a root folder path
										</td>
									</tr>
									<tr>
										<td colspan="6" align="center"><input type="submit" value="Change Paths" name="submit"></td>
									</tr>
								</table>
								<input type="hidden" name="changePaths" value="1">
								<input type="hidden" name="hash" value="<?= $hash ?>">
								<input type="hidden" name="dbFile" value="<?= $dbFile ?>">
							</form>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?PHP
	}
}

$db = null;

function friendly_filesize($bytes, $decimals = 2)
{
    $size = array('B', 'kB', 'MB', 'GB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function checkWindowsPath($path)
{
	if (substr($path, 0, 2) == '\\\\')
	{
		return true;
	}
	if (ctype_alpha(substr($path, 0, 1)) && substr($path, 1, 2) == ':\\')
	{
		return true;
	}

	return false;
}

function checkLinuxPath($path)
{
	if (substr($path, 0, 1) !== '/' || substr($path, -1) !== '/')
	{
		return false;
	}

	return true;
}
?>