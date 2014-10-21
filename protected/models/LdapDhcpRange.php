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
 * LdapDhcpRange class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class LdapDhcpRange extends CLdapRecord {
	protected $_branchDn = 'cn=config-01,ou=dhcp,ou=networks,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'cn=*');
	protected $_dnAttributes = array('cn');
	protected $_objectClasses = array('sstVirtualizationNetworkRange', 'top');

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'subnet' => array(self::BELONGS_TO_DN, '~2', 'LdapDhcpSubnet', 'dn'),
		);
	}

	public function getRange() {
		return Utils::getIpRange($this->cn);
	}

	public function getRangeAsString() {
		$range = $this->getRange();
		return $range['hostmin'] . ' - ' . $range['hostmax'];
	}

	public function getFreeIp() {
		$retval = null;
		$range = $this->getRange();
		$subnet = $this->subnet;
		$ips = array();
		$router = $subnet->dhcpOption['routers'];
		$ips[] = ip2long($router);
		//echo '<pre>' . print_r($subnet, true) . '</pre>';
		$vms = $subnet->vms;
		foreach($vms as $vm) {
			$ips[] = ip2long($vm->dhcpStatements['fixed-address']);
		}
		$hostmin_dec = ip2long($range['hostmin']);
		$hostmax_dec = ip2long($range['hostmax']);
		for($i=$hostmin_dec; $i<=$hostmax_dec; $i++) {
			if (!in_array($i, $ips)) {
				$retval = long2ip($i);
				break;
			}
		}
		return $retval;
	}

	public function overlap($ip, $netmask) {
		return Utils::overlapRanges($this->cn, $ip . '/' . $netmask);
	}

	public function inRange($ip) {
		return Utils::isIpInRange($ip, $this->cn);
	}

	public function isFreeIp($ip) {
		$retval = false;
		if ($this->inRange($ip)) {
			$subnet = $this->subnet;
			//echo '<pre>' . print_r($subnet, true) . '</pre>';
			$ips = array();
			$router = $subnet->dhcpOption['routers'];
			$ips[] = $router;
			$vms = $subnet->vms;
			foreach($vms as $vm) {
				$ips[] = $vm->dhcpStatements['fixed-address'];
			}
			$retval = !in_array($ip, $ips);
		}
		return $retval;
	}

	public function isUsed() {
		$criteria = array(
			'branchDn' => 'ou=virtual machine pools,ou=virtualization,ou=services',
			'filter' => '(&(objectClass=organizationalUnit)(ou=' . $this->CN . '))',
			'depth' => true
		);
		$result = LdapNameless::model()->findAll($criteria);

		return 0 < count($result);
	}
}