function initElements ( ) {

	$('[data-toggle="popover"]').popover();
	$('[data-toggle="tooltip"]').tooltip();

	$(document).on('click', '[data-href]', function (event) {

		if ($(this).hasClass('ajax')) {
			return;
		}

		event.preventDefault();
		event.stopImmediatePropagation();

		window.location.href = $(this).attr('data-href');
	});


	// Initialise the table
	$('table[data-sort]').each(function ( ) {

		if (typeof $(this).attr('id') == 'undefined') {
			$(this).attr('id', 'tempTableId' + String(Math.floor(Math.random() * 100000)));
		}
	});

	$('table[data-sort]').tableDnD({
		dragHandle: '.handle',
		serializeParamName: 'row',
		onDragClass: 'dragging',
		onDrop: function (table, row) {

			$.ajax({
				url: $(table).attr('data-sort'),
				data: $.tableDnD.serialize(),
				success: function (response) {

					if (typeof response.success != 'undefined') {
						toastr.success(response.success);
					}
				},
				error: function ( xhr ) {
					toastr.error(xhr.responseText);
				}
			});
		}
	});

	$('input[data-role="tagsinput"]').tagsinput();
}

var checkLoginInterval = null;

$(function ( ) {

	initElements();

	checkLoginInterval = window.setInterval(function() {

		$.ajax({
			url: sessionCheckAjaxUrl,
			success: function ( response ) {


				if (!response.isLoggedIn) {

					window.clearInterval(checkLoginInterval);
					$('body').append(response.html);

				}
			}
		});
	}, 10000);
});
