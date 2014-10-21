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
	'VMProfiles'=>array('index'),
	'Manage',
);
$this->title = Yii::t('vmprofile', 'Manage VMProfiles');
//$this->helpurl = Yii::t('help', 'manageVMProfiles');

$gridid = 'vmprofiles';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';

Yii::app()->clientScript->registerScript('funcs', <<<EOS
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}, 'afterSubmit': function(response, postdata){
		var xml = response.response;
		var err = $(xml).find('error');
		err = err.text();
		if (0 != err) {
			return new Array(false, $(xml).find('message').text());
		}
		else {
			return new Array(true, '');
		}
	}});
}
EOS
, CClientScript::POS_END);

$detailurl = $this->createUrl('vmProfile/view');
$updateurl = $this->createUrl('vmProfile/update');
$deleteurl = $this->createUrl('vmProfile/delete');
$this->widget('ext.zii.CJqGrid', array(
	'extend'=>array(
		'id' => $gridid,
		'locale'=>'en',
		'pager'=>array(
			'Standard'=>array('edit'=>false, 'add' => false, 'del' => false, 'search' => false),
		),
		'filter' => array(
			'stringResult' => false,'searchOnEnter' => false
		),
	),
     'options'=>array(
		'pager'=>$gridid . '_pager',
		'url'=>$baseurl . '/vmProfile/getVmProfiles',
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array(Yii::t('vmprofile', 'No.'), 'DN', 'Arch', Yii::t('vmprofile', 'Name'), Yii::t('vmprofile', 'Architecture'), Yii::t('vmprofile', 'Language'), Yii::t('vmprofile', 'Description'), Yii::t('vmprofile', 'Action')),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>30,'align'=>'right','editable'=>false,'sortable' => false, 'search' =>  false),
			array('name'=>'dn','index'=>'dn','hidden'=>true),
			array('name'=>'vm','index'=>'vm','hidden'=>true),
			array('name'=>'name','index'=>'name','editable'=>false,'sortable' => false),
			array('name'=>'architecture','index'=>'architecture','width' => '90', 'fixed' => true, 'editable'=>false,'sortable' => false),
			array('name'=>'language','index'=>'language','width' => '60', 'fixed' => true, 'align'=>'center','sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'description','index'=>'description','width' => '200', 'fixed' => true, 'search' => false,'editable'=>false,'sortable' => false),
			array ('name' => 'act','index' => 'act','width' => 18 * 2, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
		),
		//'toolbar' => array(true, "top"),
		//'caption'=>'VMs',
		'autowidth'=>true,
		'rowNum'=>10,
		'rowList'=> array(10,20,30),
		'height'=>230,
		'altRows'=>true,
		'editurl'=>$deleteurl,
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				name = '<a href="${updateurl}?dn=' + row['dn'] + '&vm=' + row['vm'] + '">' + row['name'] + '</a>';
				var act = '';
				act += '<a href="${updateurl}?dn=' + row['dn'] + '&vm=' + row['vm'] + '"><img src="{$imagesurl}/vmprofile_edit.png" alt="" title="edit VM Profile" class="action" /></a>';
				act += '<img src="{$imagesurl}/vmprofile_del.png" style="cursor: pointer;" alt="" title="delete VM Profile" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				$('#{$gridid}_grid').setRowData(ids[i],{'name': name, 'act': act});
			}
		}
EOS
			),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));
?>
<div style="display: none;">
<a id="startcheck" href="#checkCopy">checkcopy</a>
<div id="checkCopy">
</div>
</div>
<?php
if (!is_null($copyaction)) {
	$checkCopyGuiUrl = $this->createUrl('vmProfile/getCheckCopyGui');
	Yii::app()->clientScript->registerScript('checkCopy', <<<EOS
	$('#startcheck').fancybox({
		'modal'			: false,
		'href'			: '{$checkCopyGuiUrl}?pid={$copyaction}',
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 450,
		'height'		: 120,
		'scrolling'		: 'no',
		'hideOnOverlayClick' : false,
		'onComplete'	: function() {
			check();
		},
		'onClosed'		: function () {
		}
	});
	function check() {
		$.ajax({
			url: "{$baseurl}/vmProfile/checkCopy",
			data: 'pid={$copyaction}',
			success: function(data) {
				if (!data['err']) {
					$('#running').css('display', 'none');
					$('#errorAssignment').css('display', 'none');
					$('#infoAssignment').css('display', 'block');
					$('#infoMsg').html(data['msg']);
				}
				else {
//					$('#infoAssignment').css('display', 'none');
//					$('#errorAssignment').css('display', 'block');
//					$('#errorMsg').html(data['msg']);
					timeoutid = setTimeout(check, 4000);
				}
				//$('#checkCopy').hide();
				//$.fancybox.close();
			},
			dataType: 'json'
		});
	}
	timeoutid = setTimeout("$('#startcheck').trigger('click')", 1000);
EOS
	, CClientScript::POS_END);
}

?>
<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>