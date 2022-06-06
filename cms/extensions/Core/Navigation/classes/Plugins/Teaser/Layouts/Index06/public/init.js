$(function ( ) {

    if ($('.plugin.Core.Navigation.Teaser.Index06 .height-max-100').length) {

        $(window).resize(function ( ) {

            let height = $(window).height();

            $('.plugin.Core.Navigation.Teaser.Index06 .height-max-100 .carousel-inner, .plugin.Core.Navigation.Teaser.Index06 .height-max-100 .carousel-item').css('max-height', height + 'px');
        });

        $(window).trigger('resize');
    }

});
