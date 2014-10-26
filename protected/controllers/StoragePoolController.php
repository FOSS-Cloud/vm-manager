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
 * StoragePoolController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 1.0
 */

class StoragePoolController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'storagepool';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('update' == $action) {
			$this->submenu['storagepool']['items']['storagepool']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Storage Pool Update Tooltip')),
				'active' => true,
			);
		}
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
				'actions'=>array('index', 'getStoragePools'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'storagePool\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('create'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'storagePool\', COsbdUser::$RIGHT_ACTION_CREATE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('update'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'storagePool\', COsbdUser::$RIGHT_ACTION_EDIT, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('delete'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'storagePool\', COsbdUser::$RIGHT_ACTION_DELETE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex() {
		$model=new LdapStoragePool('search');
		if(isset($_GET['LdapStoragePool'])) {
			$model->attributes = $_GET['LdapStoragePool'];
		}
		$this->render('index', array(
			'model' => $model,
		));
	}

	public function actionView() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapStoragePool')->findbyDn($_GET['dn']);
		else if (isset($_GET['node']))
			$model = CLdapRecord::model('LdapStoragePool')->findByAttributes(array('attr'=>array('sstNode' => $_GET['vmpool'])));
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');
		$this->render('view',array(
			'model' => $model,
		));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='storagepool-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Ajax functions for JqGrid
	 */
	public function actionGetStoragePools() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		// if we not pass at first time index use the first column for the index or what you want
		if(!$sidx) $sidx = 1;

		$attr = array();
		if (isset($_GET['sstDisplayName'])) {
			$attr['sstDisplayName'] = '*' . $_GET['sstDisplayName'] . '*';
		}
		if (Yii::app()->user->hasRight('storagePool', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL)) {
			$pools = LdapStoragePool::model()->findAll(array('attr' => $attr));
		}
		else {
			$pools = array();
		}
		$count = count($pools);
		$total_pages = ceil($count / $limit);

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  "<rows>";
		$s .= "<page>".$page."</page>";
		$s .= "<total>".$total_pages."</total>";
		$s .= "<records>".$count."</records>";

		$start = $limit * ($page - 1);
		$start = $start > $count ? 0 : $start;
		$end = $start + $limit;
		$end = $end > $count ? $count : $end;
		for ($i=$start; $i<$end; $i++) {
			$pool = $pools[$i];

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. ($i+1) ."</cell>\n";
			$vmpools = LdapNameless::model()->findAll(array('branchDn'=>'ou=virtual machine pools,ou=virtualization,ou=services', 'depth'=>true,'attr'=>array('ou'=>$pool->sstStoragePool, 'objectClass'=>'sstRelationship')));
			$hasVmPools = 0 < count($vmpools);
			$s .= '<cell>'. ($hasVmPools ? 'true' : 'false') ."</cell>\n";
			$s .= '<cell>'. $pool->dn ."</cell>\n";
			$s .= '<cell>'. $pool->sstDisplayName ."</cell>\n";
			$s .= '<cell>'. $pool->description ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionDelete() {
		if (isset($_POST['oper']) && 'del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			$pool = CLdapRecord::model('LdapStoragePool')->findByDn($dn);
			if (!is_null($pool)) {
				$ldapnodes = LdapNode::model()->findAll(array('attr'=>array()));
				foreach($ldapnodes as $node) {
					if ($node->isType('VM-Node')) {
						break;
					}
				}					
				if (!CPhpLibvirt::getInstance()->deleteStoragePool($node->getLibvirtUri(), $pool->sstStoragePool, substr($pool->sstStoragePoolURI, 7))) {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => CPhpLibvirt::getInstance()->getLastError()));
				}
				else {
					$pool->delete(true);
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'StoragePool \'' . $_POST['dn'] . '\' not found!'));
			}
		}
	}

	public function actionCreate() {
		$model = new StoragePoolForm('create');
		$hasError = false;

		$this->performAjaxValidation($model);

		if(isset($_POST['StoragePoolForm'])) {
			$model->attributes = $_POST['StoragePoolForm'];

			$pooldefinition = CLdapRecord::model('LdapStoragePoolDefinition')->findByAttributes(array('attr'=>array('ou'=>$model->sstStoragePoolType)));
			if($pooldefinition === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$baseURI = '';
			if ('' == $model->directory) {
				$baseURI = $pooldefinition->sstStoragePoolURI;
			}
			else {
				$basedefinition = CLdapRecord::model('LdapStoragePoolDefinition')->findByAttributes(array('attr'=>array('ou'=>'basedir')));
				if($basedefinition === null)
					throw new CHttpException(404,'The requested page does not exist.');
				
				$baseURI = $basedefinition->sstStoragePoolURI . $model->directory . '/';
			}

			$pool = new LdapStoragePool();
			$pool->sstStoragePool = CPhpLibvirt::getInstance()->generateUUID();
			$pool->sstStoragePoolURI = $baseURI . $pool->sstStoragePool;
			$pool->sstDisplayName = $model->sstDisplayName;
			$pool->description = $model->description;
			$pool->sstStoragePoolType = $pooldefinition->sstStoragePoolType;
			$pool->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$pool->sstBelongsToResellerUID = Yii::app()->user->resellerUID;

			$ldapnodes = LdapNode::model()->findAll(array('attr'=>array()));
			foreach($ldapnodes as $node) {
				if ($node->isType('VM-Node')) {
					break;
				}
			}					
			if (!CPhpLibvirt::getInstance()->createStoragePool($node->getLibvirtUri(), $pool->sstStoragePool, substr($pool->sstStoragePoolURI, 7))) {
				$hasError = true;
				$errorText = CPhpLibvirt::getInstance()->getLastError();
			}
			else {
				$pool->save();
				//echo '<pre>' . print_r($pool, true) . '</pre>';
				$this->redirect(array('index'));
			}
		}
		if (!isset($_POST['StoragePoolForm']) || $hasError) {
			$pools = CLdapRecord::model('LdapStoragePoolDefinition')->findAll(array('attr'=>array('sstSelfService'=>'TRUE')));
			$pooltypes = $this->createDropdownFromLdapRecords($pools, 'ou', 'ou');
			
			$pooldefinition = CLdapRecord::model('LdapStoragePoolDefinition')->findByAttributes(array('attr'=>array('ou'=>'basedir')));
			if($pooldefinition === null)
				throw new CHttpException(404,'The requested page does not exist.');
			
			$directories = array();
			$hiddendirs = array('vm-persistent', 'vm-dynamic', 'vm-templates', 'iso', 'iso-choosable', 'backup', 'tmp', 'retain');
			$dir = substr($pooldefinition->sstStoragePoolURI, 7);
			$dh = opendir($dir);
			if (false !== $dh) {
				while (false !== ($file = readdir($dh))) {
					if (is_dir($dir . $file) && false === strpos($file, '.') && !in_array(strtolower($file), $hiddendirs)) {
						$directories[$file] = $file;
					}
				}
				closedir($dh);
			}

			$this->render('create',array(
				'model' => $model,
				'pooltypes' => array_merge(array('' => ''), $pooltypes),
				'directories' => array_merge(array('' => ''), $directories),
				'error' => $hasError ? $errorText : false,
			));

		}
	}

	public function actionUpdate() {
		$model = new StoragePoolForm('update');
		$hasError = false;
		$errorText = '';

		$this->performAjaxValidation($model);

		if(isset($_POST['StoragePoolForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['StoragePoolForm'];

			$result = CLdapRecord::model('LdapStoragePool')->findByDn($_POST['StoragePoolForm']['dn']);
			$result->setOverwrite(true);
			$result->sstDisplayName = $model->sstDisplayName;
			$result->description = $model->description;
			$result->save();

			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$pool = CLdapRecord::model('LdapStoragePool')->findbyDn($_GET['dn']);
			}
			if($pool === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$model->dn = $pool->dn;
			$model->sstStoragePoolURI = $pool->sstStoragePoolURI;
			$model->sstStoragePoolType = $pool->sstStoragePoolType;
			$model->sstDisplayName = $pool->sstDisplayName;
			$model->description = $pool->description;

			$pools = CLdapRecord::model('LdapStoragePoolDefinition')->findAll(array('attr'=>array('sstSelfService'=>'TRUE')));
			$pooltypes = $this->createDropdownFromLdapRecords($pools, 'ou', 'ou');

			$this->render('update',array(
				'model' => $model,
				'pooltypes' => null,
				'directories' => null,
				'error' => $hasError ? $errorText : false,
			));
		}
	}

}