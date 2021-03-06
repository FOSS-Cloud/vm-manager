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
 * Licensed under the EUPL, Version 1.1 or higher - as soon they
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

/**
 * LdapUserIdentity class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

/**
 * LdapUserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class LdapUserIdentity extends CUserIdentity
{
	const ERROR_REALM_INVALID = 3;

	/**
	 * @var string realm
	 */
	public $realm;

	/**
	 * Constructor.
	 * @param string $username username
	 * @param string $password password
	 */
	public function __construct($username, $password, $realm)
	{
		parent::__construct($username, $password);
		$this->realm = $realm;
	}

	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		Yii::log('LdapUserIdentity::authenticate', 'profile', 'authentication');

		$server = CLdapServer::getInstance();
		$realm = CLdapRecord::model('LdapRealm')->findByAttributes(array('attr'=>array('ou'=>$this->realm)));
		if (!is_null($realm)) {
			if ('TRUE' != $realm->sstLDAPExternalDirectory) {
				$this->authenticateIntern($realm, $realm->usersearch, $realm->userauth, $realm->usergroupsearch);
			}
			else {
				// We use an external directory
				$parts = explode(':', $realm->labeledURI);
				$hostname = $parts[0] . ':' . $parts[1];
				$port = $parts[2];
				$connection = @ldap_connect($hostname, $port);
				if ($connection === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}
				ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				$ldapbind = @ldap_bind($connection, $realm->sstLDAPBindDn, $realm->sstLDAPBindPassword);
				if ($ldapbind === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_bind to {server} failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}

				$usersearch = $realm->usersearch;
				$branchDn = $usersearch->sstLDAPBaseDn;
				$filter = sprintf($usersearch->sstLDAPFilter, $this->username);
//echo "branchDn: $branchDn; filter: $filter<br/>";
				Yii::log('LdapUserIdentity::authenticate: checkUser ' . $branchDn . ', ' . $filter, 'profile', 'authentication');
				$result = @ldap_search($connection, $branchDn, $filter);
				if ($result === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
				}
				$entries = @ldap_get_entries($connection, $result);
				if ($entries === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
				}
//echo '<pre>' . print_r($entries, true) . '</pre>';

				if (1 == $entries['count']) {
					Yii::log('LdapUserIdentity::authenticate: checkUser found!', 'profile', 'authentication');
					$staticAttrName = $usersearch->sstLDAPForeignStaticAttribute;
					//echo $staticAttrName . '; ';
					if (isset($entries[0][$staticAttrName])) {
						if (is_array($entries[0][$staticAttrName])) {
							$staticAttr = $entries[0][$staticAttrName][0];
						}
						else {
							$staticAttr = $entries[0][$staticAttrName];
						}
					}
					else {
						throw new CLdapException(Yii::t('LdapComponent.server', 'usersearch: sstLDAPForeignStaticAttribute ({attr}) not found!',
							array('{attr}'=>$staticAttrName)));
					}
					//echo $staticAttr . '<br/>';
					$mapping = array();
					if (isset($usersearch->sstLDAPInternalForeignMapping)) {
						foreach($usersearch->sstLDAPInternalForeignMapping as $intext) {
							list($intern, $foreign) = preg_split('/:/', $intext);
							if ('' == $foreign) {
								$mapping[$intern] = '';
							}
							else {
								$mapping[$intern] = $entries[0][strtolower($foreign)][0];
							}
						}
					}
//echo '<pre>' . print_r($mapping, true) . '</pre>';

					$userauth = $realm->userauth;
					$authOk = $server->authorizeUserExtern($realm->labeledURI, $userauth, $staticAttr, $this->password);
					if (!$authOk) {
						Yii::log('LdapUserIdentity::authenticate: extern ERROR', 'profile', 'authentication');
						$this->errorCode = self::ERROR_PASSWORD_INVALID;
					}
					else {
						Yii::log('LdapUserIdentity::authenticate: extern OK', 'profile', 'authentication');
						$usergroupsearch = $realm->usergroupsearch;
						$branchDn = $usergroupsearch->sstLDAPBaseDn;
						$filter = sprintf($usergroupsearch->sstLDAPFilter, $staticAttr);
//echo "branchDn: $branchDn; filter: $filter<br/>";
						Yii::log('LdapUserIdentity::authenticate: getGroups ' . $branchDn . ', ' . $filter, 'profile', 'authentication');
						
						$result = @ldap_search($connection, $branchDn, $filter);
						if ($result === false) {
							throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failt ({errno}): {message}',
								array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
						}
						$entries = @ldap_get_entries($connection, $result);
						if ($entries === false) {
							throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failt ({errno}): {message}',
								array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
						}
//echo '<pre>' . print_r($entries, true) . '</pre>';

						$groups = $this->handleGroupData($entries, $usergroupsearch->sstLDAPReturnAttribute);
//echo '<pre>' . print_r($groups, true) . '</pre>';

						$user = LdapUser::model()->findByAttributes(array('attr'=>array('sstLDAPForeignStaticAttribute'=>$staticAttr)));
//echo '<pre>User (sstLDAPForeignStaticAttribute=' . $staticAttr . '; ' . print_r($user, true) . '</pre>';

						if (is_null($user)) {
							$user = new LdapUser();
							$user->setBranchDn('ou=people');
							$uid = Yii::app()->controller->getNextUid();
							while (is_null($uid)) {
								sleep(2);
								$uid = Yii::app()->controller->getNextUid();
							}

							$user->uid = $uid;
							$user->sstLDAPForeignStaticAttribute = $staticAttr;
							$user->givenName = '';
							$user->sn = '';
							$user->mail = '';
							$user->sstGender = '?';
							$user->cn = $this->username;
							$user->userPassword = LdapUser::encodePassword(Utils::generatePassword(), strtolower($server->getEncryptionType()));
							//$user->telephoneNumber = $model->telephone;
							//$user->mobile = $model->mobile;
							$user->preferredLanguage = 'en-GB';
							$user->sstTimeZoneOffset = 'UTC+01';
							$user->sstBelongsToCustomerUID = $realm->sstBelongsToCustomerUID;
							$user->sstBelongsToResellerUID = $realm->sstBelongsToResellerUID;
							if (0 < count($groups)) {
								$user->sstGroupUID = $groups;
							}
							$user->setOverwrite(true);
							foreach($mapping as $attr => $value) {
								$user->$attr = $value;
							}

							$userrole = LdapUserRole::model()->findByAttributes(array('attr'=>array('sstDisplayName'=>'VM-User')));
							if (!is_null($userrole)) {
								$user->sstRoleUID = $userrole->uid;
							}

							$user->save();
						}

						$this->errorCode = self::ERROR_NONE;
// 						Yii::app()->user->setState('__uid', $user->uid);
// 						Yii::app()->user->setState('__groupuids', $groups);
// 						Yii::app()->user->setState('__realm', $this->realm);
// 						Yii::app()->user->setState('__admin', false);
// 						Yii::app()->user->setState('__customeruid', $realm->sstBelongsToCustomerUID);
// 						Yii::app()->user->setState('__reselleruid', $realm->sstBelongsToResellerUID);

						$this->setState('uid', $user->uid);
						$this->setState('groupuids', $groups);
						$this->setState('roleuid', $user->sstRoleUID);
						$this->setState('realm', $this->realm);
						$this->setState('admin', false);
						$this->setState('customeruid', $realm->sstBelongsToCustomerUID);
						$this->setState('reselleruid', $realm->sstBelongsToResellerUID);
						$this->setState('lang', $user->preferredLanguage);

						$rights = array();
						if (!is_null($user->role)) {
							foreach($user->role->rights as $right) {
								list($group, $action) = explode('.', $right->sstUserRightSectionAction);
								if (!isset($rights[$group])) {
									$rights[$group] = array();
								}
								$rights[$group][$action] = $right->sstUserRightValue;
							}
						}
						$this->setState('rights', $rights);

						ldap_unbind($connection);
					}
				}
				else {
					// let's check if the user exists in the internal directory
					$usersearch = LdapNameless::model()->findByDn('ou=User Search,ou=internal searches,ou=configuration,ou=virtualization,ou=services');
					$userauth = LdapNameless::model()->findByDn('ou=User Authentication,ou=internal searches,ou=configuration,ou=virtualization,ou=services');
					$usergroupsearch = LdapNameless::model()->findByDn('ou=User Group Search,ou=internal searches,ou=configuration,ou=virtualization,ou=services');

					$this->authenticateIntern($realm, $usersearch, $userauth, $usergroupsearch);
					Yii::log("LdapUserIdentity::authenticate: " . var_export($_SESSION, true), 'profile', 'authentication');

				}
			}
		}
		else {
			$this->errorCode = self::ERROR_REALM_INVALID;
		}
		Yii::log("LdapUserIdentity::authenticate: errorCode " . var_export($this->errorCode, true), 'profile', 'authentication');
		return self::ERROR_NONE === $this->errorCode;
	}

	private function authenticateIntern($realm, $usersearch, $userauth, $usergroupsearch)
	{
		Yii::log("LdapUserIdentity::authenticateIntern", 'profile', 'authentication');

		$server = CLdapServer::getInstance();
		//$usersearch = $realm->usersearch;
		$criteria = array(
			'branchDn' => $usersearch->sstLDAPBaseDn,
			'filter' => sprintf($usersearch->sstLDAPFilter, $this->username)
		);
		$result = LdapNameless::model()->findByAttributes($criteria);
		if (!is_null($result)) {
			//echo '<pre>' . print_r($result->getAttributes(), true) . '</pre>';
			$staticAttr = $usersearch->sstLDAPForeignStaticAttribute;
			//echo $staticAttr . '; ';
			$staticAttr = $result->$staticAttr;
			//echo $staticAttr . '<br/>';
			//$userauth = $realm->userauth;
			$authOk = $server->authorizeUser($userauth, $staticAttr, $this->password);
			if (!$authOk) {
				$this->errorCode = self::ERROR_PASSWORD_INVALID;
			}
			else {
				$criteria = array(
					'branchDn' => $usergroupsearch->sstLDAPBaseDn,
					'filter' => sprintf($usergroupsearch->sstLDAPFilter, $staticAttr)
				);
				$results = LdapNameless::model()->findAll($criteria);
				$result = $results[0];
				$returnAttr = $usergroupsearch->sstLDAPReturnAttribute;
				$groups = array();
				if (isset($result->$returnAttr)) {
					$resultgroups = $result->$returnAttr;
					if (!is_array($resultgroups)) {
						$groups = array($resultgroups);
					}
					else {
						$groups = $resultgroups;
					}
				}
				$model = LdapUser::model()->findByDn(sprintf($userauth->sstLDAPAuthUserBindDn, $staticAttr));
				if (is_null($model)) {
					$this->errorCode = self::ERROR_USERNAME_INVALID;
				}
				else {
					// User is OK; Let's check if the user is assigned to the realm
					if ($model->sstBelongsToCustomerUID == $realm->sstBelongsToCustomerUID) {
						$this->errorCode = self::ERROR_NONE;

// 						Yii::app()->user->setState('__uid', $model->uid);
// 						Yii::app()->user->setState('__groupuids', $groups);
// 						Yii::app()->user->setState('__realm', $this->realm);
// 						Yii::app()->user->setState('__admin', $model->isAdmin());
// 						Yii::app()->user->setState('__foreign', $model->isForeign());
// 						Yii::app()->user->setState('__customeruid', $model->sstBelongsToCustomerUID);
// 						Yii::app()->user->setState('__reselleruid', $model->sstBelongsToResellerUID);
						//Yii::app()->user->setState('__states', array('lang' => $model->preferredLanguage));

						$this->setState('uid', $model->uid);
						$this->setState('groupuids', $groups);
						$this->setState('roleuid', $model->sstRoleUID);
						$this->setState('realm', $this->realm);
						$this->setState('externalLDAP', isset($realm->sstLDAPExternalDirectory) &&  'TRUE' === $realm->sstLDAPExternalDirectory);
						$this->setState('admin', $model->isAdmin());
						$this->setState('foreign', $model->isForeign());
						$this->setState('customeruid', $model->sstBelongsToCustomerUID);
						$this->setState('reselleruid', $model->sstBelongsToResellerUID);
						$lang = $model->preferredLanguage;
						if (2 < strlen($lang)) {
							$lang = substr($lang, 0, 2);
						}
						$this->setState('lang', $lang);
						
						$rights = array();
						if (!is_null($model->role)) {
							foreach($model->role->rights as $right) {
								list($group, $action) = explode('.', $right->sstUserRightSectionAction);
								if (!isset($rights[$group])) {
									$rights[$group] = array();
								}
								$rights[$group][$action] = $right->sstUserRightValue;
							}
						}
						$this->setState('rights', $rights);
						
						Yii::log('LdapUserIdentity::authenticationIntern: ' . var_export($_SESSION, true), 'profile', 'authentication');
					}
					else {
						$this->errorCode = self::ERROR_REALM_INVALID;
					}
				}
			}
		}
		else {
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		}
	}

	/**
	 * Handle the group data coming from the "User Group Search".
	 * @return array uid's of internal groups matching sstGroupName == $returnAttr
	 */
	private function handleGroupData($entries, $returnAttr)
	{
		$groupuids = array();
//echo $returnAttr . '; ' . $entries['count'];
		for($i=0; $i<$entries['count']; $i++) {
			$attr = $entries[$i][strtolower($returnAttr)];
			if (is_array($attr)) {
				for($j=0; $j<$attr['count']; $j++) {
//echo $attr[$j];
					$group = LdapGroup::model()->findByAttributes(array('attr'=>array('sstGroupName'=>$attr[$j])));
					if (!is_null($group)) {
						$groupuids[] = $group->uid;
//echo ' ' . $group->uid;
					}
//echo '<br/>';
				}
			}
			else {
//echo $attr;
				$group = LdapGroup::model()->findByAttributes(array('attr'=>array('sstGroupName'=>$attr)));
				if (!is_null($group)) {
					$groupuids[] = $group->uid;
//echo ' ' . $group->uid;
				}
//echo '<br/>';
			}
		}
		return $groupuids;
	}


	public static function checkRealm($realm)
	{
		Yii::log("LdapUserIdentity::checkRealm: $realm", 'profile', 'authentication');

		$server = CLdapServer::getInstance();
		$realm = CLdapRecord::model('LdapRealm')->findByAttributes(array('attr'=>array('ou'=>$realm)));
		return !is_null($realm);
	}

	public static function checkUser($username, $realm)
	{
		Yii::log("LdapUserIdentity::checkUser: $username, $realm", 'profile', 'authentication');

		$checkIntern = false;
		$server = CLdapServer::getInstance();
		$realm = CLdapRecord::model('LdapRealm')->findByAttributes(array('attr'=>array('ou'=>$realm)));
		if (!is_null($realm)) {
			if ('TRUE' === $realm->sstLDAPExternalDirectory) {
				// We use an external directory
				$parts = explode(':', $realm->labeledURI);
				$hostname = $parts[0] . ':' . $parts[1];
				$port = $parts[2];
				$connection = @ldap_connect($hostname, $port);
				if ($connection === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}
				ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				$ldapbind = @ldap_bind($connection, $realm->sstLDAPBindDn, $realm->sstLDAPBindPassword);
				if ($ldapbind === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_bind to {server} failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}

				$usersearch = $realm->usersearch;
				$branchDn = $usersearch->sstLDAPBaseDn;
				$filter = sprintf($usersearch->sstLDAPFilter, $username);
//echo "branchDn: $branchDn; filter: $filter<br/>";
				Yii::log('LdapUserIdentity::checkUser: userSearch ' . $branchDn . ', ' . $filter, 'profile', 'authentication');
				$result = @ldap_search($connection, $branchDn, $filter);
				if ($result === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
				}
				$entries = @ldap_get_entries($connection, $result);
				if ($entries === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failt ({errno}): {message}',
						array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection))), ldap_errno($connection));
				}
//echo '<pre>' . print_r($entries, true) . '</pre>';
				ldap_unbind($connection);

				if (1 === $entries['count']) {
					return true;
				}
				else {
					$checkIntern = true;
				}
			}
			Yii::log("LdapUserIdentity::checkUser: intern? " . var_export('TRUE' !== $realm->sstLDAPExternalDirectory || $checkIntern, true), 'profile', 'authentication');
			if ('TRUE' !== $realm->sstLDAPExternalDirectory || $checkIntern) {
				$usersearch = $realm->usersearch;
				$usersearch = LdapNameless::model()->findByDn('ou=User Search,ou=internal searches,ou=configuration,ou=virtualization,ou=services');
				$criteria = array(
						'branchDn' => $usersearch->sstLDAPBaseDn,
						'filter' => sprintf($usersearch->sstLDAPFilter, $username)
				);
				$result = LdapNameless::model()->findByAttributes($criteria);
				return !is_null($result);
			}
			return false;	
		}
	}
}