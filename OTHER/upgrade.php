<?php

require("config.php");
require_once(KU_ROOTDIR."inc/functions.php");
require_once(KU_ROOTDIR."inc/encryption.php");

/* Uncomment the line below to show debug information if your upgrade isn't going so well */
//$tc_db->debug = true;

if (isset($_GET['do'])) {
	if ($_GET['do'] == 'insertsql') {		
		$tc_db->Execute("CREATE TABLE `" . KU_DBPREFIX . "blotter` (
		`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
		`important` TINYINT( 1 ) NOT NULL ,
		`at` INT( 20 ) NOT NULL ,
		`message` TEXT NOT NULL ,
		INDEX ( `id` )
		) ENGINE = MYISAM ;");
		
		$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "events`");
		if (count($results) == 0) {
			$tc_db->Execute("INSERT INTO `" . KU_DBPREFIX . "events` (
			`name` ,
			`at`
			)
			VALUES (
			'pingback', '0'
			);");
		}
		
		$tc_db->Execute("ALTER TABLE `" . KU_DBPREFIX . "banlist` ADD `appealat` INT( 20 ) NOT NULL DEFAULT '0' AFTER `reason` ;");
		
		$tc_db->Execute("ALTER TABLE `" . KU_DBPREFIX . "wordfilter` CHANGE `id` `id` INT( 5 ) NOT NULL AUTO_INCREMENT");

		echo 'SQL inserted.';
	}
} else {
	echo '<a href="?do=insertsql">Insert upgrade SQL</a>';
}

?>