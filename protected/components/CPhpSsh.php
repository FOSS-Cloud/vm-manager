<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *  Axel Westhagen <axel.westhagen@limbas.com>
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
 * CPhpSsh class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

/**
 * CPhpSsh
 *
 * CPhpSsh Interface to libvirt.
 *
 * The used design pattern is Singleton. To get the one and
 * only instance of this class call CPhpSsh::getInstance().
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @since 0.8
 */
class CPhpSsh {
	public static $SSH_UNKNOWN_HOST	= 	1;
	public static $SSH_WRONG_AUTH	= 	2;

	private static $_instance = null;

	private $connections = array();

	private function __construct() {
	}

	public function getConnection($host, $username, $password) {
		if (!isset($this->connections[$host])) {
			$this->connections[$host]['resource'] = @ssh2_connect($host);
			if (false !== $this->connections[$host]['resource']) {
				if (@ssh2_auth_password ($this->connections[$host]['resource'], $username, $password)) {
					$this->connections[$host]['auth'] = true;
				}
				else {
					throw new CPhpSshException('wrong user or password', self::$SSH_WRONG_AUTH);
				}
			}
			else {
				throw new CPhpSshException('unknown host', self::$SSH_UNKNOWN_HOST);
			}
		}
		return $this->connections[$host];
	}

	/*
	 * get singleton instance of CPhpSsh
	 */
	public static function getInstance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new CPhpSsh();
		}
		return self::$_instance;
	}

	/*
	 * Don't allow clone from outside
	 */
	private function __clone() {}
}

class CPhpSshException extends CException {
}