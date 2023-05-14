<?php

/*
----------------------------------
 ------  Created: 020523   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('DRY_RUN', true);
define('ORPHAN_PREFIX', 'ORPHANED - ');
define('ORPHAN_TAG', 'Orphaned');

$seeds 		= ["S:\\completed\\", "S:\\DOWNLOADING\\"];
$ignoreDir 	= ["manual-add", "logs"];
$ignoreCat	= ['re-seed'];

define('QBT_URL', 'http://localhost:8080');
define('QBT_USER', 'admin');
define('QBT_PASS', '');
