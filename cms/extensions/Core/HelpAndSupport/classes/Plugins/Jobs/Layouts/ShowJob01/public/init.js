$(function( ) {

    $('.sticky-top').css('top', ($('#banner').outerHeight() + 30) + 'px');

    /**
     *
     */
    $(document).on('submit', '#applyForm', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $.ajax({
            url: $(this).attr('action'),
            dataType: 'json',
            type: 'post',
            data: $(this).serialize(),
            success: function ( result ) {
                window.location.href = result.redirect;
            }
        });
    });

    /**
     *
     */
    $('[data-toggle="tooltip"]').tooltip();
});
