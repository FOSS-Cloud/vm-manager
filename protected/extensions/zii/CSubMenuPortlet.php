<?php
/**
 * CPortlet class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2010 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.CPortlet');

/**
 * CPortlet is the base class for portlet widgets.
 *
 * A portlet displays a fragment of content, usually in terms of a block
 * on the side bars of a Web page.
 *
 * To specify the content of the portlet, override the {@link renderContent}
 * method, or insert the content code between the {@link CController::beginWidget}
 * and {@link CController::endWidget} calls. For example,
 *
 * <pre>
 * &lt;?php $this->beginWidget('zii.widgets.CPortlet'); ?&gt;
 *     ...insert content here...
 * &lt;?php $this->endWidget(); ?&gt;
 * </pre>
 *
 * A portlet also has an optional {@link title}. One may also override {@link renderDecoration}
 * to further customize the decorative display of a portlet (e.g. adding min/max buttons).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPortlet.php 104 2010-01-09 20:46:58Z qiang.xue $
 * @package zii.widgets
 * @since 1.1
 */
class CSubMenuPortlet extends CPortlet
{
	/**
	 * @var string the name of the portlet. Defaults to null.
	 * Needed for jquery functionality.
	 */
	public $name;

	/**
	 * @var boolean show items on start
	 * Needed for jquery functionality.
	 */
	public $show = false;

	public $static = false;

	/**
	 * Initializes the widget.
	 * This renders the open tags needed by the portlet.
	 * It also renders the decoration, if any.
	 */
	public function init()
	{
		$this->htmlOptions['id'] = $this->getId();
		echo CHtml::openTag($this->tagName, $this->htmlOptions)."\n";
		$this->renderDecoration();
		if ($this->show) {
			$style = '';
		}
		else if ($this->static) {
			$style = '';
		}
		else {
			$style = 'display: none;';
		}
		echo "<div class=\"{$this->contentCssClass}\" id=\"{$this->name}_items\" style=\"{$style}\">\n";
	}

	/**
	 * Renders the content of the portlet.
	 */
	public function run()
	{
		$this->renderContent();
		$content=ob_get_clean();
		echo $content;
		echo "</div>\n";
		echo CHtml::closeTag($this->tagName);
	}

	/**
	 * Renders the decoration for the portlet.
	 * The default implementation will render the title if it is set.
	 */
	protected function renderDecoration()
	{
		if($this->title !== null)
		{
			$baseUrl = Yii::app()->baseUrl;
			echo "<div class=\"{$this->decorationCssClass}\">\n";
			if (!$this->static) {
				$imgsrc = $baseUrl . '/images/submenu_' . ($this->show ? 'open' : 'close') . '.png';
				echo "<div class=\"{$this->titleCssClass}\"><img id=\"{$this->name}_toggle\" src=\"{$imgsrc}\" style=\"cursor: pointer; float: left;\" alt=\"\"/><div id=\"{$this->name}_nametoggle\" style=\"float: left;cursor: pointer;\">{$this->title}</div></div>\n";
				echo "</div>\n";
			}
			else {
				echo "<div class=\"{$this->titleCssClass}\"><div id=\"{$this->name}_name\" style=\"float: left;\">{$this->title}</div></div>\n";
				echo "</div>\n";
			}

			$js = <<<EOS
  $('#{$this->name}_toggle').click(function() {
    if ($('#{$this->name}_items').css('display') == 'none') {
    	$('#{$this->name}_toggle').attr('src', '{$baseUrl}/images/submenu_open.png');
    	$('#{$this->name}_items').fadeIn("slow");
    }
    else {
    	$('#{$this->name}_toggle').attr('src', '{$baseUrl}/images/submenu_close.png');
    	$('#{$this->name}_items').fadeOut("fast");
    }
  });
  $('#{$this->name}_nametoggle').click(function() {
    if ($('#{$this->name}_items').css('display') == 'none') {
    	$('#{$this->name}_toggle').attr('src', '{$baseUrl}/images/submenu_open.png');
    	$('#{$this->name}_items').fadeIn("slow");
    }
    else {
    	$('#{$this->name}_toggle').attr('src', '{$baseUrl}/images/submenu_close.png');
    	$('#{$this->name}_items').fadeOut("fast");
    }
  });
EOS;

			Yii::app()->clientScript->registerScript('fadesubmenu_' . $this->name, $js);

		}
	}
}