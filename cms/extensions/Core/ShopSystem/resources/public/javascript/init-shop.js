$(function ( ) {

    if (localStorage.getItem('fbxShopCartItemCount') === null) {
        localStorage.setItem('fbxShopCartItemCount', 0);
    }

    if (localStorage.getItem('fbxShopCartItemCount') > 0) {
        $('.fbxShopCartItemCounter').show();
        $('.fbxShopCartItemCounter').html(localStorage.getItem('fbxShopCartItemCount'));
    }
    else {
        $('.fbxShopCartItemCounter').hide();
    }


    /**
     * Add product to cart via link click
     */
    $(document).on('click', 'a.shopsystem-product-add-to-cart', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $.ajax({
            url: $(this).attr('href'),
            success: function ( response ) {

                $('.fbxShopCartItemCounter').show();
                $('.fbxShopCartItemCounter').html(response.shopcart.items);
                localStorage.setItem('fbxShopCartItemCount', response.shopcart.items);

                if (typeof response.popup != 'undefined') {

                    $('#shopcartProductAddModal').remove();

                    $('body').append(response.popup.html);

                    $('#shopcartProductAddModal').modal('show');
                }
                else {
                    window.location.href = response.continue;
                }
            },
            error: function ( response ) {
                alert("Das hat leider nicht geklappt.");
            }
        });
    });

    /**
     * Add product to cart via form submit
     */
    $(document).on('submit', 'form.shopsystem-product-add-to-cart', function ( event ) {

        event.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function ( response ) {

                $('.fbxShopCartItemCounter').show();
                $('.fbxShopCartItemCounter').html(response.shopcart.items);

                localStorage.setItem('fbxShopCartItemCount', response.shopcart.items);

                if (typeof response.popup != 'undefined') {

                    $('#shopcartProductAddModal').remove();

                    $('body').append(response.popup.html);

                    $('#shopcartProductAddModal').modal('show');
                }
                else {
                    window.location.href = response.continue;
                }
            },
            error: function ( response ) {
                alert("Das hat leider nicht geklappt.");
            }
        });
    });


    window.setInterval(function ( ) {

        $.ajax({
            url: settings.serverpath + 'static/Ext/Core/ShopSystem/Basket/getState',
            dataType: 'JSON',
            success: function ( response ) {

                localStorage.setItem('fbxShopCartItemCount', response.shopcart.items);
                $('.fbxShopCartItemCounter').html(response.shopcart.items);
            }
        });
    }, 30000);
});


