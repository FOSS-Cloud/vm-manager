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
	'VmProfile'=>array('index'),
	'UploadIso',
);
$this->title = Yii::t('vmprofile', 'Upload Iso File');
//$this->helpurl = Yii::t('help', 'uploadIso');

$this->widget('application.extensions.plupload.PluploadWidget',
	array(
		'config' => array(
			//'runtimes' => 'gears,flash,silverlight,browserplus,html5',
			'runtimes' => 'flash,html5',
			'url' => $this->createUrl('vmProfile/uploadIsoPart'),
			//'max_file_size' => str_replace("M", "mb", ini_get('upload_max_filesize')),
			//'max_file_size' => Yii::app()->params['maxFileSize'],
			'max_file_size' => '6000mb',
			'chunk_size' => '10mb',
			'unique_names' => true,
			'filters' => array(
				array('title' => Yii::t('app', 'ISO files'), 'extensions' => 'iso'),
			),
			'language' => Yii::app()->language,
			'max_file_number' => 1,
			'autostart' => true,
			'jquery_ui' => false,
			'reset_after_upload' => true,
		),
		'callbacks' => array(
			'FileUploaded' => 'function(up,file,response){console.log(response.response);}',
		),
		'id' => 'uploader'
	)
);
