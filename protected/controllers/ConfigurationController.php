<?php
/*
 * Copyright (C) 2013 FOSS-Group
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


class ConfigurationController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'config';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
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
		        'actions'=>array('general', 'backup'),
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


	public function actionGeneral() {
		$model = new ConfigurationGeneralForm('update');

		$this->performAjaxValidationGeneral($model);

		$globalConfig = LdapConfigurationSettings::model()->findByDn('ou=settings,ou=configuration,ou=virtualization,ou=services');
		if(isset($_POST['ConfigurationGeneralForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['ConfigurationGeneralForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';
			$setting = $globalConfig->getSoundSetting();
			$setting->setOverwrite(true);
			$setting->sstAllowSound = $model->allowSound ? 'TRUE' : 'FALSE';
			$setting->save();
			$setting = $globalConfig->getUsbSetting();
			$setting->setOverwrite(true);
			$setting->sstAllowUsb = $model->allowUsb ? 'TRUE' : 'FALSE';
			$setting->save();
		}
		{
			$model->allowSound = $globalConfig->isSoundAllowed();
			$model->allowUsb = $globalConfig->isUsbAllowed();
			$this->render('general', array(
				'model' => $model,
				'submittext'=>Yii::t('configuration','Save')
			));
		}
	}
	
	public function actionBackup() {
		$model = new ConfigurationBackupForm('update');

		$this->performAjaxValidationBackup($model);

		$globalBackup = LdapConfigurationBackup::model()->findByDn('ou=backup,ou=configuration,ou=virtualization,ou=services');
		if(isset($_POST['ConfigurationBackupForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['ConfigurationBackupForm'];
			//echo '<pre>' . print_r($model, true) . '</pre>';
			$globalBackup->setOverwrite(true);
			$globalBackup->sstBackupNumberOfIterations = $model->sstBackupNumberOfIterations;
			$globalBackup->sstBackupRootDirectory = $model->sstBackupRootDirectory;
			$globalBackup->sstBackupRetainDirectory = $model->sstBackupRetainDirectory;
			$globalBackup->sstVirtualizationVirtualMachineForceStart = $model->sstVirtualizationVirtualMachineForceStart;
			$globalBackup->sstVirtualizationBandwidthMerge = $model->sstVirtualizationBandwidthMerge;
			$globalBackup->sstRestoreVMWithoutState = $model->sstRestoreVMWithoutState;
			$globalBackup->sstBackupExcludeFromBackup = $model->sstBackupExcludeFromBackup;
			$globalBackup->sstBackupRamDiskLocation = $model->sstBackupRamDiskLocation;
			$globalBackup->sstVirtualizationVirtualMachineSequenceStop = $model->sstVirtualizationVirtualMachineSequenceStop;
			$globalBackup->sstVirtualizationVirtualMachineSequenceStart = $model->sstVirtualizationVirtualMachineSequenceStart;
			$globalBackup->sstVirtualizationDiskImageFormat = $model->sstVirtualizationDiskImageFormat;
			$globalBackup->sstVirtualizationDiskImageOwner = $model->sstVirtualizationDiskImageOwner;
			$globalBackup->sstVirtualizationDiskImageGroup = $model->sstVirtualizationDiskImageGroup;
			$globalBackup->sstVirtualizationDiskImagePermission = $model->sstVirtualizationDiskImagePermission;
			$globalBackup->sstVirtualizationDiskImageDirectoryOwner = $model->sstVirtualizationDiskImageDirectoryOwner;
			$globalBackup->sstVirtualizationDiskImageDirectoryGroup = $model->sstVirtualizationDiskImageDirectoryGroup;
			$globalBackup->sstVirtualizationDiskImageDirectoryPermission = $model->sstVirtualizationDiskImageDirectoryPermission;
				
			$globalBackup->sstCronMinute = $model->sstCronMinute;
			$globalBackup->sstCronHour = $model->sstCronHour;
			$globalBackup->sstCronDay = $model->sstCronDay;
			$globalBackup->sstCronMonth = $model->sstCronMonth;
			$globalBackup->sstCronDayOfWeek = $model->sstCronDayOfWeek;
			$globalBackup->sstCronActive = $model->sstCronActive;
			$globalBackup->save();
			Yii::app()->end();
		}
		{
			$model->sstBackupNumberOfIterations = $globalBackup->sstBackupNumberOfIterations;
			$model->sstBackupRootDirectory = $globalBackup->sstBackupRootDirectory;
			$model->sstBackupRetainDirectory = $globalBackup->sstBackupRetainDirectory;
			$model->sstVirtualizationVirtualMachineForceStart = $globalBackup->sstVirtualizationVirtualMachineForceStart;
			$model->sstVirtualizationBandwidthMerge = $globalBackup->sstVirtualizationBandwidthMerge;
			$model->sstRestoreVMWithoutState = $globalBackup->sstRestoreVMWithoutState;
			$model->sstBackupExcludeFromBackup = $globalBackup->sstBackupExcludeFromBackup;
			$model->sstBackupRamDiskLocation = $globalBackup->sstBackupRamDiskLocation;
			$model->sstVirtualizationVirtualMachineSequenceStop = $globalBackup->sstVirtualizationVirtualMachineSequenceStop;
			$model->sstVirtualizationVirtualMachineSequenceStart = $globalBackup->sstVirtualizationVirtualMachineSequenceStart;
			$model->sstVirtualizationDiskImageFormat = $globalBackup->sstVirtualizationDiskImageFormat;
			$model->sstVirtualizationDiskImageOwner = $globalBackup->sstVirtualizationDiskImageOwner;
			$model->sstVirtualizationDiskImageGroup = $globalBackup->sstVirtualizationDiskImageGroup;
			$model->sstVirtualizationDiskImagePermission = $globalBackup->sstVirtualizationDiskImagePermission;
			$model->sstVirtualizationDiskImageDirectoryOwner = $globalBackup->sstVirtualizationDiskImageDirectoryOwner;
			$model->sstVirtualizationDiskImageDirectoryGroup = $globalBackup->sstVirtualizationDiskImageDirectoryGroup;
			$model->sstVirtualizationDiskImageDirectoryPermission = $globalBackup->sstVirtualizationDiskImageDirectoryPermission;
				
			$model->sstCronMinute = $globalBackup->sstCronMinute;
			$model->sstCronHour = $globalBackup->sstCronHour;
			$model->sstCronDay = $globalBackup->sstCronDay;
			$model->sstCronMonth = $globalBackup->sstCronMonth;
			$model->sstCronDayOfWeek = $globalBackup->sstCronDayOfWeek;
			$model->sstCronActive = $globalBackup->sstCronActive;
			
			$this->render('backup', array(
				'model' => $model,
				'submittext'=>Yii::t('configuration','Save')
			));
		}
	}
	
	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidationGeneral($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='configurationgeneral-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	
	protected function performAjaxValidationBackup($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='configurationbackup-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}