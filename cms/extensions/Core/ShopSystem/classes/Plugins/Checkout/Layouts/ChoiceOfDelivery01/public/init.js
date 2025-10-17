$(function() {

    /**
     *
     */
    $('#month').change(function() {
        $(this).parents('form').trigger('submit');
    });

    if ($('table.calendar td a.day').length == 0) {

        let next = $('#month option:selected').next();

        if (next.length > 0) {
            next.prop('selected', true);
            $('#month').trigger('change');
        }
    }
});
