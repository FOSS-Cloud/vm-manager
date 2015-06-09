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
?>
<div class="form">
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'vmprofile-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));
echo CHtml::hiddenField('VmProfileForm[path]', '');

function getTreeData($data, $i=0, $id='') {
	$retval = array();
	foreach($data as $name => $item) {
		switch($i) {
			case 0:
				$sub = array('text' => $name, 'children' => getTreeData($item['children'], $i+1, $id . "{$name}°"));
				break;
			case 1:
				$sub = array('text' => $name, 'children' => getTreeData($item['children'], $i+1, $id . "{$name}°"));
				break;
			case 2:
				$sub = array('text' => $name, 'children' => getTreeData($item['children'], $i+1, $id . "{$name}°"));
				break;
			case 3:
				$sub = array('text' => CHtml::radioButton('VmProfileForm[basis]', false, array('style' => 'margin-right: 5px;', 'id'=>$id . "{$name}°", 'value'=>$item['dn'])) . CHtml::label($name, $id . "{$name}°", array('style'=>'display: inline;')));
				break;
		}
		$retval[] = $sub;
	}
	return $retval;
}

if (!is_null($profiles)) {
	$treedata = getTreeData($profiles);
	Yii::app()->clientScript->registerScript('profiletree', <<<EOS
	$('#profiletree input[type="radio"]').change(function (e) {
		$.ajax({
			url: "{$this->createUrl('vmProfile/getDefaults')}",
			cache: false,
			dataType: "json",
			data: "dn=" + encodeURIComponent(e.target.value) + "&p=" + encodeURIComponent(e.target.id),
			success: function(data){
				$('#hidestep2').hide();
				$("#step2").show();
				if ('default' == data['type']) {
					$('label[for="VmProfileForm_isofile"] > span').toggleClass('notrequired', false);
					$('label[for="VmProfileForm_isofile"] > span').toggleClass('required', true);
				}
				else {
					$('label[for="VmProfileForm_isofile"] > span').toggleClass('required', false);
					$('label[for="VmProfileForm_isofile"] > span').toggleClass('notrequired', true);
				}
				$('#VmProfileForm_path').val(data['path']);
				$('#VmProfileForm_name').val(data['name']);
				$('#VmProfileForm_description').val(data['description']);
				$('#VmProfileForm_sstMemory').val(data['memorydefault']);
				$('#VmProfileForm_sstVolumeCapacity').val(data['volumecapacitydefault']);
				$('#VmProfileForm_sstVCPU').empty(); // remove old options
				$.each(data['cpuvalues'], function(key, value) {
					option = $('<option></option>').attr('value', value).text(value);
					if (data['cpudefault'] == value) {
						option.attr('selected', 'selected');
					}
					$('#VmProfileForm_sstVCPU').append(option);
				});
				$('#VmProfileForm_sstClockOffset').empty(); // remove old options
				$.each(data['clockvalues'], function(key, value) {
					option = $('<option></option>');
					option.attr('value', value).text(value);
					if (data['clockdefault'] == value) {
						option.attr('selected', 'selected');
					}
					$('#VmProfileForm_sstClockOffset').append(option);
				});
				data['memorymin'] = new Number(data['memorymin']).valueOf();
				data['memorymax'] = new Number(data['memorymax']).valueOf();
				data['memorystep'] = new Number(data['memorystep']).valueOf();
				data['memorydefault'] = new Number(data['memorydefault']).valueOf();
				$('#VmProfileForm_sstMemory_slider').slider('option', { disabled: false, min: data['memorymin'], max: data['memorymax'], step: data['memorystep'] });
				$('#VmProfileForm_sstMemory_slider').slider('option', 'value', data['memorydefault']);
				$('#sstMemoryMin_display').html(getHumanSize(data['memorymin']));
				$('#sstMemoryMax_display').html(getHumanSize(data['memorymax']));
				data['volumecapacitymin'] = new Number(data['volumecapacitymin']).valueOf();
				data['volumecapacitymax'] = new Number(data['volumecapacitymax']).valueOf();
				data['volumecapacitystep'] = new Number(data['volumecapacitystep']).valueOf();
				data['volumecapacitydefault'] = new Number(data['volumecapacitydefault']).valueOf();
				$('#VmProfileForm_sstVolumeCapacity_slider').slider('option', { disabled: false, min: data['volumecapacitymin'], max: data['volumecapacitymax'], step: data['volumecapacitystep'] });
				$('#VmProfileForm_sstVolumeCapacity_slider').slider('option', 'value', data['volumecapacitydefault']);
				$('#sstVolumeCapacityMin_display').html(getHumanSize(data['volumecapacitymin']));
				$('#sstVolumeCapacityMax_display').html(getHumanSize(data['volumecapacitymax']));

				$('#submit').removeAttr('disabled');
			},
		});
	});
EOS
	, CClientScript::POS_END);
	Yii::app()->clientScript->registerScript('profiletree', <<<EOS
	//$('#hidestep2').height($('#step2').height());
	$('#hidestep2').width($('#step2').width());
	$('#hidestep2').offset($('#step2').offset());
	$("#step2").hide();
EOS
	, CClientScript::POS_READY);

	//echo '<pre>' . print_r($treedata, true) . '</pre>';
?>
	<div id="hidestep2" style="position: absolute; left: 10px; top: 10px; width: 50px; height: 50px; background: transparent url(<?=$this->cssBase;?>/opaque.png) repeat left top; z-index: 2701;"> </div>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div id="step1" class="column span-7">
		<div class="step"><?= Yii::t('vmprofile', 'step1');?> <p><?=Yii::t('vmprofile', 'step1text');?></p></div>
		<div class="row">
			<?php echo $form->labelEx($model,'profile'); ?>
<?php
	$this->widget('system.web.widgets.CTreeView', array(
		'collapsed' => true,
		'data' => $treedata,
		//'htmlOptions' => array('style'=>'height: 400px; overflow: scroll;'),
		'htmlOptions' => array(/*'class'=>'treeview-famfamfam',*/ 'class' => 'ui-widget-content', 'id'=>'profiletree'),
		'cssFile' => $this->cssBase . '/treeview/jquery.treeview.css',
	));
?>
		</div>
	</div>
	<div id="step2" class="column span-10">
		<div class="step"><?= Yii::t('vmprofile', 'step2');?> <p><?=Yii::t('vmprofile', 'step2text');?></p></div>
		<div class="row">
			<?php echo $form->labelEx($model,'isofile'); ?>
			<?php echo $form->listBox($model,'isofile', $isofiles); ?>
			<?php echo $form->error($model,'isofile'); ?>
		</div>
		<br/>
<?php
		//
		// END of !is_null($profiles)
		//
}
else {
	echo $form->hiddenField($model, 'dn');
}
?>
		<div class="row">
			<?php echo $form->labelEx($model,'name'); ?>
			<?php echo $form->textField($model,'name', !is_null($profiles) ? array('size'=>20) : array('size'=>20, 'disabled' => 'disabled')); ?>
			<?php if (is_null($profiles)) echo '<span style="font-size: 70%;">(readonly)</span>'; ?>
			<?php echo $form->error($model,'name'); ?>
		</div>
		<br/>
		<div class="row">
			<?php echo $form->labelEx($model,'description'); ?>
			<?php echo $form->textField($model,'description',array('maxlength'=>80, 'style' => 'width: 98%')); ?>
			<?php echo $form->error($model,'description'); ?>
		</div>
		<br/>
		<div class="row">
			<?php echo $form->hiddenField($model,'sstMemory',array('size'=>20)); ?>
			<?php echo $form->labelEx($model,'sstMemory',array('style'=>'margin-bottom: 5px;')); ?>
<?php
$this->widget('zii.widgets.jui.CJuiSlider', array(
	'id'=>'VmProfileForm_sstMemory_slider',
    'value'=>$model->sstMemory,
    // additional javascript options for the slider plugin
    'options'=>array(
        'min'=>(null != $defaults ? $defaults->sstMemoryMin : 1),
        'max'=>(null != $defaults ? $defaults->sstMemoryMax : 1),
		'value'=>(null != $defaults ? $model->sstMemory : 1),
		'step'=>(null != $defaults ? $defaults->sstMemoryStep : 1),
		'disabled'=>(null != $defaults ? false : true),
		'slide' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmProfileForm_sstMemory').val(ui.value);
			jQuery('#sstMemory_display').html(getHumanSize(ui.value));
		}
EOS
,
		'change' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmProfileForm_sstMemory').val(ui.value);
			jQuery('#sstMemory_display').html(getHumanSize(ui.value));
		}
EOS
	),
    'htmlOptions'=>array(
		'class'=> (is_null($profiles) ? 'span-15' : 'span-8'),
        'style'=>'height: 10px; float: left; margin-left: 10px;',
    ),
	'themeUrl' => $this->cssBase . '/jquery',
	'theme' => 'osbd',
    'cssFile' => 'jquery-ui.custom.css',
    ));

	Yii::app()->clientScript->registerScript('sliderhuman', <<<EOS
	function getHumanSize(bytes) {
		var human = '???';
		var sizes = {'GB': 1073741824, 'MB': 1048576, 'KB': 1024, 'B': 1};
		jQuery.each(sizes, function(key, value) {
			if (bytes >= value) {
				human = (Math.round(bytes / value * 100) / 100) + ' ' + key;
				return false;
			}
		});
		return human;
	}
EOS
, CClientScript::POS_END);
?>
			<div id="sstMemory_display" style="float: right;"><?= (null != $defaults ? $this->getHumanSize($model->sstMemory) : '&nbsp;');?></div>
			<div class="<?=(is_null($profiles) ? 'span-16' : 'span-9')?>" style="clear: both; float: left; font-size: 80%;">
				<div id="sstMemoryMin_display" style="float: left;"><?=$this->getHumanSize(null != $defaults ? $defaults->sstMemoryMin : 1);?></div>
				<div id="sstMemoryMax_display" style="float: right; margin-right: 20px;"><?=$this->getHumanSize(null != $defaults ? $defaults->sstMemoryMax : 1);?></div>
			</div>
			<?php echo $form->error($model,'sstMemory'); ?>
		</div>
		<br/>
		<div class="row">
			<?php echo $form->hiddenField($model,'sstVolumeCapacity',array('size'=>20)); ?>
			<?php echo $form->labelEx($model,'sstVolumeCapacity',array('style'=>'margin-bottom: 5px;')); ?>
<?php
$this->widget('zii.widgets.jui.CJuiSlider', array(
	'id'=>'VmProfileForm_sstVolumeCapacity_slider',
    'value'=>$model->sstVolumeCapacity,
    // additional javascript options for the slider plugin
    'options'=>array(
        'min'=>1,
        'max'=>1,
		'value'=>1,
		'step'=>1,
		'disabled'=>true,
		'slide' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmProfileForm_sstVolumeCapacity').val(ui.value);
			jQuery('#sstVolumeCapacity_display').html(getHumanSize(ui.value));
		}
EOS
,
		'change' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmProfileForm_sstVolumeCapacity').val(ui.value);
			jQuery('#sstVolumeCapacity_display').html(getHumanSize(ui.value));
		}
EOS
	),
    'htmlOptions'=>array(
		'class'=>(is_null($profiles) ? 'span-15' : 'span-8'),
        'style'=>'height: 10px; float: left; margin-left: 10px;',
    ),
	'themeUrl' => $this->cssBase . '/jquery',
	'theme' => 'osbd',
    'cssFile' => 'jquery-ui.custom.css',
    ));
	if(null != $defaults) {
		Yii::app()->clientScript->registerScript('sliderinit', <<<EOS
				$('#VmProfileForm_sstMemory_slider').slider('option', { disabled: false, min: {$defaults->sstMemoryMin}, max: {$defaults->sstMemoryMax}, step: {$defaults->sstMemoryStep} });
				$('#VmProfileForm_sstMemory_slider').slider('option', 'value', {$model->sstMemory});
				$('#VmProfileForm_sstVolumeCapacity_slider').slider('option', { disabled: false, min: {$defaults->VolumeCapacityMin}, max: {$defaults->VolumeCapacityMax}, step: {$defaults->VolumeCapacityStep} });
				$('#VmProfileForm_sstVolumeCapacity_slider').slider('option', 'value', {$model->sstVolumeCapacity});
EOS
, CClientScript::POS_READY);
	}
//	echo 'MMin: ' . $this->getHumanSize($defaults->sstMemoryMin) . '<br/>';
//	echo 'MMax: ' . $this->getHumanSize($defaults->sstMemoryMax) . '<br/>';
//	echo 'MStep: ' . $this->getHumanSize($defaults->sstMemoryStep) . '<br/>';
//	echo 'M: ' . $this->getHumanSize($model->sstMemory) . '<br/>';
//	echo 'VMin: ' . $this->getHumanSize($defaults->VolumeCapacityMin) . '<br/>';
//	echo 'VMax: ' . $this->getHumanSize($defaults->VolumeCapacityMax) . '<br/>';
//	echo 'VStep: ' . $this->getHumanSize($defaults->VolumeCapacityStep) . '<br/>';
//	echo 'V: ' . $this->getHumanSize($model->sstVolumeCapacity) . '<br/>';
?>
			<div id="sstVolumeCapacity_display" style="float: right;">&nbsp;</div>
			<div class="<?=(is_null($profiles) ? 'span-16' : 'span-9')?>" style="clear: both; float: left; font-size: 80%;">
				<div id="sstVolumeCapacityMin_display" style="float: left;"><?=$this->getHumanSize(null != $defaults ? $defaults->VolumeCapacityMin : 1);?></div>
				<div id="sstVolumeCapacityMax_display" style="float: right; margin-right: 20px;"><?=$this->getHumanSize(null != $defaults ? $defaults->VolumeCapacityMax : 1);?></div>
			</div>
			<?php echo $form->error($model,'sstVolumeCapacity'); ?>
		</div>
		<br/>
		<div class="column span-4">
			<div class="row">
				<?php echo $form->labelEx($model,'sstVCPU'); ?>
				<?php echo $form->dropDownList($model,'sstVCPU',(null != $defaults ? $this->createDropdown($defaults->sstVCPUValues) : array())); ?>
				<?php echo $form->error($model,'sstVCPU'); ?>
			</div>
			<br/>
		</div>
		<div class="column">
			<div class="row">
				<?php echo $form->labelEx($model,'sstClockOffset'); ?>
				<?php echo $form->dropDownList($model,'sstClockOffset',(null != $defaults ? $this->createDropdown($defaults->sstClockOffsetValues) : array())); ?>
				<?php echo $form->error($model,'sstClockOffset'); ?>
			</div>
			<br/>
		</div>
		<br class="clear"/>
		<div class="row buttons">
			<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
		</div>
		<br class="clear"/>
	<?php $this->endWidget(); ?>
</div><!-- form -->