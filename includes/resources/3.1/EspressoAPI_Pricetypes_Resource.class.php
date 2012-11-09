<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Pricetypes_Resource extends EspressoAPI_Pricetypes_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>null,
		'name'=>null,
		'is_member'=>null,
		'is_discount'=>null,
		'is_tax'=>null,
		'is_percent'=>null,
		'is_global'=>null,
		'order'=>null
	);
	var $selectFields="";
	var $relatedModels=array();
	/**
	 * in 3.1 there is no price_type table. But there are effectively 
	 * 4 price types: “base Price”,”Surcharge Amount”,”Surcharge Percent”, 
	 * and “Member Price”.  
	 */
	var $fakeDbTable=array(
		1=>array(
			"id"=>1,
			'name'=>'Base Price',
			'is_member'=>false,
			'is_discount'=>false,
			'is_tax'=>false,
			'is_percent'=>false,
			'is_global'=>true,
			'order'=>0),
		2=>array(
			"id"=>2,
			"name"=>"Surcharge Amount",
			"is_member"=>false,
			"is_discount"=>false,
			"is_tax"=>false,
			"is_percent"=>false,
			"is_global"=>true,
			"order"=>10),
		3=>array(
			"id"=>3,
			"name"=>"Surcharge Percent",
			"is_member"=>false,
			"is_discount"=>false,
			"is_tax"=>false,
			"is_percent"=>true,
			"is_global"=>true,
			"order"=>10),
		4=>array(
			"id"=>4,
			"name"=>"Member Price",
			"is_member"=>true,
			"is_discount"=>false,
			"is_tax"=>false,
			"is_percent"=>false,
			"is_global"=>true,
			"order"=>0));
	/**
     * gets all events in the database, according to query parmeters
     * @global type $wpdb
     * @param array $queryParameters of key=>values. eg: "array("start_date"=>"2012-04-23","name"=>"Mike Party").
     * @return type 
     */
    function _getMany($queryParameters){
		return new EspressoAPI_MethodNotImplementedException();
    }
    function _create($createParameters){
       return new EspressoAPI_MethodNotImplementedException();
    }
    /**
     *for handling requests liks '/events/14'
     * @param int $id id of event
     */
	protected function _getOne($id) {
		return new EspressoAPI_MethodNotImplementedException();
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
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		
		$price=array(
		'id'=>$sqlResult['Price.id'],
		'amount'=>$sqlResult['Price.event_cost'],
		'name'=>$sqlResult['Price.price_type'],
		'description'=>null,
		'limit'=>$sqlResult['Price.reg_limit'],
		'remaining'=>99999,//$sqlResult['Event.remaining'],
		'start_date'=>null,
		'end_date'=>null,
		);
		return $price; 
	}
}
//new Events_Controller();