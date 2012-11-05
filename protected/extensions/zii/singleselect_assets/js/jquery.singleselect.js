/*
 * Dual Select JQuery Widget
 *
 * @author Giancarlo Bellido
 *
 * Options:
 *
 * 	sorted       Boolean. Determines whether the list is sorted or not.
 *  leftHeader
 *  rightHeader
 *
 */

jQuery.widget("ui.singleselect", {

	container: null,
	orig: null,

	sb: null, // Searchbox
	c: null, // container
	h: null, // header text
	l: null, // list
	t: null, // toolbar

	_init_components: function()
	{
		this.c = document.createElement("DIV");
		this.h = document.createElement("DIV");
		this.l = document.createElement("UL");
		this.t = document.createElement("DIV");
		this.sb = document.createElement("DIV");

		this._init_search(this.sb, this.l);
	},

	_init_search: function(sb, list)
	{
		var self = this;

		$(sb).addClass('toolbar')
		     .html(
		           "<span style=\"float:right\" class=\"ds_close\"></span>" +
		           "<input type='text' />"
			   )
		     .hide()
		     .children().eq(1).keyup(function() {
		     		self.search(this, list);
		     }).prev().click(function() {
		     		self.closeSearch(sb, list);
		     });
	},

	_init_size: function()
	{
		var _calculateHeight = function(obj) {
			var size = obj.attr("size");
			return (size) ? size * 27 : 125;
		};

		this.l.style.height = _calculateHeight(this.element) + "px";
	},

	_init_elements: function()
	{
		var self = this;

		this.element.children().each(function(i) {
			var li = $('<li value="' + this.value + '" class="' + this.className + '" index="' + i + '">' + // ' onclick="$(this).toggleClass(\'selected\')">' +
			         this.innerHTML +
				 '</li>');
			li.click(function() {
				self.toggle(this);
			});
			$(self.l).append(li);
		});

	},

	_init: function()
	{
		this.container = $('<div id="' + this.element.attr("id") + '" class="single-select"></div>');
		this.container.data("singleselect", this.element.data("singleselect"));

		var self = this;

		this._init_components();
		this._init_elements();

		this.container.append(this.c);

		this.c.appendChild(this.h);
		this.c.appendChild(this.l);
		this.c.appendChild(this.sb);
		this.c.appendChild(this.t);

		$(this.h).html(this.options.header).addClass("ui-state-default ui-corner-top");

		$(this.t).html(
			'<button class="ds_search">Search</button>'
		).addClass("toolbar");

		$(this.t).children(".ds_search").button({icons: {primary: "ui-icon-search"}}).click(function() {
			self.showSearch(self.sb, self.l);
		});

		this._init_size();
		//this.element.before(this.container).remove();
		this.orig = this.element.before(this.container);
		this.orig.css('display', 'none');

		return this.element = this.container;
	},

	_update: function(elements, selected) {
		var self = this;
		elements.each(function() {
			var a = $(this);
			var b = a.val();
			var c = a.attr('value');
			var d = this.value;
			var val = $(this).val();
			if ($(this).hasClass('selected')) {
				self.orig.children('option[value=' + val + ']').attr('selected', 'selected');
			}
			else {
				self.orig.children('option[value=' + val + ']').removeAttr('selected');
			}
		});
	},

	/* METHODS */

	_sort: function(elements)
	{

	},


	/**
	 * Returns Selected Elements as a jQuery Object.
	 */
	selected: function() {
		return $(this.l).children('.selected');
	},

	notSelected: function(selector) {
		return $(this.l).children(selector);
	},

	toggle: function(element) {
		if (!this.orig.attr('multiple')) {
			var a = $(this.l);
			var b = a.children();
			$(this.l).children().each(function () {
				var c = $(this);
				$(this).removeClass('selected');
			});
			$(element).addClass('selected');
		}
		else {
			$(element).toggleClass('selected');
		}
		this._update($(this.l).children());
	},

	/**
	 * Filters list children Based on input's value.
	 */
	search: function(input, list)
	{
		var regex = new RegExp(input.value, "i");

		$(list).children().show().each(function() {
			if (!regex.test($(this).text())) {
				$(this).hide();
			}
		});
	},

	/**
	 * Shows all the elements in the list previosly filters
	 */
	resetSearch: function(list)
	{
		$(list).children().show();
	},

	showSearch: function(sb, list) {
		if ($(sb).is(':visible'))
		{
			this.closeSearch(sb, list);
		} else
		{
			$(sb).show();
			$(list).height($(list).height() - $(sb).outerHeight());
		}
	},

	closeSearch: function(sb, list) {
		$(list).height($(list).height() + $(sb).outerHeight());
		$(sb).hide();
		this.resetSearch(list);
	},

	/* Returns Array containing values of selected items */
	values: function() {
		var v = [];
		this.selected().each(function() {
			v.push(this.getAttribute("value"));
		});

		return v;
	},

	/* Returns array containing the text of the selected items */
	selectedText: function() {
		var v = [];
		this.selected().each(function() { v.push($(this).text()); });
		return v;
	},

	/* Returns array containing the html of the selected items */
	selectedHtml: function() {
		var v = [];
		this.selected().each(function() { v.push($(this).html()); });
		return v;
	}

});

jQuery.ui.singleselect.getter = [ "selected", "notSelected", "values", "selectedText", "selectedHtml" ];
