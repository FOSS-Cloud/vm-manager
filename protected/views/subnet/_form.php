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
	'id'=>'subnet-form',
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
?>
		<div class="column">
			<div class="row">
				<?php echo $form->labelEx($model, 'ip'); ?>
				<?php echo $form->textField($model,'ip',array('size'=>20, 'style'=>"float: left;")); ?>
				<br/><br/>
				<?php echo $form->error($model,'ip'); ?>
			</div>
		</div>
		<div class="column span-5">
			<div class="row">
				<?php echo $form->labelEx($model, 'netmask'); ?>
				<?php echo $form->dropDownList($model,'netmask', $netmasks, array('prompt'=>'', 'style'=>"float: left; margin-right: 4px;")); ?>
				<br/><br/>
				<?php echo $form->error($model,'netmask'); ?>
			</div>
		</div>
		<br class="clear"/>
		<div class="row">
			<?php echo $form->labelEx($model,'name'); ?>
			<?php echo $form->textField($model,'name',array('size'=>20)); ?>
			<?php echo $form->error($model,'name'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'domainname'); ?>
			<?php echo $form->textField($model,'domainname',array('size'=>20)); ?>
			<?php echo $form->error($model,'domainname'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'domainservers'); ?>
			<?php echo $form->textField($model,'domainservers',array('size'=>20)); ?>
			<?php echo $form->error($model,'domainservers'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'defaultgateway'); ?>
			<?php echo $form->textField($model,'defaultgateway',array('size'=>20)); ?>
			<?php echo $form->error($model,'defaultgateway'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'broadcastaddress'); ?>
			<?php echo $form->textField($model,'broadcastaddress',array('size'=>20)); ?>
			<?php echo $form->error($model,'broadcastaddress'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'ntpservers'); ?>
			<?php echo $form->textField($model,'ntpservers',array('size'=>20)); ?>
			<?php echo $form->error($model,'ntpservers'); ?>
		</div>
		<br/>
		<div class="row buttons">
			<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
		</div>
	</div>
	<br class="clear"/>
<?php $this->endWidget(); ?>

</div><!-- form -->