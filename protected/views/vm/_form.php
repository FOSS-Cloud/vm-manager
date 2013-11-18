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
	'id'=>'vm-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
	'htmlOptions' => array(
		'enctype' => 'multipart/form-data',
	)
));
	echo $form->hiddenField($model, 'dn');
	$attributeLabels = $model->attributeLabels();
	Yii::app()->clientScript->registerScript('staticip', <<<EOS
		$('#staticIP').css('display', 'none');
		$('#VmForm_useStaticIP').change(function(event) {
			if ($(this).attr('checked')) {
				$('#staticIP').css('display', 'block');
			}
			else {
				$('#staticIP').css('display', 'none');
			}
		});
EOS
, CClientScript::POS_END);

?>
		<div class="column span-6">
				<?php echo $form->labelEx($model,'node'); ?>
				<?php echo $form->textField($model, 'node',array('size'=>20, 'disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>'; ?>
				<?php echo $form->error($model,'node'); ?>
			<br/>
		</div>
		<div class="column">
				<?php echo $form->labelEx($model,'ip'); ?>
				<?php echo $form->textField($model, 'ip',array('size'=>20, 'disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>'; ?>
				<?php echo $form->checkBox($model,'useStaticIP') . '&nbsp;' . $attributeLabels['useStaticIP']; ?>
				<br/>
				<div id="staticIP" style="margin-left: 20px;">
					<div class="column">
						<div class="row">
							<?php echo $form->labelEx($model, 'range'); ?>
							<?php echo $form->dropDownList($model, 'range', $ranges, array('style'=>"float: left; margin-right: 4px;")); ?>
							<br/><br/>
							<?php echo $form->error($model,'range'); ?>
						</div>
					</div>
					<div class="column span-5">
						<div class="row">
							<?php echo $form->label($model, 'ip'); ?>
							<?php echo $form->textField($model, 'staticIP', array('size'=>20, 'style'=>"float: left;")); ?>
							<br/><br/>
							<?php echo $form->error($model,'staticIP'); ?>
						</div>
					</div>
				</div>
			<br/>
		</div>
		<br class="clear"/>
		<div class="row">
			<?php echo $form->labelEx($model,'name'); ?>
			<?php echo $form->textField($model,'name',array('size'=>20)); ?>
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
	'id'=>'VmForm_sstMemory_slider',
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
			jQuery('#VmForm_sstMemory').val(ui.value);
			jQuery('#sstMemory_display').html(getHumanSize(ui.value));
		}
EOS
,
		'change' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmForm_sstMemory').val(ui.value);
			jQuery('#sstMemory_display').html(getHumanSize(ui.value));
		}
EOS
	),
    'htmlOptions'=>array(
		'class'=> 'span-15' ,
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
			<div class="span-16" style="clear: both; float: left; font-size: 80%;">
				<div id="sstMemoryMin_display" style="float: left;"><?=$this->getHumanSize($defaults->sstMemoryMin);?></div>
				<div id="sstMemoryMax_display" style="float: right; margin-right: 20px;"><?=$this->getHumanSize($defaults->sstMemoryMax);?></div>
			</div>
			<?php echo $form->error($model,'sstMemory'); ?>
		</div>
		<br/>
		<div class="row">
			<?php echo $form->hiddenField($model,'sstVolumeCapacity',array('size'=>20)); ?>
			<?php echo $form->labelEx($model,'sstVolumeCapacity',array('style'=>'margin-bottom: 5px;')); ?>
<?php
$this->widget('zii.widgets.jui.CJuiSlider', array(
	'id'=>'VmForm_sstVolumeCapacity_slider',
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
			jQuery('#VmForm_sstVolumeCapacity').val(ui.value);
			jQuery('#sstVolumeCapacity_display').html(getHumanSize(ui.value));
		}
EOS
,
		'change' => 'js:' . <<<EOS
		function(e, ui) {
			jQuery('#VmForm_sstVolumeCapacity').val(ui.value);
			jQuery('#sstVolumeCapacity_display').html(getHumanSize(ui.value));
		}
EOS
	),
    'htmlOptions'=>array(
		'class'=>'span-15',
        'style'=>'height: 10px; float: left; margin-left: 10px;',
    ),
	'themeUrl' => $this->cssBase . '/jquery',
	'theme' => 'osbd',
    'cssFile' => 'jquery-ui.custom.css',
    ));
	if(null != $defaults) {
		Yii::app()->clientScript->registerScript('sliderinit', <<<EOS
				$('#VmForm_sstMemory_slider').slider('option', { disabled: false, min: {$defaults->sstMemoryMin}, max: {$defaults->sstMemoryMax}, step: {$defaults->sstMemoryStep} });
				$('#VmForm_sstMemory_slider').slider('option', 'value', {$model->sstMemory});
				$('#VmForm_sstVolumeCapacity_slider').slider('option', { disabled: false, min: {$defaults->VolumeCapacityMin}, max: {$defaults->VolumeCapacityMax}, step: {$defaults->VolumeCapacityStep} });
				$('#VmForm_sstVolumeCapacity_slider').slider('option', 'value', {$model->sstVolumeCapacity});
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
			<div class="span-16" style="clear: both; float: left; font-size: 80%;">
				<div id="sstVolumeCapacityMin_display" style="float: left;"><?=$this->getHumanSize($defaults->VolumeCapacityMin);?></div>
				<div id="sstVolumeCapacityMax_display" style="float: right; margin-right: 20px;"><?=$this->getHumanSize($defaults->VolumeCapacityMax);?></div>
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
	</div>
	<br class="clear"/>
<?php $this->endWidget(); ?>

</div><!-- form -->