<?php

/*
 *
 *** PocketMine-MP Crash Archive ***
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

define("ARCHIVE_ROOT", realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

if(php_sapi_name() !== "cli"){
	exit();
}

require_once("src/config.php");
require_once("src/Database.php");

$db = new Database();

if($argv[1] === "delete"){
	$reportId = (int) $argv[2];
	$db->runQuery("DELETE FROM crash_report WHERE id = $reportId;");
	unlink("reports/". sha1($reportId . SECRET_SALT) . ".log");
	echo "Report $reportId deleted!\n";
}