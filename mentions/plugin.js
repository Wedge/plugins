/**
 * WeMentions' plugins javascript file
 *
 * @package Dragooon:WeMentions
 * @author Shitiz "Dragooon" Garg <Email mail@dragooon.net> <Url http://smf-media.com>
 * @author René-Gilles "Nao" Deberdt
 * @copyright 2013, Shitiz Garg & René-Gilles Deberdt
 * @license
 *		Original code by Dragooon
 *			Licensed under the New BSD License (3-clause version)
 *			http://www.opensource.org/licenses/BSD-3-Clause
 *		Modified code by Nao
 *			Licensed under the Wedge license (sorry about that...)
 *			http://wedge.org/license/
 * @version 1.0
 */

$(function ()
{
	$.fn.getCursorPosition = function ()
	{
		var el = $(this).get(0);
		if ('selectionStart' in el)
			return el.selectionStart;
		else if ('selection' in document)
		{
			el.focus();
			var Sel = document.selection.createRange(), SelLength = document.selection.createRange().text.length;
			Sel.moveStart('character', -el.value.length);
			return Sel.text.length - SelLength;
		}
		return 0;
	};
	$.fn.selectRange = function (start, end)
	{
		return this.each(function ()
		{
			if (this.setSelectionRange)
			{
				this.focus();
				this.setSelectionRange(start, end);
			}
			else if (this.createTextRange)
			{
				var range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	};

	var mentioning = false,
		memberName = '',
		$container = '',
		start = -1,
		keyCodeFired = false;

	$('textarea.editor, div.rich').on('keydown', function (e)
	{
		// Keyboard navigation for container
		if (mentioning && memberName.length >= (window.minChars || 3))
		{
			// Moving down!
			if (e.which == 40)
			{
				if ($container.find('.auto_suggest_hover').length > 0)
					$container.find('.auto_suggest_hover').mouseleave().next().mouseenter();
				else
					$container.find('div:first').mouseenter();
			}
			// Moving up!
			else if (e.which == 38)
			{
				if ($container.find('div').first().hasClass('auto_suggest_hover'))
					$container.find('.auto_suggest_hover').mouseleave().end().find('div').last().mouseenter();
				else if ($container.find('.auto_suggest_hover').length > 0)
					$container.find('.auto_suggest_hover').mouseleave().prev().mouseenter();
				else
					$container.find('div:first').mouseenter();
			}
			// Selecting!
			else if (e.which == 13)
				$container.find('.auto_suggest_hover').click();
			else
				return true;

			// Kind of an ugly hack, couldn't prevent the next event firing.
			keyCodeFired = true;
			return false;
		}
	})
	.on('keyup', function (e)
	{
		if (keyCodeFired)
			return keyCodeFired = false;

		var $editor = $(this),
			pos = $editor.getCursorPosition() - 1,
			val = $editor.val();

		if (!mentioning && val.charAt(pos) == '@')
		{
			mentioning = true;
			start = pos + 1;
		}

		if (mentioning && (val.charAt(start - 1) != '@' || /\s/.test(val.charAt(pos))))
		{
			mentioning = false;
			if ($container.length)
				$container.remove();
			return;
		}

		if (!mentioning || (pos < start + (window.minChars || 3) - 1)) // Ensure we don't get too many possible results.
		{
			if ($container.length)
				$container.remove();
			return true;
		}

		memberName = val.slice(start, pos + 1);

		$.post(
			weUrl('action=suggest;' + we_sessvar + '=' + we_sessid),
			{ search: memberName },
			function (XMLDoc)
			{
				if ($container.length)
					$container.remove();

				if (!$('item', XMLDoc).length)
					return;

				$container = $('<div/>').addClass('auto_suggest');

				$('item', XMLDoc).each(function (index, item)
				{
					$('<div/>')
						.text($(item).text())
						.attr('data-id', $(item).attr('id'))
						.hover(function () { $(this).toggleClass('auto_suggest_hover'); })
						.click(function ()
						{
							// Escape @ to \@
							var name = $(this).text().replace('@', '\\@');

							$editor.val(val.slice(0, start) + name + ' ' + val.slice(pos + 1));

							var caretPos = start + name.length + 1;
							$editor.selectRange(caretPos, caretPos);

							$container.remove();
						})
						.appendTo($container);
				});

				var caretPos = function ($input)
				{
					if ($editor.hasClass('rich')) // WYSIWYG..?
						oEditorHandle_message.insertText('<img alt=":)" class="smiley cool_gif" src="' + we_theme_url + '/images/blank.gif" onresizestart="return false;" title=":)">');

					// More accurate solution. IE-only.
					if (is_ie8down)
					{
						$input[0].focus();
						var range = document.selection.createRange(), elOffset = $input.offset();
						return [
							range.boundingLeft - elOffset.left,
							range.boundingTop + $input[0].scrollTop + document.documentElement.scrollTop + parseInt(self.getComputedStyle('fontSize')) - elOffset.top
						];
					}

					var $test = $('<div/>').addClass('rich').insertAfter($editor);

					$.each(
						['max-width', 'margin', 'border', 'padding', 'outline', 'direction', 'font',
						 'line-height', 'white-space', 'letter-spacing', 'word-spacing', 'text-transform', 'word-wrap'],
						function () { $test.css(this, $input.css(this)); }
					);

					$test.outerWidth($editor.outerWidth()).text($input[0].value.slice(0, $input[0].selectionEnd));

					var x, y = $test[0].offsetHeight, count = 0, pixel = '';
					var t = $.now();
					while (y == $test[0].offsetHeight && ++count)
						$test.append($('<span style="display:inline-block;width:10px;height:1em;font:1px/1 a">&nbsp;</span>'));
					$test.find(':last-child').remove();

					count = --count * 10;
					pixel = '';
					while (y == $test[0].offsetHeight && ++count)
						$test.append($('<span style="display:inline-block;width:1px;height:1em;font:1px/1 a">&nbsp;</span>'));

					x = Math.min($input.width(), $test.width() - count + 1);
					y = Math.min($input.height(), y);
					$test.remove();

					return [x, y];
				},

				position = $editor.offset(),
				caret = caretPos($editor);
				position.left += caret[0];
				position.top += caret[1] * 16 / 17;
				$container.offset(position).appendTo('body').show();
			}
		);
	});
});
