(function ($) {

    let $container = '';
    let $button = '';

    $(function () {

        $container = $('#wp-admin-bar-awpp_adminbar-minify');
        $button = $container.find('#awpp-clear-cache');
        if (!$container.length || !$button.length) {
            return;
        }

        const ajaxUrl = $button.attr('data-ajaxurl');
        const nonce = $button.attr('data-nonce');
        const action = 'awpp_do_clear_minify_cache';

        $button.on('click', function () {

            $container.addClass('loading');

            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: 'action=' + action + '&nonce=' + nonce
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
                    $container.find('.count').text('0');
                    $container.find('.size').text('0 B');
                }

                $container.removeClass('loading');
            });
        });
    });

})(jQuery);