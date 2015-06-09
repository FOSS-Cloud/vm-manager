<?php

//namespace patch\components;

class PatchController extends BaseController
{
	/**
	 * @return array action filters
	 */
	public function filters ()
	{
		return array (
				'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules ()
	{
		return array (
				array('allow', // allow authenticated user to perform 'create' and 'update' actions
						'actions' => array('index', 'start', 'process'),
						'users' => array('@'),
						//'expression' => 'true',
				),
				array('deny',  // deny all users
						'users' => array('*'),
				),
		);
	}

	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'config';

			if ('process' !== $action->id) {
				$cs=Yii::app()->clientScript;
				$cs->scriptMap['jquery.js'] = false;
				$cs->scriptMap['jquery.min.js'] = false;
				
				Yii::app()->getclientScript()->registerCssFile($this->cssBase . '/jquery/osbd/jquery-ui.custom.css');
				Yii::app()->clientScript->registerScriptFile('jquerynew.js', CClientScript::POS_BEGIN);
				Yii::app()->clientScript->registerScriptFile('jqueryuinew.js', CClientScript::POS_BEGIN);
			}
		}
		return $retval;
	}

	public function actionIndex() {
		$this->title = PatchModule::t('patch', 'List of available patches');
		$patches = array();
		$patchDir = Yii::app()->runtimePath . DIRECTORY_SEPARATOR . 'patches';
		$entries = array_diff(scandir($patchDir), array('..', '.', 'archive'));
		foreach($entries as $entry) {
			if (is_dir($patchDir . DIRECTORY_SEPARATOR . $entry)) {
				$xml = new DOMDocument();
				$xml->load($patchDir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'patch.xml');
				$patch = $xml->getElementsByTagName('patch')->item(0);
				if (!is_null($patch)) {
					$patches[$entry] = array('title' => $patch->getElementsByTagName('title')->item(0)->textContent,
							'description' => $patch->getElementsByTagName('description')->item(0)->textContent);
				}
			}
		}
// 		$fh = opendir($patchDir);
// 		if (false !== $fh) {
// 			while (false !== ($entry = readdir($fh))) {
// 				if (is_dir($patchDir . DIRECTORY_SEPARATOR . $entry) && '.' !== $entry && '..' !== $entry && 'archive' !== $entry) {
// 					$xml = new DOMDocument();
// 					$xml->load($patchDir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'patch.xml');
// 					$patch = $xml->getElementsByTagName('patch')->item(0);
// 					if (!is_null($patch)) {
// 						$patches[$entry] = array('title' => $patch->getElementsByTagName('title')->item(0)->textContent,
// 							'description' => $patch->getElementsByTagName('description')->item(0)->textContent);
// 					}
// 				}
// 			}
//			closedir($fh);
// 		}
			
		$this->render('index', array('patches' => $patches));
	}

	public function actionStart($name, $key=null) {
		$session = Yii::app()->getSession();
		$options = array('error' => false, 'message' => '', 'patchname' => $name, 'patchkey' => time());
		$patchDir = Yii::app()->runtimePath . DIRECTORY_SEPARATOR . 'patches';
		$sessionpatch = array('key' => $options['patchkey']);
		if (is_dir($patchDir . DIRECTORY_SEPARATOR . $name)) {
			$xml = new DOMDocument();
			$xml->load($patchDir . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'patch.xml');
			//			$patches = $xml->getElementsByTagName('patch');
			//			foreach ($patches as $patch) {
			$patch = $xml->getElementsByTagName('patch')->item(0);
			if (!is_null($patch)) {
				$module = $patch->getAttribute('module');
				$version = $patch->getElementsByTagName('version')->item(0);
				if ('core' === $module) {
					$minVersion = $this->getVersionAsNumber($version->getAttribute('min'));
					$maxVersion = $this->getVersionAsNumber($version->getAttribute('max'));
					$actVersion = $this->getVersionAsNumber(Yii::app()->getSession()->get('version', 0));
					if (('*' !== $minVersion && $minVersion > $actVersion) || ('*' !== $maxVersion && $maxVersion < $actVersion)) {
						$options['error'] = true;
						$options['message'] = PatchModule::t('patch', 'Patch (version from {minVersion} to {maxVersion}) not allowed with this actual version ({actVersion})', 
								array('{actVersion}' => Yii::app()->getSession()->get('version', 0), '{minVersion}' => $version->getAttribute('min'), '{maxVersion}' => $version->getAttribute('max')));
					}
				}
				$description = $patch->getElementsByTagName('description')->item(0);
				if (!is_null($description)) {
					$patchDesc = $description->textContent;
				}
				else {
					$patchDesc = '';
				}
				
				$title = $patch->getElementsByTagName('title')->item(0)->textContent;
				$xmlMainClass = $patch->getElementsByTagName('mainclass')->item(0);
				// echo '<pre>' . print_r($mainClass, true) . '</pre>';
				if (!is_null($xmlMainClass)) {
					$mainClassName = $xmlMainClass->getAttribute('name');
					$sessionpatch['mainClassName'] = $mainClassName;
					Yii::import('application.runtime.patches.' . $name . '.*');
					Yii::import('application.runtime.patches.' . $name . '.models.*');

					$mainClass = new $mainClassName();
					$mainClass->patchName = $name;
					$mainClass->patchPath = $patchDir . DIRECTORY_SEPARATOR . $name;
					$mainClass->description = $patchDesc;
					$mainClass->stopOnError = 'true' === $xmlMainClass->getAttribute('stopOnError');
					try {
						$mainClass->init($patch);
						$mainClass->checkParams();
					}
					catch (Exception $e) {
						$options['error'] = true;
						$options['message'] = $e->getMessage();
					}
					$sessionpatch['mainClass'] = serialize($mainClass);
				}
			}
			$session['patch'] = $sessionpatch;
			
			//$options['patch'] = '<pre>' . print_r($sessionpatch, true) . '</pre>';
		}
		else {
			$options['error'] = true;
			$options['message'] = PatchModule::t('patch', 'Unknown patch with name "{patchName}"!', array('{patchName}' => $name));
		}
		if (is_null($key)) {
			$this->title = PatchModule::t('patch', 'Patch: {title}', array('{title}' => $title));
			$this->render('start', $options);
		}
		else {
			// TODO: handle preactions
			//$this->actionProcess($name, $key);
			$data = array('error' => false, 'message' => '', 'patchname' => $name);
			$data = array_merge($data, $mainClass->process(true));
			$sessionpatch['mainClass'] = serialize($mainClass);
			$session['patch'] = $sessionpatch;
				
			echo CJSON::encode($data);
		}
	}
	
	public function actionProcess($name, $key) {
		$session = Yii::app()->getSession();
		$sessionpatch = $session['patch'];
		$data = array('error' => false, 'message' => '', 'patchname' => $name);

		Yii::import('application.runtime.patches.' . $name . '.*');
		Yii::import('application.runtime.patches.' . $name . '.models.*');
		//Yii::import($sessionpatch['mainClassName'], true);
		
		$mainClass = unserialize($sessionpatch['mainClass']);
		//Yii::log('PathController::actionProcess ' . print_r($mainClass, true), 'profile', 'patch.PatchCustomer');

		$data = array_merge($data, $mainClass->process());
		Yii::log('PathController::actionProcess data ' . print_r($data, true), 'profile', 'patch.PatchCustomer');
		$sessionpatch['mainClass'] = serialize($mainClass);
		$session['patch'] = $sessionpatch;
		
		if (isset($data['totalvalue']) && 100 <= $data['totalvalue']) {
			unset($session['patch']);
		}
// 		$data['partvalue'] = round($data['partvalue'], 1);
// 		$data['totalvalue'] = round($data['totalvalue'], 1);

		echo CJSON::encode($data);
	}
	
	private function getVersionAsNumber($version) {
		if ('*' !== $version) {
			$parts = preg_split( '/(\.|_rc)/', $version); //explode('.', $version);
			$version = 0;
			for ($i=0; $i<4; $i++) {
				if (0 !== $version) {
					$version <<= 8;
				}
				if (isset($parts[$i])) {
					$version += $parts[$i];
				}
			}
		}
		return $version;
	}
}
