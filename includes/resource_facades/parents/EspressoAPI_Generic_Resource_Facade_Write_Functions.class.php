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
		foreach($models[$this->modelNamePlural] as $model){
			$this->createOrUpdateOne(array($this->modelName=>$model));
		}
		throw new EspressoAPI_SpecialException("it worked!");
		//create 
	
	}
	
	function createOrUpdateOne($model){
		
	}
	
	/**
	 * given API input like array('Events'=>array(0=>array('id'=>1,'name'=>'party1'...) 
	 * or array('Event'=>array('id'=>1,'name'=>'party1'...)
	 * produces array(0=>array('id'=>1,'name'='party1'...)
	 * @param array $apiInput (se above)
	 * @return see above 
	 */
	protected function extractModelsFromApiInput($apiInput){
		if(array_key_exists($this->modelName,$apiInput)){
			$models=array($apiInput[$this->modelName]);
		}elseif(array_key_exists($this->modelNamePlural,$apiInput)){
			$models=$apiInput[$this->modelNamePlural];
		}
		return $models;
	}
}
