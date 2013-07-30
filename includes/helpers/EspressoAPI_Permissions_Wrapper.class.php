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
	static function current_user_has_espresso_permissions(){
		if(function_exists('espresso_is_admin')){
			return espresso_is_admin() || current_user_can('espresso_event_manager');
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
		global $current_user;
				
		
		if(empty($current_user) || $current_user->ID==0){//no user logged in, only allow for access to public stuff		
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
						case 'Categories':
						case 'Datetimes':
						case 'Prices':
						case 'Pricetypes':
						case 'Venues':
						case 'Questions':
							return true;
						case 'Promocodes':
						case 'Attendees':
						case 'Registrations':
						case 'Transactions':
						case 'Answers':
							return false;
							break;
						default:
							return true;
					}
			}
			
		}elseif(self::current_user_has_espresso_permissions ()){//if they're an admin, give them all permisions (in this version)
			return true;
		}else{//they're a subscriber, so give no permissions
			return false;
		}
	}
	
	
}
