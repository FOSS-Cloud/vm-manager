<?php
/**
 * CJqGrid class file.
 *
 * @author Christian Wittkowski <wittkowski@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2010 DevRoom
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * CJqGrid displays a extended Table.
 *
 * CJqGrid encapsulates the {@link http://trirand.com/blog/jqgrid/jqgrid.html HqGrid}
 * plugin.
 *
 * To use this widget, you may insert the following code in a view:
 * <pre>
 * $this->widget('ext.zii.CJqGrid', array(
 *     'extend'=>array(
 *         'locale'=>'de',
 *         'pager'=>array(
 *             'Search'=>array('title'=>"Toggle Search", 'onClickButton' => "function()"),
 *            ),
 *     ),
 *     // additional javascript options for the tabs plugin
 *     'options'=>array(
 *         'collapsible'=>true,
 *     ),
 * ));
 * </pre>
 *
 * By configuring the {@link options} property, you may specify the options
 * that need to be passed to the JUI tabs plugin. Please refer to
 * the {@link http://jqueryui.com/demos/tabs/ JUI tabs} documentation
 * for possible options (name-value pairs).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiTabs.php 158 2010-03-26 20:34:24Z sebathi $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJqDualselect extends CJuiWidget
{
	public $dualScriptFile=array('jquery.dualselect.js');
	public $dualCssFile='dualselect_core.css';

	public $values=array();
	public $size = 3;
	public $multiselect = true;
	/**
	 * @var string the name of the container element that contains all panels. Defaults to 'div'.
	 */
	public $tagName='div';
	/**
	 * @var string the template that is used to generated every panel title.
	 * The token "{title}" in the template will be replaced with the panel title and
	 * the token "{url}" will be replaced with "#TabID" or with the url of the ajax request.
	 */
	public $gridTemplate='<table id="{id}"></table>';
	/**
	 * @var string the template that is used to generated every tab content.
	 * The token "{content}" in the template will be replaced with the panel content
	 * and the token "{id}" with the tab ID.
	 */
	public $pagerTemplate='<div id="{id}"></div>';

	/**
	 * Run this widget.
	 * This method registers necessary javascript and renders the needed HTML code.
	 */
	public function run()
	{
		Yii::log("CJqDualselect run");
		Yii::log(print_r($this->values, true));

		echo CHtml::openTag($this->tagName)."\n";
		echo '<select id="' . $this->id . '_dualselect" size="' . $this->size . '"';
		if ($this->multiselect) {
			echo ' multiple="multiple"';
		}
		echo '>';
		foreach($this->values as $key => $value) {
			echo '<option value="' . $key . '"' . ((isset($value['selected']) && true == $value['selected']) ? ' selected="selected"' : '') . '>' . $value['name'] . '</option>';
		}
		echo '</select>';

		echo CHtml::closeTag($this->tagName)."\n";

		$options = empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::log(print_r($this->options, true));

		$script = '$(\'#' . $this->id . '_dualselect\').dualselect(' . $options . ');';
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$this->id, $script, CClientScript::POS_READY);
	}

	/**
	 * Registers the core script files.
	 * This method registers jquery and JUI JavaScript files and the theme CSS file.
	 */
	protected function registerCoreScripts()
	{
		parent::registerCoreScripts();

		$assets = dirname(__FILE__).'/dualselect_assets';
		$this->scriptUrl = Yii::app()->assetManager->publish($assets);

		//$this->scriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('ext.zii.dualselect_assets'));

		$cs=Yii::app()->getClientScript();
		if(is_string($this->dualCssFile))
			$cs->registerCssFile($this->scriptUrl.'/css/'.$this->dualCssFile);
		else if(is_array($this->dualCssFile))
		{
			foreach($this->dualCssFile as $cssFile)
				$cs->registerCssFile($this->scriptUrl.'/css/'.$cssFile);
		}

		if(is_string($this->dualScriptFile))
			$this->registerScriptFile('js/'.$this->dualScriptFile);
		else if(is_array($this->dualScriptFile))
		{
			foreach($this->dualScriptFile as $scriptFile)
				$this->registerScriptFile('js/'.$scriptFile);
		}
	}

}
