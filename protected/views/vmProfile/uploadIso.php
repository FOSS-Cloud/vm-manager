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

$this->breadcrumbs=array(
	'VmProfile'=>array('index'),
	'UploadIso',
);
$this->title = Yii::t('vmprofile', 'Upload Iso File');
//$this->helpurl = Yii::t('help', 'uploadIso');

?>
<div class="form">
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'isoupload-form',
	'enableAjaxValidation'=>true,
	'method' => 'post',
	'clientOptions' => ($upstatus ? array(
		'validateOnSubmit' => true,
		'afterValidate' => 'js:' . <<<EOS
function(form, data, hasError) {
	if (!hasError) {
		$('#errormessage').html('');
		$('#uploadinfo').slideDown(400);
    	startTime = new Date();
		stopTime = null;
		infoUpdated = 0;
		requestInfo();
		return true;
	}
	return false;
}
EOS
	) : array(
		'validateOnSubmit' => true,
	)),
	'htmlOptions' => ($upstatus ? array(
		'target' => 'formsubmit',
		'enctype' => 'multipart/form-data',
	) : array()),
));
if ($upstatus) {
	$uploadId = /*Yii::app()->getSession()->getSessionID()*/ 'osbd_' . md5(microtime() . rand());
	echo CHtml::hiddenField('UPLOAD_IDENTIFIER', $uploadId);
}
echo $form->hiddenField($model, 'upstatus');

?>
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	<div id="errormessage" class="errorMessage">
		<?php echo $form->errorSummary($model); ?>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model,'isofile');?>
<?php
if ($upstatus) {
	//echo $form->fileField($model,'isofile',array('size'=>40));
?>
			<input size="40" name="VmIsoUploadForm[isofile]" id="VmIsoUploadForm_isofile" type="file" />
			<div id="uploadinfo" style="display: none;">
<?php
		$this->widget('zii.widgets.jui.CJuiProgressBar', array(
			'id'=>'isoprogress',
		    'value'=>0,
		//	'options' => array(
		//		'disabled' => true,
		//	),
			'options'=>array(
				'create' => 'js:function(event, ui) {$("#isoprogress .ui-progressbar-value").css(\'text-align\', \'center\'); }',
				'change' => 'js:function(event, ui) {$("#isoprogress .ui-progressbar-value").html($("#isoprogress").progressbar( "option", "value" ) + "%");}',
			),
		    'htmlOptions'=>array(
		        'style'=>'height:16px; width: 400px; margin-top: 7px; display: block;'
		    ),
			'themeUrl' => $this->cssBase . '/jquery',
			'theme' => 'osbd',
		    'cssFile' => 'jquery-ui.custom.css',
		));

	$requestInfoUrl = $this->createUrl('vmProfile/requestInfo', array('upid'=>$uploadId));
	$sizestr = Yii::t('vmprofile', 'Uploading... ({uploaded} of {total})');
	$finishedstr = Yii::t('vmprofile', 'Upload finished ({total})');

	Yii::app()->clientScript->registerScript('upload', <<<EOS
    var startTime = null;
    var stopTime = null;
    var upload_max_filesize = 1073741824;
    var infoUpdated = 0;
    var cylceid;
    function requestInfo() {
		infoUpdated++;
		//$('#refresh').attr("src", "{$requestInfoUrl}&"+(new Date()).getTime());
		$.ajax({
			url: "{$requestInfoUrl}",
			success: function(data) {
				if (1 == infoUpdated) {
					$('#submit').attr('disabled', 'disabled');
				}
				update(data['total'], data['uploaded'], data['percent'], data['estimated']);
			},
			dataType: 'json'
		});
	}
	function update(total, uploaded, percent, estimated) {
		if (stopTime == null) {
			$('#isoprogress').progressbar("option", "value", percent);
			size = '{$sizestr}';
			size = size.replace('{uploaded}', uploaded);
			size = size.replace('{total}', total);
			$('#isosize').html(size);
			$('#isoestimated').html(estimated + " seconds");
			cycleid = setTimeout(requestInfo, 10000);
		}
	}
	function finished(total) {
		stopTime = new Date();
		$('#isoprogress').progressbar( "option", "value", 100 );
		size = '{$finishedstr}';
		size = size.replace('{total}', total);
		$('#isosize').html(size);
		$('#isoestimated').html("took " + round((stopTime.getTime() - startTime.getTime()) / 1000) + " seconds");
		clearTimeout(cycleid);
		$('#submit').removeAttr('disabled');
	}
	function error(message) {
		$('#errormessage').html(message);
		clearTimeout(cycleid);
		$('#submit').removeAttr('disabled');
	}
EOS
	, CClientScript::POS_END);

?>
				<div id="isosize" style="float: left;">Uploading... (??? MB of ??? MB)</div>
				<div id="isoestimated" style="float: right;">??? sec.</div>
				<br class="clear" />
			</div>
<?php
}
?>
		<?php echo $form->error($model,'isofile'); ?>
	</div>
	<br/>
	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name', array('size'=>40)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>
	<br class="clear"/>
	<div class="row buttons">
		<?php echo CHtml::submitButton(Yii::t('vmprofile','Upload'),($upstatus ? array('id' => 'submit') : array('id' => 'submit'))); ?>
	</div>
	<br class="clear"/>
<?php
	if ($upstatus) {
?>
	<iframe name="formsubmit" id="formsubmit" style="float: right; width: 500px; height: 300px; border: 1px solid green; display: none;"></iframe>
<?php } ?>
<?php $this->endWidget(); ?>

</div><!-- form -->