<?php
class PatchInlineAction extends PatchAction
{
	public $inlineObject = null;
	
	public function run($init) {
		return $this->inlineObject->{$this->name}($init, $this->params);
	}
	
	public function checkParams() {
		return $this->inlineObject->checkParams($this->name, $this->params);
	}
}