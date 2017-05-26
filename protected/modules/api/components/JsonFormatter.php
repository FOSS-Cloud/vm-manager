<?php

/*
 * Copyright (C) 2006 - 2017 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Sören Busse <soeren.2011@live.de>
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

class JsonFormatter
{
	// HTTP-Status return code
	private $httpStatusCode;

	// The full error code to refer to the error message
	private $code;

	// A short description for the developer
	private $devDescription;

	// An array with return data
	// Must be a key-value pair
	private $data = array();

	/**
	 * JsonFormatter constructor.
	 * @param $httpStatusCode
	 * @param $code
	 */
	public function __construct($httpStatusCode, $code)
	{
		$this->httpStatusCode = $httpStatusCode;
		$this->code = $code;
	}

	/**
	 * Sets the Developer description
	 * @param $devDescription
	 * @return $this
	 */
	public function setDevDescription($devDescription)
	{
		$this->devDescription = $devDescription;
		return $this;
	}

	/**
	 * Add data as an array which will be returned as json
	 * @param $data
	 * @return $this
	 */
	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Displays the constructed message and "die" afterwards
	 */
	public function display()
	{
		// Set Contenttype to JSON
		header('Content-Type: application/json');

		// Sets the HTTP-Response Code
		http_response_code($this->httpStatusCode);

		// Show message
		die($this->getString());
	}

	/**
	 * Returns the constructed message as json string
	 */
	public function getString()
	{
		// Build the json header
		$jsonHeader = array();
		$jsonHeader["status"] = $this->httpStatusCode;
		$jsonHeader["code"] = $this->code;

		if ($this->devDescription != null)
			$jsonHeader["devDescription"] = $this->devDescription;

		// Build the json string
		return json_encode(array_merge($jsonHeader, $this->data), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
	}
}