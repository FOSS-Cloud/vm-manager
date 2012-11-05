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

jQuery.widget("ui.dualselect", {

	container: null,
	orig: null,

	sbl: null, // Searchbox Left
	sbr: null, // Searchbox Right
	lc: null, // left container
	lh: null, // left header text
	ll: null, // left list
	lt: null, // left toolbar
	rc: null,
	rh: null,
	rl: null,
	rt: null,

	_init_components: function()
	{
		this.lc = document.createElement("DIV");
		this.rc = document.createElement("DIV");

		this.lh = document.createElement("DIV");
		this.rh = document.createElement("DIV");

		this.ll = document.createElement("UL");
		this.rl = document.createElement("UL");

		this.lt = document.createElement("DIV");
		this.rt = document.createElement("DIV");

		this.sbl = document.createElement("DIV");
		this.sbr = document.createElement("DIV");

		this._init_search(this.sbl, this.ll);
		this._init_search(this.sbr, this.rl);
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

		this.rl.style.height = _calculateHeight(this.element) + "px";
		this.ll.style.height = this.rl.style.height;
	},

	_init_elements: function()
	{
		var self = this;

		this.element.children().each(function(i) {
			var li = '<li value="' + this.value + '" class="' + this.className + '" index="' + i + '" onclick="$(this).toggleClass(\'selected\')">' +
			         this.innerHTML +
				 '</li>';
			$(this.selected ? self.rl : self.ll).append(li);
		});

	},

	_init: function()
	{
		this.container = $('<div id="' + this.element.attr("id") + '" class="dual-select"></div>');
		this.container.data("dualselect", this.element.data("dualselect"));

		var self = this;

		this._init_components();
		this._init_elements();

		this.container.append(this.lc).append(this.rc);

		this.lc.appendChild(this.lh);
		this.lc.appendChild(this.ll);
		this.lc.appendChild(this.sbl);
		this.lc.appendChild(this.lt);

		this.rc.appendChild(this.rh);
		this.rc.appendChild(this.rl);
		this.rc.appendChild(this.sbr);
		this.rc.appendChild(this.rt);

		$(this.lh).html(this.options.leftHeader).addClass("ui-state-default ui-corner-top");

		$(this.lt).html(
			'<button class="ds_search">Search</button>' +
			'<button class="ds_add">Add</button>'
		).addClass("toolbar").children(".ds_add").button({icons: {primary: "ui-icon-arrowthick-1-e"}}).click(function() { self.add(); });

		$(this.lt).children(".ds_search").button({icons: {primary: "ui-icon-search"}}).click(function() {
			self.showSearch(self.sbl, self.ll);
		});

		$(this.rh).html(this.options.rightHeader).addClass("ui-state-default ui-corner-top");

		$(this.rt).html(
			'<button class="ds_search">Search</button>' +
			'<button class="ds_remove">Remove</button>'
		).addClass("toolbar").children(".ds_remove").button({icons: {primary: "ui-icon-arrowthick-1-w"}}).click(function() { self.remove(); });

		$(this.rt).children(".ds_search").button({icons: {primary: "ui-icon-search"}}).click(function() {
			self.showSearch(self.sbr, self.rl);
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
			var value = this.value;
			if (selected) {
				self.orig.children('option[value=' + value + ']').attr('selected', 'selected');
			}
			else {
				self.orig.children('option[value=' + value + ']').removeAttr('selected');
			}
		});
	},

	/* METHODS */

	/**
	 * Adds an element from the left to the right list.
	 */
	add: function(indices) {
		this._update(this.notSelected('.selected:visible'), true);
		this.move(this.notSelected('.selected:visible').removeClass('selected'), this.rl);
		if (!this.orig.attr('multiple')) {
			$(this.lt).children(".ds_add").css('display', 'none');
		}
	},

	_sort: function(elements)
	{

	},

	/** Moves elements to list */
	move: function(elements, list) {
		if (this.options.sorted)
		{
			var l2 = $(list).children();

			if (l2.length == 0)
				return $(list).append(elements);

			elements.each(function() {
				var i = 0;
				var a = this.getAttribute("index")*1;

				while (a > (l2.eq(i).attr("index")*1))
					i = i + 1;

				if (i == l2.length)
					$(list).append(this);
				else
					l2.eq(i).before(this);
			});
		} else
			$(list).append(elements);
	},

	/**
	 * Removes an element from the right to the left list.
	 */
	remove: function() {
		this._update(this.selected('.selected:visible'), false);
		this.move(this.selected(".selected:visible").removeClass('selected'), this.ll);
		if (!this.orig.attr('multiple')) {
			$(this.rt).children(".ds_remove").css('display', 'none');
			$(this.lt).children(".ds_add").css('display', 'inline');
		}
	},

	/**
	 * Returns Selected Elements as a jQuery Object.
	 */
	selected: function(selector) {
		return $(this.rl).children(selector);
	},

	notSelected: function(selector) {
		return $(this.ll).children(selector);
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

jQuery.ui.dualselect.getter = [ "selected", "notSelected", "values", "selectedText", "selectedHtml" ];
