<?php

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