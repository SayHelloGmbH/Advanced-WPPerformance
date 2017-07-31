(function ($) {

    let $container = '';
    let $checkbox = '';

    $(function () {

        $container = $('#wp-admin-bar-awpp_adminbar-criticalcss');
        $checkbox = $container.find('input#awpp-check-criticalcss');
        if (!$container.length || !$checkbox.length) {
            return;
        }

        const styles = $('head').find('link[rel=stylesheet]');

        $checkbox.on('change', function () {

            $.each(styles, function (index, e) {

                let $e = $(e);
                let link;

                if ($e.attr('data-href') === undefined) {
                    link = $e.attr('href');
                } else {
                    link = $e.attr('data-href');
                }

                if (link.includes('admin-bar') || link.includes('adminbar') || link.includes('wp-includes/')) {
                    return true;
                }

                if ($checkbox.prop('checked')) {
                    $e.attr('data-href', link);
                    $e.attr('href', '');
                } else {
                    $e.attr('href', link);
                }
            });
        });
    });

})(jQuery);