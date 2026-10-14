jQuery(document).ready(function($){
	var nonce = $('meta[name="content_mask_csrf_token"]').attr('content');
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': nonce
		}
	});

	/**
	 * Meta Box Change Functions
	 */
	$('#content_mask_meta_box [name="content_mask_method"]').on('change', function(){
		if( $(this).val() == 'download' ){
			$('.content-mask-expiration-div').fadeIn();
		} else {
			$('.content-mask-expiration-div').fadeOut();
		}
	});

	if( $('#content_mask_enable').is(':checked') ){
		$('#postdivrich').css({'height': 0, 'overflow': 'hidden'}).addClass('hide-overflow');
	}

	$('#content-mask-settings').on('click', '.content-mask-check', function(){
		if( !$(this).is(':checked') ){
			$('#postdivrich').animate({'height': 416, 'overflow': 'visible'}).removeClass('hide-overflow');
			$('.gutenberg').addClass('content-mask-unchecked');
			$('.gutenberg .edit-post-visual-editor, .gutenberg .edit-post-text-editor').fadeIn();
			$('.content-mask-notice').fadeOut();
		}
	});

	/**
	 * Insert Notices to Alert User of Actions
	 */
	function contentMaskMessage( classes, message ){
		$('#content-mask-message').remove(); //Prevent Duplicates

		$('body').append('<div id="content-mask-message"><div class="content-mask-message-content '+ classes +'">'+ message +'</div></div>');
		setTimeout(function(){
			$('#content-mask-message').fadeOut(function(){
				$(this).remove();
			});
		}, 1500);
	}

	/**
	 * Control Modal (currently only for Delete Mask)
	 */
	function insertContentMaskModal( postID, title, classes ){
		$('#content-mask-modal-container').remove(); // Prevent Duplicates

		var modal = '<div id="content-mask-modal-container">'+
						'<div class="content-mask-modal '+ classes +'">'+
							'<svg class="icon content-mask-svg svg-trash" title="Delete Mask" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>'+
							'<h2>'+ title +'</h2>'+
							'<p>Remove this Content Mask?</p>'+
							'<button data-intent="confirm" data-action="delete-mask" data-id="'+ postID +'">Yes</button><button data-intent="cancel">No</button>'+
						'</div>'+
					'</div>';

		$('body').addClass('blur');
		$('body').append( modal );
	}

	function insertContentMaskRolesModal( postID, title, classes ){
		$('#content-mask-modal-container').remove(); // Prevent Duplicates

		var data = {
			'action': 'fetch_editable_role_permissions',
			'postID': postID,
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response){
			var roles = response.message;

			var html = '<form class="role-permissions" style="width: 132px; margin: 0 auto;">';
				Object.keys(roles).forEach(function(role) {
					var checked = (roles[role] == true) ? 'checked="checked"' : '';
					html += '<label class="permission" style="display:flex; width: 132px; align-items: center; user-select: none;">' +
								'<input type="checkbox" name="content_mask_role_permissions[]" value="'+ role +'" '+ checked +'>' +
								'<span style="text-transform: capitalize;">'+ role +'</span>' +
							'</label><br>';
				});
			html += '</form>';

			var modal = '<div id="content-mask-modal-container">'+
							'<div class="content-mask-modal '+ classes +'">'+
								'<svg class="icon content-mask-svg svg-user-check" title="Edit Role Permissions" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>' +
								'<h2>'+ title +'</h2>'+
								'<p>Edit Roles that this content should be <strong>hidden</strong> from:</p>'+
								html +
								'<button style="background: #0095ee; color: #fff;" data-intent="confirm" data-action="update-role-permissions" data-id="'+ postID +'">Finish</button><button data-intent="cancel" style="background: #eee !important; color: #444 !important;">Cancel</button>'+
							'</div>'+
						'</div>';

			$('body').addClass('blur');
			$('body').append( modal );
		}, 'json');
	}

	function insertContentMaskConditionsModal( postID, title, classes ){
		$('#content-mask-modal-container').remove(); // Prevent Duplicates

		var data = {
			'action': 'fetch_editable_condition_permissions',
			'postID': postID,
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response){
			var roles = response.message;

			var html = '<form class="condition-permissions" style="width: 132px; margin: 0 auto;">';
				Object.keys(roles).forEach(function(role) {
					var checked = (roles[role] == true) ? 'checked="checked"' : '';
					html += '<label class="permission" style="display:flex; width: 132px; align-items: center; user-select: none;">' +
								'<input type="checkbox" name="content_mask_condition_permissions[]" value="'+ role +'" '+ checked +'>' +
								'<span style="text-transform: capitalize;">'+ role.replace(/_/g, ' ') +'</span>' +
							'</label><br>';
				});
			html += '</form>';

			var modal = '<div id="content-mask-modal-container">'+
							'<div class="content-mask-modal '+ classes +'">'+
								'<svg class="icon content-mask-svg svg-user-check" title="Edit Condition Permissions" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>' +
								'<h2>'+ title +'</h2>'+
								'<p>Edit Conditions that, when met, this content should be <strong>hidden</strong>:</p>'+
								html +
								'<button style="background: #0095ee; color: #fff;" data-intent="confirm" data-action="update-condition-permissions" data-id="'+ postID +'">Finish</button><button data-intent="cancel" style="background: #eee !important; color: #444 !important;">Cancel</button>'+
							'</div>'+
						'</div>';

			$('body').addClass('blur');
			$('body').append( modal );
		}, 'json');
	}

	$('#content-mask-pages').on( 'click', '.refresh-transient', function(){
		$('.content-mask-message').remove();

		var	$clicked   = $(this),
			maskURL    = $clicked.closest('tr').find('.info .meta a').text(),
			transient  = $clicked.attr('data-transient'),
			postID     = $clicked.closest('tr').attr('data-attr-id'),
			expiration = $clicked.attr('data-expiration'),
			$methodDiv = $clicked.closest('tr').find('td.method div'),
			expirationReadable = $clicked.attr('data-expiration-readable');

		$methodDiv.addClass('content-mask-reloading');

		var data = {
			'action': 'refresh_transient',
			'postID': postID,
			'expiration': expiration,
			'transient': transient,
			'maskURL': maskURL,
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {
			$('.content-mask-message').remove(); // Prevent weird interaction with existing messages
			var classes;

			if( response.status == 200 ){
				$('#content-mask-pages tr td.status').each(function(){
					if( $(this).closest('tr').find('.info .meta a').text() == maskURL ){
						$(this).find('.transient-expiration').removeClass('expired').text( expirationReadable );
					}
				});
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}

			$methodDiv.removeClass('content-mask-reloading');
			contentMaskMessage( classes, response.message );
		}, 'json');

		return false;
	});

	/**
	 * Insert Popup for Role Management
	 */
	$('#content-mask-pages').on( 'click', '.edit-role-permissions', function(){
		var	$clicked   = $(this),
			$row       = $(this).closest('tr'),
			postID     = $clicked.closest('tr').attr('data-attr-id'),
			title      = $clicked.closest('tr').find('.info strong').text();
		
		insertContentMaskRolesModal( postID, title, 'info' );

		return false;
	});

	/**
	 * Insert Popup for Conditions Management
	 */
	$('#content-mask-pages').on( 'click', '.edit-condition-permissions', function(){
		var	$clicked   = $(this),
			$row       = $(this).closest('tr'),
			postID     = $clicked.closest('tr').attr('data-attr-id'),
			title      = $clicked.closest('tr').find('.info strong').text();
		
		insertContentMaskConditionsModal( postID, title, 'info' );

		return false;
	});

	/**
	 * Insert Popup for Delete Mask
	 */
	$('#content-mask-pages').on( 'click', '.remove-mask', function(){
		var	$clicked   = $(this),
			$row       = $(this).closest('tr'),
			postID     = $clicked.closest('tr').attr('data-attr-id'),
			title      = $clicked.closest('tr').find('.info strong').text();
		
		insertContentMaskModal( postID, title, 'warning' );

		return false;
	});

	/**
	 * Handle Delete Mask Intent
	 */
	$('body').on('click', '[data-intent="cancel"]', function(){
		$('body').removeClass('blur');
		$('#content-mask-modal-container').fadeOut(function(){
			$(this).remove();
		});
		return false;
	});

	$('body').on('click', '[data-action="update-role-permissions"], [data-action="update-condition-permissions"]', function(e){
		e.preventDefault();

		var	$clicked   = $(this),
			postID     = $clicked.attr('data-id'),
			$row       = $('#content-mask-pages').find('tr[data-attr-id="'+ postID +'"]'),
			act        = $clicked.attr('data-action');

		var action = (act.includes('-role-')) ? 'update_editable_role_permissions' : 'update_editable_condition_permissions';
		var serial = (act.includes('-role-')) ? '.role-permissions' : '.condition-permissions';

		var data = {
			'action': action,
			'postID': postID,
			'values': $clicked.closest('.content-mask-modal').find(serial).serialize()
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response){
			var response = JSON.parse(response);

			$('body').removeClass('blur');
			$('#content-mask-modal-container').fadeOut(function(){
				$(this).remove();
			});

			if( response.status == 200 ){
				classes = 'info';
			} else {
				classes = 'error';
			}

			contentMaskMessage( classes, response.message );
		});
	});


	$('body').on('click', '[data-action="delete-mask"]', function(){
		var	$clicked   = $(this),
			postID     = $clicked.attr('data-id'),
			$row       = $('#content-mask-pages').find('tr[data-attr-id="'+ postID +'"]');

		var data = {
			'action': 'delete_content_mask',
			'postID': postID
		};

		$('body').removeClass('blur');
		$('#content-mask-modal-container').fadeOut(function(){
			$(this).remove();
			$row.addClass('deleting');
		});

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {
			var classes;

			if( response.status == 200 ){
				$row.fadeOut();
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				$row.addClass('deleting');
				classes = 'error';
			}
			
			contentMaskMessage( classes, response.message );
		}, 'json');

		return false;
	});

	/**
	 * Toggle Enabled/Disabled State
	 */
	$('#content-mask-pages, #the-list .column-content-mask').on( 'click', '.method div, .content-mask-method svg', function(){
		var	$clicked     = $(this),
			restoreIcon  = $clicked.attr('class'),
			stateController,
			postID;

		if( $clicked.closest('td').hasClass('method') ){
			// Content Mask Admin
			postID = $clicked.closest('tr').attr('data-attr-id');
			stateController = $clicked.closest('tr');
		} else {
			// Post/Page Edit List
			postID = $clicked.closest('tr').attr('id').replace('post-', '');
			stateController = $clicked.closest('.content-mask-method');
		}

		var currentState = stateController.attr('data-attr-state');
		var newState     = currentState == 'enabled' ? 'disabled' : 'enabled';

		$clicked.attr('class', 'content-mask-reloading');

		var data = {
			'action': 'toggle_content_mask',
			'postID': postID,
			'newState': newState,
			'currentState': currentState,
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {
			var classes;

			if( response.status == 200 ){
				$clicked.attr('class', restoreIcon);
				stateController.attr('data-attr-state', newState).toggleClass('disabled enabled');
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}

			$clicked.closest('div').removeClass('content-mask-reloading');

			contentMaskMessage( classes, response.message );
		}, 'json');
	});

	$('.content-mask-admin .content-mask-input').on('change', 'input[type="text"]', function(){
		var $input             = $(this),
			$label             = $input.closest('label'),
			optionName         = $input.attr('name'),
			optionValue        = $input.val(); // Use the input's current value
	
		var data = {
			'action': 'update_content_mask_option',
			'option': optionName,
			'value': optionValue, // Changed from currentState to optionValue
			'label': '[Go Back] Button Label',
		};

		$input.addClass('content-mask-saving'); // Changed class from reloading to saving for clarity
	
		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];
	
		$.post(ajaxurl, data, function(response) {			
			var classes = ''; // Ensure classes is defined in the function scope
			if( response.status == 200 ){
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}
			
			$input.removeClass('content-mask-saving');
			$label.find('.content-mask-value').text(response.newValue); // Assuming you want to show the saved value
	
			contentMaskMessage(classes, response.message);
		}, 'json');
	});

	/**
	 * Toggle General Options
	 */
	$('.content-mask-admin .content-mask-checkbox').on( 'click', '.content-mask-check', function(){
		var	$clicked     = $(this),
			$label       = $clicked.closest('label'),
			currentState = $label.attr('data-attr'),
			optionName   = $label.find('input[type="checkbox"]').attr('name'),
			optionDisplayName = $label.find('.display-name').attr('aria-label').replace('Enable ', '');

		var data = {
			'action': 'toggle_content_mask_option',
			'optionName': optionName,
			'currentState': currentState,
			'optionDisplayName': optionDisplayName,
		};

		$clicked.addClass('content-mask-reloading');

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {			
			if( response.status == 200 ){
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}
			
			if( optionName == 'content_mask_tracking' ){
				$('#content-mask-list').removeClass('tracking-enabled tracking-disabled');
				$('#content-mask-list').addClass('tracking-' + response.newState);
			}

			$clicked.removeClass('content-mask-reloading');
			$clicked.closest('.content-mask-option').find('.content-mask-value').text( response.newState );
			$label.attr('data-attr', response.newState );

			contentMaskMessage( classes, response.message );
		}, 'json');
	});

	/**
	 * Update Code Editors
	 */
	$('#content-mask-advanced button, #content-mask-scripts-styles button').on( 'click', function(){
		var	$clicked = $(this),
			$wrap    = $clicked.closest('.option').find('.code-edit-wrapper'),
			editor   = $clicked.attr('data-editor'),
			value    = window[editor].codemirror.getValue(),
			label    = $clicked.text().replace('Save ', '');
			target   = $clicked.attr('data-target');

		var data = {
			'action': 'update_content_mask_option',
			'option': target,
			'value': value,
			'label': label,
		};

		$wrap.addClass('loading');

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {			
			if( response.status == 200 ){
				classes = 'info';
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}

			contentMaskMessage( classes, response.message );
			$wrap.removeClass('loading');
			
			$wrap.addClass('saved');
			setTimeout(function(){
				$wrap.removeClass('saved');
			}, 1500);
		}, 'json');		
	});

	/**
	 * Dynamically Load more Content Masked Pages/Posts/CPTS
	 */
	$('#content-mask #load-more-masks').on( 'click', function(){
		var $tbody = $('#content-mask-pages').find('tbody');

		$tbody.append('<tr class="content-mask-temp"><td><div class="content-mask-spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></td></tr>');

		var data = {
			'action': 'load_more_pages',
			'offset': $tbody.find('tr').length,
		};

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response){
			$('.content-mask-temp').remove();
			$tbody.append( response.message );
			contentMaskMessage( 'info', 'Loading Completed' );

			if( response.notice != null && response.notice == 'no remaining' ){
				$('#load-more-masks').fadeOut(function(){
					$(this).remove();
				});
			}
		}, 'json');
	});

	/**
	 * Admin Panel Navigation
	 */
	$('#content-mask nav').on('click', 'li', function(){
		var $link  = $(this).find('a'),
			target = $link.attr('data-target'); 
		
		// Set Active
		$('#content-mask nav li').each(function(){
			$(this).find('a').removeClass('active');
		});
		$link.addClass('active');

		// Show/Hide Panels
		$('#content-mask .content-mask-panel.active').fadeOut(function(){
			$(this).removeClass('active');

			$('#'+target).fadeIn(function(){
				$(this).addClass('active');
			});
		});
	});

	/**
	 * Admin Mobile Menu
	 */

	$('#mobile-nav-toggle').on('click', function(){
		$('#header-nav').slideToggle();
	});

	 /**
	  * Create New Mask from Admin Panel
	  */
	$('#content-mask .new-mask .method-like div').on('click', function(){
		var	$clicked   = $(this),
			$row       = $clicked.closest('tr'),
			$fields    = $row.find('[name]'),
			$methodDiv = $clicked.closest('tr').find('td.method-like div'),
			data       = {action: 'create_new_content_mask'};

		$methodDiv.addClass('content-mask-reloading');

		$fields.each(function(){
			data[$(this).attr('name')] = $(this).val();
		});

		// Add nonce
		data.nonce = ajax_object.content_mask_nonces[data.action];

		$.post(ajaxurl, data, function(response) {
			$('.content-mask-message').remove(); // Prevent weird interaction with existing messages
			var classes;

			if( response.status == 200 ){
				classes = 'info';

				// Refresh Page
				setTimeout(function(){
					window.location.reload();
				}, 750);
			} else if( response.status == 400 || response.status == 403 ){
				classes = 'error';
			}

			$methodDiv.removeClass('content-mask-reloading');
			contentMaskMessage( classes, response.message );
		}, 'json');

		return false;
	});
});

jQuery(window).on('load',function(){
	jQuery(document).ready(function($){
		if( $('.content-mask-enabled-page .override-gutenberg-notice' ).length > 0 ){
			var contentMaskNotice     = $('.override-gutenberg-notice' ).html();
			var contentMaskNoticeHTML = '<div class="components-notice notice notice-alt content-mask-notice notice-info">'+ contentMaskNotice +'</div>';

			$('.components-notice-list').prepend( contentMaskNoticeHTML );
		}
	});
});

function sanitizeTitle(str){
	str = str.replace(/^\s+|\s+$/g, ''); // trim
	str = str.toLowerCase();

	// remove accents, swap ñ for n, etc
	var from = "àáäâèéëêìíïîòóöôùúüûñçěščřžýúůďťň·/_,:;";
	var to   = "aaaaeeeeiiiioooouuuuncescrzyuudtn------";

	for (var i=0, l=from.length ; i<l ; i++)
	{
		str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
	}

	str = str.replace('.', '-') // replace a dot by a dash 
		.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
		.replace(/\s+/g, '-') // collapse whitespace and replace by a dash
		.replace(/-+/g, '-') // collapse dashes
		.replace( /\//g, '' ); // collapse all forward-slashes

	return str;
}