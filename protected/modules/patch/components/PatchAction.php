<?php

//namespace patch\components;

abstract class PatchAction extends CComponent
{
	public $order = 0;
	public $lineno = -1;
	public $name = '';
	public $description = '';
	public $params = array();
	public $patchPath = '';
	
	protected $errno = 0;
	protected $errstr = '';

	public abstract function checkParams();
	public abstract function run($init);
	
	protected function parseFilename($filename) {
		if (0 === strpos($filename, '{app}')) {
			$filename = str_replace('{app}', Yii::app()->getBasePath(), $filename);
		}
		else if (0 === strpos($filename, '{modules}')) {
			$filename = str_replace('{modules}', Yii::app()->getModulePath(), $filename);
		}
		else if (0 === strpos($filename, '{patch}')) {
			$filename = str_replace('{patch}', $this->patchPath, $filename);
		}
		return $filename;
	}
	

	protected function errorHandler($errno , $errstr, $errfile, $errline, $errcontext) {
		$this->errno = $errno;
		$this->errstr = $errstr;
	}
	
}
