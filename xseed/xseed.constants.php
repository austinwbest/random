<?php

/*
----------------------------------
 ------  Created: 042321   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('HISTORY', 15); //-- GRAB THE NEWEST AMOUNT OF ITEMS ADDED TO QBT EACH RUN, SINCE IT RUNS ON A CRON THIS DOES NOT NEED TO BE A LOT

define('JACKET_URL', 'http://localhost:9117');
define('JACKETT_API', '');

define('QBT_URL', 'http://localhost:8080');
define('QBT_USER', 'admin');
define('QBT_PASS', '');
define('QBT_TAG', 'xseed');

$xseedIndexers = array('beyond-hd', 
                       'hdbitsapi',
                       'hdtorrents'
					  );
?>
