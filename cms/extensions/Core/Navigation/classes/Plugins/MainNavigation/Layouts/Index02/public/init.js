var _MenuPopdownCloseCooldown = null;


$(function ( ) {

    $(document).on('mouseover', '.plugin.Core.Navigation.MainNavigation.Index02.popdown-menu a.link.has-children', function ( event ) {

        $('.plugin.Core.Navigation.MainNavigation.Index02.popdown-menu ul').hide();
        $(this).parents('.link-wrapper').find('ul').show();

    });

    $(document).on('mouseleave', '.plugin.Core.Navigation.MainNavigation.Index02.popdown-menu a.link.has-children', function ( event ) {

        var link = $(this);

        _MenuPopdownCloseCooldown = window.setTimeout(function ( ) {

            link.parents('.link-wrapper').find('ul').hide();
        }, 400);
    });

    $(document).on('mouseover', '.plugin.Core.Navigation.MainNavigation.Index02.popdown-menu ul', function ( event ) {
        window.clearTimeout(_MenuPopdownCloseCooldown);
    });

    $(document).on('mouseleave', '.plugin.Core.Navigation.MainNavigation.Index02.popdown-menu ul', function ( event ) {

        var menu = $(this);

        _MenuPopdownCloseCooldown = window.setTimeout(function ( ) {

            menu.hide();
        }, 400);
    });
});