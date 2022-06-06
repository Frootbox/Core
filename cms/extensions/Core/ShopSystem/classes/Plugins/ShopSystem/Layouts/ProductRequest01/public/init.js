$(function ( ) {

    /**
     * Submit form via ajax
     */
    $(document).on('submit', 'form#requestForm', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        var form = $(this);

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            type: 'POST',
            headers: {
                Accept: "application/json; charset=utf-8",
            },
            success: function ( response ) {

                console.log(response);

                if (typeof response.redirect != "undefined") {
                    window.location.href = response.redirect;
                }
                else {
                    alert("Ihre Daten wurden gesendet. Vielen Dank.");

                    form.find('input[type="text"], textarea').val('');

                    $('.filesReceiver[data-field]').html('');
                    $('.dz-preview').remove();
                }
            },
            error: function ( xhr ) {

                if (xhr.responseText.charAt(0) != '{') {
                    alert(xhr.responseText);
                    return;
                }
                var response = $.parseJSON(xhr.responseText);

                alert(response.error);
            }
        });
    });
});
