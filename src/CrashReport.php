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

require_once("VersionString.php");

class CrashReport{
	const TYPE_GENERIC = "generic";
	const TYPE_OUT_OF_MEMORY = "out_of_memory";
	const TYPE_UNDEFINED_CALL = "undefined_call";
	const TYPE_CLASS_VISIBILITY = "class_visibility";
	const TYPE_INVALID_ARGUMENT = "invalid_argument";
	const TYPE_CLASS_NOT_FOUND = "class_not_found";
	const TYPE_UNKNOWN = "unknown";

	private $report;
	private $reportLines;
	private $lineOffset;
	private $valid;
	
	private $reportType;
	private $reportDate;
	private $causedByPlugin;
	
	private $errorType;
	private $errorFile;
	private $errorLine;
	private $errorMessage;
	private $version;
	
	
	public function __construct($reportStr){
		$this->report = $reportStr;
		$this->trimHead();
		$this->reportLines = array_map("trim", explode("\n", $this->report));
		$this->parse();
	}
	
	public function isValid(){
		return $this->valid === true;
	}
	
	public function getLine(){
		return $this->errorLine;
	}
	
	public function getMessage(){
		return $this->errorMessage;
	}
	
	public function getType(){
		return $this->errorType;
	}
	
	public function getFile(){
		return $this->errorFile;
	}
	
	public function getDate(){
		return $this->reportDate;
	}
	
	public function getVersion(){
		return $this->version;
	}
	
	public function getVersionString(){
		return (string) $this->version;
	}
	
	public function getReportType(){
		return $this->reportType;
	}
	
	public function isCausedByPlugin(){
		return $this->causedByPlugin === true;
	}
	
	protected function trimHead(){
		$this->report = trim($this->report, "\r\n\t` ");
	}
	
	protected function parse(){
		$this->lineOffset = 0;
		$this->reportType = self::TYPE_GENERIC;
		$this->causedByPlugin = false;
		$this->valid = true;
		$this->parseDate();
		$this->parseHeader();
		$this->classifyMessage();
		$this->parseVersion();
	}
	
	private function parseDate(){
		$line = ltrim($this->reportLines[$this->lineOffset++], "# ");
		if(substr($line, 0, 24) !== "PocketMine-MP Error Dump"){
			$this->valid = false;
		}else{
			$this->reportDate = date_create_from_format("D M j H:i:s T Y", substr($line, 25))->getTimestamp();
		}
	}
	
	private function parseHeader(){
		$i = 1;
		$status = 0;
		while(isset($this->reportLines[$i]) and substr($line = $this->reportLines[$i], 0, 21) !== "PocketMine-MP version"){
			++$i;
			++$this->lineOffset;
			if($line === ""){
				$status = 0;
				continue;
			}
			
			switch($status){
				case 0: //Status selection mode
					$section = substr($line, 0, (int) strpos($line, ":") + (int) strpos($line, "."));
					if($section === "Error"){
						$status = 1;
					}elseif($section === "Code"){
						$status = 2;
					}elseif($section === "Backtrace"){
						$status = 3;
					}elseif($section === "THIS ERROR WAS CAUSED BY A PLUGIN"){
						$this->causedByPlugin = true;
					}
					break;
					
				case 1: //Error info
					if(preg_match("#^'([a-z]{1,})' => (.*),$#", $line, $matches) > 0){
						$matches[2] = trim($matches[2], "',");
						switch($matches[1]){
							case "type":
								$this->errorType = $matches[2];
								break;
							case "message":
								$this->errorMessage = $matches[2];
								break;
							case "line":
								$this->errorLine = (int) $matches[2];
								break;
							case "file":
								$file = str_replace(array("\\\\", "\\"), "/", $matches[2]);
								if(($index = strrpos($file, "src/")) !== false){
									$this->errorFile = substr($file, $index);
								}elseif(($index = strrpos($file, "plugins/")) !== false){
									$this->errorFile = substr($file, $index);
									$this->causedByPlugin = true;
								}else{
									$this->errorFile = "NO_FILE";									
								}
								if(strpos($this->errorFile, "eval()") !== false){
									$this->causedByPlugin = true;
								}
								break;
						}
					}
					break;
				case 2: //Code
					break;
				case 3: //Backtrace
					break;
			}
		}
	}
	
	private function classifyMessage(){
		if(!isset($this->errorMessage)){
			$this->valid = false;
			return;
		}
		
		if(substr($this->errorMessage, 0, 22) === "Allowed memory size of"){
			$this->reportType = self::TYPE_OUT_OF_MEMORY;
		}elseif(substr($this->errorMessage, 0, 17) === "Call to undefined"
			or substr($this->errorMessage, 0, 16) === "Call to a member"){
			$this->reportType = self::TYPE_UNDEFINED_CALL;
		}elseif(substr($this->errorMessage, 0, 22) === "Call to private method"
			or substr($this->errorMessage, 0, 24) === "Call to protected method"
			or substr($this->errorMessage, 0, 30) === "Cannot access private property"
			or substr($this->errorMessage, 0, 32) === "Cannot access protected property"){
			$this->reportType = self::TYPE_CLASS_VISIBILITY;
		}elseif(substr($this->errorMessage, -10) === " not found"){
			$this->reportType = self::TYPE_CLASS_NOT_FOUND;
		}elseif(substr($this->errorMessage, 0, 9) === "Argument "){
			$this->reportType = self::TYPE_INVALID_ARGUMENT;
			$line = str_replace(array("\\\\", "\\"), "/", $this->errorMessage);
			if(($index = strrpos($line, "src/")) !== false){
				$this->errorMessage = substr($line, 0, strpos($line, "called in ") + 10) . substr($line, $index);
			}elseif(($index = strrpos($line, "plugins/")) !== false){
				$this->errorMessage = substr($line, 0, strpos($line, "called in ") + 10) . substr($line, $index);
				$this->causedByPlugin = true;
			}
		}elseif($this->errorType !== "E_ERROR" and $this->errorType !== "E_USER_ERROR" and $this->errorType !== "1"){
			$this->reportType = self::TYPE_UNKNOWN; //Catch those PHP core crashes
		}
	}
	
	private function parseVersion(){
		if(preg_match("/^PocketMine-MP version: ([A-Za-z0-9_\\.\\-]{1,})/", $this->reportLines[$this->lineOffset++], $matches) > 0){
			$this->version = new VersionString($matches[1]);
		}else{
			$this->valid = false;
		}
	}
}