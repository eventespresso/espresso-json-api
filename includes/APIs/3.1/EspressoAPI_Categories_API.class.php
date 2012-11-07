<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Categories_API extends EspressoAPI_Categories_API_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Category.id',
		'name'=>'Category.category_name',
		'identifier'=>'Category.category_identifier',
		'description'=>'Category.category_desc',
		'user'=>'Category.wp_user'
	);
	var $selectFields="
		Category.id AS 'Category.id',
		Category.category_name AS 'Category.name',
		Category.category_identifier AS 'Category.identifier',
		Category.category_desc AS 'Category.description',
		Category.wp_user AS 'Category.user'";
	var $relatedModels=array();
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
		$metas=unserialize($sqlResult['Venue.metas']);
		$category=array(
		'id'=>$sqlResult['Category.id'],
		'name'=>$sqlResult['Category.name'],
		'identifier'=>$sqlResult['Category.identifier'],
		'description'=>$sqlResult['Category.description'],
		'user'=>$sqlResult['Category.user']
		);
		return $category; 
	}
}
//new Events_Controller();