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


class UserController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'user';
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
			$this->submenu['user']['items']['user']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'User Update Tooltip')),
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
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
		        	'actions'=>array('index', 'create', 'update', 'delete',
					'updatePopup',
					'getUser', 'getVMsGui', 'getRoles', 'saveVMsAssign'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
	    	    		'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index');
	}

	public function actionView() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapVmProfile')->findbyDn($_GET['dn']);
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');

		$criteria = array('attr'=>array());

		$this->render('view',array(
			'model' => $model,
		));
	}

	public function actionCreate() {
		$model = new UserForm('create');

		$this->performAjaxValidation($model);

		if(isset($_POST['UserForm']))
		{
			$model->attributes = $_POST['UserForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';

			$server = CLdapServer::getInstance();
			$enctype = strtolower($server->getEncryptionType());

			$user = new LdapUser();
			$user->setBranchDn('ou=people');
			$uid = $this->getNextUid();
			while (is_null($uid)) {
				sleep(2);
				$uid = $this->getNextUid();
			}
			$user->uid = $uid;
			$user->givenName = $model->givenname;
			$user->surName = $model->surname;
			$user->mail = $model->mail;
			$user->sstGender = $model->gender;
			$user->cn = $model->username;
			$user->userPassword = LdapUser::encodePassword($model->password, $enctype);
			$user->telephoneNumber = $model->telephone;
			$user->mobile = $model->mobile;
			$user->preferredLanguage = $model->language;
			$user->sstTimeZoneOffset = 'UTC+01';
			$user->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$user->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$user->sstGroupUID = $model->usergroups;
			$user->save();

			$userrole = new LdapUserRole();
			$userrole->setBranchDn($user->dn);
			$userrole->sstProduct = '0';
			$userrole->sstRole = 'User';
			$userrole->save();
			if ('admin' == $model->userrole) {
				$userrole = new LdapUserRole();
				$userrole->setBranchDn($user->dn);
				$userrole->sstProduct = '0';
				$userrole->sstRole = 'Admin Virtualization';
				$userrole->save();
			}

			$this->redirect(array('index'));
		}
		else {
			$usergroups = LdapGroup::model()->findAll(array('attr'=>array()));

			$this->render('create', array(
				'model' => $model,
				'usergroups' => $this->createDropdownFromLdapRecords($usergroups, 'uid', 'sstGroupName'),
			));
		}
	}

	public function actionUpdate() {
		$model = new UserForm('update');

		$this->performAjaxValidation($model);

		if(isset($_POST['UserForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['UserForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';

			$server = CLdapServer::getInstance();
			$enctype = strtolower($server->getEncryptionType());

			$user = CLdapRecord::model('LdapUser')->findByDn($model->dn);
			$user->setOverwrite(true);

			$user->givenName = $model->givenname;
			$user->surName = $model->surname;
			$user->mail = $model->mail;
			$user->sstGender = $model->gender;
			$user->cn = $model->username;
			if (isset($model->password) && '' != $model->password) {
				$user->userPassword = LdapUser::encodePassword($model->password, $enctype);
			}
			$user->telephoneNumber = $model->telephone;
			$user->mobile = $model->mobile;
			$user->preferredLanguage = $model->language;
			$user->sstTimeZoneOffset = 'UTC+01';
			if (is_array($model->usergroups)) {
				$user->sstGroupUID = $model->usergroups;
			}
			else if (!is_null($user->sstGroupUID)) {
				$user->removeAttribute('sstGroupUID');
				$data = array('sstGroupUID' => array());
				$server->modify_del($user->dn, $data);
			}
			$user->save();

			if ('admin' == $model->userrole && !$user->isAdmin()) {
				$userrole = new LdapUserRole();
				$userrole->setBranchDn($user->dn);
				$userrole->sstProduct = '0';
				$userrole->sstRole = 'Admin Virtualization';
				$userrole->save();
			}
			else if ('user' == $model->userrole && $user->isAdmin()) {
				$userroles = $user->roles;
				foreach($userroles as $role) {
					if ('User' != $role->sstRole) {
						$role->delete();
					}
				}
			}

			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$user = CLdapRecord::model('LdapUser')->findbyDn($_GET['dn']);
			}
			if($user === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$model->dn = $user->dn;
			$model->givenname = $user->givenName;
			$model->surname = $user->surName;
			$model->mail = $user->mail;
			$model->gender = $user->sstGender;
			$model->username = $user->cn;
			$model->telephone = $user->telephoneNumber;
			$model->mobile = $user->mobile;
			$model->userrole = ($user->isAdmin() ? 'admin' : 'user');
			$model->language = $user->preferredLanguage;
			$groups = $user->sstGroupUID;
			if (!is_array($groups)) {
				$groups = array($groups);
			}
			$model->usergroups = $groups;

			$usergroups = LdapGroup::model()->findAll(array('attr'=>array()));

			$this->render('update', array(
				'model' => $model,
				'usergroups' => $this->createDropdownFromLdapRecords($usergroups, 'uid', 'sstGroupName'),
			));
		}
	}

	public function actionUpdatePopup() {
		$this->disableWebLogRoutes();
		$this->layout = 'application.views.layouts.fancypopup';
		$model = new UserForm();

		$this->performAjaxValidation($model);

		if(isset($_POST['UserForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['UserForm'];

			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$user = CLdapRecord::model('LdapUser')->findbyDn($_GET['dn']);
			}
			if($user === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$model->givenname = $user->givenName;
			$model->surname = $user->surName;
			$model->mail = $user->mail;
			$model->gender = $user->sstGender;
			$model->password = $user->userPassword;
			$model->username = $user->cn;
			$model->telephone = $user->telephoneNumber;
			$model->mobile = $user->mobile;

			$this->render('update',array(
				'model' => $model,
			));
		}
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='user-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetUser() {
		Yii::log('getUser', 'profile');
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
		if (isset($_GET['name']) && '' != $_GET['name']) {
			$attr['surname'] = '*' . $_GET['name'] . '*';
		}
		if (isset($_GET['email']) && '' != $_GET['email']) {
			$attr['mail'] = '*' . $_GET['email'] . '*';
		}
		$users = CLdapRecord::model('LdapUser')->findAll(array('attr' => $attr));
		$count = count($users);
		if (isset($_GET['role'])) {
			for ($i=0; $i<$count; $i++) {
				$user = $users[$i];
				if ('admin' == $_GET['role'] && !$user->isAdmin()) {
					unset($users[$i]);
				}
				else if ('user' == $_GET['role'] && $user->isAdmin()) {
					unset($users[$i]);
				}
			}
			$users = array_values($users);
			$count = count($users);
		}
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
			$user = $users[$i];

		//foreach ($users as $user) {
			//	'colNames'=>array('No.', 'DN', 'Name', 'eMail', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. ($i+1) ."</cell>\n";
			$s .= '<cell>'. rawurlencode($user->dn) ."</cell>\n";
			$s .= '<cell>'. $user->getName() ."</cell>\n";
			$s .= '<cell>'. $user->mail ."</cell>\n";
			$s .= '<cell>'. ($user->isAdmin() ? Yii::t('user', 'Admin') : Yii::t('user', 'VM User')) ."</cell>\n";
			$s .= '<cell>'. ($user->isActiveUser() ? 'true' : 'false') ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVMsGui() {
		$this->disableWebLogRoutes();
		$varray = array();
		$user = CLdapRecord::model('LdapUser')->findByDn($_GET['dn']);
		$vms = CLdapRecord::model('LdapVm')->findAll(array('attr'=>array()));
		foreach ($vms as $vm) {
			if ('persistent' != $vm->sstVirtualMachineType) {
				continue;
			}
			$varray[$vm->sstVirtualMachine] = array('name' => $vm->sstDisplayName);
			if ($user->isAssignedToVm($vm->dn)) {
				$varray[$vm->sstVirtualMachine]['selected'] = true;
			}
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('user', 'Assign VMs to user') . ' \'' . $user->getName() . '\'</span></div>';
		$dual = $this->createWidget('ext.zii.CJqDualselect', array(
			'id' => 'userAssignment',
			'values' => $varray,
			'size' => 6,
			'options' => array(
				'sorted' => true,
				'leftHeader' => Yii::t('user', 'VMs'),
				'rightHeader' => Yii::t('user', 'Assigned VMs'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'dualselect.css',
		));
		$dual->run();
?>
		<button id="saveAssignment" style="margin-top: 10px; float: left;"></button>
		<div id="errorAssignment" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorMsg"></span></p>
		</div>
		<div id="infoAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionGetRoles() {
		echo '<select>';
		echo '<option value=""></option>';
		foreach(LdapUser::getRoleNames() as $val => $name) {
			echo '<option value="' . $val . '">' . $name . '</option>';
		}
		echo '</select>';
	}

	public function actionSaveVMsAssign() {
		$this->disableWebLogRoutes();
		$user = CLdapRecord::model('LdapUser')->findByDn($_GET['dn']);
		$server = CLdapServer::getInstance();
		$getvms = explode(',', $_GET['vms']);
		$vmassigns = array();
		if (!is_null($user->assign)) {
			foreach($user->assign->pools as $pool) {
				$vmassigns = array_merge($vmassigns, $pool->sstVirtualMachine);
			}
		}
		foreach($vmassigns as $assign) {
			if (in_array($assign, $getvms)) {
//				$user->assign->addVmAssignment($assign);
				unset($getvms[array_search($assign, $getvms)]);
			}
			else {
				$user->assign->removeVmAssignment($assign);
			}
		}
		if (0 < count($getvms)) {
			$dn = 'ou=people,ou=' . Yii::app()->user->realm . ',ou=authentication,ou=virtualization,ou=services';
			$result = $server->search($dn, '(&(ou=' . $user->uid  . '))', array('dn'));
			if (0 == $result['count']) {
				// object not found!
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($user->uid);
				$data['description'] = array('This is the user ' . $user->givenName . ' ' . $user->surname . '.');
				$dn = 'ou=' . $user->uid . ',' . $dn;
				$server->add($dn, $data);
			}
			else {
				$dn = $result[0]['dn'];
			}
		}
		foreach($getvms as $uuid) {
			$vm = CLdapRecord::model('LdapVm')->findByDn('sstVirtualMachine=' . $uuid . ',ou=virtual machines,ou=virtualization,ou=services');
			if (!$user->isAssignedToVm($vm->dn)) {
				$result = $server->search($dn, '(&(sstVirtualMachinePool=' . $vm->sstVirtualMachinePool  . '))', array('dn', 'sstVirtualMachine'));
				$data = array();
				if (0 == $result['count']) {
					$data['objectClass'] = array('top', 'sstVirtualMachines');
					$data['sstVirtualMachinePool'] = array($vm->sstVirtualMachinePool);
					$data['sstVirtualMachine'] = array($vm->sstVirtualMachine);
					$server->add('sstVirtualMachinePool=' . $vm->sstVirtualMachinePool . ',' . $dn, $data);
				}
				else {
					for ($i=0; $i<$result[0]['sstvirtualmachine']['count']; $i++) {
						$data['sstVirtualMachine'][] = $result[0]['sstvirtualmachine'][$i];
					}
					$data['sstVirtualMachine'][] = $vm->sstVirtualMachine;
					$server->modify($result[0]['dn'], $data);
				}
			}
		}
		$json = array('err' => false, 'msg' => Yii::t('user', 'Assignment saved!'));
		$this->sendJsonAnswer($json);
	}

	public function actionDelete() {
		$this->disableWebLogRoutes();
		if ('del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			$user = CLdapRecord::model('LdapUser')->findByDn($dn);
			if (!is_null($user)) {
				if ($user->UID != Yii::app()->user->uid) {
					if (!is_null($user->assign)) {
						$user->assign->delete(true);
					}
					$user->delete(true);
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Unable to delete yourself!'));
				}
			}
		}
	}

	/* Private functions */
}