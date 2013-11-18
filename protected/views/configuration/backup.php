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
		<div class="row">
  			<?php echo $form->labelEx($model,'sstBackupNumberOfIterations'); ?>
  			<?php echo $form->textField($model, 'sstBackupNumberOfIterations', array('size' => 3)); ?>
  			<?php echo $form->error($model,'sstBackupNumberOfIterations'); ?>
  		</div>
		<div class="row">
  			<?php echo $form->labelEx($model, 'sstVirtualizationVirtualMachineForceStart'); ?>
  			<?php //echo $form->dropDownList($model,'sstVirtualizationVirtualMachineForceStart',array('TRUE'=>'Yes', 'FALSE'=>'No')); ?>
   			<div id="vmforcestart" style="float: left;">
  			<?php echo $form->radioButtonList($model,'sstVirtualizationVirtualMachineForceStart', array('FALSE'=>'No', 'TRUE'=>'Yes'), 
   					array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
   			</div>
   			<div class="hint" style="float: left; margin-top: 0;"><?php echo Yii::t('configuration', 'vm force start hint')?></div>
  			<?php echo $form->error($model,'sstVirtualizationVirtualMachineForceStart'); ?>
  		</div>
		<h2><?php echo Yii::t('configuration', 'Schedule')?></h2>
  		<div class="row">
  			<?php echo $form->radioButton($model,'sstCronActive', array('id' => 'ConfigurationBackupForm_cronActiveFalse', 'value' => 'FALSE', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'sstCronActiveFalse', array('style' => 'display: inline;')); ?>
  		</div>
  		<div class="row">
  			<?php echo $form->radioButton($model,'sstCronActive', array('id' => 'ConfigurationBackupForm_cronActiveTrue', 'value' => 'TRUE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'sstCronActiveTrue', array('style' => 'display: inline; float: left;')); ?>
  			<div id="globalcron" style="float: left; margin-left: 15px;">
  				<?php echo $form->hiddenField($model, 'sstCronHour'); ?>
  				<?php echo $form->hiddenField($model, 'sstCronMinute'); ?>
  				<?php echo $form->textField($model, 'cronTime', array('size' => 4)); ?>&nbsp;<span>(24h)</span><br/>&nbsp;
   				<div id="dayofweek">
   					<?php echo $form->radioButton($model,'everyDay', array('id' => 'ConfigurationBackupForm_everyDayTrue', 'value' => 'TRUE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  					<?php echo $form->labelEx($model, 'everyDayTrue', array('style' => 'display: inline; float: left;')); ?><br style="clear: both;" />
   					<?php echo $form->radioButton($model,'everyDay', array('id' => 'ConfigurationBackupForm_everyDayFalse', 'value' => 'FALSE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
   					
   					<?php echo $form->checkBoxList($model,'sstCronDayOfWeek', CLocale::getInstance(Yii::t('app', 'locale'))->getWeekDayNames('abbreviated'), 
   						array('separator' => '&nbsp;&nbsp;', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
			</div>
   		</div>
	</div>
	<div style="clear: both;" class="row buttons">
		<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
	</div>
<?php $this->endWidget(); ?>
</div><!-- form -->
<?php
$locale = str_replace('_', '-', Yii::t('app', 'locale'));
Yii::app()->clientScript->registerScriptFile('globalize.js');
Yii::app()->clientScript->registerScriptFile('globalizecultures.js');
Yii::app()->clientScript->registerScript('buttons', <<<EOS
Globalize.culture('de-DE');
$.widget( "ui.timespinner", $.ui.spinner, {
	options: {
		// seconds
		step: 5 * 60 * 1000,
		// hours
		page: 12
	},
	_parse: function( value ) {
		if ( typeof value === "string" ) {
			// already a timestamp
			if ( Number( value ) == value ) {
				return Number( value );
			}
			return +Globalize.parseDate( value );
		}
		return value;
	},
	_format: function( value ) {
		return Globalize.format( new Date(value), "t" );
	}
});
$("#ConfigurationBackupForm_sstBackupNumberOfIterations").spinner({min: 1, max: 20});
$("#vmforcestart").buttonset();
$("#ConfigurationBackupForm_cronTime").timespinner();
 		
$("#ConfigurationBackupForm_cronActiveFalse").click(function() {
	$("#ConfigurationBackupForm_cronTime").timespinner('disable');
	$("#globalcron input[type=radio]").attr('disabled', true);
 	$("#dayofweek input[type=checkbox]").attr('disabled', true);
});
$("#ConfigurationBackupForm_cronActiveTrue").click(function() {
	$("#ConfigurationBackupForm_cronTime").timespinner('enable');
	$("#globalcron input[type=radio]").attr('disabled', false);
 	$("#dayofweek input[type=checkbox]").attr('disabled', false);
});
$("#ConfigurationBackupForm_cronActiveFalse:checked").click();
 		
$("#ConfigurationBackupForm_everyDayTrue").click(function() {
 	$("#dayofweek input[type=checkbox]").attr('disabled', true);
});
$("#ConfigurationBackupForm_everyDayFalse").click(function() {
 	$("#dayofweek input[type=checkbox]").attr('disabled', false);
});
$("#ConfigurationBackupForm_everyDayTrue:checked").click();
 		
EOS
, CClientScript::POS_READY);
?>