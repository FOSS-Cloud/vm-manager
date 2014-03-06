<?php
class ChangeConfigurationVmPools extends Patch
{
	private $countVmPoolConfig = 0;
	private $processedVmPoolConfig = 0;
	private $idx = 0;
	
//	private $paramA;
	
	public function init($xmlPatch) {
		parent::init($xmlPatch);
		$config = LdapVmPoolDefinitionAct::model()->findAll(array('attr' => array()));
		$this->countVmPoolConfig = count($config);
		Yii::log('Config: ' . $this->countVmPoolConfig, 'profile', 'patch.ChangeConfigurationVmPools');
	}

	public function process($init=false) {
		$data = parent::process($init);

		if (100 <= $data['totalvalue']) {
			$data['message'] = '(' . $this->countVmPoolConfig . ' VmPools processed)';
		}
		return $data;
	}

	public function processVmPools($init, $params) {
		Yii::log('processVmPools init: ' . var_export($init, true), 'profile', 'patch.ChangeConfigurationVmPools');
		$processed = $this->processedVmPoolConfig + 1;
		if ($init) {
			$actionCount = 1;
		}
		else {
			//sleep(2);
			$vmPools = LdapVmPoolDefinitionAct::model()->findAll(array('attr' => array()));
			if (0 < count($vmPools)) {
				$vmPool = $vmPools[$this->idx];
				Yii::log('processVmPools: VmPool=' . $vmPool->ou, 'profile', 'patch.ChangeConfigurationVmPools');
				
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
				$processed = 0;
			}
			$actionCount = $this->actionCount;
		}
// 		return array('parttext' => $this->actions[0]['description'] . '<br/><b>' . $processed . ' of ' . $this->countVmPoolConfig . ' VmPools processed</b>', 
// 				'partvalue' => $this->actionProgress, 
// 				'totalvalue' => ($this->actions[0]['order'] * 100 / $actionCount) * $this->actionProgress / 100);
		return array('parttext' => $this->actions[0]->description . '<br/><b>' . $processed . ' of ' . $this->countVmPoolConfig . ' VmPools processed</b>', 
				'partvalue' => $this->actionProgress, 
				'totalvalue' => ($this->actions[0]->order * 100 / $actionCount) * $this->actionProgress / 100);
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