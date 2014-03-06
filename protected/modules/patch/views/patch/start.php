<?php
$messagestyle = $messageclass = '';
if ($error) {
	$messageclass = 'flash-error';
}
else {
	$messagestyle = 'display: none;';
}
?>
<div id="message" class="<?php echo $messageclass?>" style="<?php echo $messagestyle; ?>"><?php echo $message;?></div>
<div id="actions">
</div>
<?php
if (!$error) {
	$oktext = PatchModule::t('patch', 'OK');
	$canceltext = PatchModule::t('patch', 'CANCEL');
	$startUrl = $this->createUrl('patch/start');
	$processUrl = $this->createUrl('patch/process');
	Yii::app()->getClientScript()->registerScript('patch', <<<EOS
		$("#startdialog").dialog({
			modal: true,
			height: 'auto',
			width: 'auto',
			buttons:   [
				{
					text: '{$oktext}',
					click: function() {
						var that = $(this);
						$.get('{$startUrl}', {name: '{$patchname}', key: '{$patchkey}'}, function(data) {
							if (1 == data.error) {
								$("#startmessage").show().html(data.message);
							}
							else {
								that.dialog('close');
								$("#progress").show();
								showProgress(data);
								setTimeout('process()', 100);
							}
						}, 'json');
					}
				},
				{
					text: '{$canceltext}',
					click: function() {
						$(this).dialog('close');
					}
				}
			]
		});
		$("#progresspart div.progressbar").progressbar({
			change: function() {
				$("#progresspart .progressbar-label").text($("#progresspart .progressbar").progressbar("value") + "%");
			},
		});
		$("#progresstotal div.progressbar").progressbar({
			change: function() {
				$("#progresstotal .progressbar-label").text($("#progresstotal .progressbar").progressbar("value") + "%");
			},
		});
					
		function showProgress(data) {
			$("#progresspart .progresstext").html(data.parttext);
			$("#progresspart .progressbar").progressbar('value', data.partvalue);
			$("#progresspart .progressbar-label").text(data.partvalue + "%");
			$("#progresstotal .progresstext").html(data.totaltext);
			$("#progresstotal .progressbar").progressbar('value', data.totalvalue);
			$("#progresstotal .progressbar-label").text(data.totalvalue + "%");
		}
					
		function process() {
			$.get('{$processUrl}', {name: '{$patchname}', key: '{$patchkey}'}, function(data) {
				if (1 == data.error) {
					$("#message").addClass('flash-error').html(data.message).show();
				}
				else {
					showProgress(data);
					if (100 > data.totalvalue) {
						setTimeout('process()', 100);
					}
					else {
						$("#message").addClass('flash-success').html(data.message).show();
					}
				}
			}, 'json');
		}
EOS
	, CClientScript::POS_END);
}

//echo $patch;
?>

<div id="progress" style="display: none;">
	<div id="progresstotal" class="ui-state-default ui-corner-all" style="margin-bottom: 10px;">
		<div class="ui-widget-header" style="padding: 0.4em 1em;"><?php echo PatchModule::t('patch', 'Total progress')?></div>
		<p style="margin: 10px;" class="progresstext"></p>
		<div style="margin: 10px; position: relative;" class="progressbar"><div class="progressbar-label" style="position: absolute;left: 50%;top: 4px;font-weight: bold;text-shadow: 1px 1px 0 #fff;"></div></div>
	</div>
	<div id="progresspart" class="ui-state-default ui-corner-all" style="margin-bottom: 10px;">
		<div class="ui-widget-header" style="padding: 0.4em 1em;"><?php echo PatchModule::t('patch', 'Part progress')?></div>
		<p style="margin: 10px;" class="progresstext"></p>
		<div style="margin: 10px; position: relative;" class="progressbar"><div class="progressbar-label" style="position: absolute;left: 50%;top: 4px;font-weight: bold;text-shadow: 1px 1px 0 #fff;"></div></div>
	</div>
</div>
<div id='startdialog' title="<?php echo PatchModule::t('patch', 'start patch')?>" style="display: none;">
	<p><?php echo PatchModule::t('patch', 'Do you really want to start this patch?')?></p>
	<div id="startmessage" style="display: none;" class="flash-error"></div>
</div>