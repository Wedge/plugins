(function ($) {

	var errors = false, msg = 0;

	$("form#postmodify").submit(function() {
		var $that = $(this);

		show_ajax();
		$.post(
			$that.attr("action"),
			$that.serializeArray(),
			function (XMLDoc)
			{
				if (!XMLDoc);

				// Let Wedge handle the errors. This is hard enough as it is, plus, who likes to write Javascript?
				else if ($('errors', XMLDoc).length)
					window.location = $that.attr("action");

				else if ($('msg', XMLDoc).length)
				{
					var
						msg = $('msg', XMLDoc).text(),
						$new_page = $('#quickModForm');

					$.post(

						// Load the reply we've just posted. Fool me once...
						weUrl('topic=' + we_topic + '.new;ajaxqr'),

						// This asks Wedge to ignore the Ajax status, and load the index template for page indexes.
						{ infinite: true },

						function (html)
						{
							// Hide the message first so that we can properly animate it.
							var
								$html = $(html),
								$root = $html.find('.msg').hide().appendTo($new_page);

							// We're rebuilding scripts from the string response, and inserting them to force jQuery to execute them.
							// Please note that jQuery doesn't need to be reloaded, and script.js causes issues, so we'll avoid it for now.
							$new_page.append($html.filter('script:not([src*=jquery]):not([src*=script])'));

							// We have to re-run the event delayer, as it has new values to insert...
							// !! Is it worth putting it into its own function in script.js..?
							$('*[data-eve]', $new_page).each(function ()
							{
								var that = $(this);
								$.each(that.attr('data-eve').split(' '), function () {
									that.on(eves[this][0], eves[this][1]);
								});
							});

							// Update the num_replies counter so if the user replies more than once without refreshing, we don't get an error.
							document.forms.postmodify.elements['last'].value = $root.attr('id').slice(3);

							// And empty the post reply box
							$('#message', $that).val('');

							hide_ajax();
							$root.fadeIn();
						}
					);
				}
			}
		);

		if (!errors)
			return false;
	});

}) (jQuery);