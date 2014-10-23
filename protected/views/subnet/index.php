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
	'Subnets'=>array('index'),
	'Manage',
);
$this->title = Yii::t('subnet', 'Manage Subnets');
//$this->helpurl = Yii::t('help', 'manageSubnets');

$gridid = 'subnets';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';
$deleteurl = $this->createUrl('subnet/delete');
$updateurl = $this->createUrl('subnet/update');
$addrangeurl = $this->createUrl('subnet/createRange');
$updaterangeurl = $this->createUrl('subnet/updateRange');

$networkEdit = Yii::app()->user->hasRight('network', 'Edit', 'All') ? 'true' : 'false';
$networkDelete = Yii::app()->user->hasRight('network', 'Delete', 'All') ? 'true' : 'false';
$networkCreate = Yii::app()->user->hasOtherRight('network', 'Create', 'None') ? 'true' : 'false';

Yii::app()->clientScript->registerScript('actions', <<<EOS
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn'], 'level': row['level']}});
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
		'url'=>$baseurl . '/subnet/getSubnets',
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('ID', 'isUsed', 'DN', 'Range', 'Type / Name', 'Tooltip', 'Min', 'Max', 'Action'),
		'colModel'=>array(
			array('name'=>'id','index'=>'id','align'=>'right','hidden'=>true, 'sortable' => false, 'search' =>  false, 'editable'=>false, 'key'=>true),
			array('name'=>'isUsed','index'=>'isUsed','hidden'=>true,'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'range','index'=>'range','sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'type','index'=>'type', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'tooltip','index'=>'tooltip','hidden'=>true, 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'min','index'=>'min','editable'=>false, 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'max','index'=>'max','editable'=>false, 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array ('name' => 'act','index' => 'act','width' => 18 * 4, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
			// 'width' => 124
		),
		'autowidth'=>true,
//		'rowNum'=>20,
//		'rowList'=> array(10,20,30),
		'height'=>300,
		'altRows'=>true,
		'editurl'=>$deleteurl,
		'treeGrid'=>true,
		'treeGridModel'=>'adjacency',
		'ExpandColumn'=>'range',
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				var range = row['range'];
				var act = '';
				if (2 > row['level'])
				{
					if (0 == row['level'])
					{
						if ('true' !== row['isUsed']) {
							if ({$networkEdit}) {
								range = '<a href="${updateurl}?dn=' + row['dn'] + '">' + row['range'] + '</a>';
								act += '<a href="${updateurl}?dn=' + row['dn'] + '"><img src="${imagesurl}/subnet_edit.png" alt="" title="edit Subnet" class="action" /></a>';
							}
							else {
								range = row['range'];
								act += '<img src="${imagesurl}/subnet_edit.png" alt="" title="" class="action notallowed" /></a>';
							}
							if ({$networkDelete}) {
								act += '<img src="${imagesurl}/subnet_del.png" style="cursor: pointer;" alt="" title="delete Subnet" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
							}
							else {
								act += '<img src="${imagesurl}/subnet_del.png" alt="" title="" class="action notallowed" />';
							}
						}
						else {
							range = row['range'];
							act += '<img src="${imagesurl}/subnet_edit.png" alt="" title="" class="action notallowed" /></a>';
							act += '<img src="${imagesurl}/subnet_del.png" alt="" title="" class="action notallowed" />';
						}
						if ({$networkCreate}) {
							act += '<a href="${addrangeurl}?dn=' + row['dn'] + '"><img src="${imagesurl}/subnet_add.png" alt="" title="add Range" class="action" /></a>';
						}
						else {
							act += '<img src="${imagesurl}/subnet_add.png" alt="" title="add Range" class="action notallowed" />';
						}
					}
					else
					{
						if ('true' !== row['isUsed']) {
							if ({$networkEdit}) {
								range = '<a href="${updaterangeurl}?dn=' + row['dn'] + '">' + row['range'] + '</a>';
								act += '<a href="${updaterangeurl}?dn=' + row['dn'] + '"><img src="${imagesurl}/subnet_edit.png" alt="" title="edit Range" class="action" /></a>';
							}
							else {
								range = row['range'];
								act += '<img src="${imagesurl}/subnet_edit.png" alt="" title="" class="action notallowed" /></a>';
							}
							if ({$networkDelete}) {
								act += '<img src="{$imagesurl}/subnet_del.png" style="cursor: pointer;" alt="" title="delete Range" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
							}
							else {
								act += '<img src="${imagesurl}/subnet_del.png" alt="" title="" class="action notallowed" />';
							}
						}
						else {
							range = row['range'];
							act += '<img src="${imagesurl}/subnet_edit.png" alt="" title="" class="action notallowed" /></a>';
							act += '<img src="${imagesurl}/subnet_del.png" alt="" title="" class="action notallowed" />';
						}
					}
				}
				$('#{$gridid}_grid').setRowData(ids[i],{'range': range, 'act': act});
				/*$('#{$gridid}_grid').setCell(ids[i], 'type', row['type'], '', {title: row['tooltip']});*/
			}
		}
EOS
	),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));
