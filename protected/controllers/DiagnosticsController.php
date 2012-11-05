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
 * DiagnosticsController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

class DiagnosticsController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'diag';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		$this->activesubmenu = 'diag';
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index', 'vminfos', 'vmtemplateinfos', 'ldapattrtypes', 'ldapobjclasses'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionVMInfos()
	{
		$vms = array();
		$result = CLdapRecord::model('LdapVm')->findAll(array('attr'=>array()));
		foreach($result as $vm) {
			$vms[$vm->dn] = array('name' => $vm->sstDisplayName, 'selected' => (isset($_GET['dn']) && $_GET['dn'] == $vm->dn ? true : false));
		}
		$startxml = null;
		$libvirturi = null;
		$spiceuri = null;
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				$startxml = $libvirt->getXML($vm->getStartParams());
				$libvirturi = $vm->node->getLibvirtUri();
				$spiceuri = $vm->getSpiceUri();
			}
		}
		$this->render('vminfos', array('vms' => $vms, 'startxml' => $startxml, 'libvirturi' => $libvirturi, 'spiceuri' => $spiceuri));
	}

	public function actionVMTemplateInfos()
	{
		$vms = array();
		$result = CLdapRecord::model('LdapVmFromTemplate')->findAll(array('attr'=>array()));
		foreach($result as $vm) {
			$vms[$vm->dn] = array('name' => $vm->sstDisplayName, 'selected' => (isset($_GET['dn']) && $_GET['dn'] == $vm->dn ? true : false));
		}
		$startxml = null;
		$libvirturi = null;
		$spiceuri = null;
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				$startxml = $libvirt->getXML($vm->getStartParams());
				$libvirturi = $vm->node->getLibvirtUri();
				$spiceuri = $vm->getSpiceUri();
			}
		}
		$this->render('vminfos', array('vms' => $vms, 'startxml' => $startxml, 'libvirturi' => $libvirturi, 'spiceuri' => $spiceuri));
	}

	public function actionLDAPObjClasses()
	{
		$server = CLdapServer::getInstance();
		$schema = $server->getSchema();
		$this->render('ldapobjclasses', array('objclasses' => $schema->getAllObjectClasses()));
	}
	public function actionLDAPAttrTypes()
	{
		$server = CLdapServer::getInstance();
		$schema = $server->getSchema();
		$this->render('ldapattrtypes', array('attrtypes' => $schema->getAllAttributeTypes()));
	}
}
