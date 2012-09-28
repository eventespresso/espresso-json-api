<?php
include_once('../wp-config.php');
include_once('../wp-load.php');
include_once('../wp-includes/wp-db.php');
require 'Slim/Slim.php';

//$domain = preg_replace("/^(.*\.)?([^.]*\..*)$/", "$2", $_SERVER['HTTP_HOST']);

/*if ( isset($_REQUEST['ApiKey']) && isset($_REQUEST['Password']) ){
	if ( $_REQUEST['ApiKey'] != 'testapi' ) return;
	if ( $_REQUEST['Password'] != 'testpass' ) return;
	//if ( $domain != 'test-3-1.dev' ) return;
}else{
	return;
}*/

$app = new Slim();

$app->get('/events', 'getEvents');
$app->get('/events/:id', 'getEvent');
$app->get('/events/search/:query', 'findByName');

$app->run();

function getEvents() {
	global $wpdb, $org_options;
	
	$sql = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
	
	//Venue sql
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
		
	//Staff sql
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= ", st.name staff_name " : '';
	
	$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
	
	//Venue JOIN
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
		
	//Staff JOIN
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_PERSONNEL_REL_TABLE . " str ON str.event_id = e.id LEFT JOIN " . EVENTS_PERSONNEL_TABLE . " st ON st.id = str.person_id " : '';
		
	$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
	$sql .= " LEFT JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
	$sql .= " WHERE e.is_active = 'Y' AND e.event_status != 'D' ORDER BY event_name ";
	
	//Run the query	
	if ( $wpdb->get_results($wpdb->prepare($sql)) ){
		$events = $wpdb->last_result;
		$events = stripslashes_deep($events);
		//Output the json array of event data
		echo '{"event": ' . json_encode($events) . '}';
	}else{
		echo '{"error":{"text": No Events }}';
	}
	
}

function getEvent($id) {

	global $wpdb, $org_options;
	
	$sql = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
	
	//Venue sql
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
		
	//Staff sql
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= ", st.name staff_name " : '';
	
	$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
	
	//Venue JOIN
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
		
	//Staff JOIN
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_PERSONNEL_REL_TABLE . " str ON str.event_id = e.id LEFT JOIN " . EVENTS_PERSONNEL_TABLE . " st ON st.id = str.person_id " : '';
		
	$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
	$sql .= " LEFT JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
	$sql .= " WHERE e.id=$id ";
	
	//Run the query	
	if ( $wpdb->get_results($wpdb->prepare($sql)) ){
		$events = $wpdb->last_result;
		$events = stripslashes_deep($events);
		//Output the json array of event data
		echo '{"event": ' . json_encode($events) . '}';
	}else{
		echo '{"error":{"text": No Events }}';
	}
}

function findByName($query) {

	global $wpdb, $org_options;
	
	$sql = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
	
	//Venue sql
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
		
	//Staff sql
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= ", st.name staff_name " : '';
	
	$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
	
	//Venue JOIN
	isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
		
	//Staff JOIN
	isset($org_options['use_personnel_manager']) && $org_options['use_personnel_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_PERSONNEL_REL_TABLE . " str ON str.event_id = e.id LEFT JOIN " . EVENTS_PERSONNEL_TABLE . " st ON st.id = str.person_id " : '';
		
	$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
	$sql .= " LEFT JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
	$sql .= " WHERE  e.event_name LIKE '%%".$query."%%' ";

	//Run the query	
	if ( $wpdb->get_results($wpdb->prepare($sql)) ){
		$events = $wpdb->last_result;
		$events = stripslashes_deep($events);
		//Output the json array of event data
		echo '{"event": ' . json_encode($events) . '}';
	}else{
		echo '{"error":{"text": No Events }}';
	}
}