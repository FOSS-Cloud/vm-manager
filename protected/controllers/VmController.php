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
 * VmController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class VmController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'vm';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('update' == $action) {
			$submenu = '';
			if ('persistent' == $_GET['vmtype']) {
				$submenu = 'vm';
			}
			else {
				$submenu = 'vmdyn';
			}
			$this->submenu['vm']['items'][$submenu]['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Virtual Machine Update Tooltip')),
				'active' => true,
			);

		}
		if ('index' == $action) {
			$this->submenu['links'] = array(
				'label' => Yii::t('menu', 'Links'),
				'static' => true,
				'items' => array(
					array(
						'label' => Yii::t('menu', 'Download Spice Client'),
						'url' => 'http://www.foss-cloud.org/en/index.php/Spice-Client',
						'itemOptions' => array('title' => Yii::t('menu', 'Spice Client Tooltip')),
					)
				)
			);
		}
		$this->activesubmenu = 'vm';
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
				'actions'=>array('index', 'view', 'update', 'delete', 'template', 'profile',
					'getPoolInfo', 'getVms', 'getVmInfo', 'refreshTimeout', 'refreshVms', 'getUserGui', 'saveUserAssign', 'getGroupGui', 'saveGroupAssign', 'getNodeGui',
					'saveVm', 'startVm', 'shutdownVm', 'rebootVm', 'destroyVm', 'migrateVm',
					'makeGolden', 'activateGolden'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$vmtype = (isset($_GET['vmtype']) ? $_GET['vmtype'] : 'persistent');
		if ('persistent' != $vmtype && 'dynamic' != $vmtype) {
			$vmtype = 'persistent';
		}
		$criteria = array('attr'=>array('sstVirtualMachinePoolType' => $vmtype));
		$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$vmpool = Yii::app()->getSession()->get('vm.index.' . $vmtype . '.vmpool', null);
		if (is_null($vmpool) && 1 == count($vmpools)) {
			$vmpool = $vmpools[0]->sstVirtualMachinePool;
		}
		if (!is_null($vmpool)) {
			Yii::app()->getSession()->add('vm.index.' . $vmtype . '.vmpool', $vmpool);
		}
		else {
			$vmpool = '???';
		}
		$this->render('index', array(
			'vmtype' => $vmtype,
			'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
			'vmpool' => $vmpool,
		));
	}

	public function actionView() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapVm')->findbyDn($_GET['dn']);
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');

		$criteria = array('attr'=>array());
		$user = CLdapRecord::model('LdapUser')->findAll($criteria);

		$this->render('view',array(
			'model' => $model,
			'user' => $user
		));
	}

	public function actionUpdate() {
		$model = new VmForm('update');

		if(isset($_GET['dn'])) {
			$model->dn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidation($model);

		if(isset($_POST['VmForm'])) {
			$model->attributes = $_POST['VmForm'];

			$result = CLdapRecord::model('LdapVm')->findByDn($_POST['VmForm']['dn']);
			$result->setOverwrite(true);
			$result->sstClockOffset = $model->sstClockOffset;
			$result->sstMemory = $model->sstMemory;
			$result->sstVCPU = $model->sstVCPU;
			$result->description = $model->description;
			$result->sstDisplayName = $model->name;
			//$result->sstNode = $model->node;
			$result->save();

			$rdevices = $result->devices;
			foreach($rdevices->disks as $rdisk) {
				if ('disk' == $rdisk->sstDevice) {
					$rdisk->setOverwrite(true);
					$rdisk->sstVolumeCapacity = $model->sstVolumeCapacity;
					$rdisk->save();
				}
			}

			if ($model->useStaticIP) {
				$dhcpvm = $result->dhcp;
				$dhcpvm->dhcpStatements = 'fixed-address ' . $model->staticIP;
				if (!$dhcpvm->subnet->inRange($model->staticIP)) {
					$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
					foreach ($subnets as $subnet) {
						if ($subnet->inRange($model->staticIP)) {
							$dhcpvm->save();
							$dhcpvm->move('ou=virtual machines,' . $subnet->dn);
							break;
						}
					}
				}
				else {
					$dhcpvm->save();
				}
			}

			$this->redirect(array('index', 'vmtype'=>$result->sstVirtualMachineType));
		}
		else {
			$nodes = array();
			$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));

			$vm = CLdapRecord::model('LdapVm')->findbyDn($_GET['dn']);
			$defaults = $vm->defaults;
			//$subnet = $vm->dhcp->subnet;
			$allRanges = array('' => '');
/*
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			foreach($subnets as $subnet) {
				$ranges = array();
				foreach($subnet->ranges as $range) {
					if ($range->sstNetworkType == 'persistent') {
						$ranges[$range->cn] = $range->getRangeAsString();
					}
				}
				$allRanges[$subnet->cn . '/' . $subnet->dhcpNetMask] = $ranges;
			}
*/
			$range = $vm->vmpool->ranges[0];
			$range = CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('depth'=>true,'attr'=>array('cn'=>$range->ou)));
			$subnet = $range->subnet;
			$allRanges[$subnet->cn . '/' . $subnet->dhcpNetMask] = array($range->cn => $range->getRangeAsString());

			$model->dn = $vm->dn;
			$model->type = $vm->sstVirtualMachineType;
			$model->subtype = $vm->sstVirtualMachineSubType;
			$model->node = $vm->sstNode;
			$model->ip = $vm->dhcp->dhcpStatements['fixed-address'];
			$model->name = $vm->sstDisplayName;
			$model->description = $vm->description;
			//echo '<pre>' . print_r($profile, true) . '</pre>';
			//echo '<pre>' . print_r($defaults, true) . '</pre>';
			$model->sstClockOffset = $vm->sstClockOffset;
			$model->sstMemory = $vm->sstMemory;
			$model->sstVCPU = $vm->sstVCPU;
			$result = $vm->devices->getDiskByName('vda');
			if (isset($result->sstVolumeCapacity)) {
				$model->sstVolumeCapacity = $result->sstVolumeCapacity;
				$defaults->setVolumeCapacityMin($result->sstVolumeCapacity, true);
			}
			else {
				$model->sstVolumeCapacity = $defaults->VolumeCapacityMin;
			}

			$this->render('update',array(
				'model' => $model,
				'nodes' => $this->createDropdownFromLdapRecords($nodes, 'sstNode', 'sstNode'),
				'ranges' => $allRanges,
				'defaults' => $defaults,
			));
		}
	}

	public function actionDelete() {
		$this->disableWebLogRoutes();
		if ('del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			$vm = CLdapRecord::model('LdapVm')->findByDn($dn);
			if (!is_null($vm)) {
				if (!$vm->isActive()) {
					// delete sstDisk=vda->sstSourceFile
					$vda = $vm->devices->getDiskByName('vda');
					$libvirt = CPhpLibvirt::getInstance();
					if (!$libvirt->deleteVolumeFile($vda->sstSourceFile)) {
						$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Unable to delete Volume File for Vm \'' . $vm->sstDisplayName . '\'!'));
					}
					else {
						// delete IP
						//echo '<pre>delete IP ' . print_r($vm->dhcp, true) . '</pre>';
						if (!is_null($vm->dhcp)) {
							$vm->dhcp->delete();
						}

						// delete User assign
/*
						$criteria = array(
							'branchDn'=>'ou=people,ou=' . Yii::app()->user->realm . ',ou=authentication,ou=virtualization,ou=services',
							'depth'=>true,
							'attr'=>array('sstVirtualMachinePool'=>$vm->sstVirtualMachinePool));
						$userAssigns = CLdapRecord::model('LdapUserAssignVmPool')->findAll($criteria);
						foreach($userAssigns as $userAssign) {
							$userAssign->removeVmAssignment($vm->sstVirtualMachine);
						}
*/
						
						$libvirt->undefineVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));

						// delete VM
						$vm->delete(true);
					}
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Vm \'' . $vm->sstDisplayName . '\' is running!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Vm \'' . $_POST['dn'] . '\' not found!'));
			}
		}
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vm-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Ajax functions for JqGrid
	 */
	public function actionGetVMs() {
		$this->disableWebLogRoutes();
		if (isset($_GET['time'])) {
			$session = Yii::app()->getSession();
			$session->add('vm_refreshtime', (int) $_GET['time']);
		}

		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		$criteria = array('attr'=>array());
		if (isset($_GET['vmtype'])) {
			$criteria['attr']['sstVirtualMachineType'] = $_GET['vmtype'];
		}
		if (isset($_GET['vmpool'])) {
			$criteria['attr']['sstVirtualMachinePool'] = $_GET['vmpool'];
			Yii::app()->getSession()->add('vm.index.' . $_GET['vmtype'] . '.vmpool', $_GET['vmpool']);
		}
		if (isset($_GET['sstDisplayName'])) {
			$criteria['attr']['sstDisplayName'] = '*' . $_GET['sstDisplayName'] . '*';
		}
		if (isset($_GET['sstNode'])) {
			$criteria['attr']['sstNode'] = '*' . $_GET['sstNode'] . '*';
		}
		if ($sidx != '')
		{
			$criteria['sort'] = $sidx . '.' . $sord;
		}
		$vms = LdapVm::model()->findAll($criteria);
		$count = count($vms);

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
		$start = $limit * $page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;

		$vms = LdapVm::model()->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  "<rows>";
		$s .= "<page>" . $page . "</page>";
		$s .= "<total>" . $total_pages . "</total>";
		$s .= "<records>" . $count . "</records>";

		$i = 1;
		foreach ($vms as $vm) {
			//	'colNames'=>array('No.', 'DN', 'UUID', , 'Spice', 'Type', 'SubType', 'active Golden-Image', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i ."</cell>\n";
			$s .= '<cell>'.$vm->dn ."</cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachine ."</cell>\n";
			$s .= '<cell><![CDATA['. $vm->getSpiceUri() . "]]></cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachineType ."</cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachineSubType ."</cell>\n";
			if ('Golden-Image' == $vm->sstVirtualMachineSubType) {
				$s .= '<cell>' . ($vm->sstVirtualMachine == $vm->vmpool->sstActiveGoldenImage ? 'true' : 'false') . "</cell>\n";
			}
			else {
				$s .= "<cell></cell>\n";
			}
			$s .= '<cell>'. $vm->formatCreateTimestamp('d.m.Y H:i:s') ."</cell>\n";
			//$s .= "<cell>???</cell>\n";
			if (0 == count($vm->people)) {
				$s .= "<cell></cell>\n";
			}
			else {
				$uid = $vm->people[0]->ou;
				$user = LdapUser::model()->findByAttributes(array('attr'=>array('uid' => $uid)));
				$s .= '<cell>' . $user->cn . ' (' . $user->uid . ")</cell>\n";
			}
			$s .= '<cell>'. $vm->sstDisplayName ."</cell>\n";
			$s .= "<cell>unknown</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= '<cell>'. $vm->sstNode ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
			$i++;
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVmInfo() {
		//sleep(5);
		$this->disableWebLogRoutes();
		$dn = $_GET['dn'];
		$vm = CLdapRecord::model('LdapVm')->findByDn($dn);
		$rowid  = $_GET['rowid'];

		$ip = '???';
		$network = $vm->network;
		if (!is_null($network)) {
			//echo '<pre>Network: ' . print_r($network, true) . '</pre>';
			$ip = $network->dhcpstatements['fixed-address'];
		}
		$memory = $this->getHumanSize($vm->sstMemory);
		$loading = $this->getImageBase() . '/loading.gif';

		/*
		 * with cpu graph
      <td style="text-align: right"><b>Memory:</b></td>
      <td>$memory</td>
      <td rowspan="3" style="text-align: right"><b>CPU:</b></td>
      <td rowspan="3" style="height: 53px;" id="cpu2_$rowid"><img src="{$loading}" alt="" /></td>

		 */
		echo <<<EOS
    <table style="margin-bottom: 0px; font-size: 90%; width: auto;"><tbody>
    <tr>
      <td style="text-align: right"><b>Type:</b></td>
      <td>{$vm->sstVirtualMachineType}, {$vm->sstVirtualMachineSubType}</td>
      <td style="text-align: right"><b>VM UUID:</b></td>
      <td>{$vm->sstVirtualMachine}</td>
    </tr>
    <tr>
      <td style="text-align: right"><b>Memory:</b></td>
      <td>$memory</td>
      <td style="text-align: right"><b>VM Pool:</b></td>
      <td>{$vm->sstVirtualMachinePool}</td>
    </tr>
    <tr>
      <td style="text-align: right"><b>CPUs:</b></td>
      <td>{$vm->sstVCPU}</td>
    </tr>
EOS;
		if ('Golden-Image' !== $vm->sstVirtualMachineSubType) {
			echo <<< EOS
    <tr>
      <td style="text-align: right"><b>IP Adress:</b></td>
      <td>$ip</td>
    </tr>
EOS;
		}
		echo '</tbody></table>';
		if (!is_null($vm->backup)) {
			echo <<< EOS
	<h3>Backups</h3>
	<table style="margin-bottom: 0px; width: auto;"><tbody>
    <tr>
      <th style="text-align: center"><b>Date</b></th>
      <th style="text-align: center"><b>State</b></th>
      <th style="text-align: center"><b>Action</b></th>
					</tr>
			
EOS;
			foreach($vm->backup->backups as $backup) {
				echo '<tr><td>' . $backup->ou . '</td><td>' . $backup->sstProvisioningMode . '</td>';
				echo '<td>&nbsp;</td></tr>';
			}
			echo '</tbody></table>';
		}
	}

	public function actionGetUserGui() {
		$this->disableWebLogRoutes();
		$uarray = array();
		$users = LdapUser::model()->findAll(array('attr'=>array()));
		foreach ($users as $user) {
			$uarray[$user->uid] = array('name' => $user->getName() . ($user->isAdmin() ? ' (Admin)' : ' (User)') . ($user->isForeign() ? '(E)' : ''));
			if ($user->isAssignedToVm($_GET['dn'])) {
				$uarray[$user->uid]['selected'] = true;
			}
		}
		$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Assign users to VM') . ' \'' . $vm->sstDisplayName . '\'</span></div>';
		$dual = $this->createWidget('ext.zii.CJqDualselect', array(
			'id' => 'userAssignment',
			'values' => $uarray,
			'size' => 6,
			'options' => array(
				'sorted' => true,
				'leftHeader' => Yii::t('vm', 'Users'),
				'rightHeader' => Yii::t('vm', 'Assigned users'),
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

	public function actionGetNodeGui() {
		$this->disableWebLogRoutes();
		$narray = array();
		$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
		$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));
		foreach ($nodes as $node) {
			if ($node->sstNode != $vm->sstNode && $node->isType('VM-Node')) {
				$narray[$node->dn] = array('name' => $node->sstNode);
			}
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Migrate VM "{name}"', array('{name}' => $vm->sstDisplayName)) . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'nodeSelection',
			'values' => $narray,
			'multiselect' => false,
			'size' => 7,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'Nodes'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
?>
		<br/>
		<button id="migrateNode" style="margin-top: 10px; float: left;"></button>
		<div id="errorNode" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorNodeMsg" style="display: block;"></span></p>
		</div>
		<div id="infoNode" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoNodeMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionSaveUserAssign() {
		$this->disableWebLogRoutes();
		$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
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
		$json = array('err' => false, 'msg' => Yii::t('vm', 'Assignment saved!'));
		$this->sendJsonAnswer($json);
	}

	public function actionGetGroupGui() {
		$this->disableWebLogRoutes();
		$garray = array();
		$groups = LdapGroup::model()->findAll(array('attr'=>array()));
		foreach ($groups as $group) {
			$garray[$group->uid] = array('name' => $group->sstGroupName);
			if ($group->isAssignedToVm($_GET['dn'])) {
				$garray[$group->uid]['selected'] = true;
			}
		}
		$vm = LdapVm::model()->findByDn($_GET['dn']);
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmpool', 'Assign groups to VM') . ' \'' . $vm->sstDisplayName . '\'</span></div>';
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

	public function actionSaveVm() {
		if (isset($_POST['oper']) && '' != $_POST['oper']) {
			switch($_POST['oper']) {
				case 'edit':
					break;
				case 'del':

					break;
			}
		}
	}

	public function actionRefreshTimeout() {
		if (isset($_GET['time'])) {
			$session = Yii::app()->getSession();
			$session->add('vm_refreshtime', (int) $_GET['time']);
		}
	}

	private $status = array('unknown', 'stopped', 'running', 'migrating', 'shutdown');

	public function actionRefreshVms() {
		$this->disableWebLogRoutes();
		$data = array();
		if (isset($_GET['time'])) {
			$session = Yii::app()->getSession();
			$session->add('vm_refreshtime', (int) $_GET['time']);
		}
		if (isset($_GET['dns'])) {
			$dns = explode(';', $_GET['dns']);
			foreach($dns as $dn) {
				//echo "DN: $dn";
				$vm = CLdapRecord::model('LdapVm')->findByDn($dn);
				//echo '<pre>' . print_r($vm, true) . '</pre>';
				if (!is_null($vm)) {
					$answer = array(/* 'type' => $vm->sstVirtualMachineType, 'subtype' => $vm->sstVirtualMachineSubType,*/ 'node' => $vm->sstNode, 'statustxt' => '');
					$checkStatus = true;
					if ('dynamic' == $vm->sstVirtualMachineType) {
						switch($vm->sstVirtualMachineSubType) {
							case 'Golden-Image':
								$answer['status'] = 'golden';
								$answer['node'] = '';
								if ($vm->sstVirtualMachine == $vm->vmpool->sstActiveGoldenImage) {
									$answer['statustxt'] = ', active';
									$answer['status'] = 'golden_active';
								}
								$checkStatus = false;
								break;
							case 'System-Preparation':
								$answer['statustxt'] = ', sys-prep';
								break;
							default:
								if (0 == count($vm->people)) {
									$answer['statustxt'] = ', free';
								}
								else {
									$uid = $vm->people[0]->ou;
									$user = LdapUser::model()->findByAttributes(array('attr'=>array('uid' => $uid)));
									$answer['statustxt'] = ', ' . $user->cn;
								}
								break;
						}
						$data[$vm->sstVirtualMachine] = $answer;
					}
					if ($checkStatus) {
						$libvirt = CPhpLibvirt::getInstance();
						if ($status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
							if ($status['active']) {
								$memory = $this->getHumanSize($status['memory'] * 1024);
								$maxmemory = $this->getHumanSize($status['maxMem'] * 1024);
								//$data[$vm->sstVirtualMachine] = array('status' => ($status['active'] ? 'running' : 'stopped'), 'mem' => $memory . ' / ' . $maxmemory, 'cpu' => $status['cpuTime'], 'cpuOrig' => $status['cpuTimeOrig']);
								$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'running', 'mem' => $memory . ' / ' . $maxmemory, 'spice' => $vm->getSpiceUri()));
							}
							else if ('dynamic' == $vm->sstVirtualMachineType && ('Desktop' == $vm->sstVirtualMachineSubType || 'Server' == $vm->sstVirtualMachineSubType)) {
//								$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'removed'));

								// delete User assign
/*
								$criteria = array(
									'branchDn'=>'ou=people,ou=' . Yii::app()->user->realm . ',ou=authentication,ou=virtualization,ou=services',
									'depth'=>true,
									'attr'=>array('sstVirtualMachinePool'=>$vm->sstVirtualMachinePool));
								$userAssigns = CLdapRecord::model('LdapUserAssignVmPool')->findAll($criteria);
								foreach($userAssigns as $userAssign) {
									$userAssign->removeVmAssignment($vm->sstVirtualMachine);
								}
*/
//								@unlink($vm->devices->getDiskByName('vda')->sstSourceFile);
//								$vm->dhcp->delete(true);
//								$vm->delete(true);
							}
							else {
								$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'stopped'));
							}
						}
						else {
							$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt getVmStatus failed!'));
						}
					}
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $dn . '\' not found!'));
				}
			}
		}
		$this->sendJsonAnswer($data);
	}

	public function actionStartVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			//$devices = $vm->devices[0];
			//echo '$devices <pre>' . print_r($devices, true) . '</pre>';
			//$disks = $devices->disks;
			//echo '<pre>' . print_r($devices->disks, true) . '</pre>';
			//$interfaces = $devices->interfaces;
			//echo '<pre>' . print_r($devices->interfaces, true) . '</pre>';
			if (!is_null($vm)) {
				//echo '<pre>' . print_r($params, true) . '</pre>';
				$retval = false;
				$answer = array();
				$libvirt = CPhpLibvirt::getInstance();
				if ('persistent' == $vm->sstVirtualMachineType) {
					$retval = $libvirt->startVm($vm->getStartParams());
				}
				else {
					if ('Golden-Image' == $vm->sstVirtualMachineSubType) {
						//$vm->setOverwrite(true);

						$vmpool = $vm->vmpool;
						$storagepool = $vmpool->getStoragePool();
						if (is_null($storagepool)) {
							$this->sendAjaxAnswer(array('error' => 1, 'message' => 'No storagepool found for selected vmpool!'));
							return;
						}

						// 'save' devices before
						$rdevices = $vm->devices;
						/* Create a copy to be sure that we will write a new record */
						$vmcopy = new LdapVm();
						/* Don't change the labeledURI; must refer to a default Profile */
						$vmcopy->attributes = $vm->attributes;

						$vmcopy->setOverwrite(true);
						$vmcopy->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
						$vmcopy->sstVirtualMachineType = 'dynamic';
						$vmcopy->sstVirtualMachineSubType = 'Desktop';

						// necessary ?
						$vmcopy->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
						/* Delete all objectclasses and let the LdapVM set them */
						$vmcopy->removeAttribute(array('objectClass', 'member'));
						$vmcopy->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

						// necessary ?
						$vmcopy->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
						// necessary ?
						$vmcopy->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
						// necessary ?
						$vmcopy->sstOsBootDevice = 'hd';
						$vmcopy->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($vmcopy->sstNode);
						$vmcopy->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
						$vmcopy->save();

						$settings = new LdapVmPoolConfigurationSettings();
						$settings->setBranchDn($vmcopy->dn);
						$settings->ou = "settings";
						$settings->save();
				
						$devices = new LdapVmDevice();
						$devices->setOverwrite(true);
						$devices->attributes = $rdevices->attributes;
						$devices->setBranchDn($vmcopy->dn);
						$devices->save();

						// Workaround to get Node
						$vmcopy = CLdapRecord::model('LdapVm')->findByDn($vmcopy->getDn());

						$names = array();
						foreach($rdevices->disks as $rdisk) {
							$disk = new LdapVmDeviceDisk();
							//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
							$disk->setOverwrite(true);
							$disk->attributes = $rdisk->attributes;
							if ('disk' == $disk->sstDevice) {
								$templatesdir = substr($storagepool->sstStoragePoolURI, 7);
								//$goldenimagepath = $vm->devices->getDiskByName('vda')->sstSourceFile;
								$goldenimagepath = $vm->devices->getDiskByName('vda')->sstVolumeName . '.qcow2';
								$names = CPhpLibvirt::getInstance()->createBackingStoreVolumeFile($templatesdir, $storagepool->sstStoragePool, $goldenimagepath, $vmcopy->node->getLibvirtUri(), $disk->sstVolumeCapacity);
								if (false !== $names) {
									$disk->sstVolumeName = $names['VolumeName'];
									$disk->sstSourceFile = $names['SourceFile'];
								}
								else {
									$hasError = true;
									$vmcopy->delete(true);
									$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Unable to create backingstore volume!'));
									break;
								}
							}
							$disk->setBranchDn($devices->dn);
							$disk->save();
						}
						if (!$hasError) {
							$firstMac = null;
							foreach($rdevices->interfaces as $rinterface) {
								$interface = new LdapVmDeviceInterface();
								$interface->attributes = $rinterface->attributes;
								$interface->setOverwrite(true);
								$interface->sstMacAddress = CPhpLibvirt::getInstance()->generateMacAddress();
								if (is_null($firstMac)) {
									$firstMac = $interface->sstMacAddress;
								}
								$interface->setBranchDn($devices->dn);
								$interface->save();
							}

							$range = $vmpool->getRange();
							if (is_null($range)) {
								$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vm', 'No range found for VMPool!')));
								return;
							}
							$dhcpvm = new LdapDhcpVm();
							$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
							$dhcpvm->cn = $vmcopy->sstVirtualMachine;
							$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
							$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
							$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

							$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
							$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
							$dhcpvm->save();

							$retval = $libvirt->startVm($vmcopy->getStartParams());
							$answer['refresh'] = 1;
						}
					}
					else {
						$retval = $libvirt->startVm($vm->getStartParams());
					}
				}
				if (false !== $retval) {
					$answer['error'] = 0;
					$this->sendAjaxAnswer($answer);
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt startVm failed (' . $libvirt->getLastError() . ')!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionRebootVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->rebootVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt rebootVm failed (' . $libvirt->getLastError() . ')!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionShutdownVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->shutdownVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt shutdownVm failed (' . $libvirt->getLastError() . ')!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionDestroyVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ('persistent' == $vm->sstVirtualMachineType) {
					if ($libvirt->destroyVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
						$this->sendAjaxAnswer(array('error' => 0));
					}
					else {
						$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt destroyVm failed (' . $libvirt->getLastError() . ')!'));
					}
				}
				else if ('Desktop' == $vm->sstVirtualMachineSubType || 'Server' == $vm->sstVirtualMachineSubType) {
					if ($libvirt->destroyVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {

						// delete User assign
/*
						$criteria = array(
							'branchDn'=>'ou=people,ou=' . Yii::app()->user->realm . ',ou=authentication,ou=virtualization,ou=services',
							'depth'=>true,
							'attr'=>array('sstVirtualMachinePool'=>$vm->sstVirtualMachinePool));
						$userAssigns = CLdapRecord::model('LdapUserAssignVmPool')->findAll($criteria);
						foreach($userAssigns as $userAssign) {
							$userAssign->removeVmAssignment($vm->sstVirtualMachine);
						}
*/
						@unlink($vm->devices->getDiskByName('vda')->sstSourceFile);
						$vm->dhcp->delete(true);
						$vm->delete(true);
						$this->sendAjaxAnswer(array('error' => 0, 'refresh' => 1));
					}
					else {
						$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt destroyVm failed (' . $libvirt->getLastError() . ')!'));
					}
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionMigrateVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if ('undefined' == $_GET['newnode']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a node!'));
				return;
			}

			$newnode = CLdapRecord::model('LdapNode')->findByDn($_GET['newnode']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$move = false;
					if ($status['active']) {
						if ($libvirt->migrateVm(array('libvirt' => $vm->node->getLibvirtUri(), 'newlibvirt' => $newnode->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
							$move = true;
						}
						else {
							$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt migrateVm failed (' . $libvirt->getLastError() . ')!'));
						}
					}
					else {
						$move = true;
					}
					if ($move) {
						$libvirt->undefineVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));
						$vm->setOverwrite(true);
						$vm->sstNode = $newnode->sstNode;
						$vm->sstSpicePort = $libvirt->nextSpicePort($newnode->sstNode);
						$vm->save();
						
						$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
						$libvirt->defineVm($vm->getStartParams());
						$this->sendAjaxAnswer(array('error' => 0, 'message' => Yii::t('vm', 'Migration finished'), 'refresh' => 1));
					}
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt unable to check status of VM!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt VM with dn=\'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionMakeGolden() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = LdapVm::model()->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$subType = $vm->sstVirtualMachineSubType;
				$vm->setOverwrite(true);
				$vm->sstVirtualMachineSubType = 'Golden-Image';
				$vm->save(true, array('sstVirtualMachineSubType'));
				if ('System-Preparation' == $subType) {
					$vm->dhcp->delete();
				}
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionActivateGolden() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$vmpool = $vm->vmpool;
				if ($vm->sstVirtualMachine != $vmpool->sstActiveGoldenImage) {
					$vmpool->setOverwrite(true);
					$vmpool->sstActiveGoldenImage = $vm->sstVirtualMachine;
					$vmpool->save(true, array('sstActiveGoldenImage'));
					$json = array('err' => false, 'msg' => Yii::t('vm', 'Golden Image activated!'));
				}
				else {
					$json = array('err' => true, 'msg' => Yii::t('vm', 'Golden Image already activated!'));
				}
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'VM with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		else {
			$json = array('err' => true, 'msg' => Yii::t('vm', 'Parameter dn not found!'));
		}
		$this->sendJsonAnswer($json);
	}
}