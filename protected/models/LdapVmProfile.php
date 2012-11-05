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

class LdapVmProfile extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'objectClass=*');
	protected $_dnAttributes = array('sstVirtualMachine');
	protected $_objectClasses = array('organizationalUnit', 'top');

//	public $path;
//	public $basis;
//	public $isofile;
//	public $name;
//	public $sstVolumeCapacity;
	public $vmsubtree;

	public function rules()
	{
		return array(
			array('name, description', 'required'),
			//array('isofile', 'file', 'allowEmpty'=>false),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('title', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'vm' => array(self::HAS_ONE_DEPTH_BRANCH, '*', 'LdapVmFromProfile', 'sstVirtualMachine', array('objectclass' => 'sstVirtualizationVirtualMachine')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'title' => Yii::t('vmprofile', 'title'),
			'sstMemory' => Yii::t('vmprofile', 'sstMemory'),
			'sstVolumeCapacity' => Yii::t('vmprofile', 'sstVolumeCapacity'),
			'sstVCPU' => Yii::t('vmprofile', 'sstVCPU'),
			'sstClockOffset' => Yii::t('vmprofile', 'sstClockOffset'),
			'description' => Yii::t('vmprofile', 'Description'),
		);
	}
}