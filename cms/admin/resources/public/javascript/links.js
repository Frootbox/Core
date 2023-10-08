$(function ( ) {

    /**
     *
     */
    $(document).on('click', '.ajax[href], .ajax[data-href]', function ( event ) {

        if (event.target.tagName == 'A' && !$(event.target).hasClass('ajax')) {
            return;
        }

        if ($(this)[0].tagName == 'TR') {
            if (event.target.tagName == 'svg' && $(event.target).parent('a').length) {

                let link = $(event.target).parent('a');

                if (link.attr('href').length) {
                    return;
                }
            }
        }

        if (event.target.tagName == "INPUT") {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        var url = $(this).attr('data-href') ? $(this).attr('data-href') : $(this).attr('href');

        if (typeof $(this).attr('data-confirm') != 'undefined') {

            if (!confirm($(this).attr('data-confirm'))) {
                toastr.error('Aktion abgebrochen.');
                return;
            }
        }

        var link = $(this);

        $.ajax({
            url : url,
            headers: {          
                Accept: "application/json; charset=utf-8",     
            },
            success : function ( response ) {

                try {

                    if (typeof response != 'object') {
                        var response = JSON.parse(response);
                    }
                }
                catch (e) {

                    console.log(e);
                    toastr.error("Invalid JSON Response");
                    return;
                }


                // Remove popover/popdown on success
                if (link.parents('.dropdown-menu.show').length > 0) {
                    link.parents('.dropdown-menu.show').removeClass('show');
                }

                if (typeof response.triggerLink !== 'undefined') {

                    if ($('#linkToTrigger').length > 0) {
                        $('#linkToTrigger').remove();
                    }

                    $('body').append('<a id="linkToTrigger" href="' + response.triggerLink + '" class="ajax">xxx</a>');
                    $('#linkToTrigger').trigger('click');
                }

                if (typeof response.redirect !== 'undefined') {
                    window.location.href = response.redirect;
                }

                if (typeof response.reload !== 'undefined') {
                    window.location.reload();
                }

                if (typeof response.anchor !== 'undefined') {
                    history.replaceState(null,null, window.location.href.split('#')[0] + '#' + response.anchor);
                }

                if (typeof response.replace !== 'undefined') {
                    $(response.replace.selector).html(response.replace.html);
                    initElements();
                }

                if (typeof response.remove !== 'undefined') {
                    $(response.remove).remove();
                }

                if (typeof response.append !== 'undefined') {

                    let html = $(response.append.selector).html();

                    $(response.append.selector).html(html + response.append.html);
                    initElements();
                }

                if (typeof response.replacements !== 'undefined') {

                    $.each(response.replacements, function ( key, value ) {

                        $(value.selector).html(value.html);

                        initElements();
                    });
                }

                if (typeof response.removeClass !== 'undefined') {
                    $(response.removeClass.selector).removeClass(response.removeClass.className)
                }

                if (typeof response.addClass !== 'undefined') {
                    $(response.addClass.selector).addClass(response.addClass.className)
                }

                if (typeof response.setIcon !== 'undefined') {
                    $(response.setIcon.selector).attr('data-icon', response.setIcon.icon)
                }

                if (typeof response.setIconPrefix !== 'undefined') {
                    $(response.setIconPrefix.selector).attr('data-prefix', response.setIconPrefix.prefix)
                }

                if (typeof response.modalDismiss !== 'undefined') {
                    $('#genericModal').modal('hide');
                }

                if (typeof response.fadeOut !== 'undefined') {

                    if (typeof response.fadeOut == 'object') {
                        $.each(response.fadeOut, function(key, selector) {
                            $(selector).fadeOut();
                        });
                    }
                    else {
                        $(response.fadeOut).fadeOut();
                    }
                }

                if ($(this).attr('data-preservestate')) {

                    var url = window.location.href.split('#')[0];

                    window.location.href = url + '#!ps-' + link.attr('id')
                }

                if (typeof response.success != 'undefined') {
                    toastr.success(response.success);
                }

                if (typeof response.error != 'undefined') {
                    toastr.error(response.error);
                }
            },
            error : function ( xhr ) {

                try {

                    var response = JSON.parse(xhr.responseText);
                }
                catch (e) {

                    toastr.error(xhr.responseText);
                    return;
                }

                if (typeof response.error == 'undefined') {
                    response.error = 'unknown';
                }

                if (response.error == 'NotLoggedIn') {

                    $.ajax({
                        url: response.modal,
                        success: function (html) {

                            $('#genericModal .modal-body').remove();
                            $('#genericModal .modal-footer').remove();

                            $('#genericModal .modal-header').after(html);

                            $('#genericModal').modal('show');

                            $('#genericModal').on('shown.bs.modal', function () {

                                $('#genericModal .modal-body input').filter(function() { return $(this).val() == ""; }).first().focus();
                            });
                        }
                    });
                }

                console.log(response);

            }
        });
    });
});