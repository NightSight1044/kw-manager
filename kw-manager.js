jQuery(function ($) {

    // Abrir media library al hacer click en "Cambiar imagen"
    $(document).on('click', '.kw-select-image', function (e) {
        e.preventDefault();
        var $btn     = $(this);
        var $field   = $btn.closest('.kw-image-picker');
        var $input   = $field.find('.kw-image-url');
        var $preview = $field.find('.kw-image-preview');

        var frame = wp.media({
            title: 'Seleccionar imagen',
            button: { text: 'Usar esta imagen' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.url;

            $input.val(url);
            $preview.empty().append($('<img>', { src: url, alt: '' }));

            // Marcar como modificado
            var $header = $field.closest('.kw-field').find('.kw-field-header');
            $header.find('.kw-badge').removeClass('kw-badge--default').addClass('kw-badge--modified').text('Modificado');
        });

        frame.open();
    });

    // Restablecer imagen al valor por defecto
    $(document).on('click', '.kw-reset-btn', function (e) {
        e.preventDefault();
        var $btn       = $(this);
        var defaultUrl = $btn.data('default');
        var $field     = $btn.closest('.kw-image-picker');
        var $input     = $field.find('.kw-image-url');
        var $preview   = $field.find('.kw-image-preview');

        $input.val(defaultUrl);

        if (defaultUrl) {
            $preview.empty().append($('<img>', { src: defaultUrl, alt: '' }));
        } else {
            $preview.html('<div class="kw-no-image">Sin imagen</div>');
        }

        var $header = $btn.closest('.kw-field').find('.kw-field-header');
        $header.find('.kw-badge').removeClass('kw-badge--modified').addClass('kw-badge--default').text('Por defecto');

        $btn.remove();
    });

    // Marcar campos de texto como modificados al escribir
    $(document).on('input', '.kw-text-input', function () {
        var $header = $(this).closest('.kw-field').find('.kw-field-header');
        $header.find('.kw-badge').removeClass('kw-badge--default').addClass('kw-badge--modified').text('Modificado');
    });

});
