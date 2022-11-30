$(function() {

    /**
     * Submit form
     */
    $('#eventBookingForm').submit(function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            headers: {
                Accept: "application/json; charset=utf-8",
            },
            data: $(this).serialize(),
            success: function ( response ) {

                var response = $.parseJSON(response);

                window.location.href = response.redirect;
            },
            error: function ( xhr ) {

                console.log(xhr.responseText);

                if (xhr.responseText.charAt(0) == '{') {
                    var response = $.parseJSON(xhr.responseText);
                    alert(response.error);
                }
                else {
                    alert("Die Buchung konnte nicht fortgesetzt werden.");
                }
            }
        })
    });
});
