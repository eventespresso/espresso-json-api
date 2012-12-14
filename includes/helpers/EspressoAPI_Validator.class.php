<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Class for validating responses and input arrays. The validator operates on 
 * data in array form (ie, not in xml or json itself. it's a layer abstracted away from either of those).
 * Validation is clearly highly dependent on WHAT is being validated (ie, 
 * are we validating a response which should contain a list of events, a response for
 * a single registration, or the input to update a single attendee?)
 * So at first it made sense for these functions to be part of the Generic
 * Resource Facade, but it got factored out because that class was gettting unruly.
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
	
	function validateQueryParameters($keyOpVals){
		//get related models
		$relatedModels=$this->resource->getFullRelatedModels();
		
		foreach($keyOpVals as $keyOpVal){
			list($modelName,$fieldName)=explode(".",$keyOpVal['key']);
			$fieldInfo=null;
			if($this->resource->modelName==$modelName){
				$fieldInfo=$this->resource->getRequiredFieldInfo($fieldName);
			}else{
				foreach($relatedModels as $relatedModel){
					if($relatedModel['modelName']==$modelName){
						$fieldInfo=$relatedModel['class']->getRequiredFieldInfo($fieldName);
						break;
					}
				}
			}if(empty($fieldInfo) || empty($fieldInfo['type'])){
				throw new EspressoAPI_BadRequestException(sprintf(__("Query parameter '%s' not a valid parameter of resource '%s'"),$fieldName,$modelName));
			}
			elseif(!$this->valueIs($keyOpVal['value'],$fieldInfo['type'],$fieldInfo['allowedEnumValues'])){
				if($fieldInfo['type']=='enum'){
					$enumInfoString=sprintf(__("Allowed values are :%s"),implode(",",$fieldInfo['allowedEnumValues']));
				}else{
					$enumInfoString='';
				}
				throw new EspressoAPI_BadRequestException(sprintf(
								__("Param '%s' with value '%s' is not of allowed type '%s'. %s","event_espresso"),$keyOpVal['key'],$keyOpVal['value'],$fieldInfo['type'],$enumInfoString));
				
			}
			
		}
		return true;
	}
	function validate($models,$options=array()){
		if(array_key_exists('single',$options) && $options['single']==true){
			$single=true;
		}else{
			$single=false;
		}
		if(array_key_exists('requireRelated',$options) && $options['requireRelated']==false){
			$requireRelated=false;
		}else{
			$requireRelated=true;
		}
		if($single){
			return 	$models=$this->forceResponseIntoFormat($models,
		     array($this->resource->modelName=>$this->getRequiredFullResponse()),$requireRelated);	
		}else{
			return 	$models=$this->forceResponseIntoFormat($models,
		     array($this->resource->modelNamePlural=>array($this->getRequiredFullResponse())),$requireRelated);	
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
	private function forceResponseIntoFormat($response,$format,$requireRelated=true){
		$filteredResponse=array();
		foreach($format as $key=>$value){
			if(is_numeric($key)){				
				if(is_array($value)){
					if(array_key_exists('var', $value)){//this should be a variable
						$variableInfo=$value;
						//validate the key of the response
						if(!array_key_exists($variableInfo['var'],$response)){
							if(WP_DEBUG){
								throw new Exception(sprintf(__("Response in wrong Event Espresso Format! Expected value: %s but it wasnt set in %s","event_espresso"),$variableInfo['var'],print_r($response,true)));
							}
							else{ 
								throw new Exception(__("Response in wrong format. For more information please turn on WP_DEBUG in wp-config","event_espresso"));
							}

						}
						//validate the value of the response
						if(array_key_exists('type',$variableInfo)){
							if($this->valueIs($response[$variableInfo['var']], $variableInfo['type'],  array_key_exists('allowedEnumValues', $variableInfo)?$variableInfo['allowedEnumValues']:null)){
								$filteredResponse[$value['var']]=$this->castToType($response[$variableInfo['var']], $variableInfo['type']);
							}else{
								throw new EspressoAPI_BadRequestException(sprintf(
										__("Param %s with value %s is not of allowed type %s.","event_espresso"),$variableInfo['var'],$response[$variableInfo['var']],$variableInfo['type']));
							}
							
						}else{
							throw new EspressoAPI_SpecialException(__("Event Espresso Internal bug. Misconfiguered Resource variables. Please contact Event Espresso.","event_espresso"));
						}
					}else{
						
					
						//we're probably iterating through a list of things like events,
						//so if there's an subelement in teh response, force it into teh correct format too, otherwise continue
						foreach($response as $responseSubElement){
							$filteredResponse[]=$this->forceResponseIntoFormat($responseSubElement,$value);
						}	
					}
				}else{//if the value is just a string and the key is numeric, this is an error. it shouldn't happen
					throw new EspressoAPI_SpecialException(sprintf(__e("Event Espresso Internal bug. We have a badly configuered validation array at %s",'event_espresso'),$value));
					 
				}
			}else{//it's a string key, require it in the response
				if(!isset($response[$key])){
					//if it's a related model, and we're not requiring them, ignore it and carry on
					if($this->resource->isARelatedModel($key) && !$requireRelated){
						continue;
					}
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
	 * validates that $value is of the specified type
	 * @param type $value
	 * @param type $type
	 * @return boolean 
	 */
	function valueIs($value,$type,$allowedEnumValues=array()){
		if(is_array($value)){
			$csvValues=array($value);
		}else{
			$csvValues=explode(",",$value);
		}
		foreach($csvValues as $value){
			if($value==null)
				return true;
			switch($type){
				case 'int':
					if(ctype_digit($value) || is_int($value)){
						continue;
					}
					else{
						return false;
					}
				case 'float':
					if(is_numeric($value)){
						continue;
					}else{
						return false;
					}
				case 'string':
					if(is_string($value)){
						continue;
					}else{
						return false;
					}
				case 'bool':
					if(is_bool($value) || intval($value)===0 || intval($value)===1){
						continue;
					}else{
						return false;
					}
				case 'datetime':
					if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$value)){
						continue;
					}else{
						return false;
					}
				case 'enum':
					if(in_array($value, $allowedEnumValues)){
						continue;
					}else{
						return false;
					}
				case 'array':
					if(is_array($value)){
						continue;
					}else{
						return false;
					}
				default:
					throw new EspressoAPI_SpecialException(sprintf(__("Internal Event Espresso API Error while validating data. Type %s not permitted","event_espresso"),$type));
			}
		}
		return true;
	}
	
	/**
	 * validates that $value is of the specified type
	 * @param type $value
	 * @param type $type
	 * @return boolean 
	 */
	function castToType($value,$type){
		switch($type){
			case 'int':
				return intval($value);	
			case 'float':
				return floatval($value);				
			case 'string':
				return $value;
			case 'bool':
				return $value;
			case 'datetime':
				return $value;
			case 'enum':
				return $value;
			case 'array':
				return $value;
			default:
				throw new EspressoAPI_SpecialException(sprintf(__("Internal Event Espresso API Error while validating data. Type %s not permitted","event_espresso"),$type));
		}
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
