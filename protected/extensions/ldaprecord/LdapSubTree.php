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
 * LdapSubTree class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

/**
 * LdapSubTree
 *
 * LdapSubTree allows reading of a LDAP subtree.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class LdapSubTree extends CLdapRecord {
	protected $_filter = array('all' => 'objectClass=*');
	protected $_objectClasses = '*';						// allow all object classes
	protected $__children = null;							// children of this node

	/**
	 * Returns the children of this node.
	 * @return Array list of children
	 */
	public function getChildren() {
		return $this->__children;
	}

	public function setChildren($children) {
		$this->__children = $children;
	}

	public function addChild($child) {
		$this->__children[] = $child;
	}
}