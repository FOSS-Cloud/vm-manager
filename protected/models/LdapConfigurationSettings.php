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

class LdapConfigurationSettings extends CLdapRecord {
	protected $_branchDn = 'ou=configuration,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'ou=*');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('organizationalUnit', 'top');

	protected $location = array();
	
	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'settings' => array(self::HAS_MANY, 'dn', 'LdapConfigurationSetting', '$model->getDn()', array('ou' => '*')),
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
		if (!is_null($setting)) {
			if (is_null($location)) {
				$this->location['sound'] = 'global';
			}
			else {
				$location['sound'] = 'global';
			}
			return isset($setting->sstAllowSound) && 'TRUE' === $setting->sstAllowSound;
		}
		return $retval;
	}
	
	public function isUsbAllowed(&$location=null) {
		$retval = false;
		$setting = $this->getSetting('usb');
		if (!is_null($setting)) {
			if (is_null($location)) {
				$this->location['usb'] = 'global';
			}
			else {
				$location['usb'] = 'global';
			}
			return isset($setting->sstAllowUSB) && 'TRUE' === $setting->sstAllowUSB;
		}
		return $retval;
	}
	
	public function isSpiceAllowed(&$location=null) {
		$retval = false;
		$setting = $this->getSetting('spice');
		if (!is_null($setting)) {
			if (is_null($location)) {
				$this->location['spice'] = 'global';
			}
			else {
				$location['spice'] = 'global';
			}
			return isset($setting->sstAllowSpice) && 'TRUE' === $setting->sstAllowSpice;
		}
		return $retval;
	}
	
	public function getSoundLocation() {
		return $this->location['sound'];
	}
	public function getUsbLocation() {
		return $this->location['usb'];
	}
	public function getSpiceLocation() {
		return $this->location['spice'];
	}
	
	public function getSoundSetting() {
		return $this->getSetting('sound');
	}
	public function getUsbSetting() {
		return $this->getSetting('usb');
	}
	public function getSpiceSetting() {
		return $this->getSetting('spice');
	}
	
	protected function getSetting($name) {
		$settings = $this->settings;
		if (!is_array($settings)) {
			$settings = array($settings);
		}
		foreach($settings as $setting) {
			if ($name === $setting->ou) {
				return $setting;
			}
		}
		return null;
	}
}