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
 * Licensed under the EUPL, Version 1.1 or higher - as soon they
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

class COsbdForm extends CFormModel {
	/**
	 * Returns a value indicating whether the attribute is oneOf.
	 * This is determined by checking if the attribute is associated with a
	 * {@link COneOfValidator} validation rule in the current {@link scenario}.
	 * @param string $attribute attribute name
	 * @return boolean whether the attribute is oneOf
	 */
	public function isAttributeOneOf($attribute)
	{
		foreach($this->getValidators($attribute) as $validator)
		{
			if($validator instanceof COneOfValidator)
				return true;
		}
		return false;
	}


}