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
 * LdapVmDefaults class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVmDefaults extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'ou=devices');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstVirtualizationVirtualMachine', 'sstVirtualizationVirtualMachineDefaults', 'top');

	public function relations()
	{
		return array(
			'devices' => array(self::HAS_ONE, 'dn', 'LdapVmDefaultsDevice', '$model->getDn()', array('ou' => 'devices')),
		);
	}

	public function getVolumeCapacityMin() {
		return $this->devices->getDiskByName('vda')->sstVolumeCapacityMin;
	}
	public function setVolumeCapacityMin($newMin, $ow) {
		$this->devices->getDiskByName('vda')->setOverwrite($ow);
		$this->devices->getDiskByName('vda')->sstVolumeCapacityMin = $newMin;
	}
	public function getVolumeCapacityMax() {
		return $this->devices->getDiskByName('vda')->sstVolumeCapacityMax;
	}
	public function getVolumeCapacityStep() {
		return $this->devices->getDiskByName('vda')->sstVolumeCapacityStep;
	}
}