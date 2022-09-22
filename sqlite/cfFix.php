<?php
/*
----------------------------------
 ------  Created: 092222   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$dbFile = 'radarr.db';
$db = null;

//-- CONNECT TO THE SQLITE DATABASE
try {
    $db = new PDO('sqlite:'. $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Failed to open "'. $dbFile .'"<br>';
    echo 'Error: '. $e->getMessage();
    exit();
}

//-- GET ALL CURRENT CUSTOM FORMAT IDS
try {
    $sql = "SELECT Id
            FROM CustomFormats";
    $query = $db->query($sql);
} catch (PDOException $e) {
    echo 'Failed query '. $sql .'<br>';
    echo 'Error: '. $e->getMessage();
    exit();
}

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $cfIds[] = $customFormat['Id'];
}
$cfIds = array_values(array_unique(array_filter($cfIds)));

//-- GET ALL CURRENT PROFILES
try {
    $sql = "SELECT *
            FROM Profiles";
    $query = $db->query($sql);
} catch (PDOException $e) {
    echo 'Failed query '. $sql .'<br>';
    echo 'Error: '. $e->getMessage();
    exit();
}

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $profiles[] = $row;
}

//-- LOOP PROFILS TO FIND DELETED CUSTOM FORMAT IDS
foreach ($profiles as $profileIndex => $profile) {
    if ($profile['FormatItems']) {
        $formatItems    = json_decode($profile['FormatItems'], true);
        $brokenProfile  = false;

        foreach ($formatItems as $formatIndex => $formatItem) {
            //-- HUNT DOWN THE BROKEN SHIT
            if (!in_array($formatItem['format'], $cfIds)) {
                $brokenProfile = true;
                echo 'Profile ' . $profile['Name'] .' has a link to missing CF ' . $formatItem['format'] .'<br>';
                //-- REMOVE THIS BROKEN SHIT
                unset($formatItems[$formatIndex]);
            }
        }

        if ($brokenProfile) {
            $formatItems = json_encode(array_values($formatItems));

            //-- PUSH IT BACK TO THE DATABASE
            try {
                $sql = "UPDATE Profiles
                        SET FormatItems = '". $formatItems ."'
                        WHERE id = " . $profile['Id'];
                $query = $db->query($sql);
                echo 'Fixed ' . $profile['Name'] . '<br><br>';
            } catch (PDOException $e) {
                echo 'Failed query '. $sql .'<br>';
                echo 'Error: '. $e->getMessage() .'<br><br>';
            }
        }
    }
}

?>