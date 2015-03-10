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
 * VmListController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class VmListController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'vmlist';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$this->submenu['links'] = array(
			'label' => Yii::t('menu', 'Links'),
			'static' => true,
			'items' => array(
				array(
					'label' => Yii::t('menu', 'Download Spice Client'),
					'url' => 'http://www.foss-cloud.org/en/wiki/Spice-Client',
					'itemOptions' => array('title' => Yii::t('menu', 'Spice Client Tooltip')),
					'linkOptions' => array('target' => '_blank')
				)
			)
		);

		$this->activesubmenu = 'vmList';
		return true;
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
			        'actions'=>array('index', 'getStartVmGui', 'startVm', 'getAssignVmGui', 'assignVm'),
		        'users'=>array('@'),
			),
			array('deny',  // deny all users
	   	 	    'users'=>array('*'),
			),
		);
	}

	public function actionIndex() {
		$user = CLdapRecord::model('LdapUser')->findByDn('uid=' . Yii::app()->user->uid . ',ou=people');
		$usergroups = Yii::app()->user->getState('groupuids', array());
		//echo 'User: ' . $user->uid . '; <pre>Usergroups: ' . print_r($usergroups, true) . '</pre>';
		$data = array('vms'=>array(), 'vmpools'=>array());

		// Let's check the dynamic VM Pools
		$vmpools = LdapVmPool::model()->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'dynamic')));
		foreach($vmpools as $vmpool) {
			$poolAssigned = false;
			$poolgroups = $vmpool->groups;
			//echo 'Pool: ' . $vmpool->sstDisplayName . '; Groups: ';
			foreach($poolgroups as $poolgroup) {
				//echo $poolgroup->ou . ', ';
				if (false !== array_search($poolgroup->ou, $usergroups)) {
					$poolAssigned = true;
					break;
				}
			}
			//echo '<br/>';
			if (!$poolAssigned) {
				// No Pool assigned; try users
				//echo 'Pool: ' . $vmpool->sstDisplayName . '; Users: ';
				$vmuser = $vmpool->people;
				foreach($vmuser as $vmoneuser) {
					//echo $vmoneuser->ou . ', ';
					if ($user->uid == $vmoneuser->ou) {
						$poolAssigned = true;
						break;
					}
				}
				//echo '<br/>';
			}
			//echo 'looking for pool: ' . $vmpool->sstDisplayName . '<br/>';
			$poolAssigned = $this->additionalVmPoolCheck($vmpool, $poolAssigned);
			
			if ($poolAssigned) {
				$vmAssigned = false;
				$vmFree = false;
				//echo '   group found<br/>';
				// The user is in a group. Now let's check if there is already a VM running
				//$vms = LdapVm::model()->findAll(array('attr'=>array('sstVirtualMachinePool'=>$vmpool->sstVirtualMachinePool)));
				$vms = $vmpool->runningDynVms;
				foreach($vms as $vm) {
					$vmpeople = $vm->people;
					//echo '<span style="margin-left: 20px;"> </span>looking for vm: ' .  $vm->sstVirtualMachine . '<br/>';
					if (0 == count($vmpeople) && !$vmFree) {
						$vmFree = true;
					}
					$vmAssigned = $this->additionalVmCheck($vm, $vmAssigned);
					if ($vmAssigned) {
						$data['vmpools'][$vmpool->sstDisplayName] = array(
							'description' => $vmpool->description,
							'spiceuri' => $vm->getSpiceUri()
						);
						//echo ' <pre>DATA '  . var_export($data, true) . '</pre>';
						break;
					}
				}
				if (!$vmAssigned && $vmFree) {
					$data['vmpools'][$vmpool->sstDisplayName] = array(
						'description' => $vmpool->description,
						'dn' => $vmpool->getDn(),
					);
				}
				//echo '<br/>';
			}
		}
		$vms = LdapVm::getAssignedVms('persistent', array('attr' => array('sstVirtualMachineType' => 'persistent')));
		foreach($vms as $vm) {
			$libvirt = CPhpLibvirt::getInstance();
			$status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));
			$data['vms'][$vm->sstDisplayName] = array(
				'description' => $vm->description . ' (persistent)',
				'spiceuri' => $vm->getSpiceUri(),
				'uuid' => $vm->sstVirtualMachine,
				'dn' => $vm->getDn(),
				'active' => $status['active']
			);
		}
		
		$this->header[] = '<meta http-equiv="refresh" content="30; URL=' . Yii::app()->request->url . '">';
		$this->render('index',array(
			'data'=>$data,
		));
	}
	
	protected function additionalVmPoolCheck($vmpool, $poolAssigned) {
		return $poolAssigned;
	}

	protected function additionalVmCheck($vm, $vmAssigned) {
		if ('dynamic' === $vm->sstVirtualMachineType) {
			$vmpeople = $vm->people;
			foreach($vmpeople as $vmonepeople) {
				if ($vmonepeople->ou == Yii::app()->user->uid) {
					$vmAssigned = true;
					break;
				}
			}
		}
		return $vmAssigned;
	}

	public function actionGetStartVmGui() {
		$this->disableWebLogRoutes();
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmlist', 'Start VM') . '</span></div>';
?>
		<div style="text-align: center;" ><img id="running" src="<?php echo Yii::app()->baseUrl; ?>/images/loading.gif" alt="" /><br/></div>
		<div id="errorAssignment" class="ui-state-error ui-corner-all" style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>
			<span id="errorMsg">
			<?=Yii::t('vmlist', 'starting VM'); ?></span></p>
		</div>
		<div id="infoAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionStartVm($dn) {
		$this->disableWebLogRoutes();
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($dn);

		if (!is_null($vmpool)) {
			try {
				$vm = $vmpool->startVm();
				if (!is_null($vm)) {
					$json = array('err' => false, 'message' => 'VM started!', 'spiceuri' => $vm->getSpiceUri());
				}
				else {
					$json = array('err' => true, 'message' => 'unable to start VM!');
				}
			}
			catch (Exception $e) {
				$json = array('err' => true, 'message' => $e->getMessage());
			}
		}
		else {
			$json = array('err' => true, 'message' => 'VM Pool ' . $dn . ' not found!');
		}
		$this->sendJsonAnswer($json);
	}

	public function actionGetAssignVmGui() {
		$this->disableWebLogRoutes();
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmlist', 'Assign VM') . '</span></div>';
?>
		<div style="text-align: center;" ><img id="running" src="<?php echo Yii::app()->baseUrl; ?>/images/loading.gif" alt="" /><br/></div>
		<div id="errorAssignment" class="ui-state-error ui-corner-all" style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>
			<span id="errorMsg">
			<?=Yii::t('vmlist', 'assigning VM'); ?></span></p>
		</div>
		<div id="infoAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionAssignVm($dn) {
		$this->disableWebLogRoutes();
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($dn);

		if (!is_null($vmpool)) {
			$vm = $vmpool->getFreeVm();
			if (!is_null($vm)) {
				$vm->assignUser();
				$json = array('err' => false, 'message' => 'VM found!', 'spiceuri' => $vm->getSpiceUri());
			}
			else {
				$json = array('err' => true, 'message' => <<<EOS
Two parameters must be taken into account in which always the lower has precedence:<br/><br/>

<ul>
<li>Maximum number of virtual machines that are specified in the VM Pool.</li>
<li>Maximum number of IP addresses in the Network Range(s).</li>
</ul><br/>
There is currently no free workplace. Contact your administrator or try it again
later.
EOS
				);
			}
		}
		else {
			$json = array('err' => true, 'message' => 'VM Pool ' . $dn . ' not found!');
		}
		$this->sendJsonAnswer($json);
	}

	public function actionStartPersistentVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
			//$devices = $vm->devices[0];
			//echo '$devices <pre>' . print_r($devices, true) . '</pre>';
			//$disks = $devices->disks;
			//echo '<pre>' . print_r($devices->disks, true) . '</pre>';
			//$interfaces = $devices->interfaces;
			//echo '<pre>' . print_r($devices->interfaces, true) . '</pre>';
			if (!is_null($vm)) {
				//echo '<pre>' . print_r($params, true) . '</pre>';
				$retval = false;
				$answer = array();
				$libvirt = CPhpLibvirt::getInstance();
				if ('persistent' == $vm->sstVirtualMachineType) {
					$data = $vm->getStartParams();
					$data['name'] = $data['sstName'];
					$libvirt->redefineVm($data);
					$retval = $libvirt->startVm($data);
					if ($retval) {
						$vm->setOverwrite(true);
						$vm->sstStatus = 'running';
						$vm->save();
					}
				}
				if (false !== $retval) {
					$answer['error'] = 0;
					$this->sendAjaxAnswer($answer);
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt startVm failed (' . $libvirt->getLastError() . ')!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}
}
