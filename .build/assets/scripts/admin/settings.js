(function ($) {

    $(function () {

        const $container = $('.awpp-settings-wrap');
        const $loadCSS = $container.find('select#loadcss');
        const $ServerPush = $container.find('select#serverpush');
        const $trigger = $container.find('select#serverpush, select#loadcss');

        $trigger.each(function () {

            const $e = $(this);
            let event = 'click';
            const $sub_container = $e.parents('td').find('.settings-sub');

            if ($e.prop('nodeName') === 'SELECT') {
                event = 'change';
            }

            $e.on(event, function () {
                const val = $(this).val();
                const id = $(this).attr('id');
                $sub_container.each(function () {
                    if ($(this).hasClass(`settings-sub-${id}-${val}`)) {
                        $(this).slideDown();
                    } else {
                        $(this).slideUp();
                    }
                });
            });
        });

        /*
        $loadCSS.on('click', function () {
            const $ccss_container = $(this).next('.settings-critical-css-container');
            if ($(this).prop('checked')) {
                $ccss_container.slideDown();
            } else {
                $ccss_container.slideUp();
            }
        });

        $ServerPush.on('change', function () {
            const $htaccess_container = $(this).next('.settings-htaccess-push-container');
            if ($(this).val() === 'htaccess') {
                $htaccess_container.slideDown();
            } else {
                $htaccess_container.slideUp();
            }
        });
        */
    });
})(jQuery);