$(function ( ) {

    /**
     *
     */
    $(document).on('submit', '#loginForm', function ( event ) {

        event.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            method: 'post',
            dataType: 'json',
            success: function ( response ) {

                window.location.href = response.redirect;
            },
            error: function ( xhr ) {

                var response = JSON.parse(xhr.responseText);
                alert(response.error);
            }
        });
    });
});
