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
	<div class="column" style="padding: 5px;">
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
		<div style="clear: both;" class="row buttons">
			<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
		</div>
<?php $this->endWidget(); ?>

</div><!-- form -->