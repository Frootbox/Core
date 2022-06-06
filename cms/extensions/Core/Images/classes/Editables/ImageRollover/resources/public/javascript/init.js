$(function ( ) {

    /**
     *
     */
    $(document).on('mouseenter', 'img[data-swap]', function ( event ) {

        if ($(this).attr('data-swap').length == 0) {
            return;
        }

        let swap = $(this).attr('data-swap');
        let src = $(this).attr('src');

        $(this).attr('src', swap);
        $(this).attr('data-swap', src);
    });

    /**
     *
     */
    $(document).on('mouseleave', 'img[data-swap]', function ( event ) {

        if ($(this).attr('data-swap').length == 0) {
            return;
        }

        let swap = $(this).attr('data-swap');
        let src = $(this).attr('src');

        $(this).attr('src', swap);
        $(this).attr('data-swap', src);
    });
});
