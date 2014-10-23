<?php
/*
 * Copyright (C) 2006 - 2014 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or higher - as soon they
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
			array('allow',
				'actions'=>array('index', 'ldapattrtypes', 'ldapobjclasses', 'vmcounter'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'diagnostic\', \'Access\', \'Enabled\')'
			),
			array('allow',
				'actions'=>array('persistentvminfos'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'persistentVM\', \'View\', \'None\')'
			),
			array('allow',
				'actions'=>array('dynamicvminfos'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'dynamicVM\', \'View\', \'None\')'
			),
			array('allow',
				'actions'=>array('vmtemplateinfos'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'templateVM\', \'View\', \'None\')'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionPersistentVMInfos()
	{
		$vms = array();
		$criteria = array('filter'=>'(&(sstVirtualMachineType=persistent))');
		if (Yii::app()->user->hasRight('persistentVM', 'View', 'All')) {
			$result = LdapVm::model()->findAll($criteria);
		}
		else if (Yii::app()->user->hasRight('persistentVM', 'View', 'Owner')) {
			$result = LdapVm::getAssignedVms('persistent', $criteria);
			$result = array_values($result);
		}
		else {
			$result = array();
		}
		$startxml = null;
		$libvirturi = null;
		$spiceuri = null;
		foreach($result as $vm) {
			$vms[$vm->dn] = array('name' => $vm->sstDisplayName, 'selected' => (isset($_GET['dn']) && $_GET['dn'] == $vm->dn ? true : false));
			if (isset($_GET['dn']) && $_GET['dn'] == $vm->dn) {
				$libvirt = CPhpLibvirt::getInstance();
				$startxml = $libvirt->getXML($vm->getStartParams());
				$libvirturi = $vm->node->getLibvirtUri();
				$spiceuri = $vm->getSpiceUri();
			}
		}
		$this->render('persistentvminfos', array('vms' => $vms, 'startxml' => $startxml, 'libvirturi' => $libvirturi, 'spiceuri' => $spiceuri));
	}

	public function actionDynamicVMInfos()
	{
		$vms = array();
		$criteria = array('filter'=>'(&(sstVirtualMachineType=dynamic))');
		if (Yii::app()->user->hasRight('dynamicVM', 'View', 'All')) {
			$result = LdapVm::model()->findAll($criteria);
		}
		else if (Yii::app()->user->hasRight('dynamicVM', 'View', 'Owner')) {
			$result = LdapVm::getAssignedVms('dynamic', $criteria);
			$result = array_values($result);
		}
		else {
			$result = array();
		}
		
		//$result = CLdapRecord::model('LdapVm')->findAll(array('filter'=>'(&(sstVirtualMachineType=dynamic))'));
		$startxml = null;
		$libvirturi = null;
		$spiceuri = null;
		foreach($result as $vm) {
			$vms[$vm->dn] = array('name' => $vm->sstDisplayName, 'selected' => (isset($_GET['dn']) && $_GET['dn'] == $vm->dn ? true : false));
			if (isset($_GET['dn']) && $_GET['dn'] == $vm->dn) {
				$libvirt = CPhpLibvirt::getInstance();
				$startxml = $libvirt->getXML($vm->getStartParams());
				$libvirturi = $vm->node->getLibvirtUri();
				$spiceuri = $vm->getSpiceUri();
			}
		}
		$this->render('dynamicvminfos', array('vms' => $vms, 'startxml' => $startxml, 'libvirturi' => $libvirturi, 'spiceuri' => $spiceuri));
	}

	public function actionVMTemplateInfos()
	{
		$vms = array();
		$criteria = array('filter'=>'(&(sstVirtualMachineType=template))');
		if (Yii::app()->user->hasRight('templateVM', 'View', 'All')) {
			$result = LdapVmFromTemplate::model()->findAll($criteria);
		}
		else {
			$result = array();
		}
		$startxml = null;
		$libvirturi = null;
		$spiceuri = null;
		foreach($result as $vm) {
			$vms[$vm->dn] = array('name' => $vm->sstDisplayName, 'selected' => (isset($_GET['dn']) && $_GET['dn'] == $vm->dn ? true : false));
			if (isset($_GET['dn']) && $_GET['dn'] == $vm->dn) {
				$libvirt = CPhpLibvirt::getInstance();
				$startxml = $libvirt->getXML($vm->getStartParams());
				$libvirturi = $vm->node->getLibvirtUri();
				$spiceuri = $vm->getSpiceUri();
			}
		}
		$this->render('vmtemplateinfos', array('vms' => $vms, 'startxml' => $startxml, 'libvirturi' => $libvirturi, 'spiceuri' => $spiceuri));
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
	
	public function actionVmCounter() 
	{
		if (isset($_GET['print'])) {
			$this->layout = 'application.views.layouts.osbdPrint';			
		}
		$criteria = array('attr'=>array('sstVirtualmachinePoolType' => 'template'));
		$tpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$criteria = array('attr'=>array('sstVirtualmachinePoolType' => 'persistent'));
		$ppools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$criteria = array('attr'=>array('sstVirtualmachinePoolType' => 'dynamic'));
		$dpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
		$this->render('vmcounter', array('tpools' => $tpools, 'ppools' => $ppools, 'dpools' => $dpools));
	}
}
