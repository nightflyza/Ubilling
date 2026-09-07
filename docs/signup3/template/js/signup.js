$(document).ready(function () {
    var lang = $('html').attr('lang') || 'en';

    $('.sn-select2').each(function () {
        var $el = $(this);
        $el.select2({
            width: '100%',
            language: lang,
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body),
            placeholder: $el.attr('data-placeholder') || '',
            allowClear: true
        });
        $el.on('select2:open', function () {
            window.setTimeout(function () {
                $('.select2-container--open .select2-search__field').trigger('focus');
            }, 0);
        });
    });

    $(document).on('keypress', '.select2-container', function (e) {
        var $container = $(this);
        var $el = $container.prev('select.sn-select2');
        var ch = '';

        if ($container.hasClass('select2-container--open')) {
            return;
        }
        if (e.ctrlKey || e.metaKey || e.altKey) {
            return;
        }
        if (e.which < 32) {
            return;
        }
        if (!$el.length) {
            return;
        }

        ch = String.fromCharCode(e.which);
        $el.select2('open');
        window.setTimeout(function () {
            $('.select2-container--open .select2-search__field').val(ch).trigger('input');
        }, 0);
        e.preventDefault();
    });
});
