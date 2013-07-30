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
 * Events API Permission Wrapper class
 *
 * @package			Espresso REST API
 * @subpackage	includes/helpers/espressoAPI_Permissions_Wrapper.class.php
 * @author				Mike Nelson
 *
 * wraps functions contained in the espresso-permissions and espresso-permissions-pro plugin. 
 * If neither of those plugins is installed, handles the method call in the logically manner
 * (eg, on EspressoAPI_Permissions_Wrapper::espresso_is_admin, if neither permissions plugin is 
 * installed, it just checks if the current user is an admin)
 * ------------------------------------------------------------------------
 */
class EspressoAPI_Permissions_Wrapper{
	
	/**
	 *if espresso_is_my_event isnt defined, just returns if the user is an admin.
	 * I think 'espresso_can_manage_event' would be a better name for this function
	 * @param int $event_id
	 * @return boolean 
	 */
	static function espresso_is_my_event($event_id){
		if(function_exists('espresso_is_my_event')){
			return espresso_is_my_event($event_id);
		}else{
			return current_user_can('administrator');
		}
	}
	/**
	 * Utilizes the permissions addon's function to determine if 
	 * the user who's trying to login should be allowed to use EE (and the API)
	 * @return boolean
	 */
	static function espresso_is_admin(){
		if(function_exists('espresso_is_admin')){
			return espresso_is_admin();
		}else{
			return current_user_can('administrator');
		}
	}
	
	/**
	 * wrapper for checking if the current user has the necessary permission to
	 * access/edit this resource.
	 * initially though, we've just hard-coded the permissions
	 * @param $httpMethod like get,post,put,delete
	 * @param $resource name of API Model pluralized which user is trying to access,eg 'Events','Categories', etc.
	 * @param $id of the resource if they're wanting access to a particular resource. 
	 */
	static function current_user_can($httpMethod='get',$resource='Events',$id=null){
		
//		echo "check current users permission using current_user_can. http: $httpMethod, resource:$resource<br>";

		
		
//		if(isset($current_user) && $current_user->ID!=0){//no user logged in, only allow for access to public stuff
			//as some point in the future, we may wish to have more permissions
			switch($httpMethod){
				case'get':
				case'GET':
				case'post':
				case'POST':
				case'put':
				case'PUT':
				case'delete':
				case'DELETE':
				default:
					switch($resource){
						case 'Events':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Categories':
							return self::current_user_has_espresso_permission('espresso_manager_categories');
						case 'Datetimes':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Prices':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Pricetypes':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Venues':
							return self::current_user_has_espresso_permission('espresso_manager_venue_manager');
						case 'Questions':
							return self::current_user_has_espresso_permission('espresso_manager_form_builder');
						case 'Question_Groups':
							return self::current_user_has_espresso_permission('espresso_manager_form_groups');
						case 'Promocodes':
							return self::current_user_has_espresso_permission('espresso_manager_discounts');
						case 'Attendees':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Registrations':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Transactions':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						case 'Answers':
							return self::current_user_has_espresso_permission('espresso_manager_events');
						default:
							return current_user_can('administrator');
					}
			}
//		}else{
//			return true;
//		}
	}
	
	/**
	 * 
	 * @global array $espresso_manager set in the permissions addons
	 * @param string $permission like espresso_manager_events (one of the array keys in
	 * $espresso_manager) 
	 * @return boolean
	 */
	private static function current_user_has_espresso_permission($permission){
		global $espresso_manager, $current_user;
		//if user isn't logged in, only grant them access to particular stuff
//		echo "current user is :";
//				var_dump($current_user);
		if( ! $current_user->ID){
//			echo "override epsresso_manager";
			$espresso_manager = array(
				'espresso_manager_events'=>true,
				'espresso_manager_venue_manager'=>true,
				//espresso_manager_form_builder
				//espresso_manager_form_groups
				//espresso_manager_categories
				//espresso_manager_discounts
			);
			if(isset($espresso_manager[$permission]) && $espresso_manager[$permission] ){
				return true;
			}
		}else{
			$can =  current_user_can(isset($espresso_manager[$permission]) ? $espresso_manager[$permission] : 'administrator');
			return $can;
		}
	}
	
	
}
