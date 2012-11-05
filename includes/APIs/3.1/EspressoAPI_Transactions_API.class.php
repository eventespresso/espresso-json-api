<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Transactions_API extends EspressoAPI_Transactions_API_Facade{
	var $APIattributes=array(
		'id'=>'Attendee.id',
		'timestamp'=>'Attendee.date',
		'total'=>'Attendee.total_cost',
		'amount_paid'=>'Attendee.amount_pd',
		'status'=>'Transaction.payment_status.PROCESS',
		'registrations_on_transaction'=>'Attendee.quantity');
		
	
	var $selectFields="
		Attendee.id as 'Transaction.id',
		Attendee.date as 'Transaction.timestamp',
		Attendee.total_cost as 'Transaction.total',
		Attendee.amount_pd as 'Transaction.amount_pd',
		Attendee.payment_status as 'Transaction.status.PROCESS',
		Attendee.quantity as 'Transaction.registrations_on_transaction'";
	
	
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
			
			$statusMapping=array(
				'Completed'=>'complete',
				'Pending'=>'pending',
				'Incomplete'=>'pending');
			$status=$statusMapping[$sqlResult['Transaction.status.PROCESS']];
			$transaction=array(
				'id'=>$sqlResult['Transaction.id'],
				'timestamp'=>$sqlResult['Transaction.timestamp'],
				'total'=>$sqlResult['Transaction.total'],
				'paid'=>$sqlResult['Transaction.amount_pd'],
				'registrations_on_transaction'=>$sqlResult['Transaction.registrations_on_transaction'],
				'status'=>$status,
				'details'=>null,
				'tax_data'=>null,
				'session_data'=>null
				);
			return $transaction;
	}
	
    function _create($createParameters){
        return array("status"=>"Not Yet Implemented","status_code"=>"500");
    }
    /**
     *for handling requests liks '/events/14'
     * @param int $id id of event
     */
	protected function _getOne($id) {
		global $wpdb;
		$result=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$id'",ARRAY_A);
		if(empty($result))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(EspressoAPI_Permissions_Wrapper::espresso_is_my_event($result['event_id']))
			return array("attendee"=>$result);
		else
			throw new EspressoAPI_UnauthorizedException();
	
	}
}
//new Events_Controller();