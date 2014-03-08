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

require_once("Template.php");

class TemplateStack extends SplDoublyLinkedList{

	public function get(){
		$result = "";
		foreach($this as $template){
			$result .= $template->get();
		}
		return $result;
	}

}