(function ($) {

	$(function () {

		const $container = $('.awpp-server-wrap ');
		const $select = $container.find('select#serverpush');
		const $htaccess_info = $container.find('#serverpush-htaccess-info');

		const $trigger = $container.find('a#scan-page');
		const $loader = $container.find('.loader');
		const action = $trigger.attr('data-action');
		const ajaxUrl = $trigger.attr('data-ajaxurl');

		showhide_htacess_info();
		$select.on('change', showhide_htacess_info);

		function showhide_htacess_info() {
			if ($select.val() === 'htaccess') {
				$htaccess_info.slideDown();
			} else {
				$htaccess_info.slideUp();
			}
		}


		$trigger.on('click', function () {
			$loader.fadeIn();

			jQuery.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: 'action=' + action
			}).done(function (data) {

				if (data['type'] === null || data['type'] !== 'success') {

					/**
					 * error
					 */

					let msg_content = data['message'];
					if (msg_content === '' || msg_content === undefined) {
						msg_content = 'Error';
					}

					alert(msg_content);
				} else {

					//console.log(data['add']);
					$.each(data['add'], function (key, data) {
						if (key !== 'styles' && key !== 'scripts') {
							return true;
						}
						const $list = $('ul.files-list#' + key);
						const $last = $list.find('li.no-items');
						$list.find('li').addClass('remove');
						$last.removeClass('remove');
						$.each(data, function (id, url) {
							if ($list.find('li#' + id).length) {
								$list.find('li#' + id).removeClass('remove');
								return true;
							}
							$(`<li id="${id}"><label title="${url}"><input type="checkbox" name="awpp-settings[serverpush_files][${key}][${id}]"/> ${id}</label></li>`).insertBefore($last);
						});
						$list.find('li.remove').remove();

					});
				}

				$loader.fadeOut();
			});
		});
	});
})(jQuery);