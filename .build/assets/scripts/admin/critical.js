(function ($) {

    $(function () {


        const $wrapper = $('.awpp-settings-wrap');
        if (!$wrapper.length) {
            return;
        }
        const $checkbox = $wrapper.find('input#loadcss');
        const $critical_textarea = $wrapper.find('textarea#criticalcss');
        const $critical_container = $critical_textarea.parents('tr');

        showhide_criticalcss();
        $checkbox.on('change', function () {
            showhide_criticalcss();
        });

        function showhide_criticalcss() {
            console.log($checkbox.prop('checked'));
            if ($checkbox.prop('checked')) {
                $critical_container.show();
            } else {
                $critical_container.hide();
            }
        }

    });

})(jQuery);