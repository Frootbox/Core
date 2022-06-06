$(function ( ) {

    $('.Partial.Core.ShopSystem.ShopSystem.Filter a.show-filter').click(function ( event ) {

        event.preventDefault();

        let fieldId = $(this).attr('data-field');

        if ($('.filter-body[data-field="' + fieldId + '"]').hasClass('visible')) {
            $('.filter-body').removeClass('visible');
        }
        else {
            $('.filter-body').removeClass('visible');
            $('.filter-body[data-field="' + fieldId + '"]').addClass('visible');
        }
    });
});
