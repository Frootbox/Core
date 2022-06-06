$(function ( ) {

    /**
     * Make slides full height
     */
    $(window).resize(function ( event ) {

        var height = $(window).height();


        $('.plugin.Core.Navigation.Teaser.Index11').each(function ( ) {

            $(this).find('.teaser').each(function ( ) {
                $(this).css('height', height + 'px');
            });
        });

    });

    $(window).trigger('resize');
});
