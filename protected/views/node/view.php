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
	'Node'=>array('index'),
	$model->sstNode,
);

$this->title = Yii::t('node', 'View Node');
//$this->helpurl = Yii::t('help', 'viewNode');

Yii::app()->getClientScript()->registerScript('refreshVms', <<<EOS
$('#tabpanel').tabs([]);
EOS
, CClientScript::POS_READY);
?>
<div id="tabpanel">
	<ul>
		<li><a href="#tab1">General</a></li>
		<li><a href="#tab2">VMs</a></li>
	</ul>
	<div id="tab1" title="General">
		<div class="row">
			<b><?php echo $model->getAttributeLabel('node'); ?>: </b><?php echo $model->sstNode;?><br /><br />
			<b>pub IP: </b><?php echo $model->getVLanIP('pub');?><br />
			<b>data IP: </b><?php echo $model->getVLanIP('data');?><br />
			<b>int IP: </b><?php echo $model->getVLanIP('int');?><br />
			<b>admin IP: </b><?php echo $model->getVLanIP('admin');?><br />
			<b style="float: left;"><?php echo $model->getAttributeLabel('type'); ?>:&nbsp;</b><div style="float: left;"><?php foreach($model->types as $type) echo $type->sstNodeType . '<br/>';?></div>
			<br style="clear: both;" />
		</div>
	</div>
	<div id="tab2" title="VMs">
		<?php include (dirname(__FILE__) . '/_viewVms.php'); ?>
	</div>
</div>
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
		<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>
