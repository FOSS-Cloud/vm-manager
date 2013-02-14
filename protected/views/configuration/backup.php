<?php
/*
 * Copyright (C) 2012 FOSS-Group
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
?>
<div class="form">
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'configurationbackup-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));

$this->title = Yii::t('configuration', 'Backup');
//$this->helpurl = Yii::t('help', 'updateUser');
?>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div>
		<div class="column span-8">
			<h2>Backup</h2>
			<div class="row">
	  			<?php echo $form->labelEx($model,'sstBackupNumberOfIterations'); ?>
	  			<?php echo $form->textField($model, 'sstBackupNumberOfIterations'); ?>
	  			<?php echo $form->error($model,'sstBackupNumberOfIterations'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstBackupRootDirectory'); ?>
	  			<?php echo $form->textField($model, 'sstBackupRootDirectory'); ?>
	  			<?php echo $form->error($model,'sstBackupRootDirectory'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstBackupRetainDirectory'); ?>
	  			<?php echo $form->textField($model, 'sstBackupRetainDirectory'); ?>
	  			<?php echo $form->error($model,'sstBackupRetainDirectory'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationVirtualMachineForceStart'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationVirtualMachineForceStart'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationVirtualMachineForceStart'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationBandwidthMerge'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationBandwidthMerge'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationBandwidthMerge'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstRestoreVMWithoutState'); ?>
	  			<?php echo $form->textField($model, 'sstRestoreVMWithoutState'); ?>
	  			<?php echo $form->error($model,'sstRestoreVMWithoutState'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstBackupExcludeFromBackup'); ?>
	  			<?php echo $form->textField($model, 'sstBackupExcludeFromBackup'); ?>
	  			<?php echo $form->error($model,'sstBackupExcludeFromBackup'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstBackupRamDiskLocation'); ?>
	  			<?php echo $form->textField($model, 'sstBackupRamDiskLocation'); ?>
	  			<?php echo $form->error($model,'sstBackupRamDiskLocation'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationVirtualMachineSequenceStop'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationVirtualMachineSequenceStop'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationVirtualMachineSequenceStop'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationVirtualMachineSequenceStart'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationVirtualMachineSequenceStart'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationVirtualMachineSequenceStart'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageFormat'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageFormat'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageFormat'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageOwner'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageOwner'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageOwner'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageGroup'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageGroup'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageGroup'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImagePermission'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImagePermission'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImagePermission'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageDirectoryOwner'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageDirectoryOwner'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageDirectoryOwner'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageDirectoryGroup'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageDirectoryGroup'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageDirectoryGroup'); ?>
	  		</div>
			<div class="row">
	  			<?php echo $form->labelEx($model, 'sstVirtualizationDiskImageDirectoryPermission'); ?>
	  			<?php echo $form->textField($model, 'sstVirtualizationDiskImageDirectoryPermission'); ?>
	  			<?php echo $form->error($model,'sstVirtualizationDiskImageDirectoryPermission'); ?>
	  		</div>
		</div>
		<div class="column span-8" style="padding-left: 10px; border-left: 1px solid lightgrey;">
			<h2>Cron</h2>
			<div class="row">
	  			<?php echo $form->labelEx($model,'sstCronDayOfWeek'); ?>
	  			<?php echo $form->dropDownList($model,'sstCronDayOfWeek',array('*'=>'*')+Yii::app()->locale->getWeekDayNames()); ?>
	  			<?php echo $form->error($model,'sstCronDayOfWeek'); ?>
	  		</div>
	  		<div class="row">
	  			<?php echo $form->labelEx($model,'sstCronMonth'); ?>
	  			<?php echo $form->dropDownList($model,'sstCronMonth',array('*'=>'*')+Yii::app()->locale->getMonthNames()); ?>
	  			<?php echo $form->error($model,'sstCronMonth'); ?>
	  		</div>
	  	  <div class="row">
	  			<?php echo $form->labelEx($model,'sstCronDay'); ?>
<?php 
$cronDays = array('*'=>'*');
for ($i = 0; $i <= 31; $i += 1) {
	$cronDays[$i] = $i;
}
?>
				<?php echo $form->dropDownList($model,'sstCronDay',$cronDays); ?>
	  			<?php echo $form->error($model,'sstCronDay'); ?>
	  		</div>
		    <div class="row">
	  			<?php echo $form->labelEx($model,'sstCronHour'); ?>
<?php 
$cronHours = array('*'=>'*');
for ($i = 0; $i <= 23; $i += 1) {
	$cronHours[$i] = $i;
}
?>
				<?php echo $form->dropDownList($model,'sstCronHour',$cronHours); ?>
	  			<?php echo $form->error($model,'sstCronHour'); ?>
	  		</div>
		    <div class="row">
	  			<?php echo $form->labelEx($model,'sstCronMinute'); ?>
<?php 
$cronMinutes = array('*'=>'*');
for ($i = 0; $i <= 55; $i += 5) {
	$cronMinutes[$i] = $i;
}
?>
	  			<?php echo $form->dropDownList($model,'sstCronMinute',$cronMinutes); ?>
	  			<?php echo $form->error($model,'sstCronMinute'); ?>
	  		</div>
	  		<div class="row">
	  			<?php echo $form->labelEx($model,'sstCronActive'); ?>
	  			<?php echo $form->dropDownList($model,'sstCronActive',array('TRUE'=>'true', 'FALSE'=>'false')); ?>
	  			<?php echo $form->error($model,'sstCronActive'); ?>
	  		</div>
		</div>
	</div>
	<div style="clear: both;" class="row buttons">
		<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
	</div>
<?php $this->endWidget(); ?>

</div><!-- form -->