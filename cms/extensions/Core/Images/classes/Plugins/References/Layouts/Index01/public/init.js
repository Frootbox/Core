$(function() {

    $(document).on('click', '.filter-bar button', function(event) {

        $(this).toggleClass('active');

        _ImagesReferencesUpdateVisibility();


    });

});

function _ImagesReferencesUpdateVisibility() {

    $('.reference-wrapper').show();

    $('.filter-bar button.active').each(function() {
        let tag = $(this).data('tag');

        $('.reference-wrapper:not(.' + tag + ')').hide();
    });
}
