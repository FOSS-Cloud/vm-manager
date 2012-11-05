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
	'VM'=>array('index'),
	$model->sstDisplayName,
);
$this->title = Yii::t('vm', 'VM Details "{name}"', array('{name}' => $model->sstdisplayName));
//$this->helpurl = Yii::t('help', 'viewVM');

ob_start();
foreach($model->devices->disks as $disk) {
?>
	<b><?=$disk->sstDevice . ': ' . $disk->sstDisk;?></b> <i><?=('TRUE' == $disk->sstReadonly ? 'readonly' : '');?></i><br/>
	<div class="row">
		<b><?=$disk->getAttributeLabel('sstSouceFile');?>:</b><br/>
		<?=$disk->sstSourceFile;?>
	</div>
	<br/>
<?php
}
foreach($model->devices->interfaces as $interface) {
?>
	<b>interface: <?=$interface->sstInterface;?></b><br/>
	<div class="row">
		<b><?=$interface->getAttributeLabel('sstMacAddress');?>:</b><br/>
		<?=$interface->sstMacAddress;?>
	</div>
	<br/>
<?php
}
$devices = ob_get_contents();
ob_end_clean();

$uarray = array();
foreach ($user as $u) {
	$uarray[$u->uid] = $u->getName() . ($u->isAdmin() ? ' (Admin)' : ' (User)');
}
ob_start();
$dual = $this->createWidget('ext.zii.CJqDualselect', array(
	'id' => 'userAssignment',
	'values' => $uarray,
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
$dual->run();
$dual = ob_get_contents();
ob_end_clean();

$widget = $this->createWidget('zii.widgets.jui.CJuiTabs', array(
    'tabs'=>array(
        'General'=> <<<EOS
	<div class="row">
		<b>{$model->getAttributeLabel('DisplayName')}:</b>
		{$model->sstDisplayName}
		<br />
	</div>
	<div class="row">
		<b>{$model->getAttributeLabel('Node')}:</b>
		{$model->sstNode}
		<br />
	</div>
EOS
,
	'Basic resources'=> <<<EOS
	<div class="row">
	</div>
EOS
,
/*
	'User Assignment'=> <<<EOS
	{$dual}
	<button id="saveAssignment" style="margin-top: 10px;"></button>
EOS
,
*/
'Devices'=> $devices,
),
     // additional javascript options for the tabs plugin
    'options'=>array(
        'collapsible'=>true,
   		'spinner'=>'<img src="' . Yii::app()->baseUrl . '/images/loading.gif" style="border: 0; width: 12px;" /> <em>Loading&#8230;</em>',
    ),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
    ));
$widget->setId('tabpanel');
$widget->htmlOptions['style'] = 'margin-bottom: 10px;';
$widget->headerTemplate='<li><a href="{url}"><span>{title}</span></a></li>';
$widget->run();

$savetxt = Yii::t('vm', 'Save');
Yii::app()->clientScript->registerScript('viewVMs', <<<EOS
	$('#saveAssignment').button({icons: {primary: "ui-icon-disk"}, label: '{$savetxt}'})
		.click(function() {
			var selected = $('#userAssignment_dualselect').dualselect("values");
			var a = selected.length;
			alert('a ' + a);
		});
EOS
, CClientScript::POS_READY);

?>
