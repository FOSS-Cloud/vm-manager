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

class UserForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $surname;
	public $givenname;
	public $username;
	public $password, $passwordcheck;
	public $mail;
	public $gender;
	public $telephone;
	public $mobile;
	public $userrole = 'user';
	public $language = 'en';
	public $usergroups = array();

	public function rules() {
		return array(
			array('dn, surname, givenname, username, mail, userrole', 'required', 'on' => 'update'),
			array('password, passwordcheck, gender, telephone, language, usergroups', 'safe', 'on' => 'update'),
			array('surname, givenname, username, mail, password, passwordcheck, userrole', 'required', 'on' => 'create'),
			array('dn, gender, telephone, language, usergroups', 'safe', 'on' => 'create'),
			array('username', 'match', 'pattern' => '/^[a-z0-9_]*$/', 'message' => Yii::t('user', 'Please use only<br/>a-z, 0-9 and the _ character.')),
			array('mail', 'email'),
			array('telephone, mobile', 'match', 'pattern' => '/^[0-9\s\+\/\(\)]*$/', 'message' => Yii::t('user', 'Please use only<br/>"0-9+()" characters and blank.')),
			array('passwordcheck', 'compare', 'compareAttribute' => 'password', 'allowEmpty' => true, 'on' => 'update'),
			array('passwordcheck', 'compare', 'compareAttribute' => 'password', 'allowEmpty' => false,'on' => 'create'),
			array('mail', 'uniqueEmail', 'filter'=>'(mail={mail})'),
			array('username', 'uniqueUsername', 'filter'=>'(cn={username})'),
		);
	}

	public function uniqueEmail($attribute, $params) {
		$check = true;
		if (!is_null($this->dn) && '' != $this->dn) {
			$user = CLdapRecord::model('LdapUser')->findByDn($this->dn);
			if ($user->mail == $this->$attribute) {
				$check = false;
			}
		}
		if ($check && '' !== $this->$attribute) {
			$server = CLdapServer::getInstance();
			$criteria = array();
			$count = 0;
			$criteria['branchDn'] = 'ou=people';
			$criteria['filter'] = str_replace('{' . $attribute . '}', $this->$attribute, $params['filter']);
			$result = $server->findAll(null, $criteria);
			$count = $result['count'];
			if(0 < $count) {
				$this->addError($attribute, Yii::t('user', 'Mail already in use!'));
			}
		}
	}

	public function uniqueUsername($attribute, $params) {
		$check = true;
		if (!is_null($this->dn) && '' != $this->dn) {
			$user = CLdapRecord::model('LdapUser')->findByDn($this->dn);
			if ($user->cn == $this->$attribute) {
				$check = false;
			}
		}
		if ($check) {
			$server = CLdapServer::getInstance();
			$criteria = array();
			$count = 0;
			$criteria['branchDn'] = 'ou=people';
			$criteria['filter'] = str_replace('{' . $attribute . '}', $this->$attribute, $params['filter']);
			$result = $server->findAll(null, $criteria);
			$count = $result['count'];
			if(0 < $count) {
				$this->addError($attribute, Yii::t('user', 'Username already in use!'));
			}
		}
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'surname' => Yii::t('user', 'surname'),
			'givenname' => Yii::t('user', 'givenname'),
			'username' => Yii::t('user', 'username'),
			'password' => Yii::t('user', 'password'),
			'passwordcheck' => Yii::t('user', 'passwordcheck'),
			'mail' => Yii::t('user', 'mail'),
			'gender' => Yii::t('user', 'gender'),
			'telephone' => Yii::t('user', 'telephone'),
			'mobile' => Yii::t('user', 'mobile'),
			'userrole' => Yii::t('user', 'userrole'),
			'language' => Yii::t('user', 'language'),
		);
	}

	public function getName() {
		return $this->surname . ', ' . $this->givenname;
	}

}