(function ($) {

    $(function () {
        const preload = 'preload';
        const $styles = $(`head link[as=style][rel=${preload}]`);

        $styles.each(function (i, e) {
            $(e).attr('rel', 'stylesheet');
            //console.log($(e).attr('id') + ': parsed');
        })
    });
})(jQuery);