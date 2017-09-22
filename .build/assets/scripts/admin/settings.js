(function ($) {

    $(function () {

        const $container = $('.awpp-settings-wrap');
        const $loadCSS = $container.find('input#loadcss');
        const $ServerPush = $container.find('select#serverpush');

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
    });
})(jQuery);