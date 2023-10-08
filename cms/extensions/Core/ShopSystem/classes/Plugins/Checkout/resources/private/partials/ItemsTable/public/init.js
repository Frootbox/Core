$(function ( ) {

    /**
     *
     */
    $(document).on('change', '.set-amount', function ( event ) {

        $.ajax({
            headers: {
                Accept: "application/json; charset=utf-8",
            },
            url: $(this).attr('data-update'),
            data: {
                amount: $(this).val()
            },
            success: function ( response ) {

                $('#itemsTableReceiver').html(response.html);

                localStorage.setItem('fbxShopCartItemCount', response.shopcart.items);
                $('.fbxShopCartItemCounter').show();
                $('.fbxShopCartItemCounter').html(response.shopcart.items);
            }
        });
    });

    /**
     *
     */
    $(document).on('click', 'a.drop-item', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        if (!confirm('Soll dieses Produkt aus dem Warenkorb entfernt werden?')) {
            return;
        }

        $.ajax({
            url: $(this).attr('href'),
            type: 'GET',
            dataType: 'JSON',
            success: function ( response ) {

                $('#itemsTableReceiver').html(response.html);

                localStorage.setItem('fbxShopCartItemCount', response.shopcart.items);
                $('.fbxShopCartItemCounter').show();
                $('.fbxShopCartItemCounter').html(response.shopcart.items);
            }
        });
    });

    /**
     *
     */
    $(document).on('click', 'a.open-options', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $.ajax({
            url: $(this).attr('href'),
            type: 'GET',
            success: function ( html ) {

                $('.generic-shopcart-modal').remove();

                $('body').append('<div ' + 'class="generic-shopcart-modal">' + html + '</' + 'div>');
            }
        });

    });

    /**
     *
     */
    $(document).on('click', 'a.generic-modal-close', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $('.generic-shopcart-modal').remove();
    });
});
