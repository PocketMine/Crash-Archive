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
	const TYPE_OPERAND_TYPE = "operand_type";
	const TYPE_OUT_OF_MEMORY = "out_of_memory";
	const TYPE_UNDEFINED_CALL = "undefined_call";
	const TYPE_CLASS_VISIBILITY = "class_visibility";
	const TYPE_INVALID_ARGUMENT = "invalid_argument";
	const TYPE_CLASS_NOT_FOUND = "class_not_found";
	const TYPE_UNKNOWN = "unknown";

	private $report;
	/** @var stdClass */
	private $data;
	private $valid;
	
	private $reportType;
	private $reportDate;
	private $causedByPlugin;
	private $causingPlugin = null;
	
	private $errorType;
	private $errorFile;
	private $errorLine;
	private $errorMessage;
	/** @var VersionString */
	private $version;
	private $apiVersion;
	
	
	public function __construct($reportStr){
		$this->report = $reportStr;
		$this->trimHead();
		$this->parse();
	}

	public function getEncoded(){
		return "===BEGIN CRASH DUMP===" . PHP_EOL . base64_encode(zlib_encode(json_encode($this->data, JSON_UNESCAPED_SLASHES), ZLIB_ENCODING_DEFLATE, 9)) . PHP_EOL . "===END CRASH DUMP===";
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

	public function getCode(){
		return $this->data->code;
	}

	public function getProperties(){
		return $this->data->{"server.properties"};
	}

	public function getSettings(){
		return $this->data->{"pocketmine.yml"};
	}

	public function getPlugins(){
		return $this->data->plugins;
	}

	public function getTrace(){
		return $this->data->trace;
	}

	public function getPHPVersion(){
		return $this->data->general->php;
	}

	public function getOS(){
		return $this->data->general->os;
	}

	public function getUname(){
		return $this->data->general->uname;
	}

	public function getApiVersion(){
		return $this->apiVersion;
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

	public function getCausingPlugin(){
		return $this->causingPlugin;
	}
	
	protected function trimHead(){
		$this->report = trim($this->report, "\r\n\t` ");
		$this->report = substr($this->report, (int) strpos($this->report, "===BEGIN CRASH DUMP==="));
	}
	
	protected function parse(){
		$this->reportType = self::TYPE_GENERIC;
		$this->causedByPlugin = false;
		$this->valid = true;
		$data = @json_decode(zlib_decode(base64_decode(str_replace(["===BEGIN CRASH DUMP===", "===END CRASH DUMP==="], "", $this->report)), false));
		if(!is_object($data)){
			$this->valid = false;
			return;
		}
		$this->data = $data;

		$this->parseDate();
		$this->parseError();
		$this->classifyMessage();
		$this->parseVersion();
	}
	
	private function parseDate(){
		$this->reportDate = isset($this->data->time) ? $this->data->time : time();
	}
	
	private function parseError(){
		if(isset($this->data->plugin) and $this->data->plugin !== false){
			$this->causedByPlugin = true;
			if($this->data->plugin !== true){
				$this->causingPlugin = clean($this->data->plugin);
			}
		}

		$this->errorType = $this->data->error->type;
		$this->errorMessage = $this->data->error->message;
		$this->errorLine = $this->data->error->line;
		$this->errorFile = $this->data->error->file;
	}
	
	private function classifyMessage(){
		if(!isset($this->errorMessage)){
			$this->valid = false;
			return;
		}

		if(substr($this->errorMessage, 0, 25) === "Unsupported operand types"){
			$this->reportType = self::TYPE_OPERAND_TYPE;
		}elseif(substr($this->errorMessage, 0, 22) === "Allowed memory size of"){
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
			$line = str_replace(["\\\\", "\\"], "/", $this->errorMessage);
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
		if(isset($this->data->general->version)){
			$this->version = new VersionString($this->data->general->version.($this->data->general->build > 0 ? "-".$this->data->general->build:""));
			$this->apiVersion = $this->data->general->api;
		}else{
			$this->valid = false;
		}
	}
}