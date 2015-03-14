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

/**
 * LdapVm class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVm extends CLdapRecord {
	protected $_branchDn = 'ou=virtual machines,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'sstVirtualMachine=*');
	protected $_dnAttributes = array('sstVirtualMachine');
	protected $_objectClasses = array('sstVirtualizationVirtualMachine', 'sstRelationship', 'sstSpice', 'labeledURIObject', 'top');

	public function rules()
	{
		return array(
			array('', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('title', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'node' => array(self::HAS_ONE, 'sstNode', 'LdapNode', 'sstNode'),
			'network' => array(self::HAS_ONE_DEPTH, 'sstVirtualMachine', 'LdapNetwork', 'cn'),
			'devices' => array(self::HAS_ONE, 'dn', 'LdapVmDevice', '$model->getDn()', array('ou' => 'devices')),
			'defaults' => array(self::HAS_ONE_DN, 'dn', 'LdapVmDefaults', '$model->labeledURI', array()),
			'dhcp' => array(self::HAS_ONE_DEPTH, 'sstVirtualMachine', 'LdapDhcpVm', 'cn', array('objectclass' => 'dhcpHost')),
			'vmpool' => array(self::HAS_ONE, 'sstVirtualMachinePool', 'LdapVmPool', 'sstVirtualMachinePool'),
			'groups' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=groups,\' . $model->getDn()'),
			'people' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=people,\' . $model->getDn()'),
			'backup' => array(self::HAS_ONE, 'dn', 'LdapVmBackup', '$model->getDn()', array('ou' => 'backup')),
			'settings' => array(self::HAS_ONE, 'dn', 'LdapVmConfigurationSettings', '$model->getDn()', array('ou' => 'settings')),
		);
	}

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getIp()
	{
		return $this->network->dhcpstatements['fixed-address'];
	}

	public function isActive()
	{
		$retval = false;
		$libvirt = CPhpLibvirt::getInstance();
		if (!$this->isNewEntry()) {
			if ($status = $libvirt->getVmStatus(array('libvirt' => $this->node->getLibvirtUri(), 'name' => $this->sstVirtualMachine))) {
				$retval = $status['active'];
			}
		}
		return $retval;
	}

	public function getSpiceUri() {
		return 'spice://' . $this->node->getSpiceIp() . '?port=' . $this->sstSpicePort . '&password=' . $this->sstSpicePassword;
	}

	public function getStartParams() {
		$params = array();
		$params['sstName'] = $this->sstVirtualMachine;
		$params['sstUuid'] = $this->sstVirtualMachine;
		$params['sstClockOffset'] = $this->sstClockOffset;
		$params['sstMemory'] = $this->sstMemory;
		//$params['sstNode'] = $this->sstNode;
		$params['libvirt'] = $this->node->getLibvirtUri();
		$params['sstOnCrash'] = $this->sstOnCrash;
		$params['sstOnPowerOff'] = $this->sstOnPowerOff;
		$params['sstOnReboot'] = $this->sstOnReboot;
		$params['sstOSArchitecture'] = $this->sstOSArchitecture;
		$params['sstOSBootDevice'] = $this->sstOSBootDevice;
		$params['sstOSMachine'] = $this->sstOSMachine;
		$params['sstOSType'] = $this->sstOSType;
		$params['sstType'] = $this->sstType;
		$params['sstVCPU'] = $this->sstVCPU;
		if (!isset($this->sstNumberOfScreens)) {
			$params['sstNumberOfScreens'] = $this->vmpool->sstNumberOfScreens;
		}
		else {
			$params['sstNumberOfScreens'] = $this->sstNumberOfScreens;
		}
		$params['profileGroup'] = $this->defaults->getProfileGroup();
		$params['sstFeature'] = $this->sstFeature;
		$params['devices'] = array();
		$params['devices']['usb'] = ($this->settings->isUsbAllowed() ? 'yes' : 'no');
		$params['devices']['sound'] = $this->settings->isSoundAllowed();
		$params['devices']['sstEmulator'] = $this->devices->sstEmulator;
		$params['devices']['sstMemBalloon'] = $this->devices->sstMemBalloon;
		$params['devices']['graphics'] = array();
		$params['devices']['graphics']['spiceport'] = $this->sstSpicePort;
		$params['devices']['graphics']['spicepassword'] = $this->sstSpicePassword;
		$params['devices']['graphics']['spicelistenaddress'] = $this->node->getVLanIP('pub');
		$params['devices']['graphics']['spiceacceleration'] = isset(Yii::app()->params['virtualization']['disableSpiceAcceleration'])
			&& Yii::app()->params['virtualization']['disableSpiceAcceleration'];
		$params['devices']['disks'] = array();
		foreach($this->devices->disks as $disk) {
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
		foreach($this->devices->interfaces as $interface) {
			$params['devices']['interfaces'][$interface->sstInterface] = array();
			$params['devices']['interfaces'][$interface->sstInterface]['sstInterface'] = $interface->sstInterface;
			$params['devices']['interfaces'][$interface->sstInterface]['sstMacAddress'] = $interface->sstMacAddress;
			$params['devices']['interfaces'][$interface->sstInterface]['sstModelType'] = $interface->sstModelType;
			$params['devices']['interfaces'][$interface->sstInterface]['sstSourceBridge'] = $interface->sstSourceBridge;
			$params['devices']['interfaces'][$interface->sstInterface]['sstType'] = $interface->sstType;
		}
		return $params;
	}

	public function assignUser() {
		$user = CLdapRecord::model('LdapUser')->findByDn('uid=' . Yii::app()->user->getUID() . ',ou=people');
		if (!is_null($user)) {
			$server = CLdapServer::getInstance();
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $user->uid;
			$data['description'] = array('This entry links to the user ' . $user->getName() . '.');
			$data['labeledURI'] = array('ldap:///' . $user->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=' . $user->uid . ',ou=people,' . $this->getDn();
			$server->add($dn, $data);
		}
	}

	/**
	 * is there a backup running for this vm
	 * @return boolean
	 * 
	 * @copyright Copyright (c) 2014, stepping stone GmbH, Switzerland, http://www.stepping-stone.ch, support@stepping-stone.ch
	 */
	public function hasActiveBackup() {
		$single = LdapVmSingleBackup::model();
		$single->branchDn = $this->getDn(); // Don't use 'ou=backup,' . $this->getDn(); because there might be no backup branch
		$active = $single->findAll(array('filterName' => 'active', 'depth' => true));
				
		return 0 < count($active);
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'name' => Yii::t('vm', 'name')
		);
	}

	public function search()
	{
		$criteria = array(
			'attr' => array(
				'name' => $this->name
			),
		);

		return new CLdapDataProvider('LdapVm', array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 1,
			),
		));
	}

	public static function getAssignedVms($type, $criteria) {
		$unique_vms = array();
		if (Yii::app()->user->hasRight($type . 'VM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL)) {
			//$criteria = array_merge_recursive(array('attr' => array('sstVirtualMachineType' => $type)), $crit);
			$vms = LdapVm::model()->findAll($criteria);
			foreach($vms as $vm) {
				$unique_vms[$vm->sstVirtualMachine] = $vm;
			}
		}
		else if(Yii::app()->user->hasRight($type . 'VM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_OWNER)) {
			$user = Yii::app()->user->getLdapUser();
			
			//echo 'User: ' . $user->cn . '(' . $user->uid . ')<br/>';
			$groups = $user->sstGroupUID;
			//echo '<pre>groups ' . print_r($groups, true) . '</pre>';

			$pools = array();
			$criteriaVms = $criteria;
			//echo '<pre>criteria init ' . print_r($criteriaVms, true) . '</pre>';
			if (!isset($criteria['attr']['sstVirtualMachinePool'])) {
				$assignedPools = LdapVmPool::getAssignedPools($type);
	
				//echo '<h1>VMs</h1>';
				foreach($assignedPools as $pool) {
					$pools[] = $pool->sstVirtualMachinePool;
				}
				$criteriaVms['attr']['sstVirtualMachinePool'] = $pools;
			}
			
			//echo '<pre>criteria ' . print_r($criteriaVms, true) . '</pre>';
			if (0 < count($pools)) {
				$vms = LdapVm::model()->findAll($criteriaVms);
				//echo "$type pool vmcount " . count($vms) . '<br />';
				foreach($vms as $vm) {
					$unique_vms[$vm->sstVirtualMachine] = $vm;
					//echo '<pre>	' . $vm->sstVirtualMachine . ', ' . $vm->sstDisplayName . ' (pool: ' . $vm->sstVirtualMachinePool . ')</pre>';
				}
			}

			if (0 < count($groups)) {
				$criteriaVms = $criteria;
				$criteriaVms['relattr'] = array();
				$criteriaVms['relattr']['groups'] = array('ou' => $groups);
				//echo '<pre>criteria ' . print_r($criteria, true) . '</pre>';
					
				$vms = LdapVm::model()->findAll($criteriaVms);
				//echo 'group vmcount ' . count($vms) . '<br/>';
				foreach($vms as $vm) {
					//echo '<pre>	' . $vm->sstVirtualMachine . ', ' . $vm->sstDisplayName . ' (pool: ' . $vm->sstVirtualMachinePool . ')</pre>';
					if (!isset($unique_vms[$vm->sstVirtualMachine])) {
						$unique_vms[$vm->sstVirtualMachine] = $vm;
					}
				}
			}
	
			$criteriaVms = $criteria;
			$criteriaVms['relattr'] = array();
			$criteriaVms['relattr']['people'] = array('ou' => $user->uid);
			//echo '<pre>criteria ' . print_r($criteriaVms, true) . '</pre>';
					
			$vms = LdapVm::model()->findAll($criteriaVms);
			//echo 'people vmcount ' . count($vms) . '<br/>';
			foreach($vms as $vm) {
				//echo '<pre>	' . $vm->sstVirtualMachine . ', ' . $vm->sstDisplayName . ' (pool: ' . $vm->sstVirtualMachinePool . ')</pre>';
				if (!isset($unique_vms[$vm->sstVirtualMachine])) {
					$unique_vms[$vm->sstVirtualMachine] = $vm;
				}
			}
			//echo "$type vmcount " . count($unique_vms) . '<br />';
			//foreach($unique_vms as $vm) {
			//	echo '<pre>	' . $vm->sstVirtualMachine . ', ' . $vm->sstDisplayName . ' (pool: ' . $vm->sstVirtualMachinePool . ')</pre>';
			//}
		}
		return $unique_vms;
	}
	
	public static function getPoolsFromAssignedVms($type, $alsoFromPools=false, $attr=array()) {
		$pools = array();
		$vms = self::getAssignedVms($type, $attr);
		foreach ($vms as $vm) {
			if (!isset($pools[$vm->sstVirtualMachinePool])) {
				$pools[$vm->sstVirtualMachinePool] = $vm->vmpool;
			}
		}

		//echo "$type poolcount " . count($pools) . '<br />';
		//foreach($pools as $pool) {
		//	echo '<pre>	' . $pool->sstVirtualMachinePool . ', ' . $pool->sstDisplayName . '</pre>';
		//}
		
		return $pools;
	}
}