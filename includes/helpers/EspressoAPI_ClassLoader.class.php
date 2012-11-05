<?php

class EspressoAPI_ClassLoader{
	/**
	 * loads a class from the EventEspresoAPI and returns a new isntance of it
	 * @param string $class eg 'Events' or 'Attendees'
	 * @param string $type  eg 'Controller' or 'Facade'
	 */
	function load($class,$type){
		switch($type){
			case 'Facade':
				$version = substr(EVENT_ESPRESSO_VERSION, 0, 3);
				$genericFacadeFilePath=EspressoAPI_DIR_PATH . "includes/APIFacades/EspressoAPI_Generic_API_Facade.class.php";
				$apiFacadeFilePath=EspressoAPI_DIR_PATH . "includes/APIFacades/EspressoAPI_{$class}_API_Facade.class.php";
				$apiFilePath=EspressoAPI_DIR_PATH . "includes/APIs/{$version}/EspressoAPI_{$class}_API.class.php";
				if(file_exists($genericFacadeFilePath) && file_exists($apiFacadeFilePath) && file_exists($apiFilePath)){
					require_once($genericFacadeFilePath);
					require_once($apiFacadeFilePath);
					require_once($apiFilePath);
					$apiFacadeName = "EspressoAPI_{$class}_API";
					return  new $apiFacadeName;
				}
				break;
			case 'Controller':
				$genericControllerFilePath=EspressoAPI_DIR_PATH."includes/controllers/EspressoAPI_Generic_Controller.class.php";
				$controllerFileName="EspressoAPI_{$class}_Controller";
				$controllerFilePath=EspressoAPI_DIR_PATH."includes/controllers/{$controllerFileName}.class.php";
				if(file_exists($genericControllerFilePath) && file_exists($controllerFilePath)){
					require_once($genericControllerFilePath);
					require_once($controllerFilePath);
					return new $controllerFileName;	
				}
				break;
			default:
				throw new EspressoAPI_ClassNotFound(__("EspressoAPI Class Loader Error. Could not find Class of Type:","event_espresso")."$class, $type");
		}
		throw new EspressoAPI_ClassNotFound(__("EspressoAPI Class Loader Error. Could not find Class of Type:","event_espresso")."$class, $type");
	}
}