<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Validator
 *
 * @author mnelson4
 */
class EspressoAPI_Validator {
	/**
	 * the resource (model) this validator validates
	 * @var type 
	 */
	var $resource;
	
	function __construct($resource){
		$this->resource=$resource;
	}
	
	function validate($models,$single=true){
		if($single){
			return 	$models=$this->forceResponseIntoFormat($models,
		     array($this->resource->modelName=>$this->getRequiredFullResponse()));	
		}else{
			return 	$models=$this->forceResponseIntoFormat($models,
		     array($this->resource->modelNamePlural=>array($this->getRequiredFullResponse())));	
		}
	
	}
	
	/**
	 * ensures that the response is in the format specified.	 * 
	 * @param response $format eg, array("body"=>array("events"=>array(array("id","name")))). this would require the array key "body" to be set in topmost array. 
	 * It would then allow 0 or more numeric keys. 
	 * Within the value pointed to by each numeric key, it will require it to be an array with keys "id" and "name'.
	 * @param array $response eg, array("body"=>array("events"=>array(array("id"=>1,"name"=>"party132"),array("id"=>2,"name"=>"grad"))))
	 * @return array just passes the response on if there were no errors thrown
	 * @throws Exception if the response is not in the specified format
	 */
	private function forceResponseIntoFormat($response,$format){
		$filteredResponse=array();
		foreach($format as $key=>$value){
			if(is_numeric($key)){				
				if(is_array($value) || is_object($value)){
						//we're probably iterating through a list of things like events,
						//so if there's an subelement in teh response, force it into teh correct format too, otherwise continue
					foreach($response as $responseSubElement){
						$filteredResponse[]=$this->forceResponseIntoFormat($responseSubElement,$value);
					}	
					}else{//if the value is just a string and the key is numeric, require it be a key in the response
						if(!array_key_exists($value,$response) /*&& $response[$value]!=='' && $response[$value]!==0*/ ){//
							$filteredResponse[$value]='';
							if(WP_DEBUG)
								throw new Exception(__("Response in wrong Event Espresso Format! Expected value: ","event_espresso").$value.__(" but it wasnt set in ","event_espresso").print_r($response,true));
							else 
								throw new Exception(__("Response in wrong format. For more information please turn on WP_DEBUG in wp-config","event_espresso"));
								
						}else{
							$filteredResponse[$value]=$response[$value];
						}
					} 
				}else{//it's a string key, require it in the response
				if(!isset($response[$key])){
					//$filteredResponse[$key]='';
					
					if(WP_DEBUG)
						throw new Exception(__("Response in wrong Event Espresso Format! Expected value: ","event_espresso").print_r($value,true).__(" but it wasnt set in ","event_espresso").print_r($response,true));
					else 
						throw new Exception(__("Response in wrong format. For more information please turn on WP_DEBUG in wp-config","event_espresso"));
						
				}else{ 
					$filteredResponse[$key]=$this->forceResponseIntoFormat ($response[$key],$value);
				}
			}
		}
		return $filteredResponse;
	}
	
	/**
	 * gets the required full response from the requiredFields of the current
	 * model and related ones. For example, returns
	 * array("events"=>array(
	 *		array("id","name","description"...
	 *			"Datetimes"=>array(
	 *				array("id","event_start",...)
	 *			)
	 *		)
	 * )
	 * @return type 
	 */
	private function getRequiredFullResponse(){
		$requiredFullResponse=$this->resource->getRequiredFields();
		foreach($this->resource->relatedModels as $modelName=>$modelInfo){
			//only require the related model's attributes as part of the response 
			//if the current user should eb able to see them anyway
			if(EspressoAPI_Permissions_Wrapper::current_user_can('get',$modelInfo['modelNamePlural'])){
				$modelClass=  EspressoAPI_ClassLoader::load($modelInfo['modelNamePlural'],'Resource');
				if($modelInfo['hasMany']){
					$requiredFullResponse[$modelInfo['modelNamePlural']][]=$modelClass->getRequiredFields();
				}else{
					$requiredFullResponse[$modelName]=$modelClass->getRequiredFields();
				}
			}
		}
		return $requiredFullResponse;
	}
}

?>
