(function ($) {

    $(function () {

        const preload_support = function () {
            try {
                return document.createElement("link").relList.supports("preload");
            } catch (e) {
                return false;
            }
        };

        if (preload_support() || navigator.userAgent.includes('Chrome')) {
            return;
        }

        const $scripts = $('head link[rel=preload][as=style]');

        $scripts.each(function (i, e) {
            $(e).attr('rel', 'stylesheet');
            //console.log($(e).attr('id'));
        });
    });

})(jQuery);