<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Generic_Resource_Facade_Update_Functions
 *
 * @author mnelson4
 */
require_once(dirname(__FILE__).'/EspressoAPI_Generic_Resource_Facade_Base_Functions.class.php');
abstract class EspressoAPI_Generic_Resource_Facade_Read_Functions extends EspressoAPI_Generic_Resource_Facade_Base_Functions{


	
	/**
	 * converts the queyr parameters (usually $_GET parameters) into an SQL string.
	 * eg: id=4&date__lt=2012-04-02 08:04:45&title__like=%party%&graduation_year__IN=1997,1998,1999
	 * becomes id=4 AND date< '2012-04-02 08:04:4' AND title LIKE '%party%' AND graduation_year IN (1997,1998,1999)
	 * @param array $keyOpVals result of $this->seperateIntoKeyOperatorValue
	 * @return string mySQL content for a WHERE clause
	 */
	protected function constructSQLWhereSubclauses($keyOpVals){
		$whereSqlArray=array();
		foreach($keyOpVals as $keyOpAndVal){
			$whereSubclause=$this->constructSQLWhereSubclause($keyOpAndVal['key'],$keyOpAndVal['operator'],$keyOpAndVal['value']);
			if(!empty($whereSubclause)){
				$whereSqlArray[]=$whereSubclause;
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
			list($modelAndApiParam,$operator)=$this->getSQLOperatorAndCorrectAPIParam($keyAndOp);
			//if they only passed the api param name, then add the model for clarification
			if(strpos($modelAndApiParam,".")===FALSE){
				$modelAndApiParam=$this->modelName.".$modelAndApiParam";
			}
			$keyOperatorValues[]=array('key'=>$modelAndApiParam, 'operator'=>$operator,'value'=>$value);
		}
		return $keyOperatorValues;
	}
	/**
	 * makes each "foo='bar'" in a MYSQL WHERE clause like '...WHERE foo=bar AND uncle LIKE '%bob%' AND date < '2012-04-02 23:22:02'
	 * @param string $apiParam like 'Event.name' (API param, not SQL column yet. Mapping from one to the other is part of what happens here)
	 * @param string $operator like '<', '=', 'LIKE', (SQL already, no longer API operators like __lt or __like)
	 * @param string $value like 23, 'foobar', '2012-03-03 12:23:34' (API values, not SQL ones yet. Mapping happens here)
	 * @return string of full where Subcluae like "foo='bar'", no 'AND's 
	 */
	protected function constructSQLWhereSubclause($apiParam,$operator,$value){
		//take an api param like "Datetime.is_primary" or "id"
		$apiParamParts=explode(".",$apiParam,2);
		
		//determine which model its referring to ("Datetime" in teh first case, in the second case it's $this->modelName)
		if(count($apiParamParts)==1){//if it's an api param with no ".", like "name" (as opposed to "Event.name")
			$modelName=$this->modelName;
			$columnName=$apiParam;
		}else{//it's an api param like "StartEnd.start_time"
			$modelName=$apiParamParts[0];
			$columnName=$apiParamParts[1];
		}
		//construct sqlSubWhereclause, or get the related model (to whom the attribute belongs)to do it.
		if($this->modelName==$modelName){
			if(in_array("{$modelName}.{$columnName}",$this->calculatedColumnsToFilterOn))
					return null;
			$dbColumn=$this->convertAPIParamToDBColumn($apiParam);
			$formattedValue=$this->constructValueInWhereClause($operator,$value);
			return "$dbColumn $operator $formattedValue";
		}else{//this should be handled by the model to whom this attribute belongs, in case there's associated special logic
			$otherFacade=EspressoAPI_ClassLoader::load($this->relatedModels[$modelName]['modelNamePlural'],'Resource');
			return $otherFacade->constructSQLWhereSubclause($apiParam,$operator,$value);
		}
		
	}
	/**
	 * constructs a complete value for a where clause, inluding quotes and parentheses. eg, 123, 'monkey', (1,23), ('foo','bar','weee').
	 * optional parameter include $mappingFromApiToDbColumn and $apiKey.
	 * @param type $operator like 'IN','<',etc.
	 * @param type $valueInput value from $_GET 
	 * @param type $mappingFromApiToDbColumn eg array('true'=>'Y','false'=>'N')
	 * @param type $apiKey eg 'Event.name'
	 * @return type 
	 */
	protected function constructValueInWhereClause($operator,$valueInput,$mappingFromApiToDbColumn=null,$apiKey=null){
		if($operator=='IN'){
			$values=explode(",",$valueInput);
			$valuesProcessed=array();
			foreach($values as $value){
				$valuesProcessed[]=$this->constructSimpleValueInWhereClause($value,$mappingFromApiToDbColumn,$apiKey);
			}
			$value=implode(",",$valuesProcessed);
			return "($value)";
		}else{
			return $this->constructSimpleValueInWhereClause($valueInput,$mappingFromApiToDbColumn,$apiKey);
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
	 * calls 'processSqlResults' on each related model and the current one
	 * @param array $rows results of wpdb->get_results, with lots of inner joins and renaming of tables in normal format
	 * @param array $queryParameters like those 
	 * @return same results as before, but with certain results filtered out as implied by queryParameters not taken
	 * into account in the SQL
	 */
	protected function initiateProcessSqlResults($rows,$keyOpVals){
		$rows=$this->processSqlResults($rows,$keyOpVals);
		foreach($this->relatedModels as $relatedModel=>$relatedModelInfo){
			$otherFacade=EspressoAPI_ClassLoader::load($relatedModelInfo['modelNamePlural'],'Resource');
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
	 * for filtering out rows from sqlresults that don't meet the criteria expressed
	 * in keyOpVal, where the key is a post-db-query-calculated column.
	 * Eg, in 3.1, 'Datetime.tickets_left' doesn't exist in the db, and
	 * is calculated in processSqlResults(). An api query param of 
	 * 'Datetime.tickets_left__lt=10' would be handled in this function.
	 * @param array $row single row from wpdb->get_results
	 * @param array $keyOpVals like array(0=>array('key'=>'Datetime.tickets_left','operator'=>'<','value'=>10))
	 */
	protected function rowPassesFilterByCalculatedColumns($row,$keyOpVals){
		foreach($keyOpVals as $keyOpVal){
			foreach($this->calculatedColumnsToFilterOn as $postSqlFilterParam){
				if($keyOpVal['key']==$postSqlFilterParam){
					if(!$this->evaluate($row[$postSqlFilterParam],$keyOpVal['operator'],$keyOpVal['value'])){
						return false;//this condiiton failed, don't include this row in the results!!
					}
				}
			}
		}
		return true;
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
		$booleanStrings=array('true'=>true,'false'=>false);
		if(array_key_exists($operand2,$booleanStrings)){
			$operand2=$booleanStrings[$operand2];
		}	
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
	 /**
	  * return first result from  extractMyUniqueModelsfromSqlResults 
	  */
	 protected function extractMyUniqueModelFromSqlResults($sqlResults,$idKey=null,$idValue=null){
		 $modelRepresentations=$this->extractMyUniqueModelsFromSqlResults($sqlResults, $idKey, $idValue);
		 return array_shift($modelRepresentations); 
	 }
	
	 
	 
	
	

	
	
	
	
	/**
	 * Gets events from database according ot query parameters by calling the concrete child classes' _getEvents function
	 * @param array $queryParameters
	 * @return array  
	 */
     function getMany($queryParameters){
		 if(!EspressoAPI_Permissions_Wrapper::current_user_can('get', $this->modelNamePlural)){
			 throw new EspressoAPI_UnauthorizedException();
		 }
		 //parse query parameter
		 if (!empty($queryParameters)){
			 if(array_key_exists('cache_result',$queryParameters)){
				 $cacheResult=true;
				 unset($queryParameters['cache_result']);
			 }else{
				 $cacheResult=false;
			 }
			$keyOpVals=$this->seperateIntoKeyOperatorValues($queryParameters);
		}
		else{
			$cacheResult=false;
			$keyOpVals=array();
		}
		//validate query parameter input first by normalizing input into 'Model.parameter'
		$this->validator->validateQueryParameters($keyOpVals);
		
		$whereSubclauses=$this->constructSQLWhereSubclauses($keyOpVals);//should still be called in case it needs to add special where subclauses
		//construct database query
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
		//process results (calculate 'calculated columns' and filter on them)
		$processedResults=$this->initiateProcessSqlResults($results,$keyOpVals);
		//begin constructing response array
		$topLevelModels=$this->extractMyUniqueModelsFromSqlResults($processedResults);
		$completeResults=array();
		foreach($topLevelModels as $key=>$model){
			foreach($relatedModelInfos as $relatedModelInfo){
				//only add the related model's info if the current user has permission to access it
				if(EspressoAPI_Permissions_Wrapper::current_user_can('get',$relatedModelInfo['modelNamePlural'])){
					$modelClass=$relatedModelInfo['class'];
					if($relatedModelInfo['hasMany']){
						$model[$relatedModelInfo['modelNamePlural']]=$modelClass->extractMyUniqueModelsFromSqlResults($processedResults,$this->modelName.'.id',$model['id']);
					}else{
						$model[$relatedModelInfo['modelName']]=$modelClass->extractMyUniqueModelFromSqlResults($processedResults,$this->modelName.'.id',$model['id']);
					}
				}
			}
			$completeResults[$key]=$model;
		}
		$models= array($this->modelNamePlural => $completeResults);
		$models=$this->validator->validate($models,array('single'=>false));
		if($cacheResult){
			$transientKey=EspressoAPI_Functions::generateRandomString(40);
			set_transient($transientKey,$models,60*60);
			return array("count"=>count($completeResults),"cached_result_key"=>$transientKey);
		}else
			return $models;
     }
	 
	
	/**
	  * gets a specific event acording to its id
	  * @param int $id
	  * @return array 
	  */
	 function getOne($id){
		$queryParam=array('id'=>$id);
		$fullResults=$this->getMany($queryParam);
		$singleResult=array_shift($fullResults[$this->modelNamePlural]);
		if(empty($singleResult)){
			throw new EspressoAPI_ObjectDoesNotExist($id);
		}
		$model= array($this->modelName=>$singleResult);
		return $model;
	 }

	
	

	
}
