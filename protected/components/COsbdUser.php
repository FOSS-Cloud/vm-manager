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
 * COsbdUser class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

/**
 * COsbdUser represents the persistent state for a Web application user.
 *
 * COsbdUser is used as an application component whose ID is 'user'.
 * Therefore, at any place one can access the user state via
 * <code>Yii::app()->user</code>.
 *
 */
class COsbdUser extends CWebUser {
	
	/* Allowed actions of a right */
	public static $RIGHT_ACTION_ACCESS 	= 'access';
	public static $RIGHT_ACTION_VIEW	= 'view';
	public static $RIGHT_ACTION_CREATE	= 'create';
	public static $RIGHT_ACTION_EDIT	= 'edit';
	public static $RIGHT_ACTION_DELETE	= 'delete';
	public static $RIGHT_ACTION_USE		= 'use';
	public static $RIGHT_ACTION_MANAGE	= 'manage';
	
	/* Allowed values of an action */
	public static $RIGHT_VALUE_ALL		= 'all';
	public static $RIGHT_VALUE_OWNER	= 'owner';
	public static $RIGHT_VALUE_NONE		= 'none';

	private $rights = null;
	
	/**
	 * Returns the URL that the user should be redirected to after successful login.
	 * This property is usually used by the login action. If the login is successful and does not have role 'Admin',
	 * the action should read this property and use it to redirect the user browser.
	 * @return string the URL that the user should be redirected to after login.
	 */
	public function getVmListUrl()
	{
		return $this->getState('__vmListUrl',Yii::app()->getRequest()->getScriptUrl());
	}

	/**
	 * @param string $value the URL that the user should be redirected to after login.
	 */
	public function setVmListUrl($value)
	{
		$this->setState('__vmListUrl',$value);
	}

	/**
	 * @return boolean has this user the 'Admin' role
	 */
	public function getIsAdmin() {
		return $this->getState('admin', false);
	}
	/**
	 * @return boolean is this user from external LDAP
	 */
	public function getIsForeign() {
		return $this->getState('foreign', false);
	}
	/**
	 * @return string the uid
	 */
	public function getUID() {
		return $this->getState('uid');
	}
	/**
	 * @return string the uid of the realm the user belongs to
	 */
	public function getRealm() {
		return $this->getState('realm');
	}
	/**
	 * @return string the uid of the customer the user belongs to
	 */
	public function getCustomerUID() {
		return $this->getState('customeruid');
	}
	/**
	 * @return string the uid of the reseller the user belongs to
	 */
	public function getResellerUID() {
		return $this->getState('reselleruid');
	}
	
	public function getLdapUser() {
		return LdapUser::model()->findByDn('uid=' . $this->getUID() . ',ou=people');
	}
	
	public function hasRight($group, $action, $value) {
		return $value === $this->getRight($group, $action);
	}
	
	public function hasOtherRight($group, $action, $value) {
		$right = $this->getRight($group, $action);
		return !is_null($right) && $value !== $right;
	}
	
	public function getRight($group, $action) {
		if (is_null($this->rights)) {
			$this->rights = $this->getState('rights');
		}
		if (isset($this->rights[$group]) && isset($this->rights[$group][$action])) {
			return $this->rights[$group][$action];
		}
		return null;
	}
}