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


class ConfigurationController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'config';
			
			if ('backup' === $action->id) {
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
		        'actions'=>array('global', 'backup'),
				'users'=>array('@'),
				'expression'=>'Yii::app()->user->hasRight(\'configuration\', COsbdUser::$RIGHT_ACTION_ACCESS, COsbdUser::$RIGHT_VALUE_ALL)'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index');
	}

	public function actionGlobal() {
		$model = new ConfigurationGlobalForm('update');

		$this->performAjaxValidationGlobal($model);

		$globalConfig = LdapConfigurationSettings::model()->findByDn('ou=settings,ou=configuration,ou=virtualization,ou=services');
		if(isset($_POST['ConfigurationGlobalForm'])) {
			//echo '<pre>' . print_r($_POST, true) . '</pre>';
			$model->attributes = $_POST['ConfigurationGlobalForm'];
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
			$spiceSetting = $globalConfig->getSpiceSetting();
			$model->minSpicePort = $spiceSetting->sstSpicePortMin;
			$model->maxSpicePort = $spiceSetting->sstSpicePortMax;
			$this->render('global', array(
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
			$globalBackup->sstVirtualizationVirtualMachineForceStart = $model->sstVirtualizationVirtualMachineForceStart;
			
			list($hour, $minute) = explode(':', $model->cronTime);
			$globalBackup->sstCronMinute = (int) $minute;
			$globalBackup->sstCronHour = (int) $hour;
			if ('TRUE' == $model->everyDay) {
				$globalBackup->sstCronDayOfWeek = '*';
			}
			else {
				$globalBackup->sstCronDayOfWeek = implode(',', $model->sstCronDayOfWeek);
			}
			$globalBackup->sstCronActive = $model->sstCronActive;
			$globalBackup->save(false, array('sstBackupNumberOfIterations', 'sstVirtualizationVirtualMachineForceStart', 'sstCronMinute', 'sstCronHour', 'sstCronDayOfWeek', 'sstCronActive'));
			//echo '<pre>' . print_r($globalBackup, true) . '</pre>';
		}
		{
			$model->sstBackupNumberOfIterations = $globalBackup->sstBackupNumberOfIterations;
			$model->sstVirtualizationVirtualMachineForceStart = $globalBackup->sstVirtualizationVirtualMachineForceStart;
				
			$model->sstCronMinute = $globalBackup->sstCronMinute;
			$model->sstCronHour = $globalBackup->sstCronHour;
			$model->sstCronDayOfWeek = explode(',', $globalBackup->sstCronDayOfWeek);
			if ('*' == $globalBackup->sstCronDayOfWeek) {
				$model->everyDay = 'TRUE';
			}
			else {
				$model->everyDay = 'FALSE';
			}
			$model->sstCronActive = $globalBackup->sstCronActive;
			$model->cronTime = $model->sstCronHour . ':' . $model->sstCronMinute;
			
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
	protected function performAjaxValidationGlobal($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='configurationglobal-form')
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