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
 * Licensed under the EUPL, Version 1.1 or � as soon they
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

/**
 * WizardNodeTest class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

class WizardNodeTest extends WizardActions {
	public function __construct() {
		$config = require(Yii::app()->getBasePath() . '/../wizards.php');
		parent::__construct($config['node'], 'test', 'node/handleWizardAction');
	}

	public function rules() {
		$retval = parent::rules();
		$retval[] = array('nodename', 'uniqueName');

		return $retval;
	}

	public function uniqueName($attribute,$params) {
		$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array('sstNode'=>$this->$attribute)));
		if(0 < count($nodes)) {
			$this->addError($attribute, Yii::t('node', '"{nodename}" is already integrated!', array('{nodename}' =>$this->$attribute)));
		}
	}
}
