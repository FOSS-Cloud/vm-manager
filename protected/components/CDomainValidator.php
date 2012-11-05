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

/**
 * CDomainValidator class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 1.0
 */

class CDomainValidator extends CValidator
{
	/**
	 * @var string the regular expression used to validate the attribute value.
	 * @see http://www.regular-expressions.info
	 */
	public $pattern='/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
	/**
	 * @var string the regular expression used to validate domain name for DNS.
	 * This property is used only when {@link useDns} is true.
	 * @since 1.0
	 * @see allowName
	 */
	public $dnsPattern='/^"(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?"$/';
	/**
	 * @var boolean whether to use the DNS pattern. Defaults to false.
	 * @since 1.0
	 * @see dnsPattern
	 */
	public $useDns=false;
	/**
	 * @var boolean whether the attribute value can be null or empty. Defaults to true,
	 * meaning that if the attribute is empty, it is considered valid.
	 */
	public $allowEmpty=true;

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->useDns && $this->isEmpty($value))
			return;
		if(!$this->validateValue($value))
		{
			$message=$this->message!==null?$this->message:Yii::t('system','{attribute} is not a valid domain name.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Validates a static value to see if it is a valid email.
	 * Note that this method does not respect {@link allowEmpty} property.
	 * This method is provided so that you can call it directly without going through the model validation rule mechanism.
	 * @param mixed $value the value to be validated
	 * @return boolean whether the value is a valid email
	 * @since 1.1.1
	 */
	public function validateValue($value)
	{
		$valid=false;
		if (!$this->useDns) {
			$valid = is_string($value) && preg_match($this->pattern,$value);
		}
		else {
			$valid = is_string($value) && preg_match($this->dnsPattern,$value);
		}
		return $valid;
	}
}
