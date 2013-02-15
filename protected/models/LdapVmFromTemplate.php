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
 * LdapVmFromTemplate class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVmFromTemplate extends LdapVm {
//	protected $_branchDn = 'ou=virtual machine templates,ou=virtualization,ou=services';
	protected $_branchDn = 'ou=virtual machines,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'sstVirtualMachine=*');
	protected $_dnAttributes = array('sstVirtualMachine');
	protected $_objectClasses = array('sstVirtualizationVirtualMachine', 'sstSpice', 'labeledURIObject', 'top');

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
			'node' => array(self::HAS_ONE, 'sstNode', 'LdapNode', 'sstNode'),
			'network' => array(self::HAS_ONE_DEPTH, 'sstVirtualMachine', 'LdapNetwork', 'cn'),
			'devices' => array(self::HAS_ONE, 'dn', 'LdapVmDevice', '$model->getDn()', array('ou' => 'devices')),
			'profilevm' => array(self::HAS_ONE_DN, 'dn', 'LdapVmFromProfile', '$model->labeledURI', array()),
			'defaults' => array(self::HAS_ONE_DN, 'dn', 'LdapVmDefaults', '$model->labeledURI', array()),
			'dhcp' => array(self::HAS_ONE_DEPTH, 'sstVirtualMachine', 'LdapDhcpVm', 'cn', array('objectclass' => 'dhcpHost')),
			'vmpool' => array(self::HAS_ONE, 'sstVirtualMachinePool', 'LdapVmPool', 'sstVirtualMachinePool'),
			'backup' => array(self::HAS_ONE, 'dn', 'LdapVmBackup', '$model->getDn()', array('ou' => 'backup')),
			'settings' => array(self::HAS_ONE, 'dn', 'LdapVmConfigurationSettings', '$model->getDn()', array('ou' => 'settings')),
		);
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

}