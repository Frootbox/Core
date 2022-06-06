$(function ( ) {

    /**
     * Show article
     */
    $(document).on('click', '.plugin.Core.News.News.Index08 a.show-article', function ( event ) {

        event.preventDefault();
        event.stopImmediatePropagation();

        $.ajax({
            url : $(this).attr('href'),
            success : function ( html ) {
                $('#articleReceiver').html(html);
            }
        });
    });
});
