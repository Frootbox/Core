$(function() {

    /**
     *
     */
    $('#month').change(function() {
        $(this).parents('form').trigger('submit');
    });

    /*
    if ($('#pickupDay option').length == 0) {
        $('#month option:selected').next().prop('selected', true);
        $('#month').trigger('change');
    }
    */
});
