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
 * LdapVmDevice class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVmDevice extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'ou=devices');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstVirtualizationVirtualMachineDevices', 'organizationalUnit', 'top');

	public function rules()
	{
		return array(
		);
	}

	public function relations()
	{
		return array(
			'disks' => array(self::HAS_MANY, 'dn', 'LdapVmDeviceDisk', '$model->getDn()', array('sstDisk' => '*')),
			'interfaces' => array(self::HAS_MANY, 'dn', 'LdapVmDeviceInterface', '$model->getDn()', array('sstInterface' => '*')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'sstSourceFile' => Yii::t('vmdevice', 'sstSourceFile'),
			'sstMacAddress' => Yii::t('vmdevice', 'sstMacAddress')
		);
	}

	public function getDiskByName($name) {
		$disks = $this->disks;
		if (!is_array($disks)) {
			$disks = array($disks);
		}
		foreach($disks as $disk) {
			if ($name == $disk->sstDisk) {
				return $disk;
			}
		}
		return null;
	}

	public function getDisksByDevice($device) {
		$retval = array();
		$disks = $this->disks;
		if (!is_array($disks)) {
			$disks = array($disks);
		}
		foreach($disks as $disk) {
			if ($device == $disk->sstDevice) {
				$retval[] = $disk;
			}
		}
		return $retval;
	}
}