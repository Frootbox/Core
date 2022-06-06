$(function ( ) {
	
	/**
	 * 
	 */
	$(document).on('submit', 'form.ajax', function ( event ) {
		
		event.preventDefault();
		event.stopImmediatePropagation();

		console.log($(this));
		if (typeof $(this).attr('data-confirm') != 'undefined') {

			if (!confirm($(this).attr('data-confirm'))) {
				toastr.error('Aktion abgebrochen.');
				return;
			}
		}

		$.ajax({
			url : $(this).attr('action'),
            headers: {          
                Accept: "application/json; charset=utf-8",     
            },
			type : $(this).attr('method'),
			data : $(this).serialize(),
			success : function ( response ) {

				if (response === null) {
					response = { };
				}

				try {

					if (typeof response != 'object') {
						var response = JSON.parse(response);
					}
				}
				catch (e) {

					console.log(e);
					toastr.error('Invalid JSON response');
					return;
				}



				if (typeof response.redirect !== 'undefined') {
					window.location.href = response.redirect;
				}

				if (typeof response.replace !== 'undefined') {
					$(response.replace.selector).html(response.replace.html);

					initElements();
				}

				if (typeof response.replacements !== 'undefined') {

					$.each(response.replacements, function ( key, value ) {

						$(value.selector).html(value.html);

						initElements();
					});
				}

				if (typeof response.modalDismiss !== 'undefined') {
					$('#genericModal').modal('hide');
				}

				if (typeof response.success !== 'undefined') {
					toastr.success(response.success);
				}
				else {
					toastr.success('Die Daten wurden gespeichert.');
				}
			},
			error : function ( xhr ) {

				toastr.error(xhr.responseText);
			}
		});	
	});
	
	
	/**
	 * 
	 */
	$(document).on('change', '[data-onchange]', function ( event ) {
		
		alert($(this).attr('data-onchange'));
		$.ajax({
			url : $(this).attr('data-onchange'),
            headers: {          
                Accept: "application/json; charset=utf-8",     
            },
			type : 'post',
			data : $(this).serialize(),
			success : function ( html ) {
				
			}
		});
	});
});