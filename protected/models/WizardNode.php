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
 * Licensed under the EUPL, Version 1.1 or � as soon they
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
 * WizardNode class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

// Todo:  implement a wizard for all Node Types


class WizardNode extends CFormModel {
	public $nodetype;
	//public $name;
	public $nodeip;
	public $nodeuser;
	public $nodepassword;
	//public $nodepasswordcheck;

	public function rules() {
		return array(
			array('nodetype, nodeip, nodeuser, nodepassword', 'required'),
//			array('name', 'uniqueName',
//				'branches'=>array('ou=nodes,ou=virtualization,ou=services'),
//				'filter'=>'(sstNode={name})',
//			),
			array('nodeip', 'checkIp'),
//			array('nodepassword', 'compare', 'compareAttribute' => 'nodepasswordcheck', 'allowEmpty' => false),
		);
	}

	public function uniqueName($attribute,$params) {
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
			$this->addError($attribute, Yii::t('node', 'Name already in use!'));
		}
	}

	public function checkIp($attribute,$params) {
		if (1 == preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/i', $this->$attribute)) {
		}
		else {
			$this->addError($attribute, Yii::t('node', 'Not a valid IP Address!'));
		}
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'nodetype' => Yii::t('node', 'wizard.type'),
			'nodeip' => Yii::t('node', 'wizard.ip'),
			'nodeuser' => Yii::t('node', 'wizard.user'),
			'nodepassword' => Yii::t('node', 'wizard.password'),
//			'nodepasswordcheck' => Yii::t('node', 'wizard.passwordcheck'),
		);
	}

	public function getForm() {
		return new CForm(array(
			'showErrorSummary'=>false,
			'elements'=>array(
				'nodetype'=>array(
					'type'=>'listbox',
					'items'=>LdapNode::getAllTypeDefinitions(),
					'multiple'=>'multiple',
				),
//				'name'=>array(
//					'enableAjaxValidation'=>true,
//					'enableClientValidation'=>true,
//				),
				'nodeip'=>array(),
				'nodeuser'=>array('attributes' => array('autocomplete' => 'off')),
				'nodepassword'=>array(
					'type'=>'password'
				),
//				'nodepasswordcheck'=>array(
//					'type'=>'password'
//				),
			),
			'buttons'=>array(
				'submit'=>array(
					'type'=>'submit',
					'label'=>'Next'
				),
				'cancel'=>array(
					'type'=>'submit',
					'label'=>'Cancel',
					'style'=>'margin-left: 40px;'
				)
			)
		), $this);
	}

}
