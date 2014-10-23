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
 * NodeController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class NodeController extends WizardController
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'node';
		}

		$config = array();
		switch ($action->id) {
			case 'wizard':
				$config = array(
					'steps'=>array('Node' => 'wizardNode', 'Testing' => 'wizardNodeTest', 'Provisioning' => 'wizardNodeProvision'),
					'events'=>array(
						'onStart'=>'wizardStart',
						'onProcessStep'=>'wizardProcessStep',
						'onFinished'=>'wizardFinished',
						'onInvalidStep'=>'wizardInvalidStep',
						'onCancelled'=>'wizardCancelled',
					),
					'menuLastItem'=>'Finish',
					'cancelledUrl'=>$this->createUrl('/node/index'),
				);
				break;
			default:
				break;
		}
		if (!empty($config)) {
			$config['class']='application.components.WizardBehavior';
			$this->attachBehavior('wizard', $config);
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('view' == $action) {
			$this->submenu['node']['items']['node']['items'][] = array(
				'label' => Yii::t('menu', 'View'),
				'itemOptions' => array('title' => Yii::t('menu', 'Node View Tooltip')),
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
				'actions'=>array('index', 'getNodes'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'node\', \'Access\', \'Enabled\')'
			),
			array('allow',
				'actions'=>array('view'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'node\', \'View\', \'Enabled\', \'None\')'
			),
				array('allow',
				'actions'=>array('wizard', 'handleWizardAction'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'node\', \'Create\', \'Enabled\')'
			),
			array('allow',
				'actions'=>array('maintainVmNode'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'node\', \'Edit\', \'Enabled\', \'None\')'
			),
			array('allow',
				'actions'=>array('delete'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasOtherRight(\'node\', \'Delete\', \'Enabled\', \'None\')'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex() {
		$model=new LdapNode('search');
		if(isset($_GET['LdapNode'])) {
			$model->attributes = $_GET['LdapNode'];
		}
		$this->render('index', array(
			'model' => $model,
		));
	}

	public function actionView() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapNode')->findbyDn($_GET['dn']);
		else if (isset($_GET['node']))
			$model = CLdapRecord::model('LdapNode')->findByAttributes(array('attr'=>array('sstNode' => $_GET['node'])));
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');
		$this->render('view',array(
			'model' => $model,
		));
	}

	public function actionViewVms() {
		$this->renderPartial('viewVms', array('sstNode' => $_GET['node']));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='menue-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Ajax functions for JqGrid
	 */
	public function actionGetNodes() {
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

		$criteria = array('attr'=>array());
		if (isset($_GET['sstNode'])) {
			$criteria['attr']['sstNode'] = '*' . $_GET['sstNode'] . '*';
		}
		
		if(Yii::app()->user->hasRight('node', 'View', 'All')) {
			$nodes = LdapNode::model()->findAll($criteria);
		}
		else {
			$nodes = array();
		}
		$count = count($nodes);

		// calculate the total pages for the query
		if( $count > 0 && $limit > 0)
		{
			$total_pages = ceil($count/$limit);
		}
		else
		{
			$total_pages = 0;
		}

		// if for some reasons the requested page is greater than the total
		// set the requested page to total page
		if ($page > $total_pages)
		{
			$page = $total_pages;
		}

		// calculate the starting position of the rows
		$start = $limit*$page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;
		if (1 != $sidx) {
			$criteria['sort'] = $sidx . '.' . $sord;
		}

		if (Yii::app()->user->hasRight('node', 'View', 'All')) {
			$nodes = CLdapRecord::model('LdapNode')->findAll($criteria);
		}
		else {
			$nodes = array();
		}

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  "<rows>";
		$s .= "<page>".$page."</page>";
		$s .= "<total>".$total_pages."</total>";
		$s .= "<records>".$count."</records>";

		$i = 1;
		// be sure to put text data in CDATA
		foreach ($nodes as $node) {
			//$s .= '<row id="' . $node->dn . '">';
			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i++ ."</cell>\n";
			$s .= '<cell>'. $node->dn ."</cell>\n";
			$s .= '<cell>'. $node->sstNode ."</cell>\n";
			$vmnodemaintain = null;
			$running = CPhpLibvirt::getInstance()->checkNode($node->getLibvirtUri());
			$types = false === $running ? 'stopped' : 'running';
			if ($node->types) {
				foreach($node->types as $type) {
					if ('VM-Node' === $type->sstNodeType) {
						$vmnodemaintain = 'maintenance' === $type->sstNodeState;
					}
					if ('' != $types) {
						$types .= ';<br/>';
					}
					$types .= $type->sstNodeType . ': ';
					switch($type->sstNodeState) {
						case 'active': $types .= '<span style="color: green;">active</span>'; break;
						case 'maintenance': $types .= '<span style="color: orange;">maintenance</span>'; break;
						default: $types .= '<span style="color: red;">' . $type->sstNodeState . '</span>'; break;
					}
				}
			}
			$vmpools = array();
			$nodepools = $node->vmpools;
			foreach($nodepools as $nodepool) {
				$pooldn = CLdapRecord::getParentDn(CLdapRecord::getParentDn($nodepool->getDn()));
				$vmpool = LdapVmPool::model()->findByDn($pooldn);
				$vmpools[] = $vmpool->sstDisplayName;
			}
			//echo '<pre>' . print_r($vmpools, true) . '</pre>';
			$s .= '<cell><![CDATA['. $types . "]]></cell>\n";
			$s .= '<cell>' . (is_null($vmnodemaintain) ? 'null' : ($vmnodemaintain ? 'true' : 'false'))  . "</cell>\n";
			$s .= '<cell>'. $node->getVLanIP('pub') ."</cell>\n";
			$s .= '<cell><![CDATA['. implode(';<br/>', $vmpools) ."]]></cell>\n";
			$s .= "<cell></cell>\n";
//			$s .= "<cell><![CDATA[__XX__" .
//				'<a href="' . $detailurl . '?dn=' . $i .  '"><img src="' . $imagesurl . '/node_detail.png" alt="" title="view Node"/></a>' .
//				'<img src="' . $imagesurl . '/node_edit.png" alt="" title="edit Node" onclick="editRow(\'' . $i .  '\');" />' .
//				'<img src="' . $imagesurl . '/node_del.png" alt="" title="delete Node" onclick="deleteRow(\'' . $i . '\');" />' .
//			"]]></cell>\n";
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
			$node = CLdapRecord::model('LdapNode')->findByDn($dn);
			if (!is_null($node)) {
				$vms = array_merge($node->vms, $node->vmtemplates);
				if (0 == count($vms)) {
					// delete Node
					$node->delete(true);
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Node \'' . $node->sstNode . '\' attached to VMs!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Node \'' . $_POST['dn'] . '\' not found!'));
			}
		}
	}

	public function actionGetPoolInfo() {
		if(isset($_GET['dn']))
			$model = CLdapRecord::model('LdapPeople')->findbyDn($_GET['dn']);
		if($model === null)
			throw new CHttpException(404,'The requested page does not exist.');
		$this->renderPartial('info',array(
			'model' => $model,
		));
	}

	public function actionGetVms() {
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

		$criteria = array('attr'=>array());
		if (isset($_GET['node'])) {
			$criteria['attr']['sstNode'] = $_GET['node'];
		}
		$vms = CLdapRecord::model('LdapVm')->findAll($criteria);
		$count = count($vms);

		// calculate the total pages for the query
		if( $count > 0 && $limit > 0)
		{
			$total_pages = ceil($count/$limit);
		}
		else
		{
			$total_pages = 0;
		}

		// if for some reasons the requested page is greater than the total
		// set the requested page to total page
		if ($page > $total_pages)
		{
			$page = $total_pages;
		}

		// calculate the starting position of the rows
		$start = $limit * $page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;
		if (1 != $sidx) {
			$criteria['sort'] = $sidx . '.' . $sord;
		}

		$vms = CLdapRecord::model('LdapVm')->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  "<rows>";
		$s .= "<page>" . $page . "</page>";
		$s .= "<total>" . $total_pages . "</total>";
		$s .= "<records>" . $count . "</records>";

		$i = 1;
		foreach ($vms as $vm) {
			//	'colNames'=>array('No.', 'DN', 'UUID', 'Spice','Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i ."</cell>\n";
			$s .= '<cell>'.$vm->dn ."</cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachine ."</cell>\n";
			$s .= '<cell><![CDATA['. 'spice://' . $vm->getSpiceUri() . "]]></cell>\n";
			$s .= '<cell>'. $vm->sstDisplayName ."</cell>\n";
			$s .= "<cell>unknown</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= '<cell>'. $vm->sstNode ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
			$i++;
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}
	
	public function actionMaintainVmNode() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$node = LdapNode::model()->findByDn($_GET['dn']);
			if (!is_null($node)) {
				$maintain = Yii::app()->getRequest()->getParam('maintain', null);
				if (!is_null($maintain)) {
					$type = $node->getType('VM-Node');
					$type->setOverwrite(true);
					$type->sstNodeState = 'true' == $maintain ? 'maintenance' : 'active';
					$type->save(false);
				} 
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'maintainVmNode: Parameter maintain not found!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'maintainVmNode: Node \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'maintainVmNode: Parameter dn not found!'));
		}
	}
	
	/**
	 * Node Wizard
	 */

	public function actionWizard($step=null) {
		Yii::log('actionWizard: session: ' . print_r($_SESSION, true), 'profile', 'wizard');
		$this->pageTitle = 'Node Wizard';
		$this->title = 'Node Wizard';
		$this->process($step);
	}

	public function actionHandleWizardAction() {
		$this->disableWebLogRoutes();
		$actionsConfig = require(Yii::app()->getBasePath() . '/../wizards.php');
		//echo '<pre>' . print_r($actionsConfig, true) . '</pre>';

		$last = '';
		$next = '';
		if (isset($_GET['step'])) {
			$session = Yii::app()->getSession();
			$allActions = $actionsConfig['node'][$_GET['step']]['actions'];
			//echo '<pre>' . print_r($allActions, true) . '</pre>';
			$action = $session->get('node.wizard.action');
			if (!is_null($action)) {
				// there is an action running
				Yii::log('Node.actionHandleWizardAction: checkAction ' . print_r($action, true), 'profile', 'wizard');
				if (isset($action['ssh']) && is_array($action['ssh'])) {
					$ssh = $action['ssh'];
					$return = Utils::readScriptReturnSsh($ssh['host'], $ssh['username'], $ssh['password'], $action['retvalfile'], $action['pid']);
				}
				else {
					$return = Utils::readScriptReturn($action['retvalfile'], $action['pid']);
				}
				if (false !== $return) {
					// action is finished
					$return = (int) rtrim($return[0]);
					$wizardAction = $allActions[$action['idx']];
					$action['return'] = $return;
					$message = $wizardAction['return'][$return];
					$return = $return == $wizardAction['ok'];
					$lastvars = '';
					if ($return && isset($wizardAction['outputvars'])) {
						// does this script has needed vars
						if (isset($action['ssh']) && is_array($action['ssh'])) {
							$ssh = $action['ssh'];
							$output = Utils::readScriptOutputSsh($ssh['host'], $ssh['username'], $ssh['password'], $action['outputfile'], $action['pid']);
						}
						else {
							$output = Utils::readScriptOutput($action['outputfile'], $action['pid']);
						}
						if (isset($wizardAction['outputtype'])) {
							// only possible if there is an outputtype
							switch($wizardAction['outputtype']) {
								case 'JSON':
									if (is_array($output)) {
										$output = implode('', $output);
									}
									$output = CJSON::decode($output);
									Yii::log('Node.actionHandleWizardAction: JSON ' . print_r($output, true), 'profile', 'wizard');
									foreach($wizardAction['outputvars'] as $stepvar => $outputvar) {
										$found = true;
										if (1 == preg_match('/^([a-zA-Z0-9 ]+)\[([a-zA-Z0-9 ]+)\]$/', $outputvar, $matches))
										{
											Yii::log('Node.actionHandleWizardAction: from Array ' . print_r($matches, true), 'profile', 'NodeController');
											if (isset($output[$matches[1]])) {
												if (isset($output[$matches[1]][$matches[2]])) {
													$lastvars .= '<var name="' . $stepvar . '" value="' . $output[$matches[1]][$matches[2]] . '"/>';
												}
												else {
													$found = false;
												}
											}
											else {
												$found = false;
											}
										}
										else if (isset($output[$outputvar])) {
											$lastvars .= '<var name="' . $stepvar . '" value="' . $output[$outputvar] . '"/>';
										}
										else {
											$found = false;
										}
										if (!$found) {
											$return = false;
											$lastvars = '';
											$message = Yii::t('node', 'wizard.outputvar \'{var}\' missing', array('{var}' => $outputvar));
											break;
										}
									}
									break;
								case 'STRING': $output = implode('', $output); break;
							}
						}
					}
					$last = '<last idx="' . $action['idx'] . '" name="' . $wizardAction['name'] . '" return="' . ($return ? 1 : 0) . '" message="' . $message .  '">' . $lastvars . '</last>';
				}
				else {
					$last = '<last idx="' . $action['idx'] . '"/>';
					$action['return'] = -1;
				}
			}
			if (is_null($action) || -1 != $action['return']) {
				// new or first action should start
				$idx = (!is_null($action) ? $action['idx'] + 1 : 0);
				if ($idx < count($allActions)) {
					$wizardAction = $allActions[$idx];
					if (isset($wizardAction['phpfile']) && '' != $wizardAction['phpfile']) {
						require Yii::app()->extensionPath . '/wizard/' . $wizardAction['phpfile'];
					}
					//echo '<pre>' . $idx . ': ' . print_r($wizardAction, true) . '</pre>';

					$steps = $session->get('Wizard.steps');
					$call = $wizardAction['call'];
					$params = '';
					foreach($wizardAction['params'] as $param) {
						$params .= ' "' . $this->getParameter($param, $steps) . '"';
					}
					//echo "Call: " . $call;
					if (isset($wizardAction['ssh']) && is_array($wizardAction['ssh'])) {
						$ssh = $wizardAction['ssh'];
						$ssh['host'] = $this->getParameter($ssh['host'], $steps);
						$ssh['username'] = $this->getParameter($ssh['username'], $steps);
						$ssh['password'] = $this->getParameter($ssh['password'], $steps);
						// echo '<pre>ssh ' . print_r($ssh, true) . '</pre>';
						try {
							$data = Utils::executeScriptASyncSsh($ssh['host'], $ssh['username'], $ssh['password'], $call, $params);
							$action = array('idx' => $idx, 'title' => $wizardAction['title'], 'pid' => $data['pid'], 'outputfile' => $data['outputfile'], 'retvalfile' => $data['retvalfile'],
										'ssh' => $ssh);
						}
						catch (CPhpSshException $e) {
							//if (CPhpSsh::$SSH_UNKNOWN_HOST == $e->getCode()) {
								$return = false;
								$message = $e->getMessage();
							//}
							$last = '<last idx="' . $idx . '" name="' . $wizardAction['name'] . '" return="' . ($return ? 1 : 0) . '" message="' . $message .  '"></last>';
						}
					}
					else {
						$data = Utils::executeScriptASync($call, $params);
						$action = array('idx' => $idx, 'title' => $wizardAction['title'], 'pid' => $data['pid'], 'outputfile' => $data['outputfile'], 'retvalfile' => $data['retvalfile']);
					}
					Yii::log('Node.actionHandleWizardAction: createAction ' . print_r($action, true), 'profile', 'wizard');

					if (isset($action)) {
						$next = '<next idx="' . $action['idx'] . '" name="' . $wizardAction['name'] .  '" title="' . $action['title'] . '" />';
						$session->add('node.wizard.action', $action);
					}
				}
				else {
					$session->remove('node.wizard.action');
				}
			}
		}

		Yii::app()->params['paramName'];

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .= '<progress total="' . count($allActions) . '">';
		$s .= $last;
		$s .= $next;
		$s .= '</progress>';
		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;

	}

	// Wizard Behavior Event Handlers
	/**
	* Raised when the wizard starts; before any steps are processed.
	* MUST set $event->handled=true for the wizard to continue.
	* Leaving $event->handled===false causes the onFinished event to be raised.
	* @param WizardEvent The event
	*/
	public function wizardStart($event) {
		$event->handled = true;
	}

	/**
	 * Raised when the wizard detects an invalid step
	 * @param WizardEvent The event
	 */
	public function wizardInvalidStep($event) {
		Yii::app()->getUser()->setFlash('notice', $event->step.' is not a vaild step in this wizard');
	}

	/**
	 * Raised when the wizard is cancelled
	 * @param WizardEvent The event
	 */
	public function wizardCancelled($event) {
		$session = Yii::app()->getSession();
		$session->remove('node.wizard.action');
	}

	/**
	 * The wizard has finished; use $event->step to find out why.
	 * Normally on successful completion ($event->step===true) data would be saved
	 * to permanent storage; the demo just displays it
	 * @param WizardEvent The event
	 */
	public function wizardFinished($event) {
		if ($event->step===true) {
			$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array('sstNode'=>$event->data['wizardNodeTest']['nodename'])));
			if (0 == count($nodes)) {
				$server = CLdapServer::getInstance();
				$node = new LdapNode();
				$node->sstNode = $event->data['wizardNodeTest']['nodename'] . '.' . $event->data['wizardNodeTest']['domain'];
				$node->description = 'The node ' . $node->sstNode . '.';
				$node->labeledURI = 'ldap:///ou=virtual machines,ou=virtualization,ou=services,' . $server->getBaseDn() . '??sub?(sstNode=' . $node->sstNode . ')';
				$node->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
				$node->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
				if ($node->save()) {
					$data = array();
					$data['objectClass'] = array('top', 'organizationalUnit');
					$data['ou'] = 'node-types';
					$dn = 'ou=node-types,sstNode=' . $node->sstNode . ',ou=nodes,ou=virtualization,ou=services';
					$server->add($dn, $data);
					$neededservices = array();
					foreach($event->data['wizardNode']['nodetype'] as $type) {
						$nodeType = CLdapRecord::model('LdapNodeTypeDefinition')->findByDn('sstNodeType=' . $type . ',ou=node-types,ou=configuration,ou=virtualization,ou=services,' . $server->getBaseDn());
						$nodeType->setOverwrite(true);
						$nodeType->setAsNew();
						$nodeType->setBranchDn($dn);
						$nodeType->sstNodeState = 'active';
						$neededservices = array_merge($neededservices, $nodeType->sstService);
						$nodeType->save();
					}
					//echo '<pre>' . print_r($neededservices, true) . '</pre>';
					$data = array();
					$data['objectClass'] = array('top', 'organizationalUnit');
					$data['ou'] = 'networks';
					$dn = 'ou=networks,sstNode=' . $node->sstNode . ',ou=nodes,ou=virtualization,ou=services';
					$server->add($dn, $data);

					$networkdefs = CLdapRecord::model('LdapNodeNetworkDefinition')->findAll(array('attr' => array()));
					foreach($networkdefs as $networkdef) {
						$network = new LdapNodeNetwork();
						$network->setBranchDn($dn);
						$network->ou = $networkdef->ou;
						$network->sstNetworkIPAddress = (isset($event->data['wizardNodeTest'][$networkdef->ou . 'ip']) ? $event->data['wizardNodeTest'][$networkdef->ou . 'ip'] : '??TBD??');
						//echo $networkdef->ou . '<br/>';
						$network->save();
						foreach($networkdef->services as $servicedef) {
							//echo $servicedef->sstService;
							if (in_array($servicedef->sstService, $neededservices)) {
								//echo ' needed';
								$service = new LdapNodeNetworkService();
								$service->setBranchDn($network->getDn());
								$service->sstService = $servicedef->sstService;
								$service->sstDisplayName = $servicedef->sstDisplayName;
								$service->description = $servicedef->description;
								$service->save();
							}
							//echo '<br/>';
						}
					}
				}
			}
			else {
				Yii::app()->user->setFlash('notice', Yii::t('node', '"{nodename}" is already integrated!', array('{nodename}' =>$event->data['wizardNodeTest']['nodename'])));
			}
		}

		$event->sender->reset();
		$this->redirect(array('node/index'));
	}

	/**
	 * Saves a draft of the wizard that can be resumed at a later time
	 * @param WizardEvent $event
	 */
	public function wizardSaveDraft($event) {
		$step = new WizardNode();
		$uuid = $step->saveRegistration($event->data);
		$event->sender->reset();
		$this->render('wizard_draft',compact('uuid'));
		Yii::app()->end();
	}

	/**
	 * Process wizard steps.
	 * The event handler must set $event->handled=true for the wizard to continue
	 * @param WizardEvent The event
	 */
	public function wizardProcessStep($event) {
		$modelName = ucfirst($event->step);
		$model = new $modelName();
		$model->attributes = $event->data;
		$form = $model->getForm();
		$jscript = null;
		if (is_subclass_of($model, 'WizardActions')) {
			$jscript = $model->getJScript($this);
		}

		// Note that we also allow sumission via the Save button
		if (($form->submitted()||$form->submitted('save_draft')) && $form->validate()) {
			$event->sender->save($model->attributes);
			$event->handled = true;
		}
		else
		$this->render('wizard_form', compact('event','form','jscript'));
	}
}