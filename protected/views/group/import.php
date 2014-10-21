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

$this->breadcrumbs=array(
	'Groups'=>array('index'),
	'Import',
);

$this->title = Yii::t('group', 'Import Groups');
?>
<div class="form">
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'import-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => array(
		'validateOnSubmit' => true,
	),
));
echo CHtml::hiddenField('ForeignGroupForm[displayName]', $displayName);
echo CHtml::hiddenField('ForeignGroupForm[staticAttrName]', $staticAttrName);

	?>
	<p class="note">Fields with <span class="required">*</span> are required if row is selected.</p>
	<table>
		<tr><th></th><th colspan="2">foreign</th><th colspan="2" style="border-left: 1px solid lightgrey;">local</th></tr>
		<tr><th></th><th>unique attribute<br/>(<?php echo $staticAttrName;?>)</th><th>group name<br/>(<?php echo $displayName;?>)</th><th style="border-left: 1px solid lightgrey;">saved<br/>group name</th><th>display name <span class="required">*</span></th></tr>
		<?php foreach($items as $i=>$item): ?>
		<?php //echo '<pre>' . print_r($item, true) . '</pre>';?>
		<?php $style = $item->diffName ? 'color: orange;' : ''?>
		<tr>
			<td style="nowrap">
				<?php echo $form->checkBox($item,"[items][$i]selected"); ?>
				<?php echo $item->found ? '<img src="' . Yii::app()->baseUrl . '/images/group_found.png' . '" title="group found"/>' : ''?>
			</td>
			<td><?php echo $item->static; echo $form->hiddenField($item,"[items][$i]static");?></td>
			<td style="<?php echo $style; ?>"><?php echo $item->name; echo $form->hiddenField($item,"[items][$i]name");?></td>
			<td style="<?php echo $style; ?>border-left: 1px solid lightgrey;"><?php  echo ('' !== $item->savedName ?  $item->savedName : '&nbsp;');?></td>
			<td><?php echo $form->textField($item,"[items][$i]local", array('size' => 20));
				echo $form->hiddenField($item,"[items][$i]found");  
				echo $form->hiddenField($item,"[items][$i]diffName");
				echo $form->hiddenField($item,"[items][$i]savedName");
				?>
			</td>
		</tr>
		<?php if ('' !== $item->message) : ?>
		<tr><td  colspan="2" style="padding: 0 10px 0 5px;">&nbsp;</td><td colspan="3" style="color: red;padding: 0 10px 0 5px;"><i><?php echo $item->message;?></i></td></tr>
		<?php endif;?>
	<?php endforeach; ?>
	</table>
	<div style="clear: both;" class="row buttons">
		<?php echo CHtml::submitButton($submittext, array('id' => 'submit')); ?>
	</div>
<?php $this->endWidget(); ?>

</div><!-- form -->