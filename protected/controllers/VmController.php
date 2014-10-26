<?php
/*
 * Copyright (C) 2006 - 2014 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or higher - as soon they
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
						'url' => 'http://www.foss-cloud.org/en/wiki/Spice-Client',
						'itemOptions' => array('title' => Yii::t('menu', 'Spice Client Tooltip')),
						'linkOptions' => array('target' => '_blank'),
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
			array('allow',
				'actions'=>array('index'),
				'users'=>array('@'),
				'expression'=>'(isset($_GET[\'vmtype\']) && \'dynamic\' === $_GET[\'vmtype\'] && $user->hasRight(\'dynamicVM\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL)) || ' . 
					'((!isset($_GET[\'vmtype\']) || \'persistent\' === $_GET[\'vmtype\']) && $user->hasRight(\'persistentVM\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL))'
			),
			array('allow',
				'actions'=>array('view', 'getPoolInfo', 'getVms', 'getVmInfo', 'refreshTimeout', 'refreshVms'),
				'users'=>array('@'),
				'expression'=>'$user->hasRight(\'dynamicVM\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL) || ' . 
					'$user->hasRight(\'persistentVM\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('update', 'makeGolden', 'activateGolden', 'getUserGui', 'saveUserAssign', 'getGroupGui', 'saveGroupAssign', 'getNodeGui'),
				'users'=>array('@'),
				'expression'=>'$user->hasRight(\'dynamicVM\', COsbdUser::$RIGHT_ACTION_EDIT, COsbdUser::$RIGHT_VALUE_ALL) || ' . 
					'$user->hasRight(\'persistentVM\', COsbdUser::$RIGHT_ACTION_EDIT, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('delete'),
				'users'=>array('@'),
				'expression'=>'$user->hasRight(\'dynamicVM\', COsbdUser::$RIGHT_ACTION_DELETE, COsbdUser::$RIGHT_VALUE_ALL) || ' . 
					'$user->hasRight(\'persistentVM\', COsbdUser::$RIGHT_ACTION_DELETE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('startVm', 'shutdownVm', 'rebootVm', 'destroyVm', 'migrateVm', 'restoreVm', 'waitForRestoreAction', 'getRestoreAction', 'startRestoreAction', 'cancelRestoreAction', 'handleRestoreAction'),
				'users'=>array('@'),
				'expression'=>'$user->hasRight(\'dynamicVM\', COsbdUser::$RIGHT_ACTION_MANAGE, COsbdUser::$RIGHT_VALUE_ALL) || ' . 
					'$user->hasRight(\'persistentVM\', COsbdUser::$RIGHT_ACTION_MANAGE, COsbdUser::$RIGHT_VALUE_ALL)'
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
		$sessionvars = Yii::app()->getSession()->get('vm.' . $vmtype . '.index', array('page' => 1, 
			'refreshTime' => 10000, 
			'filter' => array('pool' => null, 'name' => null, 'node' => null)
		));
		
		$vmpool = null;
		if (isset($_GET['vmpool'])) {
			if ('' !== $_GET['vmpool']) {
				$sessionvars['filter']['pool'] = $_GET['vmpool'];
				$vmpool = $_GET['vmpool'];
			}
			else {
				$vmpool = null;
			}
		}
		else {
			$vmpool = $sessionvars['filter']['pool'];
		}
		//$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$vmpools = LdapVmPool::getAssignedPools($vmtype);
		$vmpools = array_values($vmpools);
		if (is_null($vmpool) && 1 === count($vmpools)) {
			$vmpool = $vmpools[0]->sstVirtualMachinePool;
		}
		if ('dynamic' === $vmtype) {
			$hasGoldenImage = false;
		}
		else {
			$hasGoldenImage = null;
		}
		if (!is_null($vmpool)) {
			//Yii::app()->getSession()->add('vm.index.' . $vmtype . '.vmpool', $vmpool);
			$sessionvars['filter']['pool'] = $vmpool;
			if ('dynamic' == $vmtype) {
				$criteria = array('attr'=>array('sstVirtualMachinePool' => $vmpool));
				$pool = LdapVmPool::model()->findByAttributes($criteria);
				if (!is_null($pool)) {
					$hasGoldenImage = isset($pool->sstActiveGoldenImage);
				}
			}
		}
		else {
			$hasGoldenImage = null;
//			$vmpool = '???';
		}
		Yii::app()->getSession()->add('vm.' . $vmtype . '.index', $sessionvars);
		$this->render('index', array(
			'vmtype' => $vmtype,
			'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
			'vmpool' => $vmpool,
			'hasGoldenImage' => $hasGoldenImage,
			'sessionvars' => $sessionvars
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

			// reload because of the node bug
			$vm = CLdapRecord::model('LdapVm')->findByDn($_POST['VmForm']['dn']);
			$data = $vm->getStartParams();
			$data['name'] = $data['sstName'];
			CPhpLibvirt::getInstance()->redefineVm($data);
				
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

						if ('Golden-Image' == $vm->sstVirtualMachineSubType) {
							$criteria = array('attr' => array('sstActiveGoldenImage' => $vm->sstVirtualMachine));
							$pools = LdapVmPool::model()->findAll($criteria);
							$server = CLdapServer::getInstance();
							foreach($pools as $pool) {
								$data = array('sstActiveGoldenImage' => array());
								$server->modify_del($pool->dn, $data);
							}
						}
						
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
	public function actionGetVms() {
		$this->disableWebLogRoutes();
		if (isset($_GET['vmtype'])) {
			$vmtype = $_GET['vmtype'];
		}
		$sessionvars = Yii::app()->getSession()->get('vm.' . $vmtype . '.index', array('page' => 1, 
			'refreshTime' => 10000, 
			'filter' => array('pool' => null, 'name' => null, 'node' => null)
		));
		if (isset($_GET['time'])) {
			$sessionvars['refreshTime'] = (int) $_GET['time'];
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
			$criteria['attr']['sstVirtualMachineType'] = $vmtype;
		}
		if (isset($_GET['vmpool'])) {
			if ('' != $_GET['vmpool']) {
				$criteria['attr']['sstVirtualMachinePool'] = $_GET['vmpool'];
				$sessionvars['filter']['pool'] = $_GET['vmpool'];
			}
			else {
				$criteria['attr']['sstVirtualMachinePool'] = $sessionvars['filter']['pool'];
			}
		}
		if (isset($_GET['sstDisplayName'])) {
			$criteria['attr']['sstDisplayName'] = '*' . $_GET['sstDisplayName'] . '*';
			$sessionvars['filter']['name'] = $_GET['sstDisplayName'];
		}
		if (isset($_GET['sstNode'])) {
			$criteria['attr']['sstNode'] = '*' . $_GET['sstNode'] . '*';
			$sessionvars['filter']['node'] = $_GET['sstNode'];
		}

		if ($sidx != '')
		{
			$criteria['sort'] = $sidx . '.' . $sord;
			$sessionvars['sort'] = $criteria['sort'];
		}
		//echo '<pre>' . print_r($criteria, true) . '</pre>';
		if (Yii::app()->user->hasRight($vmtype . 'VM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL)) {
			$vms = LdapVm::model()->findAll($criteria);
		}
		else if (Yii::app()->user->hasRight($vmtype . 'VM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_OWNER)) {
			$vms = LdapVm::getAssignedVms($vmtype, $criteria);
			$vms = array_values($vms);
		}
		else {
			$vms = array();
		}
		$count = count($vms);
		$total_pages = ceil($count / $limit);

		$s = '<?xml version="1.0" encoding="utf-8"?>';
		$s .=  '<rows>';
		$s .= '<page>' . $page . '</page>';
		$s .= '<total>' . $total_pages . '</total>';
		$s .= '<records>' . $count . '</records>';

		$start = $limit * ($page - 1);
		$start = $start > $count ? 0 : $start;
		$end = $start + $limit;
		$end = $end > $count ? $count : $end;
		for ($i=$start; $i<$end; $i++) {
			$vm = $vms[$i];
					//	'colNames'=>array('No.', 'DN', 'UUID', , 'Spice', 'Type', 'SubType', 'active Golden-Image', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. ($i+1) ."</cell>\n";
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
			if ('Golden-Image' == $vm->sstVirtualMachineSubType) {
				$s .= "<cell></cell>\n";
			}
			else {
				$s .= '<cell>'. $vm->sstNode ."</cell>\n";
			}
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
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
       <td style="text-align: right; vertical-align: top;"><b>Type:</b></td>
      <td style="vertical-align: top;">{$vm->sstVirtualMachineType}, {$vm->sstVirtualMachineSubType}</td>
      <td style="text-align: right; vertical-align: top;"><b>VM:</b></td>
      <td>{$vm->sstDisplayName}<br/>{$vm->sstVirtualMachine}</td>
    </tr>
    <tr>
      <td style="text-align: right; vertical-align: top;"><b>Memory:</b></td>
      <td style="vertical-align: top;">$memory</td>
      <td style="text-align: right;vertical-align: top;"><b>VM Pool:</b></td>
      <td>{$vm->vmpool->sstDisplayName}<br/>{$vm->sstVirtualMachinePool}</td>
    </tr>
    <tr>
      <td style="text-align: right"><b>CPUs:</b></td>
      <td>{$vm->sstVCPU}</td>
EOS;
		if ('dynamic' === $vm->sstVirtualMachineType && 'Golden-Image' !== $vm->sstVirtualMachineSubType) {
			echo <<< EOS
      <td style="text-align: right;vertical-align: top;"><b>Golden:</b></td>
      <td>{$vm->vmpool->sstActiveGoldenImage}</td>
EOS;
		}
		echo '    </tr>';
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
	<br />
	<h3>Backups</h3>
	<table style="margin-bottom: 0px; width: 100%;"><tbody>
    <tr>
		<th style="text-align: center; width: 16px;">&nbsp;</th>
		<th style="text-align: center; width: 120px;"><b>Date</b></th>
		<th style="text-align: center; width: 120px;"><b>State</b></th>
		<th style="text-align: center;"><b>Message</b></th>
		<th style="text-align: center; width: 60px;"><b>Action</b></th>
	</tr>
			
EOS;
			$formatter = new CDateFormatter(CLocale::getInstance(Yii::t('app', 'locale')));
			$restoring = false;
			foreach($vm->backup->backups as $backup) {
				if (0 === strpos($backup->sstProvisioningMode, 'unretain') || 0 === strpos($backup->sstProvisioningMode, 'restor')) {
					$restoring = true;
					break;
				}
			}
			foreach($vm->backup->backups as $backup) {
				echo '<tr><td>';
				if ('finished' === $backup->sstProvisioningMode) {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_finished.png" />';
				}
				else if (0 != $backup->sstProvisioningReturnValue) {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_error.png" />';
				}
				else {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_running.png" />';
				}
				echo '</td>';
				$date = $formatter->formatDateTime(substr($backup->ou, 0, strlen($backup->ou)-1));
				echo '<td style="white-space: nowrap;">' . $date . '</td><td style="text-align: center;">' . $backup->sstProvisioningMode . '</td><td>';
				if (0 != $backup->sstProvisioningReturnValue) {
					echo $backup->sstProvisioningReturnValue . ' (';	
				
					switch($backup->sstProvisioningReturnValue) {
						case  1: echo Yii::t('backup', 'UNDEFINED_ERROR'); break;
						case  2: echo Yii::t('backup', 'MISSING_PARAMETER_IN_CONFIG_FILE'); break;
						case  3: echo Yii::t('backup', 'CONFIGURED_RAM_DISK_IS_NOT_VALID'); break;
						case  4: echo Yii::t('backup', 'NOT_ENOUGH_SPACE_ON_RAM_DISK'); break;
						case  5: echo Yii::t('backup', 'CANNOT_SAVE_MACHINE_STATE'); break;
						case  6: echo Yii::t('backup', 'CANNOT_WRITE_TO_BACKUP_LOCATION'); break;
						case  7: echo Yii::t('backup', 'CANNOT_COPY_FILE_TO_BACKUP_LOCATION'); break;
						case  8: echo Yii::t('backup', 'CANNOT_COPY_IMAGE_TO_BACKUP_LOCATION'); break;
						case  9: echo Yii::t('backup', 'CANNOT_COPY_XML_TO_BACKUP_LOCATION'); break;
						case 10: echo Yii::t('backup', 'CANNOT_COPY_BACKEND_FILE_TO_BACKUP_LOCATION'); break;
						case 11: echo Yii::t('backup', 'CANNOT_MERGE_DISK_IMAGES'); break;
						case 12: echo Yii::t('backup', 'CANNOT_REMOVE_OLD_DISK_IMAGE'); break;
						case 13: echo Yii::t('backup', 'CANNOT_REMOVE_FILE'); break;
						case 15: echo Yii::t('backup', 'CANNOT_CREATE_EMPTY_DISK_IMAGE'); break;
						case 16: echo Yii::t('backup', 'CANNOT_RENAME_DISK_IMAGE'); break;
						case 17: echo Yii::t('backup', 'CANNOT_CONNECT_TO_BACKEND'); break;
						case 18: echo Yii::t('backup', 'WRONG_STATE_INFORMATION'); break;
						case 19: echo Yii::t('backup', 'CANNOT_SET_DISK_IMAGE_OWNERSHIP'); break;
						case 20: echo Yii::t('backup', 'CANNOT_SET_DISK_IMAGE_PERMISSION'); break;
						case 21: echo Yii::t('backup', 'CANNOT_RESTORE_MACHINE'); break;
						case 22: echo Yii::t('backup', 'CANNOT_LOCK_MACHINE'); break;
						case 23: echo Yii::t('backup', 'CANNOT_FIND_MACHINE'); break;
						case 24: echo Yii::t('backup', 'CANNOT_COPY_STATE_FILE_TO_RETAIN'); break;
						case 25: echo Yii::t('backup', 'RETAIN_ROOT_DIRECTORY_DOES_NOT_EXIST'); break;
						case 26: echo Yii::t('backup', 'BACKUP_ROOT_DIRECTORY_DOES_NOT_EXIST'); break;
						case 27: echo Yii::t('backup', 'CANNOT_CREATE_DIRECTORY'); break;
						case 28: echo Yii::t('backup', 'CANNOT_SAVE_XML'); break;
						case 29: echo Yii::t('backup', 'CANNOT_SAVE_BACKEND_ENTRY'); break;
						case 30: echo Yii::t('backup', 'CANNOT_SET_DIRECTORY_OWNERSHIP'); break;
						case 31: echo Yii::t('backup', 'CANNOT_SET_DIRECTORY_PERMISSION'); break;
						case 32: echo Yii::t('backup', 'CANNOT_FIND_CONFIGURATION_ENTRY'); break;
						case 33: echo Yii::t('backup', 'BACKEND_XML_UNCONSISTENCY'); break;
						case 34: echo Yii::t('backup', 'CANNOT_CREATE_TARBALL'); break;
						case 35: echo Yii::t('backup', 'UNSUPPORTED_FILE_TRANSFER_PROTOCOL'); break;
						case 36: echo Yii::t('backup', 'UNKNOWN_BACKEND_TYPE'); break;
						case 37: echo Yii::t('backup', 'MISSING_NECESSARY_FILES'); break;
						case 38: echo Yii::t('backup', 'CORRUPT_DISK_IMAGE_FOUND'); break;
						case 39: echo Yii::t('backup', 'UNSUPPORTED_CONFIGURATION_PARAMETER'); break;
						case 40: echo Yii::t('backup', 'CANNOT_MOVE_DISK_IMAGE_TO_ORIGINAL_LOCATION'); break;
						case 41: echo Yii::t('backup', 'CANNOT_DEFINE_MACHINE'); break;
						case 42: echo Yii::t('backup', 'CANNOT_START_MACHINE'); break;
						case 43: echo Yii::t('backup', 'CANNOT_WORK_ON_UNDEFINED_OBJECT'); break;
						case 44: echo Yii::t('backup', 'CANNOT_READ_STATE_FILE'); break;
						case 45: echo Yii::t('backup', 'CANNOT_READ_XML_FILE'); break;
						case 46: echo Yii::t('backup', 'NOT_ALL_FILES_DELETED_FROM_RETAIN_LOCATION'); break;
						default: echo Yii::t('backup', 'UNKNOWN_ERROR'); break;
					}
					echo ')';
				}
				else {
					echo '&nbsp;';
				}
				echo '</td><td>';
				if ('finished' === $backup->sstProvisioningMode && !$restoring) {
					echo '<img class="action" title="restore VM backup" alt="restore" src="' . Yii::app()->baseUrl . '/images/vm_restore.png" backupDn="' . $backup->Dn . '" style="cursor: pointer;">';
				}
				else {
					echo '&nbsp;';
				}
				echo '</td></tr>';
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
		$vmpool = $vm->vmpool;
		foreach ($vmpool->nodes as $poolnode) {
			$node = LdapNode::model()->findByAttributes(array('attr'=>array('sstNode' => $poolnode->ou)));
			if (!is_null($node) && $node->sstNode != $vm->sstNode && $node->isType('VM-Node') && 'maintenance' !== $node->getType('VM-Node')->sstNodeState) {
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
		$showbutton = '';
		$showerror = 'display: none;';
		$errormsg = '';
		if (0 == count($narray)) {
			$showbutton = 'display: none;';
			$showerror = '';
			$errormsg = Yii::t('vmtemplate', 'No node found to migrate to');
		}
?>
		<br/>
		<button id="migrateNode" style="margin-top: 10px; float: left; <?php echo $showbutton?>"></button>
		<div id="errorNode" class="ui-state-error ui-corner-all" style="<?php echo $showerror;?>  width: 160px; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorNodeMsg" style="display: block;"><?php echo $errormsg;?></span></p>
		</div>
		<div id="infoNode" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoNodeMsg"></span></p>
		</div>
		<br style="clear: both;"/><br/><br/><br/>&nbsp;
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
			$garray[$group->uid] = array('name' => $group->sstDisplayName);
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
				$data['description'] = array('This entry links to the group ' . $group->sstDisplayName . '.');
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
					$answer = array(/* 'type' => $vm->sstVirtualMachineType, 'subtype' => $vm->sstVirtualMachineSubType,*/ 'name' => $vm->sstDisplayName, 'node' => $vm->sstNode, 'statustxt' => '');
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
								$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'stopped', 'spice' => $vm->getSpiceUri()));
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
					$data = $vm->getStartParams();
					$data['name'] = $data['sstName'];
					$libvirt->redefineVm($data);
					$retval = $libvirt->startVm($data);
					if ($retval) {
						$vm->setOverwrite(true);
						$vm->sstStatus = 'running';
						$vm->save();
					}
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
						$vmcopy->sstStatus = 'running';
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

							$retval = $libvirt->startDynVm($vmcopy->getStartParams());
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
					$vm->setOverwrite(true);
					$vm->sstStatus = 'shutdown';
					$vm->save();
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
						$vm->setOverwrite(true);
						$vm->sstStatus = 'shutdown';
						$vm->save();
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
					$spiceport = $libvirt->nextSpicePort($newnode->sstNode);
					if ($status['active']) {
						$vm->setOverwrite(true);
						$vm->sstMigrationNode = $newnode->sstNode;
						$vm->sstMigrationSpicePort = $spiceport;
						$vm->save();
						if ($libvirt->migrateVm(array(
								'libvirt' => $vm->node->getLibvirtUri(), 
								'newlibvirt' => $newnode->getLibvirtUri(), 
								'name' => $vm->sstVirtualMachine, 
								'spiceport' => $spiceport,
								'newlisten' => $newnode->getVLanIP('pub')))) {
							$vm->sstNode = $newnode->sstNode;
							$vm->sstSpicePort = $spiceport;
							$vm->save();
							$entries = array('sstMigrationNode' => array(), 'sstMigrationSpicePort' => array());
							CLdapServer::getInstance()->modify_del($vm->dn, $entries);
								
							$this->sendAjaxAnswer(array('error' => 0, 'message' => Yii::t('vm', 'Migration finished'), 'refresh' => 1));
						}
						else {
							$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt migrateVm failed (' . $libvirt->getLastError() . ')!'));
						}
					}
					else {
						$libvirt->undefineVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));
						$vm->setOverwrite(true);
						$vm->sstNode = $newnode->sstNode;
						$vm->sstSpicePort = $spiceport;
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
	
	public function actionRestoreVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$backup = CLdapRecord::model('LdapVmSingleBackup')->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$backup->setOverwrite(true);
				$backup->sstProvisioningMode = 'unretainSmallFiles';
				$backup->sstProvisioningState = '0';
				$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
				$json = array('err' => false, 'msg' => Yii::t('vm', 'Restore Vm started!'));
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		else {
			$json = array('err' => true, 'msg' => Yii::t('vm', 'Parameter dn not found!'));
		}
		$this->sendJsonAnswer($json);
	}
	
	public function actionWaitForRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('waitForRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				Yii::log('waitForRestoreAction: ' . $backup->sstProvisioningMode . ', ' . $backup->sstProvisioningReturnValue, 'profile', 'vmController');
				if ('unretainedSmallFiles' === $backup->sstProvisioningMode) {
					if (0 == $backup->sstProvisioningReturnValue) {
						$vm = $backup->vm;
						$vmpool = $vm->vmpool;
						$backupconf = $vmpool->getConfigurationBackup();
						$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
						$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
							$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;
						Yii::log('waitForRestoreAction: ' . $ldiffile, 'profile', 'vmController');
						if (file_exists($ldiffile)) {
							$json = array('err' => false, 'msg' => Yii::t('vm', 'Should restore of Vm start?'));
						}
						else {
							$json = array('err' => true, 'msg' => Yii::t('vm', 'Error finding LDIF file'));
						}
					}
					else {
						$json = array('err' => true, 'msg' => Yii::t('vm', 'Error unretaining files'));
					}
				}
				else {
					$json = array('err' => true, 'msg' => Yii::t('vm', 'Waiting for data'), 'refresh' => true);
				}
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
	}
	
	public function actionStartRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('startRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$vm = $backup->vm;
				$vmpool = $vm->vmpool;
				$backupconf = $vmpool->getConfigurationBackup();
				$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
				$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;
				$ldiftofile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif';
				if (copy($ldiffile, $ldiftofile)) {
					$backup->setOverwrite(true);
					$backup->sstProvisioningMode = 'unretainLargeFiles';
					$backup->sstProvisioningState = '0';
					$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
						
					$json = array('err' => false, 'msg' => Yii::t('vm', 'Restore started'));
				}
				else {
					$json = array('err' => true, 'msg' => Yii::t('vm', 'Error copying LDIF file'));
				}
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
	}
					
	public function actionCancelRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('cancelRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$backup->setOverwrite(true);
				$backup->sstProvisioningMode = 'finished';
				$backup->sstProvisioningState = '0';
				$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
		
				$json = array('err' => false, 'msg' => Yii::t('vm', 'Canceled'));
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
		}
	
	public function actionGetRestoreAction() {
		Yii::log('getRestoreAction: ' . $_POST['dn'], 'profile', 'vmController');
		if (isset($_POST['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_POST['dn']);
			if (!is_null($backup)) {
				$vm = LdapVm::model()->findByDn(CLdapRecord::getParentDn(CLdapRecord::getParentDn($backup->getDn())));
				$backupconf = $vm->backup;
				if (!isset($backupconf->sstBackupRetainDirectory)) {
					$backupconf = $vm->vmpool->backup;
					if (is_null($backupconf) || !isset($backupconf->sstBackupRetainDirectory)) {
						$backupconf = LdapConfigurationBackup::model()->findByDn('ou=backup,ou=configuration,ou=virtualization,ou=services');
					}
				}
				$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
				$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;

		echo $dir . '<br/>';
		echo $ldiffile . '<br/>';
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
?>
<form action="#">
	Test1: <input type="text" size="12" />
</form>
<?php
	}
	
	public function actionHandleRestoreAction() {
	}
}
