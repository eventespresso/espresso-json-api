<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Temp_Id_Holder
 * class for storing and retrieiving temporary IDs used in updates or creates.
 * uses a singleton pattern instead of globals, but users of this class shouldn't
 * even know it's a singleton, because the only functions available to them are 
 * set and get.
 *
 * @author mnelson4
 */
class EspressoAPI_Temp_Id_Holder {
	private static $instance;
	private $tempIds=array();
	
	protected function __construct(){
		
	}
	/**
	 * sets the temorary id specified by name (like 'temp-123')
	 * to be the value $value. Afterwards, this id may be retrieved using EspressoAPI_Temp_Id_Holder::get($name)
	 * @param string $name
	 * @param whatever $value 
	 */
	public static function set($name,$value){
		$instance=self::getInstance();
		$instance->tempIds[$name]=$value;
	}
	/**
	 * gets the temporary id specified by its name (like 'temp-my-event')
	 * @param string $name
	 * @return value of the temporary id, or null if it can't be found 
	 */
	public static function get($name){
		$instance=self::getInstance();
		if(!in_array($name,$instance->tempIds)){
			return null;
		}
		return $instance->tempIds[$name];
	}
	/*
	 * gets singleton which has all the temporary ids
	 */
	private static function getInstance(){
		if(!self::$instance){
			self::$instance=new EspressoAPI_Temp_Id_Holder();
		}
		return self::$instance;
	}
}

?>
