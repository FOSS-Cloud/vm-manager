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
class CJqGrid extends CJuiWidget
{
	public $gridScriptFile=array('jquery.jqGrid.min.js', 'grid.formedit.js');
	public $gridI18nFile='grid.locale-{locale}.js';
	public $gridCssFile='ui.jqgrid.css';

	public $extend=array();
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
		Yii::log("CJqGrid run");
		Yii::log(print_r($this->extend, true));
		$id=$this->extend['id']; //getId();
		if (!isset($this->extend['containerAttr']))
		{
			$this->extend['containerAttr'] = array();
		}

		echo CHtml::openTag($this->tagName, $this->extend['containerAttr'])."\n";

		$grid = strtr($this->gridTemplate, array('{id}'=>$id . '_grid'))."\n";
		$pager = strtr($this->pagerTemplate, array('{id}'=>$id . '_pager'))."\n";
		echo $grid;
		echo $pager;

		echo CHtml::closeTag($this->tagName)."\n";

		$options = empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::log(print_r($this->options, true));

		$script = '';
		if (isset($this->options['editurl'])) {
//			$script = 'var ' . $id . '_lastsel=-1;' . "\n";
//			$script .= <<<EOS
//function editRow(id)
//{
//	if(id && id != {$id}_lastsel)
//	{
//		$('#{$id}_grid').restoreRow({$id}_lastsel);
//		{$id}_lastsel=id;
//	}
//	var row = $('#{$id}_grid').getRowData(id);
//	$('#{$id}_grid').editRow(id,true);
//}
//function deleteRow(id)
//{
//	$('#{$id}_grid').delGridRow(id, {});
//}
//EOS;
		}
		$script .= '$(\'#' . $id . '_grid\').jqGrid(' . $options . ')';
		if (isset($this->extend['pager']['Standard']))
		{
			$script .= '.jqGrid(\'navGrid\', \'#' . $id . '_pager\', {';
			foreach($this->extend['pager']['Standard'] as $key => $val)
			{
				$script .= '\'' . $key . '\':' . CJavaScript::encode($val) . ',';
			}
			$script .= '}, {}, {}, {}, {}, {})';
		}
		$script .= ';' . "\n";
		$script .= '$(document).ready(function(){' . "\n";
		foreach($this->extend['pager'] as $caption => $button)
		{
			if ('Standard' != $caption)
			{
				$script .= '$(\'#' . $id . '_grid\').jqGrid(\'navButtonAdd\',\'#' . $id . '_pager\',{caption:"' . $caption . '"';
				foreach ($button as $key => $val)
				{
					$script .= ',\'' . $key . '\':' . CJavaScript::encode($val);
				}
				$script .= '});' . "\n";
			}
		}
		if (isset($this->extend['filter']))
		{
			//$script .= '$(\'#t_' . $id . '_grid\').jqGrid(\'filterGrid\',\'#' . $id . '_grid\', {';
			$script .= '$(\'#' . $id . '_grid\').jqGrid(\'filterToolbar\', {';
			foreach($this->extend['filter'] as $key => $val)
			{
				$script .= '\'' . $key . '\':' . CJavaScript::encode($val) . ',';
			}
			$script .= '});' . "\n";
		}
		$script .= '});';
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id, $script, CClientScript::POS_END);
	}

	/**
	 * Registers the core script files.
	 * This method registers jquery and JUI JavaScript files and the theme CSS file.
	 */
	protected function registerCoreScripts()
	{
		parent::registerCoreScripts();

		$this->scriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('ext.zii')).'/assets';

		$cs=Yii::app()->getClientScript();
		if(is_string($this->gridCssFile))
			$cs->registerCssFile($this->scriptUrl.'/css/'.$this->gridCssFile);
		else if(is_array($this->gridCssFile))
		{
			foreach($this->gridCssFile as $cssFile)
				$cs->registerCssFile($this->scriptUrl.'/css/'.$cssFile);
		}

		if (!isset($this->extend['locale']))
		{
			$this->extend['locale'] = 'en';
		}
		$cs->registerScriptFile($this->scriptUrl.'/i18n/'.strtr($this->gridI18nFile, array('{locale}'=>$this->extend['locale'])));

		if(is_string($this->gridScriptFile))
			$cs->registerScriptFile($this->scriptUrl.'/js/'.$this->gridScriptFile);
		else if(is_array($this->gridScriptFile))
		{
			foreach($this->gridScriptFile as $scriptFile)
				$cs->registerScriptFile($this->scriptUrl.'/js/'.$scriptFile);
		}
	}

}
