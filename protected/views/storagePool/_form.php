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
	'id'=>'storagepool-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));
	echo $form->hiddenField($model, 'dn');
	?>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div class="column" style="padding: 5px;">
		<?php if ($create) : ?>
		<div class="row">
			<?php echo $form->labelEx($model, 'sstStoragePoolType'); ?>
			<?php echo $form->dropDownList($model, 'sstStoragePoolType', $pooltypes); ?>
			<?php echo $form->error($model,'sstStoragePoolType'); ?>
		</div>
		<?php else : ?>
		<div class="row">
			<?php echo $form->labelEx($model, 'sstStoragePoolType'); ?>
			<?php echo $form->textField($model, 'sstStoragePoolType',array('disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>'; ?>
			<?php echo $form->error($model,'sstStoragePoolType'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'sstStoragePoolURI'); ?>
			<?php echo $form->textField($model, 'sstStoragePoolURI',array('size'=>80, 'disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>'; ?>
			<?php echo $form->error($model,'sstStoragePoolURI'); ?>
		</div>
		<?php endif; ?>
		<div class="row">
			<?php echo $form->labelEx($model,'sstDisplayName'); ?>
			<?php echo $form->textField($model,'sstDisplayName',array('size'=>30)); ?>
			<?php echo $form->error($model,'sstDisplayName'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'description'); ?>
			<?php echo $form->textField($model,'description',array('size'=>30)); ?>
			<?php echo $form->error($model,'description'); ?>
		</div>
	</div>
		<div style="clear: both;" class="row buttons">
			<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
		</div>
<?php $this->endWidget(); ?>

</div><!-- form -->