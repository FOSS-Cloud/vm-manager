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
	'Nodes'=>array('index'),
	'Manage',
);
$this->title = Yii::t('node', 'Manage Nodes');
//$this->helpurl = Yii::t('help', 'manageNodes');

if(Yii::app()->user->hasFlash('notice')) {
	echo CHtml::tag('div',array('class'=>'flash-error'),Yii::app()->user->getFlash('notice'));
}

$gridid = 'nodes';
$baseurl = Yii::app()->baseUrl;

$nodeEdit = Yii::app()->user->hasRight('node', 'Edit', 'All') ? 'true' : 'false';
$nodeDelete = Yii::app()->user->hasRight('node', 'Delete', 'All') ? 'true' : 'false';

Yii::app()->clientScript->registerScript('rowEdit', <<<EOS
var {$gridid}_lastsel=-1;
function editRow(id)
{
	if(id && id != {$gridid}_lastsel)
	{
		$('#{$gridid}_grid').restoreRow({$gridid}_lastsel);
		{$gridid}_lastsel=id;
	}
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').editRow(id,true,null,null,null,{"dn" : row['dn']},afterSaveRow);
}
function afterSaveRow(id,data) {
	var xml = data.responseXML;
	var err = $(xml).find('error');
	err = err.text();
	if (0 == err) {
		$('#{$gridid}_grid').setRowData(id,{'dn': $(xml).find('dn').text()});
	}
	else {
		alert($(xml).find('message').text());
	}
}
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
function maintainRow(id, maintain)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$.ajax({
		url: "{$baseurl}/node/maintainVmNode",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'] + '&maintain=' + maintain,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
  		}
	});
	$('#{$gridid}_grid').trigger("reloadGrid");
}
EOS
, CClientScript::POS_END);

$imagesurl = Yii::app()->baseUrl . "/images";
$vmurl = $this->createUrl('vm/index');
$detailurl = $this->createUrl('node/view');
$deleteurl = $this->createUrl('node/delete');
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
		'url'=>Yii::app()->baseUrl . '/node/getNodes',
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.','DN', 'Node', 'State', 'VmNodeMaintain', 'IP', 'VM Pools', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>30,'align'=>'right','editable'=>false,'resizable'=>false,'sortable'=>false,'search'=>false,'classes'=>'valigntop'),
			array('name'=>'dn','index'=>'dn','hidden'=>true),
			array('name'=>'node','index'=>'sstNode',/*'width'=>300,*/'editable'=>false,'sortable'=>true,'search'=>true,'classes'=>'valigntop'),
			array('name'=>'status','index'=>'status','editable'=>false,'sortable'=>true,'search'=>false,'classes'=>'valigntop'),
			array('name'=>'vmnodemaintain','index'=>'vmnodemaintain','editable'=>false,'sortable'=>true,'search'=>false,'hidden'=>true),
			array('name'=>'ip','index'=>'ip','width'=>80,'editable'=>false,'sortable'=>false,'search'=>false,'classes'=>'valigntop'),
			array('name'=>'vmpools','index'=>'vmpools','editable'=>false,'sortable'=>false,'search'=>false,'classes'=>'valigntop'),
			array ('name' => 'act','index' => 'act','width' => 18 * 3,'editable'=>false,'resizable'=>false,'sortable'=>false,'search' =>false,'classes'=>'valigntop')
		),
		'sortname'=>'sstNode',
		'sortorder'=>'asc',
		//'toolbar' => array(true, "top"),
		//'caption'=>'Nodes',
		'autowidth'=>true,
		'height'=>200,
		'altRows'=>true,
		//'gridview' => true,
		'editurl'=>$deleteurl,
		'ajaxRowOptions' => array('dataType' => 'xml'),
		'ondblClickRow'=> 'js:editRow',
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				var act = '<a href="{$detailurl}?dn=' + row['dn'] + '"><img src="{$imagesurl}/node_detail.png" alt="" title="view Node" class="action" /></a>';
				if ('' == row['vms'] && {$nodeDelete})
				{
					act += '<img id="node_del_' + ids[i] + '" src="{$imagesurl}/node_del.png" alt="" title="delete Node" class="action" />';
				}
				else
				{
					act += '<img src="{$imagesurl}/node_del.png" alt="" title="delete Node" class="action notallowed" />';
				}
				if ('false' == row['vmnodemaintain']) {
					act += '<img id="node_maintain_' + ids[i] + '" src="{$imagesurl}/node_maintain.png" alt="" title="put Node in maintenance" class="action notallowed" />';
				}
				else if ('true' == row['vmnodemaintain']) {
					act += '<img id="node_maintain_' + ids[i] + '" src="{$imagesurl}/node_active.png" alt="" title="end maintenance" class="action notallowed" />';
				}
				$('#{$gridid}_grid').setRowData(ids[i],{'act': act});

				if ('' == row['vmpools'] && {$nodeDelete})
				{
					$('#node_del_' + ids[i]).css({cursor: 'pointer'});
					$('#node_del_' + ids[i]).click({did: ids[i]}, function(event) {event.stopPropagation(); deleteRow(event.data.did);});
				}
				if ({$nodeEdit}) {
					if ('false' == row['vmnodemaintain']) {
						$('#node_maintain_' + ids[i]).css({cursor: 'pointer'}).removeClass('notallowed');
						$('#node_maintain_' + ids[i]).click({did: ids[i]}, function(event) {event.stopPropagation(); maintainRow(event.data.did, true);});
					}
					else if ('true' == row['vmnodemaintain']) {
						$('#node_maintain_' + ids[i]).css({cursor: 'pointer'}).removeClass('notallowed');
						$('#node_maintain_' + ids[i]).click({did: ids[i]}, function(event) {event.stopPropagation(); maintainRow(event.data.did, false);});
					}
				}
			}
		}
EOS

	),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));
?>
