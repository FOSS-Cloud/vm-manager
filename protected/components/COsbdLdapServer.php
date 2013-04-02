<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Axel Westhagen <axel.westhagen@limbas.com>
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
 * COsbdLdapServer class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6.1
 */

/**
 * COsbdLdapServer
 *
 * COsbdLdapServer holds all extensions of class CLdapServer.
 *
 * The used design pattern is Singleton. To get the one and
 * only instance of this class call CLdapServer::getInstance().
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @since 0.6.1
 */
final class COsbdLdapServer extends CLdapServer {
	/**
	 * Constructor protected
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Authorize a User with LDAPs bind functionality
	 *
	 * @param string $uid UID of this user (LDAP: uid=<uid>,ou=people)
	 * @param string $passwd
	 *
	 * @return boolean success
	 * @since 0.6.1
	 */
	public function authorizeUser($userauth, $uid, $passwd) {
		$connection = @ldap_connect($this->_config['server'], $this->_config['port']);
		if ($connection === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failt', array('{server}'=>$this->_config['server'])));
		}
		ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		$ldapbind = @ldap_bind($connection, sprintf($userauth->sstLDAPAuthUserBindDn, $uid), $passwd);
		if ($ldapbind === false) {
			return false;
		}
		ldap_unbind($connection);
		return true;
	}

	/**
	 * Authorize a User with LDAPs bind functionality
	 *
	 * @param string $uri URI of LDAP Server
	 * @param string $userauth contains the BindDn
	 * @param string $uid UID of this user (LDAP: uid=<uid>,ou=people)
	 * @param string $passwd
	 *
	 * @return boolean success
	 * @since 1.0.1
	 */
	public function authorizeUserExtern($uri, $userauth, $uid, $passwd) {
		$parts = explode(':', $uri);
		$server = $parts[0] . ':' . $parts[1];
		$port = $parts[2];
		Yii::log('COsbdLdapServer::authorizeUserExtern: connect ' . $uri, 'profile', 'authentication');
		$connection = @ldap_connect($server, $port) or die('LDAP connect failed!');
		if ($connection === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failt', array('{server}'=>$uri)));
		}
		ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		Yii::log('COsbdLdapServer::authorizeUserExtern: bind ' . sprintf($userauth->sstLDAPAuthUserBindDn, $uid), 'profile', 'authentication');
		$ldapbind = @ldap_bind($connection, sprintf($userauth->sstLDAPAuthUserBindDn, $uid), $passwd);
		if ($ldapbind === false) {
			return false;
		}
		ldap_unbind($connection);
		return true;
	}

	/**
	 * Don't allow cloning of this class from outside
	 */
	private function __clone() {}
}
