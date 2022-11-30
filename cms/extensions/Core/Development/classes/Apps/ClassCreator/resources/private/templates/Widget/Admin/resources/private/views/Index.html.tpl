{% verbatim %}{% set v = view.getViewHelper('\\Frootbox\\Admin\\Viewhelper\\GeneralPurpose') %}

<script nonce="{{ settings.nonce }}">
    $(function ( ) {

        $('#updateForm').submit(function ( event ) {

            event.preventDefault();
            event.stopImmediatePropagation();

            $.ajax({
                url: $(this).attr('action'),
                data: $(this).serialize(),
                type: 'POST',
                headers: {
                    Accept: "application/json; charset=utf-8",
                },
                success: function ( response ) {

                    $('figure[data-id="' + response.widget.id + '"]').replaceWith(response.widget.html);

                    $('#fbxEditorGenericModal').modal('hide');
                },
                error: function ( xhr ) {
                    alert(xhr.responseText);
                }
            });

        });
    });
</script>


<form id="updateForm" action="{{ widgetController.getAdminUrl('ajaxUpdate') }}" method="post">

    <div class="modal-header">
        <h5 class="modal-title">Widget konfigurieren</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">

        <div class="form-group">
            <label for="exampleInputEmail1">Email address</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
            <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
        </div>

    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Speichern</button>
    </div>

</form>
{% endverbatim %}