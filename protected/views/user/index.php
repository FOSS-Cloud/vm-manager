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
	'User'=>array('index'),
	'Manage',
);
$this->title = Yii::t('user', 'Manage User');
//$this->helpurl = Yii::t('help', 'manageUser');

$gridid = 'user';
$baseUrl = Yii::app()->baseUrl;
$imagesUrl = $baseUrl . '/images';
$getUserUrl = $this->createUrl('user/getUser');
$vmsGuiUrl = $this->createUrl('user/getVMsGui');
$saveAssignUrl = $this->createUrl('user/saveVMsAssign');
$getRolesUrl = $this->createUrl('user/getRoles');
$updateUrl = $this->createUrl('user/update');
$deleteUrl = $this->createUrl('user/delete');

$savetxt = Yii::t('user', 'Save');

$userEdit = Yii::app()->user->hasRight('user', COsbdUser::$RIGHT_ACTION_EDIT, COsbdUser::$RIGHT_VALUE_ALL) ? 'true' : 'false';
$userDelete = Yii::app()->user->hasRight('user', COsbdUser::$RIGHT_ACTION_DELETE, COsbdUser::$RIGHT_VALUE_ALL) ? 'true' : 'false';

Yii::app()->clientScript->registerScript('assignVMs', <<<EOS
function assignVMs(dn)
{
	$('#startuser').fancybox({
		'modal'			: false,
		'href'			: '{$vmsGuiUrl}?dn=' + dn,
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 600,
		'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#userAssignment_dualselect').dualselect({
				'sorted':true,
				'leftHeader':'VMs',
				'rightHeader':'Assigned VMs'
			});
			$('#saveAssignment').button({icons: {primary: "ui-icon-disk"}, label: '{$savetxt}'})
			.click(function() {
				var selected = $('#userAssignment_dualselect').dualselect("values");
				var a = selected.length;
				$.ajax({
					url: "{$saveAssignUrl}",
					data: 'vms=' + selected + '&dn=' + dn,
					success: function(data) {
						if (data['err']) {
							$('#infoAssignment').css('display', 'none');
							$('#errorAssignment').css('display', 'block');
							$('#errorMsg').html(data['msg']);
						}
						else {
							$('#errorAssignment').css('display', 'none');
							$('#infoAssignment').css('display', 'block');
							$('#infoMsg').html(data['msg']);
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
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}, 'afterSubmit': function(response, postdata){
		var xml = response.responseXML;
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
$updatePopupUrl = $this->createUrl('user/updatePopup');
Yii::app()->clientScript->registerScript('updatePopup', <<<EOS
function update(dn)
{
	$('#startuser').fancybox({
		'modal'			: false,
		'href'			: '{$updatePopupUrl}?dn=' + dn,
		'type'			: 'iframe',
		'autoDimensions': false,
		'width'			: 770,
		'height'		: 360,
		'scrolling'		: 'auto',
		'onComplete'	: function() {
		},
		'onClosed'	: function() {
		}
	});
	$('#startuser').trigger('click');
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
		'url'=>$getUserUrl,
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'DN', 'Name', 'eMail', 'Role', 'isActive', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'name','editable'=>false),
			array('name'=>'email','index'=>'email','editable'=>false),
			array('name'=>'role','index'=>'role','width'=>'70','editable'=>false,'stype'=>'select','surl'=>$getRolesUrl),
			array('name'=>'isActive','index'=>'isActive','hidden'=>true,'editable'=>false),
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
				if (${userEdit}) {
					name = '<a href="${updateUrl}?dn=' + row['dn'] + '">' + row['name'] + '</a>';
				}
				else {
					name = row['name'];
				}
				var act = '';
				if (${userEdit}) {
					act += '<a href="${updateUrl}?dn=' + row['dn'] + '"><img src="{$imagesUrl}/user_edit.png" alt="" title="edit User" class="action" /></a>';
				}
				else {
					act += '<img src="{$imagesUrl}/user_edit.png" alt="" title="" class="action notallowed" /></a>';
				}
				//act += '<img src="{$imagesUrl}/user_edit.png" style="cursor: pointer;" alt="" title="edit User" class="action" onclick="update(\'' + row['dn'] + '\');" />';
				if ('true' !== row['isActive'] || !${userDelete}) {
					act += '<img src="{$imagesUrl}/user_del.png" alt="" title="" class="action notallowed" />';
				}
				else {
					act += '<img src="{$imagesUrl}/user_del.png" style="cursor: pointer;" alt="" title="delete User" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				}
				
				//act += '<img src="{$imagesUrl}/uservm_add.png" style="cursor: pointer;" alt="" title="Assign VMs to this user" class="action" onclick="assignVMs(\'' + row['dn'] + '\');" />';
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
			'leftHeader' => Yii::t('user', 'VMs'),
			'rightHeader' => Yii::t('user', 'Assigned VMs'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'dualselect.css',
	));
?>
<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>