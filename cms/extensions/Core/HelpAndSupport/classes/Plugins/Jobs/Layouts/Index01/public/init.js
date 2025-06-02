$(function ( ) {

    $(document).on('click', 'form.refresh-jobs-form input', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        let input = $(this);

        window.setTimeout(function() {
            input.parents('label').trigger('click');
        }, 100);

    });

    /**
     *
     */
    $(document).on('click', 'form.refresh-jobs-form label', function (event) {

        event.preventDefault();
        event.stopImmediatePropagation();

        let input = $(this).find('input');

        if (input.is(':checked')) {
            input.prop('checked', false);
        }
        else {
            input.prop('checked', true);
        }

        $(this).parents('form').trigger('submit');
    });

    $(document).on('submit', 'form.refresh-jobs-form', function ( event ) {

        event.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function ( response ) {
                $('#jobsReceiver').html(response.html);
            }
        });

    });

});
