/**
 * DateInput
 *
 * Visual date picker script. Very compact at 1760 bytes minified and
 * gzipped. Based on jQuery Tools Dateinput
 *
 * Developed and customized/optimized for inclusion with Wedge plugins
 * by John "live627" Rayes.
 *
 * @version 0.2
 */


(function($)
{
	function Dateinput(input, uconf)
	{
		var
			conf = $.extend({
				yearRange: [-5, 10],
			}, uconf),
			now = $.now(),
			yearNow = now.getFullYear(),
			root,
			currYear, currMonth, currDay,
			value = input.attr('data-value') || conf.value || input.val() || now,
			min = input.attr('min') || conf.min,
			max = input.attr('max') || conf.max,
			opened,

			show = function(e) {

				if (input.attr('readonly') || input.attr('disabled') || opened)
					return;

				$('.cal').hide();
				opened = true;

				// set date
				setValue(value);

				root.animate(is_opera ? { opacity: 'show' } : { opacity: 'show', height: 'show' }, 150);
				onShow();
			},

			setValue = function(year, month, day, fromKey)
			{
				var date = integer(month) >= -1 ? new Date(integer(year), integer(month), integer(day == undefined || isNaN(day) ? 1 : day)) : year || value;

				if (date < min)
					date = min;
				else if (date > max)
					date = max;

				year = date.getFullYear();
				month = date.getMonth();
				day = date.getDate();

				// roll year & month
				if (month == -1) {
					month = 11;
					year--;
				} else if (month == 12) {
					month = 0;
					year++;
				}

				if (!opened)
					select(date);

				currMonth = month;
				currYear = year;
				currDay = day;

				// variables
				var tmp = new Date(year, month, 1 - (conf.firstDay || 0)), begin = tmp.getDay(),
					days = dayAm(year, month),
					prevDays = dayAm(year, month - 1),
					week;

				if (!fromKey)
				{
					monthSelector.empty();
					$.each(months, function(i, m) {
						if ((min && min < new Date(year, i + 1, 1)) && (max && max > new Date(year, i, 0)))
							monthSelector.append($('<option/>').text(m).attr('value', i));
					});

					yearSelector.empty();

					for (var i = yearNow + conf.yearRange[0]; i < yearNow + conf.yearRange[1]; i++)
						if ((min && min < new Date(i + 1, 0, 1)) && (max && max > new Date(i, 0, 0)))
							yearSelector.append($('<option/>').text(i).val(i));

					monthSelector.val(month).sb();
					yearSelector.val(year).sb();
				}

				// populate weeks
				weeks.empty();

				// !begin === 'sunday'
				for (var j = !begin ? -7 : 0, td, num; j < (!begin ? 35 : 42); j++) {

					td = $('<td/>').addClass('right');

					if (j % 7 === 0)
						week = $('<tr/>').appendTo(weeks);

					if (j < begin)
					{
						td.addClass('disabled');
						num = prevDays - begin + j + 1;
						thisDate = new Date(year, month - 1, num);
					}
					else if (j >= begin + days)
					{
						td.addClass('disabled');
						num = j - days - begin + 1;
						thisDate = new Date(year, month + 1, num);
					}
					else
					{
						num = j - begin + 1;
						thisDate = new Date(year, month, num);

						// chosen date
						if (isSameDay(value, thisDate))
							td.addClass('chosen');

						// today
						if (isSameDay(now, thisDate))
							td.addClass('today');

						// current
						if (isSameDay(date, thisDate))
							td.addClass('hove');
					}

					// disabled
					if ((min && thisDate < min) || (max && thisDate > max))
						td.addClass('disabled');

					td.appendTo(week).text(num).filter(':not(.disabled)').data('date', thisDate).click(function (e)
					{
						select($(this).data('date'), e);
					}).mouseover(function()
					{
						$(this).parent().parent().find('td').removeClass('hove');
						$(this).addClass('hove');
					}).mouseout(function (e)
					{
						$(this).removeClass('hove');
					});
				}
			},

			addDay = function(amount) {
				return setValue(currYear, currMonth, currDay + (amount || 1), true);
			},

			addMonth = function(amount) {
				var targetMonth		= currMonth + (amount || 1),
				daysInTargetMonth	= dayAm(currYear, targetMonth),
				targetDay			= currDay <= daysInTargetMonth ? currDay : daysInTargetMonth;

				return setValue(currYear, targetMonth, targetDay);
			},

			hide = function()
			{
				if (opened)
				{
					$(document).off('.d');

					// do the hide
					root.hide();
					opened = false;
				}
			},

		// @return amount of days in certain month
		dayAm = function (year, month)
		{
			return new Date(year, month + 1, 0).getDate();
		},

		integer = function (val)
		{
			return parseInt(val, 10);
		},

		isSameDay = function (d1, d2)
		{
			return d1.getFullYear() === d2.getFullYear() &&
				d1.getMonth() == d2.getMonth() &&
				d1.getDate() == d2.getDate();
		},

		parseDate = function (val)
		{
			if (val === undefined)
				return;

			if (val.constructor == Date)
				return val;

			if (typeof val == 'string')
			{

				// rfc3339?
				var els = val.split('-');
				if (els.length == 3)
					return new Date(integer(els[0]), integer(els[1]) - 1, integer(els[2]));

				// invalid offset
				if ( !(/^-?\d+$/).test(val) )
					return;

				// convert to integer
				val = integer(val);
			}

			var date = new Date;
			date.setDate(date.getDate() + val);
			return date;
		},

		select = function (date)
		{
			// current value
			value		= date;
			currYear	= date.getFullYear();
			currMonth	= date.getMonth();
			currDay		= date.getDate();

			// formatting
			input.val(date.getFullYear()
				+ '-' + pad(date.getMonth() + 1)
				+ '-' + pad(date.getDate()));

			// store value into input
			input.data('date', date);
			hide();
		},

		onShow = function (ev)
		{
			$(document).on('keydown.d', function(event)
			{
				if (opened)
					switch (event.keyCode)
					{
						case 9: case 27:
							hide();
							break; // hide on tab out // hide on escape
						case 13:
							var sel = $('td.hove:not(chosen)', root);
							if (sel[0])
								select(sel.data('date'));
							return false; // don't submit the form
							break; // select the value on enter
						case 33:
							addMonth(-1);
							break; // previous month/year on page up/+ ctrl
						case 34:
							addMonth(+1);
							break; // next month/year on page down/+ ctrl
						case 82: // r
							select(now);
							break; // current
						case 37: // left arrow
							addDay(-1);
							break;
						case 38: // up arrow
							addDay(-7);
							break; // -1 week
						case 39: // right arrow
							addDay(+1);
							break;
						case 40: // down arrow
							addDay(+7);
							break; // +1 week
					}
			});

			// click outside dateinput
			$(document).on('click.d', function(e)
			{
				var el = e.target;

				if ($(el).parents('.cal').length || el == input[0])
					return;

				hide(e);
			});
		},

		pad = function (number)
		{
			var r = String(number);
			if (r.length === 1)
				r = '0' + r;

			return r;
		};

		// use sane values for value, min & max
		value = parseDate(value);
		min = parseDate(min || new Date(yearNow + conf.yearRange[0], 1, 1));
		max = parseDate(max || new Date(yearNow + conf.yearRange[1] + 1, 1, -1));

		// root
		root = $('<div><div><div/></div><table><tbody/><tr/></table></div>')
			.addClass('cal');

		input.after(root).addClass('dateinput');

		// elements
		var
			$children = root.children(),
			title = $children.first(),
			$tableChildren = $children.eq(1).children(),
			days = $tableChildren.eq(0).append($('<tr/>')),
			weeks = $tableChildren.eq(1);

		// year & month selectors
		var monthSelector = $('<select/>').change(function() {
			setValue(yearSelector.val(), $(this).val());
			}),
			yearSelector = $('<select/>').change(function() {
				setValue($(this).val(), monthSelector.val());
			});
		title.append(monthSelector.add(yearSelector));

		for (var d = 0; d < 7; d++)
			days.add($('<th/>').addClass('right').text(daysShort[(d + (conf.firstDay || 0)) % 7]));

		if (value)
			select(value);

		if (!conf.editable)
		{
			input.on('focus.d click.d', show).keydown(function(e)
			{
				var key = e.keyCode;

				// open dateinput with navigation keys
				// h=72, j=74, k=75, l=76, down=40, left=37, up=38, right=39
				if (!opened && $([75, 76, 38, 39, 74, 72, 40, 37]).index(key) >= 0)
				{
					show();
					return e.preventDefault();
				}
				// clear value on backspace or delete
				else if (key == 8 || key == 46)
					input.val('');

				// allow tab
				return e.shiftKey || e.ctrlKey || e.altKey || key == 9 ? true : e.preventDefault();
			});
		}
	}

	$.fn.dateinput = function (conf)
	{
		return this.each(function ()
		{
			var $e = $(this), obj = $e.data('dateinput');

			if (!obj)
				$e.data('dateinput', new Dateinput($e, conf));
		});
	};

}) (jQuery);
