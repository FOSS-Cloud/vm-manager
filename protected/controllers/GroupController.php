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


class GroupController extends Controller
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
			$this->submenu['user']['items']['group']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Group Update Tooltip')),
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
				'actions'=>array('index', 'getGroups'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'group\', \'Access\', \'Enabled\')'
			),
			array('allow',
				'actions'=>array('create'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'group\', \'Create\', \'Enabled\')'
			),
			array('allow',
				'actions'=>array('update', 'import'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'group\', \'Edit\', \'Enabled\', \'None\')'
			),
			array('allow',
				'actions'=>array('delete'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'group\', \'Delete\', \'Enabled\', \'None\')'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index');
	}

	public function actionCreate() {
		$model = new GroupForm('create');

		$this->performAjaxValidation($model);

		if(isset($_POST['GroupForm']))
		{
			$model->attributes = $_POST['GroupForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';

			$group = new LdapGroup();
			$uid = $this->getNextUid();
			while (is_null($uid)) {
				sleep(2);
				$uid = $this->getNextUid();
			}
			$group->uid = $uid;
			$group->sstDisplayName = $model->sstDisplayName;
			$group->sstGroupName = $model->sstGroupName;
			$group->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$group->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$group->labeledURI = 'ldap:///ou=people,' . CLdapServer::getInstance()->getBaseDn() . '??one?(sstGroupUID=' . $uid . ')';
			$group->save();

			$this->redirect(array('index'));
		}
		else {
			$this->render('create', array(
				'model' => $model,
			));
		}
	}

	public function actionUpdate() {
		$model = new GroupForm('update');

		$this->performAjaxValidation($model);

		if(isset($_POST['GroupForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['GroupForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';

			$group = CLdapRecord::model('LdapGroup')->findByDn($model->dn);
			$group->setOverwrite(true);

			$group->sstDisplayName = $model->sstDisplayName;
			$group->sstGroupName = $model->sstGroupName;
			$group->save();


			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$group = CLdapRecord::model('LdapGroup')->findbyDn($_GET['dn']);
			}
			if($group === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$model->dn = $group->dn;
			$model->sstDisplayName = $group->sstDisplayName;
			$model->sstGroupName = $group->sstGroupName;

			$this->render('update', array(
				'model' => $model,
			));
		}
	}

	public function actionImport() {
		$items = array();
		if(isset($_POST['ForeignGroupForm']))
		{
			foreach($_POST['ForeignGroupForm']['items'] as $key => $value) {
				$item = new ForeignGroupForm();
				//echo '<pre>' . $key . ': ' . print_r($value, true) . '</pre>';
				$item->attributes = $value;
				$item->static = $key;
				$items[$key] = $item;
			}
			
			$errors = array();
			foreach($items as $key => $item) {
				//echo '<pre>' . print_r($item, true) . '</pre>';
				//echo $item->selected;
				if ('1' === $item->selected) {
					if ('' !== $item->local) {
						$groups = LdapGroup::model()->findAll(array('attr'=>array('sstLdapForeignStaticAttribute' => $item->static)));
						if (1 === count($groups)) {
							$group = $groups[0];
							$group->setOverwrite(true);
							$group->sstDisplayName = $item->local;
							$group->sstGroupName = $item->name;
							$group->save();
						}
						else if (0 === count($groups)) {
							$group = new LdapGroup();
							$uid = $this->getNextUid();
							while (is_null($uid)) {
								sleep(2);
								$uid = $this->getNextUid();
							}
							$group->uid = $uid;
							$group->sstDisplayName = $item->local;
							$group->sstGroupName = $item->name;
							$group->sstLdapForeignStaticAttribute = $item->static;
							$group->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
							$group->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
							$group->labeledURI = 'ldap:///ou=people,' . CLdapServer::getInstance()->getBaseDn() . '??one?(sstGroupUID=' . $uid . ')';
							//$group->save();
						}
					}
					else {
						$item->message = Yii::t('group', 'please define a display name');
					}	
				}
			}
		}
		{
			$realm = Yii::app()->user->getState('realm');
			$realm = CLdapRecord::model('LdapRealm')->findByAttributes(array('attr'=>array('ou'=>$realm)));
			if (!is_null($realm)) {
				// We use an external directory
				$parts = explode(':', $realm->labeledURI);
				$hostname = $parts[0] . ':' . $parts[1];
				$port = $parts[2];
				$connection = @ldap_connect($hostname, $port);
				if ($connection === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failt ({errno}): {message}',
							array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}
				ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				$ldapbind = @ldap_bind($connection, $realm->sstLDAPBindDn, $realm->sstLDAPBindPassword);
				if ($ldapbind === false) {
					throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_bind to {server} failt ({errno}): {message}',
							array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection),'{server}'=>$realm->labeledURI)), ldap_errno($connection));
				}
			
				$groupsearch = $realm->groupsearch;
				$branchDn = $groupsearch->sstLDAPBaseDn;
				$filter = $groupsearch->sstLDAPFilter;
				//echo "branchDn: $branchDn; filter: $filter<br/>";
				$result = @ldap_search($connection, $branchDn, $filter);
				if ($result === false) {
					$message = Yii::t('LdapComponent.server', 'ldap_search failt ({errno}): {message}',
							array('{errno}'=>ldap_errno($connection), '{message}'=>ldap_error($connection)));
					$message .= "\n" . 'Unable to search "' . $filter . '" at "' . $branchDn . '" on server ' .  $realm->labeledURI;
					throw new CLdapException($message, ldap_errno($connection));
				}
				$entries = @ldap_get_entries($connection, $result);
				$displayName = strtolower($groupsearch->sstLDAPForeignGroupDisplayName);
				$staticAttrName = strtolower($groupsearch->sstLDAPForeignStaticAttribute);
				for($i=0; $i<$entries['count']; $i++) {
					//echo $entries[$i][$staticAttrName][0] . ': ' . $entries[$i][$displayName][0] . '<br/>';
					if (!isset($items[$entries[$i][$staticAttrName][0]])) {
						$item = new ForeignGroupForm();
						$item->static = $entries[$i][$staticAttrName][0];
						$item->name = $entries[$i][$displayName][0];
						$item->local = $item->name;
						$items[$entries[$i][$staticAttrName][0]] = $item;
					}
				}
				//echo '<pre>' . print_r($items, true) . '</pre>';
				$groups = LdapGroup::model()->findAll(array('attr'=>array()));
				//echo '<pre>' . print_r($groups, true) . '</pre>';
				foreach($groups as $group) {
					//echo $group->sstLDAPForeignStaticAttribute . '<br/>';
					if (isset($items[$group->sstLDAPForeignStaticAttribute])) {
						$items[$group->sstLDAPForeignStaticAttribute]->found = true;
						if ('' === $items[$group->sstLDAPForeignStaticAttribute]->message) {
							$items[$group->sstLDAPForeignStaticAttribute]->message = Yii::t('group', 'group already imported');
						}
						$items[$group->sstLDAPForeignStaticAttribute]->local = $group->sstDisplayName;
						if ($items[$group->sstLDAPForeignStaticAttribute]->name != $group->sstGroupName) {
							$items[$group->sstLDAPForeignStaticAttribute]->savedName = $group->sstGroupName;
							$items[$group->sstLDAPForeignStaticAttribute]->diffName = true;
							if ('' === $items[$group->sstLDAPForeignStaticAttribute]->message) {
								$items[$group->sstLDAPForeignStaticAttribute]->message = Yii::t('group', 'group already imported; different groupnames found');
							}
						}
					}
				}
				$displayName = $groupsearch->sstLDAPForeignGroupDisplayName;
				$staticAttrName = $groupsearch->sstLDAPForeignStaticAttribute;
			}
		}

		$this->render('import',array(
			'items' => $items,
			'displayName' => $displayName,
			'staticAttrName' => $staticAttrName,
			'submittext'=>Yii::t('group','Import')
		));
	}
	
	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='group-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetGroups() {
		Yii::log('getGroups', 'profile');
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
			$attr['displayName'] = '*' . $_GET['name'] . '*';
		}
		if (isset($_GET['extname']) && '' != $_GET['extname']) {
			$attr['name'] = '*' . $_GET['extname'] . '*';
		}
		if(Yii::app()->user->hasRight('group', 'View', 'All')) {
			$groups = CLdapRecord::model('LdapGroup')->findAll(array('attr' => $attr));
		}
		else {
			$groups = array();
		}
		$count = count($groups);
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
			$group = $groups[$i];

		//foreach ($users as $user) {
			//	'colNames'=>array('No.', 'DN', 'Name', 'eMail', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. ($i+1) ."</cell>\n";
			$s .= '<cell>'. rawurlencode($group->dn) ."</cell>\n";
			$s .= '<cell>'. $group->sstDisplayName ."</cell>\n";
			$s .= '<cell>'. $group->sstGroupName ."</cell>\n";
			$s .= '<cell>'. (isset($group->member) && 0 < count($group->member) ? 'true' : 'false') ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionDelete() {
		$this->disableWebLogRoutes();
		if ('del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			$group = CLdapRecord::model('LdapGroup')->findByDn($dn);
			if (!is_null($group) && !isset($group->member)) {
				$group->delete(true);
				$this->sendAjaxAnswer(array('error' => 0));
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'There are users assigned to this group!'));
			}
		}
	}

	/* Private functions */
}