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

return array(
	// application components
	'components'=>array(
		'log'=>array(
			'routes'=>array(
				// uncomment the following to log messages for ldap actions
				array(
					'class' => 'CFileLogRoute',
					'levels' => 'profile',
					'categories' => 'ext.ldaprecord.*',
					'logFile' => 'ldaprecord.log'
				),
				// uncomment the following to log messages for libvirt actions
				array(
					'class' => 'CFileLogRoute',
					'levels' => 'profile',
					'categories' => 'phplibvirt',
					'logFile' => 'phplibvirt.log'
				),
				array(
					'class'=>'ext.ESysLogRoute',
					'logName'=>'vm-manager',
					'logFacility'=>LOG_LOCAL0,
					'levels'=>'profile',
					'categories' => 'ext.ldaprecord.*',
				),
			),
		),
		'ldap'=>array(
			'class' => 'ext.ldaprecord.LdapComponent',
			'serverclass' => 'COsbdLdapServer',
			'server' => 'ldap://127.0.0.1/',
			'port' => 389,
			'bind_rdn' => 'cn=admin,dc=devroom,dc=de',
			'bind_pwd' => 'flinx',
			'base_dn' => 'dc=devroom,dc=de',
			'passwordtype' => 'SHA',
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		'virtualization' => array(
			'version' => '1.2.2',
			// Don't change the following params if you don't know what you are doing
			'spiceByName' => false,
			'disableSpiceAcceleration' => false,
		),
	),
);
