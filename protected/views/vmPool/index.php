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
	'VM Pool'=>array('index'),
	'Manage',
);
$this->title = Yii::t('vmpool', 'Manage VM Pools');
//$this->helpurl = Yii::t('help', 'manageUser');

$gridid = 'vmpool';
$baseUrl = Yii::app()->baseUrl;
$imagesUrl = $baseUrl . '/images';
$getPoolUrl = $this->createUrl('vmPool/getVmPools');
$updateUrl = $this->createUrl('vmPool/update');
$deleteUrl = $this->createUrl('vmPool/delete');
$viewNodeUrl = $this->createUrl('node/view');
$updateStoragePoolUrl = $this->createUrl('storagePool/update');

$poolEdit = Yii::app()->user->hasRight('vmPool', 'Edit', 'All') ? 'true' : 'false';
$poolDelete = Yii::app()->user->hasRight('vmPool', 'Delete', 'All') ? 'true' : 'false';
$nodeView = Yii::app()->user->hasRight('node', 'View', 'All') ? 'true' : 'false';
$storagePoolEdit = Yii::app()->user->hasRight('storagePool', 'Edit', 'All') ? 'true' : 'false';
$userManage = Yii::app()->user->hasRight('user', 'Manage', 'All') ? 'true' : 'false';
$groupManage = Yii::app()->user->hasRight('group', 'Manage', 'All') ? 'true' : 'false';

$savetxt = Yii::t('vmpool', 'Save');

Yii::app()->clientScript->registerScript('javascript', <<<EOS
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}});
}
EOS
, CClientScript::POS_END);

$userGuiUrl = $this->createUrl('vmPool/getUserGui');
$savetxt = Yii::t('vm', 'Save');
$saveUserAssignUrl = $this->createUrl('vmPool/saveUserAssign');

Yii::app()->clientScript->registerScript('assignUser', <<<EOS
function assignUser(dn)
{
	$('#startuser').fancybox({
		'modal'			: false,
		'href'			: '{$userGuiUrl}?dn=' + dn,
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 600,
		'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#userAssignment_dualselect').dualselect({
				'sorted':true,
				'leftHeader':'Users',
				'rightHeader':'Assigned users'
			});
			$('#saveUserAssignment').button({icons: {primary: "ui-icon-disk"}, label: '{$savetxt}'})
			.click(function() {
				var selected = $('#userAssignment_dualselect').dualselect("values");
				var a = selected.length;
				$.ajax({
					url: "{$saveUserAssignUrl}",
					data: 'users=' + selected + '&dn=' + dn,
					success: function(data) {
						if (data['err']) {
							$('#infoUserAssignment').css('display', 'none');
							$('#errorUserAssignment').css('display', 'block');
							$('#errorUserMsg').html(data['msg']);
						}
						else {
							$('#errorUserAssignment').css('display', 'none');
							$('#infoUserAssignment').css('display', 'block');
							$('#infoUserMsg').html(data['msg']);
						}
						//$('#assignUser').hide();
						//$.fancybox.close();
					},
					dataType: 'json'
				});
			});
		},
		'onClosed'	: function() {
			$('#assignUser').hide();
		}
	});
	$('#startuser').trigger('click');
}
EOS
, CClientScript::POS_END);

$groupGuiUrl = $this->createUrl('vmPool/getGroupGui');
$savetxt = Yii::t('vm', 'Save');
$saveGroupAssignUrl = $this->createUrl('vmPool/saveGroupAssign');

Yii::app()->clientScript->registerScript('assignGroup', <<<EOS
function assignGroup(dn)
{
	$('#startgroup').fancybox({
		'modal'			: false,
		'href'			: '{$groupGuiUrl}?dn=' + dn,
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 600,
		'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#groupAssignment_dualselect').dualselect({
				'sorted':true,
				'leftHeader':'Groups',
				'rightHeader':'Assigned groups'
			});
			$('#saveGroupAssignment').button({icons: {primary: "ui-icon-disk"}, label: '{$savetxt}'})
			.click(function() {
				var selected = $('#groupAssignment_dualselect').dualselect("values");
				var a = selected.length;
				$.ajax({
					url: "{$saveGroupAssignUrl}",
					data: 'groups=' + selected + '&dn=' + dn,
					success: function(data) {
						if (data['err']) {
							$('#infoGroupAssignment').css('display', 'none');
							$('#errorGroupAssignment').css('display', 'block');
							$('#errorGroupMsg').html(data['msg']);
						}
						else {
							$('#errorGroupAssignment').css('display', 'none');
							$('#infoGroupAssignment').css('display', 'block');
							$('#infoGroupMsg').html(data['msg']);
						}
						//$('#assignGroup').hide();
						//$.fancybox.close();
					},
					dataType: 'json'
				});
			});
		},
		'onClosed'	: function() {
			$('#assignGroup').hide();
		}
	});
	$('#startgroup').trigger('click');
}
EOS
, CClientScript::POS_END);

if (Yii::app()->user->hasRight('vmPool', 'Edit', 'All')) {
	$displayname = '\'<a href="' . $updateUrl . '?dn=\' + row[\'dn\'] + \'">\' + row[\'name\'] + \'</a>\'';
}
else {
	$displayname = 'row[\'name\']';
}

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
		'colNames'=>array('No.', 'hasVms', 'DN', 'Type', 'Name', 'Description', 'NodesDN', 'Nodes', 'StoragePoolDN', 'StoragePool', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'hasVms','index'=>'hasVms','hidden'=>true,'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'type','index'=>'type','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'sstDisplayName','editable'=>false),
			array('name'=>'description','index'=>'description','editable'=>false, 'sortable' => false, 'search' =>  false),
			array('name'=>'nodesdn','index'=>'nodesdn','hidden'=>true,'editable'=>false),
			array('name'=>'nodes','index'=>'nodes','editable'=>false, 'sortable' => false, 'search' =>  false),
			array('name'=>'storagepooldn','index'=>'storagepooldn','hidden'=>true,'editable'=>false),
			array('name'=>'storagepool','index'=>'storagepool','editable'=>false, 'sortable' => false, 'search' =>  false),
			array ('name' => 'act','index' => 'act','width' => 18 * 4, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
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
				
				var nodes = '';
				var names = row['nodes'].split('|');
				var dns = row['nodesdn'].split('|');
				for (var j=0; j<names.length; j++) {
					if ('' != nodes) {
						nodes += '<br/>';
					}
					if ({$nodeView}) {
						nodes += '<a href="{$viewNodeUrl}?dn=' + dns[j] + '">' + names[j] + '</a>';
					}
					else {
						nodes += names[j];
					}
				}
				var storagepool;
				if ({$storagePoolEdit}) {
					storagepool = '<a href="{$updateStoragePoolUrl}?dn=' + row['storagepooldn'] + '">' + row['storagepool'] + '</a>';
				}
				else {
					storagepool = row['storagepool'];
				}
				var act = '';
				if (${poolEdit}) {
					act += '<a href="${updateUrl}?dn=' + row['dn'] + '"><img src="{$imagesUrl}/vmpool_edit.png" alt="" title="edit VM Pool" class="action" /></a>';
				}
				else {
					act += '<img src="{$imagesUrl}/vmpool_edit.png" alt="" title="" class="action notallowed" /></a>';
				}
				if ('true' == row['hasVms'] || !${poolDelete}) {
					act += '<img src="{$imagesUrl}/vmpool_del.png" alt="" title="" class="action notallowed" />';
				}
				else {
					act += '<img src="{$imagesUrl}/vmpool_del.png" style="cursor: pointer;" alt="" title="delete VM Pool" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				}
				if ('dynamic' == row['type'] || 'persistent' == row['type']) {
					if ({$userManage}) {
						act += '<img src="{$imagesUrl}/vmuser_add.png" style="cursor: pointer;" alt="" title="assign user to VM Pool" class="action" onclick="assignUser(\'' + row['dn'] + '\');" />';
					}
					else {
						act += '<img src="{$imagesUrl}/vmuser_add.png" alt="" title="" class="action notallowed" />';
					}
					if ({$groupManage}) {
						act += '<img src="{$imagesUrl}/vmgroup_add.png" style="cursor: pointer;" alt="" title="assign groups to VM Pool" class="action" onclick="assignGroup(\'' + row['dn'] + '\');" />';
					}
					else {
						act += '<img src="{$imagesUrl}/vmgroup_add.png" alt="" title="" class="action notallowed" />';
					}
				}
				$('#{$gridid}_grid').setRowData(ids[i],{'name': name, 'nodes': nodes, 'storagepool': storagepool, 'act': act});
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
<a id="startuser" href="#assignUser">start user</a>
<div id="assignUser">
</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqDualselect', array(
		'id' => 'userAssignment',
		'values' => array(),
		'size' => 5,
		'options' => array(
			'sorted' => true,
			'leftHeader' => Yii::t('vm', 'Users'),
			'rightHeader' => Yii::t('vm', 'Assigned users'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'dualselect.css',
	));
?>
<div style="display: none;">
<a id="startgroup" href="assignGroup">start group</a>
<div id="assignGroup">
</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqDualselect', array(
		'id' => 'groupAssignment',
		'values' => array(),
		'size' => 5,
		'options' => array(
			'sorted' => true,
			'leftHeader' => Yii::t('vm', 'Groups'),
			'rightHeader' => Yii::t('vm', 'Assigned groups'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'dualselect.css',
	));

$this->createWidget('ext.fancybox.EFancyBox');
