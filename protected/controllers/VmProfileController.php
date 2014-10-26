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
 * VmProfileController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class VmProfileController extends Controller
{
	public function beforeAction($action) {
		if ('getVmProfiles' != $action->id) {
			$retval = parent::beforeAction($action);
			if ($retval) {
				$this->activesubmenu = 'vm';
			}
			return $retval;
		}
		return true;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('update' == $action) {
			$this->submenu['vm']['items']['vmprofile']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Virtual Machine Profile Update Tooltip')),
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
				'actions'=>array('index', 'getVmProfiles', 'getCheckCopyGui', 'checkCopy'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'profile\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('create', 'getDefaults'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'profile\', COsbdUser::$RIGHT_ACTION_CREATE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('update', 'getDefaults'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'profile\', COsbdUser::$RIGHT_ACTION_EDIT, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('delete'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'profile\', COsbdUser::$RIGHT_ACTION_DELETE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('allow',
				'actions'=>array('uploadIso', 'uploadIsoPart', 'requestInfo'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'profile\', COsbdUser::$RIGHT_ACTION_MANAGE, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index', array('copyaction' => isset($_GET['copyaction']) ? $_GET['copyaction'] : null));
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
		$model = new VmProfileForm('create');
		if(isset($_POST['VmProfileForm']))
		{
			$parts = explode('°', $_POST['VmProfileForm']['path']);
			if ('default' != $parts[1]) {
				$model = new VmProfileForm('createOther');
			}
		}
		$this->performAjaxValidation($model);

		if(isset($_POST['VmProfileForm']))
		{
			$model->attributes = $_POST['VmProfileForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';

			$isodir = LdapStoragePoolDefinition::getPathByType('iso');
			$isosourcefile = $isodir . CPhpLibvirt::getInstance()->generateUUID() . '.iso';

			$copydata = array();
			$parts = explode('°', $_POST['VmProfileForm']['path']);
			if ('default' == $parts[1]) {
				$copydata = CPhpLibvirt::getInstance()->copyIsoFile(LdapStoragePoolDefinition::getPathByType('iso-choosable') . $model->isofile, $isosourcefile);

				// One of the default profiles is selected
				$result = CLdapRecord::model('LdapVmDefaults')->findByDn($_POST['VmProfileForm']['basis']);
				$result->setOverwrite(true);
				$result->sstClockOffset = $model->sstClockOffset;
				$result->sstMemory = $model->sstMemory;
				$result->sstOSArchitecture = $parts[2];
				$result->sstVCPU = $model->sstVCPU;
				$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
				$result->description = $model->description;
				if ('TBD_GUI' == $result->sstOnCrash) {
					$result->sstOnCrash = $result->sstOnCrashDefault;
				}
				if ('TBD_GUI' == $result->sstOnPowerOff) {
					$result->sstOnPowerOff = $result->sstOnPowerOffDefault;
				}
				if ('TBD_GUI' == $result->sstOnReboot) {
					$result->sstOnReboot = $result->sstOnRebootDefault;
				}
				// 'save' devices before
				$rdevices = $result->devices;
				$result->setDn(null);
				$result->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDefaults');
				$profilevm = new LdapVmFromProfile();
				$profilevm->attributes = $result->attributes;
				$profilevm->labeledURI = 'ldap:///' . $model->basis;
				//$profilevm->sstVirtualMachineType = 'profile';
				//$profilevm->sstVirtualMachineSubType = 'VM-Profile';
				$profilevm->removeAttribute(array('objectClass', 'member'));

				$server = CLdapServer::getInstance();
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($model->name);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (operating system name level).');
				$dn = 'ou=' . $model->name . ',ou=' . $parts[0] . ',ou=virtual machine profiles,ou=virtualization,ou=services';
				$server->add($dn, $data);
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($parts[2]);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (architecture level).');
				$dn = 'ou=' . $parts[2] . ',' . $dn;
				$server->add($dn, $data);
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($parts[3]);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (language level).');
				$dn = 'ou=' . $parts[3] . ',' . $dn;
				$server->add($dn, $data);
				$profilevm->setBranchDn($dn);
				$profilevm->save();
				$devices = new LdapVmDevice();
				//echo '<pre>' . print_r($result->devices, true) . '</pre>';
				$devices->attributes = $rdevices->attributes;
				$devices->setBranchDn($profilevm->dn);
				//echo '<pre>' . print_r($devices, true) . '</pre>';
				$devices->save();

				foreach($rdevices->disks as $rdisk) {
					$disk = new LdapVmDeviceDisk();
					$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
					$disk->setOverwrite(true);
					$disk->attributes = $rdisk->attributes;
					if (-1 == $disk->sstVolumeCapacity && 'disk' == $disk->sstDevice) {
						$disk->sstVolumeCapacity = $model->sstVolumeCapacity;
					}
					if ('TBD_GUI' == $disk->sstSourceFile && 'cdrom' == $disk->sstDevice) {
						$disk->sstSourceFile = $isosourcefile;
					}
					//echo '<pre>' . print_r($disk, true) . '</pre>';
					$disk->setBranchDn($devices->dn);
					$disk->save();
				}
				foreach($rdevices->interfaces as $rinterface) {
					$interface = new LdapVmDeviceInterface();
					$interface->attributes = $rinterface->attributes;
					$interface->setBranchDn($devices->dn);
					$interface->save();
				}
			}
			else {
				$result = CLdapRecord::model('LdapVmFromProfile')->findByDn($_POST['VmProfileForm']['basis']);
				$result->setOverwrite(true);
				$result->sstClockOffset = $model->sstClockOffset;
				$result->sstMemory = $model->sstMemory;
				$result->sstOSArchitecture = $parts[2];
				$result->sstVCPU = $model->sstVCPU;
				$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
				$result->description = $model->description;
				if ('TBD_GUI' == $result->sstOnCrash) {
					$result->sstOnCrash = $result->sstOnCrashDefault;
				}
				if ('TBD_GUI' == $result->sstOnPowerOff) {
					$result->sstOnPowerOff = $result->sstOnPowerOffDefault;
				}
				if ('TBD_GUI' == $result->sstOnReboot) {
					$result->sstOnReboot = $result->sstOnRebootDefault;
				}
				// 'save' devices before
				$rdevices = $result->devices;
				$result->setDn(null);
				/* Create a copy to be sure that we will write a new record */
				$profilevm = new LdapVmFromProfile();
				/* Don't change the labeledURI; must refer to a default Profile */
				$profilevm->attributes = $result->attributes;
				/* Delete all objectclasses and let the LdapVMFromProfile set them */
				$profilevm->removeAttribute(array('objectClass', 'member'));

				$server = CLdapServer::getInstance();
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($model->name);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (operating system name level).');
				$dn = 'ou=' . $model->name . ',ou=' . $parts[0] . ',ou=virtual machine profiles,ou=virtualization,ou=services';
				$server->add($dn, $data);
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($parts[2]);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (architecture level).');
				$dn = 'ou=' . $parts[2] . ',' . $dn;
				$server->add($dn, $data);
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit');
				$data['ou'] = array($parts[3]);
				$data['description'] = array('This is the ' . $model->name . ' VM-Profile subtree (language level).');
				$dn = 'ou=' . $parts[3] . ',' . $dn;
				$server->add($dn, $data);
				$profilevm->setBranchDn($dn);
				$profilevm->save();
				$devices = new LdapVmDevice();
				//echo '<pre>' . print_r($result->devices, true) . '</pre>';
				$devices->attributes = $rdevices->attributes;
				$devices->setBranchDn($profilevm->dn);
				//echo '<pre>' . print_r($devices, true) . '</pre>';
				$devices->save();

				foreach($rdevices->disks as $rdisk) {
					$disk = new LdapVmDeviceDisk();
					//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
					$disk->setOverwrite(true);
					$disk->attributes = $rdisk->attributes;
					if ('disk' == $disk->sstDevice) {
						$disk->sstVolumeCapacity = $model->sstVolumeCapacity;
					}
					if ('cdrom' == $disk->sstDevice) {
						$copydata = CPhpLibvirt::getInstance()->copyIsoFile($disk->sstSourceFile, $isosourcefile);

						$disk->sstSourceFile = $isosourcefile;
					}
					//echo '<pre>' . print_r($disk, true) . '</pre>';
					$disk->setBranchDn($devices->dn);
					$disk->save();
				}
				foreach($rdevices->interfaces as $rinterface) {
					$interface = new LdapVmDeviceInterface();
					$interface->attributes = $rinterface->attributes;
					$interface->setBranchDn($devices->dn);
					$interface->save();
				}
			}
			if ($model->upstatus) {
				echo '<html><head><title></title><script type="text/javascript">parent.finished();</script></head><body><pre>finished' . print_r($_POST, true) . print_r($profile, true) . '</pre></body></html>';
			}
			else {
				$this->redirect(array('index', 'copyaction' => $copydata['pid']));
			}
		}
		else {
			$isofiles = array();
			$path = LdapStoragePoolDefinition::getPathByType('iso-choosable');
			if (is_dir($path)) {
				if ($dh = opendir($path)) {
					while (($file = readdir($dh)) !== false) {
						if (is_file($path . $file)) {
							$isofiles[$file] = $file;
						}
					}
					closedir($dh);
				}
			}

			$profiles = array();
			$node = CLdapRecord::model('LdapSubTree');
			$node->setBranchDn('ou=virtual machine profiles,ou=virtualization,ou=services');
			$result = $node->findSubTree(array());

			$this->render('create',array(
				'model' => $model,
				'isofiles' => $isofiles,
				'profiles' => $this->getProfilesFromSubTree($result),
				'defaults' => null,
			));
		}
	}

	public function actionDelete() {
		if ('del' == $_POST['oper']) {
			$dn = urldecode(Yii::app()->getRequest()->getParam('dn'));
			$vmprofile = CLdapRecord::model('LdapVmProfile')->findByDn($dn);
			if (!is_null($vmprofile)) {
				// delete sstDisk=hdb->sstSourceFile
				$vm = $vmprofile->vm;
				//echo '<pre>' . print_r($vm, true) . '</pre>';
				$hdb = $vmprofile->vm->devices->getDiskByName('hdb');
				$libvirt = CPhpLibvirt::getInstance();
				// not necessary to check if deletion was ok
				$libvirt->deleteIsoFile($hdb->sstSourceFile);
				// delete VM Profile
				$vmprofile->delete(true);
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Vm Profile\'' . $_POST['dn'] . '\' not found!'));
			}
		}
	}

	public function actionUpdate() {
		$model = new VmProfileForm('update');

		$this->performAjaxValidation($model);

		if(isset($_POST['VmProfileForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['VmProfileForm'];

			$result = CLdapRecord::model('LdapVmFromProfile')->findByDn($_POST['VmProfileForm']['dn']);
			$result->setOverwrite(true);
			$result->sstClockOffset = $model->sstClockOffset;
			$result->sstMemory = $model->sstMemory;
			$result->sstVCPU = $model->sstVCPU;
			$result->description = $model->description;
			$result->save();

			$rdevices = $result->devices;
			foreach($rdevices->disks as $rdisk) {
				if ('disk' == $rdisk->sstDevice) {
					$rdisk->setOverwrite(true);
					$rdisk->sstVolumeCapacity = $model->sstVolumeCapacity;
					$rdisk->save();
				}
			}
			$this->redirect(array('index'));
		}
		else {
			if(isset($_GET['dn'])) {
				$profile = CLdapRecord::model('LdapVmProfile')->findbyDn($_GET['dn']);
				$profile->vmsubtree = $_GET['vm'];
			}
			if($profile === null)
				throw new CHttpException(404,'The requested page does not exist.');

			$vm = $profile->vm;
			$defaults = $vm->defaults;

			$model->dn = $vm->dn;
			$model->name = $profile->ou;
			$model->description = $profile->description;
			$model->sstClockOffset = $vm->sstClockOffset;
			$model->sstMemory = $vm->sstMemory;
			$model->sstVCPU = $vm->sstVCPU;
			$result = $vm->devices->getDiskByName('vda');
			$model->sstVolumeCapacity = $result->sstVolumeCapacity;

			$this->render('update',array(
				'model' => $model,
				'profiles' => null,
				'defaults' => $defaults,
			));
		}
	}

	public function actionUploadIso() {
		$model = new VmIsoUploadForm();

		if(isset($_POST['ajax']) && $_POST['ajax']==='isoupload-form')
		{
//			echo '<pre>' . print_r($_FILES, true) . '</pre>';
			if (0 == $_FILES['VmIsoUploadForm']['error']['isofile']) {
				$POST['VmIsoUploadForm']['isofile'] = 'asdf';
			}
//			echo '<pre>' . print_r($_POST, true) . '</pre>';
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		if(isset($_POST['VmIsoUploadForm']))
		{
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			/*
			 * Uploadable Version
			 */
			$model->attributes = $_POST['VmIsoUploadForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';
			$ufile = CUploadedFile::getInstance($model, 'isofile');
			if (!is_null($ufile)) {
				if (0 == $ufile->error) {
					$isodir = LdapStoragePoolDefinition::getPathByType('iso-choosable');
					if ('' !== $model->name) {
						$name = $model->name;
					}
					else {
						$name = $ufile->getName();
					}
					if (substr($name, -4) !== '.iso') {
						$name .= '.iso';
					}
					$isosourcefile = $isodir . $name;
					$ufile->saveAs($isosourcefile);
					//echo '<pre>' . print_r($ufile, true) . "\n"  .'</pre>';
				}
				else {
					echo '<html><head><title></title><script type="text/javascript">parent.error("' .
						Yii::t('vmprofile','Upload error ({errno}).', array('{errno}' => $ufile->error)) .
						'");</script></head><body><pre>finished' . print_r($_POST, true) . print_r($profile, true) . '</pre></body></html>';
					return;
				}
			}
			else {
				echo '<html><head><title></title><script type="text/javascript">parent.error(' .
				Yii::t('vmprofile','No file for upload!') .
					');</script></head><body><pre>finished' . print_r($_POST, true) . print_r($profile, true) . '</pre></body></html>';
				return;
			}
			/*
			 * Uploadable Version END
			 */
			if ($model->upstatus) {
				echo '<html><head><title></title><script type="text/javascript">parent.finished(\'' . $this->getHumanSize($ufile->getSize()) . '\');</script></head><body><pre>finished' . print_r($_POST, true) . print_r($profile, true) . '</pre></body></html>';
			}
			else {
				$this->redirect(array('index'));
			}
		}
		else {
			/*
			 * Uploadable Version
			 */
			$templateini =  ini_get("uploadprogress.file.filename_template");
			$testid = "thisisjustatest";
			$template = sprintf($templateini,$testid);
			$upstatus = true;
			if ($template && $template != $templateini && touch($template) && file_exists($template)) {
				//print '('.$templateini.' is writable. The realpath is ' . str_replace($testid,"%s",realpath($template)) .')';
				unlink($template);
			}
			else {
				$upstatus = false;
				$model->addError('isofile', Yii::t('vmprofile', 'Extension uploadprogress not installed!'));
			}
			$model->upstatus = $upstatus;
			/*
			 * Uploadable Version END
			 */

			$this->render('uploadIso',array(
				'model' => $model,
				'upstatus' => $upstatus,
			));
		}
	}

	public function actionUploadIsoText()
	{
		$this->render('uploadIso', array(
		));
	}

	public function actionUploadIsoPart()
	{
		// HTTP headers for no cache etc
		header('Content-type: text/plain; charset=UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// Settings
		//$targetDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "plupload";
		//$targetDir = sys_get_temp_dir() . "plupload";
		$targetDir = Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads';
		$cleanupTargetDir = false; // Remove old files
		$maxFileAge = 60 * 60; // Temp file age in seconds

		// 5 minutes execution time
		@set_time_limit(5 * 60);
		// usleep(5000);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
		$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._\s]+/', '', $fileName);

		$fh = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName . '.log', 'a');
		if ($fh) {
			fwrite($fh, "$chunk of $chunks; " . date('c') . "\n");
			fclose($fh);
		}

		// Create target dir
		if (!file_exists($targetDir))
		@mkdir($targetDir);

		// Remove old temp files
		if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
			if ($cleanupTargetDir) {
				while (($file = readdir($dir)) !== false) {
					$filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

					// Remove temp files if they are older than the max age
					if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
					@unlink($filePath);
				}

				closedir($dir);
			}
		}
		else {
			throw new CHttpException (500, Yii::t('app', "Can't open temporary directory."));
		}
		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
		$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
		}

		if (isset($_SERVER["CONTENT_TYPE"])) {
		$contentType = $_SERVER["CONTENT_TYPE"];
		}

		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in) {
						while ($buff = fread($in, 4096)) {
							fwrite($out, $buff);
						}
					}
					else {
						throw new CHttpException (500, Yii::t('app', "Can't open input stream."));
					}

					fclose($out);
					//@unlink($_FILES['file']['tmp_name']);
				}
				else {
					throw new CHttpException (500, Yii::t('app', "Can't open output stream."));
				}
			}
			else {
				throw new CHttpException (500, Yii::t('app', "Can't move uploaded file."));
			}
		}
		else {
			// Open temp file
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}
				}
				else {
					throw new CHttpException (500, Yii::t('app', "Can't open input stream."));
				}

				fclose($out);
			}
			else {
				throw new CHttpException (500, Yii::t('app', "Can't open output stream."));
			}
		}

		// After last chunk is received, process the file
		$ret = array('result' => '1');
		if (intval($chunk) + 1 >= intval($chunks)) {
			$originalname = $fileName;
			if (isset($_SERVER['HTTP_CONTENT_DISPOSITION'])) {
				$arr = array();
				preg_match('@^attachment; filename="([^"]+)"@',$_SERVER['HTTP_CONTENT_DISPOSITION'],$arr);
				if (isset($arr[1])) {
					$originalname = $arr[1];
				}
			}

			$original_name=  $_FILES['file']['name'];
			// **********************************************************************************************
			// Do whatever you need with the uploaded file, which has $originalname as the original file name
			// and is located at $targetDir . DIRECTORY_SEPARATOR . $fileName
			// **********************************************************************************************

			$dest = LdapStoragePoolDefinition::getPathByType('iso-choosable') . $original_name;
			rename($targetDir . DIRECTORY_SEPARATOR . $fileName, $dest);
		}

		// Return response
		die(json_encode($ret));
	}

	public function actionGetCheckCopyGui() {
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmprofile', 'Check ISO Copy') . '</span></div>';
		?>
<div style="text-align: center;">
	<img id="running" src="<?php echo Yii::app()->baseUrl;?>/images/loading.gif" alt="" /><br />
</div>
<div id="errorAssignment" class="ui-state-error ui-corner-all"
	style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
	<p style="margin: 0.3em 0pt;">
		<span style="float: left; margin-right: 0.3em;"
			class="ui-icon ui-icon-alert"></span> <span id="errorMsg"> <?=Yii::t('vmprofile', 'Copy of ISO file still running!'); ?>
		</span>
	</p>
</div>
<div id="infoAssignment" class="ui-state-highlight ui-corner-all"
	style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
	<p style="margin: 0.3em 0pt;">
		<span style="float: left; margin-right: 0.3em;"
			class="ui-icon ui-icon-info"></span><span id="infoMsg"></span>
	</p>
</div>
		<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionCheckCopy() {
		if (CPhpLibvirt::getInstance()->checkPid($_GET['pid'])) {
			$json = array('err' => true, 'msg' => Yii::t('vmprofile', 'Still copying!'));
		}
		else {
			$json = array('err' => false, 'msg' => Yii::t('vmprofile', 'Finished!'));
		}
		$this->sendJsonAnswer($json);
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vmprofile-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionRequestInfo() {
		$this->disableWebLogRoutes();
		$info = array();
		if(isset($_GET['upid'])) {
			if (function_exists("uploadprogress_get_info")) {
				$info = uploadprogress_get_info($_GET['upid']);
			} else {
				$info['found'] = false;
			}
		}
		else {
			$info['get'] = false;
		}
		$total = (!isset($info['bytes_total']) ? '???' : $this->getHumanSize($info['bytes_total'], 'MB'));
		$uploaded = (!isset($info['bytes_uploaded']) ? '???' : $this->getHumanSize($info['bytes_uploaded'], 'MB'));
		$percent = (!isset($info['bytes_total']) ? 0 : floor($info['bytes_uploaded'] / $info['bytes_total'] * 100));
		$estimated = (!isset($info['est_sec']) ? '???' : $info['est_sec']);
//		echo "<html><head><title></title><script type=\"text/javascript\">parent.update('$total','$uploaded',$percent,'$estimated');</script></head><body></body></html>";
		Yii::log('UploadProgress: ' . print_r($info, true), CLogger::LEVEL_WARNING, 'uploadprogress');
		$answer = array(
			'total' => $total,
			'uploaded' => $uploaded,
			'percent' => (int) $percent,
			'estimated' => $estimated,
			'info' => $info,
			'upid' => $_GET['upid'],
		);
		$s = CJSON::encode($answer);
		header('Content-Type: application/json');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetDefaults() {
		$defaults = array();
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$defaults['path'] = $_GET['p'];
			if ('sstVirtualMachine=default' == substr($_GET['dn'], 0, strlen('sstVirtualMachine=default'))) {
				$defaults['type'] = 'default';
				$defaults['name'] = '';
				$defaults['description'] = '';
				$result = CLdapRecord::model('LdapVmDefaults')->findByDn($_GET['dn']);
				$defaults['memorydefault'] = $result->sstMemoryDefault;
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpudefault'] = $result->sstVCPUDefault;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockdefault'] = $result->sstClockOffsetDefault;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				//echo '<pre>' . print_r($result->device->disks, true) . '</pre>';
				//$result = CLdapRecord::model('LdapVmDeviceDisk')->findByDn('sstDisk=hdb,ou=devices,' . $_GET['dn']);
				$result = $result->devices->getDiskByName('vda');
				//echo '<pre>' . print_r($result, true) . '</pre>';
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacityDefault;
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
				//echo '<pre>' . print_r($defaults, true) . '</pre>';
			}
			else {
				$defaults['type'] = 'other';
				$parts = explode('°', $_GET['p']);
				$defaults['name'] = $parts[1];
				$vm = CLdapRecord::model('LdapVmFromProfile')->findByDn($_GET['dn']);
				$defaults['description'] = $vm->description;
				$defaults['memorydefault'] = $vm->sstMemory;
				$defaults['cpudefault'] = $vm->sstVCPU;
				$defaults['clockdefault'] = $vm->sstClockOffset;

				$result = $vm->devices->getDiskByName('vda');
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacity;

				$result = CLdapRecord::model('LdapVmDefaults')->findByDn(substr($vm->labeledURI, 8));
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				$result = $result->devices->getDiskByName('vda');
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
			}
		}
		$s = CJSON::encode($defaults);
		header('Content-Type: application/json');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVMProfiles() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];
/*
		$criteria = array('attr'=>array());
		if (isset($_GET['name'])) {
			$criteria['attr']['ou'] = $_GET['name'];
		}
		if ($sidx != '')
		{
			$criteria['sort'] = $sidx . '.' . $sord;
		}
*/
		$items = array();
		if(Yii::app()->user->hasRight('profile', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL)) {
			$subtree = CLdapRecord::model('LdapSubTree');
			$subtree->setBranchDn('ou=virtual machine profiles,ou=virtualization,ou=services');
			$result = $subtree->findSubTree(array());

			if (!is_null($result->children)) {
				foreach($result->children as $child) {
					$type = $child->ou;
					foreach($child->children as $child2) {
						$name = $child2->ou;
						if ('default' == $name) {
							continue;
						}
						if (isset($_GET['name']) && 0 == preg_match('/.*' . $_GET['name'] . '.*/', $name)) {
							continue;
						}
	
						foreach($child2->children as $child3) {
							$arch = $child3->ou;
							foreach($child3->children as $child4) {
								$lang = $child4->ou;
								foreach($child4->children as $child5) {
									$desc = $child5->description;
									if (isset($_GET['architecture']) && 0 == preg_match('/.*' . $_GET['architecture'] . '.*/', $arch) && 0 == preg_match('/.*' . $_GET['architecture'] . '.*/', $type)) {
										continue;
									}
									$item = array('dn' =>$child2->dn , 'type'=>$type, 'name'=>$name,
												'arch'=>$arch, 'lang'=>$lang, 'desc'=>$desc,
												'vmsubtree'=>substr($child5->dn, 0, strpos($child5->dn, $child2->dn)-1));
									$items[] = $item;
								}
							}
						}
					}
				}
			}
		}
		
		$count = count($items);

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

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  '<rows>';
		$s .= '<page>' . $page . '</page>';
		$s .= '<total>' . $total_pages . '</total>';
		$s .= '<records>' . $count . '</records>';

		// be sure to put text data in CDATA
		for ($i = $start; $i<$start+$limit && isset($items[$i]); $i++) {
			$item = $items[$i];
			//	'colNames'=>array('No.', 'DN', 'OrigName', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . ($i+1) . '">';
			$s .= '<cell>'. ($i+1) ."</cell>\n";
			$s .= '<cell>'. $item['dn'] ."</cell>\n";
			$s .= '<cell>'. $item['vmsubtree'] ."</cell>\n";
			$s .= '<cell>'. $item['name'] ."</cell>\n";
			$s .= '<cell>'. $item['type'] . ' / ' . $item['arch'] ."</cell>\n";
			$s .= '<cell>'. $item['lang'] ."</cell>\n";
			$s .= '<cell>'. $item['desc'] . "</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	/* Private functions */
	private static $_levels = array('ou', 'sstOSArchitectureValues', 'sstLanguageValues');

	private function getProfilesFromSubTree($result, $i=0) {
//		if (!isset(self::$_levels[$i])) {
//			return null;
//		}
//		$attr = self::$_levels[$i];
//		$retval = array();
//		echo '<br/>' . substr('-----', 0, $i) . $i . ': ' . $result->dn . '<br/>';
//		$children = $result->children;
//		if (!is_array($children)) {
//			$children = array($children);
//		}
//		foreach($children as $child) {
//
//			echo '(' . $child->dn . ', ' . $child->$attr . ')';
//			if (isset($child->ou)) {
//				//echo substr('+++++', 0, $i+1) . ($i+1) . ': ' . $child->ou . '<br/>';
//				//$retval[$child->dn] = array('name' => $child->ou);
//				if (isset($child->children)) {
//					$retval[$child->dn]['children'] = $this->getProfilesFromSubTree($child, $i+1);
//				}
//			//}
//		}
//		return $retval;

		$retval = array();
		foreach($result->children as $child) {
			//echo $child->dn . '  (' . count($child->children) . ')<br/>';
			$retval[$child->ou] = array('dn' => $child->dn, 'children' => array());
			if (!is_null($child->children)) {
				$childs = $child->children;
				if (!is_array($childs)) {
					$childs = array($childs);
				}
				foreach($childs as $child2) {
					//echo $child2->dn . '<br/>';
					$retval[$child->ou]['children'][$child2->ou] = array('dn' => $child2->dn, 'children' => array());
					if (!is_null($child2->children)) {
						$childs2 = $child2->children;
						if (!is_array($childs2)) {
							$childs2 = array($childs2);
						}
						foreach($childs2 as $child3) {
							//echo $child3->dn . '<br/>';
							if ($child3->hasObjectClass('sstVirtualizationProfileArchitectureDefaults')) {
								$archs = $child3->sstOSArchitectureValues;
								if (!is_array($archs)) {
									$archs = array($archs);
								}
								foreach($archs as $arch) {
									//echo $arch . '<br/>';
									$retval[$child->ou]['children'][$child2->ou]['children'][$arch] = array('dn' => $child3->dn, 'children' => array());
									$childs3 = $child3->children;
									if (!is_array($childs3)) {
										$childs3 = array($childs3);
									}
									foreach($childs3 as $child4) {
										$langs = $child4->sstLanguageValues;
										if (!is_array($langs)) {
											$langs = array($langs);
										}
										foreach($langs as $lang) {
											//echo $lang . '<br/>';
											if (!is_null($child4->children)) {
												$dn = $child4->children[0]->dn;
											}
											else {
												$dn = $child4->dn;
											}
											$retval[$child->ou]['children'][$child2->ou]['children'][$arch]['children'][$lang] = array('dn' => $dn);
										}
									}
								}
							}
							else {
								$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou] = array('dn' => $child3->dn, 'children' => array());
								$childs3 = $child3->children;
								foreach($childs3 as $child4) {
									if (!is_null($child4->children)) {
										$dn = $child4->children[0]->dn;
									}
									else {
										$dn = $child4->dn;
									}
									$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou]['children'][$child4->ou] = array('dn' => $dn);
								}
							}
						}
					}
				}
			}
		}
		return $retval;
	}
}