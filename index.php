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

require_once("src/config.php");
require_once("src/TemplateStack.php");

ob_start("ob_gzhandler", 0, PHP_OUTPUT_HANDLER_CLEANABLE);

$route = explode("&", ltrim($_SERVER["REQUEST_URI"], "/?"));

$page = new TemplateStack();
$page[] = new Template("header");

if(count($route) === 0 or $route[0] === "" or $route[0] === "home"){
	$page[] = new Template("home");
}

switch(array_shift($route)){
	default:
		$page[] = new Template("404");
		break;
}

$page[] = new Template("footer");
echo $page->get();
