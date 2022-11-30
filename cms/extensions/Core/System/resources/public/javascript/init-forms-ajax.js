$(function() {

    /**
     * Init ajax submittable forms
     */
    $(document).on('submit', 'form.ajax', function(event) {

        event.preventDefault();
        event.stopImmediatePropagation();

        var form = $(this);

        $.ajax({
            url: form.attr('action'),
            data: form.serialize(),
            type: 'POST',
            headers: {
                Accept: "application/json; charset=utf-8",
            },
            success: function(response) {

                alert("OK");

            },
            error : function ( xhr ) {

                alert(xhr.responseText);
            }
        });
    });
});
