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
	'id'=>'user-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));
	echo $form->hiddenField($model, 'dn');
//	echo $form->hiddenField($model, 'language');
	?>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div class="column" style="padding: 5px;">
		<div class="row">
			<?php echo $form->labelEx($model,'givenname'); ?>
			<?php echo $form->textField($model,'givenname',array('size'=>30)); ?>
			<?php echo $form->error($model,'givenname'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'surname'); ?>
			<?php echo $form->textField($model,'surname',array('size'=>30)); ?>
			<?php echo $form->error($model,'surname'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'telephone'); ?>
			<?php echo $form->textField($model,'telephone',array('size'=>20)); ?>
			<?php echo $form->error($model,'telephone'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'mobile'); ?>
			<?php echo $form->textField($model,'mobile',array('size'=>20)); ?>
			<?php echo $form->error($model,'mobile'); ?>
		</div>
	</div>
	<div class="column" style="background-color: #FAF9F4; padding: 5px;">
		<div class="row">
			<?php echo $form->labelEx($model,'mail'); ?>
			<?php echo $form->textField($model,'mail',array('size'=>30)); ?>
			<?php echo $form->error($model,'mail'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'username'); ?>
			<?php echo $form->textField($model,'username',array('size'=>30, 'autocomplete' => 'off')); ?>
			<?php echo $form->error($model,'username'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'password'); ?>
			<?php echo $form->passwordField($model,'password',array('size'=>30)); ?>
			<?php echo $form->error($model,'password'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'passwordcheck'); ?>
			<?php echo $form->passwordField($model,'passwordcheck',array('size'=>30)); ?>
			<?php echo $form->error($model,'passwordcheck'); ?>
		</div>
	</div>
	<div class="column last" style="padding: 5px;">
		<div class="row" style="">
			<?php echo $form->labelEx($model,'gender'); ?>
			<?php echo $form->radioButtonList($model,'gender', LdapUser::getGender(), array('separator' => '', 'style' => 'clear: none; float: left; margin-right: 6px; margin-top: 8px; margin-bottom: 6px;' , 'labelOptions' => array('style' => 'float: left; margin: 8px 20px 6px 0;'))); ?>
			<?php echo $form->error($model,'gender', array('style' => 'clear: both;')); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'language'); ?>
<?php
	$options = array();
	$languages = array();
	foreach(LdapUser::getLanguages() as $key => $name) {
		$languages[$key] = $name;
		$options[$key] = array(); //array('style' => 'background: url(' . Yii::app()->baseUrl . '/images/lang/' . $key . '.png) no-repeat scroll 1px 2px transparent; padding-left: 20px;');
	}
	//print_r($languages);
?>
			<?php echo $form->dropDownList($model,'language', $languages, array('prompt' => '', 'options' => $options, 'encode' => false, /*'style' => 'background: url(' . Yii::app()->baseUrl . '/images/lang/' . $key . '.png) no-repeat scroll 1px 2px transparent; padding-left: 20px;'*/)); ?>
			<?php echo $form->error($model,'language'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'userrole'); ?>
			<?php echo $form->radioButtonList($model,'userrole', $userroles, array('separator' => '', 'style' => 'clear: none; float: left; margin-right: 6px; margin-top: 8px; margin-bottom: 6px;' , 'labelOptions' => array('style' => 'float: left; margin: 8px 20px 6px 0;'))); ?>
			<?php echo $form->error($model,'userrole'); ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model,'usergroups'); ?>
			<?php echo $form->checkBoxList($model,'usergroups', $usergroups, array('separator' => '<br/>', 'style' => 'clear: both; float: left; margin-right: 6px; margin-top: 8px; margin-bottom: 6px;' , 'labelOptions' => array('style' => 'float: left; margin: 8px 20px 6px 0;'))); ?>
			<?php echo $form->error($model,'usergroups'); ?>
		</div>
	</div>
		<div style="clear: both;" class="row buttons">
			<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
		</div>
<?php $this->endWidget(); ?>

</div><!-- form -->