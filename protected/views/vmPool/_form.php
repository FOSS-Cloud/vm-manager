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
	'id'=>'vmpool-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));
$subnetcreate = $this->createUrl('subnet/index');
$storagepoolcreate = $this->createUrl('storagepool/index');
echo $form->hiddenField($model, 'dn');
?>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div class="column span-10" style="padding: 5px;">
		<div class="row">
			<?php echo $form->labelEx($model,'type'); ?>
<?php
if (is_null($model->dn)) {
	$params = array('prompt'=>'',
		'style'=>'float: left;',
		'ajax' => array(
	        'type'=>'GET', //request type
			'dataType'=>'json',
	        'url'=>$this->createUrl('vmPool/getDynData'), //url to call
	        'data'=>array('type'=>'js:$(\'#VmPoolForm_type\').val()'),
	        'success'=> <<< EOS
function(data) {
	$('#VmPoolForm_storagepool').children().remove();
	$('#VmPoolForm_storagepool').append($('<option value=""> </option>'));
	storagepoolCount = 0;
	$.each(data['storagepools'], function(key, value) {
		$('#VmPoolForm_storagepool').append($('<option value="' + key + '">' + value + '</option>'));
		storagepoolCount++;
	});
	if (0 == storagepoolCount) {
		$('#VmPoolForm_storagepool_em_').html('No StoragePool found! Please <a href="$storagepoolcreate">create</a> one.').show();
	}
	$('#VmPoolForm_storagepool').removeProp('disabled');

	$('#VmPoolForm_range').children().remove();
	$('#VmPoolForm_range').append($('<option value=""> </option>'));
	rangeCount = 0;
	$.each(data['ranges'], function(subnet, ranges) {
		group = $('<optgroup label="' + subnet + '"></optroup>');
		$.each(ranges, function(key, range) {
			group.append($('<option value="' + key + '">' + range + '</option>'));
			rangeCount++;
		});
		$('#VmPoolForm_range').append(group);
	});
	$('#VmPoolForm_range').removeProp('disabled');
	if (0 == rangeCount) {
		$('#VmPoolForm_range_em_').html('No Range found! Please <a href="$subnetcreate">create</a> one.').show();
	}
	if ('dynamic' == data['type']) {
		$('#VmPoolForm_brokerMin').val(data['brokerMin']);
		$('#brokerMin').show();
		$('#VmPoolForm_brokerMax').val(data['brokerMax']);
		$('#brokerMax').show();
		$('#VmPoolForm_brokerPreStart').val(data['brokerPreStart']);
		$('#brokerPreStart').show();
	}
	else {
		$('#brokerMin').hide();
		$('#brokerMax').hide();
		$('#brokerPreStart').hide();
	}
}
EOS
	));
    echo $form->dropDownList($model,'type',$types, $params);
?>
			<div class="hint"><?php echo Yii::t('vmpool', 'changes the selection of StoragePools and Ranges!') ?></div>
			<?php echo $form->error($model,'type',array('style'=>'clear: both;')); ?>
<?php
}
else {
	echo $form->textField($model, 'type',array('disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>';
}
?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'storagepool'); ?>
<?php
if (is_null($model->dn)) {
	echo $form->dropDownList($model,'storagepool',$storagepools, array('prompt'=>'',
				'style'=>'float: left;', 'disabled'=>'disabled',
	));
	echo $form->error($model,'storagepool',array('style'=>'clear: both;'));
}
else {
	echo CHtml::textField('storagepool', $model->storagepool, array('size'=>40, 'disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>';
	echo $form->hiddenField($model, 'storagepool');
}
?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'displayName'); ?>
			<?php echo $form->textField($model,'displayName',array('size'=>30)); ?>
			<?php echo $form->error($model,'displayName'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'description'); ?>
			<?php echo $form->textField($model,'description',array('size'=>30)); ?>
			<?php echo $form->error($model,'description'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'nodes'); ?>
			<?php echo $form->listBox($model,'nodes',$nodes,array('multiple'=>'multiple')); ?>
			<?php echo $form->error($model,'nodes'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'range'); ?>
<?php
if (is_null($model->dn)) {
		echo $form->dropDownList($model,'range',$ranges, array('prompt'=>'', 'style'=>'float: left;', 'disabled' => 'disabled'));
		echo $form->error($model,'range',array('style'=>'clear: both;'));
}
else if (isset($vmcount) && 0 < $vmcount) {
	echo CHtml::textField('range', $model->range,array('size'=>40, 'disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>';
	echo $form->hiddenField($model, 'range');
}
else {
	echo $form->dropDownList($model,'range',$ranges, array('prompt'=>'', 'style'=>'float: left;'));
	echo $form->error($model,'range',array('style'=>'clear: both;'));
}
?>
		</div>
		<div id="brokerMin" class="row" <?php echo -1 == $model->brokerMin ? 'style="display: none;"' : '';?>>
			<?php echo $form->labelEx($model,'brokerMin'); ?>
			<?php echo $form->textField($model,'brokerMin',array('size'=>5, 'style'=>'float: left;')); ?>
			<div class="hint">minimal number of running VMs</div>
			<?php echo $form->error($model,'brokerMin'); ?>
		</div>
		<div id="brokerMax" class="row" <?php echo -1 == $model->brokerMax ? 'style="display: none;"' : '';?>>
			<?php echo $form->labelEx($model,'brokerMax'); ?>
			<?php echo $form->textField($model,'brokerMax',array('size'=>5, 'style'=>'float: left;')); ?>
			<div class="hint">maximal number of running VMs</div>
			<?php echo $form->error($model,'brokerMax'); ?>
		</div>
		<div id="brokerPreStart" class="row" <?php echo -1 == $model->brokerPreStart ? 'style="display: none;"' : '';?>>
			<?php echo $form->labelEx($model,'brokerPreStart'); ?>
			<?php echo $form->textField($model,'brokerPreStart',array('size'=>5, 'style'=>'float: left;')); ?>
			<div class="hint">minimal number of free VMs</div>
			<?php echo $form->error($model,'brokerPreStart'); ?>
		</div>
	</div>
	<div class="column span-7">
		<h2><?php echo Yii::t('configuration', 'Backup')?></h2>
  		<div class="row">
  			<?php echo $form->radioButton($model,'poolBackupActive', array('id' => 'VmPoolForm_backupActiveFalse', 'value' => 'FALSE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'poolBackupActiveFalse', array('style' => 'display: inline;')); ?>
  		</div>
  		<div class="row">
  			<?php echo $form->radioButton($model,'poolBackupActive', array('id' => 'VmPoolForm_backupActiveTrue', 'value' => 'TRUE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'poolBackupActiveTrue', array('style' => 'display: inline; float: left;')); ?><br/>
  			<div id="poolbackup" style="clear: both; margin-left: 20px;">
	   			<?php echo $form->labelEx($model,'sstBackupNumberOfIterations'); ?>
	  			<?php echo $form->textField($model, 'sstBackupNumberOfIterations', array('size' => 3)); ?>
	  			<?php echo $form->error($model,'sstBackupNumberOfIterations'); ?>
	   			<?php echo $form->labelEx($model, 'sstVirtualizationVirtualMachineForceStart'); ?>
	  			<?php //echo $form->dropDownList($model,'sstVirtualizationVirtualMachineForceStart',array('TRUE'=>'Yes', 'FALSE'=>'No')); ?>
	   			<div id="vmforcestart">
		  			<?php echo $form->radioButtonList($model,'sstVirtualizationVirtualMachineForceStart', array('FALSE'=> Yii::t('vmPool', 'NO'), 'TRUE'=> Yii::t('vmPool', 'YES')), 
		   					array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
	   			</div>
	  			<?php echo $form->error($model,'sstVirtualizationVirtualMachineForceStart'); ?>
	  		</div>
  		</div>
		<h2><?php echo Yii::t('configuration', 'Schedule')?></h2>
  		<div class="row">
  			<?php echo $form->radioButton($model,'poolCronActive', array('id' => 'VmPoolForm_globalCronActive', 'value' => 'GLOBAL', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'poolCronActive', array('style' => 'display: inline;')); ?>
  		</div>
		<div class="row">
  			<?php echo $form->radioButton($model,'poolCronActive', array('id' => 'VmPoolForm_cronActiveFalse', 'value' => 'FALSE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'sstCronActiveFalse', array('style' => 'display: inline;')); ?>
  		</div>
  		<div class="row">
  			<?php echo $form->radioButton($model,'poolCronActive', array('id' => 'VmPoolForm_cronActiveTrue', 'value' => 'TRUE', 'style' => 'float: left; margin-top: 7px;', 'uncheckValue' => null)); ?>
  			<?php echo $form->labelEx($model, 'sstCronActiveTrue', array('style' => 'display: inline; float: left; margin: 4px 6px 0 0;')); ?>
 			<?php echo $form->textField($model, 'cronTime', array('size' => 4, 'style' => 'display: inline; float: left;')); ?>&nbsp;<span>(24h)</span><br/>&nbsp;
  			<div id="poolcron" style="clear: both; margin-left: 15px;">
  				<?php echo $form->hiddenField($model, 'sstCronHour'); ?>
  				<?php echo $form->hiddenField($model, 'sstCronMinute'); ?>
   				<div id="dayofweek1" style="float: right;">
   				<?php 
   					$localedays = CLocale::getInstance(Yii::t('app', 'locale'))->getWeekDayNames('abbreviated');
   					$days = array_merge(array('*' => Yii::t('configuration', 'every day')), array_slice($localedays, 0, 3));
   					//echo '<pre>' . print_r($days, true) . '</pre>';
   					echo $form->radioButtonList($model,'sstCronDayOfWeek', $days, 
   						array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
  				<div id="dayofweek2" style="float: right;">
   				<?php
   					//$localedays = CLocale::getInstance(Yii::t('app', 'locale'))->getWeekDayNames('abbreviated');
   					$days = array(); 
   					for($i=3; $i<7; $i++) {
						$days[$i] = $localedays[$i];
					}
					$days = array_slice($localedays, 3, 4, true);
					//echo '<pre>' . print_r($days, true) . '</pre>';
   					echo $form->radioButtonList($model,'sstCronDayOfWeek', $days, 
   						array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
			</div>
   		</div>
		<h2><?php echo Yii::t('vmpool', 'interfaces')?></h2>
		<div class="row">
			<?php echo $globalSound ? $form->labelEx($model,'allowSoundTrue') : $form->labelEx($model,'allowSoundFalse'); ?>
			<?php echo $form->checkbox($model, 'poolSound', array('style' => 'float: left; margin-top: 10px;'))?>
			<?php echo $form->labelEx($model, 'poolSound', array('style' => 'float: left; margin: 8px 6px 0 6px;'))?>
			<div id="soundsettings" style="float: right;">
				<input type="radio" name="VmPoolForm[allowSound]" value="0"  <?php echo (false === $model->allowSound ? 'checked="checked"' : ''); ?> id="sound2" /><label for="sound2" style="display: inline-block;"><?php echo Yii::t('vmPool', 'NO');?></label>
				<input type="radio" name="VmPoolForm[allowSound]" value="1"  <?php echo (true === $model->allowSound ? 'checked="checked"' : '');  ?> id="sound3" /><label for="sound3" style="display: inline-block;"><?php echo Yii::t('vmPool', 'YES');?></label>
			</div>
			<?php echo $form->error($model,'allowSound', array('style' => 'clear: both;')); ?>
		</div>
		<div class="row">
			<?php echo $globalUsb ? $form->labelEx($model,'allowUsbTrue') : $form->labelEx($model,'allowUsbFalse'); ?>
			<?php echo $form->checkbox($model, 'poolUsb', array('style' => 'float: left; margin-top: 10px;'))?>
			<?php echo $form->labelEx($model, 'poolUsb', array('style' => 'float: left; margin: 8px 6px 0 6px;'))?>
			<div id="usbsettings" style="float: right;">
				<input type="radio" name="VmPoolForm[allowUsb]" value="0"  <?php echo (false === $model->allowUsb ? 'checked="checked"' : ''); ?> id="usb2" /><label for="usb2" style="display: inline-block;"><?php echo Yii::t('vmPool', 'NO');?></label>
				<input type="radio" name="VmPoolForm[allowUsb]" value="1"  <?php echo (true === $model->allowUsb ? 'checked="checked"' : '');  ?> id="usb3" /><label for="usb3" style="display: inline-block;"><?php echo Yii::t('vmPool', 'YES');?></label>
			</div>
			<?php echo $form->error($model,'allowUsb'); ?>
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
$("#VmPoolForm_sstBackupNumberOfIterations").spinner({min: 1, max: 20});
$("#vmforcestart").buttonset();
var el, val;
// el = $("#dayofweek2 #VmPoolForm_sstCronDayOfWeek_4");
// val = parseInt(el.val()) + 1;
// $("#dayofweek2 label[for=VmPoolForm_sstCronDayOfWeek_4]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
// el.attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
el = $("#dayofweek2 #VmPoolForm_sstCronDayOfWeek_3");
val = parseInt(el.val()) + 1;
$("#dayofweek2 label[for=VmPoolForm_sstCronDayOfWeek_3]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
el.attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
el = $("#dayofweek2 #VmPoolForm_sstCronDayOfWeek_2");
val = parseInt(el.val()) + 1;
$("#dayofweek2 label[for=VmPoolForm_sstCronDayOfWeek_2]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
el.attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
el = $("#dayofweek2 #VmPoolForm_sstCronDayOfWeek_1");
val = parseInt(el.val()) + 1;
$("#dayofweek2 label[for=VmPoolForm_sstCronDayOfWeek_1]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
el.attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
 		el = $("#dayofweek2 #VmPoolForm_sstCronDayOfWeek_0");
val = parseInt(el.val()) + 1;
$("#dayofweek2 label[for=VmPoolForm_sstCronDayOfWeek_0]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
el.attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
 		
// $("#dayofweek2 input[type=radio]").each(function() {
//   	var val = parseInt($(this).val()) + 1;
//   	$("#dayofweek2 label[for=" + $(this).attr('id') + "]").attr('for', 'VmPoolForm_sstCronDayOfWeek_' + val);
//  	$(this).attr('id', 'VmPoolForm_sstCronDayOfWeek_' + val);
// 	});
$("#VmPoolForm_cronTime").timespinner();
$("#dayofweek1").buttonset();
$("#dayofweek2").buttonset();
$("#soundsettings").buttonset();
$("#usbsettings").buttonset();
 		
$("#VmPoolForm_backupActiveFalse").click(function() {
	$("#VmPoolForm_sstBackupNumberOfIterations").spinner('disable');
	$("label[for=VmPoolForm_sstBackupNumberOfIterations]").addClass('disabled');
 	$("#vmforcestart").buttonset('disable');
	$("label[for=VmPoolForm_sstVirtualizationVirtualMachineForceStart]").addClass('disabled');
});
$("#VmPoolForm_backupActiveTrue").click(function() {
	$("#VmPoolForm_sstBackupNumberOfIterations").spinner('enable');
	$("label[for=VmPoolForm_sstBackupNumberOfIterations]").removeClass('disabled');
 	$("#vmforcestart").buttonset('enable');
	$("label[for=VmPoolForm_sstVirtualizationVirtualMachineForceStart]").removeClass('disabled');
});
$("#VmPoolForm_backupActiveFalse:checked").click();
 		
$("#VmPoolForm_globalCronActive").click(function() {
	$("#VmPoolForm_cronTime").timespinner('disable');
	$("#dayofweek1").buttonset('disable');
	$("#dayofweek2").buttonset('disable');
});
$("#VmPoolForm_cronActiveFalse").click(function() {
	$("#VmPoolForm_cronTime").timespinner('disable');
	$("#dayofweek1").buttonset('disable');
	$("#dayofweek2").buttonset('disable');
});
$("#VmPoolForm_cronActiveTrue").click(function() {
	$("#VmPoolForm_cronTime").timespinner('enable');
 	$("#dayofweek1").buttonset('enable');
	$("#dayofweek2").buttonset('enable');
});
$("#VmPoolForm_globalCronActive:checked").click();
$("#VmPoolForm_cronActiveFalse:checked").click();

$("#VmPoolForm_poolSound").change(function() {
	if (this.checked) {
 		$("#soundsettings").buttonset('enable');
 	}
 	else  {
 		$("#soundsettings").buttonset('disable');
 	}
});
if (!$("#VmPoolForm_poolSound").prop('checked')) {
	$("#soundsettings").buttonset('disable');
}


$("#VmPoolForm_poolUsb").change(function() {
	if (this.checked) {
 		$("#usbsettings").buttonset('enable');
 	}
 	else  {
 		$("#usbsettings").buttonset('disable');
 	}
});
if (!$("#VmPoolForm_poolUsb").prop('checked')) {
	$("#usbsettings").buttonset('disable');
}
EOS
, CClientScript::POS_END);
?>