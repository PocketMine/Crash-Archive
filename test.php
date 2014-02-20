<?php

/**
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

include_once("src/CrashReport.php");

$reportSTR = @file_get_contents($argv[1]);

$report = new CrashReport($reportSTR);
if(!$report->isValid()){
	var_dump($report);
	echo "Invalid report\n";
	exit(1);
}

$methods = array(
	"getVersionString",
	"getReportType",
	"isCausedByPlugin",
	"getDate",
	"getType",
	"getMessage",
	"getFile",
	"getLine",
);

foreach($methods as $method){
	echo "Report->$method() => " . $report->$method() . "\n";
}

exit(0);