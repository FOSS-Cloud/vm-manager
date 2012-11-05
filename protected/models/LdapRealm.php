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

class LdapRealm extends CLdapRecord {
	protected $_branchDn = 'ou=authentication,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'ou=*');
	protected $_objectClasses = array('sstLDAPAuthenticationProvider', 'sstRelationship', 'labeledURIObject', 'organizationalUnit', 'top');

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'assignedUsers' => array(self::HAS_MANY, 'dn', 'LdapUserAssign', '\'ou=people,\' . $model->getDn()'),
			'usersearch' => array(self::HAS_ONE_DN, 'dn', 'LdapNameless',  '\'ou=User Search,\' . $model->getDn()'),
			'userauth' => array(self::HAS_ONE_DN, 'dn', 'LdapNameless',  '\'ou=User Authentication,\' . $model->getDn()'),
			'usergroupsearch' => array(self::HAS_ONE_DN, 'dn', 'LdapNameless',  '\'ou=User Group Search,\' . $model->getDn()'),
			'groupsearch' => array(self::HAS_ONE_DN, 'dn', 'LdapNameless',  '\'ou=Group Search,\' . $model->getDn()'),
		);
	}
}