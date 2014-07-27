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
	return preg_replace('#[^A-Za-z0-9_\\-\\.\\,\\;\\:/\\#\\(\\)\\\\ ]#', "", $str);
}


require_once("src/config.php");
require_once("src/TemplateStack.php");

ob_start("ob_gzhandler", 0, PHP_OUTPUT_HANDLER_CLEANABLE);

$path = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : $_SERVER["PATH_INFO"];

$isAPI = substr($path, -4) === "/api" ? true : false;
if($isAPI){
	$path = substr($path, 0, -4);
	header("Content-Type: application/json");
}

$path = explode("/", ltrim($path, "/"));
$main = array_shift($path);

$page = new TemplateStack();
$page[] = new Template("header", $isAPI);

switch($main === "" ? "home" : $main){
	case "home":
		$page[] = new Template("home", $isAPI);
		break;
	case "view":
		if(!isset($path[0])){
			$error = new Template("error", $isAPI);
			$error->addTransform("message", "Please specify a report");
			$error->addTransform("url", "/home");
			$page[] = $error;
			break;
		}
		$reportId = (int) $path[0];
		$error = new Template("error", $isAPI);
		$error->addTransform("message", "Report not found");
		$error->addTransform("url", "/home");
		$page[] = $error;

		break;
	case "submit":
		if(isset($_POST["report"])){
			require_once("src/CrashReport.php");
			if(isset($_POST["reportPaste"]) and trim($_POST["reportPaste"]) !== ""){
				$report = @new CrashReport($_POST["reportPaste"]);
			}elseif(isset($_FILES["reportFile"]) and $_FILES["reportFile"]["size"] > 0){
				$report = @new CrashReport(@file_get_contents($_FILES["reportFile"]["tmp_name"]));
			}else{
				$error = new Template("error", $isAPI);
				$error->addTransform("message", "Please add your crash report file");
				$error->addTransform("url", "/submit");
				$page[] = $error;
			}
			if(isset($report) and $report instanceof CrashReport){
				if(!$report->isValid()){
					$error = new Template("error", $isAPI);
					$error->addTransform("message", "This crash report is not valid");
					$error->addTransform("url", "/submit");
					$page[] = $error;
				}else{
					require_once("src/ReportHandler.php");
					$handler = new ReportHandler($report, $isAPI);
					$tpl = $handler->showDetails($page);
					$reportId = mt_rand(100, 65535); //placeholder :P
					//header("Location: /view/$reportId" . ($isAPI ? "/api":""));
					$tpl->addTransform("crash_id", $reportId);
					$tpl->addTransform("email_hash", md5($_POST["email"]));
					$tpl->addTransform("name", clean($_POST["name"]));
					$tpl->addTransform("attached_issue", "None");

				}
			}
		}else{
			$page[] = new Template("submit", $isAPI);
		}
		break;
	default:
		$page[] = new Template("404", $isAPI);
		break;
}


$page[] = new Template("footer", $isAPI);
echo $page->get();
