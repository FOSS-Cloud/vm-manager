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
	'Node'=>array('index'),
	$model->sstNode,
);

$this->title = Yii::t('node', 'View Node {name}', array('{name}' => $model->sstNode));
//$this->helpurl = Yii::t('help', 'viewNode');
?>

		<div class="row">
			<span style="font-weight: bold; width: 100px; display: inline-block;">pub IP: </span><?php echo $model->getVLanIP('pub');?><br />
			<span style="font-weight: bold; width: 100px; display: inline-block;">data IP: </span><?php echo $model->getVLanIP('data');?><br />
			<span style="font-weight: bold; width: 100px; display: inline-block;">int IP: </span><?php echo $model->getVLanIP('int');?><br />
			<span style="font-weight: bold; width: 100px; display: inline-block;">admin IP: </span><?php echo $model->getVLanIP('admin');?><br /><br />
			<span style="font-weight: bold; width: 100px; display: inline-block; float: left;"><?php echo $model->getAttributeLabel('type'); ?>:&nbsp;</span><div style="float: left;"><?php foreach($model->types as $type) echo $type->sstNodeType . '<br/>';?></div>
			<br style="clear: both;" />
		</div>
