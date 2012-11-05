<?php
/*
 * EFancyBox widget class file.
 * @author Thiago Otaviani Vidal <thiagovidal@othys.com>
 * @link http://www.othys.com
 * Copyright (c) 2010 Thiago Otaviani Vidal
 * MADE IN BRAZIL

 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:

 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.

 * EFancyBox extends CWidget and implements a base class for a fancybox widget.
 * more about fancybox can be found at http://fancybox.net/.
 * @version: 1.2
 */
class EFancyBox extends CWidget
{
	public $target;
	public $easingEnabled=false;
	public $mouseEnabled=true;
	public $config=array();

	public function init()
	{
		$this->publishAssets();
	}

    public function run()
    {
		$config = CJavaScript::encode($this->config);
		Yii::app()->clientScript->registerScript(__CLASS__, "
			$('$this->target').fancybox($config);
		");
	}

	public function publishAssets()
	{
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		if(is_dir($assets)){
			Yii::app()->clientScript->registerCoreScript('jquery');
			//Yii::app()->clientScript->registerScriptFile($baseUrl . '/jquery.fancybox-1.3.4.pack.js', CClientScript::POS_HEAD);
			Yii::app()->clientScript->registerScriptFile($baseUrl . '/jquery.fancybox-1.3.4.js', CClientScript::POS_HEAD);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/jquery.fancybox-1.3.4.css');
			if ($this->mouseEnabled) {
				Yii::app()->clientScript->registerScriptFile($baseUrl . '/jquery.mousewheel-3.0.4.pack.js', CClientScript::POS_HEAD);
			}
			if ($this->easingEnabled) {
				Yii::app()->clientScript->registerScriptFile($baseUrl . '/jquery.easing-1.3.pack.js', CClientScript::POS_HEAD);
			}
		} else {
			throw new Exception('EFancyBox - Error: Couldn\'t find assets to publish.');
		}
	}
}