<?php
class PatchInlineAction extends PatchAction
{
	public $inlineObject = null;
	
	public function run($init) {
		return $this->inlineObject->{$this->name}($init, $this->params);
		//call_user_func($this->inlineCallback, $init, $this->params);
	}
	
	public function checkParams() {
		return $this->inlineObject->checkParams($this->name, $this->params);
	}
}