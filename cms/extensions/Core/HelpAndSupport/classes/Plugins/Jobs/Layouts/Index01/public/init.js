$(function ( ) {

    $(document).on('change', 'form.refresh-jobs-form input', function ( event ) {
        $(this).parents('form').trigger('submit');
    });

    $(document).on('click', 'form.refresh-jobs-form label', function ( event ) {
        event.preventDefault();
        event.stopImmediatePropagation();

        $(this).find('input')
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
