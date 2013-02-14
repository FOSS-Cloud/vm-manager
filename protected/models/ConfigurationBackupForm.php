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

class ConfigurationBackupForm extends CFormModel {
	public $sstBackupNumberOfIterations;
	public $sstBackupRootDirectory;
	public $sstBackupRetainDirectory;
	public $sstVirtualizationVirtualMachineForceStart;
	public $sstVirtualizationBandwidthMerge;
	public $sstRestoreVMWithoutState;
	public $sstBackupExcludeFromBackup;
	public $sstBackupRamDiskLocation;
	public $sstVirtualizationVirtualMachineSequenceStop;
	public $sstVirtualizationVirtualMachineSequenceStart;
	public $sstVirtualizationDiskImageFormat;
	public $sstVirtualizationDiskImageOwner;
	public $sstVirtualizationDiskImageGroup;
	public $sstVirtualizationDiskImagePermission;
	public $sstVirtualizationDiskImageDirectoryOwner;
	public $sstVirtualizationDiskImageDirectoryGroup;
	public $sstVirtualizationDiskImageDirectoryPermission;
	
	public $sstCronMinute;
	public $sstCronHour;
	public $sstCronDay;
	public $sstCronMonth;
	public $sstCronDayOfWeek;
	public $sstCronActive;
	
	public function rules()
	{
		return array(
				array('sstBackupNumberOfIterations, sstBackupRootDirectory, sstBackupRetainDirectory, sstVirtualizationVirtualMachineForceStart, sstVirtualizationBandwidthMerge, sstRestoreVMWithoutState, sstBackupExcludeFromBackup, sstBackupRamDiskLocation, sstVirtualizationVirtualMachineSequenceStop, sstVirtualizationVirtualMachineSequenceStart, sstVirtualizationDiskImageFormat, sstVirtualizationDiskImageOwner, sstVirtualizationDiskImageGroup, sstVirtualizationDiskImagePermission, sstVirtualizationDiskImageDirectoryOwner, sstVirtualizationDiskImageDirectoryGroup, sstVirtualizationDiskImageDirectoryPermission, sstCronMinute, sstCronHour, sstCronDay, sstCronMonth, sstCronDayOfWeek, sstCronActive', 'safe')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
		);
	}
}