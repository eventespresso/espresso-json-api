<?php
/**
 * EspressoAPI
 *
 * RESTful API for Even tEspresso
 *
 * @ package			Espresso REST API
 * @ author				Mike Nelson
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link					{@link http://www.eventespresso.com}
 * @ since		 		3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * Generic API Facade class
 *
 * @package			Espresso REST API
 * @subpackage	includes/APIFacades/Espresso_Generic_API_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
abstract class EspressoAPI_Generic_API_Facade{
	/**
	 * array for converting between object parameters the API expects and DB columns (modified DB columns, see each API's $selectFields).
	 * keys are the API-expected parameters, and values are teh DB columns
	 * eg.: array("event_id"=>'id','niceName'=>'uglyDbName')
	 * sometimes these may seem repetitious, but they're mostly handy for enumerating a specific list of allowed api params
	 * @var array 
	 */
	var $APIqueryParamsToDbColumns=array();
	
	protected function convertApiParamToDBColumn($apiParam){
		$apiParamParts=explode(".",$apiParam,2);
		
		if(count($apiParamParts)==2 && $this->relatedModels[$apiParamParts[0]] && strpos($apiParam,"/")===FALSE){
			//it's in teh form 'Table.column', and there' sno funny business like a '/' in it!
			$otherFacade=EspressoAPI_ClassLoader::load($this->relatedModels[$apiParamParts[0]]['modelNamePlural'],"Facade");
			$columnName=$otherFacade->convertApiParamToDBColumn($apiParamParts[1]);
			return $columnName;//if the otherfacade returned a result like "attendees.id", don't prepend the current model's name onto it
		}elseif(count($apiParamParts==1)){
			return $this->APIqueryParamsToDbColumns[$apiParam];
		}else{
			throw new EspressoAPI_BadRequestException(__("Illegal get parameter passed!:","event_espresso").$apiParam);
		}
	}
	/**
	 * converts the queyr parameters (usually $_GET parameters) into an SQL string.
	 * eg: id=4&date__lt=2012-04-02 08:04:45&title__like=%party%&graduation_year__IN=1997,1998,1999
	 * becomes id=4 AND date< '2012-04-02 08:04:4' AND title LIKE '%party%' AND graduation_year IN (1997,1998,1999)
	 * @param array $keyOpVals result of $this->seperateIntoKeyOperatorValue
	 * @return string mySQL content for a WHERE clause
	 */
	protected function constructSQLWhereSubclauses($keyOpVals){
		$whereSqlArray=array();
		foreach($keyOpVals as $key=>$OpAndVal){
			$whereSubclause=$this->constructSQLWhereSubclause($key,$OpAndVal['operator'],$OpAndVal['value']);
			if(!empty($whereSubclause)){
				$whereSqlArray[]=$whereSqlArray;
			}
		}
		return $whereSqlArray;
	}
	/**
	 * for seperating querystrings like 'id=123&Datetime.event_start__lt=2012-03-04%2012:23:34' into an array like
	 * array('Event.id'=>array('operator'=>'equals','123'),
	 *		'Datetime.event_start'=>array('operator'=>'lt','value'=>'2012-03-04%2012:23:34'))
	 * @param array $queryParameters basically $_GET parameters
	 * @return array as described above 
	 */
	protected function seperateIntoKeyOperatorValues($queryParameters){
		$keyOperatorValues=array();
		foreach($queryParameters as $keyAndOp=>$value){
			list($columnName,$operator)=$this->getSQLOperatorAndCorrectAPIParam($keyAndOp);
			//$columnName=$this->convertApiParamToDBColumn($columnName);
			$keyOperatorValues[$columnName]=array('operator'=>$operator,'value'=>$value);
		}
		return $keyOperatorValues;
	}
	/**
	 * makes each "foo='bar'" in a MYSQL WHERE clause like '...WHERE foo=bar AND uncle LIKE '%bob%' AND date < '2012-04-02 23:22:02'
	 * @param string $columnName like 'Event.name'
	 * @param string $operator like '<', '=', 'LIKE',
	 * @param string $value like 23, 'foobar', '2012-03-03 12:23:34'
	 * @return string of full where Subcluae like "foo='bar'", no 'AND's 
	 */
	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		//take an api param like "Datetime.is_primary" or "id"
		$apiParamParts=explode(".",$columnName,2);
		
		//determine which model its referring to ("Datetime" in teh first case, in the second case it's $this->modelName)
		if(count($apiParamParts)==1){//if it's an api param with no ".", like "name" (as opposed to "Event.name")
			$modelName=$this->modelName;
			$columnName=$apiParamParts[0];
		}else{//it's an api param like "Datetime.start_time"
			$modelName=$apiParamParts[0];
			$columnName=$apiParamParts[1];
		}
		//construct sqlSubWhereclause, or get the related model (to whom the attribute belongs)to do it.
		//eg
		if($this->modelName==$modelName){
			$dbColumn=$this->convertAPIParamToDBColumn($columnName);
			$formattedValue=$this->constructValueInWhereClause($operator,$value);
			return "$dbColumn $operator $formattedValue";
		}else{//this should be handled by the model to whom this attribute belongs, in case there's associated special logic
			$otherFacade=EspressoAPI_ClassLoader::load($this->relatedModels[$modelName]['modelNamePlural'],"Facade");
			return $otherFacade->constructSQLWhereSubclause($columnName,$operator,$value);
		}
		
	}
	/**
	 *gets the API Facade classes for each related model and puts in an array with keys like the following:
	 * array('Event'=>array('modelName'=>'Event','modelNamePlural'=>'Events','hasMany'=>true,'class'=>EspressoAPI_events_API),
	 *		'Datetime'=>...)
	 * @return array as described above 
	 */
	protected function getFullRelatedModels(){
		$relatedModels=array();
		foreach($this->relatedModels as $modelName=>$relatedModel){
			$relatedModels[$modelName]['modelName']=$modelName;
			$relatedModels[$modelName]['modelNamePlural']=$relatedModel['modelNamePlural'];
			$relatedModels[$modelName]['hasMany']=$relatedModel['hasMany'];
			$relatedModels[$modelName]['class']=EspressoAPI_ClassLoader::load($relatedModel['modelNamePlural'],"Facade");
		}
		return $relatedModels;
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
	protected function getRequiredFullResponse(){
		$requiredFullResponse=$this->getRequiredFields();
		/*$relatedModelClasses=$this->getFullRelatedModels();
		
		foreach($relatedModelClasses as $modelNamePlural=>$modelClass){
			$requiredFullResponse[$modelNamePlural]=$modelClass->getRequiredFields();
		}*/
		foreach($this->relatedModels as $modelName=>$modelInfo){
			$modelClass=  EspressoAPI_ClassLoader::load($modelInfo['modelNamePlural'],'Facade');
			if($modelInfo['hasMany']){
				$requiredFullResponse[$modelInfo['modelNamePlural']][]=$modelClass->getRequiredFields();
			}else{
				$requiredFullResponse[$modelName]=$modelClass->getRequiredFields();
			}
		}
		return $this->tweakRequiredFullResponse($requiredFullResponse);;
	}
	
	/**
	 * returns the list of fields on teh current model that are required in a response
	 * and should eb acceptable for querying on
	 * @return type 
	 */
	function getRequiredFields(){
		return $this->requiredFields;
	}
	/**
	 * method for overriding in cases where the full response that's required needs to be tweaked.
	 * @param type $rquiredFullResponse
	 * @return type 
	 */
	protected function tweakRequiredFullResponse($requiredFullResponse){
		return $requiredFullResponse;
	}
	protected function constructValueInWhereClause($operator,$valueInput){
		if($operator=='IN'){
			$values=explode(",",$valueInput);
			$valuesProcessed=array();
			foreach($values as $value){
				$valuesProcessed[]=$this->constructSimpleValueInWhereClause($value);
			}
			$value=implode(",",$valuesProcessed);
			return "($value)";
		}else{
			return $this->constructSimpleValueInWhereClause($valueInput);
		}
	}
	private function constructSimpleValueInWhereClause($valueInput){
		if($valueInput=='true'){
			return 'true';
		}elseif($valueInput=='false'){
			return 'false';
		}elseif(is_numeric($valueInput)){
			return $valueInput;
		}else{
			return "'$valueInput'";
		}
	}
	protected function getSQLOperatorAndCorrectAPIParam($apiParam){
		list($key,$operatorRepresentation)=$this->seperateQueryParamAndOperator($apiParam);
		switch($operatorRepresentation){
			case 'lt':
				$operator= "<";
				break;
			case 'lte':
				$operator= "<=";
				break;
			case 'gt':
				$operator= ">";
				break;
			case 'gte':
				$operator= ">";
				break;
			case 'like':
			case 'LIKE':
				$operator= "LIKE";
				break;
			case 'in':
			case 'IN':
				$operator= "IN";
				break;
			case 'equals':
				$operator="=";
				break;
			default:
				throw new EspressoAPI_BadRequestException($operatorRepresentation.__(" is not a valid api operator. try one of these: lt,lte,gt,gte,like,in","event_espresso"));
		}
		return array($key,$operator);
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
	protected function forceResponseIntoFormat($response,$format){
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
	 * Gets events from database according ot query parameters by calling the concrete child classes' _getEvents function
	 * @param array $queryParameters
	 * @return array  
	 */
     function getMany($queryParameters){		 
		 if (!empty($queryParameters)){
			$keyOpVals=$this->seperateIntoKeyOperatorValues($queryParameters);
			$whereSubclauses=$this->constructSQLWhereSubclauses($keyOpVals);
		}
		else{
			$keyOpVals=array();
			$whereSubclauses=$this->constructSQLWhereSubclauses($keyOpVals);//should still be called in case it needs to add special where subclauses
		}
		if(empty($whereSubclauses))
			$sqlWhere='';
		else
			$sqlWhere = "WHERE " . implode(" AND ",$whereSubclauses);
		global $wpdb;
		$relatedModelInfos=$this->getFullRelatedModels();
		$relatedModelFields=array();
		foreach($relatedModelInfos as $modelInfo){
			$relatedModelFields[$modelInfo['modelNamePlural']]=$modelInfo['class']->selectFields;
		}
		$sqlSelect=implode(",",$relatedModelFields);
		$sqlQuery=$this->getManyConstructQuery($sqlSelect,$sqlWhere);
		if(isset($_GET['debug']))echo "generic api facade 301: sql:$sqlQuery";
		$results = $wpdb->get_results($sqlQuery, ARRAY_A);
		//var_dump($results);
		$processedResults=$this->initiateProcessSqlResults($results,$keyOpVals);
		$topLevelModels=$this->extractMyUniqueModelsFromSqlResults($processedResults);
		$completeResults=array();
		foreach($topLevelModels as $key=>$model){
			foreach($relatedModelInfos as $relatedModelInfo){
				$modelClass=$relatedModelInfo['class'];
				if($relatedModelInfo['hasMany']){
					$model[$relatedModelInfo['modelNamePlural']]=$modelClass->extractMyUniqueModelsFromSqlResults($processedResults,$this->modelName.'.id',$model['id']);
				}else{
					$model[$relatedModelInfo['modelName']]=$modelClass->extractMyUniqueModelFromSqlResults($processedResults,$this->modelName.'.id',$model['id']);
				}
			}
			$completeResults[$key]=$model;
		}
		$models= array($this->modelNamePlural => $completeResults);
		 $models=$this->forceResponseIntoFormat($models,
		     array($this->modelNamePlural=>array($this->getRequiredFullResponse())));
		 return $models;
     }
	 
	 /**
	  * return first result from  extractMyUniqueModelsfromSqlResults 
	  */
	 protected function extractMyUniqueModelFromSqlResults($sqlResults,$idKey=null,$idValue=null){
		 $modelRepresentations=$this->extractMyUniqueModelsFromSqlResults($sqlResults, $idKey, $idValue);
		 return array_shift($modelRepresentations); 
	 }
	/**
	  * gets a specific event acording to its id
	  * @param int $id
	  * @return array 
	  */
	 function getOne($id){
		$queryParam=array('id'=>$id);
		$fullResults=$this->getMany($queryParam);
		$model= array($this->modelName=>array_shift($fullResults[$this->modelNamePlural]));
		return $model;
	 }
	/**
	 * calls 'processSqlResults' on each related model and the current one
	 * @param array $rows results of wpdb->get_results, with lots of inner joins and renaming of tables in normal format
	 * @param array $queryParameters like those 
	 * @return same results as before, but with certain results filtered out as implied by queryParameters not taken
	 * into account in the SQL
	 */
	protected function initiateProcessSqlResults($rows,$keyOpVals){
		$rows=$this->processSqlResults($rows,$keyOpVals);
		foreach($this->relatedModels as $relatedModel=>$relatedModelInfo){
			$otherFacade=EspressoAPI_ClassLoader::load($relatedModelInfo['modelNamePlural'],"Facade");
			$rows=$otherFacade->processSqlResults($rows,$keyOpVals);
		}
		return $rows;
	}
	
	/**
	 * To be overriden by subclasses that need todo more processing of the rows
	 * before putting into API result format.
	 * An example is filtering out results by query parameters that couldn't be take into account by simple SQL.
	 * For example, filtering out by Datetime.tickets_left in 3.1, because there is no MYSQL column called 'tickets_left',
	 * (although there is in 3.2).
	 * Or adding fields that are calculated from other fields (eg, calculated_price)
	 * @param array $rows like resutls of wpdb->get_results
	 * @param array $keyOpVals basically like results of $this->seperateIntoKeyOperatorValues
	 * @return array original $rows, with some fields added or removed
	 */
	protected function processSqlResults($rows,$keyOpVals){
		return $rows;
	}
	
	/**
	 * seperatesan input parameter like 'Event.id__lt' or 'id__like' into array('Event.id','lt') and array('id','like'), respectively
	 * @param string $apiParam, basically a GET parameter
	 * @return array with 2 values: frst being the queryParam, the second beign the operator 
	 */
	protected function seperateQueryParamAndOperator($apiParam){
		$matches=array();
		preg_match("~^(.*)__(.*)~",$apiParam,$matches);
		if($matches){
			return array($matches[1],$matches[2]);
		}else{
			return array($apiParam,"equals");
		}
	}

	/**
	 * for evaluating if a {op} b is true.
	 * @param string/int $operand1, usually the result of a database query
	 * @param string $operatorRepresentation, one of 'lt','lte','gt','gte','like','in','equals'
	 * @param string $operand2 querystringValue. eg: '2','monkey','thing1,thing2,thing3','%mysql%like%value'
	 * @return boolean
	 * @throws EspressoAPI_MethodNotImplementedException
	 * @throws EspressoAPI_BadRequestException 
	 */
	protected function evaluate($operand1,$operatorRepresentation,$operand2){
		if(is_int($operand2))
			$operand2=intval($operand2);
		switch($operatorRepresentation){
			case '<':
				return $operand1<$operand2;
			case '<=':
				return $operand1<=$operand2;
			case '>':
				return $operand1>$operand2;
			case '>=':
				return $operand1>=$operand2;
			case 'LIKE':
				//create regex by converting % to .* and _ to .
				//also remove anything in the string that could be considered regex
				$regexFromOperand2=preg_quote($operand2,"~");//using ~ as the regex delimeter
				$regexFromOperand2=str_replace(array('%','_'),array('.*','.'),$regexFromOperand2);
				
				$regexFromOperand2='~^'.$regexFromOperand2.'$~';
				$matches=array();
				preg_match($regexFromOperand2,strval($operand1),$matches);
				if(empty($matches))
					return false;
				else
					return true;
			case 'IN':
				$operand2Values=explode(",",$operand2);
				return (in_array($operand1,$operand2Values));
			case '=':
				return $operand1==$operand2;
			default:
					throw new EspressoAPI_BadRequestException($operatorRepresentation.__(" is not a valid api operator. try one of these: lt,lte,gt,gte,like,in","event_espresso"));
		}
	}
	
	/**
	 * takes the results acquired from a DB selection, and extracts
	 * each instance of this model, and compiles into a nice array like
	 * array(12=>("id"=>12,"name"=>"mike party","description"=>"all your base"...)
	 * Also, if we're going to just be finding models that relate
	 * to a specific foreign_key on any table in the query, we can specify
	 * to only return those models using the $idKey and $idValue,
	 * for example if you have a bunch of results from a query like 
	 * "select * FROM events INNER JOIn attendees", and you just want
	 * all the attendees for event with id 13, then you'd call this as follows:
	 * $attendeesForEvent13=parseSQLREsultsForMyDate($results,'Event.id',13);
	 * @param array $sqlResults
	 * @param string/int $idKey
	 * @param string/int $idValue 
	 * @return array compatible with the required reutnr type for this model
	 */
	protected function extractMyUniqueModelsFromSqlResults($sqlResults,$idKey=null,$idValue=null){
		$filteredResults=array();
		foreach($sqlResults as $sqlResult){
			if((!empty($idKey) && !empty($idValue) && $sqlResult[$idKey]!= $idValue))
				continue;
			$formatedResult=$this->_extractMyUniqueModelsFromSqlResults($sqlResult);
			if(array_key_exists('id',$formatedResult) && $formatedResult['id']!==NULL)
				$filteredResults[$formatedResult['id']]=$formatedResult;
		}
		return $filteredResults;
	}	
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	abstract protected function _extractMyUniqueModelsFromSqlResults($sqlRow);
}