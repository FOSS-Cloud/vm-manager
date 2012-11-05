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

class LdapGroup extends CLdapRecord {
	protected $_branchDn = 'ou=groups';
	protected $_filter = array('all' => 'uid=*');
	protected $_dnAttributes = array('uid');
	protected $_objectClasses = array('sstGroupObjectClass', 'sstRelationship', 'labeledURIObject', 'top');

	public function relations()
	{
		return array(
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

	public function isAssignedToVm($dn) {
		$retval = false;
		$parts = explode(',', $dn);
		$server = CLdapServer::getInstance();
		$result = $server->search('ou=groups,' . $dn, '(&(ou=' . $this->uid  . '))', array('dn'));
		//echo '<pre>' . print_r($result, true) . '</pre>';
		$retval = 0 != $result['count'];
		return $retval;
	}

	public function isAssignedToVmPool($dn) {
		$retval = false;
		$parts = explode(',', $dn);
		$server = CLdapServer::getInstance();
		$result = $server->search('ou=groups,' . $dn, '(&(ou=' . $this->uid  . '))', array('dn'));
		//echo '<pre>' . print_r($result, true) . '</pre>';
		$retval = 0 != $result['count'];
		return $retval;
	}
}