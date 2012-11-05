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

class StoragePoolForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $sstDisplayName;
	public $description;
	public $sstStoragePoolType;
	public $sstStoragePoolURI;

	public function rules()
	{
		return array(
			array('sstDisplayName, description, sstStoragePoolType', 'required', 'on' => 'create'),
			array('sstDisplayName, description', 'required', 'on' => 'update'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'sstDisplayName' => Yii::t('storagepool', 'sstDisplayName'),
			'description' => Yii::t('storagepool', 'description'),
			'sstStoragePoolURI' => Yii::t('storagepool', 'sstStoragePoolURI'),
			'sstStoragePoolType' => Yii::t('storagepool', 'sstStoragePoolType'),
		);
	}
}