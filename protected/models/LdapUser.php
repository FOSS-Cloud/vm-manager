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

class LdapUser extends CLdapRecord {
	protected $_branchDn = 'ou=people';
	protected $_filter = array('all' => 'uid=*');
	protected $_dnAttributes = array('uid');
	protected $_objectClasses = array('sstPerson', 'top');

	public function relations()
	{
		return array(
			//'roles' => array(self::HAS_MANY, 'dn', 'LdapUserRole', '$model->getDn()'),
			'role' => array(self::HAS_ONE, 'sstRoleUID', 'LdapUserRole', 'uid'),
			'assign' => array(self::HAS_ONE_DN, 'dn', 'LdapUserAssign', '\'ou=\' . $model->uid . \',ou=people,ou=\' . Yii::app()->user->realm . \',ou=authentication,ou=virtualization,ou=services\''),
		);
	}

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getName() {
		return $this->surname . ', ' . $this->givenname;
	}

	public function isAdmin() {
		$retval = false;
// 		$roles = $this->roles;
// 		foreach($this->roles as $role) {
// 			if ('User' != $role->sstRole) {
// 				$retval = true;
// 				break;
// 			}
// 		}
		if (!is_null($this->role) && 'Admin' === $this->role->sstDisplayName) {
			$retval = true;
		}
		else {
			$criteria = array('branchDn' => $this->getDn(), 'attr' => array());
			$roles = LdapUserRoleOld::model()->findAll($criteria);
			if (!is_null($roles)) {
				foreach($roles as $role) {
					if ('Admin' == substr($role->sstRole, 0, 5)) {
						$retval = true;
						break;
					}
				}
			}
		}
		return $retval;
	}

	public function isForeign() {
		return isset($this->sstLDAPForeignStaticAttribute) && '' != $this->sstLDAPForeignStaticAttribute;
	}

	public function isAssignedToVm($dn) {
		$retval = false;
		$server = CLdapServer::getInstance();
		$result = $server->search('ou=people,' . $dn, '(&(ou=' . $this->uid  . '))', array('dn'));
		//echo '<pre>' . print_r($result, true) . '</pre>';
		$retval = 0 != $result['count'];
		return $retval;
	}

	public function isAssignedToVmPool($dn) {
		$retval = false;
		$server = CLdapServer::getInstance();
		$result = $server->search('ou=people,' . $dn, '(&(ou=' . $this->uid  . '))', array('dn'));
		//echo '<pre>' . print_r($result, true) . '</pre>';
		$retval = 0 != $result['count'];
		return $retval;
	}

	public function isActiveUser() {
		return $this->UID == Yii::app()->user->uid;
	}

	public function checkPassword($pwdattr, $password) {
		//echo '<pre>PWD-attr: ' . $pwdattr . '<br/>';
		$ldappassword = $this->$pwdattr;
		if (preg_match('/{([^}]+)}(.*)/', $ldappassword, $matches)) {
			$cryptedpassword = $matches[2];
			$type = strtolower($matches[1]);
		}
		//echo "PWD-type: " . $type . '<br/>';
		//echo "PWD-orig: " . $cryptedpassword . '<br/>';
		$authOk = false;
		switch(strtolower($type)) {
			case 'sha':
				$given = LdapUser::encodePassword($password, 'sha');
				//echo "PWD-check: " . $given . '<br/></pre>';
				$authOk = 0 == strcasecmp($given, $ldappassword);
				break;
			case 'ssha':
				$given = LdapUser::encodePassword($password, 'ssha');
				if (function_exists('mhash')) {
					$password = $password;
					$hash = base64_decode($cryptedpassword);
					$salt = substr($hash, 20);
					$given = base64_encode(mhash(MHASH_SHA1, $password . $salt) . $salt);
					$authOk = 0 == strcmp($given, $cryptedpassword);
				}
				else {
					new CException(Yii::t('user', 'PHP function mhash needed to encode password for encryption type "SSHA"'));
				}
				break;
			default:
				new CException(Yii::t('user', 'Unknown encryption type "{type}" for LDAP user authentication!', array('type', $type)));
				break;
		}
		return $authOk;
	}

	public static function encodePassword($password, $type) {
		$retval = null;
		//echo '<pre>' . print_r($server, true) . '</pre>';
		switch($type) {
			case 'sha':
				$retval = '{SHA}' . base64_encode(pack('H*', sha1($password)));
				break;
			case 'ssha':
				if (function_exists('mhash') && function_exists('mhash_keygen_s2k')) {
					mt_srand((double)microtime() * 1000000);
					$salt = mhash_keygen_s2k(MHASH_SHA1, $password, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
					$retval = '{SSHA}' . base64_encode(mhash(MHASH_SHA1, $password . $salt) . $salt);
				}
				else {
					new CException(Yii::t('user', 'PHP function mhash and mhash_keygen_s2k for encryption type "SSHA"'));
				}
				break;
			default:
				new CException(Yii::t('user', 'Unknown encryption type "{type}" for LDAP user authentication!', array('type', $type)));
				break;
		}
		//echo '<pre>PWD: ' . print_r($retval, true) . '</pre>';
		return $retval;
	}

	public static function getGender() {
		return array(
			'm' => Yii::t('user', 'Male'),
			'f' => Yii::t('user', 'Female')
		);
	}

	public static function getLanguages() {
		$retval = array();

		if (!isset($_SESSION['languages'])) {
			$dh = opendir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'messages');
			while (($file = readdir($dh)) !== false) {
				if ('.' != $file && '..' != $file &&
				is_dir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'messages' . DIRECTORY_SEPARATOR . $file)) {
					$retval[$file] = strtoupper($file);
				}
			}
			closedir($dh);
			$_SESSION['languages'] = $retval;
		}
		else {
			$retval = $_SESSION['languages'];
		}
		return $retval;
	}
}

/**
 * Representing a user role of the previous role management.
 * Only needed to allow login before patch was made
 * 
 * Todo: remove in next version
 *
 */
class LdapUserRoleOld extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'sstRole=*');
	protected $_dnAttributes = array('sstRole');
	protected $_objectClasses = array('sstRoles', 'top');

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	*/
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}