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
 * Wizard configuration file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

return array(
	'node' => array(
		'test' => array(
			'actions' => array(
				array('name' => 'test1', 'title' => Yii::t('node', 'wizard.test1.title'),
					'ssh' => array('host' => '$wizardNode.nodeip', 'username' => '$wizardNode.nodeuser', 'password' => '$wizardNode.nodepassword'),
					'call' => '/usr/libexec/foss-cloud/node-integration-check', 'params' => array('--module', 'network'),
					'ok' => 0, 'return' => array(
						0 => Yii::t('node', 'wizard.test1.0'),
						1 => Yii::t('node', 'wizard.test1.1'),
						2 => Yii::t('node', 'wizard.test1.2'),
					),
					'outputtype' => 'JSON',
					'outputvars' => array('adminip' => 'admin[ip]', 'dataip' => 'data[ip]', 'pubip' => 'pub[ip]', 'intip' => 'int[ip]', 'nodename' => 'Node Name', 'domain' => 'pub[domain]'),
				),
			)
		),
		'provision' => array(
			'actions' => array(
				array('name' => 'prov1', 'title' => Yii::t('node', 'wizard.prov1.title'),
					'ssh' => array('host' => '$wizardNode.nodeip', 'username' => '$wizardNode.nodeuser', 'password' => '$wizardNode.nodepassword'),
					'call' => '/usr/libexec/foss-cloud/node-integration-provisioning', 'params' => array('--module', 'filesystem', '--fstype', 'glusterfs', '--ip1', '127.0.0.1', '--ip2', '127.0.0.1'),
					'ok' => 0, 'return' => array(
					0 => Yii::t('node', 'wizard.prov1.0'),
					1 => Yii::t('node', 'wizard.prov1.1'),
				)),
			)
		),
	)
);
