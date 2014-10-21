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
	'id'=>'group-form',
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
		<div class="row">
			<?php echo $form->labelEx($model,'sstDisplayName'); ?>
			<?php echo $form->textField($model,'sstDisplayName',array('size'=>30)); ?>
			<?php echo $form->error($model,'sstDisplayName'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'sstGroupName'); ?>
			<?php 	echo $form->textField($model, 'sstGroupName',array('disabled'=>"disabled")) . '<span style="font-size: 70%;"> (readonly)</span><br/>'; ?>
		</div>
	</div>
	<div style="clear: both;" class="row buttons">
		<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
	</div>
<?php $this->endWidget(); ?>

</div><!-- form -->