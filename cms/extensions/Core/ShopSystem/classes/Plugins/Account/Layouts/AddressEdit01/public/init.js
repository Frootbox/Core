$(function ( ) {

    $('#updateForm').submit(function ( event ) {

        event.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            method: 'post',
            success: function ( response ) {

            },
            error: function ( xhr ) {

                let body = xhr.responseText;

                if (body.charAt(0) != '{') {
                    alert(body);
                    return;
                }

                var response = $.parseJSON(body);

                alert(response.error);
            }
        });
    });
});
