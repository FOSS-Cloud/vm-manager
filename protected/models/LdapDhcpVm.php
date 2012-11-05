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
 * LdapDhcpVm class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class LdapDhcpVm extends CLdapRecord {
	protected $_branchDn = 'cn=config-01,ou=dhcp,ou=networks,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'cn=*');
	protected $_dnAttributes = array('cn');
	protected $_objectClasses = array('sstVirtualizationNetwork', 'dhcpHost', 'top');

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'subnet' => array(self::BELONGS_TO_DN, '~2', 'LdapDhcpSubnet', 'cn'),
			'vm' => array(self::HAS_ONE, 'cn', 'LdapVm', 'sstVirtualMachine'),
			'vmtemplate' => array(self::HAS_ONE, 'cn', 'LdapVmFromTemplate', 'sstVirtualMachine'),
		);
	}

	protected function createAttributes() {
		parent::createAttributes();

		if (isset($this->_attributes['dhcphwaddress'])) {
			$this->_attributes['dhcphwaddress']['type'] = 'assozarray';
			$this->_attributes['dhcphwaddress']['typedata'] = "/^([\S]*)\s+(.*)$/";
		}
		if (isset($this->_attributes['dhcpstatements'])) {
			$this->_attributes['dhcpstatements']['type'] = 'assozarray';
			$this->_attributes['dhcpstatements']['typedata'] = "/^([\S]*)\s?(.*)$/";
		}
	}
}