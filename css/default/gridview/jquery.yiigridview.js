/**
 * jQuery Yii GridView plugin file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2010 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @version $Id: jquery.yiigridview.js 165 2010-05-01 14:12:28Z qiang.xue $
 */

;(function($) {
	/**
	 * yiiGridView set function.
	 * @param map settings for the grid view. Availablel options are as follows:
	 * - ajaxUpdate: array, IDs of the containers whose content may be updated by ajax response
	 * - ajaxVar: string, the name of the GET variable indicating the ID of the element triggering the AJAX request
	 * - pagerClass: string, the CSS class for the pager container
	 * - tableClass: string, the CSS class for the table
	 * - selectableRows: integer, the number of rows that can be selected
	 * - updateSelector: string, the selector for choosing which elements can trigger ajax requests
	 * - beforeAjaxUpdate: function, the function to be called before ajax request is sent
	 * - afterAjaxUpdate: function, the function to be called after ajax response is received
	 * - selectionChanged: function, the function to be called after the row selection is changed
	 */
	$.fn.yiiGridView = function(options) {
		return this.each(function(){
			var settings = $.extend({}, $.fn.yiiGridView.defaults, options || {});
			var $this = $(this);
			var id = $this.attr('id');
			if(settings.updateSelector == undefined) {
				settings.updateSelector = '#'+id+' .'+settings.pagerClass+' a, #'+id+' .'+settings.tableClass+' thead th a';
			}
			$.fn.yiiGridView.settings[id] = settings;

			if(settings.ajaxUpdate.length > 0) {
				$(settings.updateSelector).live('click',function(){
					$.fn.yiiGridView.update(id, {url: $(this).attr('href')});
					return false;
				});
			}

			var inputSelector='#'+id+' .'+settings.filterClass+' input, '+'#'+id+' .'+settings.filterClass+' select';
			// temporary fix for the bug of supporting live change in IE
			$(inputSelector).live($.browser.msie ? 'click keyup' : 'change', function(){
				var data = $.param($(inputSelector))+'&'+settings.ajaxVar+'='+id;
				$.fn.yiiGridView.update(id, {data: data});
			});

			if(settings.selectableRows > 0) {
				$('#'+id+' .'+settings.tableClass+' > tbody > tr').live('click',function(){
					if(settings.selectableRows == 1)
						$(this).siblings().removeClass('selected');
					$(this).toggleClass('selected');
					if(settings.selectionChanged != undefined)
						settings.selectionChanged(id);
				});
			}
		});
	};

	$.fn.yiiGridView.defaults = {
		ajaxUpdate: [],
		ajaxVar: 'ajax',
		pagerClass: 'pager',
		loadingClass: 'loading',
		filterClass: 'filters',
		tableClass: 'items',
		selectableRows: 1
		// updateSelector: '#id .pager a, '#id .grid thead th a',
		// beforeAjaxUpdate: function(id) {},
		// afterAjaxUpdate: function(id, data) {},
		// selectionChanged: function(id) {},
	};

	$.fn.yiiGridView.settings = {};

	/**
	 * Returns the key value for the specified row
	 * @param string the ID of the grid view container
	 * @param integer the row number (zero-based index)
	 * @return string the key value
	 */
	$.fn.yiiGridView.getKey = function(id, row) {
		return $('#'+id+' > div.keys > span:eq('+row+')').text();
	};

	/**
	 * Returns the URL that generates the grid view content.
	 * @param string the ID of the grid view container
	 * @return string the URL that generates the grid view content.
	 */
	$.fn.yiiGridView.getUrl = function(id) {
		return $('#'+id+' > div.keys').attr('title');
	};

	/**
	 * Returns the jQuery collection of the cells in the specified row.
	 * @param string the ID of the grid view container
	 * @param integer the row number (zero-based index)
	 * @return jQuery the jQuery collection of the cells in the specified row.
	 */
	$.fn.yiiGridView.getRow = function(id, row) {
		var settings = $.fn.yiiGridView.settings[id];
		return $('#'+id+' .'+settings.tableClass+' > tbody > tr:eq('+row+') > td');
	};

	/**
	 * Returns the jQuery collection of the cells in the specified column.
	 * @param string the ID of the grid view container
	 * @param integer the column number (zero-based index)
	 * @return jQuery the jQuery collection of the cells in the specified column.
	 */
	$.fn.yiiGridView.getColumn = function(id, column) {
		var settings = $.fn.yiiGridView.settings[id];
		return $('#'+id+' .'+settings.tableClass+' > tbody > tr > td:nth-child('+(column+1)+')');
	};

	/**
	 * Performs an AJAX-based update of the grid view contents.
	 * @param string the ID of the grid view container
	 * @param map the AJAX request options (see jQuery.ajax API manual). By default,
	 * the URL to be requested is the one that generates the current content of the grid view.
	 */
	$.fn.yiiGridView.update = function(id, options) {
		var settings = $.fn.yiiGridView.settings[id];
		$('#'+id).addClass(settings.loadingClass);
		options = $.extend({
			type: 'GET',
			url: $.fn.yiiGridView.getUrl(id),
			success: function(data,status) {
				$.each(settings.ajaxUpdate, function(i,v) {
					var id='#'+v,
						$d=$(data)
						$filtered=$d.filter(id);
					$(id).replaceWith( $filtered.size() ? $filtered : $d.find(id));
				});
				if(settings.afterAjaxUpdate != undefined)
					settings.afterAjaxUpdate(id, data);
				$('#'+id).removeClass(settings.loadingClass);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#'+id).removeClass(settings.loadingClass);
				alert(XMLHttpRequest.responseText);
			}
		}, options || {});
		if(options.data!=undefined && options.type=='GET') {
			options.url = $.param.querystring(options.url, options.data);
			options.data = {};
		}
		options.url = $.param.querystring(options.url, settings.ajaxVar+'='+id)

		if(settings.beforeAjaxUpdate != undefined)
			settings.beforeAjaxUpdate(id);
		$.ajax(options);
	};

	/**
	 * Returns the key values of the currently selected rows.
	 * @param string the ID of the grid view container
	 * @return array the key values of the currently selected rows.
	 */
	$.fn.yiiGridView.getSelection = function(id) {
		var settings = $.fn.yiiGridView.settings[id];
		var keys = $('#'+id+' > div.keys > span');
		var selection = [];
		$('#'+id+' .'+settings.tableClass+' > tbody > tr').each(function(i){
			if($(this).hasClass('selected'))
				selection.push(keys.eq(i).text());
		});
		return selection;
	};

})(jQuery);