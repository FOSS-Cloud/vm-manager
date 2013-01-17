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
 * VmTemplateController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class VmTemplateController extends Controller
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
			$this->submenu['vm']['items']['vmtemplate']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Virtual Machine Template Update Tooltip')),
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
		        	'actions'=>array('index', 'view', 'create', 'update', 'delete', 'finish', 'finishDynamic',
					'getDefaults', 'getVmTemplates', 'refreshVMs', 'getNodeGui',
              				'saveVm', 'startVm', 'shutdownVm', 'rebootVm', 'destroyVm', 'migrateVm', 'toogleBoot',
					'getCheckCopyGui', 'checkCopy', 'getDynData', 'getStaticPoolGui', 'getDynamicPoolGui'),
		        	'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
	    	    'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index', array('copyaction' => isset($_GET['copyaction']) ? $_GET['copyaction'] : null));
	}

	public function actionView() {
	}

	public function actionCreate() {
		$model = new VmTemplateForm('create');
		$hasError = false;

		$this->performAjaxValidation($model);

		if(isset($_POST['VmTemplateForm'])) {
			$model->attributes = $_POST['VmTemplateForm'];
			$parts = explode('°', $_POST['VmTemplateForm']['path']);
/*
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			if (0 == count($subnets)) {
				$model->addError('dn', Yii::t('vmtemplate', 'No Subnet found! <a href="{anker}">Please create one.</a>', array('{anker}' => '/subnet/create.html')));
				$hasError = true;
			}
			else {
				$subnet = $subnets[0];
				$ranges = $subnet->ranges;
				if (0 == count($ranges)) {
					$model->addError('dn', Yii::t('vmtemplate', 'No Range in Subnet ({subnet}) found! <a href="{anker}">Please create one.</a>',
						array(
							'{subnet}' => $subnet->cn . '/' . $subnet->dhcpNetMask,
							'{anker}' => $this->createUrl('subnet/createRange', array('dn' => $subnet->dn))
						)
					));
					$hasError = true;
				}
				else {
					$range = $ranges[0];
					if (is_null($range->getFreeIp())) {
						$model->addError('dn', Yii::t('vmtemplate', 'No free IP in Range {range} (Subnet {subnet}) found!',
							array(
								'{range}' => $range->cn,
								'{subnet}' => $subnet->cn . '/' . $subnet->dhcpNetMask,
							)
						));
						$hasError = true;
					}
				}
			}
*/
			$pool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$model->vmpool)));
			$range = null;
			foreach($pool->ranges as $poolrange) {
				$range = CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('attr'=>array('cn'=>$poolrange->ou), 'depth'=>true));
				if (!is_null($range) && !is_null($range->getFreeIp())) {
					break;
				}
			}
			if (is_null($range) || is_null($range->getFreeIp())) {
				$model->addError('dn', Yii::t('vmtemplate', 'No free IP in Ranges of VmPool {pool} found!',
					array(
						'{pool}' => $pool->sstDisplayName,
					)
				));
				$hasError = true;
			}

			if (!$hasError) {
				//$range = $subnets[0]->ranges[0];

				$result = CLdapRecord::model('LdapVmFromProfile')->findByDn($_POST['VmTemplateForm']['basis']);
				$result->setOverwrite(true);
				$result->sstVirtualMachineType = 'template';
				$result->sstVirtualMachineSubType = 'VM-Template';
				$result->sstClockOffset = $model->sstClockOffset;
				$result->sstMemory = $model->sstMemory;
				$result->sstOSArchitecture = $parts[2];
				$result->sstVCPU = $model->sstVCPU;
				$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
				$result->description = $model->description;
				if ('TBD_GUI' == $result->sstOnCrash) {
					$result->sstOnCrash = $result->sstOnCrashDefault;
				}
				if ('TBD_GUI' == $result->sstOnPowerOff) {
					$result->sstOnPowerOff = $result->sstOnPowerOffDefault;
				}
				if ('TBD_GUI' == $result->sstOnReboot) {
					$result->sstOnReboot = $result->sstOnRebootDefault;
				}
				if ('TBD_GUI' == $result->sstDisplayName) {
					$result->sstDisplayName = $model->name;
				}
				if ('TBD_GUI' == $result->sstNode) {
					$result->sstNode = $model->node;
				}

				$vmpool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$model->vmpool)));
				if ('TBD_GUI' == $result->sstVirtualMachinePool) {
					$result->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
				}

				$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array('sstNode' => $model->node)));
				$node = $nodes[0];
				
				// 'save' devices before
				$rdevices = $result->devices;
				/* Create a copy to be sure that we will write a new record */
				$templatevm = new LdapVmFromTemplate();
				/* Don't change the labeledURI; must refer to a default Profile */
				$templatevm->attributes = $result->attributes;
				/* Delete all objectclasses and let the LdapVMFromProfile set them */
				$templatevm->removeAttribute(array('objectClass', 'member'));
				$templatevm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

				$templatevm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($node->sstNode);
				$templatevm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
				$templatevm->save();

				// Workaround to get Node
				$templatevm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($templatevm->getDn());

				$settings = new LdapVmConfigurationSettings();
				$settings->setBranchDn($templatevm->dn);
				$settings->ou = "settings";
				$settings->save();
				
				$devices = new LdapVmDevice();
				//echo '<pre>' . print_r($result->devices, true) . '</pre>';
				$devices->attributes = $rdevices->attributes;
				$devices->setBranchDn($templatevm->dn);
				//echo '<pre>' . print_r($devices, true) . '</pre>';
				$devices->save();
				
				foreach($rdevices->disks as $rdisk) {
					$disk = new LdapVmDeviceDisk();
					//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
					$disk->setOverwrite(true);
					$disk->attributes = $rdisk->attributes;
					if ('disk' == $disk->sstDevice) {
						$disk->sstVolumeCapacity = $model->sstVolumeCapacity;
						$storagepool = $vmpool->getStoragePool();
						$templatesdir = substr($storagepool->sstStoragePoolURI, 7);
						$names = CPhpLibvirt::getInstance()->createVolumeFile($templatesdir, $storagepool->sstStoragePool, $node->getLibvirtUri(), $disk->sstVolumeCapacity);
						if (false !== $names) {
							$disk->sstVolumeName = $names['VolumeName'];
							$disk->sstSourceFile = $names['SourceFile'];
						}
						else {
							$hasError = true;
							$model->addError('dn', Yii::t('vmtemplate', 'Unable to create Volume file!'));
							$templatevm->delete();
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

					$dhcpvm = new LdapDhcpVm();
					$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
					$dhcpvm->cn = $result->sstVirtualMachine;
					$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
					$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
					$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

					$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
					$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
					$dhcpvm->save();

					$ret = CPhpLibvirt::getInstance()->defineVm($templatevm->getStartParams());
						
					$this->redirect(array('index'));
				}
			}
		}
		if (!isset($_POST['VmTemplateForm']) || $hasError) {
			$vmpools = array();
			$criteria = array('attr'=>array('sstVirtualMachinePoolType'=>'template'));
			//$criteria = array('filter'=>'(|(sstVirtualMachinePoolType=template)(sstVirtualMachinePoolType=static)(sstVirtualMachinePoolType=dynamic))');
			$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
/*
			$nodes = array();
			$criteria = array('attr'=>array());
			$nodes = CLdapRecord::model('LdapNode')->findAll($criteria);
*/
			$profiles = array();
			$subtree = CLdapRecord::model('LdapSubTree');
			$subtree->setBranchDn('ou=virtual machine profiles,ou=virtualization,ou=services');
			$profiles = $subtree->findSubTree(array());
			//echo '<pre>' . print_r($profiles, true) . '</pre>';

			$this->render('create',array(
				'model' => $model,
				'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
				'nodes' => array(), //$this->createDropdownFromLdapRecords($nodes, 'sstNode', 'sstNode'),
				'profiles' => $this->getProfilesFromSubTree($profiles),
				'defaults' => null,
			));
		}
	}

	public function actionUpdate() {
		$model = new VmTemplateForm('update');

		if(isset($_GET['dn'])) {
			$model->dn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidation($model);

		if(isset($_POST['VmTemplateForm'])) {
			$model->attributes = $_POST['VmTemplateForm'];

			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_POST['VmTemplateForm']['dn']);
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

			$this->redirect(array('index'));
		}
		else {
			$vmpools = array();
			$criteria = array('filter'=>'(|(sstVirtualMachinePoolType=template)(sstVirtualMachinePoolType=dynamic))');
			$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);

			$nodes = array();
			$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));

			$vm = CLdapRecord::model('LdapVmFromTemplate')->findbyDn($_GET['dn']);
			$defaults = $vm->defaults;
			//$subnet = $vm->dhcp->subnet;
			$allRanges = array('' => '');
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

			$model->dn = $vm->dn;
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
				'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
				'nodes' => $this->createDropdownFromLdapRecords($nodes, 'sstNode', 'sstNode'),
				'profiles' => null,
				'ranges' => $allRanges,
				'defaults' => $defaults,
			));
		}
	}

	public function actionDelete() {
		$this->disableWebLogRoutes();
		if ('del' == Yii::app()->getRequest()->getParam('oper', '??')) {
			$dn = urldecode(Yii::app()->getRequest()->getParam('dn'));
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($dn);
			if (!is_null($vm)) {
				if (!$vm->isActive()) {
					//echo 'delete sstDisk=vda->sstSourceFile';
					$vda = $vm->devices->getDiskByName('vda');
					$libvirt = CPhpLibvirt::getInstance();
					if (!$libvirt->deleteVolumeFile($vda->sstSourceFile)) {
						$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Unable to delete Volume File for Vm Template \'' . $vm->sstDisplayName . '\'!'));
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

						// delete VM Template
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

	public function actionFinish() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn']) && isset($_GET['pool'])) {
			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			$result->setOverwrite(true);
			$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
			//$pools = CLdapRecord::model('LdapVmPool')->findAll( array('attr'=>array()));
			//$result->sstVirtualMachinePool = $pools[0]->sstVirtualMachinePool;

			if ('undefined' == $_GET['pool']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a pool!'));
				return;
			}
			if ('undefined' == $_GET['subtype']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a type!'));
				return;
			}
			$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['pool']);
			$storagepool = $vmpool->getStoragePool();
			if (is_null($storagepool)) {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No storagepool found for selected vmpool!')));
				return;
			}

			// 'save' devices before
			$rdevices = $result->devices;
			/* Create a copy to be sure that we will write a new record */
			$vm = new LdapVm();
			/* Don't change the labeledURI; must refer to a default Profile */
			$vm->attributes = $result->attributes;

			$vm->setOverwrite(true);
			if (isset($_GET['name']) && '' != $_GET['name']) {
				$vm->sstDisplayName = $_GET['name'];
			}
			$vm->sstVirtualMachineType = 'persistent';
			$vm->sstVirtualMachineSubType = $_GET['subtype'];
			$vm->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
			/* Delete all objectclasses and let the LdapVM set them */
			$vm->removeAttribute(array('objectClass', 'member'));
			$vm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

			$vm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$vm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$vm->sstOsBootDevice = 'hd';
			$vm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($vm->sstNode);
			$vm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
			$vm->save();

			$settings = new LdapVmConfigurationSettings();
			$settings->setBranchDn($vm->dn);
			$settings->ou = "settings";
			$settings->save();
				
			$devices = new LdapVmDevice();
			$devices->setOverwrite(true);
			$devices->attributes = $rdevices->attributes;
			$devices->setBranchDn($vm->dn);
			$devices->save();

			$copydata = array();
			foreach($rdevices->disks as $rdisk) {
				$disk = new LdapVmDeviceDisk();
				//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
				$disk->setOverwrite(true);
				$disk->attributes = $rdisk->attributes;
				if ('disk' == $disk->sstDevice) {
					$persistentdir = substr($storagepool->sstStoragePoolURI, 7);
					$copydata = CPhpLibvirt::getInstance()->copyVolumeFile($persistentdir, $disk);
					$copydata['Dn'] = $vm->getDn();
					$disk->sstVolumeName = $copydata['VolumeName'];
					$disk->sstSourceFile = $copydata['SourceFile'];
					$_SESSION['copyVolumeFile'] = $copydata;
				}
				$disk->setBranchDn($devices->dn);
				$disk->save();
			}
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
				$vm->delete(true);
				$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No range found for VMPool!')));
				return;
			}
			$dhcpvm = new LdapDhcpVm();
			$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
			$dhcpvm->cn = $result->sstVirtualMachine;
			$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

			$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
			$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
			$dhcpvm->save();

			$server = CLdapServer::getInstance();
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('groups');
			$data['description'] = array('This is the assigned groups subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=groups,' . $vm->dn;
			$server->add($dn, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
			$data['ou'] = array('people');
			$data['description'] = array('This is the assigned people subtree.');
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=people,' . $vm->dn;
			$server->add($dn, $data);
		}
		//$this->redirect(array('index', 'copyaction' => $copydata['pid']));
		$this->sendAjaxAnswer(array('error' => 0, 'url' => $this->createUrl('index', array('copyaction' => $copydata['pid']))));
	}

	public function actionFinishDynamic() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn']) && isset($_GET['pool'])) {
			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			$result->setOverwrite(true);
			$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
			//$pools = CLdapRecord::model('LdapVmPool')->findAll( array('attr'=>array()));
			//$result->sstVirtualMachinePool = $pools[0]->sstVirtualMachinePool;

			if ('undefined' == $_GET['pool']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a pool!'));
				return;
			}
			$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['pool']);
			$storagepool = $vmpool->getStoragePool();
			if (is_null($storagepool)) {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'No storagepool found for selected vmpool!'));
				return;
			}

			// 'save' devices before
			$rdevices = $result->devices;
			/* Create a copy to be sure that we will write a new record */
			$vm = new LdapVm();
			/* Don't change the labeledURI; must refer to a default Profile */
			$vm->attributes = $result->attributes;

			$vm->setOverwrite(true);
			$vm->sstVirtualMachineType = 'dynamic';
			if (isset($_GET['sysprep']) && 'true' == $_GET['sysprep']) {
				$vm->sstVirtualMachineSubType = 'System-Preparation';
			}
			else {
				$vm->sstVirtualMachineSubType = 'Golden-Image';
			}
			if (isset($_GET['name']) && '' != $_GET['name']) {
				$vm->sstDisplayName = $_GET['name'];
			}
			$vm->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
			/* Delete all objectclasses and let the LdapVM set them */
			$vm->removeAttribute(array('objectClass', 'member'));
			$vm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

			$vm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$vm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$vm->sstOsBootDevice = 'hd';
			$vm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($vm->sstNode);
			$vm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
			$vm->save();

			$settings = new LdapVmPoolConfigurationSettings();
			$settings->setBranchDn($vm->dn);
			$settings->ou = "settings";
			$settings->save();
				
			$devices = new LdapVmDevice();
			$devices->setOverwrite(true);
			$devices->attributes = $rdevices->attributes;
			$devices->setBranchDn($vm->dn);
			$devices->save();

			$copydata = array();
			foreach($rdevices->disks as $rdisk) {
				$disk = new LdapVmDeviceDisk();
				//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
				$disk->setOverwrite(true);
				$disk->attributes = $rdisk->attributes;
				if ('disk' == $disk->sstDevice) {
					$persistentdir = substr($storagepool->sstStoragePoolURI, 7);
					$copydata = CPhpLibvirt::getInstance()->copyVolumeFile($persistentdir, $disk);
					$disk->sstVolumeName = $copydata['VolumeName'];
					$disk->sstSourceFile = $copydata['SourceFile'];
				}
				$disk->setBranchDn($devices->dn);
				$disk->save();
			}
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

			if ('System-Preparation' === $vm->sstVirtualMachineSubType) {
				/* Not necessary for a golden image */
				$range = $vmpool->getRange();
				if (is_null($range)) {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No range found for VMPool!')));
					return;
				}
				$dhcpvm = new LdapDhcpVm();
				$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
				$dhcpvm->cn = $result->sstVirtualMachine;
				$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
				$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
				$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

				$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
				$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
				$dhcpvm->save();

				$server = CLdapServer::getInstance();
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
				$data['ou'] = array('people');
				$data['description'] = array('This is the assigned people subtree.');
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn = 'ou=people,' . $vm->dn;
				$server->add($dn, $data);
			}
		}
		//$this->redirect(array('index', 'copyaction' => $copydata['pid']));
		$this->sendAjaxAnswer(array('error' => 0, 'url' => $this->createUrl('index', array('copyaction' => $copydata['pid']))));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vmtemplate-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetDefaults() {
		$defaults = array();
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$defaults['path'] = $_GET['p'];
			if ('sstVirtualMachine=default' == substr($_GET['dn'], 0, strlen('sstVirtualMachine=default'))) {
				$defaults['name'] = '';
				$defaults['description'] = '';
				$result = CLdapRecord::model('LdapVmDefaults')->findByDn($_GET['dn']);
				$defaults['memorydefault'] = $result->sstMemoryDefault;
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpudefault'] = $result->sstVCPUDefault;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockdefault'] = $result->sstClockOffsetDefault;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				//echo '<pre>' . print_r($result->device->disks, true) . '</pre>';
				//$result = CLdapRecord::model('LdapVmDeviceDisk')->findByDn('sstDisk=hdb,ou=devices,' . $_GET['dn']);
				$result = $result->devices->getDiskByName('vda');
				//echo '<pre>' . print_r($result, true) . '</pre>';
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacityDefault;
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
				//echo '<pre>' . print_r($defaults, true) . '</pre>';
			}
			else {
				$parts = explode('°', $_GET['p']);
				$defaults['name'] = $parts[1];
				$profile = CLdapRecord::model('LdapVmFromProfile')->findByDn($_GET['dn']);
				$defaults['description'] = $profile->description;
				$defaults['memorydefault'] = $profile->sstMemory;
				$defaults['cpudefault'] = $profile->sstVCPU;
				$defaults['clockdefault'] = $profile->sstClockOffset;

				$result = $profile->devices->getDiskByName('vda');
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacity;

				$result = CLdapRecord::model('LdapVmDefaults')->findByDn(substr($profile->labeledURI, 8));
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				$result = $result->devices->getDiskByName('vda');
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
			}
		}
		$s = CJSON::encode($defaults);
		header('Content-Type: application/json');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVMTemplates() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		$criteria = array('attr'=>array());
		$criteria['attr']['sstVirtualMachineType'] = 'template';
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
		$vms = CLdapRecord::model('LdapVmFromTemplate')->findAll($criteria);
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

		$vms = CLdapRecord::model('LdapVmFromTemplate')->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  '<rows>';
		$s .= '<page>' . $page . '</page>';
		$s .= '<total>' . $total_pages . '</total>';
		$s .= '<records>' . $count . '</records>';

		$i = 1;
		foreach ($vms as $vm) {
			//	'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Boot', 'Name', 'Displayname', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i ."</cell>\n";
			$s .= '<cell>'.$vm->dn ."</cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachine ."</cell>\n";
			$s .= '<cell><![CDATA['. $vm->getSpiceUri() . "]]></cell>\n";
			$s .= '<cell>'. $vm->sstOsBootDevice ."</cell>\n";
			$s .= '<cell>'. $vm->sstDisplayName ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>unknown</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= "<cell>---</cell>\n";
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

	public function actionGetCheckCopyGui() {
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmtemplate', 'Check Volume Copy') . '</span></div>';
?>
		<div style="text-align: center;" ><img id="running" src="<?php echo Yii::app()->baseUrl; ?>/images/loading.gif" alt="" /><br/></div>
		<div id="errorAssignment" class="ui-state-error ui-corner-all" style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>
			<span id="errorMsg">
			<?=Yii::t('vmtemplate', 'Copy of VM Template volume to VM volume still running!'); ?></span></p>
		</div>
		<div id="infoAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionGetNodeGui() {
		$this->disableWebLogRoutes();
		$narray = array();
		$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
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

	public function actionGetStaticPoolGui() {
		$this->disableWebLogRoutes();
		$parray = array();
		$pools = CLdapRecord::model('LdapVmPool')->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'persistent')));
		foreach ($pools as $pool) {
			$parray[$pool->dn] = array('name' => $pool->sstDisplayName);
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Create persistent VM') . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'staticpoolSelection',
			'values' => $parray,
			'multiselect' => false,
			'size' => 4,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'persistent VM Pools'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
?>
		<div style="padding-top: 10px; clear: both;">
			<label for="displayname">Name </label><input type="text" id="displayname" name="displayname" value="<?php echo (isset($_GET['name']) ? $_GET['name'] : ''); ?>"/>
		</div>
		<br/>
		<div id="radiosubtype" style="">
			<label>Type </label>
			<input type="radio" id="radiosubtype1" name="radiosubtype" value="Server" checked="checked" /><label for="radiosubtype1">Server</label>
			<input type="radio" id="radiosubtype2" name="radiosubtype" value="Desktop" /><label for="radiosubtype2">Desktop</label>
		</div>
		<button id="selectStaticButton" style="margin-top: 10px; float: left;"></button>
		<div id="errorSelectStatic" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectStaticMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectStatic" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectStaticMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionGetDynamicPoolGui() {
		$this->disableWebLogRoutes();
		$parray = array();
		$pools = CLdapRecord::model('LdapVmPool')->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'dynamic')));
		foreach ($pools as $pool) {
			$parray[$pool->dn] = array('name' => $pool->sstDisplayName);
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Create dynamic VM') . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'dynamicpoolSelection',
			'values' => $parray,
			'multiselect' => false,
			'size' => 4,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'dynamic VM Pools'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
?>
		<br/>
		<div style="padding-top: 10px; clear: both;">
			<label for="displayname">Name </label><input type="text" id="displayname" name="displayname" value="<?php echo (isset($_GET['name']) ? $_GET['name'] : ''); ?>"/>
		</div>
		<br/>
		<div style="">
			<input type="checkbox" id="radiosysprep" name="radiosysprep" /><label for="radiosysprep">Sys Prep</label>
		</div>
		<button id="selectDynamicButton" style="margin-top: 10px;"></button>
		<div id="errorSelectDynamic" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectDynamicMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectDynamic" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectDynamicMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionCheckCopy() {
		$this->disableWebLogRoutes();
		if (CPhpLibvirt::getInstance()->checkPid($_GET['pid'])) {
			$json = array('err' => true, 'msg' => Yii::t('vmtemplate', 'Still copying!'));
		}
		else {
			if (isset($_SESSION['copyVolumeFile'])) {
				chmod($_SESSION['copyVolumeFile']['SourceFile'], 0660);

				$vm = CLdapRecord::model('LdapVm')->findByDn($_SESSION['copyVolumeFile']['Dn']);
				if (!is_null($vm)) {
					$retval = CPhpLibvirt::getInstance()->defineVm($vm->getStartParams());
					if (false !== $retval) {
						$json = array('err' => false, 'msg' => Yii::t('vmtemplate', 'Finished!'));
					}
					else {
						$json = array('err' => 1, 'msg' => 'CPhpLibvirt defineVm failed (' . CPhpLibvirt::getInstance()->getLastError() . ')!');
					}
				}
				else {
					$json = array('err' => 1, 'msg' => 'Copied Vm not found!');
				}
				unset($_SESSION['copyVolumeFile']);
			}
			else {
				$json = array('err' => 1, 'msg' => 'No copy action found in session!');
			}
		}
		$this->sendJsonAnswer($json);
	}

	public function actionGetDynData() {
		$this->disableWebLogRoutes();
		$pool = $_GET['pool'];
		$vmpool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$pool)));
		$retval = array();
		$retval['nodes'] = array();
		foreach($vmpool->nodes as $node) {
			$retval['nodes'][$node->ou] = $node->ou;
		}

		$this->sendJsonAnswer($retval);
	}


	/* Private functions */
	private static $_levels = array('ou', 'sstOSArchitectureValues', 'sstLanguageValues');

	private function getProfilesFromSubTree($result, $i=0) {
//		if (!isset(self::$_levels[$i])) {
//			return null;
//		}
//		$attr = self::$_levels[$i];
//		$retval = array();
//		echo '<br/>' . substr('-----', 0, $i) . $i . ': ' . $result->dn . '<br/>';
//		$children = $result->children;
//		if (!is_array($children)) {
//			$children = array($children);
//		}
//		foreach($children as $child) {
//
//			echo '(' . $child->dn . ', ' . $child->$attr . ')';
//			if (isset($child->ou)) {
//				//echo substr('+++++', 0, $i+1) . ($i+1) . ': ' . $child->ou . '<br/>';
//				//$retval[$child->dn] = array('name' => $child->ou);
//				if (isset($child->children)) {
//					$retval[$child->dn]['children'] = $this->getProfilesFromSubTree($child, $i+1);
//				}
//			//}
//		}
//		return $retval;

		$retval = array();
		foreach($result->children as $child) {
			//echo $child->dn . '  (' . count($child->children) . ')<br/>';
			$retval[$child->ou] = array('dn' => $child->dn, 'children' => array());
			if (!is_null($child->children)) {
				$childs = $child->children;
				if (!is_array($childs)) {
					$childs = array($childs);
				}
				foreach($childs as $child2) {
					if ('default' == $child2->ou) continue;

					//echo $child2->dn . '<br/>';
					$retval[$child->ou]['children'][$child2->ou] = array('dn' => $child2->dn, 'children' => array());
					if (!is_null($child2->children)) {
						$childs2 = $child2->children;
						if (!is_array($childs2)) {
							$childs2 = array($childs2);
						}
						foreach($childs2 as $child3) {
							//echo $child3->dn . '<br/>';
							if ($child3->hasObjectClass('sstVirtualizationProfileArchitectureDefaults')) {
								$archs = $child3->sstOSArchitectureValues;
								if (!is_array($archs)) {
									$archs = array($archs);
								}
								foreach($archs as $arch) {
									//echo $arch . '<br/>';
									$retval[$child->ou]['children'][$child2->ou]['children'][$arch] = array('dn' => $child3->dn, 'children' => array());
									$childs3 = $child3->children;
									if (!is_array($childs3)) {
										$childs3 = array($childs3);
									}
									foreach($childs3 as $child4) {
										$langs = $child4->sstLanguageValues;
										if (!is_array($langs)) {
											$langs = array($langs);
										}
										foreach($langs as $lang) {
											//echo $lang . '<br/>';
											if (!is_null($child4->children)) {
												$dn = $child4->children[0]->dn;
											}
											else {
												$dn = $child4->dn;
											}
											$retval[$child->ou]['children'][$child2->ou]['children'][$arch]['children'][$lang] = array('dn' => $dn);
										}
									}
								}
							}
							else {
								$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou] = array('dn' => $child3->dn, 'children' => array());
								$childs3 = $child3->children;
								foreach($childs3 as $child4) {
									if (!is_null($child4->children)) {
										$dn = $child4->children[0]->dn;
									}
									else {
										$dn = $child4->dn;
									}
									$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou]['children'][$child4->ou] = array('dn' => $dn);
								}
							}
						}
					}
				}
			}
		}
		return $retval;
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
				$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($dn);
				//echo '<pre>' . print_r($vm, true) . '</pre>';
				if (!is_null($vm)) {
					$answer = array('node' => $vm->sstNode, 'statustxt' => '');
					$libvirt = CPhpLibvirt::getInstance();
					$status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));
					if ($status['active']) {
						$memory = $this->getHumanSize($status['memory'] * 1024);
						$maxmemory = $this->getHumanSize($status['maxMem'] * 1024);
						//$data[$vm->sstVirtualMachine] = array('status' => ($status['active'] ? 'running' : 'stopped'), 'mem' => $memory . ' / ' . $maxmemory, 'node' => $vm->sstNode);
						$data[$vm->sstVirtualMachine] = array('status' => ($status['active'] ? 'running' : 'stopped'), 'mem' => $memory . ' / ' . $maxmemory, 'node' => $vm->sstNode, 'spice' => $vm->getSpiceUri());
					}
					else {
						$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'stopped'));
					}
				}
				else {
					$data['error'] = 1;
					$data['message'] = 'CPhpLibvirt Vm \'' . $dn . '\' not found!';
				}
			}
		}
		$this->sendJsonAnswer($data);
	}

	public function actionStartVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			//$devices = $vm->devices[0];
			//echo '$devices <pre>' . print_r($devices, true) . '</pre>';
			//$disks = $devices->disks;
			//echo '<pre>' . print_r($devices->disks, true) . '</pre>';
			//$interfaces = $devices->interfaces;
			//echo '<pre>' . print_r($devices->interfaces, true) . '</pre>';
			if (!is_null($vm)) {
				$params = array();
				$params['sstName'] = $vm->sstVirtualMachine;
				$params['sstUuid'] = $vm->sstVirtualMachine;
				$params['sstClockOffset'] = $vm->sstClockOffset;
				$params['sstMemory'] = $vm->sstMemory;
				//$params['sstNode'] = $vm->sstNode;
				$params['libvirt'] = $vm->node->getLibvirtUri();
				$params['sstOnCrash'] = $vm->sstOnCrash;
				$params['sstOnPowerOff'] = $vm->sstOnPowerOff;
				$params['sstOnReboot'] = $vm->sstOnReboot;
				$params['sstOSArchitecture'] = $vm->sstOSArchitecture;
				$params['sstOSBootDevice'] = $vm->sstOSBootDevice;
				$params['sstOSMachine'] = $vm->sstOSMachine;
				$params['sstOSType'] = $vm->sstOSType;
				$params['sstType'] = $vm->sstType;
				$params['sstVCPU'] = $vm->sstVCPU;
				$params['sstFeature'] = $vm->sstFeature;
				$params['devices'] = array();
				$params['devices']['usb'] = ($vm->settings->isUsbAllowed() ? 'yes' : 'no');
				$params['devices']['sound'] = $vm->settings->isSoundAllowed();
				$params['devices']['sstEmulator'] = $vm->devices->sstEmulator;
				$params['devices']['sstMemBalloon'] = $vm->devices->sstMemBalloon;
				$params['devices']['graphics'] = array();
				$params['devices']['graphics']['spiceport'] = $vm->sstSpicePort;
				$params['devices']['graphics']['spicepassword'] = $vm->sstSpicePassword;
				$params['devices']['graphics']['spiceacceleration'] = isset(Yii::app()->params['virtualization']['disableSpiceAcceleration'])
					&& Yii::app()->params['virtualization']['disableSpiceAcceleration'];
				$params['devices']['disks'] = array();
				foreach($vm->devices->disks as $disk) {
					$params['devices']['disks'][$disk->sstDisk] = array();
					$params['devices']['disks'][$disk->sstDisk]['sstDevice'] = $disk->sstDevice;
					$params['devices']['disks'][$disk->sstDisk]['sstDisk'] = $disk->sstDisk;
					$params['devices']['disks'][$disk->sstDisk]['sstSourceFile'] = $disk->sstSourceFile;
					$params['devices']['disks'][$disk->sstDisk]['sstTargetBus'] = $disk->sstTargetBus;
					$params['devices']['disks'][$disk->sstDisk]['sstType'] = $disk->sstType;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverName'] = $disk->sstDriverName;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverType'] = $disk->sstDriverType;
					$params['devices']['disks'][$disk->sstDisk]['sstReadonly'] = $disk->sstReadonly;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverCache'] = $disk->sstDriverCache;
				}
				$params['devices']['interfaces'] = array();
				foreach($vm->devices->interfaces as $interface) {
					$params['devices']['interfaces'][$interface->sstInterface] = array();
					$params['devices']['interfaces'][$interface->sstInterface]['sstInterface'] = $interface->sstInterface;
					$params['devices']['interfaces'][$interface->sstInterface]['sstMacAddress'] = $interface->sstMacAddress;
					$params['devices']['interfaces'][$interface->sstInterface]['sstModelType'] = $interface->sstModelType;
					$params['devices']['interfaces'][$interface->sstInterface]['sstSourceBridge'] = $interface->sstSourceBridge;
					$params['devices']['interfaces'][$interface->sstInterface]['sstType'] = $interface->sstType;
				}
				//echo '<pre>' . print_r($params, true) . '</pre>';
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->startVm($params)) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt startVm failed!'));
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
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->rebootVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt rebootVm failed!'));
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
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->shutdownVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt shutdownVm failed!'));
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
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->destroyVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt destroyVm failed!'));
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
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
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

	public function actionToogleBoot() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				$dev = array($_GET['dev']);
				$dev[1] = 'hd' === $_GET['dev'] ? 'cdrom' : 'hd';
				if ($libvirt->changeVmBootDevice(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine, 'device1' => $dev[0], 'device2' => $dev[1]))) {
					$vm->setOverwrite(true);
					$vm->sstOsBootDevice = $_GET['dev'];
					$vm->save();
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt startVm failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}
}
