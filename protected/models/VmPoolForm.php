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
 * Licensed under the EUPL, Version 1.1 or – as soon they
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

class VmPoolForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $displayName;
	public $description;
	public $storagepool;
	public $nodes;
	public $range = null;
	public $brokerMin = -1;
	public $brokerMax = -1;
	public $brokerPreStart = -1;
	public $brokerPreStartInterval = -1;
	public $type;
	public $poolSound;
	public $allowSound;
	public $poolUsb;
	public $allowUsb;
	
	public $poolBackupActive = '';
	public $sstBackupNumberOfIterations = '';
	public $sstVirtualizationVirtualMachineForceStart = '';
	public $sstNumberOfScreens = 1;
	
	public $poolCronActive = '';
	public $sstCronMinute;
	public $sstCronHour;
	public $sstCronDayOfWeek;
	//public $sstCronActive;
	public $cronTime;
	public $everyDay;
	
	public $poolShutdown = false;			// show GUI part or not
	public $poolShutdownActive;
	public $poolShutdownMinute;
	public $poolShutdownHour;
	public $poolShutdownDayOfWeek;
	public $poolShutdownTime;
	public $poolShutdownEveryDay;
	
	public function rules()
	{
		Yii::log('Scenario: ' . $this->getScenario(), 'profile', 'VmPoolForm.rules');
		if ('update' === $this->getScenario()) {
			$required = array('dn', 'storagepool', 'displayName', 'description', 'nodes', 'range', 'sstNumberOfScreens', 'poolBackupActive');
			$safe = array('brokerMin', 'brokerMax', 'brokerPreStart', 'brokerPreStartInterval', 'nodes', 'range', 'poolSound', 'allowSound', 'poolUsb', 'allowUsb', 
					'sstBackupNumberOfIterations', 'sstVirtualizationVirtualMachineForceStart', 'poolCronActive', 'sstCronMinute', 'sstCronHour', 'sstCronDayOfWeek', 'cronTime', 'everyDay', 
					'poolShutdown', 'poolShutdownActive', 'poolShutdownMinute', 'poolShutdownHour', 'poolShutdownDayOfWeek', 'poolShutdownTime', 'poolShutdownEveryDay');
			Yii::log('required: ' . print_r($required, true), 'profile', 'VmPoolForm.rules');
			//Yii::log('safe: ' . print_r($safe, true), 'profile', 'VmPoolForm.rules');
			Yii::log('poolBackupActive: ' . $this->poolBackupActive, 'profile', 'VmPoolForm.rules');
			if ('TRUE' === $this->poolBackupActive) {
				$required[] = 'sstBackupNumberOfIterations';
				$required[] = 'sstVirtualizationVirtualMachineForceStart';
				unset($safe[array_search('sstBackupNumberOfIterations', $safe)]);
				unset($safe[array_search('sstVirtualizationVirtualMachineForceStart', $safe)]);
			}
			if ('TRUE' === $this->poolCronActive) {
				$required[] = 'cronTime';
				$required[] = 'everyDay';
				unset($safe[array_search('cronTime', $safe)]);
				unset($safe[array_search('everyDay', $safe)]);
				if ('FALSE' === $this->everyDay) {
					$required[] = 'sstCronDayOfWeek';
					unset($safe[array_search('sstCronDayOfWeek', $safe)]);
				}
			}
			Yii::log('required: ' . print_r($required, true), 'profile', 'VmPoolForm.rules');
			Yii::log('safe: ' . print_r($safe, true), 'profile', 'VmPoolForm.rules');
			return array(
					array(implode(',', $required), 'required', 'on' => 'update'),
					array('type', 'safe', 'on' => 'update'),
					array(implode(',', $safe), 'safe'),
			);
		}
		else {
			return array(
				array('storagepool, displayName, description, nodes, range, type, sstNumberOfScreens', 'required', 'on' => 'create'),
				array('dn', 'safe', 'on' => 'create'),
				array('dn, storagepool, displayName, description, nodes, range, sstNumberOfScreens', 'required', 'on' => 'update'),
				array('type', 'safe', 'on' => 'update'),
				array('brokerMin, brokerMax, brokerPreStart, brokerPreStartInterval, nodes, range, poolSound, allowSound, poolUsb, allowUsb, poolBackupActive, sstBackupNumberOfIterations, sstVirtualizationVirtualMachineForceStart, poolCronActive, sstCronMinute, sstCronHour, sstCronDayOfWeek, cronTime, everyDay, poolShutdown, poolShutdownActive, poolShutdownMinute, poolShutdownHour, poolShutdownDayOfWeek, poolShutdownTime, poolShutdownEveryDay', 'safe'),
			);
				
		}
	}

	public function validate($attributes=null, $clearErrors=true)
	{
		Yii::log("validate", 'profile', 'VmPoolForm.validate');
		if ('' != $this->poolBackupActive) {
			try {
				throw new Exception ($this->poolBackupActive);
			}
			catch (Exception $e) {
				Yii::log("\n" . $e->getTraceAsString(), 'profile', 'VmPoolForm.validate');
			}
		}
		$retval = parent::validate($attributes, $clearErrors);
		if ($retval) {
			
		}
		return $retval;
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'displayName' => Yii::t('vmpool', 'displayName'),
			'description' => Yii::t('vmpool', 'description'),
			'storagepool' => Yii::t('vmpool', 'storagepool'),
			'nodes' => Yii::t('vmpool', 'nodes'),
			'range' => Yii::t('vmpool', 'range'),
			'brokerMin' => Yii::t('vmpool', 'brokerMin'),
			'brokerMax' => Yii::t('vmpool', 'brokerMax'),
			'brokerPreStart' => Yii::t('vmpool', 'brokerPreStart'),
			'brokerPreStartInterval' => Yii::t('vmpool', 'brokerPreStartInterval'),
			'poolBackupActiveFalse' => Yii::t('vmpool', 'global backup'),
			'poolBackupActiveTrue' => Yii::t('vmpool', 'pool backup'),
			'sstBackupNumberOfIterations' => Yii::t('configuration', 'no. of iterations'),
			'sstVirtualizationVirtualMachineForceStart' => Yii::t('configuration', 'vm force start'),
			'poolCronActive' => Yii::t('vmpool', 'global cron'),
			'sstCronActiveFalse' => Yii::t('configuration', 'no schedule'),
			'sstCronActiveTrue' => Yii::t('configuration', 'at'),
			'everyDayTrue' => Yii::t('vmpool', 'every day'),
			'poolShutdownActive' => Yii::t('vmpool', 'global cron'),
			'poolShutdownActiveFalse' => Yii::t('configuration', 'no schedule'),
			'poolShutdownActiveTrue' => Yii::t('configuration', 'at'),
			'poolShutdownEveryDayTrue' => Yii::t('vmpool', 'every day'),
			'poolSound' => Yii::t('vmpool', 'poolSound'),
			'allowSoundTrue' => Yii::t('vmpool', 'allowSound (gloabal: YES)'),
			'allowSoundFalse' => Yii::t('vmpool', 'allowSound (gloabal: NO)'),
			'poolUsb' => Yii::t('vmpool', 'poolUsb'),
			'allowUsbTrue' => Yii::t('vmpool', 'allowUsb (gloabal: YES)'),
			'allowUsbFalse' => Yii::t('vmpool', 'allowUsb (gloabal: NO)'),
			'sstNumberOfScreens' => Yii::t('vmpool', 'sstNumberOfScreens'),
		);
	}
}