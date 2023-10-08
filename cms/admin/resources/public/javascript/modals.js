$(function ( ) {

    /**
     *
     */
    $(document).on('click', '[data-modal]', function(event) {

        if ($(event.target).parents('a').length == 1 && $(event.target).parents('a').attr('href') != $(this).attr('href')) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        
        $('#genericModal .modal-title').html($(this).attr('data-title') ? $(this).attr('data-title') : $(this).html());

        var size = $(this).attr('data-size');

        var action = ($(this).attr('href') && $(this).attr('href') != '#') ? $(this).attr('href') : $(this).attr('data-modal');
        

        $.ajax({
            url : action,
            headers: {          
                Accept: "application/json; charset=utf-8",     
            },
            success : function ( html ) {

                // Close all dropdown
                $('.dropdown-toggle').dropdown('hide');

                $('#genericModal .modal-body').remove();
                $('#genericModal .modal-footer').remove();

                $('#genericModal #genericModalContent').html(html);

                $('#genericModal .modal-dialog').removeClass('modal-xl modal-lg modal-sm');

                if (size) {
                    $('#genericModal .modal-dialog').addClass('modal-' + size);
                }
                 
                $('#genericModal').modal('show');

                $('#genericModal').on('shown.bs.modal', function () {
                    $('#genericModal .modal-body input').filter(function() { return $(this).val() == ""; }).first().focus();
                });


                initElements();
            },
            error : function ( xhr ) {
                
            	toastr.error(xhr.responseText);
            }
        });

    });

    /*
    $('#genericModal').on('shown.bs.modal', function () {
        alert("SHOWN!!!");
    })
     */
});