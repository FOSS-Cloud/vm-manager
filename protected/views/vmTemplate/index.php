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
	'VMTemplates'=>array('index'),
	'Manage',
);
$this->title = Yii::t('vmtemplate', 'Manage VMTemplates');
//$this->helpurl = Yii::t('help', 'manageVMTemplates');

$gridid = 'vmtemplates';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';
$detailurl = $this->createUrl('vmTemplate/view');
$updateurl = $this->createUrl('vmTemplate/update');
$deleteurl = $this->createUrl('vmTemplate/delete');
$finishurl = $this->createUrl('vmTemplate/finish');
$finishdynurl = $this->createUrl('vmTemplate/finishDynamic');
$nodeurl = $this->createUrl('node/view');
$restoreurl = $this->createUrl('vm/restoreVm');
$waitforrestoreactionurl = $this->createUrl('vmTemplate/waitForRestoreAction');
$getrestoreactionurl = $this->createUrl('vmTemplate/getRestoreAction');
$startrestoreactionurl = $this->createUrl('vmTemplate/startRestoreAction');
$cancelrestoreactionurl = $this->createUrl('vmTemplate/cancelRestoreAction');

$actrefreshtime = Yii::app()->getSession()->get('vm_refreshtime', 10000);

Yii::app()->clientScript->registerScript('refresh', <<<EOS
var refreshTimeout = {$actrefreshtime};
var buttonState = [];
function refreshVmButtons(id, buttons) {
	$.each(buttons, function (key, active) {
		if (!(id in buttonState)) {
			buttonState[id] = [];
			if (!(key in buttonState[id])) {
				buttonState[id][key] = false;
			}
		}
		if (active) {
			if (!buttonState[id][key]) {
				$('#' + key + '_' + id).css({cursor: 'pointer'});
				$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '.png');
				switch(key) {
					case 'vm_start': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); startVm(id);}); break;
					case 'vm_reboot': break;
					case 'vm_shutdown': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); shutdownVm(id);}); break;
					case 'vm_destroy': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); destroyVm(id);}); break;
					case 'vm_migrate': break;
					case 'vm_edit': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); editVm(id);}); break;;
					case 'vm_del': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); deleteRow(id);}); break;
					case 'vm_login': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); loginVm(id);}); break;
					case 'vmtemplate_finish': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); selectStaticPool(id);}); break;
					case 'vmtemplate_finishdyn': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); selectDynamicPool(id);}); break;
				}
			}
		}
		else {
			$('#' + key + '_' + id).css({cursor: 'auto'});
			$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '_n.png');
			$('#' + key + '_' + id).unbind('click');
			if (key == 'vm_edit') {
				$('#{$gridid}_grid').setRowData(id, {'displayname' : row['name']});
			}
		}
		buttonState[id][key] = active;
	});
}
var row;
var cputimes = [];
var cputimes_max = [22, 100];
var vmcount = 0;
var timeoutid = -1;
var refreshDns = new Array();
function refreshVms()
{
	clearTimeout(timeoutid);
	var dns = '';
	var ids = $('#{$gridid}_grid').getDataIDs();
	vmcount = ids.length;
	for(var i=0;i < ids.length;i++)
	{
		var id = ids[i];
		row = $('#{$gridid}_grid').getRowData(id);
		if ('' != dns) dns += ';';
		dns += row['dn'];
		refreshDns.push(row['dn']);
	}

	refreshNextVm();
}

function refreshNextVm()
{
	dn = refreshDns.shift();
	$.ajax({
		url: "{$this->createUrl('vmTemplate/refreshVms')}",
		data: {dns:  dn, time: refreshTimeout},
		success: function(data){
			if (data['error'] != undefined) {
				if (1 == data['error']) {
					alert(data['message']);
					return;
				}
			}
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var id = ids[i];
				var row = $('#{$gridid}_grid').getRowData(id);
				if (data[row['uuid']] == undefined) continue;
				var status = data[row['uuid']]['status'];
				switch(status)
				{
					case 'unknown':
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'red';
						break;
					case 'running':
						buttons = {'vm_start': false, 'vm_restart': true, 'vm_shutdown': true, 'vm_destroy': true, 'vm_migrate': true, 'vm_edit': false, 'vm_del': false, 'vm_login': true, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'green';
						break;
					case 'migrating':
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'yellow';
						break;
					case 'stopped':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': true, 'vm_del': true, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true};
						state = 'red';
						break;
					case 'shutdown':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': true, 'vm_del': true, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true};
						state = 'red';
						break;
					case 'backup':
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'red';
						break;
				}

				refreshVmButtons(id, buttons);
				if (buttons['vm_edit'])
				{
					displayname = '<a href="${updateurl}?dn=' + row['dn'] + '">' + row['name'] + '</a>';
				}
				else
				{
					displayname = row['name'];
				}
				stateimg = 'vm_status_' + state;
				node = '<a href="${nodeurl}?node=' + data[row['uuid']]['node'] + '">' + data[row['uuid']]['node'] + '</a>';

				spice = data[row['uuid']]['spice'];
				$('#{$gridid}_grid').setCell(id, 'spice', spice);
				if ('green' == state) {
					mem = data[row['uuid']]['mem'];
					cpu = data[row['uuid']]['cpu'];
					cputext = '<div id="cpu_' + id + '" style="float: left; height:16px;"></div><div style="float: right; background-color: #E7DFDA; width: 3em;">' + cpu + '%</div>';

					if (cputimes[row['uuid']] == undefined) {
						cputimes[row['uuid']] = [0];
					}
	                		cputimes[row['uuid']].push(cpu);
                			if (cputimes[row['uuid']].length > cputimes_max[0])
                    				cputimes[row['uuid']].splice(0,1);

					if (cputimes[row['uuid'] + 2] == undefined) {
						cputimes[row['uuid'] + 2] = [0];
					}
	                		cputimes[row['uuid'] + 2].push(cpu);
                			if (cputimes[row['uuid'] + 2].length > cputimes_max[1])
                    				cputimes[row['uuid'] + 2].splice(0,1);
				}
				else {
					mem = '---';
					cpu = 0;
					cputext = '---';
				}
				$('#{$gridid}_grid').setCell(id, 'status', status, {'padding-left': '20px', background: 'url({$imagesurl}/' + stateimg + '.png) no-repeat 3px 3px transparent'});

				var change = {};

				change['displayname'] = displayname;
				change['mem'] = mem;
				change['node'] = node;
				$('#{$gridid}_grid').setRowData(id, change);

				//$('#spice' + i).attr('href', spice);

//				$('#{$gridid}_grid').setCell(id, 'cpu', cputext, {'font-weight': 'normal'});
//				$('#cpu_' + id).sparkline(cputimes[row['uuid']], { lineColor: '#000000',
//			                fillColor: '', //#E7DFDA',
//			                spotColor: '#000000',
//			                chartRangeMin: 0,
//			                chartRangeMax: 100,
//	        		        //minSpotColor: '#000000',
//			                //maxSpotColor: '#000000',
//	        		        normalRangeMin: 0,
//					normalRangeMax: 50,
//					normalRangeColor: '#68C760',
//			                spotRadius: 2,
//	        		        lineWidth: 1,
//				 });
//				$('#cpu2_' + id).sparkline(cputimes[row['uuid'] + 2], { lineColor: '#000000',
//			                fillColor: '', //#E7DFDA',
//	        		        spotColor: '#000000',
//			                chartRangeMin: 0,
//	        		        chartRangeMax: 100,
//			                width : '150px',
//					height : '50px',
//	        		        //minSpotColor: '#000000',
//			                //maxSpotColor: '#000000',
//	        		        normalRangeMin: 0,
//					normalRangeMax: 50,
//					normalRangeColor: '#68C760',
//			                spotRadius: 2,
//	        		        lineWidth: 1,
//				 });
			}
			vmcount--;
			if (0 == vmcount) {
				//timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
			if (0 < refreshDns.length) {
				timeoutid = setTimeout(refreshNextVm, 100);
			}
			else {
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
		},
		error:  function(req, status, error) {
			vmcount--;
			if (0 == vmcount) {
				//timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
			//window.alert(status);
			if (0 < refreshDns.length) {
				timeoutid = setTimeout(refreshNextVm, 100);
			}
			else {
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
		},
		datatype: "json"
	});
	//}
}
function startVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	//$('#{$gridid}_grid').setRowData(id, {'displayname' : row['name'], 'act' : ''});
	$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/startVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
			clearTimeout(timeoutid);
			refreshDns.push(row['dn']);
			refreshNextVm();
  		}
	});
}
function shutdownVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/shutdownVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 != err) {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function rebootVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'rebooting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/rebootVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'rebooting', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function destroyVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/destroyVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
			clearTimeout(timeoutid);
			refreshDns.push(row['dn']);
			refreshNextVm();
  		}
	});
}

function migrateVm(id, newnodedn)
{
	$('#migrateNode').attr('disabled', 'disabled');
	$('#migrateNode').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorNode').css('display', 'none');
	$('#infoNode').css('display', 'block');
	$('#infoNodeMsg').html('Migration started');
	$.ajax({
		url: "{$baseurl}/vmTemplate/migrateVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'] + '&newnode=' + newnodedn,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 != err) {
				$('#infoNode').css('display', 'none');
				$('#errorNode').css('display', 'block');
				$('#errorNodeMsg').html($(xml).find('message').text());
				if (2 == err) {
					$('#migrateNode').removeAttr('disabled');
					$('#migrateNode').removeClass('ui-state-disabled');
				}
			}
			else {
				$('#errorNode').css('display', 'none');
				$('#infoNode').css('display', 'block');
				$('#infoNodeMsg').html($(xml).find('message').text());
				var refresh = $(xml).find('refresh');
				refresh = refresh.text();
				if (1 == refresh) {
					$('#{$gridid}_grid').trigger("reloadGrid");
				}
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}

function editVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = '{$updateurl}?dn=' + row['dn'];
}
function loginVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = row['spice'];
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

function toogleBoot(id, device)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$.ajax({
		url: "{$baseurl}/vmTemplate/toogleBoot",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'] + '&dev=' + device,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').trigger("reloadGrid");
  		}
	});
}
function restoreVm(evt)
{
	clearTimeout(timeoutid);
	timeoutid = -1;
	$.ajax({
		url: "{$restoreurl}",
		data: 'dn=' + evt.data.backupDn,
		success: function(data) {
			if (data['err']) {
				$("#vmdialogtext").html(data['msg']);
				$("#vmdialog").dialog({
					title: 'Error restoring Vm',
					resizable: true,
					modal: true,
					buttons: {
						Cancel: function() {
							$(this).dialog('close');
						}
					}
				});
			}
			else {
				$("#vmdialogtext").html(data['msg']);
				$("#vmdialog").dialog({
					title: 'Restore Vm',
					resizable: true,
					minWidth: 330,
					modal: false,
					open: function( event, ui ) {
						$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: true}); //.attr('disabled', 'disabled');
					},
					buttons: {
						OK: function() {
							$.ajax({
								url: "{$startrestoreactionurl}",
								data: 'dn=' + waitForRestoreActionDn,
								success: function(data) {
									timeoutid = -1;
									$('#{$gridid}_grid').trigger('reloadGrid');
								},
								dataType: 'json'
							});
							$(this).dialog('close');
						},
						Cancel: function() {
							$.ajax({
								url: "{$cancelrestoreactionurl}",
								data: 'dn=' + waitForRestoreActionDn,
								success: function(data) {
									timeoutid = -1;
									$('#{$gridid}_grid').trigger('reloadGrid');
								},
								dataType: 'json'
							});
						$(this).dialog('close');
						}
					}
				});
//				alert("NOW");
				waitForRestoreActionDn = evt.data.backupDn;
				timeoutid = setTimeout(waitForRestoreAction, 3000);
			}
			
			$('#{$gridid}_grid').trigger('reloadGrid');
		},
		dataType: 'json'
	});
}
var waitForRestoreActionDn = null;
function waitForRestoreAction()
{
	$.ajax({
		url: "{$waitforrestoreactionurl}",
		data: 'dn=' + waitForRestoreActionDn,
		success: function(data) {
			if (data['err']) {
				$("#vmdialogtext").html(data['msg']);
				if (undefined != data['refresh'] && data['refresh']) {
					timeoutid = setTimeout(waitForRestoreAction, 5000);
				}
				else {
					$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: false});
				}
			}
			else {
				$("#vmdialogtext").html(data['msg']);
				clearTimeout(timeoutid);
				$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: false});
				timeoutid = -1;
//				timeoutid = setTimeout(getRestoreAction, 100);
			}
		},
		dataType: 'json'
	});
}
function getRestoreAction()
{
	$("#vmdialogtext").load("{$getrestoreactionurl}", {'dn': waitForRestoreActionDn}, function(response, status, xhr) {
		var a = 12;
		if (status == "error") {
		}
		else {
			//timeoutid = setTimeout(refreshVms, 1000);
		}
	});
}
EOS
, CClientScript::POS_END);

$nodeGuiUrl = $this->createUrl('vmTemplate/getNodeGui');
$migratetxt = Yii::t('vm', 'Migrate');

Yii::app()->clientScript->registerScript('selectNode', <<<EOS
function selectNode(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#startnode').fancybox({
		'modal'			: false,
		'href'			: '{$nodeGuiUrl}?dn=' + row['dn'],
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 300,
		//'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#nodeSelection_singleselect').singleselect({
				'sorted':true,
				'header':'Nodes',
			});
			$('#migrateNode').button({icons: {primary: "ui-icon-disk"}, label: '{$migratetxt}'})
			.click(function() {
				var selected = $('#nodeSelection_singleselect').singleselect("values");
				migrateVm(id, selected[0]);
				//$.fancybox.close();
			});
		},
		'onClosed'	: function() {
			$('#selectNode').hide();
		}
	});
	$('#startnode').trigger('click');
}
EOS
, CClientScript::POS_END);

$selectStaticGuiUrl = $this->createUrl('vmTemplate/getStaticPoolGui');
$selectStaticTxt = Yii::t('vmtemplate', 'Create persistent VM');
$selectStaticOkButtonTxt = Yii::t('vmtemplate', 'Create');
$selectStaticCancelButtonTxt = Yii::t('vmtemplate', 'Cancel');

$okButtonTxt = Yii::t('vmtemplate', 'Ok');
$cancelButtonTxt = Yii::t('vmtemplate', 'Cancel');

Yii::app()->clientScript->registerScript('finish', <<<EOS
//function finish(id, pooldn, name, subtype, domainname, hostname)
function finish(id, formdata)
{
	$('#selectStaticOkButton').attr('disabled', 'disabled');
	$('#selectStaticOkButton').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	//$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorSelectStatic').css('display', 'none');
	$('#infoSelectStatic').css('display', 'block');
	$('#infoSelectStaticMsg').html('<img src="{$imagesurl}/loading.gif" alt=""/>{$selectStaticTxt}');
	$.ajax({
		type: "POST",
		url: "{$baseurl}/vmTemplate/finish",
		cache: false,
		dataType: 'json',
		//data: 'dn=' + row['dn'] + '&pool=' + pooldn + '&name=' + name + '&subtype=' + subtype + '&domainname=' + domainname + '&hostname=' + hostname,
		data: 'dn=' + row['dn'] + '&' + formdata,
		success: function(data) {
			if (0 != data.error) {
				$('#infoSelectStatic').css('display', 'none');
				$('#errorSelectStatic').css('display', 'block');
				$('#errorSelectStaticMsg').html(data.message);
				if (2 == data.error) {
					$('#selectStaticOkButton').removeAttr('disabled');
					$('#selectStaticOkButton').removeClass('ui-state-disabled');
				}
			}
			else {
				message = data.message;
				if ('' != message) {
					$('#errorSelectStatic').css('display', 'none');
					$('#infoSelectStatic').css('display', 'block');
					$('#infoSelectStaticMsg').html(message);
				}
				else {
					var url = data.url;
					window.location.replace(url);
				}
			}
  		}
	});
}

function selectStaticPool(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$("#finishdialog").dialog({
		resizable: true,
		width: 'auto',
		height: 'auto',
		modal: true,
		open: function(event, ui) {
			$('#selectStaticOkButton').attr('disabled', 'disabled');
			$('#selectStaticOkButton').addClass('ui-state-disabled');
			$('#errorSelectStatic').css('display', 'none');
			$('#infoSelectStatic').css('display', 'none');

			$("#finishId").val(id);
			$("#finishPool").val("");
			$("#finishNode").val("").empty();
			$("#finishdisplayname").val(row['name']);
			$('#selectStaticOkButton').removeAttr('disabled');
			$('#selectStaticOkButton').removeClass('ui-state-disabled');
		},
		buttons:  [
			{
				text: '{$selectStaticOkButtonTxt}',
				id: 'selectStaticOkButton',
				click: function() {
					finish($("#finishId").val(), $("#finishForm").serialize());
				}
			},
			{
				text: '{$selectStaticCancelButtonTxt}',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});
}
EOS
, CClientScript::POS_END);

$selectDynamicGuiUrl = $this->createUrl('vmTemplate/getDynamicPoolGui');
$selectDynamicTxt = Yii::t('vm', 'Create dynamic VM');
$selectDynamicOkButtonTxt = Yii::t('vmtemplate', 'Create');
$selectDynamicCancelButtonTxt = Yii::t('vmtemplate', 'Cancel');

Yii::app()->clientScript->registerScript('finishdyn', <<<EOS
function finishDyn(id, formdata)
{
	$('#selectDynamicOkButton').attr('disabled', 'disabled');
	$('#selectDynamicOkButton').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	//$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorSelectDynamic').css('display', 'none');
	$('#infoSelectDynamic').css('display', 'block');
	$('#infoSelectDynamicMsg').html('<img src="{$imagesurl}/loading.gif" alt=""/>{$selectDynamicTxt}');
	$.ajax({
		type: "POST",
		url: "{$baseurl}/vmTemplate/finishDynamic",
		cache: false,
		dataType: 'json',
		//data: 'dn=' + row['dn'] + '&pool=' + pooldn + '&name=' + name + '&sysprep=' + sysprep,
		data: 'dn=' + row['dn'] + '&' + formdata,
		success: function(data){
			if (0 != data.error) {
				$('#infoSelectDynamic').css('display', 'none');
				$('#errorSelectDynamic').css('display', 'block');
				$('#errorSelectDynamicMsg').html(data.message);
				if (2 == data.error) {
					$('#selectDynamicButton').removeAttr('disabled');
					$('#selectDynamicButton').removeClass('ui-state-disabled');
				}
			}
			else {
				message = data.message;
				if ('' != message) {
					$('#errorSelectDynamic').css('display', 'none');
					$('#infoSelectDynamic').css('display', 'block');
					$('#infoSelectDynamicMsg').html(message);
				}
				else {
					var url = data.url;
					window.location.replace(url);
				}
			}
  		}
	});
}

function selectDynamicPool(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$("#finishDynDialog").dialog({
		resizable: true,
		width: 'auto',
		height: 'auto',
		modal: true,
		open: function(event, ui) {
			$('#selectDynamicOkButton').attr('disabled', 'disabled');
			$('#selectDynamicOkButton').addClass('ui-state-disabled');
			$('#errorSelectDynamic').css('display', 'none');
			$('#infoSelectDynamic').css('display', 'none');

			$("#finishDynId").val(id);
			$("#finishDynPool").val("");
			$("#finishDynDisplayname").val(row['name']);
			$('#selectDynamicOkButton').removeAttr('disabled');
			$('#selectDynamicOkButton').removeClass('ui-state-disabled');
		},
		buttons:  [
			{
				text: '{$selectDynamicOkButtonTxt}',
				id: 'selectDynamicOkButton',
				click: function() {
					finishDyn($("#finishDynId").val(), $("#finishDynForm").serialize());
				}
			},
			{
				text: '{$selectDynamicCancelButtonTxt}',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});
}

EOS
, CClientScript::POS_END);
//Yii::app()->clientScript->registerScriptFile($baseurl . '/js/jquery.sparkline.min.js');

$refreshtimes = array(5 => 5000, 10 => 10000, 20 => 20000, 60 => 60000);
$refreshoptions = '';
foreach($refreshtimes as $key => $value) {
	$refreshoptions .= '<option value="' . $value . '" ' . ($actrefreshtime == $value ? 'selected="selected"' : '') . '>' .$key . '</option>';
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
		'url'=>$baseurl . '/vmTemplate/getVmTemplates',
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Boot', 'OrigName', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'uuid','index'=>'uuid','hidden'=>true,'editable'=>false),
			array('name'=>'spice','index'=>'spice','hidden'=>true,'editable'=>false),
			array('name'=>'boot','index'=>'boot','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'name','hidden'=>true,'editable'=>false),
			array('name'=>'displayname','index'=>'sstDisplayName','editable'=>false),
			array('name'=>'status','index'=>'status', 'sortable' =>false, 'search' =>false, 'editable'=>false),
			array('name'=>'statusact','index'=>'statusact','width' => 70, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'mem','index'=>'mem','width' => '100', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false),
			array('name'=>'cpu','index'=>'cpu','width' => '104', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false, 'hidden'=>true),
			array('name'=>'node','index'=>'sstNode','editable'=>false),
			array ('name' => 'act','index' => 'act','width' => 19 * 6, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
			// 'width' => 124
		),
		//'toolbar' => array(true, "top"),
		//'caption'=>'VMs',
		'autowidth'=>true,
		'rowNum'=>10,
		'rowList'=> array(10,20,30),
		'height'=>300,
		'altRows'=>false,
		'editurl'=>$deleteurl,
		'subGrid' => true,
//		'subGridUrl' =>$baseurl . '/vmtemplate/getVmInfo',
      		'subGridRowExpanded' => 'js:' . <<<EOS
      	function(pID, id) {
      		$('#{$gridid}_grid_' + id).html('<img src="{$imagesurl}/loading.gif"/>');
      		var row = $('#{$gridid}_grid').getRowData(id);
			$.ajax({
				url: "{$baseurl}/vmTemplate/getVmInfo",
				cache: false,
				data: "dn=" + row['dn'] + "&rowid=" + id,
				success: function(html){
					$('#{$gridid}_grid_' + id).html(html);
					$("img[alt=restore]").each(function() {
						$(this).click({backupDn: $(this).attr("backupDn")}, restoreVm);
					});
				}
			});
      	}
EOS
,
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				if ('cdrom' == row['boot']) {
					bootdevice = 'hd';
				}
				else if ('hd' == row['boot']) {
					bootdevice = 'cdrom';
				}
				var statusact = '';
				statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start_n.png" alt="" title="start VM" class="action" />';
//				statusact += '<img id="vm_restart_' + ids[i] + '" src="{$imagesurl}/vm_restart_n.png" alt="" title="reboot VM" class="action" onclick="rebootVm(\'' + ids[i] + '\');" />';
				statusact += '<img id="vm_shutdown_' + ids[i] + '" src="{$imagesurl}/vm_shutdown_n.png" alt="" title="shutdown VM" class="action" />';
				statusact += '<img id="vm_destroy_' + ids[i] + '" src="{$imagesurl}/vm_destroy_n.png" alt="" title="destroy VM" class="action" />';
				statusact += '<img id="vm_migrate_' + ids[i] + '" src="{$imagesurl}/vm_migrate.png" alt="" title="migrate VM" class="action" onclick="selectNode(\'' + ids[i] + '\');" />';
				var act = '';
				//act += '<a href="${detailurl}?dn=' + row['dn'] + '"><img src="{$imagesurl}/vmtemplate_detail.png" alt="" title="view VM Template" class="action" /></a>';
				act += '<img id="vm_edit_' + ids[i] + '" src="{$imagesurl}/vm_edit_n.png" alt="" title="edit VM Template" class="action" />';
				act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del_n.png" alt="" title="delete VM Template" class="action" />';
				act += '<img src="{$imagesurl}/vmtemplate_' + row['boot'] + '.png" alt="" title="toogle bootdevice to ' + bootdevice + '" class="action" style="cursor: pointer;" onclick="toogleBoot(\'' + ids[i] + '\', \'' + bootdevice + '\');" />';
//				act += '<a id="spice' + i + '" href="' + row['spice'] + '"><img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login_n.png" alt="" title="use VM Template" class="action" /></a>';
				act += '<img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login_n.png" alt="" title="use VM Template" class="action" />';
				act += '<img id="vmtemplate_finish_' + ids[i] + '" src="{$imagesurl}/vmtemplate_finish_n.png" alt="" title="VM Template =&gt; persistent VM" class="superaction"/>';
				act += '<img id="vmtemplate_finishdyn_' + ids[i] + '" src="{$imagesurl}/vmtemplate_finishdyn_n.png" alt="" title="VM Template =&gt; dynamic VM" class="superaction"/>';
				//act += '<img src="{$imagesurl}/vm_del.png" alt="" title="delete VM" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				var node = '<a href="${nodeurl}?node=' + row['node'] + '">' + row['node'] + '</a>';
				$('#{$gridid}_grid').setRowData(ids[i],{'displayname': row['name'], 'act': act, 'statusact': statusact, 'node': node});
			}
			if (-1 == timeoutid)
			{
				clearTimeout(timeoutid);
				timeoutid = setTimeout(refreshVms, 1000);
				$('#{$gridid}_pager_right').html('<table cellspacing="0" cellpadding="0" border="0" class="ui-pg-table" style="table-layout: auto; float: right;"><tbody><tr><td><input type="button" id="{$gridid}_refreshNow" value="Refresh"/></td><td><select id="{$gridid}_refresh" class="ui-pg-selbox">{$refreshoptions}</select></td></tr></tbody></table>');
				$('#{$gridid}_refreshNow').click(function() {
					clearTimeout(timeoutid);
					refreshVms();
				});
				$('#{$gridid}_refresh').change(function() {
					refreshTimeout = $(this).val();
				});
			}
			buttonState = [];
		}
EOS
	),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));
if (!is_null($copyaction)) {
Yii::app()->clientScript->registerScript('checkCopy', <<<EOS
	$("#checkCopyDialog").dialog({
		resizable: true,
		width: 'auto',
		height: 'auto',
		modal: true,
		open: function(event, ui) {
			clearTimeout(timeoutid);
			check();
		},
		buttons:  [
			{
				text: '{$okButtonTxt}',
				click: function() {
					$(this).dialog('close');
					clearTimeout(timeoutid);
					timeoutid = setTimeout(refreshVms, 100);
				}
			}
		]
	});

	function check() {
		$.ajax({
			url: "{$baseurl}/vmTemplate/checkCopy",
			data: 'pid={$copyaction}',
			success: function(data) {
				if (!data['err']) {
					$('#checkCopyRunning').css('display', 'none');
					$('#errorCheckCopy').css('display', 'none');
					$('#infoCheckCopy').css('display', 'block');
					$('#infoCheckCopyMsg').html(data['msg']);
				}
				else {
//					$('#infoCheckCopy').css('display', 'none');
//					$('#errorCheckCopy').css('display', 'block');
//					$('#errorCheckCopyMsg').html(data['msg']);
					timeoutid = setTimeout(check, 4000);
				}
			},
			dataType: 'json'
		});
	}
	clearTimeout(timeoutid);
EOS
	, CClientScript::POS_END);
}

?>
<div style="display: none;">
	<a id="startnode" href="#selectNode">start node</a>
	<div id="selectNode">
	</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqSingleselect', array(
		'id' => 'nodeSelection',
		'values' => array(),
		'size' => 5,
		'multiselect' => false,
		'options' => array(
			'sorted' => true,
			'header' => Yii::t('vm', 'Nodes'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'singleselect.css',
	));
?>
<div id="vmdialog" title="" style="display: none;">
	<span class="ui-icon ui-icon-notice" style="float:left; margin:0 7px 0 0;"></span>
	<div id="vmdialogtext"></div>
</div>
<div id="finishdialog" title="<?php echo Yii::t('vm', 'Create persistent VM'); ?>" style="display: none;">
<form id="finishForm">
<?php 
	Yii::app()->clientScript->registerScript('finishdialog', <<<EOS
$("#finishPool").change(function() {
	var value = $(this).val();
	$("#finishNode").empty().append($('<option value=""></option>'));
	if ('' != value) {
	 	var nodes = persistentpools[value]['nodes'];
	 	$.each(nodes, function(key, val) {
			$("#finishNode").append($('<option value="' + key + '">' + val + '</option>'));
		});
 	}
});
EOS
, CClientScript::POS_READY);
	
	$ppools = CJSON::encode($persistentpools);
	
	Yii::app()->clientScript->registerScript('finishdialog2', <<<EOS
	var persistentpools =  $.parseJSON('{$ppools}');
EOS
, CClientScript::POS_END);
	
	$parray = array();
	foreach($persistentpools as $key => $pool) {
		$parray[$key] = $pool['name'];
	}
?>
		<?php echo CHtml::hiddenField('FinishForm[id]','', array('id' => 'finishId')); ?>
		<div>
			<label for="finishPool" style="width: 150px; float: left;">VM Pool </label>
			<?php echo CHtml::dropDownList('FinishForm[pool]', '', $parray, array('prompt' => '', 'id' => 'finishPool')); ?>
		</div>
		<br/>
		<div>
			<label for="finishNode" style="width: 150px; float: left;">Node </label>
			<?php echo CHtml::dropDownList('FinishForm[node]', '', array(), array('prompt' => '', 'id' => 'finishNode')); ?>
		</div>
		<br/>
		<div>
			<label for="displayname" style="width: 150px; float: left;">Name </label>
			<input type="text" id="finishdisplayname" name="FinishForm[displayname]" value="" />
		</div>
		<br/>
		<div id="radiosubtype" style="">
			<label style="width: 150px; float: left;">Type </label>
			<input type="radio" id="radiosubtype1" name="FinishForm[subtype]" value="Server" checked="checked" /><label for="radiosubtype1">Server</label>
			<input type="radio" id="radiosubtype2" name="FinishForm[subtype]" value="Desktop" /><label for="radiosubtype2">Desktop</label>
		</div>
		<br/>
		<div id="errorSelectStatic" class="ui-state-error ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectStaticMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectStatic" class="ui-state-highlight ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectStaticMsg"></span></p>
		</div>
</form>
</div>
<div id="finishDynDialog" title="<?php echo Yii::t('vm', 'Create dynamic VM'); ?>" style="display: none;">
<form id="finishDynForm">
<?php 
	$dpools = CJSON::encode($dynamicpools);
	
	Yii::app()->clientScript->registerScript('finishDynDialog', <<<EOS
	var dynamicpools =  $.parseJSON('{$dpools}');
EOS
, CClientScript::POS_END);

	$darray = array();
	foreach($dynamicpools as $key => $pool) {
		$darray[$key] = $pool['name'];
	}
?>
		<?php echo CHtml::hiddenField('FinishForm[id]','', array('id' => 'finishDynId')); ?>
		<div>
			<label for="finishDynPool" style="width: 150px; float: left;">VM Pool </label>
			<?php echo CHtml::dropDownList('FinishForm[pool]', '', $darray, array('prompt' => '', 'id' => 'finishDynPool')); ?>
		</div>
		<br/>
		<div>
			<label for="displayname" style="width: 150px; float: left;">Name </label>
			<input type="text" id="finishDynDisplayname" name="FinishForm[displayname]" value="" />
		</div>
		<br/>
<!-- 
		<div>
			<div style="width: 150px; float: left;">&nbsp;</div>
			<input type="checkbox" id="radiosysprep" name="FinishForm[sysprep]" /><label for="radiosysprep">Sys Prep</label>
		</div>
		<br/>
 -->
		<div id="errorSelectDynamic" class="ui-state-error ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectDynamicnMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectDynamic" class="ui-state-highlight ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectDynamicMsg"></span></p>
		</div>
</form>
</div>
<div id="checkCopyDialog" title="<?php echo Yii::t('vmtemplate', 'Check Volume Copy'); ?>" style="display: none;">
	<div style="text-align: center;" ><img id="checkCopyRunning" src="<?php echo $imagesurl; ?>/loading.gif" alt="" /><br/></div>
	<div id="errorCheckCopy" class="ui-state-error ui-corner-all" style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
		<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>
		<span id="errorCheckCopyMsg">
		<?=Yii::t('vmtemplate', 'Copy of VM Template volume to VM volume still running!'); ?></span></p>
	</div>
	<div id="infoCheckCopy" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
		<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoCheckCopyMsg"></span></p>
	</div>
</div>
<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>