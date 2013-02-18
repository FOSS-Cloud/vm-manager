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
 * LdapVmPool class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 1.0
 */

class LdapVmPool extends CLdapRecord {
	protected $_branchDn = 'ou=virtual machine pools,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'sstVirtualMachinePool=*');
	protected $_dnAttributes = array('sstVirtualMachinePool');
	protected $_objectClasses = array('sstVirtualMachines', 'sstVirtualMachinePoolDynamicObjectClass', 'sstRelationship', 'top');

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'storagepools' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=storage pools,\' . $model->getDn()'),
			'nodes' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=nodes,\' . $model->getDn()'),
			'ranges' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=ranges,\' . $model->getDn()'),
			'groups' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=groups,\' . $model->getDn()'),
			'people' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=people,\' . $model->getDn()'),
			'vms' => array(self::HAS_MANY, 'sstVirtualMachinePool', 'LdapVm', 'sstVirtualMachinePool'),
			'runningDynVms' => array(self::HAS_MANY, 'sstVirtualMachinePool', 'LdapVm', 'sstVirtualMachinePool', array('sstVirtualMachineType' => 'dynamic', 'sstVirtualMachineSubType' => 'Desktop')),
			'settings' => array(self::HAS_ONE, 'dn', 'LdapVmPoolConfigurationSettings', '$model->getDn()', array('ou' => 'settings')),
			'backup' => array(self::HAS_ONE, 'dn', 'LdapConfigurationBackup', '$model->getDn()', array('ou' => 'backup')),
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

	public function getStoragePool() {
		$storagepools = $this->storagepools;
		if (0 == count($storagepools)) {
			return null;
		}
		$storagepool = $storagepools[0]->ou;
		return CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstStoragePool'=>$storagepool)));
	}

	public function getRange() {
		$ranges = $this->ranges;
		if (0 == count($ranges)) {
			return null;
		}
		$range = $ranges[0]->ou;
		return CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('attr'=>array('cn'=>$range), 'depth'=>true));
	}

	public function deleteNodes() {
		foreach($this->nodes as $node) {
			$node->delete();
		}
		$this->nodes = array();
	}

	public function deleteRanges() {
		foreach($this->ranges as $range) {
			$range->delete();
		}
		$this->ranges = array();
	}

	public function startVm($golden=null) {
		if ($this->sstBrokerMaximalNumberOfVirtualMachines <= count($this->runningDynVms)) {
			throw new Exception('There is currently no free workplace. Maximum number of virtual machines reached that are specified in the VM Pool. Contact your administrator or try it again later.');
		}
		if (is_null($golden)) {
			$golden = CLdapRecord::model('LdapVm')->findByAttributes(array('attr'=>array('sstVirtualMachine'=>$this->sstActiveGoldenImage)));
		}
		if ($golden->sstVirtualMachinePool != $this->sstVirtualMachinePool) {
			throw new Exception('No active golden image found for this Vm Pool. Contact your administrator or try it again later.');
		}
		$storagepool = $this->getStoragePool();
		if (is_null($storagepool)) {
			throw new Exception('No Storage Pool found for this Vm Pool. Contact your administrator or try it again later.');
		}

		// 'save' devices before
		$rdevices = $golden->devices;
		/* Create a copy to be sure that we will write a new record */
		$vmcopy = new LdapVm();
		/* Don't change the labeledURI; must refer to a default Profile */
		$vmcopy->attributes = $golden->attributes;
		$vmcopy->setOverwrite(true);

		$vmcopy->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
		$vmcopy->sstVirtualMachineType = 'dynamic';
		$vmcopy->sstVirtualMachineSubType = 'Desktop';

		// find best Node to start on */
		$vmcount = 12345678;
		$nodename = '';
		//echo 'find Node for dyn. VM from Pool: ' . $this->sstVirtualMachinePool . '<br/>';
		foreach($this->nodes as $node) {
			//echo 'checking node: ' . $node->ou;
			$name = $node->ou;
			$node = LdapNode::model()->findByDn('sstNode=' . $node->ou . ',ou=nodes,ou=virtualization,ou=services');
			if (!is_null($node)) {
				//echo '; found';
				$nodecount = 0;
				foreach($node->vms as $vm) {
					if ($vm->sstVirtualMachinePool === $this->sstVirtualMachinePool) {
						$nodecount++;
					}
				}
				//echo '; vms: ' . $nodecount;
				if ($nodecount < $vmcout) {
					$vmcount = $nodecount;
					$nodename = $node->getName();
					//echo '; node set!<br/>';
				}
			}
		}
		if ('' !== $nodename) {
			$vmcopy->sstNode = $nodename;
		}

		Yii::log('Try to create \'' . $vmcopy->sstVirtualMachine . '\' from \'' . $golden->sstVirtualMachine, 'profile', 'ext.ldaprecord.CLdapRecord');

		// necessary ?
		$vmcopy->sstVirtualMachinePool = $this->sstVirtualMachinePool;
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

		Yii::log('       created!', 'profile', 'ext.ldaprecord.CLdapRecord');

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
				$goldenimagepath = $golden->devices->getDiskByName('vda')->sstVolumeName . '.qcow2';
				$names = CPhpLibvirt::getInstance()->createBackingStoreVolumeFile($templatesdir, $storagepool->sstStoragePool, $goldenimagepath, $vmcopy->node->getLibvirtUri(), $disk->sstVolumeCapacity);
				if (false !== $names) {
					$disk->sstVolumeName = $names['VolumeName'];
					$disk->sstSourceFile = $names['SourceFile'];
				}
				else {
					$vmcopy->delete(true);
					throw new Exception('Unable to create backingstore volume!');
				}
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

		$range = $this->getRange();
		if (is_null($range)) {
			$vmcopy->delete(true);
			throw new Exception('No range found for this Vm Pool. Contact your administrator or try it again later.');
		}
		$freeIp = $range->getFreeIp();
		if (is_null($freeIp)) {
			$vmcopy->delete(true);
			throw new Exception('There is currently no free workplace. Maximum number of IP addresses in the Network Range(s) reached. Contact your administrator or try it again later.');
		}

		$dhcpvm = new LdapDhcpVm();
		$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
		$dhcpvm->cn = $vmcopy->sstVirtualMachine;
		$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
		$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
		$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

		$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
		$dhcpvm->dhcpStatements = 'fixed-address ' . $freeIp;
		$dhcpvm->save();

		$server = CLdapServer::getInstance();
		$data = array();
		$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
		$data['ou'] = array('people');
		$data['description'] = array('This is the assigned people subtree.');
		$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
		$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
		$dn = 'ou=people,' . $vmcopy->dn;
		$server->add($dn, $data);

		$user = CLdapRecord::model('LdapUser')->findByDn('uid=' . Yii::app()->user->getUID() . ',ou=people');
		if (!is_null($user)) {
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $user->uid;
			$data['description'] = array('This entry links to the user ' . $user->getName() . '.');
			$data['labeledURI'] = array('ldap:///' . $user->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=' . $user->uid . ',' . $dn;
			$server->add($dn, $data);
		}

		$resource = CPhpLibvirt::getInstance()->startDynVm($vmcopy->getStartParams());
		if (is_null($resource)) {
			$vmcopy->delete(true);
			return null;
		}
		return $vmcopy;
	}

	public function getFreeVm() {
		$retval = null;

		foreach($this->runningDynVms as $vm) {
			$vmpeople = $vm->people;
			//echo 'looking for vm: $vm->sstVirtualMachine';
			if (0 == count($vmpeople)) {
				$retval = $vm;
				break;
			}
		}
		return $retval;
	}

}