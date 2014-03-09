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

function clean($str){
	return preg_replace('#[^A-Za-z0-9_\-\.\,\;\:/\\#\\(\\) ]#', "", $str);
}


require_once("src/config.php");
require_once("src/TemplateStack.php");

ob_start("ob_gzhandler", 0, PHP_OUTPUT_HANDLER_CLEANABLE);

$page = new TemplateStack();
$page[] = new Template("header");


switch($_SERVER["PATH_INFO"] === "/" ? "/home" : $_SERVER["PATH_INFO"]){
	case "/home":
		$page[] = new Template("home");
		break;
	case "/submit":
		if(isset($_POST["report"])){
			if(isset($_FILES["reportFile"]) and $_FILES["reportFile"]["size"] > 0){
				require_once("src/CrashReport.php");
				$report = @new CrashReport(@file_get_contents($_FILES["reportFile"]["tmp_name"]));
				if(!$report->isValid()){
					$error = new Template("error");
					$error->addTransform("message", "This crash report is not valid");
					$error->addTransform("url", "/submit");
					$page[] = $error;
				}else{
					require_once("src/ReportHandler.php");
					$handler = new ReportHandler($report);
					$tpl = $handler->showDetails($page);
					$tpl->addTransform("crash_id", mt_rand(100, 65535)); //placeholder :P
					$tpl->addTransform("email_hash", md5($_POST["email"]));
					$tpl->addTransform("name", clean($_POST["name"]));

				}
			}else{
				$error = new Template("error");
				$error->addTransform("message", "Please add your crash report file");
				$error->addTransform("url", "/submit");
				$page[] = $error;
			}
		}else{
			$page[] = new Template("submit");
		}
		break;
	default:
		$page[] = new Template("404");
		break;
}


$page[] = new Template("footer");
echo $page->get();
