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
     *
     */
    $(document).on('change', '#differentBillingRecipient', function(event) {

        let container = $('.different-billing-address');

        if ($('#differentBillingRecipient').is(':checked')) {

            container.find('input, select').prop('disabled', false);
            $('.different-billing-address').show();
        }
        else {

            container.find('input, select').prop('disabled', true);
            $('.different-billing-address').hide();
        }
    });

    $('#differentBillingRecipient').trigger('change');

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

        console.log($(this).val());

        $('.shipping-annotation').hide();
        $('.shipping-annotation[data-type="' + $(this).val() + '"]').show();

        $('.shipping-annotation input, .shipping-annotation select').each(function ( ) {

            if ($(this).prop('required')) {
                $(this).attr('data-required', 'required');
            }
        });

        $('.shipping-annotation input, .shipping-annotation select').prop('required', false);

        $('.shipping-annotation[data-type="' + $(this).val() + '"] input, .shipping-annotation[data-type="' + $(this).val() + '"] select').each(function() {

            if ($(this).attr('data-required') == 'required') {
                $(this).prop('required', true);
            }
        });

        fbxShopSystemUpdateShipping();
    });

    $('.shipping-annotation').hide();
    $('input[name="shipping[type]"]').filter(':checked').trigger('change');



});
