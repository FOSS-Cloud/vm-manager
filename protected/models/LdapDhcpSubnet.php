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
 * LdapDhcpSubnet class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class LdapDhcpSubnet extends CLdapRecord {
	protected $_branchDn = 'cn=config-01,ou=dhcp,ou=networks,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'cn=*');
	protected $_dnAttributes = array('cn');
	protected $_objectClasses = array('dhcpOptions', 'dhcpSubnet', 'sstVirtualizationNetwork', 'top');

	protected function createAttributes() {
		parent::createAttributes();

		if (isset($this->_attributes['dhcpoption'])) {
			$this->_attributes['dhcpoption']['type'] = 'assozarray';
			$this->_attributes['dhcpoption']['typedata'] = "/^([\S]*)\s+(.*)$/";
		}
		if (isset($this->_attributes['dhcpstatements'])) {
			$this->_attributes['dhcpstatements']['type'] = 'assozarray';
			$this->_attributes['dhcpstatements']['typedata'] = "/^([\S]*)\s?(.*)$/";
		}
	}

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'ranges' => array(self::HAS_MANY, 'dn', 'LdapDhcpRange', '\'ou=ranges,\' . $model->getDn()'),
			'vms' => array(self::HAS_MANY, 'dn', 'LdapDhcpVm', '\'ou=virtual machines,\' . $model->getDn()'),
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

	public function inRange($ip) {
		return Utils::isIpInRange($ip, $this->cn . '/' . $this->dhcpNetMask);
	}

	public function isFreeIp($ip)
	{
		$retval = false;
		foreach($this->ranges as $range) {
			if($range->isFreeIp($ip)) {
				$retval = true;
				break;
			}
		}
		return $retval;
	}

	public function overlap($ip, $netmask) {
		return Utils::overlapRanges($this->cn . '/' . $this->dhcpNetMask, $ip . '/' . $netmask);
	}

	public function getBroadcast() {
		$range = Utils::getIpRange($this->cn . '/' . $this->dhcpNetMask);
		return $range['broadcast'];
	}


	public function isUsed() {
		$retval = false;
		foreach($this->ranges as $range) {
			if($range->isUsed()) {
				$retval = true;
				break;
			}
		}
		return $retval;
	}
}