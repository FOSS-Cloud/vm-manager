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

class VmTemplateForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $path;
	public $basis;
	public $name;
	public $vmpool;
	public $sstVolumeCapacity;
	public $sstClockOffset;
	public $sstMemory;
	public $sstVCPU;
	public $description;
	public $node;
	public $useStaticIP = 0;
	public $staticIP;
	public $ip;
	public $range;

	public function rules()
	{
		return array(
			array('path, basis, node, name, description, vmpool, sstVolumeCapacity, sstClockOffset, sstMemory, sstVCPU', 'required', 'on' => 'create'),
			array('name, description, vmpool, sstVolumeCapacity, sstClockOffset, sstMemory, sstVCPU', 'required', 'on' => 'update'),
			array('name', 'uniqueName',
				'branches'=>array('ou=virtual machines,ou=virtualization,ou=services'),
				'filter'=>'(&(sstDisplayName={name})(sstVirtualMachineType=template))',
			),
			array('range', 'length', 'allowEmpty' => false),
			array('useStaticIP', 'checkStatic'),
			array('staticIP', 'checkIp'),
		);
	}

	public function uniqueName($attribute,$params) {
		$checkNames = true;
		if (!is_null($this->dn)) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($this->dn);
			if ($vm->sstDisplayName == $this->$attribute) {
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
				$this->addError($attribute, Yii::t('vmtemplate', 'Name already in use!'));
			}
		}
	}

	public function checkStatic($attribute, $params) {
		if (0 == $this->$attribute) {
			return;
		}
		if ($this->range == 0) {
			$this->addError('range', Yii::t('vm', 'Please select a range!'));
		}
		if ('' == $this->staticIP) {
			$this->addError('staticIP', Yii::t('vm', 'IP Address required!'));
		}
	}

	public function checkIp($attribute,$params) {
		if (0 == $this->useStaticIP) {
			return;
		}
		$checkIp = true;
		if (!is_null($this->dn)) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($this->dn);
			//echo "DHCP: " . print_r($vm->network, true);
			//echo $vm->getIp() . '==' .  $this->$attribute. '<br/>';
			if ($vm->getIp() == $this->$attribute) {
				$checkIp = false;
			}
		}
		if ($checkIp) {
			$ipOK = false;

			$criteria = array('depth' => true, 'attr'=>array('objectclass' => 'sstVirtualizationNetworkRange', 'cn' => $this->range));
			$ranges = CLdapRecord::model('LdapDhcpRange')->findAll($criteria);
			$range = $ranges[0];
			if ('' == $this->$attribute) {
				$this->addError($attribute, Yii::t('vm', 'IP Address required!'));
			}
			else if (!$range->inRange($this->$attribute)) {
				$this->addError($attribute, Yii::t('vm', 'IP Address not in range!'));
			}
			else if (!$range->isFreeIp($this->$attribute)) {
				$this->addError($attribute, Yii::t('vm', 'IP Address already in use!'));
			}

//			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
//			foreach($subnets as $subnet) {
//				if ($subnet->isFreeIp($this->$attribute)) {
//					$ipOK = true;
//					break;
//				}
//			}
//			if (!$ipOK) {
//				$this->addError($attribute, Yii::t('vm', 'IP Address already in use!'));
//			}
		}
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'title' => Yii::t('vmtemplate', 'title'),
			'sstMemory' => Yii::t('vmtemplate', 'sstMemory'),
			'sstVolumeCapacity' => Yii::t('vmtemplate', 'sstVolumeCapacity'),
			'sstVCPU' => Yii::t('vmtemplate', 'sstVCPU'),
			'sstClockOffset' => Yii::t('vmtemplate', 'sstClockOffset'),
			'description' => Yii::t('vmtemplate', 'Description'),
			'profile' => Yii::t('vmtemplate', 'Profile'),
			'node' => Yii::t('vmtemplate', 'Node'),
			'ip' => Yii::t('vmtemplate', 'ip'),
			'useStaticIP' => Yii::t('vmtemplate', 'staticIP'),
		);
	}
}