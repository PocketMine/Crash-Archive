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

class Template{
	private $html;
	private $name;
	private $isAPI;
	private $transform = array();

	public function __construct($name, $isAPI = false){
		$this->name = preg_replace("/[^A-Za-z0-9_\\-]/", "", $name);
		$this->isAPI = (bool) $isAPI;
		$this->html = (string) @file_get_contents(ARCHIVE_ROOT . "src/templates/{$this->name}.".($this->isAPI ? "json":"html"));
	}

	public function getName(){
		return $this->name;
	}

	public function setTransform(array $transform){
		$this->transform = array();
		foreach($transform as $find => $replace){
			$this->addTransform($find, $replace);
		}
	}

	public function addTransform($find, $replace){
		$this->transform['/\\{'.$find.'\\}/'] = $replace;
	}


	public function get(){
		if(count($this->transform) > 0){
			$html = $this->html;
			foreach($this->transform as $find => $replace){
				$html = preg_replace($find, $this->isAPI ? str_replace("\\", "\\\\\\", $replace) : $replace, $html);
			}
			return preg_replace('/\\{[A-Za-z_\\-]+\\}/', "", $html);
		}else{
			return preg_replace('/\\{[A-Za-z_\\-]+\\}/', "", $this->html);
		}
	}

}