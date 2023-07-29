<?php

/*
----------------------------------
 ------  Created: 092219   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- HOME_DIR is set in index.php
if (!file_exists(HOME_DIR . 'secrets.json')) {
	exit('Please rename secrets.json.example to secrets.json');
}

$secrets = json_decode(file_get_contents(HOME_DIR . 'secrets.json'), true);

//-- TESTING
/*
	Do nothing but PRINT_RUN, LOG_RUN and/or NOTIFY_RUN; will not pause, remove, recycle, tag, etc
*/
define('DRY_RUN_TAGS', false); //-- false: Check for items in qbt to tag
define('DRY_RUN_REMOVAL', false); //-- false: Check for items in qbt to remove
define('DRY_RUN_RECYCLE', false); //-- false: Check for items in the recycle bin to remove
define('DRY_RUN_ERRORS', false); //-- false: Check for all announcers broken
define('DRY_RUN_ORPHAN_QBT', false); //-- false: Check qbt items against files on disk
define('DRY_RUN_ORPHAN_DISK', false); //-- false: Check files on disk against qbt items

//-- RESULTS
define('PRINT_RUN', true); //-- Echo the run results as it is running
define('LOG_RUN', true); //-- Log everything as it is running, requires LOG_PATH
define('NOTIFY_RUN', true); //-- Send a notification of results when complete, requires NOTIFIARR_APIKEY

//-- NOTIFIARR
define('NOTIFIARR_APIKEY', $secrets['NOTIFIARR_APIKEY']); //-- Leave blank in secrets.json for no notification about the run
define('DISCORD_CHANNEL', $secrets['DISCORD_CHANNEL']); //-- Leave blank in secrets.json for no notification about the run

//-- QBT
define('QBT_URL', 'http://localhost:8080/');
define('QBT_USER', 'admin');
define('QBT_PASS', '');

//-- DELETE SATISFIED
define('DELETE_RULE_MATCHES', false); //-- Use this if you want to perm delete and not recycle (while checking torrent rules from $indexers below)
define('RECYCLE_RULE_MATCHES', true); //-- Perm delete or move things to a recycle bin, requires RECYCLE_PATH (while checking torrent rules from $indexers below)
define('RECYCLE_HOLD', 14); //-- How many days to hold in the recycle bin before perm delete
define('RECYCLE_DISK_ORPHANS', true);
define('DELETE_DISK_ORPHANS', false);

//-- TAG SATISFIED
define('TAG_SATISFIED', false); //-- Use this instead of DELETE/RECYCLE
define('PAUSE_SATISFIED', false);
define('SATISFIED_TAG', 'satisfied');

//-- PATHS
define('RECYCLE_DRIVE', 'T:\\'); //-- Can leave blank if RECYCLE is false
define('RECYCLE_PATH', 'T:\\Automation_Recycle\\'); //-- Can leave blank if RECYCLE is false
define('SEED_DRIVE', 'S:\\'); //-- Used to determine drive space, required if KEEP_UNTIL_SPACE is used
define('SEED_PATH', 'S:\\completed\\'); //-- Used to determine orphans
define('LOG_PATH', HOME_DIR . 'logs\\'); //-- Leave blank if no logs are needed, create this directory if they are being used

/*
	Torrents that have all announce failures
*/
define('TAG_ISSUES', true);
define('PAUSE_ISSUES', false);
define('ISSUES_TAG', 'issue');

/*
	Tag orphans in qbt but missing on disk
*/
define('TAG_ORPHANS', true);
define('ORPHAN_TAG', 'orphaned');

//-- IGNORE THINGS
define('IGNORE_PAUSED', true); //-- Do not do anything with a paused torrent
define('IGNORE_CATEGORIES', '["re-seed", "xseed", "misc", "upload-direct"]'); //-- Do not do anything with a torrent in one of these categories
define('IGNORE_TAGS', '[]'); //-- Do not do anything with a torrent that contains one of these tags

//-- MISC
define('ACTIVE_SEED', 14); //-- What to consider an active seed, activity in the last n days
define('MIN_DISK_SPACE', 250); //-- How many GiB should be available for KEEP_UNTIL_SPACE

/*
	ANNOUNCERS: how to match a torrent to a set of rules
	TAG: what does this site use in QBT, will add the tag if it is missing
	MIN_LENGTH: site minimum to seed without H&R
	MAX_LENGTH: how long should this script allow a seed to stay (SATISFY_RATIO & KEEP_ACTIVE can override this)
	MIN_RATIO: site minimum for ratio before removal without H&R
	SATISFY_RATIO: seed should be deleted when MIN_RATIO is met regardless of MAX_LENGTH, KEEP_ACTIVE & KEEP_UNTIL_SPACE
	KEEP_ACTIVE: keep seeding even when MAX_LENGTH and MIN_RATIO are satisfied unless SATISFY_RATIO is true (if ACTIVE_SEED is provided)
	KEEP_UNTIL_SPACE: keep seeding even when MAX_LENGTH, MIN_RATIO & KEEP_ACTIVE are satisfied unless SATISFY_RATIO is true (until disk space is needed if SEED_PATH and MIN_DISK_SPACE are provided)
*/
$indexers 	= [
				[
				//-- iptorrents.com
				'ANNOUNCERS'		=> ['routing.bgp.technology', 'ssl.empirehost.me', 'localhost.stackoverflow.tech'],
				'TAG' 				=> 'IPT',
				'MIN_LENGTH'		=> 14,
				'MAX_LENGTH'		=> 16,
				'MIN_RATIO' 		=> 1,
				'SATISFY_RATIO'		=> true,
				'KEEP_ACTIVE'		=> false,
				'KEEP_UNTIL_SPACE'	=> false
				]
			];

//-- QBT STUFF
define('STATE_PAUSED', 'pausedUP');
define('STATE_DOWNLOADING', 'downloading');