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
    // Api configuration
    private $apiConfig;

    // All API backend methods
    private $apiBackend;

    public function init()
    {
        // Call superclass init
        parent::init();

        // Load the api configuration
        $this->apiConfig = Yii::app()->params["api"];

        // Instantiate the ApiBackend Class
        $this->apiBackend = new ApiBackend($this->apiConfig);
    }

    /**
     * Runs before the execution of the action
     * @param CAction $action The name of the action which will be called
     * @return bool Should the action be run?
     */
    public function beforeAction($action)
    {
        // Check if the API is enabled
        if (!$this->apiConfig["enable"])
            (new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500022))->setDevDescription("You have to enable the api in vm_config.php")->display();

        // Checks if the default realm is set
        if (empty($this->apiConfig["defaultRealm"]))
            (new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500023))->setDevDescription("You have to set a default realm in vm_config.php")->display();

        return true;
    }

    /**
     * Handles the /user/login REST-Request
     * + Login is required
     */
    public function actionUserLogin()
    {
        $userIdentity = $this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

        (new JsonFormatter(HttpStatusCode::SUCCESS, 200001))->display();
    }

    /**
     * Handles the /server/realms REST-Request
     */
    public function actionServerRealms()
    {
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

    public function actionVmAssign()
    {
        $this->apiBackend->checkAuthentification(Yii::app()->request->getQuery("realm"));

        (new JsonFormatter(HttpStatusCode::SUCCESS, 200031))->setData(array("vm" => $this->apiBackend->assignVm(Yii::app()->request->getQuery("pool"))))->display();
    }

    /**
     * Handles all unkown REST-Requests
     */
    public function actionError()
    {
        (new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400000))->setDevDescription("Unkown action")->display();
    }

}
