<?php

//namespace patch\components;

class PatchActionException extends PatchException {
	public function __construct($action, $message) {
		parent::__construct(PatchModule::t('patch', 'Error in line {lineno}: Action "{actionName}"; {message}', array('{lineno}' => $action->lineno, '{actionName}' => $action->name, '{message}' => $message)));
	}
}