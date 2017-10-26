(function ($) {
	$(function () {
		$('#monitoring-set-psikey').each(function () {

			const $parent = $(this);
			const $button = $(this).find('button');

			$button.on('click', function () {

				let data = [];
				$parent.find('input').each(function () {
					const name = $(this).attr('name');
					const val = $(this).val();
					data.push(`${name}=${val}`);
				});

				$button.prop('disabled');
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: data.join('&')
				}).done(function (data) {

					console.log(data);

					if (data['type'] === null || data['type'] !== 'success') {

						/**
						 * error
						 */

						let msg_content = data['message'];
						if (msg_content === '' || msg_content === undefined) {
							msg_content = 'error';
						}

						alert(msg_content);

					} else {

						/**
						 * Success
						 */

						//location.reload();
					}
					$button.prop('disabled', false);
				});
			});
		});
	});
})(jQuery);