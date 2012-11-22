<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Transactions_Resource extends EspressoAPI_Transactions_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Attendee.id',
		'timestamp'=>'Attendee.date',
		'total'=>'Attendee.total_cost',
		'amount_paid'=>'Attendee.amount_pd',
		'registrations_on_transaction'=>'Attendee.quantity');
		
	var $calculatedColumnsToFilterOn=array();
	var $selectFields="
		Attendee.id as 'Transaction.id',
		Attendee.id as 'Attendee.id',
		Attendee.date as 'Attendee.date',
		Attendee.total_cost as 'Attendee.total_cost',
		Attendee.amount_pd as 'Attendee.amount_pd',
		Attendee.payment_status as 'Attendee.payment_status',
		Attendee.quantity as 'Attendee.quantity'";
	var $relatedModels=array();
	
	/**
	 * used for converting between api version of Transaction.status and the DB version
	 * keys are DB versions, valuesare teh api versions
	 * @var type 
	 */
	private $statusMapping=array(
				'Completed'=>'complete',
				'Pending'=>'pending',
				'Incomplete'=>'open');
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
			
			
			$status=$this->statusMapping[$sqlResult['Attendee.payment_status']];
			$transaction=array(
				'id'=>$sqlResult['Transaction.id'],
				'timestamp'=>$sqlResult['Attendee.date'],
				'total'=>$sqlResult['Attendee.total_cost'],
				'paid'=>$sqlResult['Attendee.amount_pd'],
				'registrations_on_transaction'=>$sqlResult['Attendee.quantity'],
				'status'=>$status,
				'details'=>null,
				'tax_data'=>null,
				'session_data'=>null
				);
			return $transaction;
	}
	
	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		switch($columnName){
			case 'Transaction.status':
				$apiStatusToDbStatus=array_flip($this->statusMapping);
				$value=$this->constructValueInWhereClause($operator,$value,$apiStatusToDbStatus,'Transaction.status');
				return "Attendee.payment_status $operator $value";	
		}
		return parent::constructSQLWhereSubclause($columnName, $operator, $value);		
	}
}
//new Events_Controller();