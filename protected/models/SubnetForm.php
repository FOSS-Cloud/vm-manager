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

class SubnetForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $ip;
	public $netmask;
	public $name;
	public $domainname = '';
	public $domainservers = '';
	public $defaultgateway = '';
	public $broadcastaddress = '';
	public $ntpservers = '';

	public function rules()
	{
		return array(
			array('ip, netmask, name', 'required'),
			array('domainname, domainservers, defaultgateway, broadcastaddress, ntpservers', 'safe'),
			array('domainname', 'CDomainValidator', 'useDns'=>true),
			array('ip', 'checkIp'),
			array('netmask', 'length', 'allowEmpty' => false),
		);
	}

	public function checkIp($attribute,$params) {
		if (1 == preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/i', $this->$attribute)) {
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			foreach($subnets as $subnet) {
				if (is_null($this->dn) || $this->dn != $subnet->dn) {
					if ($subnet->overlap($this->$attribute, $this->netmask)) {
						$this->addError($attribute, Yii::t('subnet', 'Overlaps with subnet {subnet}', array('{subnet}' => $subnet->cn . '/' . $subnet->dhcpNetMask)));
						break;
					}
				}
			}
		}
		else {
			$this->addError($attribute, Yii::t('subnet', 'Not a valid IP Address!'));
		}

	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'ip' => Yii::t('subnet', 'IP Start Address'),
			'netmask' => Yii::t('subnet', 'Net Mask'),
			'title' => Yii::t('subnet', 'title'),
			'type' => Yii::t('subnet', 'Type'),
			'domainname' => Yii::t('subnet', 'Domain Name'),
			'domainservers' => Yii::t('subnet', 'Domain Server(s)'),
			'defaultgateway' => Yii::t('subnet', 'Default Gateway'),
			'broadcastaddress' => Yii::t('subnet', 'Broadcast Address'),
			'ntpservers' => Yii::t('subnet', 'NTP Server(s)'),
		);
	}
}