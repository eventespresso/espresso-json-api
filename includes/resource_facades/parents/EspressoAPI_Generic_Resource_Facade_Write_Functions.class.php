<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Generic_Resource_Facade_Create_Functions
 *
 * @author mnelson4
 */
require_once(dirname(__FILE__).'/EspressoAPI_Generic_Resource_Facade_Read_Functions.class.php');
abstract class EspressoAPI_Generic_Resource_Facade_Write_Functions extends EspressoAPI_Generic_Resource_Facade_Read_Functions{
	function createMany($input){
		//validate input
		$models=$this->validator->validate($input,array('single'=>false,'requireRelated'=>false));
		throw new EspressoAPI_SpecialException("it worked!");
		//create 
	
	}
	
	public function updateOne($input){
		
	}
}
