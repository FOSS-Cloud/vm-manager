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
 * LdapVmFromProfile class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVmFromProfile extends LdapVm {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'sstVirtualMachine=*');
	protected $_dnAttributes = array('sstVirtualMachine');
	protected $_objectClasses = array('sstVirtualizationVirtualMachine', 'labeledURIObject', 'top');

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
			// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'devices' => array(self::HAS_ONE, 'dn', 'LdapVmDevice', '$model->getDn()', array('ou' => 'devices')),
			'defaults' => array(self::HAS_ONE_DN, 'dn', 'LdapVmDefaults', '$model->labeledURI', array(),
		));
	}

}