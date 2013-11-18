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

/**
 * ForeignGroupForm class.
 * ForeignGroupForm is the data structure for keeping one foreigen group.
 */
class ForeignGroupForm extends CFormModel
{
	public $selected = false;
	public $static;
	public $name;
	public $savedName = '';
	public $found = false;
	public $diffName = false;
	public $local;
	public $message = '';

	public function rules()
	{
		return array(
			array('selected, static, name, savedName, found, diffName, message', 'safe'),
			array('local', 'required'),
		);
	}
}