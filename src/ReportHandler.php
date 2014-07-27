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

class ReportHandler{
	private $report;
	private $isAPI;

	public function __construct(CrashReport $report, $isAPI = false){
		$this->report = $report;
		$this->isAPI = $isAPI;
	}

	public function showDetails(TemplateStack $stack){
		$tpl = new Template("crashDetails", $this->isAPI);

		$warnings = "";

		//General information
		$tpl->addTransform("pocketmine_version", $this->report->getVersionString());
		$tpl->addTransform("api_version", $this->report->getApiVersion());

		$tpl->addTransform("php_version", $this->report->getPHPVersion());
		$tpl->addTransform("os", $this->report->getOS());
		$tpl->addTransform("uname", $this->report->getUname());

		$tpl->addTransform("caused_by_plugin", $this->report->isCausedByPlugin() === true ? "<b>YES</b>" : "Not directly");
		if($this->report->isCausedByPlugin()){
			$warnings .= '<div class="alert alert-warning" style="margin-top:10px;margin-bottom:0px;"><strong>Warning!</strong> This crash was caused by a plugin. Please contact the original plugin author.</div>';
		}
		$tpl->addTransform("date", date("l d/m/Y H:i:s", $this->report->getDate()));

		//Error information
		switch($this->report->getReportType()){
			case CrashReport::TYPE_OPERAND_TYPE:
				$errorTitle = "Operand type error";
				break;
			case CrashReport::TYPE_CLASS_VISIBILITY:
				$errorTitle = "Class visibility error";
				break;
			case CrashReport::TYPE_INVALID_ARGUMENT:
				$errorTitle = "Invalid argument error";
				break;
			case CrashReport::TYPE_OUT_OF_MEMORY:
				$errorTitle = "Out of memory error";
				break;
			case CrashReport::TYPE_UNDEFINED_CALL:
				$errorTitle = "Undefined call error";
				break;
			case CrashReport::TYPE_CLASS_NOT_FOUND:
				$errorTitle = "Class not found error";
				break;
			case CrashReport::TYPE_UNKNOWN:
				$errorTitle = "<b>Unknown error</b>";
				break;
			default:
				$errorTitle = clean(ucfirst($this->report->getReportType())) . " error";
		}
		$tpl->addTransform("error_title", $errorTitle);
		$tpl->addTransform("error_level", clean($this->report->getType()));
		$tpl->addTransform("error_file", clean($this->report->getFile()));
		$tpl->addTransform("error_line", clean($this->report->getLine()));
		$tpl->addTransform("error_message", clean($this->report->getMessage()));


		$tpl->addTransform("warnings", $warnings);

		$trace = "";
		foreach($this->report->getTrace() as $line){
			$trace .= $line . PHP_EOL;
		}
		$tpl->addTransform("trace", $trace);

		$code = "";
		foreach($this->report->getCode() as $number => $line){
			$code .= "[".intval($number)."] ".htmlentities($line). PHP_EOL;
		}
		$tpl->addTransform("code", $code);

		if($this->isAPI === false){
			$plugins = "";
			foreach($this->report->getPlugins() as $data){
				if($data->enabled){
					$enabled = "<b>Enabled</b>";
				}else{
					$enabled = "Disabled";
				}
	
				if($data->website !== NULL){
					$name = '<a href="'.$data->website.'" rel="nofollow" target="_blank">'.$data->name.'</a>';
				}else{
					$name = $data->name;
				}

				$plugins .= "<tr>";
				$plugins .= "<td>{$name}</td><td>{$data->version}</td><td>{$enabled}</td><td>".implode(", ", $data->authors)."</td>";
				$plugins .= "</tr>";
			}
			$tpl->addTransform("plugins", $plugins);
		} else {
			$tpl->addTransform("plugins", json_encode($this->report->getPlugins()));
		}
		if($isAPI === false){
			$settings = "";
		} else {
			$settings = array();
		}
		foreach(explode("\n", $this->report->getProperties()) as $line){
			$line = trim($line);
			if($line === "" or $line{0} === "#"){
				continue;
			}
			$line = explode("=", $line);
			$key = array_shift($line);
			$value = implode("=", $line);
			if($this->isAPI === false){
				$settings .= "<tr><td>{$key}</td><td>{$value}</td></tr>";	
			} else {
				$settings[$key] = $value;
			}
			
		}

		$yml = yaml_parse($this->report->getSettings());
		if($yml !== false){
			$all = [];

			$iterator = function(&$data, $pointer, callable $callable, $prefix = ""){
				foreach($pointer as $key => $value){
					$index = $prefix . $key;
					if(is_array($value)){
						$callable($data, $value, $callable, $index . ".");
					}else{
						$data[$index] = $value;
					}
				}
			};

			$iterator($all, $yml, $iterator);

			foreach($all as $key => $value){
				if(is_bool($value)){
					if($value){
						$value = "true";
					}else{
						$value = "false";
					}
				}
				if($this->isAPI === false){
					$settings .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
				} else {
					$settings[$key] = $value; 
				}
			}

		}
		if($this->isAPI === true){
			$settings = json_encode($settings);
		}
		$tpl->addTransform("settings", $settings);

		$stack[] = $tpl;
		return $tpl;
	}
}
