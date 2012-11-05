<?php

class ESysLogRoute extends CLogRoute
{

	/**
	 * @var array levels
	 */
	public $levels;

	/**
	 * @var array categories
	 */
	public $categories;

	/**
	 * @var string logName
	 */
	private $_logName;

	/**
	 * @var string logFacility
	 */
	private $_logFacility;

	/**
	 * @var bool isWin
	 */
	private $isWin;

	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
	public function init()
	{
		parent::init();
		$this->isWin = (strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN')? true : false;
		if( null === $this->getLogName() )
			$this->setLogName('YiiApp');

		if( null === $this->getLogFacility() )
			$this->setLogFacility(LOG_USER);

		if(true !== openlog($this->getLogName(), LOG_ODELAY | LOG_PID, $this->getLogFacility()))
			throw new CException('Failed to initiate the logging subsystem.');
	}

	/**
	 * @return string _logName used for identifying our log messages.
	 */
	public function getLogName()
	{
		return $this->_logName;
	}

	/**
	 * @param string logname used for identifying our log messages.
	 */
	public function setLogName($logname)
	{
		$this->_logName = $logname;
	}

	/**
	 * @return constant _logFacility used for syslog facility selection.
	 */
	public function getLogFacility()
	{
		return $this->_logFacility;
	}

	/**
	 * @param constant logfacility used for syslog facility selection.
	 */
	public function setLogFacility($logfacility)
	{
		$this->_logFacility = $logfacility;
	}

	/**
	 * Saves log messages in files.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		foreach($logs as $log) {
			switch($log[1]) {
				case 'trace':
					$pri = ($this->isWin)? LOG_INFO : LOG_DEBUG;
					break;
				case 'info':
					$pri = ($this->isWin)? LOG_INFO : LOG_INFO;
					break;
				case 'profile':
					$pri = ($this->isWin)? LOG_WARNING : LOG_NOTICE;
					break;
				case 'warning':
					$pri = ($this->isWin)? LOG_WARNING : LOG_WARNING;
					break;
				case 'error':
					$pri = ($this->isWin)? LOG_EMERG : LOG_ERR;
					break;
			}
			syslog($pri, $log[1] . ' - (' . $log[2] . ') - ' . $log[0]);
		}

		closelog();
	}

}
