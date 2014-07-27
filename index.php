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

function getNextId(){ //placeholder for database, binary search algorithm for ID
	$min = 1;
	$max = 2147483646;

	while($max >= $min){
		$mid = ($min + $max) >> 1;
		if(file_exists("reports/". sha1($mid . SECRET_SALT) . ".log")){
			$min = $mid + 1;
		}elseif($max !== $min){
			$max = $mid;
		}else{
			return $mid;
		}
	}

	return -1;
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
	case "download":
	case "view":
		if(!isset($path[0])){
			$error = new Template("error", $isAPI);
			$error->addTransform("message", "Please specify a report");
			$error->addTransform("url", "/home");
			$page[] = $error;
			break;
		}

		$reportId = (int) $path[0];

		$path = "reports/". sha1($reportId . SECRET_SALT) . ".log";
		if(!file_exists($path)){
			$error = new Template("error", $isAPI);
			$error->addTransform("message", "Report not found");
			$error->addTransform("url", "/home");
			$page[] = $error;
			break;
		}

		$data = json_decode(@file_get_contents($path), true);

		if($main === "download"){
			$download = $data["report"];
			header("Content-Type: application/octet-stream");
			header('Content-Disposition: attachment; filename="'.$reportId.'.log"');
			header("Content-Length: " . strlen($download));
			echo $download;
			exit();
		}

		require_once("src/CrashReport.php");
		$report = @new CrashReport($data["report"]);
		if(!$report->isValid()){
			$error = new Template("error", $isAPI);
			$error->addTransform("message", "This crash report is not valid");
			$error->addTransform("url", "/home");
			$page[] = $error;
		}else{
			require_once("src/ReportHandler.php");
			$handler = new ReportHandler($report, $isAPI);
			$tpl = $handler->showDetails($page);
			$tpl->addTransform("crash_id", $reportId);
			$tpl->addTransform("email_hash", md5($data["email"]));
			$tpl->addTransform("name", $data["name"]);
			$tpl->addTransform("attached_issue", "None");
		}

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
					$encoded = $report->getEncoded();
					//$hash = $report->getDate() . $encoded . microtime(true);
					$reportId = getNextId(); //placeholder :P
					$data = [
						"report" => $encoded,
						"reportId" => $reportId,
						"email" => $_POST["email"],
						"name" => clean($_POST["name"]),
						"attachedIssue" => false
					];

					@file_put_contents("reports/". sha1($reportId . SECRET_SALT) . ".log", json_encode($data));
					header("Location: /view/$reportId" . ($isAPI ? "/api":""));
					$tpl->addTransform("crash_id", $reportId);
					$tpl->addTransform("email_hash", md5($_POST["email"]));
					$tpl->addTransform("name", clean($_POST["name"]));
					$tpl->addTransform("attached_issue", "None");


				}
			}else{
				$error = new Template("error", $isAPI);
				$error->addTransform("message", "This crash report is not valid");
				$error->addTransform("url", "/submit");
				$page[] = $error;
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
