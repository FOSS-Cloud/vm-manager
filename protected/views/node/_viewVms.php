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

$gridid = 'vms';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';
//$imgcontroller = $this->createUrl('img/percent');
$actrefreshtime = Yii::app()->getSession()->get('vm_refreshtime', 10000);

Yii::app()->clientScript->registerScript('refresh', <<<EOS
refreshTimeout = {$actrefreshtime};
function refreshVmButtons(id, buttons) {
	$.each(buttons, function (key, value) {
		if (value) {
			$('#' + key + '_' + id).css({cursor: 'pointer'});
			$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '.png');
			switch(key) {
				case 'vm_start': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); startVm(id);}); break;
				case 'vm_reboot': break;
				case 'vm_shutdown': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); shutdownVm(id);}); break;
				case 'vm_destroy': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); destroyVm(id);}); break;
				case 'vm_migrate': break;
			}
		}
		else {
			$('#' + key + '_' + id).css({cursor: 'auto'});
			$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '_n.png');
			$('#' + key + '_' + id).unbind('click');
		}
	});
}
var row;
var cputimes = [];
var cputimes_max = [22, 100];
var vmcount = 0;
var timeoutid;
function refreshVms()
{
	var dns = '';
	var ids = $('#{$gridid}_grid').getDataIDs();
	vmcount = ids.length;
	for(var i=0;i < ids.length;i++)
	{
		var id = ids[i];
		row = $('#{$gridid}_grid').getRowData(id);
		if ('' != dns) dns += ';';
		dns = row['dn'];
	//}
	$.ajax({
		url: "{$this->createUrl('vm/refreshVms')}",
		data: {dns:  dns, time: refreshTimeout},
		success: function(data){
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
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
						state = 'red';
						break;
					case 'running':
						buttons = {'vm_start': false, 'vm_restart': true, 'vm_shutdown': true, 'vm_destroy': true, 'vm_migrate': true};
						state = 'green';
						break;
					case 'migrating':
						buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
						state = 'yellow';
						break;
					case 'stopped':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
						state = 'red';
						break;
					case 'shutdown':
						buttons = {'vm_start': true, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
						state = 'red';
						break;
				}

				refreshVmButtons(id, buttons);
				stateimg = 'vm_status_' + state;
				spice = data[row['uuid']]['spice'];
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

				change['mem'] = mem;

				$('#{$gridid}_grid').setRowData(id, change);

				$('#spice' + i).attr('href', spice);

				//$('#{$gridid}_grid').setCell(id, 'cpu', cputext, {'font-weight': 'bold', background: 'url({$imgcontroller}?v=' + cpu + ') no-repeat 0 0 transparent'});
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
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
		},
		error:  function(req, status, error) {
			vmcount--;
			if (0 == vmcount) {
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
			//window.alert(status);
		},
		datatype: "json"
	});
	}
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
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function rebootVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
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
			$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function destroyVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vm/destroyVm",
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
			$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
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
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
			$('#{$gridid}_grid').trigger('reloadGrid');
			//location.reload(true);
			//$.fancybox.close();
  		}
	});
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

$refreshtimes = array(5 => 5000, 10 => 10000, 20 => 20000, 60 => 60000);
$refreshoptions = '';
foreach($refreshtimes as $key => $value) {
	$refreshoptions .= '<option value="' . $value . '" ' . ($actrefreshtime == $value ? 'selected="selected"' : '') . '>' .$key . '</option>';
}
$detailurl = $this->createUrl('vm/view');
$updateurl = $this->createUrl('vm/update');
$nodeurl = $this->createUrl('node/view');
$widget = $this->createwidget('ext.zii.CJqGrid', array(
	'extend'=>array(
		'id' => $gridid,
		'locale'=>'en',
		'pager'=>array(
			'Standard'=>array('edit'=>false, 'add' => false, 'del' => false, 'search' => false),
		),
	),
     'options'=>array(
		'pager'=>$gridid . '_pager',
		'url'=>$this->createUrl('/node/getVms', array('node' => $model->sstNode)),
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'uuid','index'=>'uuid','hidden'=>true,'editable'=>false),
			array('name'=>'spice','index'=>'spice','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'name','width'=>'70','editable'=>false),
			array('name'=>'status','index'=>'status','editable'=>false),
			array('name'=>'statusact','index'=>'statusact','width' => 70, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'mem','index'=>'mem','width' => '100', 'fixed' => true, 'align'=>'center', 'search' => false, 'editable'=>false),
			array('name'=>'cpu','index'=>'cpu','width' => '104', 'fixed' => true, 'align'=>'center', 'search' => false, 'editable'=>false, 'hidden'=>true),
			array('name'=>'node','index'=>'node','hidden'=>true,'editable'=>false),
			array ('name' => 'act','index' => 'act','width' => 18 * 2, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
			// 'width' => 124
		),
		//'toolbar' => array(true, "top"),
		//'caption'=>'VMs',
		'autowidth'=>false,
		'rowNum'=>10,
		'rowList'=> array(10,20,30),
		'height'=>200,
		'altRows'=>true,
		'subGrid' => true,
		'subGridUrl' =>$baseurl . '/vm/getVmInfo',
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
				name = '<a href="${detailurl}?dn=' + row['dn'] + '">' + row['name'] + '</a>';
				var statusact = '';
				statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start_n.png" alt="" title="start VM" class="action" />';
//				statusact += '<img id="vm_restart_' + ids[i] + '" src="{$imagesurl}/vm_restart_n.png" alt="" title="reboot VM" class="action" onclick="rebootVm(\'' + ids[i] + '\');" />';
				statusact += '<img id="vm_shutdown_' + ids[i] + '" src="{$imagesurl}/vm_shutdown_n.png" alt="" title="shutdown VM" class="action" />';
				statusact += '<img id="vm_destroy_' + ids[i] + '" src="{$imagesurl}/vm_destroy_n.png" alt="" title="destroy VM" class="action" />';
				statusact += '<img id="vm_migrate_' + ids[i] + '" src="{$imagesurl}/vm_migrate.png" alt="" title="migrate VM" class="action" onclick="selectNode(\'' + ids[i] + '\');" />';
				var act = '';
				act += '<a href="${detailurl}?dn=' + row['dn'] + '"><img src="{$imagesurl}/vm_detail.png" alt="" title="view VM" class="action" /></a>';
				act += '<a id="spice' + i + '" href="' + row['spice'] + '"><img src="{$imagesurl}/vm_login.png" alt="" title="use VM" class="action" /></a>';
				//act += '<a href="${updateurl}?dn=' + row['dn'] + '"><img src="{$imagesurl}/vm_edit.png" alt="" title="edit VM" class="action" /></a>';
				//act += '<img src="{$imagesurl}/vm_del.png" alt="" title="delete VM" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				var node = '<a href="${nodeurl}?node=' + row['node'] + '">' + row['node'] + '</a>';
				$('#{$gridid}_grid').setRowData(ids[i],{'name': name, 'act': act, 'statusact': statusact, 'node': node});
			}
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
EOS
	),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));

return $widget->run();
?>
