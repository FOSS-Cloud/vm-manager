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

class RangeForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $subnetDn;
	public $subnet;
	public $ip;
	public $netmask;
	public $name;
	public $type;

	public function rules()
	{
		return array(
			array('subnetDn, subnet, ip, netmask, name, type', 'required', 'on' => 'create'),
			array('dn', 'safe', 'on' => 'create'),
			array('subnetDn, subnet, dn, ip, netmask, name, type', 'required', 'on' => 'update'),
			array('ip', 'checkRange'),
			array('netmask, type', 'length', 'allowEmpty' => false),
			array('netmask', 'checkNetmask'),
		);
	}


	public function checkRange($attribute,$params) {
		if (1 == preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/i', $this->$attribute)) {
			if ('' !== $this->netmask) {
				if (!Utils::isRangeInRange($this->subnet, $this->$attribute . '/' . $this->netmask)) {
					$this->addError($attribute, Yii::t('subnet', 'Not within subnet {subnet}', array('{subnet}' => $this->subnet)));
				}
				else {
					$subnet = CLdapRecord::model('LdapDhcpSubnet')->findByDn($this->subnetDn);
					$ranges = $subnet->ranges;
					foreach($ranges as $range) {
						if (is_null($this->dn) || $this->dn != $range->dn) {
							if ($range->overlap($this->$attribute, $this->netmask)) {
								$this->addError($attribute, Yii::t('subnet', 'Overlaps with range {range}', array('{range}' => $range->cn)));
								break;
							}
						}
					}
				}
			}
		}
		else {
			$this->addError($attribute, Yii::t('subnet', 'Not a valid IP Address!'));
		}
		if (0 === count($this->getErrors($attribute)) && is_array($params) && isset($params['recursive']) && $params['recursive']) {
			$this->checkNetmask('netmask', array('recursive'=>true));
		}
	}

	public function checkNetmask($attribute,$params) {
		if ('' !== $this->ip) {
			if (!Utils::isRangeInRange($this->subnet, $this->ip . '/' . $this->$attribute)) {
				$this->addError($attribute, Yii::t('subnet', 'Not within subnet {subnet}', array('{subnet}' => $this->subnet)));
			}
			else {
				$subnet = CLdapRecord::model('LdapDhcpSubnet')->findByDn($this->subnetDn);
				$ranges = $subnet->ranges;
				foreach($ranges as $range) {
					if (is_null($this->dn) || $this->dn != $range->dn) {
						if ($range->overlap($this->ip, $this->$attribute)) {
							$this->addError($attribute, Yii::t('subnet', 'Overlaps with range {range}', array('{range}' => $range->cn)));
							break;
						}
					}
				}
			}
		}
		if (0 === count($this->getErrors($attribute)) && is_array($params) && isset($params['recursive']) && $params['recursive']) {
			$this->checkRange('ip', array('recursive'=>true));
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
		);
	}
}