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
 * Events API Facade class
 *
 * @package			Espresso REST API
 * @subpackage	includes/APIFacades/Espresso_Events_Resource_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
//require_once("EspressoAPI_Generic_Resource_Facade.class.php");
abstract class EspressoAPI_Transactions_Resource_Facade extends EspressoAPI_Generic_Resource_Facade{
	var $modelName="Transaction";
	var $modelNamePlural="Transactions";
	var $requiredFields=array(
		'id',
		'timestamp',
		'total',
		'amount_paid',
		'status',
		'details',
		'tax_data',
		'session_data',
		'registrations_on_transaction',
		'payment_gateway',
		'checked_in_quantity');
}

//$html .= '<script type="text/javascript">$jaer = jQuery.noConflict();var attendee_num = 0;var additional_limit = '.$additional_limit.';var first_add_button = null;var selector = \'div#additional_attendee_\' + attendee_num;function markup(attendee_num) {return \''.stripslashes($htm).'\';}function remove_add() {attendee_num -= 1;selector = \'div#additional_attendee_\' + attendee_num;$jaer(selector).remove();if (attendee_num != 0) {var temp_selector = \'div#additional_attendee_\' + (attendee_num - 1);$jaer(temp_selector + \' a.add\').on(\'click\',add_add);$jaer(temp_selector + \' a.remove\').on(\'click\',remove_add);} else {first_add_button.on(\'click\',add_add);}}function add_add() {if (attendee_num == additional_limit) return;$jaer(this).parent().parent().after(markup(attendee_num));$jaer(selector + \' a.add\').on(\'click\',add_add);$jaer(selector + \' a.remove\').on(\'click\',remove_add);$jaer(this).off(\'click\', add_add);if (attendee_num != 0) {var temp_selector = \'div#additional_attendee_\' + (attendee_num - 1);$jaer(temp_selector + \' a.remove\').off(\'click\', remove_add);}attendee_num += 1;selector = \'div#additional_attendee_\' + attendee_num;}jQuery(do cument).ready(function($jaer) {$jaer(\'a.add\').on(\'click\',add_add);first_add_button = $jaer(\'a.add\');});</script>';
