function fbxShopSystemUpdateShipping()
{
    $.ajax({
        url: fbxShoppingUrisUpdateShipping,
        data: {
            personal: {
                country: $('#country').val()
            },
            shipping: {
                type: $('input[name="shipping[type]"]:checked').val(),
                country: $('#shipping_country').val()
            },
        },
        success: function ( response ) {

            $('#itemsTableReceiver').html(response.html);
        }
    })
}

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

                if (typeof response.redirect != "undefined") {
                    window.location.href = response.redirect;
                }

            },
            error: function ( xhr ) {

                if (typeof xhr.responseJSON == "undefined") {
                    alert('Unbekannter Fehler.');
                    return;
                }

                if (typeof xhr.responseJSON.error != "undefined") {
                    alert(xhr.responseJSON.error);
                }
            }
        });
    });

    $('#shipping_country, #country').change(function ( ) {
        fbxShopSystemUpdateShipping();
    });

    $('input[name="shipping[type]"]').change(function () {

        if ($(this).val() == 'shipToAddress') {

            $('.shipping-form input, .shipping-form select').each(function ( ) {

                if ($(this).attr('data-required') == 'required') {
                    $(this).prop('required', true);
                }
            });

            $('.shipping-form').show();
        }
        else {

            $('.shipping-form input, .shipping-form select').each(function ( ) {

                if ($(this).prop('required')) {
                    $(this).attr('data-required', 'required');
                }
            });

            $('.shipping-form input, .shipping-form select').prop('required', false);
            $('.shipping-form').hide();
        }

        fbxShopSystemUpdateShipping();
    });

    $('input[name="shipping[type]"]').filter(':checked').trigger('change');



});
