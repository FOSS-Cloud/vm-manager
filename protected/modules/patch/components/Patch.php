<?php

//namespace patch\compponents;

class Patch extends CComponent
{
	public $patchName = '';
	public $patchPath = '';
	public $description = '';

	protected $patchParams;
	protected $finishtype = 'remove';
	
	protected $actionName = '';
	protected $actionProgress = 0;
	protected $actions = array();
	protected $actionCount = 0;
	protected $postActions = array();
	
	public function init($xmlPatch) {
		$this->patchParams = Patch::getParams($xmlPatch->getElementsByTagName('param'), null);
		$actions = $xmlPatch->getElementsByTagName('actions')->item(0);
		if (!is_null($actions)) {
			//$actions = $actions->getElementsByTagName('action');
			$this->actions = $this->getActions($actions->getElementsByTagName('action'));
// 			$order = 1;
// 			foreach ($actions as $xmlAction) {
// 				if ($xmlAction->hasAttribute('name')) {
// 					$actionName = $xmlAction->getAttribute('name');
// 				}
// 				else {
// 					throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Action without "name" attribute found!', array('{lineno}' => $xmlAction->getLineNo())));
// 				}
// 				if ($xmlAction->hasAttribute('description')) {
// 					$description = $xmlAction->getAttribute('description');
// 				}
// 				else {
// 					$description = '';
// 				}
					
// 				$params = Patch::getParams($xmlAction->getElementsByTagName('param'), $actionName);
// 				$className = 'PatchAction_' . ucfirst($actionName);
// 				if (@class_exists($className)) {
// 					$action = new $className();
// 					$action->lineno = $xmlAction->getLineNo();
// 					$action->name = $actionName;
// 					$action->patchPath = $this->patchPath;
// 					$action->order = $order;
// 					$action->description = $description;
// 					$action->params = $params;
// 					if ($action->checkParams()) {
// 						$this->actions[] = $action;
// 					}
// 				}
// 				else {
// 					if (method_exists($this, $actionName)) {
// // 						$this->actions[] = array('name' => $actionName, 'description' => $description, 'lineno' => $xmlAction->getLineNo(), 'order' => $order, 'params' => $params);
// // 						$method = $actionName;
// // 						$data = $this->$method(true, $params);
						
// 						$action = new PatchInlineAction();
// 						$action->lineno = $xmlAction->getLineNo();
// 						$action->name = $actionName;
// 						$action->patchPath = $this->patchPath;
// 						$action->order = $order;
// 						$action->description = $description;
// 						$action->params = $params;
// 						$action->inlineObject = $this;
// 						$this->actions[] = $action;
						
// 					}
// 					else {
// 						throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Unknown action "{actionName}" found!', array('{lineno}' => $xmlAction->getLineNo(), '{actionName}' => $actionName)));
// 					}
// 				}
// 				$order++;
// 			}
		}
		$this->actionCount = count($this->actions);
		$actions = $xmlPatch->getElementsByTagName('postactions')->item(0);
		if (!is_null($actions)) {
			$this->postActions = $this->getActions($actions->getElementsByTagName('action'));
		}
		
		$finish = $xmlPatch->getElementsByTagName('finish')->item(0);
		if (!is_null($finish)) {
			$finishtype = $finish->getAttribute('type');
			if ('remove' === $finishtype || 'archive' === $finishtype || 'leave' === $finishtype) {
				$this->finishtype = $finishtype;
			}
			else {
				throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Unknown finish type "{finishType}" found!', array('{lineno}' => $finish->item(0)->getLineNo(), '{finishType}' => $finishtype)));
			}
		}
		else {
			throw new PatchException(PatchModule::t('patch', 'Mandatory XML tag "{xmlTag}" missing!', array('{xmlTag}' => 'finish')));
		}
	}
	
	public function process() {
		$session = Yii::app()->getSession();
	
		try {
			if ('' !== $this->actionName) {
				Yii::log('Patch::process action ' . $this->actionName, 'profile', 'patch.Action');
				if (100 <= $this->actionProgress) {
					$this->actionName = '';
					$this->actionProgress = 0;
					array_shift($this->actions);
					sleep(2);
				}
				else {
					$action = $this->actions[0];
					if (is_subclass_of($action, 'PatchAction')) {
						Yii::log('Patch::process ' . $action->name, 'profile', 'patch.Action');
						$data = $action->run(false);
					}
					$this->actionProgress = $data['partvalue'];
				}
			}
			if ('' === $this->actionName) {
				Yii::log('Patch::process new action', 'profile', 'patch.Action');
	
				$action = $this->actions[0];
				if (!is_null($action)) {
					if (is_subclass_of($action, 'PatchAction')) {
						Yii::log('Patch::process ' . $action->name, 'profile', 'patch.Action');
						$this->actionName = $action->name;
						$this->actionProgress = 0;
						$data = $action->run(true);
					}
					$this->actionProgress = $data['partvalue'];
				}
			}
		}
		catch (PatchException $e) {
			Yii::log('Patch::process Exception ' . $e->getTraceAsString(), 'profile', 'patch.Action');
			$data['error'] = true;
			$data['message'] = $e->getMessage();
			$data['partvalue'] = 0;
		}
		
		if (!isset($data['error']) || !$data['error']) {
			$data['action'] = 'action' + $action->order;
			$data['totaltext'] = $this->description;
			$data['totalvalue'] = round(($action->order * 100 / $this->actionCount) * $data['partvalue'] / 100, 1);
			$data['partvalue'] = round($data['partvalue'], 1);
			
			if (100 <= $data['totalvalue']) {
				if (!isset($data['message'])) {
					$data['message'] = PatchModule::t('patch', 'finished: {actionCount} of {actionCount} actions processed!', array('{actionCount}' => $this->actionCount));
				}
				
				if ('archive' === $this->finishtype) {
					$patchesDir = Yii::app()->runtimePath . DIRECTORY_SEPARATOR . 'patches';
					$archiveDir = $patchesDir . DIRECTORY_SEPARATOR . 'archive';
					if (!is_dir($archiveDir)) {
						mkdir($archiveDir, fileperms(Yii::app()->runtimePath . DIRECTORY_SEPARATOR . 'patches'));
					}
					if (false === @rename($patchesDir . DIRECTORY_SEPARATOR . $this->patchName, $archiveDir . DIRECTORY_SEPARATOR . $this->patchName)) {
						$data['error'] = true;
						$data['message'] = PatchModule::t('patch', 'Unable to move patch to archive!');
					} 
				}
				else if ('remove' === $this->finishtype) {
					$patchesDir = Yii::app()->runtimePath . DIRECTORY_SEPARATOR . 'patches';
					if (false === $this->delTree($patchesDir . DIRECTORY_SEPARATOR . $this->patchName)) {
						$data['error'] = true;
						$data['message'] = PatchModule::t('patch', 'Unable to delete patch!');
					} 
				}
			}
		}

		return $data;
	}
	
	public function checkParams() {
		return true;
	}
	
	public static function getParams($paramsXml, $actionName) {
		$params = array();
		if (is_null($actionName)) {
			$within = 'the mainClass';
		}
		else {
			$within = 'the action "{actionName}"';
		}
		foreach($paramsXml as $param) {
			if (!$param->hasAttribute('name')) {
				throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Parameter without "name" attribute within ' . $within . ' found!', array('{lineno}' => $param->getLineNo(), '{actionName}' => $actionName)));
			}
			else if (0 === strlen($param->getAttribute('name'))) {
				throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Parameter with empty "name" attribute within ' . $within . ' found!', array('{lineno}' => $param->getLineNo(), '{actionName}' => $actionName)));
			}
			$set = $param->getElementsByTagName('set');
			if (1 === $set->length) {
				$items = $set->item(0)->getElementsByTagName('item');
				$paramValue = array();
				foreach($items as $item) {
					$paramValue[] = $item->textContent;
				}
			}
			else {
				$paramValue = $param->textContent;
			}
			$params[$param->getAttribute('name')] = $paramValue;
		}
		return $params;
	}
	
	public function getActions($xmlActions) {
		$order = 1;
		$actions = array();
		foreach ($xmlActions as $xmlAction) {
			if ($xmlAction->hasAttribute('name')) {
				$actionName = $xmlAction->getAttribute('name');
			}
			else {
				throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Action without "name" attribute found!', array('{lineno}' => $xmlAction->getLineNo())));
			}
			if ($xmlAction->hasAttribute('description')) {
				$description = $xmlAction->getAttribute('description');
			}
			else {
				$description = '';
			}
				
			$params = Patch::getParams($xmlAction->getElementsByTagName('param'), $actionName);
			$className = 'PatchAction_' . ucfirst($actionName);
			if (@class_exists($className)) {
				$action = new $className();
			}
			else {
				if (method_exists($this, $actionName)) {
					$action = new PatchInlineAction();
					$action->inlineObject = $this;
				}	
				else {
					throw new PatchException(PatchModule::t('patch', 'Error in line {lineno}: Unknown action "{actionName}" found!', array('{lineno}' => $xmlAction->getLineNo(), '{actionName}' => $actionName)));
				}
			}

			$action->lineno = $xmlAction->getLineNo();
			$action->name = $actionName;
			$action->patchPath = $this->patchPath;
			$action->order = $order;
			$action->description = $description;
			$action->params = $params;
			if ($action->checkParams()) {
				$actions[] = $action;
			}
			$order++;
		}
		return $actions;
	}
	
	protected function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			$path = $dir  . DIRECTORY_SEPARATOR . $file;
			(is_dir($path)) ? $this->delTree($path) : unlink($path);
		}
		return rmdir($dir);
	}
}