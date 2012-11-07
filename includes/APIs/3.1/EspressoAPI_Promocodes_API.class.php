<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Promocodes_API extends EspressoAPI_Promocodes_API_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Promocode.id',
		'coupon_code'=>'Promocode.coupon_code',
		'amount'=>'Promocode.coupon_code_price',
		'use_percentage'=>'Promocode.use_percentage',
		'description'=>'Promocode.coupon_code_description',
		'apply_to_each_attendee'=>'Promocode.each_attendee',
		'user'=>'Promocode.wp_user',
	);
	var $selectFields="
		Promocode.id AS 'Promocode.id',
		Promocode.coupon_code AS 'Promocode.coupon_code',
		Promocode.coupon_code_price AS 'Promocode.price',
		Promocode.use_percentage AS 'Promocode.use_percentage',
		Promocode.coupon_code_description AS 'Promocode.description',
		Promocode.each_attendee AS 'Promocode.apply_to_each_attendee',
		Promocode.wp_user AS 'Promocode.user'";
	var $relatedModels=array();


	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		switch($columnName){
			case 'Promocode.expiration_date':
			case 'Promocode.quantity_available':
				return null;
		}
		return parent::constructSQLWhereSubclause($columnName, $operator, $value);		
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
		$promocode=array(
		'id'=>$sqlResult['Promocode.id'],
		'coupon_code'=>$sqlResult['Promocode.coupon_code'],
		'amount'=>$sqlResult['Promocode.price'],
		'use_percentage'=>$sqlResult['Promocode.use_percentage'],
		'description'=>$sqlResult['Promocode.description'],
		'apply_to_each_attendee'=>$sqlResult['Promocode.apply_to_each_attendee'],
		'quantity_available'=>999999,
		'expiration_date'=>'9999-01-01 01:01:01',
		'user'=>$sqlResult['Promocode.user']
		);
		return $promocode; 
	}
}