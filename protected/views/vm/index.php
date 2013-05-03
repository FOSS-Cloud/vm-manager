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
	'VMs'=>array('index'),
	'Manage',
);

$this->title = Yii::t('vm', 'Manage {type} VMs', array('{type}' => $vmtype));
//$this->helpurl = Yii::t('help', 'manageVMs');

$gridid = 'vms';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';
$getvmsurl = $this->createUrl('vm/getVms');
$detailurl = $this->createUrl('vm/view');
$updateurl = $this->createUrl('vm/update');
$deleteurl = $this->createUrl('vm/delete');
$makegoldenurl = $this->createUrl('vm/makeGolden');
$activategoldenurl = $this->createUrl('vm/activateGolden');
$refreshtimeouturl = $this->createUrl('vm/refreshTimeout');
$nodeurl = $this->createUrl('node/view');
$restoreurl = $this->createUrl('vm/restoreVm');
$waitforrestoreactionurl = $this->createUrl('vm/waitForRestoreAction');
$getrestoreactionurl = $this->createUrl('vm/getRestoreAction');
$startrestoreactionurl = $this->createUrl('vm/startRestoreAction');
$cancelrestoreactionurl = $this->createUrl('vm/cancelRestoreAction');

//$imgcontroller = $this->createUrl('img/percent');
$actrefreshtime = Yii::app()->getSession()->get('vm_refreshtime', 10000);

$refreshjs = <<<EOS
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
		if (active != buttonState[id][key]) {
			if (active) {
				// This button was not active on last call
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
				}
			}
			else {
				$('#' + key + '_' + id).css({cursor: 'auto'});
				$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '_n.png');
				$('#' + key + '_' + id).unbind('click');
			}
		}
		buttonState[id][key] = active;
	});
}
var row;
var cputimes = [];
var cputimes_max = [22, 100];
var timeoutid = -1;
var refreshDns = new Array();
var gridReload = false;
function reloadVms()
{
	buttonState = [];
	clearTimeout(timeoutid);
	timeoutid = -1;
	$('#{$gridid}_grid').trigger('reloadGrid');
}
function refreshVms()
{
EOS;
if ('dynamic' != $vmtype) {
	$refreshjs .= <<<EOS
	clearTimeout(timeoutid);
EOS;
}

$refreshjs .= <<<EOS
	var dns = '';
	var ids = $('#{$gridid}_grid').getDataIDs();
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
		url: "{$this->createUrl('vm/refreshVms')}",
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
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': false, 'vm_del': false, 'vm_login': false};
						state = 'red';
						break;
					case 'running':
						buttons = {'vm_start': false, 'vm_restart': true, 'vm_shutdown': true, 'vm_destroy': true, 'vm_migrate': true, 'vm_edit': false, 'vm_del': false, 'vm_login': true};
						state = 'green';
						break;
					case 'migrating':
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false};
						state = 'yellow';
						break;
					case 'stopped':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': true, 'vm_del': true, 'vm_login': false};
						state = 'red';
						break;
					case 'shutdown':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': true, 'vm_del': true, 'vm_login': false};
						state = 'red';
						break;
					case 'golden':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': true, 'vm_del': true, 'vm_login': false};
						state = 'golden';
						break;
					case 'golden_active':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': true, 'vm_del': true, 'vm_login': false};
						status = 'golden';
						state = 'golden_active';
						break;
					case 'removed':
						// not a real status
						gridReload = true;
						$('#{$gridid}_grid').delRowData(id);
						//return;
						break;
				}
				status += data[row['uuid']]['statustxt'];

				refreshVmButtons(id, buttons);
				if (buttons['vm_edit'])
				{
					name = '<a href="${updateurl}?dn=' + row['dn']  + '&vmtype=' + row['type'] + '" title="created: ' + row['cts'];
					if ('' != row['user']) {
						name += '; user: ' + row['user'];
					}
					name += '">' + row['name'] + '</a>';
				}
				else
				{
					name = '<span title="created: ' + row['cts'];
					if ('' != row['user']) {
						name += '; user: ' + row['user'];
					}
					name += '">' + row['name'] + '</span>';
				}
				stateimg = 'vm_status_' + state;
				if ('' != data[row['uuid']]['node']) {
					node = '<a href="${nodeurl}?node=' + data[row['uuid']]['node'] + '">' + data[row['uuid']]['node'] + '</a>';
				}
				else {
					node = '';
				}

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

				change['name'] = name;
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
			if (0 < refreshDns.length) {
				setTimeout(refreshNextVm, 100);
			}
			else {
				if (gridReload) {
					gridReload = false;
					reloadGrid();
				}
				else if (-1 != refreshTimeout) {
EOS;
if ('dynamic' == $vmtype) {
	$refreshjs .= 'timeoutid = setTimeout(reloadVms, refreshTimeout);';
}
else {
	$refreshjs .= 'timeoutid = setTimeout(refreshVms, refreshTimeout);';
}

$refreshjs .= <<<EOS
				}
			}
		},
		error:  function(req, status, error) {
			if (0 < refreshDns.length) {
				setTimeout(refreshNextVm, 100);
			}
			else {
				if (gridReload) {
					gridReload = false;
					reloadGrid();
				}
				else if (-1 != refreshTimeout) {
EOS;
if ('dynamic' == $vmtype) {
	$refreshjs .= 'timeoutid = setTimeout(reloadVms, refreshTimeout);';
}
else {
	$refreshjs .= 'timeoutid = setTimeout(refreshVms, refreshTimeout);';
}

$refreshjs .= <<<EOS
				}
			}
		},
		datatype: "json"
	});
	//}
}
function reloadGrid()
{
	clearTimeout(timeoutid);
	timeoutid = -1;
	buttonState = [];
	$('#{$gridid}_grid').trigger("reloadGrid");
}
function startVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vm/startVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
				var refresh = $(xml).find('refresh');
				refresh = refresh.text();
				if (1 == refresh) {
					reloadGrid();
				}
				else {
					refreshDns.push(row['dn']);
					setTimeout(refreshNextVm, 250);
				}
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function shutdownVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vm/shutdownVm",
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
			clearTimeout(timeoutid);
			refreshDns.push(row['dn']);
			refreshNextVm();
  		}
	});
}
function rebootVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'rebooting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vm/rebootVm",
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
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vm/destroyVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
				var refresh = $(xml).find('refresh');
				refresh = refresh.text();
				if (1 == refresh) {
					reloadGrid();
				}
				else {
					refreshDns.push(row['dn']);
					setTimeout(refreshNextVm, 250);
				}
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
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
		url: "{$baseurl}/vm/migrateVm",
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
					reloadGrid();
				}
				else {
					refreshDns.push(row['dn']);
					setTimeout(refreshNextVm, 250);
				}
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function editVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = '{$updateurl}?dn=' + row['dn'] + '&vmtype=' + row['type'];
}
function loginVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = row['spice'];
}
function deleteRow(id)
{
	buttonState = [];
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}, 'afterSubmit': function(response, postdata){
		var xml = response.response;
		var err = $(xml).find('error');
		err = err.text();
		if (0 != err) {
			setTimeout(refreshVms, 250);
			return new Array(false, $(xml).find('message').text());
		}
		else {
			return new Array(true, '');
		}
	}});
}
function goldenImage(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = '{$makegoldenurl}?dn=' + row['dn'];
}
function activateGoldenImage(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$.ajax({
		url: "{$activategoldenurl}",
		data: 'dn=' + row['dn'],
		success: function(data) {
			if (data['err']) {
				$("#vmdialogtext").html(data['msg']);
				$("#vmdialog").dialog({
					title: 'Error activating Golden Image',
					resizable: true,
					modal: true,
					buttons: {
						schließen: function() {
							$(this).dialog('close');
						}
					}
				});
			}
			$('#{$gridid}_grid').trigger('reloadGrid');
		},
		dataType: 'json'
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
				$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: false});
				clearTimeout(timeoutid);
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
EOS;
Yii::app()->clientScript->registerScript('refresh', $refreshjs, CClientScript::POS_END);

$userGuiUrl = $this->createUrl('vm/getUserGui');
$savetxt = Yii::t('vm', 'Save');
$saveUserAssignUrl = $this->createUrl('vm/saveUserAssign');

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

$groupGuiUrl = $this->createUrl('vm/getGroupGui');
$savetxt = Yii::t('vm', 'Save');
$saveGroupAssignUrl = $this->createUrl('vm/saveGroupAssign');

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

$nodeGuiUrl = $this->createUrl('vm/getNodeGui');
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

//Yii::app()->clientScript->registerScriptFile($baseurl . '/js/jquery.sparkline.min.js');
?>
<div class="ui-widget">
	<label>Vm Pool</label>
<?php echo CHtml::dropDownList('vmpool', $vmpool, $vmpools, array('id' => 'vmpool', 'prompt' => '')); ?>
</div><br />
<?php
$vmpooljs = <<<EOS
$('#vmpool').change(function() {
	var vmpool = this.value;
	if ('' != vmpool) {
		$('#{$gridid}_grid').setGridParam({url: '{$getvmsurl}?vmtype={$vmtype}&vmpool=' + vmpool});
		reloadVms();
 	}
});
EOS;
/*
$vmpooljs = <<<EOS
	(function( $ ) {
		$.widget( "ui.combobox", {
			_create: function() {
				var self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "";
				var input = this.input = $( "<input>" )
					.insertAfter( select )
					.val( value )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = $( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												$.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
						},
						change: function( event, ui ) {
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( $( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									$( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								}
							}
							var vmpool = ui.item.option.value;
							if ('' != vmpool) {
								$('#{$gridid}_grid').setGridParam({url: '{$getvmsurl}?vmtype={$vmtype}&vmpool=' + vmpool});
								reloadVms();
							}
							//alert(ui.item.value);
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				this.button = $( "<button type='button'>&nbsp;</button>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.insertAfter( input )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-button-icon" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},
			destroy: function() {
				this.input.remove();
				this.button.remove();
				this.element.show();
				$.Widget.prototype.destroy.call( this );
			}
		});
	})( jQuery );
EOS;
*/
Yii::app()->clientScript->registerScript( 'vmpool', $vmpooljs, CClientScript::POS_END);

//Yii::app()->clientScript->registerScript('vmpool2', '$( "#vmpool" ).combobox();', CClientScript::POS_END);

$refreshtimes = array('never' => -1, 5 => 5000, 10 => 10000, 20 => 20000, 60 => 60000);
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
		'url'=>$getvmsurl . '?vmtype=' . $vmtype . '&vmpool=' . $vmpool,
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Type', 'SubType', 'active Golden-Image', 'createTimestamp', 'User', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'NodeName', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false, 'sortable' =>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'uuid','index'=>'uuid','hidden'=>true,'editable'=>false),
			array('name'=>'spice','index'=>'spice','hidden'=>true,'editable'=>false),
			array('name'=>'type','index'=>'type','hidden'=>true,'editable'=>false),
			array('name'=>'subtype','index'=>'subtype','hidden'=>true,'editable'=>false),
			array('name'=>'agi','index'=>'agi','hidden'=>true,'editable'=>false),
			array('name'=>'cts','index'=>'cts','hidden'=>true,'editable'=>false),
			array('name'=>'user','index'=>'user','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'sstDisplayName','width'=>'70','editable'=>false),
			array('name'=>'status','index'=>'status', 'sortable' =>false, 'search' =>false, 'editable'=>false),
			array('name'=>'statusact','index'=>'statusact','width' => 70, 'fixed' => true, 'sortable' =>false, 'search' =>false, 'editable'=>false),
			array('name'=>'mem','index'=>'mem','width' => '100', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false),
			array('name'=>'cpu','index'=>'cpu','width' => '104', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false, 'hidden'=>true),
			array('name'=>'node','index'=>'sstNode','editable'=>false),
			array('name'=>'nodename','index'=>'nodename','hidden'=>true,'editable'=>false),
			array ('name' => 'act','index' => 'act','width' => 19 * 5, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
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
//		'subGridUrl' =>$baseurl . '/vm/getVmInfo',
      	'subGridRowExpanded' => 'js:' . <<<EOS
      	function(pID, id) {
      		$('#{$gridid}_grid_' + id).html('<img src="{$imagesurl}/loading.gif"/>');
      		var row = $('#{$gridid}_grid').getRowData(id);
			$.ajax({
				url: "{$baseurl}/vm/getVmInfo",
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
				name = row['name'];
				var statusact = '';
				if ('persistent' == row['type']) {
					statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start_n.png" alt="" title="start VM" class="action" />';
					statusact += '<img id="vm_shutdown_' + ids[i] + '" src="{$imagesurl}/vm_shutdown_n.png" alt="" title="shutdown VM" class="action" />';
					statusact += '<img id="vm_destroy_' + ids[i] + '" src="{$imagesurl}/vm_destroy_n.png" alt="" title="destroy VM" class="action" />';
					statusact += '<img id="vm_migrate_' + ids[i] + '" src="{$imagesurl}/vm_migrate.png" alt="" title="migrate VM" class="action" onclick="selectNode(\'' + ids[i] + '\');" />';
				}
				else if ('System-Preparation' == row['subtype']) {
					statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start_n.png" alt="" title="start VM" class="action" />';
					statusact += '<img id="vm_shutdown_' + ids[i] + '" src="{$imagesurl}/vm_shutdown_n.png" alt="" title="shutdown VM" class="action" />';
				}
				else if ('Golden-Image' == row['subtype']) {
					statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start_n.png" alt="" title="start a test VM" class="action" />';
				}
				else {
					statusact += '<img src="{$imagesurl}/space.png" alt="" title="" class="action" />';
					statusact += '<img src="{$imagesurl}/space.png" alt="" title="" class="action" />';
					statusact += '<img id="vm_destroy_' + ids[i] + '" src="{$imagesurl}/vm_destroy_n.png" alt="" title="destroy VM" class="action" />';
					statusact += '<img id="vm_migrate_' + ids[i] + '" src="{$imagesurl}/vm_migrate.png" alt="" title="migrate VM" class="action" onclick="selectNode(\'' + ids[i] + '\');" />';
				}
				var act = '';
				if ('persistent' == row['type']) {
					act += '<img id="vm_edit_' + ids[i] + '" src="{$imagesurl}/vm_edit_n.png" alt="" title="edit VM" class="action" />';
					act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del_n.png" alt="" title="delete VM" class="action" />';
					act += '<img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login_n.png" alt="" title="use VM" class="action" />';
					act += '<img src="{$imagesurl}/vmuser_add.png" style="cursor: pointer;" alt="" title="Assign users to this VM" class="action" onclick="assignUser(\'' + row['dn'] + '\');" />';
					act += '<img src="{$imagesurl}/vmgroup_add.png" style="cursor: pointer;" alt="" title="Assign groups to this VM" class="action" onclick="assignGroup(\'' + row['dn'] + '\');" />';
				}
				else if('System-Preparation' == row['subtype']) {
					act += '<img id="vm_edit_' + ids[i] + '" src="{$imagesurl}/vm_edit_n.png" alt="" title="edit VM" class="action" />';
					act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del_n.png" alt="" title="delete VM" class="action" />';
					act += '<img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login_n.png" alt="" title="use VM" class="action" />';
					act += '<img src="{$imagesurl}/vm_goldenimage.png" style="cursor: pointer;" alt="" title="create Golden-Image VM" class="action" onclick="goldenImage(\'' + ids[i] + '\');" />';
				}
				else if ('Golden-Image' == row['subtype']) {
					act += '<img id="vm_edit_' + ids[i] + '" src="{$imagesurl}/vm_edit_n.png" alt="" title="edit VM" class="action" />';
					act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del_n.png" alt="" title="delete VM" class="action" />';
					if ('false' == row['agi']) {
						act += '<img src="{$imagesurl}/space.png" alt="" title="" class="action" />';
						act += '<img src="{$imagesurl}/vm_active_goldenimage.png" style="cursor: pointer;" alt="" title="activate Golden-Image" class="action" onclick="activateGoldenImage(\'' + ids[i] + '\');" />';
					}
				}
				else {
					act += '<img src="{$imagesurl}/space.png" alt="" title="" class="action" />';
					act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del_n.png" alt="" title="delete VM" class="action" />';
					act += '<img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login_n.png" alt="" title="use VM" class="action" />';
				}
				var node = '<a href="${nodeurl}?node=' + row['nodename'] + '">' + row['nodename'] + '</a>';
				$('#{$gridid}_grid').setRowData(ids[i],{/*'name': name,*/ 'act': act, 'statusact': statusact, 'node': node});
			}
			if (-1 == timeoutid) {
				timeoutid = setTimeout(refreshVms, 1000);
				$('#{$gridid}_pager_right').html('<table cellspacing="0" cellpadding="0" border="0" class="ui-pg-table" style="table-layout: auto; float: right;"><tbody><tr><td><input type="button" id="{$gridid}_refreshNow" value="Refresh"/></td><td><select id="{$gridid}_refresh" class="ui-pg-selbox">{$refreshoptions}</select></td></tr></tbody></table>');
				$('#{$gridid}_refreshNow').unbind('click');
				$('#{$gridid}_refreshNow').click(function() {
					refreshVms();
				});
				$('#{$gridid}_refresh').unbind('change');
				$('#{$gridid}_refresh').change(function() {
					if (-1 == refreshTimeout) {
						refreshVms();
					}
					refreshTimeout = $(this).val();
					$.ajax({
						url: "{$refreshtimeouturl}",
						cache: false,
						data: "time=" + refreshTimeout,
						dataType: 'json'
					});
				});
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

<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>