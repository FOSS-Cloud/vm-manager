<?php
class UpdateLdap extends Patch
{
	private $countVmPoolConfig = 0;
	private $processedVmPoolConfig = 0;
	private $countVmDisks = 0;
	private $processedVmDisks = 0;
	private $idx = 0;
	
//	private $paramA;
	
	public function init($xmlPatch) {
		parent::init($xmlPatch);
		$config = LdapVmPoolDefinitionAct::model()->findAll(array('attr' => array()));
		$this->countVmPoolConfig = count($config);
		Yii::log('Config: ' . $this->countVmPoolConfig, 'profile', 'patch.UpdateLdap');
		$disks = LdapVmDeviceDisk::model()->findAll(array('branchDn' => 'ou=virtual machines,ou=virtualization,ou=services', 'attr' => array('sstDriverType' => 'qcow2'), 'depth' => true));
		$disks2 = LdapVmDeviceDisk::model()->findAll(array('branchDn' => 'ou=virtual machine profiles,ou=virtualization,ou=services', 'attr' => array('sstDriverType' => 'qcow2'), 'depth' => true));
		$disks = array_merge($disks, $disks2);
		$this->countVmDisks = count($disks);
		Yii::log('Disks: ' . $this->countVmDisks, 'profile', 'patch.UpdateLdap');
	}

// 	public function process($init=false) {
// 		$data = parent::process($init);

// 		if (100 <= $data['totalvalue']) {
// 			//$data['message'] = 'finished';
// 		}
// 		return $data;
// 	}

	public function processStoragePoolConfig($init, $params) {
		Yii::log('processStoragePoolConfig init: ' . var_export($init, true), 'profile', 'patch.UpdateLdap');
		$processed = $this->processedVmPoolConfig;
		if ($init) {
			$actionCount = 1;
		}
		else {
			$basedefinition = CLdapRecord::model('LdapStoragePoolDefinition')->findByAttributes(array('attr'=>array('ou'=>'basedir')));
			if($basedefinition === null) {
				$basedefinition = new LdapStoragePoolDefinition();
				$basedefinition->ou = 'basedir';
				$basedefinition->sstSelfService = 'FALSE';
				$basedefinition->sstStoragePoolType = 'none';
				$persistentdefinition = CLdapRecord::model('LdapStoragePoolDefinition')->findByAttributes(array('attr'=>array('ou'=>'vm-persistentt')));
				if($persistentdefinition != null) {
					$basedefinition->sstStoragePoolURI = substr($persistentdefinition->sstStoragePoolURI, 0, strrpos($persistentdefinition->sstStoragePoolURI, '/', -2));
					$basedefinition->save(false);
				}
				else {
					Yii::log('processStoragePoolConfig throw', 'profile', 'patch.UpdateLdap');
					throw new PatchException('Unable to find StoragePool configuration "vm-persitent"!');
				}
			}
				
			$this->actionProgress = 100;
		}
		return array('parttext' => $this->actions[0]->description . '<br/><b>basedir set!</b>',
				'partvalue' => $this->actionProgress);
		//'totalvalue' => ($this->actions[0]->order * 100 / $actionCount) * $this->actionProgress / 100);
		}
		
	public function processVmPools($init, $params) {
		Yii::log('processVmPools init: ' . var_export($init, true), 'profile', 'patch.UpdateLdap');
		$processed = $this->processedVmPoolConfig;
		if ($init) {
			$actionCount = 1;
		}
		else {
			$vmPools = LdapVmPoolDefinitionAct::model()->findAll(array('attr' => array()));
			if (0 < count($vmPools)) {
				//sleep(4);
				$processed++;
				$vmPool = $vmPools[$this->idx];
				Yii::log('processVmPools: VmPool=' . $vmPool->ou, 'profile', 'patch.UpdateLdap');
				
				$vmPool->addObjectClass('sstVirtualMachinePoolConfigurationObjectClass');
				$vmPool->setOverwrite(true);
				$vmPool->sstNumberOfScreens = 1;
				$vmPool->save();
				
				$this->actionProgress += 100 / $this->countVmPoolConfig;
				$this->processedVmPoolConfig++;
				
				$this->idx++;
			}
			if ($this->processedVmPoolConfig === $this->countVmPoolConfig) {
				$this->actionProgress = 100;
			}
			$actionCount = $this->actionCount;
		}
// 		return array('parttext' => $this->actions[0]['description'] . '<br/><b>' . $processed . ' of ' . $this->countVmPoolConfig . ' VmPools processed</b>', 
// 				'partvalue' => $this->actionProgress, 
// 				'totalvalue' => ($this->actions[0]['order'] * 100 / $actionCount) * $this->actionProgress / 100);
		return array('parttext' => $this->actions[0]->description . '<br/><b style="margin-left: 20px;">' . $processed . ' of ' . $this->countVmPoolConfig . ' VmPools processed</b>', 
				'partvalue' => $this->actionProgress); 
				//'totalvalue' => ($this->actions[0]->order * 100 / $actionCount) * $this->actionProgress / 100);
	}
	
	public function processVmWriteback($init, $params) {
		Yii::log('processVmWriteback init: ' . var_export($init, true), 'profile', 'patch.UpdateLdap');
		$processed = $this->processedVmDisks;
		if ($init) {
			$actionCount = 1;
			$this->idx = 0;
		}
		else {
			$disks = LdapVmDeviceDisk::model()->findAll(array('branchDn' => 'ou=virtual machines,ou=virtualization,ou=services', 'attr' => array('sstDriverType' => 'qcow2'), 'depth' => true));
			$disks2 = LdapVmDeviceDisk::model()->findAll(array('branchDn' => 'ou=virtual machine profiles,ou=virtualization,ou=services', 'attr' => array('sstDriverType' => 'qcow2'), 'depth' => true));
			$disks = array_merge($disks, $disks2);
			if (0 < count($disks)) {
				//sleep(4);
				$processed++;
				$disk = $disks[$this->idx];
				Yii::log('processVmWriteback: Disk=' . $disk->getDn(), 'profile', 'patch.UpdateLdap');
		
				$disk->setOverwrite(true);
				$disk->sstDriverCache = 'writeback';
				$disk->save();
		
				$this->actionProgress += 100 / $this->countVmDisks;
				$this->processedVmDisks++;
		
				$this->idx++;
			}
			if ($this->processedVmDisks === $this->countVmDisks) {
				$this->actionProgress = 100;
			}
			$actionCount = $this->actionCount;
		}
		return array('parttext' => $this->actions[0]->description . '<br/><b>' . $processed . ' of ' . $this->countVmDisks . ' disks processed</b>',
				'partvalue' => $this->actionProgress);
				//'totalvalue' => ($this->actions[0]->order * 100 / $actionCount) * $this->actionProgress / 100);
			}
	public function checkParams($action=null, $params=null) {
		$retval = parent::checkParams();
		if ($retval) {
			if (is_null($action)) {
//				if (!isset($this->patchParams['a'])) {
//					throw new PatchException(PatchModule::t('patch', 'Error: Param with name "{paramName}" within mainClass not found!', array('{paramName}' => 'a')));
//				}
//				else {
//					$this->paramA = $this->patchParams['a'];
//				}
			}
			else if ('processVmPools' === $action) {
//				if (!isset($params['b'])) {
//					throw new PatchException(PatchModule::t('patch', 'Error: Param with name "{paramName}" within the action "{actionName}" not found!', array('{paramName}' => 'b', '{actionName}' => 'processVmPools')));
//				}
			}
		}
		return $retval;
	}
}