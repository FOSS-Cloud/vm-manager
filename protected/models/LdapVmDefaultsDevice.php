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

class LdapVmDefaultsDevice extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'ou=devices');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstVirtualizationVirtualMachineDevices', 'organizationalUnit', 'top');

	public function rules()
	{
		return array(
			array('host', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('host', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			'disks' => array(self::HAS_MANY, 'dn', 'LdapVmDefaultsDeviceDisk', '$model->getDn()', array('sstDisk' => '*')),
			'interfaces' => array(self::HAS_MANY, 'dn', 'LdapVmDeviceInterface', '$model->getDn()', array('sstInterface' => '*')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'host' => Yii::t('node', 'host')
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
}