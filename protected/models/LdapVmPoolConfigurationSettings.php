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

class LdapVmPoolConfigurationSettings extends LdapConfigurationSettings {
	protected $_branchDn = '';

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'settings' => array(self::HAS_MANY, 'dn', 'LdapConfigurationSetting', '$model->getDn()', array('ou' => '*')),
			'defaultSettings' => array(self::HAS_ONE, 'dn', 'LdapConfigurationSettings', '\'ou=configuration,ou=virtualization,ou=services\'', array('ou' => 'settings')),
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

	public function isSoundAllowed(&$location=null) {
		$retval = false;
		$setting = $this->getSetting('sound');
		if (!is_null($setting) && isset($setting->sstAllowSound)) {
			if (is_null($location)) {
				$this->location['sound'] = 'pool';
			}
			else {
				$location['sound'] = 'pool';
			}
			return 'TRUE' === $setting->sstAllowSound;
		}
		else {
			if (is_null($location)) {
				$retval = $this->defaultSettings->isSoundAllowed($this->location);
			}
			else {
				$retval = $this->defaultSettings->isSoundAllowed($location);
			}
		}
		return $retval;
	}

	public function isUsbAllowed(&$location=null) {
		$retval = false;
		$setting = $this->getSetting('usb');
		if (!is_null($setting) && isset($setting->sstAllowUSB)) {
			if (is_null($location)) {
				$this->location['usb'] = 'pool';
			}
			else {
				$location['usb'] = 'pool';
			}
			return 'TRUE' === $setting->sstAllowUSB;
		}
		else {
			if (is_null($location)) {
				$retval = $this->defaultSettings->isUsbAllowed($this->location);
			}
			else {
				$retval = $this->defaultSettings->isUsbAllowed($location);
			}
		}
		return $retval;
	}

	public function isSpiceAllowed(&$location=null) {
		$retval = false;
		$setting = $this->getSetting('spice');
		if (!is_null($setting) && isset($setting->sstAllowSpice)) {
			if (is_null($location)) {
				$this->location['spice'] = 'pool';
			}
			else {
				$location['spice'] = 'pool';
			}
			return 'TRUE' === $setting->sstAllowSpice;
		}
		else {
			if (is_null($location)) {
				$retval = $this->defaultSettings->isSpiceAllowed($this->location);
			}
			else {
				$retval = $this->defaultSettings->isSpiceAllowed($location);
			}
		}
		return $retval;
	}
}