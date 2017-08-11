<?php

/*
 * Copyright (C) 2006 - 2017 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Sören Busse <soeren.2011@live.de>
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in
 * writing, software distributed under the Licence is
 * distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied.
 * See the Licence for the specific language governing
 * permissions and limitations under the Licence.
 *
 *
 */

class ApiController extends Controller
{

	// All API backend methods
	private $apiBackend;

	public function init()
	{
		// Call superclass init
		parent::init();

		// Instantiate the ApiBackend Class
		$this->apiBackend = new ApiBackend();

		Yii::app()->errorHandler->errorAction='api/api/error';
	}

	/**
	 * Runs before the execution of the action
	 * @param CAction $action The name of the action which will be called
	 * @return bool Should the action be run?
	 */
	public function beforeAction($action)
	{
		// Check if the API is enabled
		if (!Yii::app()->getModule('api')->enable)
			(new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500022))->setDevDescription("You have to enable the api in vm_config.php")->display();

		// Checks if the default realm is set
		if (empty(Yii::app()->getModule('api')->defaultRealm))
			(new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500023))->setDevDescription("You have to set a default realm in vm_config.php")->display();

		return true;
	}

	/**
	 * Handles the /user/login REST-Request
	 * + Login is required
	 */
	public function actionUserLogin()
	{
		// Authentificate the user
		$this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

		// Return that the authentification was successfull
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200001))->display();
	}

	/**
	 * Handles the /server/realms REST-Request
	 */
	public function actionServerRealms()
	{
		// Return the realms
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200011))->setData(array("realms" => $this->apiBackend->getRealms()))->display();
	}

	/**
	 * Handles the /vm/list REST-Request
	 */
	public function actionVmList()
	{
		// Authentificate user
		$this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

		// INFO: If the user has no permission an empty VM list is returned
		$allVms = array();

		// Switch listing type
		switch (Yii::app()->request->getQuery("type")) {
			case "dynamic":
				$allVms[] = $this->apiBackend->getDynamicVms(false);
				break;
			case "persistent":
				$allVms[] = $this->apiBackend->getPersistentVms();
				break;
			case "all":
				$allVms[] = $this->apiBackend->getDynamicVms(true);
				$allVms[] = $this->apiBackend->getPersistentVms();
				break;
			case "owner":
			case "":
				$allVms[] = $this->apiBackend->getDynamicVms(false);
				$allVms[] = $this->apiBackend->getPersistentVms();
				break;
			default:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400032))->setDevDescription("Unkown type - Use nothing,all(default),dynamic,persistent")->display();
		}

		// Merge all arrays
		$result = array();
		foreach ($allVms as $array) {
			$result = array_merge($result, $array);
		}

		// Display result
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200031))->setData(array("vms" => $result))->display();
	}

	/**
	 * Handles /vm/assign requests
	 * Assigns a vm to a user
	 */
	public function actionVmAssign()
	{
		// Authenticate the user
		$this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

		// Return a success message
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200041))->setData(array("vm" => $this->apiBackend->assignVm(Yii::app()->request->getQuery("pool"))))->display();
	}

	/**
	 * Handles /vm/mapping/list requests
	 * Lists all mapped vms
	 */
	public function actionVmMappingList()
	{
		// Authenticate the user
		$this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

		// The Mac
		$mac = "";

		// Get MacAdrress
		if (Yii::app()->getModule('api')->macByParameter && Yii::app()->request->getQuery("mac") != null) {
			// Is this Opt-In enabled and is the parameter set?
			$mac = Yii::app()->request->getQuery("mac");
		} else if (Yii::app()->request->getQuery("mac") != null) {
			// Does the request contain an mac parameter, but opt-in isn't enabled?
			(new JsonFormatter(HttpStatusCode::FORBIDDEN, 403056))->setDevDescription("MacByParameter is disabled in config")->display();
		} else {
			// Use the Mac by Remote Address
			$mac = Utils::getMacAddress($_SERVER["REMOTE_ADDR"]);
		}

		// Retrieved Mapped VMs and display
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200051))->setData(array("vm" => $this->apiBackend->getMappingVm($mac)))->display();
	}

	/**
	 * Handles /vm/mapping/assign requests
	 * Assigns MAC addresses to a vm
	 */
	public function actionVmMappingAssign()
	{
		// Authenticate the user
		$this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

		// The Mac
		$mac = "";

		// Get MacAdrress
		if (Yii::app()->getModule('api')->macByParameter && Yii::app()->request->getQuery("mac") != null) {
			// Is this Opt-In enabled and is the parameter set?
			$mac = Yii::app()->request->getQuery("mac");
		} else if (Yii::app()->request->getQuery("mac") != null) {
			// Does the request contain an mac parameter, but opt-in isn't enabled?
			(new JsonFormatter(HttpStatusCode::FORBIDDEN, 403066))->setDevDescription("MacByParameter is disabled in config")->display();
		} else {
			// Use the Mac by Remote Address
			$mac = Utils::getMacAddress($_SERVER["REMOTE_ADDR"]);
		}

		// Get the pool
		$pool = Yii::app()->request->getQuery("pool");

		// Display the assigned VM
		(new JsonFormatter(HttpStatusCode::SUCCESS, 200061))->setData(array("vm" => $this->apiBackend->assignMappingVm($pool, $mac)))->display();
	}

	/**
	 * Handles all unkown REST-Requests
	 */
	public function actionError()
	{
		// Get the error
		$error = Yii::app()->errorHandler->error;

		// Display error
		if ($error["code"] == 404) {
			(new JsonFormatter(HttpStatusCode::NOT_FOUND, 404000))->setDevDescription("Unkown action")->display();
		} else {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 500000))->setDevDescription($error["message"])->display();
		}
	}

}