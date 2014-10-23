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
	'Storage Pool'=>array('index'),
	'Manage',
);
$this->title = Yii::t('storagepool', 'Manage Storage Pools');
//$this->helpurl = Yii::t('help', 'manageUser');

$gridid = 'storagepool';
$baseUrl = Yii::app()->baseUrl;
$imagesUrl = $baseUrl . '/images';
$getPoolUrl = $this->createUrl('storagePool/getStoragePools');
$updateUrl = $this->createUrl('storagePool/update');
$deleteUrl = $this->createUrl('storagePool/delete');

$savetxt = Yii::t('storagepool', 'Save');

$poolEdit = Yii::app()->user->hasRight('storagePool', 'Edit', 'All') ? 'true' : 'false';
$poolDelete = Yii::app()->user->hasRight('storagePool', 'Delete', 'All') ? 'true' : 'false';

Yii::app()->clientScript->registerScript('javascript', <<<EOS
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}});
}
EOS
, CClientScript::POS_END);

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
		'url'=>$getPoolUrl,
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'hasVmPools', 'DN', 'Name', 'Description', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>30,'align'=>'right','editable'=>false,'resizable'=>false,'sortable'=>false,'search'=>false,'classes'=>'valigntop'),
			array('name'=>'hasVmPools','index'=>'hasVmPools','hidden'=>true,'editable'=>false,'classes'=>'valigntop'),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false,'classes'=>'valigntop'),
			array('name'=>'name','index'=>'sstDisplayName','editable'=>false,'classes'=>'valigntop'),
			array('name'=>'description','index'=>'description','editable'=>false, 'sortable' => false, 'search' =>  false,'classes'=>'valigntop'),
			array ('name' => 'act','index' => 'act','width' => 18 * 2, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false,'classes'=>'valigntop'),
		),
		'autowidth'=>true,
		'rowNum'=>10,
		'rowList'=> array(10,20,30),
		'height'=>230,
		'altRows'=>true,
		'editurl'=>$deleteUrl,
//		'subGrid' => false,
//		'subGridUrl' =>$baseUrl . '/user/getUserInfo',
//      	'subGridRowExpanded' => 'js:' . <<<EOS
//      	function(pID, id) {
//      		$('#{$gridid}_grid_' + id).html('<img src="{$imagesUrl}/loading.gif"/>');
//      		var row = $('#{$gridid}_grid').getRowData(id);
//			$.ajax({
//				url: "{$baseUrl}/user/getUserInfo",
//				cache: false,
//				data: "dn=" + row['dn'] + "&rowid=" + id,
//				success: function(html){
//					$('#{$gridid}_grid_' + id).html(html);
//  				}
//			});
//      	}
//EOS
//,
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				var name;
				if (${poolEdit}) {
					name = '<a href="${updateUrl}?dn=' + row['dn'] + '">' + row['name'] + '</a>';
				}
				else {
					name = row['name'];
				}
				var act = '';
				if (${poolEdit}) {
					act += '<a href="${updateUrl}?dn=' + row['dn'] + '"><img src="{$imagesUrl}/storagepool_edit.png" alt="" title="edit Storage Pool" class="action" /></a>';
				}
				else {
					act += '<img src="{$imagesUrl}/storagepool_edit.png" alt="" title="" class="action notallowed" /></a>';
				}
				if ('true' == row['hasVmPools'] || !${poolDelete}) {
					act += '<img src="{$imagesUrl}/storagepool_del.png" alt="" title="" class="action notallowed" />';
				}
				else {
					act += '<img src="{$imagesUrl}/storagepool_del.png" style="cursor: pointer;" alt="" title="delete Storage Pool" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				}
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