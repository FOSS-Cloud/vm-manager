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

class VmIsoUploadForm extends CFormModel {
	public $isofile;
	public $name;
	public $upstatus;

	public function rules()
	{
		return array(
			array('upstatus', 'required'),
			array('isofile', 'file', 'allowEmpty' => true),
			array('name', 'uniqueFileName'),
		);
	}

	public function uniqueFileName($attribute, $params) {
		$value = $this->$attribute;
		if ('.iso' != substr($value, strlen($value) - 4)) {
			$value .= '.iso';
		}
		if (is_file(LdapStoragePoolDefinition::getPathByType('iso-choosable') . $value)) {
			$this->addError($attribute, Yii::t('vmprofile', 'Name already in use!'));
		}
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'isofile' => Yii::t('vmprofile', 'Iso File'),
			'name' => Yii::t('vmprofile', 'File Name'),
		);
	}
}