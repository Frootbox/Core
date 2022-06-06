$(function() {

    /**
     * Submit checkout form
     */
    $('form.ajax-checkout-form').submit(function ( event ) {

        event.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            type: 'POST',
            dataType: 'json',
            success: function ( response ) {

                if (typeof response.continue != "undefined") {
                    window.location.href = response.continue;
                }

            },
            error: function ( xhr ) {

                if (typeof xhr.responseJSON == "undefined") {
                    alert(xhr.responseText);
                    return;
                }

                if (typeof xhr.responseJSON.error != "undefined") {
                    alert(xhr.responseJSON.error);
                }
            }
        });


    });


    /**
     *
     */
    $(document).on('click', 'a.coupon-dismiss', function ( event ) {

        event.preventDefault();

        $.ajax({
            headers: {
                Accept: "application/json; charset=utf-8",
            },
            url: $(this).attr('href'),
            success: function ( response ) {

                $('#itemsTableReceiver').html(response.html);
            },
            error: function ( xhr ) {

                if (typeof xhr.responseJSON.error != "undefined") {
                    alert(xhr.responseJSON.error);
                }
                else {
                    alert(xhr.responseText);
                }
            }
        })
    });
});
