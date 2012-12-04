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
}