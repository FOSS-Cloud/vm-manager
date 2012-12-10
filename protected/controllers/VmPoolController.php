<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * http://www.osor.eu/eupl
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

/**
 * VmPoolController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 1.0
 */

class VmPoolController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'vmpool';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('view' == $action) {
			$this->submenu['vmpool']['items']['vmpool']['items'][] = array(
				'label' => Yii::t('menu', 'View'),
				'itemOptions' => array('title' => Yii::t('menu', 'VM Pool View Tooltip')),
				'active' => true,
			);
		}
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
			        'actions'=>array('index', 'getVmPools', 'create', 'update', 'getDynData', 'getUserGui', 'saveUserAssign', 'getgroupGui', 'saveGroupAssign', 'view', 'delete'),
		        	'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
	   	 	    'users'=>array('*'),
			),
		);
	}

	public function actionIndex() {
		$model=new LdapVmPool('search');
		if(isset($_GET['LdapVmPool'])) {
			$model->attributes = $_GET['LdapVmPool'];
		}
		$this->render('index', array(
			'model' => $model,
		));
	}

	public function actionView() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapVmPool')->findbyDn($_GET['dn']);
		else if (isset($_GET['node']))
			$model = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstNode' => $_GET['vmpool'])));
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');
		$this->render('view',array(
			'model' => $model,
		));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vmpool-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Ajax functions for JqGrid
	 */
	public function actionGetVmPools() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		// if we not pass at first time index use the first column for the index or what you want
		if(!$sidx) $sidx = 1;

		$criteria = array('attr'=>array());
		if (isset($_GET['sstDisplayName'])) {
			$criteria['attr']['sstDisplayName'] = '*' . $_GET['sstDisplayName'] . '*';
		}
		$pools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$count = count($pools);

		// calculate the total pages for the query
		if( $count > 0 && $limit > 0)
		{
			$total_pages = ceil($count/$limit);
		}
		else
		{
			$total_pages = 0;
		}

		// if for some reasons the requested page is greater than the total
		// set the requested page to total page
		if ($page > $total_pages)
		{
			$page = $total_pages;
		}

		// calculate the starting position of the rows
		$start = $limit*$page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;
		if (1 != $sidx) {
			$criteria['sort'] = $sidx . '.' . $sord;
		}

		$pools = CLdapRecord::model('LdapVmPool')->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  "<rows>";
		$s .= "<page>".$page."</page>";
		$s .= "<total>".$total_pages."</total>";
		$s .= "<records>".$count."</records>";

		$i = 1;
		// be sure to put text data in CDATA
		foreach ($pools as $pool) {
			$hasVms = 0 < count($pool->vms);
			$storagepooldns = '';
			$storagepools = '';
			//echo '<pre>StoragePools ' . print_r($pool->storagepools, true) . '</pre>';
			foreach($pool->storagepools as $tmppool) {
				$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$tmppool->ou)));
				//echo '<pre>' . print_r($storagepool, true) . '</pre>';
				$storagepooldns .= $storagepool->dn;
				$storagepools .= $storagepool->sstDisplayName;
				break;
			}
			$nodedns = '';
			$nodes = '';
			//echo '<pre>StoragePools ' . print_r($pool->storagepools, true) . '</pre>';
			foreach($pool->nodes as $tmpnode) {
				$node = CLdapRecord::model('LdapNode')->findByAttributes(array('attr'=>array('sstNode'=>$tmpnode->ou)));
				//echo '<pre>' . print_r($storagepool, true) . '</pre>';
				if ('' != $nodedns) {
					$nodedns .= '|';
				}
				$nodedns .= $node->dn;
				if ('' != $nodes) {
					$nodes .= '|';
				}
				$nodes .= $node->sstNode;
			}
			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i++ . "</cell>\n";
			$s .= '<cell>' . ($hasVms ? 'true' : 'false') . "</cell>\n";
			$s .= '<cell>'. $pool->dn ."</cell>\n";
			$s .= '<cell>'. $pool->sstVirtualMachinePoolType ."</cell>\n";
			$s .= '<cell>'. $pool->sstDisplayName ."</cell>\n";
			$s .= '<cell>'. $pool->description ."</cell>\n";
			$s .= '<cell>'. $nodedns ."</cell>\n";
			$s .= '<cell>'. $nodes ."</cell>\n";
			$s .= '<cell>'. $storagepooldns ."</cell>\n";
			$s .= '<cell>'. $storagepools ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetDynData() {
		$this->disableWebLogRoutes();
		$type = $_GET['type'];
		$retval = array();
		$retval['type'] = $type;
		$storagepools = CLdapRecord::model('LdapStoragePool')->findAll(array('attr'=>array('sstStoragePoolType'=>$type)));
		$retval['storagepools'] = $this->createDropdownFromLdapRecords($storagepools, 'sstStoragePool', 'sstDisplayName');
		$retval['ranges'] = $this->getRangesByType($retval['type'], array());

		$config = CLdapRecord::model('LdapVmPoolDefinition')->findByAttributes(array('attr'=>array('ou'=>$type)));
		if ($config->hasAttribute('sstBrokerMaximalNumberOfVirtualMachines')) {
			$retval['brokerMin'] = $config->sstBrokerMinimalNumberOfVirtualMachines;
			$retval['brokerMax'] = $config->sstBrokerMaximalNumberOfVirtualMachines;
			$retval['brokerPreStart'] = $config->sstBrokerPreStartNumberOfVirtualMachines;
		}

		$this->sendJsonAnswer($retval);
	}

	public function actionGetUserGui() {
		$this->disableWebLogRoutes();
		$uarray = array();
		$users = LdapUser::model()->findAll(array('attr'=>array()));
		foreach ($users as $user) {
			$uarray[$user->uid] = array('name' => $user->getName() . ($user->isAdmin() ? ' (Admin)' : ' (User)') . ($user->isForeign() ? '(E)' : '') );
			if ($user->isAssignedToVmPool($_GET['dn'])) {
				$uarray[$user->uid]['selected'] = true;
			}
		}
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['dn']);
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmpool', 'Assign users to VM Pool') . ' \'' . $vmpool->sstDisplayName . '\'</span></div>';
		$dual = $this->createWidget('ext.zii.CJqDualselect', array(
			'id' => 'userAssignment',
			'values' => $uarray,
			'size' => 6,
			'options' => array(
				'sorted' => true,
				'leftHeader' => Yii::t('vmpool', 'Users'),
				'rightHeader' => Yii::t('vmpool', 'Assigned users'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'dualselect.css',
		));
		$dual->run();
?>
		<button id="saveUserAssignment" style="margin-top: 10px; float: left;"></button>
		<div id="errorUserAssignment" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorUserMsg"></span></p>
		</div>
		<div id="infoUserAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoUserMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionSaveUserAssign() {
		$this->disableWebLogRoutes();
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['dn']);
		$userDn = 'ou=people,' . $_GET['dn'];
		$server = CLdapServer::getInstance();
		$server->delete($userDn, true, true);
		$getusers = explode(',', $_GET['users']);
		foreach($getusers as $uid) {
			$user = LdapUser::model()->findByDn('uid=' . $uid . ',ou=people');
			if (!is_null($user)) {
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
				$data['ou'] = $uid;
				$data['description'] = array('This entry links to the user ' . $user->getName() . '.');
				$data['labeledURI'] = array('ldap:///' . $user->dn);
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn = 'ou=' . $uid . ',' . $userDn;
				$server->add($dn, $data);
			}
		}
		$json = array('err' => false, 'msg' => Yii::t('vmpool', 'Assignment saved!'));
		$this->sendJsonAnswer($json);
	}

	public function actionGetGroupGui() {
		$this->disableWebLogRoutes();
		$garray = array();
		$groups = LdapGroup::model()->findAll(array('attr'=>array()));
		foreach ($groups as $group) {
			$garray[$group->uid] = array('name' => $group->sstGroupName);
			if ($group->isAssignedToVmPool($_GET['dn'])) {
				$garray[$group->uid]['selected'] = true;
			}
		}
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['dn']);
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmpool', 'Assign groups to VM Pool') . ' \'' . $vmpool->sstDisplayName . '\'</span></div>';
		$dual = $this->createWidget('ext.zii.CJqDualselect', array(
			'id' => 'groupAssignment',
			'values' => $garray,
			'size' => 5,
			'options' => array(
				'sorted' => true,
				'leftHeader' => Yii::t('vmpool', 'Groups'),
				'rightHeader' => Yii::t('vmpool', 'Assigned groups'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'dualselect.css',
		));
		$dual->run();
?>
		<button id="saveGroupAssignment" style="margin-top: 10px; float: left;"></button>
		<div id="errorGroupAssignment" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorGroupMsg"></span></p>
		</div>
		<div id="infoGroupAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoGroupMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionSaveGroupAssign() {
		$this->disableWebLogRoutes();
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['dn']);
		$groupDn = 'ou=groups,' . $_GET['dn'];
		$server = CLdapServer::getInstance();
		$server->delete($groupDn, true, true);
		$getgroups = explode(',', $_GET['groups']);
		foreach($getgroups as $uid) {
			$group = LdapGroup::model()->findByDn('uid=' . $uid . ',ou=groups');
			if (!is_null($group)) {
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
				$data['ou'] = $uid;
				$data['description'] = array('This entry links to the group ' . $group->sstGroupName . '.');
				$data['labeledURI'] = array('ldap:///' . $group->dn);
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn = 'ou=' . $uid . ',' . $groupDn;
				$server->add($dn, $data);
			}
		}
		$json = array('err' => false, 'msg' => Yii::t('vmpool', 'Assignment saved!'));
		$this->sendJsonAnswer($json);
	}

	public function actionDelete() {
		if (isset($_POST['oper']) && 'del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			$pool = CLdapRecord::model('LdapVmPool')->findByDn($dn);
			if (!is_null($pool)) {
				$pool->delete(true);
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'VM Pool \'' . $_POST['dn'] . '\' not found!'));
			}
		}
	}

	public function actionCreate() {
		$model = new VmPoolForm('create');

		$this->performAjaxValidation($model);

		if(isset($_POST['VmPoolForm'])) {
			$model->attributes = $_POST['VmPoolForm'];

			$libvirt = CPhpLibvirt::getInstance();

			$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$model->storagepool)));

			$pool = new LdapVmPool();
			$pool->sstVirtualMachinePool = CPhpLibvirt::getInstance()->generateUUID();
			$pool->sstDisplayName = $model->displayName;
			$pool->description = $model->description;
			$pool->sstVirtualMachinePoolType = $storagepool->sstStoragePoolType;
			if ('dynamic' === $storagepool->sstStoragePoolType) {
				$pool->sstBrokerMinimalNumberOfVirtualMachines = $model->brokerMin;
				$pool->sstBrokerMaximalNumberOfVirtualMachines = $model->brokerMax;
				$pool->sstBrokerPreStartNumberOfVirtualMachines = $model->brokerPreStart;
			}
			else {
				$pool->removeAttributesByObjectClass('sstVirtualMachinePoolDynamicObjectClass');
			}
			$pool->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$pool->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$pool->save();

			$settings = new LdapVmPoolConfigurationSettings();
			$settings->setBranchDn($pool->dn);
			$settings->ou = "settings";
			$settings->save();
				
			$server = CLdapServer::getInstance();
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('nodes');
			$data['description'] = array('This is the Nodes subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=nodes,' . $pool->dn;
			$server->add($dn, $data);

			$basepath = substr($storagepool->sstStoragePoolURI, 7);
			foreach($model->nodes as $nodename) {
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
				$data['ou'] = $nodename;
				$node = CLdapRecord::model('LdapNode')->findByAttributes(array('attr'=>array('sstNode'=>$nodename)));

				$data['description'] = array('This entry links to the node ' . $nodename . '.');
				$data['labeledURI'] = array('ldap:///' . $node->dn);
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn2 = 'ou=' . $nodename . ',' . $dn;
				$server->add($dn2, $data);

				if (false === $libvirt->createStoragePool($node->getLibvirtUri(), $basepath)) {
					echo "ERRORRRRRRRRRRRRRRRRR";
				}
			}
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('ranges');
			$data['description'] = array('This is the Ranges subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=ranges,' . $pool->dn;
			$server->add($dn, $data);
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $model->range;
			$range = CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('attr'=>array('cn'=>$model->range), 'depth'=>true));

			$data['description'] = array('This entry links to the range ' . $model->range . '.');
			$data['labeledURI'] = array('ldap:///' . $range->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn2 = 'ou=' . $model->range . ',' . $dn;
			$server->add($dn2, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('storage pools');
			$data['description'] = array('This is the StoragePool subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=storage pools,' . $pool->dn;
			$server->add($dn, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $model->storagepool;
			$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$model->storagepool)));
			$data['description'] = array('This entry links to the storagepool ' . $model->storagepool . '.');
			$data['labeledURI'] = array('ldap:///' . $storagepool->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn2 = 'ou=' . $model->storagepool . ',' . $dn;
			$server->add($dn2, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('groups');
			$data['description'] = array('This is the assigned groups subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=groups,' . $pool->dn;
			$server->add($dn, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('people');
			$data['description'] = array('This is the assigned people subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=people,' . $pool->dn;
			$server->add($dn, $data);

			//echo '<pre>' . print_r($pool, true) . '</pre>';
			$this->redirect(array('index'));
		}
		else {
/*
			$pools = CLdapRecord::model('LdapStoragePool')->findAll(array('attr'=>array()));
			$storagepools = $this->createDropdownFromLdapRecords($pools, 'sstStoragePool', 'sstDisplayName');
*/
			$ldapnodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));
			$nodes = array();
			foreach($ldapnodes as $node) {
				if ($node->isType('VM-Node')) {
					$nodes[$node->sstNode] = $node->sstNode;
				}
			}
/*
			$allRanges = array();
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			foreach($subnets as $subnet) {
				$ranges = array();
				foreach($subnet->ranges as $range) {
					if ($range->sstNetworkType == 'static') {
						$ranges[$range->cn] = $range->getRangeAsString();
					}
				}
				$allRanges[$subnet->cn . '/' . $subnet->dhcpNetMask] = $ranges;
			}
*/
			$this->render('create',array(
				'model' => $model,
				'storagepools' => array(),
				'nodes' => $nodes,
				'ranges' => array(),
				'types' => array('dynamic'=>'dynamic', 'persistent'=>'persistent', 'template'=>'template')
			));
		}
	}

	public function actionUpdate() {
		$model = new VmPoolForm('update');

		$this->performAjaxValidation($model);

		if(isset($_POST['VmPoolForm'])) {
			$model->attributes = $_POST['VmPoolForm'];

			$libvirt = CPhpLibvirt::getInstance();

			$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$model->storagepool)));

			$pool = CLdapRecord::model('LdapVmPool')->findByDn($_POST['VmPoolForm']['dn']);
			$pool->setOverwrite(true);
			$pool->sstDisplayName = $model->displayName;
			$pool->description = $model->description;
			if ('dynamic' == $storagepool->sstStoragePoolType) {
				$pool->sstBrokerMinimalNumberOfVirtualMachines = $model->brokerMin;
				$pool->sstBrokerMaximalNumberOfVirtualMachines = $model->brokerMax;
				$pool->sstBrokerPreStartNumberOfVirtualMachines = $model->brokerPreStart;
			}
			else {
				$pool->removeAttributesByObjectClass('sstVirtualMachinePoolDynamicObjectClass');
			}
			$pool->save();
			$pool->deleteNodes();

			$server = CLdapServer::getInstance();
			$dn = 'ou=nodes,' . $pool->dn;
			$basepath = substr($storagepool->sstStoragePoolURI, 7);
			foreach($model->nodes as $nodename) {
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
				$data['ou'] = $nodename;
				$node = CLdapRecord::model('LdapNode')->findByAttributes(array('attr'=>array('sstNode'=>$nodename)));

				$data['description'] = array('This entry links to the node ' . $nodename . '.');
				$data['labeledURI'] = array('ldap:///' . $node->dn);
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn2 = 'ou=' . $nodename . ',' . $dn;
				$server->add($dn2, $data);

				if (false === $libvirt->createStoragePool($node->getLibvirtUri(), $basepath)) {
					echo "ERRORRRRRRRRRRRRRRRRR";
				}

			}
			if (!is_null($model->range)) {
				$pool->deleteRanges();
				$dn = 'ou=ranges,' . $pool->dn;
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
				$data['ou'] = $model->range;
				$range = CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('attr'=>array('cn'=>$model->range)));

				$data['description'] = array('This entry links to the range ' . $model->range . '.');
				$data['labeledURI'] = array('ldap:///' . $range->dn);
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn2 = 'ou=' . $model->range . ',' . $dn;
				$server->add($dn2, $data);
			}
/*
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('storage pools');
			$data['description'] = array('This is the StoragePool subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=storage pools,' . $pool->dn;
			$server->add($dn, $data);
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $model->storagepool;
			$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$model->storagepool)));

			$data['description'] = array('This entry links to the storagepool ' . $model->storagepool . '.');
			$data['labeledURI'] = array('ldap:///' . $storagepool->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn2 = 'ou=' . $model->storagepool . ',' . $dn;
			$server->add($dn2, $data);
*/
			//echo '<pre>' . print_r($pool, true) . '</pre>';
			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$pool = CLdapRecord::model('LdapVmPool')->findbyDn($_GET['dn']);
			}
			if($pool === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$model->dn = $pool->dn;
			$model->type = $pool->sstVirtualMachinePoolType;
			$model->displayName = $pool->sstDisplayName;
			$model->description = $pool->description;
			//echo '<pre>' . print_r($pool->ranges, true) . '</pre>';
			$model->range = $pool->ranges[0]->ou;
			$allRanges = array();
			if (0 < count($pool->storagepools)) {
				//echo '<pre>' . print_r($pool->storagepools, true) . '</pre>';
				//$model->storagepool = $pool->storagepools[0]->ou;
				//$storagepool = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$model->storagepool)));
				$model->storagepool = $pool->storagepools[0]->ou;
				$storagepool = $pool->getStoragePool();
				$type = $storagepool->sstStoragePoolType;
				$allRanges = $this->getRangesByType($type, $pool->ranges);
			}
			$model->nodes = array();
			foreach($pool->nodes as $node) {
				$model->nodes[] = $node->ou;
			}
			if (0 < count($pool->ranges)) {
				//echo '<pre>' . print_r($pool->ranges, true) . '</pre>';
				$model->range = $pool->ranges[0]->ou;
				//echo $pool->ranges[0]->ou;
			}
			if ('dynamic' == $type) {
				$model->brokerMin = $pool->sstBrokerMinimalNumberOfVirtualMachines;
				$model->brokerMax = $pool->sstBrokerMaximalNumberOfVirtualMachines;
				$model->brokerPreStart = $pool->sstBrokerPreStartNumberOfVirtualMachines;
			}

			$pools = CLdapRecord::model('LdapStoragePool')->findAll(array('attr'=>array()));
			$storagepools = $this->createDropdownFromLdapRecords($pools, 'sstStoragePool', 'sstDisplayName');

			$ldapnodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));
			$nodes = array();
			foreach($ldapnodes as $node) {
				if ($node->isType('VM-Node')) {
					$nodes[$node->sstNode] = $node->sstNode;
				}
			}
			$this->render('update',array(
				'model' => $model,
				'storagepools' => $storagepools,
				'nodes' => $nodes,
				'ranges' => $allRanges,
				'types' => array('dynamic'=>'dynamic', 'persistent'=>'persistent', 'template'=>'template'),
				'vmcount' => count($pool->vms)
			));
		}
	}

	private function getRangesByType($type, $ownRanges) {
		$allRanges = array();
		$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
		foreach($subnets as $subnet) {
			$ranges = array();
			foreach($subnet->ranges as $range) {
				if ($range->sstNetworkType == $type && (!$range->isUsed() || (0 < count($ownRanges) && $ownRanges[0]->ou == $range->cn))) {
					$ranges[$range->cn] = $range->getRangeAsString();
				}
			}
			if (0 < count($ranges)) {
				$allRanges[$subnet->cn . '/' . $subnet->dhcpNetMask] = $ranges;
			}
		}
		return $allRanges;
	}
}