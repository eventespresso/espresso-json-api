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
	function createOrUpdateMany($apiInput){
		//validate input
		$apiInput=$this->validator->validate($apiInput,array('single'=>false,'requireRelated'=>false,'allowTempIds'=>true));
		$idsAffected=array();
		foreach($apiInput[$this->modelNamePlural] as $inputPerModelInstance){
			$idsAffected[]=$inputPerModelInstance['id'];
			$this->performCreateOrUpdate(array($this->modelName=>$inputPerModelInstance));
		}
		//throw new EspressoAPI_SpecialException("it worked!");
		return $this->getMany(array('id__IN'=>implode(",",$idsAffected)));
	
	}
	
	function updateOne($id,$apiInput){
		$apiInput=$this->validator->validate($apiInput,array('single'=>true,'requireRelated'=>false,'allowTempIds'=>true));
		$this->performCreateOrUpdate($apiInput);
		return $this->getOne($id);
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
	
	/**
	 * given data in the form array(TABLE_NAME=>array(1=>array('columnName'=>'columnValue'...
	 * this will update or create the appropriate tables
	 * @param type $dbUpdateData 
	 * @return true if all updates are successful, false is there was an error
	 */
	protected function updateDBTables($dbUpdateData){
		//now we go about creating or updating according to what' sin dbUpdateData
		foreach($dbUpdateData as $tableName=>$rowsToUpdate){
			foreach($rowsToUpdate as $rowId=>$columns){
				$result=$this->updateOrCreateRow($tableName,$columns);
				if($result===false){
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * updates the row indicated of $tableName, where $keyValPairs is an array of
	 * column-value-mappings for the update, or if none is found, creates it.
	 * By default, uses the 'id'=$keyValuPairs['id'] as the WHERE clause for the update,
	 * but the name of the ID field can be changed by setting $options['id'].
	 * Also, if you want to change the WHERE clause further, use $options['whereClauses']
	 * (note that this will override the default WHERE clause previous mentioned.)
	 * if overriding $options['whereClauses'], you'll also need to set $options['whereFormats'] 
	 * (which indicates the variable type of each value in the where clauses as %d (digit),
	 * %f (float) or %s (string). $options['whereFormats'] is, by default, array('%d'))
	 * @global type $wpdb
	 * @param string $tableName
	 * @param array $keyValPairs like array('id'=>12,'fname'=>'bob'... 
	 * @param array $options array of options:
	 * - 'whereClauses' is an array of key-value pairs
	 * to be used for 'where' conditions (replaces default of array('id'=>$keyValPairs['id'])
	 * - 'id' is the column name to be used instead of 'id',  default is 'id'
	 * - 'whereFormats' is an array of strings, each being one of '%s','%d',%f' like documented in http://codex.wordpress.org/Class_Reference/wpdb#UPDATE_rows
	 * 
	 * @return id of row updated
	 */
	protected function updateOrCreateRow($tableName, array $keyValPairs,array $options=array()){
		global $wpdb;
		if(array_key_exists('whereClauses',$options)){
			$wheres=$options['whereClauses'];
			$idCol=null;
		}else{
			$wheres=array();
			if(array_key_exists('id',$options)){
				$idCol=$options['id'];
			}else{
				$idCol='id';
			}
		}
		
		
		$format=array();
		$create=true;//start off assuming we're inserting a new row
		foreach($keyValPairs as $columnName=>$columnValue){
			if($columnName!=$idCol){
				if(is_float($columnValue)){
					$format[]='%f';
				}else if(is_int($columnValue)){
					$format[]='%d';
				}else{
					$format[]='%s';
				}
				//$sqlAssignments[]=$wpdb->prepare("$columnName=".(isint($columnValue) || isfloat($columnValue)?"%d":"%s"),$columnValue);
			}elseif(isset($idCol)){
				$wheres[$idCol]=$columnValue;
				if(strpos($keyValPairs[$idCol],"temp-")==0){//so if the id starts with 'temp-'
					$create=true;
				}else{
					$create=false;
				}
				unset($keyValPairs[$idCol]);
			}
		}
		if($create){
			$result=$wpdb->insert($tableName,$keyValPairs,$format);
			return $wpdb->insert_id;
		}else{
			$result= $wpdb->update($tableName,$keyValPairs,$wheres,$format,
				array_key_exists('whereFormats',$options)?$options['whereFormats']:'%d');
			if($result>1){
				if(WP_DEBUG){
					throw new EspressoAPI_SpecialException(sprintf(__("Error updating entry! We accidentally updated more than 1 when we 
						only wanted to update one! We were updating the %s database table, with these values:%s, and these conditions: %s,
						and somehow updated %d rows!","event_espresso"),$tableName,print_r($keyValPairs,true),print_r($wheres,true),$result));
				}else{
					throw new EspressoAPI_SpecialException(__("Error updating entry! Turn on WP_DEBUG for more info.","event_espresso"));
				}
			}
			if($result!==false){
				return $wheres[$idCol];
			}else{
				return false;
			}
		
		}
		//$updateSQL="UPDATE $tableName SET ".implode(",",$sqlAssignments).$wpdb->prepare(" WHERE $idCol=%d $extraSQL",$rowId);
		
		//return $wpdb->query($updateSQL);
	}
}
