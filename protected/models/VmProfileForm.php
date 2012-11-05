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

class VmProfileForm extends CFormModel {
	public $dn;					/* used for update */
	public $path;				/* used for create */
	public $basis;				/* used for create */
	public $isofile;
	public $name;
	public $sstVolumeCapacity;
	public $sstClockOffset;
	public $sstMemory;
	public $sstVCPU;
	public $description;
	//public $profile;
	public $upstatus;

	public function rules()
	{
		return array(
			array('path, basis, upstatus, name, description, isofile, sstVolumeCapacity, sstClockOffset, sstMemory, sstVCPU', 'required', 'on' => 'create'),
			array('path, basis, upstatus, name, description, sstVolumeCapacity, sstClockOffset, sstMemory, sstVCPU', 'required', 'on' => 'createOther'),
			array('upstatus, description, sstVolumeCapacity, sstClockOffset, sstMemory, sstVCPU', 'required', 'on' => 'update'),
			array('name', 'match', 'pattern' => '/^[a-zA-Z0-9_ ]*$/', 'message' => Yii::t('vmprofile', 'Please use only a-z, A-Z, 0-9, the space and the _ character.')),
			array('name', 'uniqueName',
				'branches'=>array('ou=linux,ou=virtual machine profiles,ou=virtualization,ou=services','ou=windows,ou=virtual machine profiles,ou=virtualization,ou=services'),
				'filter'=>'(ou={name})',
			),
		);
	}

	public function uniqueName($attribute, $params) {
		$checkNames = true;
		if (!is_null($this->dn)) {
			$vm = CLdapRecord::model('LdapVmFromProfile')->findByDn($this->dn);
			if ($vm->ou == $this->$attribute) {
				$checkNames = false;
			}
		}
		if ($checkNames) {
			$server = CLdapServer::getInstance();
			$criteria = array();
			$count = 0;
			foreach($params['branches'] as $branch) {
				$criteria['branchDn'] = $branch;
				$criteria['filter'] = str_replace('{' . $attribute . '}', $this->$attribute, $params['filter']);
				$result = $server->findAll(null, $criteria);
				$count += $result['count'];
			}
			if(0 < $count) {
				$this->addError($attribute, Yii::t('vmprofile', 'Name already in use!'));
			}
		}
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
			'profile' => Yii::t('vmprofile', 'BaseProfile'),
		);
	}
}