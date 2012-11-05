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
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class LoginForm extends CFormModel
{
	public $realm;
	public $username;
	public $password;
	public $rememberMe;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return array(
			// realm, username and password are required
			array('realm, username, password', 'required'),
			array('username, password', 'length', 'allowEmpty' => false),
			// rememberMe needs to be a boolean
			array('rememberMe', 'boolean'),
			// realm needs to be checked
//			array('realm', 'checkRealm'),
			// username needs to be checked
			array('username', 'checkUser'),
			// password needs to be authenticated
			array('password', 'authenticate', 'skipOnError' => true),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'realm'=>Yii::t('site', 'Realm'),
			'username'=>Yii::t('site', 'Username'),
			'password'=>Yii::t('site', 'Password'),
			'rememberMe'=>Yii::t('site', 'Remember me next time'),
		);
	}

	public function checkRealm($attribute, $params)
	{
		if (!LdapUserIdentity::checkRealm($this->$attribute)) {
			$this->addError('realm',Yii::t('site', 'User not assigned to realm.'));
		}
	}

	public function checkUser($attribute, $params)
	{
		if (!LdapUserIdentity::checkUser($this->$attribute, $this->realm)) {
			$this->addError('username',Yii::t('site', 'Incorrect username.'));
		}
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute, $params)
	{
		$this->_identity = new LdapUserIdentity($this->username, $this->password, $this->realm);
		if(!$this->_identity->authenticate()) {
			switch($this->_identity->errorCode) {
				case LdapUserIdentity::ERROR_USERNAME_INVALID:
					$this->addError('username',Yii::t('site', 'Incorrect username.'));
					break;
				case LdapUserIdentity::ERROR_PASSWORD_INVALID:
					$this->addError('password',Yii::t('site', 'Incorrect password.'));
					break;
				case LdapUserIdentity::ERROR_REALM_INVALID:
					$this->addError('realm',Yii::t('site', 'User not assigned to realm.'));
					break;
			}
		}
	}


	/**
	 * Logs in the user using the given username and password in the model.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if($this->_identity === null)
		{
			$this->_identity = new LdapUserIdentity($this->username, $this->password, $this->realm);
			$this->_identity->authenticate();
		}
		if($this->_identity->errorCode === LdapUserIdentity::ERROR_NONE)
		{
			$duration = $this->rememberMe ? 3600*24*30 : 0; // 30 days
			Yii::app()->user->login($this->_identity, $duration);
			return true;
		}
		else
			return false;
	}
}
