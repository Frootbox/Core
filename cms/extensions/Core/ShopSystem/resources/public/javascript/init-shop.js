$(function ( ) {

    if (localStorage.getItem('fbxShopCartItemCount') === null) {
        localStorage.setItem('fbxShopCartItemCount', 0);
    }

    if (localStorage.getItem('fbxShopCartItemCount') > 0) {

        $('.fbxShopCartItemCounter').show();
        $('.fbxShopCartItemCounter').html(localStorage.getItem('fbxShopCartItemCount'));

        $('.fbxShopCartVisibleOnItemCount').show();
    }
    else {
        $('.fbxShopCartItemCounter').hide();
        $('.fbxShopCartVisibleOnItemCount').hide();
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
    $(document).on('click', 'form.shopsystem-product-add-to-cart button', function ( event ) {
        $(this).attr('data-clicked', '1');
    });

    $(document).on('submit', 'form.shopsystem-product-add-to-cart', function ( event ) {

        event.preventDefault();

        let button = $(this).find('[data-clicked="1"]');

        if (button.length > 0) {
            $(this).append('<input class="auto-append" type="hidden" name="' + button.attr('name') + '" value="' + button.attr('value') + '" />');
        }

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
                else if (typeof response.continue != "undefined") {
                    window.location.href = response.continue;
                }
                else if (typeof response.success != "undefined") {
                    alert(response.success);
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


