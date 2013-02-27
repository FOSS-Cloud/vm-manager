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
 * LdapVmSingleBackup class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 1.1
 */

class LdapVmSingleBackup extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'ou=*');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstProvisioning', 'organizationalUnit', 'top');

	public function relations()
	{
		return array(
			// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'vm' => array(self::BELONGS_TO_DN, '~2', 'LdapVm', 'dn'),
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

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array();
	}

}