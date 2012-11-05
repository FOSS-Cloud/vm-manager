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

class VmPoolForm extends CFormModel {
	public $dn=null;					/* used for update */
	public $displayName;
	public $description;
	public $storagepool;
	public $nodes;
	public $range = null;
	public $brokerMin = -1;
	public $brokerMax = -1;
	public $brokerPreStart = -1;
	public $type;

	public function rules()
	{
		return array(
			array('storagepool, displayName, description, nodes, range, type', 'required', 'on' => 'create'),
			array('dn', 'safe', 'on' => 'create'),
			array('dn, storagepool, displayName, description, nodes, range', 'required', 'on' => 'update'),
			array('type', 'safe', 'on' => 'update'),
			array('brokerMin, brokerMax, brokerPreStart, nodes, range', 'safe'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'displayName' => Yii::t('vmpool', 'displayName'),
			'description' => Yii::t('vmpool', 'description'),
			'storagepool' => Yii::t('vmpool', 'storagepool'),
			'nodes' => Yii::t('vmpool', 'nodes'),
			'range' => Yii::t('vmpool', 'range'),
			'brokerMin' => Yii::t('vmpool', 'brokerMin'),
			'brokerMax' => Yii::t('vmpool', 'brokerMax'),
			'brokerPreStart' => Yii::t('vmpool', 'brokerPreStart'),
		);
	}
}