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
	'VMList'=>array('index'),
	'Assigned VMs',
);
$this->title = Yii::t('vmlist', 'Assigned VMs');
//$this->helpurl = Yii::t('help', 'assignedVMs');

$baseUrl = Yii::app()->baseUrl;

$vmcount = count($data['vms']);
$vmpoolcount = count($data['vmpools']);
if (0 == $vmcount + $vmpoolcount) {
	echo Yii::t('vmlist', 'No VM\'s assigned to this user!');
}
else {
	if (0 < $vmcount) {
		echo '<ul class="list">';
		foreach($data['vms'] as $name => $vm) {
			echo '<li class="ui-widget-content" style="border-top: 0px;border-right: 0px;border-left: 0px;">';
			echo '<a href="#" onclick="launch(\'' . $vm['spiceuri'] . '\');" style="float: left;">' . $name . '</a>';
			echo '<div style="float: right; padding-top: 3px; ">';
			if (Yii::app()->user->hasRight('persistentVM', COsbdUser::$RIGHT_ACTION_MANAGE, COsbdUser::$RIGHT_VALUE_ALL)) {
				if (!$vm['active']) {
					echo '<a href="#" onclick="startPersistentVm(\'' . $vm['dn'] . '\');" style="float: left; margin-right: 30px;"><img src="' . $baseUrl . '/images/vm_start.png" title="start VM"/></a>';
				}
				else {
					echo '<img src="' . $baseUrl . '/images/vm_start.png" class="notallowed" title="start VM" style="float: left; margin-right: 30px;" />';
				}
				//echo '<a href="#" onclick="shutdown(\'' . $vm['uuid'] . '\');" style="float: left; margin-right: 10px;"><img src="' . $baseUrl . '/images/vm_shutdown.png" title="shutdown VM"/></a>';
			}
			if (!$vm['active']) {
				echo '<img src="' . $baseUrl . '/images/vm_login.png"  class="notallowed" title="use VM" style="float: left;" />';
			}
			else {
				echo '<a href="#" onclick="launch(\'' . $vm['spiceuri'] . '\');" style="float: left;"><img src="' . $baseUrl . '/images/vm_login.png" title="use VM"/></a>';
			}
			echo '</div>';
			echo '<br class="clear" />';
			echo $vm['description'];
			echo '</li>';
		}
		echo '</ul>';
	}

	if (0 < $vmpoolcount) {
		echo '<br/><br/><h2>Dynamic</h2> <ul class="list">';
		foreach($data['vmpools'] as $name => $vmpool) {
			echo '<li class="ui-widget-content" style="border-top: 0px;border-right: 0px;border-left: 0px;">';
			if (isset($vmpool['spiceuri'])) {
				echo '<a href="#" onclick="launch(\'' . $vmpool['spiceuri'] . '\');" style="float: left;">' . $name . '</a>';
				echo '<a href="#" onclick="launch(\'' . $vmpool['spiceuri'] . '\');" style="float: right; padding-top: 3px;"><img src="' . $baseUrl . '/images/vm_login.png" title="use VM"/></a>';
			}
			else {
				echo '<a href="#" style="float: left;" onclick="assignVm(\'' . $vmpool['dn'] . '\');">' . $name . '</a>';
				echo '<a href="#" style="float: right; padding-top: 3px;" onclick="assignVm(\'' . $vmpool['dn'] . '\');"><img src="' . $baseUrl . '/images/vm_login.png" title="use dyn. VM"/></a>';
			}
			echo '<br class="clear" />';
			echo $vmpool['description'];
			echo '</li>';
		}
		echo '</ul>';
	}
	if (1 == $vmcount + $vmpoolcount) {
		if (1 == $vmcount) {
			$vm = reset($data['vms']);
			$url = $vm['spiceuri'];
			Yii::app()->clientScript->registerScript('refreshVms', <<<EOS
	window.location = "{$url}";
EOS
			, CClientScript::POS_END);
		}
		elseif (1 == $vmpoolcount) {
			$vmpool = reset($data['vmpools']);
			if (array_key_exists('spiceuri', $vmpool)) {
				$url = $vmpool['spiceuri'];
				Yii::app()->clientScript->registerScript('refreshVms', <<<EOS
	window.location = "{$url}";
EOS
				, CClientScript::POS_END);
			}
		}
	}
}
?>
<div style="display: none;">
<a id="startVmLink" href="#startVm">checkcopy</a>
<div id="startVm">
</div>
</div>
<?php
	$startVmGuiUrl = $this->createUrl('getStartVmGui');
	$startVmUrl = $this->createUrl('startVm');
	$assignVmGuiUrl = $this->createUrl('getAssignVmGui');
	$assignVmUrl = $this->createUrl('assignVm');
	Yii::app()->clientScript->registerScript('startVm', <<<EOS
	function startVm(dn) {
		$('#startVmLink').fancybox({
			'modal'			: false,
			'href'			: '{$startVmGuiUrl}',
			'type'			: 'inline',
			'autoDimensions': false,
			'width'			: 450,
			'height'		: 120,
			'scrolling'		: 'no',
			'hideOnOverlayClick' : false,
			'onComplete'	: function() {
				$.ajax({
					url: "{$startVmUrl}",
					data: 'dn=' + dn,
					success: function(data) {
						if (!data['err']) {
//							$('#running').css('display', 'none');
							$('#errorAssignment').css('display', 'none');
							$('#infoAssignment').css('display', 'block');
							$('#infoMsg').html(data['message']);
							setTimeout('launch("' + data['spiceuri'] + '")', 4000);
						}
						else {
							$('#infoAssignment').css('display', 'none');
							$('#errorAssignment').css('display', 'block');
							$('#errorMsg').html(data['message']);
						}
						//$('#startVm').hide();
						//$.fancybox.close();
					},
					dataType: 'json'
				});
			},
		});
		$('#startVmLink').trigger('click');
	}
	function assignVm(dn) {
		$('#startVmLink').fancybox({
			'modal'			: false,
			'href'			: '{$assignVmGuiUrl}',
			'type'			: 'inline',
			'autoDimensions': false,
			'width'			: 450,
			'height'		: 150,
			'scrolling'		: 'yes',
			'hideOnOverlayClick' : false,
			'onComplete'	: function() {
				$.ajax({
					url: "{$assignVmUrl}",
					data: 'dn=' + dn,
					success: function(data) {
						if (!data['err']) {
							$('#errorAssignment').css('display', 'none');
							$('#infoAssignment').css('display', 'block');
							$('#infoMsg').html(data['message']);
							setTimeout('launch("' + data['spiceuri'] + '")', 1000);
						}
						else {
							$('#infoAssignment').css('display', 'none');
							$('#errorAssignment').css('display', 'block');
							$('#errorMsg').html(data['message']);
						}
					},
					dataType: 'json'
				});
			},
		});
		$('#startVmLink').trigger('click');
	}

	function launch(uri) {
		$.fancybox.close();
		var loc = window.location;
		window.location = uri;
		setTimeout('window.location = "' + loc + '";window.location.reload();', 1000);
	}
	function startPersistentVm(dn)
	{
		$.ajax({
			url: "{$baseUrl}/vm/startVm",
			cache: false,
			dataType: 'xml',
			data: 'dn=' + dn,
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
	}
EOS
	, CClientScript::POS_END);

$this->createWidget('ext.fancybox.EFancyBox');

?>