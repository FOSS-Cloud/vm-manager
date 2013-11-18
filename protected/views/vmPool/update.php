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
	'VM Pool'=>array('index'),
	$model->displayName,
	'Update',
);
$this->title = Yii::t('vmpool', 'Edit VM Pool "{name}"', array('{name}' => $model->displayName));
//$this->helpurl = Yii::t('help', 'updateUser');

echo $this->renderPartial('_form', array(
		'model'=>$model,
		'storagepools'=>$storagepools,
		'nodes'=>$nodes,
		'ranges'=>$ranges,
		'types'=>$types,
		'vmcount'=>$vmcount,
		'globalSound'=>$globalSound,
		'globalUsb'=>$globalUsb,
		'submittext'=>Yii::t('vmpool','Save')));