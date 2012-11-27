<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
/**
 * IMPORTANT NOTE TO DEVELOPER!!!
 * When getting a single transaction, don't forget to get all the registrations for that 
 * transaction. It's easier said tahn done. only query to transaction (attendee row entries)
 * which are marked as 'primary', and then for each of them, get all 'registrations'
 * that have teh same 'code' (registration_id in the db)
 */
class EspressoAPI_Transactions_Resource extends EspressoAPI_Transactions_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Attendee.id',
		'timestamp'=>'Attendee.date',
		'total'=>'Attendee.total_cost',
		'amount_paid'=>'Attendee.amount_pd',
		'registrations_on_transaction'=>'Attendee.quantity',
		'payment_gateway'=>'Attendee.txn_type');
		
	var $calculatedColumnsToFilterOn=array();
	var $selectFields="
		Attendee.id as 'Transaction.id',
		Attendee.id as 'Attendee.id',
		Attendee.date as 'Attendee.date',
		Attendee.total_cost as 'Attendee.total_cost',
		Attendee.amount_pd as 'Attendee.amount_pd',
		Attendee.payment_status as 'Attendee.payment_status',
		Attendee.quantity as 'Attendee.quantity',
		Attendee.txn_type as 'Attendee.txn_type'";
	var $relatedModels=array();
	
	/**
	 * used for caching 'primary transactions' (that is, transaction info
	 * on events_attendee rows that are marked as 'primary')
	 * @var type 
	 */
	private $primaryTransactions=array();
	/**
	 * used for caching the count of registrations per transaction.
	 * in 3.1, that means how many attendee rows*quantity on each row, who share
	 * the same registration_id
	 * @var type 
	 */
	private $registrationsPerTransaction=array();
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
			
			//if this is a primary registration, use this data
			//if its not, call the private function getPrimaryTransaction
			if(!$sqlResult['Attendee.is_primary']){
				$sqlResult=$this->getPrimaryTransaction($sqlResult);
			}
			$status=$this->statusMapping[$sqlResult['Attendee.payment_status']];
			$transaction=array(
				'id'=>$sqlResult['Transaction.id'],
				'timestamp'=>$sqlResult['Attendee.date'],
				'total'=>$sqlResult['Attendee.total_cost'],
				'amount_paid'=>$sqlResult['Attendee.amount_pd'],
				'registrations_on_transaction'=>$this->countRegistrationPerTransaction($sqlResult),
				'status'=>$status,
				'details'=>null,
				'tax_data'=>null,
				'session_data'=>null,
				'payment_gateway'=>$sqlResult['Attendee.txn_type'],
				);
			return $transaction;
	}
	
	private function countRegistrationPerTransaction($sqlResult){
		if(empty($sqlResult['Attendee.registration_id'])){
			throw new EspressoAPI_OperationFailed(__("Error counting registrations per transaction. There is no registration_id on the results on the row we're using to count","event_espresso"));
		}
		if(!array_key_exists($sqlResult['Attendee.registration_id'],$this->registrationsPerTransaction)){
			global $wpdb;
			$quantitieRows=$wpdb->get_results("SELECT quantity FROM {$wpdb->prefix}events_attendee Attendee
				WHERE Attendee.registration_id='{$sqlResult['Attendee.registration_id']}'",ARRAY_A);
			if(empty($quantitieRows)){
				throw new EspressoAPI_OperationFailed(__("Error counting registrations per transaction. Somehow there are no registrations for this transaction...","event_espresso"));
			}
				$count=0;
			foreach($quantitieRows as $quantityRow){
				$count+=intval($quantityRow['quantity']);
			}
			$this->registrationsPerTransaction[$sqlResult['Attendee.registration_id']]=$count;
		}
		return $this->registrationsPerTransaction[$sqlResult['Attendee.registration_id']];
	}

	/**
	 * special function for getting the transaction id and other data from the primary 
	 * attendee's row, not the current row being processed. 
	 * Eg: 
	 * @global type $wpdb
	 * @param type $sqlResult
	 * @return type 
	 */
	private function getPrimaryTransaction($sqlResult){
		//based on the Attendee.registration_id, and Attendee.is_primary
		//get the primary transaction
		if(!array_key_exists($sqlResult['Attendee.registration_id'],$this->primaryTransactions)){
			global $wpdb;
			$primaryTransactionRow=$wpdb->get_row("SELECT {$this->selectFields},
			Attendee.registration_id AS 'Attendee.registration_id'
			FROM {$wpdb->prefix}events_attendee Attendee
			WHERE Attendee.is_primary=1 AND Attendee.registration_id='{$sqlResult['Attendee.registration_id']}'",ARRAY_A );
			if(empty($primaryTransactionRow)){
				//the database is somehow corrupted. There should always be a primary attendee for each registration_id
				//so we'll just make do with what we already have
				return $sqlResult;
			}
			$this->primaryTransactions[$sqlResult['Attendee.registration_id']]=$primaryTransactionRow;
		}
		return $this->primaryTransactions[$sqlResult['Attendee.registration_id']];
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