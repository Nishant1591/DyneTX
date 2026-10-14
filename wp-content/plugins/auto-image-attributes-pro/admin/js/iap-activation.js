jQuery(document).ready(function ($) {

	function iaffpro_installBasePlugin() {
		$.ajax({
			url: iaffpro_activation.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'iaffpro_maybe_install_base_plugin',
				security: iaffpro_activation.nonce,
			},
			success: function (response) {
				$('#iaffpro-spinner').hide();
				$('#iap-base-plugin-check').css('border-left-color', response.data.border_color);
				$('#iaffpro-base-plugin-status').html(response.data.message);
			},
			error: function () {
				$('#iaffpro-spinner').hide();
                $('#iap-base-plugin-check').css('border-left-color', iaffpro_activation.status_error.border_color);
				$('#iaffpro-base-plugin-status').html(iaffpro_activation.status_error.message);
			},
		});
	}

	iaffpro_installBasePlugin();
});