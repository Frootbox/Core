$(function ( ) {

    var pathname = window.location.href;

    /**
     *
     */
    $('a').each(function ( ) {

        var href = $(this).attr('href');

        if (typeof href == 'undefined' || href.length == 0) {
            return;
        }

        if (href.substr(0,1) == "#") {

            if ($(this).attr('data-norewrite')) {
                return;
            }

            $(this).attr('href', pathname.split('#')[0] + href);

            return;
        }

        if (!href.match(/^http:/) && !href.match(/^https:/)) {
            return;
        }

        var match = '^' + settings.serverpath_protocol;

        var regex = new RegExp(match);

        if (href.match(regex)) {
            return;
        }

        // Mark link
        $(this).attr('target', '_blank');
    });

    /**
     *
     */
    $(document).on('click', '[data-href]', function(event) {



        if (typeof isEditing != "undefined" && isEditing) {
            return;
        }

        if (event.target.tagName == 'A') {
            event.stopImmediatePropagation();
            return;
        }

        if ($(event.target).parents('a').length) {
            event.stopImmediatePropagation();
            return;
        }

        if (typeof $(this).attr('data-href') != 'undefined' && $(this).attr('data-href').length > 0) {
            var href = $(this).attr('data-href');
        }
        else {

            var links = $(this).find('[href]');

            if (links.length == 0) {
                return;
            }

            var href = links.first().attr('href');
        }

        var match = '^' + settings.serverpath_protocol;
        var regex = new RegExp(match);

        if (href.charAt(0) == '/' || href.match(regex) || (!href.match(/^http:/) && !href.match(/^https:/))) {
            window.location.href = href;
        }
        else {
            window.open(href);
        }
    });

    let keymap = { };

    $(document).on('keydown', function ( event ) {

        var keyCode = event.key || event.keyCode;

        keymap[keyCode] = event.type == 'keydown';

        // Switch to edit mode
        if (keymap['e'] && keymap['Meta']) {

            let da = window.location.href.split('?');


            if (window.location.href.match(/edit/)) {

                if (typeof da[1] != 'undefined') {
                    var uri = settings.serverpath_protocol + settings.request + '?' + da[1];
                }
                else {
                    var uri = settings.serverpath_protocol + settings.request;
                }
            }
            else {

                if (typeof da[1] != 'undefined') {
                    var uri = settings.serverpath_protocol + 'edit/' + settings.request + '?' + da[1];
                }
                else {
                    var uri = settings.serverpath_protocol + 'edit/' + settings.request;
                }

            }

            window.location.href = uri;

            keymap = {};
        }
    });

    /**
     *
     */
    $(document).on('keyup', function ( event ) {

        var keyCode = event.key || event.keyCode;

        keymap[keyCode] = event.type == 'keydown';

        if (keymap[keyCode] === false) {
            delete keymap[keyCode];
        }
    });

    /**
     *
     */
    $(document).on('mouseenter', 'tr[data-href]', function ( event) {
        $(this).addClass('hovered');
    });

    /**
     *
     */
    $(document).on('mouseleave', 'tr[data-href]', function ( event) {
        $(this).removeClass('hovered');
    });
});
