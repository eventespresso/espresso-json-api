<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Generic_Resource_Facade_Base_Functions
 *
 * @author mnelson4
 */
abstract class EspressoAPI_Generic_Resource_Facade_Base_Functions {
	/**
	 * array for converting between object parameters the API expects and DB columns (modified DB columns, see each API's $selectFields).
	 * keys are the API-expected parameters, and values are teh DB columns
	 * eg.: array("event_id"=>'id','niceName'=>'uglyDbName')
	 * sometimes these may seem repetitious, but they're mostly handy for enumerating a specific list of allowed api params
	 * @var array 
	 */
	var $APIqueryParamsToDbColumns=array();
	/**
	 * 
	 * @var type 
	 */
	protected $validator;
	function __construct(){
		$this->validator=new EspressoAPI_Validator($this);
	}
	/**
	 * returns the list of fields on teh current model that are required in a response
	 * and should eb acceptable for querying on
	 * @return type 
	 */
	function getRequiredFields(){
		return $this->requiredFields;
	}
	
	protected function convertApiParamToDBColumn($apiParam){
		$apiParamParts=explode(".",$apiParam,2);
		if(count($apiParamParts)!=2){
			throw new EspressoAPI_BadRequestException(__("Illegal get parameter passed!:","event_espresso").$apiParam);
		}else if($apiParamParts[0]==$this->modelName && array_key_exists($apiParamParts[1], $this->APIqueryParamsToDbColumns)){
			return $this->APIqueryParamsToDbColumns[$apiParamParts[1]];
		}elseif(count($apiParamParts)==2 && array_key_exists($apiParamParts[0],$this->relatedModels)){
			$otherFacade=EspressoAPI_ClassLoader::load($this->relatedModels[$apiParamParts[0]]['modelNamePlural'],'Resource');
			$columnName=$otherFacade->convertApiParamToDBColumn($apiParamParts[1]);
			return $columnName;
		//}elseif(count($apiParamParts)==1){//th
		//	return $this->APIqueryParamsToDbColumns[$apiParam];
		}else{
			throw new EspressoAPI_BadRequestException(__("Illegal get parameter passed!:","event_espresso").$apiParam);
		}
	}
	
	/**
	 * takes the api param value and produces a db value for using in a mysql WHERE clause.
	 * also takes an option $mappingFromApiToDbColumn and $key, which, if value is 
	 * a key in the array, convert the db value into the associated value in $mappingFromApiToDbColumn
	 * @param type $valueInput
	 * @param type $mappingFromApiToDbColumn eg array('true'=>'Y','false'=>'N')
	 * @param type $apiKey
	 * @throws EspressoAPI_BadRequestException 
	 */
	protected function constructSimpleValueInWhereClause($valueInput,$mappingFromApiToDbColumn=null,$apiKey=null){
		if(isset($mappingFromApiToDbColumn)){
			if(array_key_exists($valueInput,$mappingFromApiToDbColumn)){
				$valueInput=$mappingFromApiToDbColumn[$valueInput];
			}else{
				$validInputs=implode(",",array_keys($mappingFromApiToDbColumn));
				throw new EspressoAPI_BadRequestException(__("The key/value pair you specified in your query is invalid:","event_espresso").$apiKey."/".$valueInput.__(". Valid inputs would be :","event_espresso").$validInputs);
			}
		}
		if(is_numeric($valueInput) || in_array($valueInput,array('true','false'))){
			return $valueInput;
		}else{
			return "'$valueInput'";
		}

	}
	
		/**
	 *gets the API Facade classes for each related model and puts in an array with keys like the following:
	 * array('Event'=>array('modelName'=>'Event','modelNamePlural'=>'Events','hasMany'=>true,'class'=>EspressoAPI_events_Resource),
	 *		'Datetime'=>...)
	 * @return array as described above 
	 */
	protected function getFullRelatedModels(){
		$relatedModels=array();
		foreach($this->relatedModels as $modelName=>$relatedModel){
			$relatedModels[$modelName]['modelName']=$modelName;
			$relatedModels[$modelName]['modelNamePlural']=$relatedModel['modelNamePlural'];
			$relatedModels[$modelName]['hasMany']=$relatedModel['hasMany'];
			$relatedModels[$modelName]['class']=EspressoAPI_ClassLoader::load($relatedModel['modelNamePlural'],'Resource');
		}
		return $relatedModels;
	}
	
	
}