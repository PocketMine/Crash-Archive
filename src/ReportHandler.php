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

	public function __construct(CrashReport $report){
		$this->report = $report;
	}

	public function showDetails(TemplateStack $stack){
		$tpl = new Template("crashDetails");

		//General information
		$tpl->addTransform("pocketmine_version", $this->report->getVersionString());
		$tpl->addTransform("caused_by_plugin", $this->report->isCausedByPlugin() === true ? "<b>YES</b>" : "Not directly");
		$tpl->addTransform("date", date("l d/m/Y H:i:s", $this->report->getDate()));

		//Error information
		switch($this->report->getReportType()){
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
			case CrashReport::TYPE_UNKNOWN:
				$errorTitle = "<b>Unknown error</b>";
				break;
			default:
				$errorTitle = clean($this->report->getReportType()) . " error";
		}
		$tpl->addTransform("error_title", $errorTitle);
		$tpl->addTransform("error_level", clean($this->report->getType()));
		$tpl->addTransform("error_file", clean($this->report->getFile()));
		$tpl->addTransform("error_line", clean($this->report->getLine()));
		$tpl->addTransform("error_message", clean($this->report->getMessage()));

		$stack[] = $tpl;
		return $tpl;
	}
}