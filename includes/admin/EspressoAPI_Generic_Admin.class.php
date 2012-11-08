<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EspressoAPI_Admin_Common
 *
 * @author mnelson4
 */

define('EspressoAPI_ADMIN_REAUTHENTICATE','espressoapi_admin_reauthenticate');
define('EspressoAPI_ADMIN_SESSION_TIMEOUT_OPTIONS','EspressoAPI_session_timeout_options');
define('EspressoAPI_ADMIN_SETTINGS_PAGE_SLUG','espresso-api-settings');
class EspressoAPI_Generic_Admin {
	function __construct(){
		//echo "generic admin loaded";
		add_action('action_hook_espresso_include_admin_files_start', array($this,'loadVersionSpecificHooks'), 30);
	}
	function loadVersionSpecificHooks(){
		$version = substr(EVENT_ESPRESSO_VERSION, 0, 3);
		$genericAdminFilePath=EspressoAPI_DIR_PATH."includes/admin/EspressoAPI_Generic_Admin.class.php";
		$adminFileName="EspressoAPI_Admin";
		$adminFilePath=  EspressoAPI_DIR_PATH."includes/admin/$version/$adminFileName.class.php";
		if(file_exists($genericAdminFilePath) && file_exists($adminFilePath)){
			require_once($genericAdminFilePath);
			require_once($adminFilePath);
			return new $adminFileName($this);
		}
	}
	function display_api_settings_page(){
		if(isset($_POST[EspressoAPI_ADMIN_REAUTHENTICATE])){
			EspressoAPI_SessionKey_Manager::regeneratAllSessionKeys();
		}
		if(isset($_POST[EspressoAPI_ADMIN_SESSION_TIMEOUT])){
			update_option(EspressoAPI_ADMIN_SESSION_TIMEOUT,$_POST[EspressoAPI_ADMIN_SESSION_TIMEOUT]);
		}
		$templateVars=array();
		$templateVars[EspressoAPI_ADMIN_SESSION_TIMEOUT]=get_option(EspressoAPI_ADMIN_SESSION_TIMEOUT);
		$templateVars[EspressoAPI_ADMIN_SESSION_TIMEOUT_OPTIONS]=apply_filters("filter_hook_espresso_api_session_timeout_options",
					array(
						'1 Minute'=>60,
						'5 Minutes'=>60*20,
						'10 Minutes'=>60*60,
						'20 Minutes'=>60*60*6,
						'An Hour'=>60*60*24,
						'6 Hours'=>60*60*24*7,
						'Never'=>-1));
		$this->includeVersionedTemplate('settings.php',$templateVars);
	}
	
	private function includeVersionedTemplate($templateFileName,$templateVars=null){
		$version = substr(EVENT_ESPRESSO_VERSION, 0, 3);	
		include(EspressoAPI_DIR_PATH."includes/admin/$version/templates/$templateFileName");
	}
}
new EspressoAPI_Generic_Admin();