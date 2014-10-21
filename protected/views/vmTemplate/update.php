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
	'VmTemplate'=>array('index'),
	$model->name,
	'Edit',
);
$this->title = Yii::t('vmtemplate', 'Edit VMTemplate "{name}"', array('{name}' => $model->name));
//$this->helpurl = Yii::t('help', 'editVmTemplate');

echo $this->renderPartial('_form', array('model'=>$model,'vmpools'=>$vmpools,'nodes'=>$nodes,'profiles' =>$profiles,'ranges'=>$ranges,'defaults'=>$defaults,'submittext'=>Yii::t('vmtemplate','Save')));