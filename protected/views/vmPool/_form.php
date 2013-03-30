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

$nodesjs = 'var nodes = new Array();';
$nodevalues = array();
$nodeparams = array();
$i = 0;
foreach($nodes as $name => $hasVms) {
	$nodesjs .= 'nodes["' . $name . '"]=' . ($hasVms ? 'true' : 'false') . ';';
	$nodevalues[$name] = $name;
	$nodeparams[$name] = array('label' => $name);
	if ($hasVms) {
		$nodevalues[$name] = '&raquo; ' . $name;
		$nodeparams[$name]['style'] = 'font-style: italic;';
	}
	$i++;
}

if (!is_null($model->dn)) {
	Yii::app()->clientScript->registerScript('nodes', <<<EOS
	{$nodesjs}
	$("#VmPoolForm_nodes").change(function() {
		var options = $("#VmPoolForm_nodes option:not(:selected)");
		options.each(function (idx) {
			var val = $(this).val();
			if (undefined != nodes[val] && nodes[val]) {
				$(this).attr('selected', true);
			}
		});
	});			
EOS
, CClientScript::POS_READY);
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
			<?php echo $form->listBox($model,'nodes',$nodevalues,array('encode' => false, 'multiple'=>'multiple', 'style' => 'float: left;' , 'options' => $nodeparams));
				if (!is_null($model->dn)) {
					echo '<div style="float: left; vertical-align: top; margin-left: 10px; font-size: 70%;">(<span style="font-style: italic;"> &raquo; Node:</span> has VMs assigned to this VM Pool <br /> and must stay selected!)</div><br/>';
				}
			?>
			<br style="clear: both;" />
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
		<fieldset style="position: relative; margin-bottom: 20px;">
		<label><span style="display:block;position:absolute;top:-10px;left:10px; background-color: white;"><?php echo Yii::t('configuration', 'Backup')?>&nbsp;</span></label>
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
  		</fieldset>
		<fieldset style="position: relative; margin-bottom: 20px;">
  		<label><span style="display:block;position:absolute;top:-10px;left:10px; background-color: white;"><?php echo Yii::t('configuration', 'Schedule')?>&nbsp;</span></label>
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
   				<div id="dayofweek">
   					<?php echo $form->radioButton($model,'everyDay', array('value' => 'TRUE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
  					<?php echo $form->labelEx($model, 'everyDayTrue', array('style' => 'display: inline; float: left;')); ?><br style="clear: both;" />
   					<?php echo $form->radioButton($model,'everyDay', array('value' => 'FALSE', 'style' => 'float: left;', 'uncheckValue' => null)); ?>
   					<div style="float: left;">
   					<?php echo $form->checkBoxList($model,'sstCronDayOfWeek', CLocale::getInstance(Yii::t('app', 'locale'))->getWeekDayNames('abbreviated'), 
   						array('separator' => '&nbsp;&nbsp;', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
   					</div>
				</div>
 			
<?php /*?>   				
  				<div id="dayofweek1" style="float: right;">
   				<?php 
   					$localedays = CLocale::getInstance(Yii::t('app', 'locale'))->getWeekDayNames('abbreviated');
   					$days = array_merge(array('*' => Yii::t('configuration', 'every day')), array_slice($localedays, 0, 1));
   					//echo '<pre>' . print_r($days, true) . '</pre>';
   					echo $form->radioButtonList($model,'sstCronDayOfWeek', $days, 
   						array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
  				<div id="dayofweek2" style="float: right;">
   				<?php
					$days = array_slice($localedays, 1, 3, true);
					//echo '<pre>' . print_r($days, true) . '</pre>';
   					echo $form->radioButtonList($model,'sstCronDayOfWeek', $days, 
   						array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
  				<div id="dayofweek3" style="float: right;">
   				<?php
   					$days = array(); 
   					for($i=3; $i<7; $i++) {
						$days[$i] = $localedays[$i];
					}
					$days = array_slice($localedays, 4, 3, true);
					//echo '<pre>' . print_r($days, true) . '</pre>';
   					echo $form->radioButtonList($model,'sstCronDayOfWeek', $days, 
   						array('separator' => '', 'uncheckValue' => null, 'labelOptions' => array('style' => 'display: inline-block;'))); ?>
				</div>
<?php */ ?>
			</div>
   		</div>
   		</fieldset>
		<fieldset style="position: relative;">
		<label><span style="display:block;position:absolute;top:-10px;left:10px; background-color: white;"><?php echo Yii::t('vmpool', 'interfaces')?>&nbsp;</span></label>
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
		</fieldset>
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
$("#VmPoolForm_sstCronDayOfWeek_3").before('<br />');
$("#VmPoolForm_sstCronDayOfWeek_6").before('<br />');
$("#VmPoolForm_cronTime").timespinner();
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
	$("#poolcron input[type=radio]").attr('disabled', true);
 	$("#dayofweek input[type=checkbox]").attr('disabled', true);
});
$("#VmPoolForm_cronActiveFalse").click(function() {
	$("#VmPoolForm_cronTime").timespinner('disable');
	$("#poolcron input[type=radio]").attr('disabled', true);
 	$("#dayofweek input[type=checkbox]").attr('disabled', true);
});
$("#VmPoolForm_cronActiveTrue").click(function() {
	$("#VmPoolForm_cronTime").timespinner('enable');
	$("#poolcron input[type=radio]").attr('disabled', false);
 	$("#dayofweek input[type=checkbox]").attr('disabled', false);
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
, CClientScript::POS_READY);
?>