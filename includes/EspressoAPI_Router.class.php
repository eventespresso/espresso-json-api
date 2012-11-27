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
 * Router class
 *
 * @package			Espresso REST API
 * @subpackage	includes/EspressoAPI_Router.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
class EspressoAPI_Router{
    function __construct(){
          add_action('parse_query',array($this,'template_redirect'));//template_redirect was original action
    }
	/**
	 *checks if the user has capabilities allowing them to use the api at all. ie: at least event manager or event admin.
	 * if the event espresso permissions plugin is installed, uses it to determine if the user is an event espresso admin
	 *  
	 */
	function currentUserCanUseAPI(){
		$current_user = wp_get_current_user();
		if($current_user->ID==0){
			return false;
		}
		
		if(EspressoAPI_Permissions_Wrapper::espresso_is_admin() || current_user_can('espresso_event_manager'))
			return true;
		else{
			return false;
		}
	}
	protected function  publicAccessQuery($sessionKey){
		$allowPublicAccess=get_option(EspressoAPI_ALLOW_PUBLIC_API_ACCESS);
		if($sessionKey=='public' && $allowPublicAccess)
			return true;
		else
			return false;
	}
    /**
     *intercepts requests, and finds ones for the espresso API, and routes them to the appropriate controller, if there is one
     * @return type 
     */
     function template_redirect(){
		//fetch params and sanitize. $_REQUEST variables must be passed by the api before doing anything in teh db
		$apiRequest=get_query_var('espresso-api-request');
		$apiAuthenticate=get_query_var('espresso-api-authenticate');
		$sessionKeyAndMaybeFormat=sanitize_text_field(mysql_real_escape_string(get_query_var('espresso-sessionkey')));
		$sessionKey=$this->stripFormat($sessionKeyAndMaybeFormat);
        $apiParam1=sanitize_text_field(mysql_real_escape_string(get_query_var('espresso-api1')));
        $apiParam2=sanitize_text_field(mysql_real_escape_string(get_query_var('espresso-api2')));
        $apiParam3=sanitize_text_field(mysql_real_escape_string(get_query_var('espresso-api3')));
        
		if(empty($apiRequest))//this wasn't actually a request to the espresso API, let it go through the normal Wordpress response process
            return;
		try{
			if($apiAuthenticate=='true'){				
				$controller=EspressoAPI_ClassLoader::load('Authentication',"Controller");
				$response=$controller->authenticate();
			}else{
				if(!empty($sessionKey) && empty($apiParam1))
					throw new EspressoAPI_BadRequestException(__("Invalid request. You should also provide a resource, eg: 'events'. You only provided the following api key:","event-espresso").$sessionkey);
				global $current_user;
				if($this->publicAccessQuery($sessionKey)){
					$current_user->ID=0;
				}else{				
					$current_user=EspressoAPI_SessionKey_Manager::getUserFromSessionKey($sessionKey);
					EspressoAPI_SessionKey_Manager::updateSessionKeyActivity($current_user->ID);
				}
				//basic authentication: only logged-in users can do anything, and only those with basic event manager admin permissions
				//if(!$this->currentUserCanUseAPI())
				//	throw new EspressoAPI_UnauthorizedException();
				$controller=EspressoAPI_ClassLoader::load(ucwords($apiParam1),"Controller");
				$response=$controller->handleRequest($apiParam2,$apiParam3);
			}
        } catch (EspressoAPI_MethodNotImplementedException $e) {
			$response= array(EspressoAPI_STATUS => __("Endpoint not yet implemented","event_espresso"), EspressoAPI_STATUS_CODE => 500);
		} catch (EspressoAPI_UnauthorizedException $e) {
			$response= array(EspressoAPI_STATUS => __("Not authorized to access that endpoint","event_espresso"), EspressoAPI_STATUS_CODE => 403);
		} catch(EspressoAPI_ObjectDoesNotExist $e){
			$response= array(EspressoAPI_STATUS => __("Request is ok, but there is no object of specified type with id:","event_espresso")." ".$e->getMessage(), EspressoAPI_STATUS_CODE => 404);
		} catch(EspressoAPI_SpecialException $e){
			$response=array(EspressoAPI_STATUS=>$e->getMessage(),EspressoAPI_STATUS_CODE=>$e->getStatusCode());
		} catch(EspressoAPI_BadRequestException $e){
			$response=array(EspressoAPI_STATUS=>$e->getMessage(),EspressoAPI_STATUS_CODE=>400);
		}catch(EspressoAPI_BadCredentials $e){
			$response=array(EspressoAPI_STATUS=>"Bad username and password combination.",EspressoAPI_STATUS_CODE=>401);
		}
		catch (Exception $e) {
			$response= array(EspressoAPI_STATUS => $e->getMessage(), EspressoAPI_STATUS_CODE => 500);
		}
		$format=EspressoAPI_Response_Formatter::findFormatInParams(array($sessionKeyAndMaybeFormat));
		if($format=='xml'){
			header('Content-type: text/xml, application/xml');
		}elseif($format=='json'){
			header('Content-type: application/json');
		}
		echo EspressoAPI_Response_Formatter::format($response,$format);
        die;
    }
    function stripFormat($urlPart){
		$posOfDot=strpos($urlPart,".");
		if($posOfDot===FALSE)
			return $urlPart;
		else{
			return substr($urlPart,0,$posOfDot);
		}	
	}
    
}
new EspressoAPI_Router();